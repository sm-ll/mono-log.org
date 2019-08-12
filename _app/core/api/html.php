<?php
/**
 * HTML
 * API for creating quick HTML
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class HTML
{

    /**
     * Convert HTML characters to entities.
     *
     * The encoding specified in the application configuration file will be used.
     *
     * @param  string  $value  Text to convert
     * @return string
     */
    public static function convertEntities($value)
    {
        return htmlentities($value, ENT_QUOTES, Config::get('encoding', 'UTF-8'), FALSE);
    }


    /**
     * Convert entities to HTML characters.
     *
     * @param  string  $value  Text to decode
     * @return string
     */
    public static function decodeEntities($value)
    {
        return html_entity_decode($value, ENT_QUOTES, Config::get('encoding', 'UTF-8'));
    }


    /**
     * Convert HTML special characters.
     *
     * The encoding specified in the application configuration file will be used.
     *
     * @param  string  $value  Text to convert
     * @return string
     */
    public static function convertSpecialCharacters($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, Config::get('encoding', 'UTF-8'), FALSE);
    }


    /**
     * Generate a link to a JavaScript file.
     *
     * <code>
     *    // Generate a link to a JavaScript file
     *    echo HTML::script('js/jquery.js');
     *
     *    // Generate a link to a JavaScript file and add some attributes
     *    echo HTML::script('js/jquery.js', array('defer'));
     * </code>
     *
     * @param  string  $url  Url for the script
     * @param  array   $attributes  List of attributes to include in the link tag
     * @return string
     */
    public static function includeScript($url, $attributes = array())
    {
        // $url = URL::to_asset($url);

        return '<script src="' . $url . '"' . self::buildAttributes($attributes) . '></script>' . PHP_EOL;
    }


    /**
     * Generate a link to a CSS file.
     *
     * If no media type is selected, "all" will be used.
     *
     * <code>
     *    // Generate a link to a CSS file
     *    echo HTML::style('css/common.css');
     *
     *    // Generate a link to a CSS file and add some attributes
     *    echo HTML::style('css/common.css', array('media' => 'print'));
     * </code>
     *
     * @param  string  $url  URL for the stylesheet
     * @param  array   $attributes  List of attributes to include in the link tag
     * @return string
     */
    public static function includeStylesheet($url, $attributes = array())
    {
        $defaults = array('media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet');

        $attributes = $attributes + $defaults;

        // $url = URL::to_asset($url);

        return '<link href="' . $url . '"' . self::buildAttributes($attributes) . '>' . PHP_EOL;
    }


    /**
     * Generate a HTML link.
     *
     * <code>
     *    // Generate a link to a location within the application
     *    echo HTML::makeLink('user/profile', 'User Profile');
     *
     *    // Generate a link to a location outside of the application
     *    echo HTML::makeLink('http://google.com', 'Google');
     * </code>
     *
     * @param  string  $url  URL to use for the link
     * @param  string  $title  Title attribute to use on the link
     * @param  array   $attributes  List of attributes to include in the link tag
     * @param  bool    $https  Should we use https?
     * @return string
     */
    public static function makeLink($url, $title = NULL, $attributes = array(), $https = false)
    {

        $title = (is_null($title)) ? $url : $title;

        return '<a href="' . $url . '"' . self::buildAttributes($attributes) . '>' . self::convertEntities($title) . '</a>';
    }


    /**
     * Generate a HTTPS HTML link.
     *
     * @param  string  $url  URL to use
     * @param  string  $title  Title attribute to use on the link
     * @param  array   $attributes  List of attributes to include in the link tag
     * @return string
     */
    public static function linkToSecure($url, $title = NULL, $attributes = array())
    {
        return self::makeLink($url, $title, $attributes, TRUE);
    }


    /**
     * Generate an HTML mailto link.
     *
     * The E-Mail address will be obfuscated to protect it from spam bots.
     *
     * @param  string  $email  Email address to use
     * @param  string  $title  Title attribute to use on the tag
     * @param  array   $attributes  List of attributes to include in the link tag
     * @return string
     */
    public static function mailTo($email, $title = NULL, $attributes = array())
    {
        $email = self::obfuscateEmail($email);

        if (is_null($title)) $title = $email;

        $email = '&#109;&#097;&#105;&#108;&#116;&#111;&#058;' . $email;

        return '<a href="' . $email . '"' . self::buildAttributes($attributes) . '>' . self::convertEntities($title) . '</a>';
    }

    /**
     * Obfuscate an e-mail address to prevent spam-bots from sniffing it.
     *
     * @param  string  $email  Email string to obfuscate
     * @return string
     */
    public static function obfuscateEmail($email)
    {
        return str_replace('@', '&#64;', self::obfuscate($email));
    }

    /**
     * Generate an HTML image element.
     *
     * @param  string  $url  URL of image
     * @param  string  $alt  String to use for alt text on the image tag
     * @param  array   $attributes  List of attributes to include in the image tag
     * @return string
     */
    public static function makeImage($url, $alt = '', $attributes = array())
    {
        $attributes['alt'] = $alt;

        return '<img src="' . URL::to_asset($url) . '"' . self::buildAttributes($attributes) . '>';
    }

    /**
     * Generate an ordered list of items.
     *
     * @param  array   $list  List of items to list
     * @param  array   $attributes  List of attributes to include in the list tag
     * @return string
     */
    public static function makeOl($list, $attributes = array())
    {
        return self::makeList('ol', $list, $attributes);
    }

    /**
     * Generate an un-ordered list of items.
     *
     * @param  array   $list  List of items to list
     * @param  array   $attributes  List of attributes to include in the list tag
     * @return string
     */
    public static function makeUl($list, $attributes = array())
    {
        return self::makeList('ul', $list, $attributes);
    }

    /**
     * Generate an ordered or unordered list.
     *
     * @param  string  $type  Type of list to create (ol|ul)
     * @param  array   $list  List of items to list
     * @param  array   $attributes  List of attributes to include in the list tag
     * @return string
     */
    public static function makeList($type, $list, $attributes = array())
    {
        $html = '';

        if (count($list) == 0) return $html;

        foreach ($list as $key => $value) {
            // If the value is an array, we will recurse the function so that we can
            // produce a nested list within the list being built. Of course, nested
            // lists may exist within nested lists, etc.
            if (is_array($value)) {
                if (is_int($key)) {
                    $html .= self::makeList($type, $value);
                } else {
                    $html .= '<li>' . $key . self::makeList($type, $value) . '</li>';
                }
            } else {
                $html .= '<li>' . self::convertEntities($value) . '</li>';
            }
        }

        return '<' . $type . self::buildAttributes($attributes) . '>' . $html . '</' . $type . '>';
    }

    /**
     * Generate an input.
     *
     * @param  string  $type  Type of input to create (text, email, etc)
     * @param  array   $attributes  List of attributes to include in the input tag
     * @return string
     */
    public static function makeInput($type, $attributes = array(), $is_required = false)
    {
        $attributes = array_merge($attributes, compact($type));

        if ($is_required) {

            $attributes = array_merge($attributes, array('data-required' => 'true'));
        }

        return '<input type="' . $type . '"' . self::buildAttributes($attributes) . ' />';
    }


    /**
     * Generate a textarea.
     *
     * @param  array   $attributes  List of attributes to include in the input tag
     * @return string
     */
    public static function makeTextarea($value = '', $attributes = array(), $is_required = false)
    {

        if ($is_required) {
            $attributes = array_merge($attributes, array('data-required' => 'true'));
        }

        return '<textarea ' . self::buildAttributes($attributes) . ' />'. $value .'</textarea>';
    }

    /**
     * Build a list of HTML attributes from an array.
     *
     * @param  array   $attributes  List of attributes to build
     * @return string
     */
    public static function buildAttributes($attributes)
    {
        $html = array();

        foreach ((array)$attributes as $key => $value) {
            // For numeric keys, we will assume that the key and the value are the
            // same, as this will convert HTML attributes such as "required" that
            // may be specified as required="required", etc.
            if (is_numeric($key)) $key = $value;

            if (!is_null($value)) {
                if (is_array($value)) {
                    $value = implode(' ', $value);
                }

                // if empty, then don't add attribute
                if ($value === '') continue;

                $html[] = $key . '="' . self::convertEntities($value) . '"';
            }
        }

        return (count($html) > 0) ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Obfuscate a string to prevent spam-bots from sniffing it.
     *
     * @param  string  $value  String to obfuscate
     * @return string
     */
    protected static function obfuscate($value)
    {
        $safe = '';

        foreach (str_split($value) as $letter) {
            // To properly obfuscate the value, we will randomly convert each
            // letter to its entity or hexadecimal representation, keeping a
            // bot from sniffing the randomly obfuscated letters.
            switch (rand(1, 3)) {
                case 1:
                    $safe .= '&#' . ord($letter) . ';';
                    break;

                case 2:
                    $safe .= '&#x' . dechex(ord($letter)) . ';';
                    break;

                case 3:
                    $safe .= $letter;
            }
        }

        return $safe;
    }

}