<?php
/**
 * Session
 * API for interacting with the PHP session
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Session
{
    /**
     * Fetch a bit of flash data, if available; accepts "colon" notation
     *
     * @param string  $name  Name of Flash to get
     * @param mixed  $default  Default value to use if none exists
     * @return string
     */
    public static function getFlash($name, $default = NULL)
    {
        if (isset($_SESSION['slim.flash'])) {
            return array_get($_SESSION['slim.flash'], $name, $default);
        }

        return $default;
    }


    /**
     * Sets flash data to be made available at the next request
     *
     * @param string  $name  Name of Flash to set
     * @param string  $value  Value to set to Flash
     * @return void
     */
    public static function setFlash($name, $value)
    {
        $app = \Slim\Slim::getInstance();
        $app->flash($name, $value);
    }


    /**
     * Get value of unencrypted HTTP cookie
     *
     * Return the value of a cookie from the current HTTP request,
     * or return NULL if cookie does not exist. Cookies created during
     * the current request will not be available until the next request.
     *
     * @param string  $cookie  Name of cookie to retrieve
     * @return string|null
     */
    public static function getCookie($cookie)
    {
        $app = \Slim\Slim::getInstance();
        return $app->getCookie($cookie);
    }


    /**
     * Get value of encrypted HTTP cookie
     *
     * Return the value of an encrypted cookie from the current HTTP request,
     * or return FALSE if cookie does not exist. Encrypted cookies created during
     * the current request will not be available until the next request.
     *
     * @param string  $cookie  Name of cookie to retrieve
     * @return string|boolean
     */
    public static function getEncryptedCookie($cookie)
    {
        $app = \Slim\Slim::getInstance();
        return $app->getEncryptedCookie($cookie);
    }

    /**
     * Sets an encrypted HTTP cookie
     *
     * @param string  $name  Name of cookie to set
     * @param string  $value  Value of cookie to set
     * @param string  $expire  Optional length of time until the cookie expires
     * @return void
     */
    public static function setCookie($name, $value, $expire = NULL)
    {
        $app = \Slim\Slim::getInstance();
        $app->setCookie($name, $value, $expire);
    }

    /**
     * Sets an encrypted HTTP cookie
     *
     * @param string  $cookie  Name of cookie to set
     * @param string  $value  Value of cookie to set
     * @param string  $expire  Optional length of time until the cookie expires
     */
    public static function setEncryptedCookie($cookie, $value, $expire = '1 day')
    {
        $app = \Slim\Slim::getInstance();
        $app->setEncryptedCookie($cookie, $value, $expire);
    }




    // namespacing sessions
    // -------------------------------------------------------------------------

    /**
     * Gets the value of a namespaced session variable if it exists
     *
     * @param string  $type  Type of data being stored (a subset of stored data)
     * @param string  $namespace  Namespace to use
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value to return if no value exists
     * @throws Exception
     * @return mixed
     */
    public static function get($type, $namespace, $key, $default=null)
    {
        // starts up the session if it hasn't already been started
        self::startSession();

        // check that this key exists
        if (!self::isKey($type, $namespace, $key)) {
            return $default;
        }

        return $_SESSION['_statamic'][$type][$namespace][$key];
    }


    /**
     * Sets the value of a namespaced session variable
     *
     * @param string  $type  Type of data being stored (a subset of stored data)
     * @param string  $namespace  Namespace to set
     * @param string  $key  Key to set within the namespace
     * @param mixed  $value  Value to set
     * @return void
     */
    public static function set($type, $namespace, $key, $value)
    {
        // starts up the session if it hasn't already been started
        self::startSession();
        
        // ensure arrays exist
        self::ensure($namespace, $type);

        $_SESSION['_statamic'][$type][$namespace][$key] = $value;
    }


    /**
     * Ensures that a given $type and $namespace exist as arrays in the session
     * 
     * @param string  $type  Type to ensure for this session
     * @param string  $namespace  Namespace to ensure for this session
     * @return void
     */
    public static function ensure($type, $namespace)
    {
        self::ensureType($type);
        self::ensureNamespace($type, $namespace);
    }


    /**
     * Ensures that a given $type exists in the session
     * 
     * @param string  $type  Type to ensure
     * @return void
     */
    public static function ensureType($type)
    {
        if (!self::isType($type)) {
            $_SESSION['_statamic'][$type] = array();
        }
    }


    /**
     * Ensures that a given $namespace exists in the session/$type
     * 
     * @param string  $type  Type to ensure
     * @param string  $namespace  Namespace to ensure
     * @return void
     */
    public static function ensureNamespace($type, $namespace)
    {
        if (!self::isNamespace($namespace, $type)) {
            $_SESSION['_statamic'][$type] = array();
        }
    }


    /**
     * destroy
     * Destroys a namespace, unsetting all values within it
     *
     * @param string  $type  Type of data being stored (a subset of stored data)
     * @param string  $namespace  Namespace to destroy
     * @return void
     */
    public static function destroy($type, $namespace)
    {
        // starts up the session if it hasn't already been started
        self::startSession();

        if (self::isNamespace($type, $namespace)) {
            unset($_SESSION['_statamic'][$type][$namespace]);
        }
    }


    /**
     * Unsets a given $key from a given $namespace
     *
     * @param string  $type  Type of data being stored (a subset of stored data)
     * @param string  $namespace  Namespace to use
     * @param string  $key  Key to unset
     * @return void
     */
    public static function unsetKey($type, $namespace, $key)
    {
        // starts up the session if it hasn't already been started
        self::startSession();

        if (self::isKey($type, $namespace, $key)) {
            unset($_SESSION['_statamic'][$type][$namespace][$key]);
        }
    }


    /**
     * Checks if a given $type exists
     * 
     * @param string  $type  Type to check
     * @return boolean
     */
    public static function isType($type)
    {
        // starts up the session if it hasn't already been started
        self::startSession();

        return (isset($_SESSION['_statamic'][$type]) && is_array($_SESSION['_statamic'][$type]));
    }


    /**
     * Checks if a given $namespace exists
     *
     * @param string  $type  Type of data being stored (a subset of stored data)
     * @param string  $namespace  Namespace to check
     * @return boolean
     */
    public static function isNamespace($type, $namespace)
    {
        // starts up the session if it hasn't already been started
        self::startSession();

        return (self::isType($type) && isset($_SESSION['_statamic'][$type][$namespace]) && is_array($_SESSION['_statamic'][$type][$namespace]));
    }


    /**
     * Checks to see if a given $key exists within a given $namespace
     *
     * @param string  $type  Type of data being stored (a subset of stored data)
     * @param string  $namespace  Namespace to check within
     * @param string  $key  Key to check
     * @return boolean
     */
    public static function isKey($type, $namespace, $key)
    {
        self::startSession();

        if (!self::isType($type) || !self::isNamespace($type, $namespace)) {
            return false;
        }

        return (isset($_SESSION['_statamic'][$type][$namespace][$key]));
    }


    /**
     * Starts up the session if it hasn't already been started, otherwise aborts
     *
     * @return void
     */
    protected static function startSession()
    {
        // enable sessions if that hasn't been done
        if (!isset($_SESSION)) {
            session_start();
        }

        // check for our namespaced variables
        if (isset($_SESSION['_statamic'])) {
            return;
        }

        // start up our namespaced session
        $_SESSION['_statamic'] = array();
    }
}