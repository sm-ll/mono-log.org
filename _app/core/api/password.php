<?php

class Password
{
    /**
     * Static copy of the password hasher
     */
    static private $hasher;
    
    
    /**
     * Generate a hashed password
     * 
     * @param string  $password  Password to hash
     * @return string
     */
    public static function hash($password)
    {
        return self::getHasher()->HashPassword($password);
    }
    
    
    /**
     * Compares a password with an existing hash created by the hasher
     * 
     * @param string  $password  Password to check
     * @param string  $hash  Hash to check against
     * @return bool
     */
    public static function check($password, $hash)
    {
        return self::getHasher()->checkPassword($password, $hash);
    }


    /**
     * Retrieves the hasher object, creating it if necessary
     *
     * @return object
     */
    private static function getHasher()
    {
        // check that the hasher has been loaded
        if (!is_object(self::$hasher)) {
            // not loaded, so load it
            require_once(APP_PATH . '/vendor/Openwall/PasswordHash.php');
            self::$hasher = new PasswordHash(32768, FALSE);
        }

        return self::$hasher;
    }
}