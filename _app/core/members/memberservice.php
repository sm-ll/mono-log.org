<?php

class MemberService
{
    public static $members;
    public static $members_loaded = false;
    private static $path;
    
    
    /**
     * Loads the member cache into the local cache variable if not done yet
     * 
     * @return void
     */
    public static function loadMembers()
    {
        if (self::$members_loaded) {
            return;
        }
        
        // set that we've loaded things
        self::$members_loaded = true;
        self::$path = Config::getConfigPath() . '/users/%s.yaml';
        self::$members = unserialize(File::get(Path::tidy(BASE_PATH . "/_cache/_app/members/members.php")));
        
        // no members found, that's ok, load a blank array
        if (!is_array(self::$members)) {
            self::$members = array();
        }
    }
    
    
    // member checking
    // ------------------------------------------------------------------------
    
    /**
     * Checks if a given member exists
     * 
     * @param string  $username  Username of member to check
     * @return boolean
     */
    public static function isMember($username)
    {
        self::loadMembers();
        return isset(self::$members[$username]);
    }
    
    
    // single member
    // ------------------------------------------------------------------------
    
    /**
     * Returns a Member object for one member
     * 
     * @param string  $username  Username of member to get
     * @return Member
     */
    public static function getMember($username)
    {
        self::loadMembers();
        if (!self::isMember($username)) {
            return array();
        }
        
        // get data
        $data = self::$members[$username];
        
        // load bio
        $file = File::get(sprintf(self::$path, $username));
        if ($file) {
            $content               = substr(File::get($file), 3);
            $divide                = strpos($content, "\n---");
            $data['biography_raw'] = trim(substr($content, $divide + 4));
            $data['biography']     = Content::transform($data['biography_raw']);
        }
        
        return new Member($data);
    }
    
    
    // many members
    // ------------------------------------------------------------------------
    
    /**
     * Returns a MemberSet of members
     * 
     * @return MemberSet
     */
    public static function getMembers()
    {
        self::loadMembers();
        return new MemberSet(self::$members);
    }
}