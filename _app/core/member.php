<?php

class Member
{
    /**
     * Array of Member-related data
     * @var array
     */
    protected $data = array();

    /**
     * Username of Member
     * @var string
     */
    protected $username = null;
    
    
    /**
     * Constructor
     * 
     * @param array  $data  Data about this member
     * @param string  $username  Username of this member
     * @return Member
     */
    public function __construct($data, $username=null)
    {
        if (isset($data['username'])) {
            unset($data['username']);
        }
        
        $this->data = $data;
        
        if ($username) {
            $this->set('username', $username);
            $this->ensureUID(true);
        }
    }
    
    
    /**
     * Set data for this member
     * 
     * @param string  $key  Key to set
     * @param mixed  $value  Value to set
     * @return void
     */
    public function set($key, $value)
    {
        // manage special exceptions
        if ($key == '_uid') {
            // cannot be set this way
            return;
        } elseif ($key == 'name' || $key == 'username') {
            // set the name to the name field, not in data
            $this->username = $value;
        } elseif ($key == 'password') {
            // create a hash out of the password
            $this->data['password'] = '';
            $this->data['password_hash'] = Password::hash($value);
        } elseif ($key == 'biography' || $key == 'biography_raw') {
            // setting the bio
            $this->data['biography_raw'] = $value;
            $this->data['biography'] = Content::transform($value);
        } elseif ($key == 'roles') {
            // setting roles
            if (is_string($value)) {
                $this->data[$key] = explode(',', $value);
            } elseif (is_array($value)) {
                $this->data[$key] = $value;
            }
        } else {
            // standard stuff, store a value
            $this->data[$key] = $value;
        }
    }
    
    
    /**
     * Gets data for a member's field, or the default value if not found
     * 
     * @param string  $key  Key to return
     * @param mixed  $default  Default to return if no value found
     * @return mixed
     */
    public function get($key, $default=null)
    {
        // manage special exceptions
        if ($key == 'name' || $key == 'username') {
            // name is a separate field
            return $this->username;
        } elseif ($key == 'password') {
            // we only ever return the password_hash
            $key = 'password_hash';
        }
        
        return array_get($this->data, $key, $default);
    }


    /**
     * Checks if a given data field is set
     * 
     * @param string  $key  Key to check
     * @return bool
     */
    public function dataKeyExists($key)
    {
        if ($key == 'name' || $key == 'username') {
            return (bool) $this->username;
        } elseif ($key == 'password') {
            return (bool) $this->get($key);
        } else {
            return isset($this->data[$key]);
        }
    }


    /**
     * Unsets a value from the data set
     * 
     * @param string  $key  Key to unset
     * @return void
     */
    public function remove($key)
    {
        if ($key == 'name' || $key == 'username' || $key == 'password' || $key == 'password_hash') {
            return;
        }
        
        unset($this->data[$key]);
    }


    /**
     * Gets the gravatar for this member at a given $size
     * 
     * @param int  $size  Size of avatar to retrieve in pixels
     * @return string
     */
    public function getGravatar($size=26)
    {
        return Avatar::getGravatar($this->get('email'), $size);
    }


    /**
     * Gets a list of roles for this member with settable $delimiter
     * 
     * @param string  $delimiter  Delimiter to use to separate roles
     * @return string
     */
    public function getRolesList($delimiter=', ')
    {
        return join($delimiter, $this->get('roles', array()));
    }


    /**
     * Gets this member's UID, shortcut method
     * 
     * @return string
     */
    public function getUID()
    {
        return $this->get('_uid');
    }


    /**
     * Returns an array of all user data
     * 
     * @param bool  $include_username  Should the $username be included in the data returned?
     * @return array
     */
    public function export($include_username=false)
    {
        $data = $this->data;
        
        if ($include_username) {
            $data['username'] = $this->username;
        }
        
        return $data;
    }


    /**
     * Checks if a given $password matches this member's password
     * 
     * @param string  $password  Password to check
     * @return boolean
     */
    public function checkPassword($password)
    {
        // user's password is not hashed, hash it
        if (isset($this->data['password']) && $this->data['password'] !== '') {
            $this->set('password', $this->data['password']);
            $this->save();
        }
        
        // now look for passwords
        if ($this->get('password_hash')) {
            // check for new password
            return Password::check($password, $this->get('password_hash'));

        } elseif ($this->get('encrypted_password')) {
            // legacy: check for old password
            return $this->matches_old_password($password);

        }

        return false;
    }


    /**
     * Checks if this member's password is hashed
     * 
     * @return boolean
     */
    public function hasHashedPassword()
    {
        return (bool) $this->get('password_hash');
    }


    /**
     * Checks to see if this member has a given $role
     * 
     * @param mixed  $role  Role to check
     * @return boolean
     */
    public function hasRole($role)
    {
        $role          = array_map('strtolower', Helper::ensureArray($role));
        $member_roles  = array_map('strtolower', $this->get('roles'));
        
        // check for intersection
        $matches = array_intersect($member_roles, $role);
        
        return (bool) count($matches);
    }


    /**
     * Adds a role to this member
     * 
     * @param string  $role  Role to add
     * @return void
     */
    public function addRole($role)
    {
        if (!$this->hasRole($role)) {
            $roles = $this->get('roles');
            $roles[] = $role;
            $this->set('roles', $roles);
        }
    }


    /**
     * Removes a role from this member
     * 
     * @param string  $role  Role to remove
     * @return void
     */
    public function removeRole($role)
    {
        if ($this->hasRole($role)) {
            $roles = $this->get('roles');
            $key = array_search($role, $roles);
            
            if ($key !== false) {
                unset($roles[$key]);
            }
            
            $this->set('roles', $roles);
        }
    }


    /**
     * Ensures this user has a UID
     * 
     * @param bool  $save  Save this record?
     * @return void
     */
    public function ensureUID($save)
    {
        if ($this->get('_uid')) {
            return;
        }
        
        $this->data['_uid'] = uniqid(Helper::getRandomString(6)) . Helper::getRandomString(8);
        
        if ($save) {
            $this->save();
        }
    }


    /**
     * Saves this member's profile data to file
     * 
     * @todo some checks are needed to make sure things are allowed
     * @return void
     */
    public function save()
    {
        // ensure UID
        $this->ensureUID(false);
        
        // set up variables
        $data     = $this->data;
        $content  = $this->get('biography_raw');
        $file     = Config::getConfigPath() . '/users/' . $this->username . '.yaml';
        
        // biography is content, we don't need them in the data array
        if (isset($data['biography_raw'])) {
            unset($data['biography_raw']);
        }
        if (isset($data['biography'])) {
            unset($data['biography']);
        }
        
        // if username is set, we don't need that either
        if (isset($data['username'])) {
            unset($data['username']);
        }

        File::put($file, File::buildContent($data, $content));
    }


    /**
     * Renames this member
     * 
     * @param string  $username  username to rename this Member to
     * @return boolean
     * @throws Exception
     */
    public function rename($username)
    {
        // the files in question
        $current_file  = Config::getConfigPath() . '/users/' . $this->get('username') . '.yaml';
        $new_file      = Config::getConfigPath() . '/users/' . $username . '.yaml';
        
        // is this the same that it already is? 
        if ($username === $this->get('username')) {
            return true;
        }

        // is this a valid username?
        if (!self::isValidusername($username)) {
            throw new Exception(Localization::fetch('invalid_username'));
        }
        
        // does this filename already exist?
        if (File::exists($new_file)) {
            throw new Exception(Localization::fetch('username_already_exists'));
        }
        
        // everything checks out, rename the file and return true
        File::rename($current_file, $new_file);
        return true;
    }


    /**
     * Deletes this member
     *
     * @todo some checks are needed to make sure things are allowed
     * @return void
     */
    public function delete()
    {
        File::delete(Config::getConfigPath() . '/users/' . $this->username . '.yaml');
    }


    /**
     * Checks to see if a given $username is a valid filename
     *
     * @param string  $username  username to check
     * @return boolean
     */
    public static function isValidUsername($username)
    {
        return (bool) preg_match('/^[a-z0-9\-_\.]+$/i', $username);
    }


    // ------------------------------------------------------------------------
    // static create methods
    // ------------------------------------------------------------------------

    /**
     * Loads a Member
     * 
     * @param string  $username  username to load
     * @return Member
     */
    public static function load($username)
    {
        try {
            // load data
            $data = self::loadMemberData($username);
            
            // populate object
            $member = new Member($data, $username);
            
            // return it
            return $member;
        } catch (Exception $e) {
            return null;
        }
    }


    // ------------------------------------------------------------------------
    // other static methods
    // ------------------------------------------------------------------------

    /**
     * Checks to see if a Member exists
     * 
     * @param string  $username  username to check
     * @return boolean
     */
    public static function exists($username)
    {
        return (bool) File::get(Config::getConfigPath() . '/users/' . $username . '.yaml', false);
    }


    /**
     * Gets a list of registered users
     * 
     * @param boolean  $protected  Are we displaying information in a protected area?
     * @return array
     */
    public static function getList($protected=true)
    {
        // start a place to put users
        $users  = array();
        
        // grab a list of files that should be users
        $list   = glob(Config::getConfigPath() . '/users/*.yaml');
        
        // did we find anything?
        if ($list) {
            // loop through what we found, grabbing Member data along the way
            foreach ($list as $name) {
                // get delimiters surrounding the username
                $slash = strrpos($name, '/') + 1;
                $dot   = strrpos($name, '.');
                
                // parse username
                $username = substr($name, $slash, $dot - $slash);
                
                // protected?
                $users[$username] = ($protected) ? Member::load($username) : Member::getProfile($username);
            }
        }
        
        // return whatever we found
        return $users;
    }


    /**
     * Loads a Member's profile
     * 
     * @param string  $username  username to load data for
     * @return array
     * @throws Exception
     */
    private static function loadMemberData($username)
    {
        // pull profile data from filesystem
        $file = Config::getConfigPath() . '/users/' . $username . '.yaml';
        $raw_profile = substr(File::get($file), 3);

        // no profile found, throw an exception
        if (!$raw_profile) {
            throw new Exception('Member `' . $username . '` not found.');
        }

        // split out the profile into parts
        $divide        = strpos($raw_profile, "\n---");
        $front_matter  = trim(substr($raw_profile, 0, $divide));
        $content_raw   = trim(substr($raw_profile, $divide + 4));

        // create data array for populating into the Member object
        $data = YAML::parse($front_matter);
        $data['biography_raw'] = $content_raw;
        $data['biography']     = Content::transform($content_raw);
        $data['username']      = $username;
        
        // return the Member data
        return $data;
    }


    /**
     * Gets a Member's full profile
     *
     * @param string  $username  username to retrieve
     * @param array  $do_not_exclude  Excluded fields not to exclude
     * @return mixed
     */
    public static function getProfile($username, $do_not_exclude=array())
    {
        try {
            // attempt to load Member data
            $data = self::loadMemberData($username);
            
            // set the username (as that's not part of the data)
            $data['username'] = $username;
            
            // create a list of fields that should not be returned
            $protected_fields = array_fill_keys(
                array_diff(array('password', 'encrypted_password', 'salt', 'password_hash'), $do_not_exclude),
                null);

            // return Member data minus the fields that shouldn't be returned
            return array_diff_key($data, $protected_fields);
        } catch (Exception $e) {
            // something went pear-shaped, return null
            return null;
        }
    }
    
    
    public static function getProfileByUID($uid, $do_not_exclude=array())
    {
        $member_set = MemberService::getMembers();
        $member_set->filter(array('conditions' => '_uid:' . $uid));
        $members = $member_set->get();
        
        // no members found
        if (!$members) {
            return null;
        }

        // create a list of fields that should not be returned
        $protected_fields = array_fill_keys(
            array_diff(array('password', 'encrypted_password', 'salt', 'password_hash'), $do_not_exclude),
            null);
        
        foreach ($members as $member_data) {
            // only want the first one
            return array_diff_key($member_data, $protected_fields);
        }
    }


    // ------------------------------------------------------------------------
    // legacy interface
    // ------------------------------------------------------------------------

    /**
     * Set name
     * 
     * @deprecated
     * @param string  $name  Name to set
     * @return void
     */
    public function set_name($name)
    {
        // deprecation warning
        Log::warn('Use of `set_name` is deprecated. Use `set` instead.', 'core', 'Member');
        
        // set
        $this->set('name', $name);
    }
    
    
    /**
     * Get name
     *
     * @deprecated
     * @return string
     */
    public function get_name()
    {
        // deprecation warning
        Log::warn('Use of `get_name` is deprecated. Use `get` instead.', 'core', 'Member');

        // get
        return $this->get('username');
    }
    
    
    /**
     * Set first name
     *
     * @deprecated
     * @param string  $first_name  First name to set
     * @return void
     */
    public function set_first_name($first_name)
    {
        // deprecation warning
        Log::warn('Use of `set_first_name` is deprecated. Use `set` instead.', 'core', 'Member');

        // set
        $this->set('first_name', $first_name);
    }


    /**
     * Get first name
     *
     * @deprecated
     * @return string
     */
    public function get_first_name()
    {
        // deprecation warning
        Log::warn('Use of `get_first_name` is deprecated. Use `get` instead.', 'core', 'Member');

        // get
        return $this->get('first_name');
    }
    
    
    /**
     * Set last name
     *
     * @deprecated
     * @param string  $last_name  Last name to set
     * @return void
     */
    public function set_last_name($last_name)
    {
        // deprecation warning
        Log::warn('Use of `set_last_name` is deprecated. Use `set` instead.', 'core', 'Member');

        // set
        $this->set('last_name', $last_name);
    }


    /**
     * Get last name
     *
     * @deprecated
     * @return string
     */
    public function get_last_name()
    {
        // deprecation warning
        Log::warn('Use of `get_last_name` is deprecated. Use `get` instead.', 'core', 'Member');

        // get
        return $this->get('last_name');
    }
    
    
    /**
     * Set email
     *
     * @deprecated
     * @param string  $email  Email to set
     * @return void
     */
    public function set_email($email)
    {
        // deprecation warning
        Log::warn('Use of `set_email` is deprecated. Use `set` instead.', 'core', 'Member');

        // set
        $this->set('email', $email);
    }


    /**
     * Get email
     *
     * @deprecated
     * @return string
     */
    public function get_email()
    {
        // deprecation warning
        Log::warn('Use of `get_email` is deprecated. Use `get` instead.', 'core', 'Member');

        // get
        return $this->get('email');
    }
    
    
    /**
     * Get full name
     *
     * @deprecated
     * @return string
     */
    public function get_full_name()
    {
        // deprecation warning
        Log::warn('Use of `get_full_name` is deprecated. Use `get` instead.', 'core', 'Member');
        
        // get
        $names = array(
            'first_name' => $this->get('first_name'),
            'last_name'  => $this->get('last_name')
        );

        $full_name = trim(implode($names, ' '));

        // No name. Fall back to username.
        if (!$full_name) {
            $full_name = $this->get('username');
        }

        return $full_name;
    }
    
    
    /**
     * Get gravatar
     *
     * @deprecated
     * @param int  $size  Size of gravatar to return
     * @return string
     */
    public function get_gravatar($size=26)
    {
        // deprecation warning
        Log::warn('Use of `get_gravatar` is deprecated. Use `getGravatar` instead.', 'core', 'Member');

        // get
        return $this->getGravatar($size);
    }


    /**
     * Get biography
     *
     * @deprecated
     * @return string
     */
    public function get_biography()
    {
        // deprecation warning
        Log::warn('Use of `get_biography` is deprecated. Use `get` instead.', 'core', 'Member');

        // get
        return $this->get('biography');
    }


    /**
     * Set biography (raw)
     *
     * @deprecated
     * @param string  $biography_raw  Raw biography to set
     * @return void
     */
    public function set_biography($biography_raw)
    {
        // deprecation warning
        Log::warn('Use of `set_biography` is deprecated. Use `set` instead.', 'core', 'Member');

        // set
        $this->set('biography_raw', $biography_raw);
    }


    /**
     * Get biography_raw
     *
     * @deprecated
     * @return string
     */
    public function get_biography_raw()
    {
        // deprecation warning
        Log::warn('Use of `get_biography_raw` is deprecated. Use `get` instead.', 'core', 'Member');

        // get
        return $this->get('biography_raw');
    }


    /**
     * Set roles
     *
     * @deprecated
     * @param string  $roles  Roles to set
     * @return void
     */
    public function set_roles($roles)
    {
        // deprecation warning
        Log::warn('Use of `set_roles` is deprecated. Use `set` instead.', 'core', 'Member');

        // set
        $this->set('roles', $roles);
    }
    
    
    /**
     * Get roles list
     *
     * @deprecated
     * @param string  $delimiter  Delimiter to use
     * @return string
     */
    public function get_roles_list($delimiter=', ')
    {
        // deprecation warning
        Log::warn('Use of `get_roles_list` is deprecated. Use `getRolesList` instead.', 'core', 'Member');

        // get
        return $this->getRolesList($delimiter);
    }
    
    
    /**
     * Checks if the given $password matches for this Member
     *
     * @deprecated
     * @param string  $password  Password to check
     * @return boolean
     */
    public function correct_password($password)
    {
        // deprecation warning
        Log::warn('Use of `correct_password` is deprecated. Use `checkPassword` instead.', 'core', 'Member');

        // get
        return $this->checkPassword($password);
    }


    /**
     * Get password_hash
     *
     * @deprecated
     * @return string
     */
    public function get_hashed_password()
    {
        // deprecation warning
        Log::warn('Use of `get_hashed_password` is deprecated. Use `get` instead.', 'core', 'Member');

        // get
        return $this->get('password_hash');
    }
    
    
    /**
     * Checks if this Member's password is hashed
     * 
     * @deprecated
     * @return boolean
     */
    public function is_password_hashed()
    {
        // deprecation warning
        Log::warn('Use of `is_password_hashed` is deprecated. Use `hasHashedPassword` instead.', 'core', 'Member');

        // get
        return $this->hasHashedPassword();
    }
    
    
    /**
     * Checks if a Member has a give role
     *
     * @deprecated
     * @param string  $role  Role to check for
     * @return boolean
     */
    public function has_role($role)
    {
        // deprecation warning
        Log::warn('Use of `has_role` is deprecated. Use `hasRole` instead.', 'core', 'Member');

        // get
        return $this->hasRole($role);
    }
    
    
    /**
     * Checks to see if a given password matches the old password hash system
     * 
     * @deprecated
     * @param string  $password  Password to check
     * @return boolean
     */
    private function matches_old_password($password)
    {
        if ($this->get('encrypted_password')) {
            // return true/false on if it matches
            return (sha1($password.$this->get('salt', '')) === $this->get('encrypted_password'));
        }

        return false;
    }


    /**
     * Checks to see if a given string is a valid filename
     * 
     * @deprecated
     * @param string  $username  Name to check  
     * @return boolean
     */
    public static function is_valid_name($username)
    {
        // deprecation warning
        Log::warn('Use of `is_valid_name` is deprecated. Use `Member::isValidName` instead.', 'core', 'Member');

        // check
        return self::isValidusername($username);
    }
    
    
    /**
     * Gets a Member's profile
     * 
     * @deprecated
     * @param string  $username  username to check
     * @return mixed
     */
    public static function get_profile($username)
    {
        // deprecation warning
        Log::warn('Use of `get_profile` is deprecated. Use `Member::getProfile` instead.', 'core', 'Member');
        
        // get profile
        return self::getProfile($username);
    }
}