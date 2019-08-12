<?php
/**
 * Request
 * API for interacting with request variables
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Request
{
    /**
     * Returns a $_GET variable value, or $default if not available
     *
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value if no GET variable is set
     * @return mixed
     */
    public static function get($key, $default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->get($key), $default);
    }


    /**
     * Returns a $_POST variable value, or $default if not available
     *
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value if no POST variable is set
     * @return mixed
     */
    public static function post($key, $default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->post($key), $default);
    }


    /**
     * Returns a $_POST variable value if set, falling back to $_GET, or $default if not available
     *
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value if no POST or GET variable is set
     * @return mixed
     */
    public static function fetch($key, $default=NULL)
    {
        return Helper::pick(
            self::post($key, $default),
            self::get($key, $default),
            $default
        );
    }


    /**
     * Returns a $_PUT variable value, or $default if not available
     *
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value if no PUT variable is set
     * @return mixed
     */
    public static function put($key, $default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->put($key), $default);
    }


    /**
     * Checks to see if the current request was made with Ajax
     *
     * @return bool
     */
    public static function isAjax()
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->isAjax());
    }


    /**
     * Returns a given request $header's value, or $default if not available
     *
     * @param string  $header  Header to retrieve
     * @param mixed  $default  Default value if no $header was set
     * @return mixed
     */
    public static function getHeader($header, $default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->headers($header), $default);
    }


    /**
     * Retrieves the content type for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getContentType($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getContentType(), $default);
    }


    /**
     * Retrieves the media type for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getMediaType($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getMediaType(), $default);
    }


    /**
     * Retrieves the media type parameters for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getMediaTypeParameters($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getMediaTypeParams(), $default);
    }


    /**
     * Retrieves the character set for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getCharacterSet($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getContentCharset(), $default);
    }


    /**
     * Retrieves the content length for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getContentLength($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getContentLength(), $default);
    }


    /**
     * Retrieves the host for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getHost($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getHost(), $default);
    }


    /**
     * Retrieves the host (with port) for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getHostWithPort($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getHostWithPort(), $default);
    }


    /**
     * Retrieves the port for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getPort($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getPort(), $default);
    }


    /**
     * Retrieves the scheme (http or https) for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getScheme($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getScheme(), $default);
    }


    /**
     * Retrieves the path for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getPath($default=NULL)
    {
        return Helper::pick(URL::sanitize(\Slim\Slim::getInstance()->request()->getPath()), $default);
    }


    /**
     * Retrieves the URL for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getURL($default=NULL)
    {
        return Helper::pick(URL::sanitize(\Slim\Slim::getInstance()->request()->getUrl()), $default);
    }


    /**
     * Retrieves the IP address for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getIP($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getIp(), $default);
    }


    /**
     * Retrieves the referrer for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getReferrer($default=NULL)
    {
        return Helper::pick(URL::sanitize(\Slim\Slim::getInstance()->request()->getReferrer()), $default);
    }


    /**
     * Retrieves the user agent for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getUserAgent($default=NULL)
    {
        return Helper::pick(\Slim\Slim::getInstance()->request()->getUserAgent(), $default);
    }


    /**
     * Retrieves the resource URI for this request
     *
     * @param mixed  $default  Default value to use if not set
     * @return string
     */
    public static function getResourceURI($default=NULL)
    {
        return Helper::pick(URL::sanitize(\Slim\Slim::getInstance()->request()->getResourceUri()), $default);
    }
}
