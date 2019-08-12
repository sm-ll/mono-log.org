<?php
/**
 * Pattern
 * API for checking and validating against common regular expression patterns
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Pattern
{
    const DATE              = "/(\d{4})[\-_\.](\d{2})[\-_\.](\d{2})[\-_\.]/";
    const DATETIME          = "/(\d{4})[\-_\.](\d{2})[\-_\.](\d{2})[\-_\.](\d{4})[\-_\.]/";
    const DATE_OR_DATETIME  = "/(\d{4})[\-_\.](\d{2})[\-_\.](\d{2})[\-_\.](?:(\d{4})[\-_\.])?/";
    const NUMERIC           = "/(\d+)[\-_\.]/";
    const ORDER_KEY         = "/(?<=\/)_{0,2}(\d{4})[\-_\.](\d{2})[\-_\.](\d{2})[\-_\.](?:(\d{4})[\-_\.])?|(?<=\/)(\d+)[\-_\.]/";
    const COORDINATES       = "/^(\-?\d{1,3}(?:\.[\d]*)?)\,\s?(-?\d{1,3}(?:\.[\d]*)?)$/";
    const UUID              = "/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i";
    const TAG               = "/\{(?!\{)\s*(([|a-zA-Z0-9_\.]+))\s*\}(?!\})/im";
    const ENTRY_FILEPATH    = "/^\d{4}[\-_\.]\d{2}[\-_\.]\d{2}[\-_\.](?:\d{4}[\-_\.])?|^\d+[\-_\.].*\./";
    const PAGE_FILEPATH     = "/^_?(?:\d+[\-_\.](?!\d{2}[\-_\.]\d{2}))?[a-z]+/i";
    const USING_CONTENT     = "/\{\s*content(?:_raw)?[\s|}]/ism";


    /**
     * Checks to see whether a given $date is a valid YYYY-MM-DD(-HHII) value
     *
     * @param string  $date  Date string to check
     * @return bool
     */
    public static function isValidDate($date)
    {
        // trim string down to just yyyy-mm-dd
        $date = substr($date, 0, 10);

        // grab the delimiter (character after yyyy)
        $delimiter = substr($date, 4, 1);

        // explode that into chunks
        $chunks = explode($delimiter, $date);

        return checkdate((int) $chunks[1], (int) $chunks[2], (int) $chunks[0]);
    }


    /**
     * Checks to see if a given $haystack starts with a given $needle
     *
     * @param string  $haystack  String to check within
     * @param string  $needle  String to look for
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return (substr($haystack, 0, strlen($needle)) === $needle);
    }


    /**
     * Checks to see if a given $haystack ends with a given $needle
     *
     * @param string  $haystack  String to check within
     * @param string  $needle  String to look for
     * @param bool  $case_sensitive  Should this match be case-sensitive?
     * @return bool
     */
    public static function endsWith($haystack, $needle, $case_sensitive=TRUE)
    {
        if ($case_sensitive) {
            return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
        }

        return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
    }


    /**
     * Checks to see if a given $value matches the given $pattern
     *
     * @param string  $pattern  Pattern to match
     * @param string  $value  Value to check
     * @return bool
     */
    public static function matches($pattern, $value)
    {
        $pattern = ($pattern !== "/") ? str_replace('*', '(.*)', $pattern) . '\z' : '^/$';

        return (bool) preg_match('#' . $pattern . '#', $value);
    }


    /**
     * Checks to see if a given value is a valid UUID
     *
     * @param string  $value  Value to check
     * @return boolean
     */
    public static function isValidUUID($value)
    {
        return (bool) preg_match(Pattern::UUID, $value);
    }
}