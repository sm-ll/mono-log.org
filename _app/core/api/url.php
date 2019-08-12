<?php
/**
 * URL
 * API for inspecting and manipulating URLs
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class URL
{
    /**
     * Format a given URI.
     *
     * @param  string  $uri
     * @return string
     */
    public static function format($uri)
    {
        return rtrim(self::tidy('/' . $uri), '/') ?: '/';
    }


    /**
     * Determine if the given URL is valid.
     *
     * @param  string  $url
     * @return bool
     */
    public static function isValid($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
    }


    /**
     * Get the full URL for the current request.
     *
     * @param boolean  $include_root  Should this include the site root itself?
     * @return string
     */
    public static function getCurrent($include_root = true)
    {
        $url = Request::getResourceURI();

        if ($include_root) {
            $url = Config::getSiteRoot() . '/' . $url;
        }

        return self::sanitize(self::format($url));
    }


    /**
     * Creates a full URL from a local one, assumes you've accounted for the site's root in a subfolder
     * 
     * @param string  $url  URL to make full
     * @return string
     */
    public static function makeFull($url)
    {
        return (self::isRelativeUrl($url)) ? self::tidy(self::getSiteUrl() . $url) : $url;
    }


    /**
     * Checks whether a URL is relative or not
     * @param  string  $url
     * @return boolean
     */
    public static function isRelativeUrl($url)
    {
        $parts = parse_url($url);

        return ! array_get($parts, 'scheme', false);
    }


    /**
     * Checks whether a URL is external or not
     * @param  string  $url
     * @return boolean
     */
    public static function isExternalUrl($url)
    {
        return ! Pattern::startsWith(URL::makeFull($url), URL::getSiteUrl());
    }


    /**
     * Get the current site url from Apache headers
     * @return string
     */
    public static function getSiteUrl()
    {
        $protocol = ( ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'] . '/';
        
        return $protocol . $domainName;
    }


    /**
     * Redirect visitor to a specified URL
     *
     * @param string  $url  URL to redirect to
     * @param int  $status  Status code to use
     * @return void
     **/
    public static function redirect($url, $status = 302)
    {
        $app = \Slim\Slim::getInstance();

        if (self::isRelativeUrl($url)) {
            $url = self::makeFull($url);
        }

        $app->redirect($url, $status);
    }


    /**
     * Assembles a URL from an ordered list of segments
     *
     * @param string  Open ended number of arguments
     * @return string
     */
    public static function assemble()
    {
        $args = func_get_args();

        if (!is_array($args) || !count($args)) {
            return NULL;
        }

        return self::tidy('/' . join($args, '/'));
    }


    /**
     * Gets the value of pagination in the current URL
     *
     * @return int
     */
    public static function getCurrentPaginationPage()
    {
        return Helper::pick(Request::get(Config::getPaginationVariable()), 1);
    }


    /**
     * Pops off the last segment of a given URL
     *
     * @param string  $url  URL to pop
     * @return string
     */
    public static function popLastSegment($url)
    {
        $url_array = explode('/', $url);
        array_pop($url_array);

        return (is_array($url_array)) ? implode('/', $url_array) : $url_array;
    }

    /**
     * Removes occurrences of "//" in a $path (except when part of a protocol)
     * Alias of Path::tidy()
     *
     * @param string  $url  URL to remove "//" from
     * @return string
     */
    public static function tidy($url)
    {
        return Path::tidy($url);
    }
    
    
    /**
     * Sanitizes a variable
     * 
     * @param string  $variable  Variable to sanitize
     * @return string
     */
    public static function sanitize($variable)
    {
        if (is_array($variable)) {
            array_walk_recursive($variable, function(&$item, $key) {
                $item = htmlspecialchars(urldecode($item));
            });
        } else {
            $variable = htmlspecialchars(urldecode($variable));
        }
        
        return $variable;
    }
    
    
    /**
     * Appends a get query appropriately
     * 
     * @param string  $url  URL base
     * @param string  $key  Key of get variable
     * @param string  $value  Value of get variable
     * @return string
     */
    public static function appendGetVariable($url, $key, $value)
    {
        // set delimiter
        $delimiter = (strpos($url, '?') !== false) ? '&' : '?';
        
        // return appended URL
        return $url . $delimiter . $key . '=' . urlencode($value);
    }
    
    
    /**
     * Prepends the site's configured site root onto given $url
     * 
     * @param string  $url  URL to prepend
     * @return string
     */
    public static function prependSiteRoot($url)
    {
        return Path::tidy(Config::getSiteRoot() . $url);
    }

    /**
     * Strip taxonomy segments from the URL
     * 
     * @param string  $url  URL to prepend
     * @return string
     */
    public static function stripTaxonomy($url)
    {
        $taxonomies = Config::getTaxonomies();
        $segments = explode('/', ltrim($url, '/'));
        
        array_pop($segments);
        array_pop($segments);

        return implode($segments, '/');
    }
}
