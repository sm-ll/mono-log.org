<?php
/**
 * Helper
 * API for doing miscellaneous tasks
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Helper
{
    /**
     * Picks the first value that isn't null
     *
     * @return mixed
     */
    public static function pick()
    {
        $args = func_get_args();

        if (!is_array($args) || !count($args)) {
            return null;
        }

        foreach ($args as $arg) {
            if (!is_null($arg)) {
                return $arg;
            }
        }

        return null;
    }


    /**
     * Creates a random string
     *
     * @param int  $length  Length of string to return
     * @param bool  $expanded  When true, uses a more complete list of characters
     * @return string
     */
    public static function getRandomString($length=32, $expanded=false)
    {
        $string = '';
        $characters = "BCDFGHJKLMNPQRSTVWXYZbcdfghjklmnpqrstvwxwz0123456789";
        
        if ($expanded) {
            $characters = "ABCDEFGHIJKLMNPOQRSTUVWXYZabcdefghijklmnopqrstuvwxwz0123456789!@#$%^&*()~[]{}`';?><,./|+-=_";
        }
        
        $upper_limit = strlen($characters) - 1;

        for (; $length > 0; $length--) {
            $string .= $characters{rand(0, $upper_limit)};
        }

        return str_shuffle($string);
    }


    /**
     * Checks whether the given $value is an empty array or not
     *
     * @param mixed  $value  Value to check
     * @return bool
     */
    public static function isEmptyArray($value)
    {
        if (is_array($value)) {
            foreach ($value as $subvalue) {
                if (!self::isEmptyArray($subvalue)) {
                    return FALSE;
                }
            }
        } elseif (!empty($value) || $value !== '') {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Ensures that a given variable is an array
     *
     * @param mixed $value  variable to check
     * @return array
     **/
    public static function ensureArray($value)
    {
        if ( ! is_array($value)) {
            return array($value);
        }

        return $value;
    }

    /**
     * Convert an object to an array
     *
     * @param object $object
     * @return array
     **/
    public static function objectToArray($object)
    {
        if ( ! is_object($object) && ! is_array($object)) {
            return $object;
        }

        if (is_object($object)) {
            $object = (array) $object;
        }

        return array_map( 'self::objectToArray', $object);
    }

    public static function prettifyZeroIndexes($array)
    {
        if (array_values($array) === $array) {
            $new_array = array_values($array);

            return array_combine($new_array, array_map('Slug::prettify', $new_array));
        }

        return $array;
    }


    /**
     * Explodes options into an array
     *
     * @param string  $string  String to explode
     * @param bool $keyed  Are options keyed?
     * @return array
     */
    public static function explodeOptions($string, $keyed=FALSE)
    {
        $options = explode('|', $string);

        if ($keyed) {

            $temp_options = array();
            foreach ($options as $value) {

                if (strpos($value, ':')) {
                    # key:value pair present
                    list($option_key, $option_value) = explode(':', $value);
                } else {
                    # default value is false
                    $option_key   = $value;
                    $option_value = FALSE;
                }

                # set the main options array
                $temp_options[$option_key] = $option_value;
            }
            # reassign and override
            $options = $temp_options;
        }

        return $options;
    }


    /**
     * Compares two values, returns 1 if first is greater, -1 if second is, 0 if same
     *
     * @param mixed  $value_1  Value 1 to compare
     * @param mixed  $value_2  Value 2 to compare
     * @return int
     */
    public static function compareValues($value_1, $value_2) {
        // something is NULL
        if (is_null($value_1) || is_null($value_2)) {
            if (is_null($value_1) && !is_null($value_2)) {
                return 1;
            } elseif (!is_null($value_1) && is_null($value_2)) {
                return -1;
            }

            return 0;
        }

        // something is an array
        if (is_array($value_1) || is_array($value_2)) {
            if (is_array($value_1) && !is_array($value_2)) {
                return 1;
            } elseif (!is_array($value_1) && is_array($value_2)) {
                return -1;
            }

            return 0;
        }

        // something is an object
        if (is_object($value_1) || is_object($value_2)) {
            if (is_object($value_1) && !is_object($value_2)) {
                return 1;
            } elseif (!is_object($value_1) && is_object($value_2)) {
                return -1;
            }

            return 0;
        }

        // something is a boolean
        if (is_bool($value_1) || is_bool($value_2)) {
            if ($value_1 && !$value_2) {
                return 1;
            } elseif (!$value_1 && $value_2) {
                return -1;
            }

            return 0;
        }

        // string based
        if (!is_numeric($value_1) || !is_numeric($value_2)) {
            return strcasecmp($value_1, $value_2);
        }

        // number-based
        if ($value_1 > $value_2) {
            return 1;
        } elseif ($value_1 < $value_2) {
            return -1;
        }

        return 0;
    }


    /**
     * Creates a sentence list from the given $list
     *
     * @param array  $list  List of items to list
     * @param string  $glue  Joining string before the last item when more than one item
     * @param bool  $oxford_comma  Include a comma before $glue?
     * @return string
     */
    public static function makeSentenceList(Array $list, $glue="and", $oxford_comma=TRUE)
    {
        $length = count($list);

        switch ($length) {
            case 0:
            case 1:
                return join("", $list);
                break;

            case 2:
                return join(" " . $glue . " ", $list);
                break;

            default:
                $last = array_pop($list);
                $sentence  = join(", ", $list);
                $sentence .= ($oxford_comma) ? "," : "";
                return $sentence . " " . $glue . " " . $last;
        }
    }


    /**
     * Resolves a given $value, if a closure, calls closure, otherwise returns $value
     *
     * @param mixed  $value  Value or closure to resolve
     * @return mixed
     */
    public static function resolveValue($value)
    {
        return (is_callable($value) && !is_string($value)) ? call_user_func($value) : $value;
    }


    /**
     * Confirms $array is an array, then returns $key if key is set, $that if it isn't
     *
     * @param mixed  $array  Array to use
     * @param string  $key  Key to return if set
     * @param mixed  $default  Default value to return
     * @return mixed
     */
    public static function choose($array, $key, $default)
    {
        return (is_array($array) && isset($array[$key])) ? $array[$key] : $default;
    }


    /**
     * Parses a mixed folder representation into a standardized array
     *
     * @deprecated
     * @param mixed  $folders  Folders
     * @return array
     */
    public static function parseForFolders($folders)
    {
        Log::warn('Helper::parseForFolders has been deprecated. Use Parse::pipeList() instead.', 'core', 'api');
        return Parse::pipeList($folders);
    }


    /**
     * Deep merges arrays better than array_merge_recursive()
     *
     * @param arrays  takes two arrays to tango
     * @return array
     */
    public static function &arrayCombineRecursive(array &$array1, &$array2 = null)
    {
        $merged = $array1;
     
        if (is_array($array2)) {
            foreach ($array2 as $key => $val) {
                if (is_array($array2[$key])) {
                    $merged[$key] = (isset($merged[$key]) && is_array($merged[$key])) ? self::arrayCombineRecursive($merged[$key], $array2[$key]) : $array2[$key];
                } else {
                    $merged[$key] = $val;
                }
            }
        }
     
      return $merged;
    }
    
    
    /**
     * Creates a hash value for the arguments passed
     * 
     * @param mixed  ...  Arguments to include in hash
     * @return string
     */
    public static function makeHash()
    {
        $mark = Debug::markStart('math', 'hashing');

        $data = array_flatten(func_get_args());

        // return a hash of the flattened $data array
        $hash = md5(join('%', $data));

        Debug::markEnd($mark);
        
        return $hash;
    }


    /**
     * Encrypt a string
     *
     * @param  string $string
     * @return string
     */
    public static function encrypt($string)
    {
	    return Encryption::encrypt($string);
    }


    /**
     * Decrypt a string
     *
     * @param  string $string
     * @return string
     */
    public static function decrypt($string)
    {
	    return Encryption::decrypt($string);
    }


    public static function strrpos_count($haystack, $needle, $instance=0)
    {
        do {
            // get the last occurrence in the current haystack
            $last = strrpos($haystack, $needle);
            
            if ($last === false) {
                return false;
            }
            
            $haystack = substr($haystack, 0, $last);            
            $instance--;
        } while ($instance >= 0);
        
        return $last;
    }

    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     */
    public static function camelCase($value)
    {
        $value = ucwords(str_replace(array('-', '_'), ' ', $value));

        return lcfirst(str_replace(' ', '', $value));
    }
    

    /**
     * Is a given $ip_address within any of the given $ip_ranges?
     *
     * @param string  $ip_address  IP Address to check
     * @param mixed  $ip_ranges   One or more IP ranges to check
     * @return boolean
     */
    public static function isIPInRange($ip_address, $ip_ranges)
    {
        if (!is_array($ip_ranges)) {
            $ip_ranges = array($ip_ranges);
        }

        foreach ($ip_ranges as $ip_range) {
            if (strpos($ip_range, '/')) {
                // this is a CIDR range
                list($range, $netmask) = explode('/', $ip_range, 2);

                $range  = (float) sprintf("%u", ip2long($range));
                $ip     = (float) sprintf("%u", ip2long($ip_address));

                // NOT the wildcard value
                $wildcard  = pow(2, (32 - $netmask)) - 1;
                $netmask   = ~$wildcard;

                // check by ANDing the origin IP and the range address
                $result = (($ip & $netmask) == ($range & $netmask));

                if ($result) {
                    // found a good one, return true and break out
                    return true;
                }
            } else {
                if (strpos($ip_range, '-')) {
                    // this is a start and end range
                    list($lower, $upper)  = explode('-', $ip_range, 2);
                } elseif (strpos($ip_range, '*')) {
                    $lower  = str_replace('*', 0, $ip_range);
                    $upper  = str_replace('*', 255, $ip_range);
                } else {
                    $lower  = $ip_range;
                    $upper  = $ip_range;
                }

                // convert to long
                $lower  = (float) sprintf("%u", ip2long($lower));
                $upper  = (float) sprintf("%u", ip2long($upper));
                $ip     = (float) sprintf("%u", ip2long($ip_address));

                // compare
                $result = (($ip >= $lower) && ($ip <= $upper));

                if ($result) {
                    // found a good one, return true and break out
                    return true;
                }
            }
        }

        // didn't find any matches, must be false
        return false;
    }
}
