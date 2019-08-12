<?php
class Hooks_member extends Hooks
{
    /**
     * Target for the member:login_form form
     * 
     * @return void
     */
    public function member__login()
    {
        $username  = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password  = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $remember  = (bool) filter_input(INPUT_POST, 'remember', FILTER_SANITIZE_NUMBER_INT);
        $token     = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
        $return    = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_STRING);
        $referrer  = $_SERVER['HTTP_REFERER'];
        
        // validate form token
        if (!$this->tokens->validate($token)) {
            $this->flash->set('login_error', 'Invalid token.');
            URL::redirect($referrer);
        }

        // test for a valid login
        if (Auth::login($username, $password, $remember)) {
            $this->flash->set('login_success', 'Member logged in.');
            URL::redirect($return);
        } else {
            $this->flash->set('login_error', 'Invalid login.');
            $this->flash->set('old_values', $_POST);
            URL::redirect($referrer);
        }
    }


    /**
     * Logs a user out
     * 
     * @return void
     */
    public function member__logout()
    {
        $return = Request::get('return', Config::getSiteRoot());
        Auth::logout();

        URL::redirect(URL::assemble(Config::getSiteRoot(), $return));
    }


    /**
     * Target for the member:register_form form
     * 
     * @return void
     */
    public function member__register()
    {
        $referrer    = $_SERVER['HTTP_REFERER'];
        $token       = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
        $return      = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_STRING);
        $auto_login  = (bool) filter_input(INPUT_POST, 'auto_login', FILTER_SANITIZE_NUMBER_INT);

        // validate form token
        if (!$this->tokens->validate($token)) {
            $this->flash->set('login_error', 'Invalid token.');
            URL::redirect($referrer);
        }

        // is user logged in?
        if (Auth::isLoggedIn()) {
            URL::redirect($return);
        }
        
        // get configurations
        $allowed_fields = array_get($this->loadConfigFile('fields'), 'fields', array());
        
        // get username
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);

        // set up iterators and flags
        $submission = array(
            'username' => $username
        );
        
        // create member object
        $member = new Member(array());
        
        // adjust allowed fields to include username and password
        if (!isset($allowed_fields['username'])) {
            $allowed_fields['username'] = array();
        }
        if (!isset($allowed_fields['password'])) {
            $allowed_fields['password'] = array();
        }
        
        // loop through allowed fields, validating and storing
        foreach ($allowed_fields as $field => $options) {
            if (!isset($_POST[$field])) {
                // field wasn't set, skip it
                continue;
            }
            
            // set value
            $value = filter_input(INPUT_POST, $field, FILTER_SANITIZE_STRING);
            
            // don't store this value if `save_value` is set to `false`
            if (array_get($options, 'save_value', true)) {
                $member->set($field, $value);
            }
            
            // add to submissions, including non-save_value fields because this
            // is the list that will be validated
            $submission[$field] = $value;
        }
        
        // ensure UID
        $member->ensureUID(false);
        
        // user-defined validation
        $errors = $this->tasks->validate($submission);
        
        
        // built-in validation
        // --------------------------------------------------------------------
        
        // username
        if (!$username) {
            $errors['username'] = 'Username is required.';
        } elseif (!Member::isValidUsername($username)) {
            $errors['username'] = 'Username entered is not valid.';
        } elseif (Member::exists($username)) {
            $errors['username'] = 'Username is already in use.';
        }
        
        // password
        $password          = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $password_confirm  = filter_input(INPUT_POST, 'password_confirmation', FILTER_SANITIZE_STRING);

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }
        
        if (
            !isset($errors['password']) &&   // make sure password isn't already an error
            !is_null($password_confirm) &&   // a password_confirm field was entered
            $password !== $password_confirm  // password doesn't match password_confirm
        ) {
            $errors['password_confirmation'] = 'Passwords did not match.';
        }

        if (count($errors)) {
            // errors were found, set a flash message and redirect
            $this->flash->set('register_error', 'Member not created.');
            $this->flash->set('register_field_errors', $errors);
            
            // remove password and password_confirm from submission
            if (isset($submission['password'])) {
                unset($submission['password']);
            }
            
            if (isset($submission['password_confirmation'])) {
                unset($submission['password_confirmation']);
            }
            
            $this->flash->set('register_old_values', $submission);
            
            // redirect back to the form
            URL::redirect($referrer);
        } else {
            // set new member roles
            $member->set('roles', Helper::ensureArray($this->fetchConfig('new_member_roles', array(), null, false, false)));
            
            // save member
            $member->save();
            
            // trigger a hook
            $this->runHook('register', 'call', null, $member);

            // user saved
            $this->flash->set('register_success', 'Member created.');
            
            if ($auto_login) {
                Auth::login($username, $password);
            }
            
            // run hook
            $this->runHook('registration_complete', null, null, $member);
            
            // redirect to member home
            URL::redirect($return);
        }
    }


    /**
     * Target for the member:profile_form form
     * 
     * @return void
     */
    public function member__update_profile()
    {
        $site_root = Config::getSiteRoot();   
        $referrer  = $_SERVER['HTTP_REFERER'];
        $return    = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_URL);
        
        // is user logged in?
        if (!Auth::isLoggedIn()) {
            URL::redirect($this->fetchConfig('login_url', $site_root, null, false, false));
        }
        
        // get current user
        $member = Auth::getCurrentMember();
        
        // get configurations
        $allowed_fields   = array_get($this->loadConfigFile('fields'), 'fields', array());
        $role_definitions = $this->fetchConfig('role_definitions');
        
        // who are we editing?
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $username = (!$username) ? $member->get('username') : $username;
        
        // if the user isn't the current user, ensure that's allowed
        if ($username !== $member->get('username')) {
            // username is different from current user
            if (!array_get($role_definitions, 'edit_other_users', null) || !$member->hasRole($role_definitions['edit_other_users'])) {
                // this user does not have permission to do this
                $this->flash->set('update_profile_error', 'You are not allowed to edit another member’s profile.');
                URL::redirect($referrer);
            } else {
                // all set, update member
                $member = Member::load($username);
            }
        }
        
        // get old values
        $old_values = $member->export();
        
        // set up iterators and flags
        $submission = array();
        
        // loop through allowed fields, validating and updating
        foreach ($allowed_fields as $field => $options) {
            if (!isset($_POST[$field])) {
                // was this username? that can be included separately
                if ($field === 'username') {
                    $value = $username;
                } else {
                    // field wasn't set, skip it
                    continue;
                }
            } else {
                // set value
                $value = filter_input(INPUT_POST, $field, FILTER_SANITIZE_STRING);
            }

            // set value
            $old_values[$field] = $value;

            // don't store this value if `save_value` is set to `false`
            if (array_get($options, 'save_value', true)) {
                $member->set($field, $value);
            }

            // add to submissions, including non-save_value fields because this
            // is the list that will be validated
            $submission[$field] = $value;
        }
        
        // validate
        $errors = $this->tasks->validate($submission);
        
        if (count($errors)) {
            // errors were found, set a flash message and redirect
            $this->flash->set('update_profile_error', 'Member profile not updated.');
            $this->flash->set('update_profile_field_errors', $errors);
            $this->flash->set('update_profile_old_values', $old_values);
            
            URL::redirect($referrer);
        } else {
            // save member
            $member->save();
            
            // trigger a hook
            $this->runHook('profile_update', 'call', null, $member);

            // user saved
            $this->flash->set('update_profile_success', 'Member profile updated.');
            
            if ($return) {
                URL::redirect($return);
            } else {
                URL::redirect($referrer);
            }
        }
    }


    public function member__forgot_password()
    {
        $globals      = Statamic::loadAllConfigs();
        $username     = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $token        = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
        $return       = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_STRING);
        $reset_return = filter_input(INPUT_POST, 'reset_return', FILTER_SANITIZE_STRING);
        $referrer     = Request::getReferrer();

        // validate form token
        if ( ! $this->tokens->validate($token)) {
            $this->flash->set('forgot_password_error', 'Invalid token.');
            URL::redirect($referrer);
        }

        // bail if member doesn't exist
        if ( ! $member = Member::load($username)) {
            $this->flash->set('forgot_password_error', Localization::fetch('member_doesnt_exist'));
            URL::redirect($referrer);
        }

        // cache reset data
        $token = $this->tokens->create();
        $reset_data = array('username' => $username);
        if (isset($reset_return)){
            $reset_data['return'] = $reset_return;
        }
        $this->cache->putYAML($token, $reset_data);

        // generate reset url
        $reset_url = URL::makeFull($this->fetchConfig('reset_password_url', str_replace(Config::getSiteURL(), '', $referrer)));
        $reset_url .= '?H=' . $token;

        // send email
        $attributes = array(
            'from' => $this->fetchConfig('email_sender', Config::get('email_sender'), null, false, false),
            'to'   => $member->get('email'),
            'subject' => $this->fetchConfig('reset_password_subject', 'Password Reset', null, false, false)
        );

        if ($html_template = $this->fetchConfig('reset_password_html_email', false, null, false, false)) {
            $attributes['html'] = Theme::getTemplate($html_template);
        }

        if ($text_template = $this->fetchConfig('reset_password_text_email', false, null, false, false)) {
            $attributes['text'] = Theme::getTemplate($text_template);
        }

        foreach ($attributes as $key => $value) {
            $attributes[$key] = Parse::template($value, array('reset_url' => $reset_url), array('statamic_view', 'callback'), $globals);
        }

        Email::send($attributes);
        $this->flash->set('forgot_password_sent', true);

        // redirect
        URL::redirect($return);
    }


    public function member__reset_password()
    {
        $site_root         = Config::getSiteRoot();
        $password          = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $password_confirm  = filter_input(INPUT_POST, 'password_confirmation', FILTER_SANITIZE_STRING);
        $token             = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
        $hash              = filter_input(INPUT_POST, 'hash', FILTER_SANITIZE_STRING);
        $referrer          = $_SERVER['HTTP_REFERER'];

        // validate form token
        if ( ! $this->tokens->validate($token)) {
            $this->flash->set('reset_password_error', 'Invalid token.');
            URL::redirect($referrer);
        }
        
        // bail if cache doesnt exist or if its too old.
        // this should have been caught on the page itself,
        // but if it got submitted somehow, just redirect and the error logic will be in the plugin.
        if (
            ! $this->cache->exists($hash) 
            || $this->cache->getAge($hash) > $this->fetchConfig('reset_password_age_limit', 20, 'is_numeric') * 60
        ) {
            URL::redirect($referrer);
        }

        // password check
        if (is_null($password) || $password == '') {
            $this->flash->set('reset_password_error', 'Password cannot be blank.');
            URL::redirect($referrer);
        }

        // password confirmation check        
        if (
            !is_null($password_confirm) &&   // a password_confirm field was entered
            $password !== $password_confirm  // password doesn't match password_confirm
        ) {
            $this->flash->set('reset_password_error', 'Passwords did not match.');
            URL::redirect($referrer);
        }

        // get username
        $cache = $this->cache->getYAML($hash);
        $username = $cache['username'];

        // change password
        $member = Member::load($username);
        $member->set('password', $password);
        $member->save();

        // delete used cache
        $this->cache->delete($hash);

        // redirect
        URL::redirect(array_get($cache, 'return', $this->fetchConfig('member_home', $site_root, null, false, false)));
    }
}
