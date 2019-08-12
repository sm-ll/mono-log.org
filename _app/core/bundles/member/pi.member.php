<?php
class Plugin_member extends Plugin
{
    public function login_form()
    {
        // parse parameters and vars
        $attr_string          = '';
        $site_root            = Config::getSiteRoot();
        $return               = $this->fetchParam('return', URL::getCurrent(), null, false, false);
        $allow_request_return = $this->fetchParam('allow_request_return', false, null, true, false);
        $logged_in_redirect   = $this->fetchParam('logged_in_redirect', $return, null, false, false);
        $attr                 = $this->fetchParam('attr', false);

        // grab request return
        $get_return      = filter_input(INPUT_GET, 'return', FILTER_SANITIZE_URL);
        $post_return     = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_URL);
        $request_return  = Helper::pick($post_return, $get_return);
        
        // is user already logged in? forward as needed
        if (Auth::isLoggedIn()) {
            URL::redirect($logged_in_redirect, 302);
        }
        
        // if we're letting return values to be set in URL and one exists, grab it
        if ($allow_request_return && $request_return) {
            $return = $request_return;
        }
        
        // set up any data to be parsed into content 
        $data = array(
            'error' => $this->flash->get('login_error', ''),
            'old_values' => array_map('htmlspecialchars', $this->flash->get('old_values', array()))
        );

        // set up attributes
        if ($attr) {
            $attributes_array = Helper::explodeOptions($attr, true);
            
            foreach ($attributes_array as $key => $value) {
                $attr_string .= ' ' . $key . '="' . $value . '"';
            }
        }

        // set up form HTML
        $html  = '<form method="post" action="' . Path::tidy($site_root . "/TRIGGER/member/login") . '" ' . $attr_string . '>';
        $html .= '<input type="hidden" name="return" value="' . $return . '">';
        $html .= '<input type="hidden" name="token" value="' . $this->tokens->create() . '">';
        $html .= Parse::template($this->content, $data);
        $html .= '</form>';
        
        // return that HTML
        return $html;
    }
    
    
    public function logout_url()
    {
        $return = $this->fetchParam('return', URL::getCurrent());
        return URL::assemble(Config::getSiteRoot(), "TRIGGER", 'member', "logout?return={$return}");
    }
    
    
    public function logout()
    {
        URL::redirect($this->logout_url());
    }
    
    
    public function register_form()
    {
        if (Auth::isLoggedIn()) {
            // logged in
            return false;
        }

        $attr_string          = '';
        $site_root            = Config::getSiteRoot();
        $return               = $this->fetchParam('return', URL::getCurrent(), null, false, false);
        $allow_request_return = $this->fetchParam('allow_request_return', false, null, true, false);
        $attr                 = $this->fetchParam('attr', false);
        $auto_login           = (int) $this->fetchParam('auto_login', true, null, true, false);

        // grab request return
        $get_return      = filter_input(INPUT_GET, 'return', FILTER_SANITIZE_URL);
        $post_return     = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_URL);
        $request_return  = Helper::pick($post_return, $get_return);

        // if we're letting return values to be set in URL and one exists, grab it
        if ($allow_request_return && $request_return) {
            $return = $request_return;
        }

        // get old values
        $old_values   = $this->flash->get('register_old_values', array());

        array_walk_recursive($old_values, function(&$item, $key) {
            $item = htmlspecialchars($item);
        });

        // set up any data to be parsed into content 
        $data = array(
            'error' => $this->flash->get('register_error', ''),
            'success' => $this->flash->get('register_success', ''),
            'field_errors' => $this->flash->get('register_field_errors', array()),
            'old_values' => $old_values
        );

        // set up attributes
        if ($attr) {
            $attributes_array = Helper::explodeOptions($attr, true);

            foreach ($attributes_array as $key => $value) {
                $attr_string .= ' ' . $key . '="' . $value . '"';
            }
        }

        // set up form HTML
        $html  = '<form method="post" action="' . Path::tidy($site_root . "/TRIGGER/member/register") . '" ' . $attr_string . '>';
        $html .= '<input type="hidden" name="return" value="' . $return . '">';
        $html .= '<input type="hidden" name="token" value="' . $this->tokens->create() . '">';
        $html .= '<input type="hidden" name="auto_login" value="' . $auto_login . '">';

        $html .= Parse::template($this->content, $data);
        $html .= '</form>';

        // return that HTML
        return $html;
    }
    
    
    public function profile_form()
    {
        if (!Auth::isLoggedIn()) {
            // not logged in
            return false;
        }
        
        $attr_string  = '';
        $member       = Auth::getCurrentMember();
        $site_root    = Config::getSiteRoot();
        $username     = $this->fetchParam('username', $member->get('username'));
        $return       = $this->fetchParam('return', URL::getCurrent(), null, false, false);
        $attr         = $this->fetchParam('attr', false);
        
        // get old values
        $old_values   = $this->flash->get('update_profile_old_values', array()) + Member::getProfile($username);

        array_walk_recursive($old_values, function(&$item, $key) {
            $item = htmlspecialchars($item);
        });

        // set up any data to be parsed into content 
        $data = array(
            'error' => $this->flash->get('update_profile_error', ''),
            'success' => $this->flash->get('update_profile_success', ''),
            'field_errors' => $this->flash->get('update_profile_field_errors', array()),
            'old_values' => $old_values
        );

        // set up attributes
        if ($attr) {
            $attributes_array = Helper::explodeOptions($attr, true);

            foreach ($attributes_array as $key => $value) {
                $attr_string .= ' ' . $key . '="' . $value . '"';
            }
        }
        
        // set username in flash
        $this->flash->set('update_username', $username);

        // set up form HTML
        $html  = '<form method="post" action="' . Path::tidy($site_root . "/TRIGGER/member/update_profile") . '" ' . $attr_string . '>';
        $html .= '<input type="hidden" name="return" value="' . $return . '">';
        $html .= '<input type="hidden" name="token" value="' . $this->tokens->create() . '">';
        
        // are we editing someone other than the current user?
        // security note, the hook for this form will check that the current
        // user has permissions to edit this user's information
        if ($username !== $member->get('username')) {
            $html .= '<input type="hidden" name="username" value="' . $username . '">';
        }
        
        $html .= Parse::template($this->content, $data);
        $html .= '</form>';

        // return that HTML
        return $html;
    }
    
    public function login()
    {
        // deprecation warning
        $this->log->warn("Use of `login` is deprecated. Use `login_form` instead.");
        
        $site_root = Config::getSiteRoot();

        $return = $this->fetchParam('return', $site_root);

        /*
        |--------------------------------------------------------------------------
        | Form HTML
        |--------------------------------------------------------------------------
        |
        | The login form writes a hidden field to the form to set the return url.
        | Form attributes are accepted as colon/piped options:
        | Example: attr="class:form|id:contact-form"
        |
        | Note: The content of the tag pair is inserted back into the template
        |
        */

        $attributes_string = '';

        if ($attr = $this->fetchParam('attr', false)) {
            $attributes_array = Helper::explodeOptions($attr, true);
            foreach ($attributes_array as $key => $value) {
                $attributes_string .= " {$key}='{$value}'";
            }
        }

        $html  = "<form method='post' action='" . Path::tidy($site_root . "/TRIGGER/member/login") . "' {$attributes_string}>";
        $html .= "<input type='hidden' name='return' value='$return' />";
        $html .= $this->content;
        $html .= "</form>";

        return $html;

    }

    public function forgot_password_form()
    {
        // parse parameters and vars
        $attr_string          = '';
        $site_root            = Config::getSiteRoot();
        $return               = $this->fetchParam('return', URL::getCurrent(), null, false, false);
        $reset_return         = $this->fetchParam('reset_return', null, null, false, false);
        $allow_request_return = $this->fetchParam('allow_request_return', false, null, true, false);
        $logged_in_redirect   = $this->fetchParam('logged_in_redirect', $return, null, false, false);
        $attr                 = $this->fetchParam('attr', false);

        // check that email template(s) exist
        if ( 
            ! Theme::getTemplate($this->fetchConfig('reset_password_html_email', false, null, false, false)) 
            && ! Theme::getTemplate($this->fetchConfig('reset_password_text_email', false, null, false, false))
        ) {
            throw new Exception('Your reset password email template(s) must exist and contain a {{ reset_url }}.');
        }

        // grab request return
        $get_return      = filter_input(INPUT_GET, 'return', FILTER_SANITIZE_URL);
        $post_return     = filter_input(INPUT_POST, 'return', FILTER_SANITIZE_URL);
        $request_return  = Helper::pick($post_return, $get_return);
        
        // is user already logged in? forward as needed
        if (Auth::isLoggedIn()) {
            URL::redirect($logged_in_redirect, 302);
        }
        
        // if we're letting return values to be set in URL and one exists, grab it
        if ($allow_request_return && $request_return) {
            $return = $request_return;
        }
        
        // set up any data to be parsed into content 
        $data = array(
            'error' => $this->flash->get('forgot_password_error', ''),
            'email_sent' => $this->flash->get('forgot_password_sent')
        );

        // set up attributes
        if ($attr) {
            $attributes_array = Helper::explodeOptions($attr, true);
            
            foreach ($attributes_array as $key => $value) {
                $attr_string .= ' ' . $key . '="' . $value . '"';
            }
        }

        // set up form HTML
        $html  = '<form method="post" action="' . Path::tidy($site_root . "/TRIGGER/member/forgot_password") . '" ' . $attr_string . '>';
        $html .= '<input type="hidden" name="return" value="' . $return . '">';
        if ($reset_return) {
            $html .= '<input type="hidden" name="reset_return" value="' . $reset_return . '">';
        }
        $html .= '<input type="hidden" name="token" value="' . $this->tokens->create() . '">';
        $html .= Parse::template($this->content, $data);
        $html .= '</form>';
        
        // return that HTML
        return $html;
    }

    public function reset_password_form()
    {
        $data = array();
        $errors = array();

        // parse parameters and vars
        $attr_string          = '';
        $site_root            = Config::getSiteRoot();
        $logged_in_redirect   = $this->fetchParam('logged_in_redirect', $this->fetchConfig('member_home', $site_root), null, false, false);
        $attr                 = $this->fetchParam('attr', false);
        $hash                 = filter_input(INPUT_GET, 'H', FILTER_SANITIZE_URL);

        // is user already logged in? forward as needed
        if (Auth::isLoggedIn()) {
            URL::redirect($logged_in_redirect, 302);
        }        

        // no hash in URL?
        if (!$hash) {
            $errors[] = Localization::fetch('reset_password_url_invalid');
            $data['url_invalid'] = true;
        }

        if (count($errors) == 0) {
            // cache file doesn't exist or is too old
            if (
                ! $this->cache->exists($hash) 
                || $this->cache->getAge($hash) > $this->fetchConfig('reset_password_age_limit') * 60
            ) {
                $errors[] = Localization::fetch('reset_password_url_expired');
                $data['expired'] = true;
            }

            // flash errors
            if ($flash_error = $this->flash->get('reset_password_error')) {
                $errors[] = $flash_error;
            }
        }

        // set up attributes
        if ($attr) {
            $attributes_array = Helper::explodeOptions($attr, true);
            
            foreach ($attributes_array as $key => $value) {
                $attr_string .= ' ' . $key . '="' . $value . '"';
            }
        }

        // errors
        $data['errors'] = $errors;

        // set up form HTML
        $html  = '<form method="post" action="' . Path::tidy($site_root . "/TRIGGER/member/reset_password") . '" ' . $attr_string . '>';
        $html .= '<input type="hidden" name="token" value="' . $this->tokens->create() . '">';
        $html .= '<input type="hidden" name="hash" value="' . $hash . '">';
        $html .= Parse::template($this->content, $data);
        $html .= '</form>';

        
        // return that HTML
        return $html;
    }

    public function profile()
    {
        // parse parameters
        $username = $this->fetchParam(array('username', 'name', 'member'), null, false, false, false);
        $uid      = $this->fetchParam('uid', null, false, false, false);

        if ($username) {  // username
            return Member::getProfile($username);
        } elseif ($uid) {  // uid
            return Member::getProfileByUID($uid);
        }
        
        // neither of those? try the current user
        $user = Auth::getCurrentMember();

        if ($user) {
            $username = $user->get('name');
            return Member::getProfile($username);
        } else {
            return array('no_results' => true);
        }
    }

    
    public function listing()
    {
        if (Config::get('disable_member_cache')) {
            $this->log->error("Cannot use `member:listing` when `_disable_member_cache` is `true`.");
            return Parse::template($this->content, array('no_results' => true));
        }
        
        // grab common parameters
        $settings = $this->parseCommonParameters();

        // grab member set based on the common parameters
        $member_set = $this->getMemberSet($settings);

        // grab total members for setting later
        $total_members = $member_set->count();

        // no users found? return no results
        if (!$total_members) {
            return Parse::template($this->content, array('no_results' => true));
        }

        // limit
        $limit     = $this->fetchParam('limit', null, 'is_numeric');
        $offset    = $this->fetchParam('offset', 0, 'is_numeric');
        $paginate  = $this->fetchParam('paginate', true, null, true, false);

        if ($limit || $offset) {
            if ($limit && $paginate && !$offset) {
                // pagination requested, isolate the appropriate page
                $member_set->isolatePage($limit, URL::getCurrentPaginationPage());
            } else {
                // just limit or offset
                $member_set->limit($limit, $offset);
            }
        }

        // manually supplement
        $member_set->supplement(array(
            'total_found' => $total_members,
        ));

        // check for results
        if (!$member_set->count()) {
            return Parse::template($this->content, array('no_results' => true));
        }

        return Parse::tagLoop($this->content, $member_set->get(), true, $this->context);
    }


    public function pagination()
    {
        // grab common parameters
        $settings = $this->parseCommonParameters();

        // grab member set based on the common parameters
        $member_set = $this->getMemberSet($settings);

        // grab limit as page size
        $limit = $this->fetchParam('limit', null, 'is_numeric'); // defaults to none

        // count the content available
        $count = $member_set->count();

        $pagination_variable  = Config::getPaginationVariable();
        $page                 = Request::get($pagination_variable, 1);

        $data                       = array();
        $data['total_items']        = (int) max(0, $count);
        $data['items_per_page']     = (int) max(1, $limit);
        $data['total_pages']        = (int) ceil($count / $limit);
        $data['current_page']       = (int) min(max(1, $page), max(1, $page));
        $data['current_first_item'] = (int) min((($page - 1) * $limit) + 1, $count);
        $data['current_last_item']  = (int) min($data['current_first_item'] + $limit - 1, $count);
        $data['previous_page']      = ($data['current_page'] > 1) ? "?{$pagination_variable}=" . ($data['current_page'] - 1) : false;
        $data['next_page']          = ($data['current_page'] < $data['total_pages']) ? "?{$pagination_variable}=" . ($data['current_page'] + 1) : false;
        $data['first_page']         = ($data['current_page'] === 1) ? false : "?{$pagination_variable}=1";
        $data['last_page']          = ($data['current_page'] >= $data['total_pages']) ? false : "?{$pagination_variable}=" . $data['total_pages'];
        $data['offset']             = (int) (($data['current_page'] - 1) * $limit);

        return Parse::template($this->content, $data);
    }


    /**
     * Parses out all of the needed parameters for this plugin
     *
     * @return array
     */
    private function parseCommonParameters()
    {
        // determine filters
        $filters = array(
            'role'       => $this->fetchParam('role', null, false, false, false),
            'conditions' => trim($this->fetchParam('conditions', null, false, false, false)),
            'where'      => trim($this->fetchParam('where', null, false, false, false))
        );

        // determine other factors
        $other = array(
            'sort_by'  => $this->fetchParam('sort_by', 'username'),
            'sort_dir' => $this->fetchParam('sort_dir', 'asc')
        );

        return $other + $filters;
    }


    /**
     * Returns a MemberSet object with the appropriate content
     *
     * @param array  $settings  Settings for filtering members and such
     * @return MemberSet
     */
    private function getMemberSet($settings)
    {
        // create a unique hash for these settings
        $set_hash = Helper::makeHash($settings);

        if ($this->blink->exists($set_hash)) {
            // blink content exists, use that
            $member_set = new MemberSet($this->blink->get($set_hash));
        } else {
            $member_set = MemberService::getMembers();

            // filter
            $member_set->filter($settings);

            // sort
            $member_set->sort($settings['sort_by'], $settings['sort_dir']);

            // store content as blink content for future use
            $this->blink->set($set_hash, $member_set->extract());
        }

        return $member_set;
    }
}
