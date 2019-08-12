<?php

class Cookie
{
    /**
     * Ensures that there's a random secret key for cookies to use
     * 
     * @return bool
     */
    public static function ensureSecretKey()
    {
        $secret_key_file = BASE_PATH . "/_cache/_app/cookie/key.php";
        
        // check that an existing key has been set
        if (File::exists($secret_key_file) && strlen(File::get($secret_key_file)) >= 24) {
            return true;
        }
        
        // no key set, generate one
        $key = Helper::getRandomString(128, true);
        
        // check the result, log errors if needed
        File::put($secret_key_file, $key, 0777);
        
        if (!File::exists($secret_key_file) || !strlen(File::get($secret_key_file))) {
            Log::error("Could not create a secret cookie key.", "core", "cookie");
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Returns the current secret key
     * 
     * @return string
     */
    public static function getSecretKey()
    {
        if (!self::ensureSecretKey()) {
            Log::error("Cannot find secret cookie key", "core", "cookie");
        }
        
        $key = File::get(BASE_PATH . "/_cache/_app/cookie/key.php");
        
        if (!$key) {
            Log::error("Cannot find secret cookie key", "core", "cookie");
        }
        
        return $key;
    }
}