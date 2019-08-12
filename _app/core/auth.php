<?php
/**
 * Auth
 * Handles user authentication within Statamic
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */

class Auth
{
    /**
     * Attempts to log a user in
     * 
     * @param string  $username  Username of user
     * @param string  $password  Password of user
     * @param bool  $remember  Remember this user?
     * @return boolean 
     */
    public static function login($username, $password, $remember=false)
    {
        // attempt to load the Member object
        $user = self::getMember($username);
        
        // if no Member object, or checkPassword fails, return false
        if (!$user || !$user->checkPassword($password)) {
            return false;
        }
        
        // log in member
        self::loginMember($username, $remember);
        
        return true;
    }


    /**
     * Logs a given member in
     * 
     * @param string  $username  Username of user to log in
     * @param bool  $remember  Should we remember this user?
     * @return void
     */
    public static function loginMember($username, $remember=false) {
        $user = self::getMember($username);
        
        // we made it! prepare the app and some data...
        $app      = \Slim\Slim::getInstance();
        $expires  = ($remember) ? '20 years' : $app->config['_cookies.lifetime'];
        $hash     = self::createHash($user);

        // trigger a hook
        Hook::run('auth', 'login', 'call', null, $user);

        // ...set the cookie and return true
        $app->setEncryptedCookie('stat_auth_cookie', $hash, $expires);

    }


    /**
     * Logs the current user out
     * 
     * @return void
     */
    public static function logout()
    {
        // trigger a hook
        Hook::run('auth', 'login', 'call', null, Auth::getCurrentMember());
        
        $app = \Slim\Slim::getInstance();
        $app->deleteCookie('stat_auth_cookie');
    }


    /**
     * Checks to see if a user is logged in
     * 
     * @return boolean
     */
    public static function isLoggedIn()
    {
        return !is_null(self::getLoggedInMember());
    }


    /**
     * Checks to see if a user is currently logged in
     * 
     * @return Member|null
     */
    public static function getLoggedInMember()
    {
        // grab the cookie
        $app = \Slim\Slim::getInstance();
        $cookie = $app->getEncryptedCookie('stat_auth_cookie');
        
        if (strpos($cookie, ':') === false) {
            return null;
        }

        // break it into parts and create the Member object
        list($username, $hash) = explode(":", $cookie);
        $member = self::getMember($username);

        // was a Member object found? 
        if ($member) {
            $hash = self::createHash($member);

            // compare the stored hash to a fresh one, do they match?
            if ($cookie === $hash) {
                // they match, Member is valid, extend lifetime
                $expire = $app->config['_cookies.lifetime'];
                $app->setEncryptedCookie('stat_auth_cookie', $cookie, $expire);

                // return the Member object
                return $member;
            }
        }

        // something above went wrong, return null
        return null;
    }


    /**
     * Gets the Member object for a given $username
     * 
     * @param string  $username  Username to look up
     * @return Member|null
     */
    public static function getMember($username)
    {
        return Member::load($username);
    }


    /**
     * Gets the current logged-in Member object if one exists
     * 
     * @return Member|null
     */
    public static function getCurrentMember()
    {
        return self::getLoggedInMember();
    }


    /**
     * Creates a hash for this user
     * 
     * @param Member  $member  Member object
     * @return string
     */
    protected static function createHash($member)
    {
        return $member->get('username') . ':' . md5($member->get('password_hash') . Cookie::getSecretKey());
    }


    
    // ------------------------------------------------------------------------
    // legacy interface
    // ------------------------------------------------------------------------
    
    /**
     * Gets the Member object for a given $username
     * 
     * @deprecated
     * @param string  $username  Username to look up
     * @return Member|null
     */
    public static function get_user($username)
    {
        // deprecation warning
        Log::warn("Use of `get_user` is deprecated. Use `Auth::getMember` instead.", "core", "auth");
        
        // return it
        return self::getMember($username);
    }
    
    
    /**
     * Gets the current logged-in Member object if one exists
     * 
     * @deprecated
     * @return Member|null
     */
    public static function get_current_user()
    {
        // deprecation warning
        Log::warn("Use of `get_current_user` is deprecated. Use `Auth::getCurrentMember` instead.", "core", "auth");
        
        // return it
        return self::getCurrentMember();
    }
    
    
    /**
     * Checks if a user is logged in and if so, returns that Member object
     * 
     * @deprecated
     * @return Member|null
     */
    public static function is_logged_in()
    {
        // deprecation warning
        Log::warn("Use of `is_logged_in` is deprecated. Use `Auth::getLoggedInMember` instead.", "core", "auth");
        
        // return it
        return self::getLoggedInMember();
    }
    
    
    /**
     * Checks if a user exists
     * 
     * @deprecated
     * @param string  $username  Username to check
     * @return boolean
     */
    public static function user_exists($username)
    {
        // deprecation warning
        Log::warn("Use of `user_exists` is deprecated. Use `Member::exists` instead.", "core", "auth");
        
        // return it
        return Member::exists($username);
    }
    
    
    /**
     * Gets a list of registered users
     * 
     * @deprecated
     * @param boolean  $protected  Displaying information in a protected area?
     * @return array
     */
    public static function get_user_list($protected=true)
    {
        // deprecation warning
        Log::warn("Use of `get_user_list` is deprecated. Use `Member::getList` instead.", "core", "auth");
        
        // return it
        return Member::getList($protected);
    }
}