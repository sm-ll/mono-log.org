<?php
/**
 * Date
 * API for managing and manipulating dates
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Date
{
    /**
     * Resolves a given date string or integer into timestamp
     *
     * @param mixed  $date  Date to resolve
     * @return int
     */
    public static function resolve($date)
    {
        return (!is_numeric($date)) ? strtotime($date) : $date;
    }


    /**
     * Formats a given $date (automatically resolving timestamps) to a given $format
     *
     * @param string  $format  Format to use (based on PHP's date formatting)
     * @param mixed  $date  Date string or timestamp (if blank, now)
     * @return mixed
     */
    public static function format($format, $date=NULL)
    {
        return date($format, self::resolve(Helper::pick($date, time())));
    }
}