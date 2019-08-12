<?php
/**
 * Modifier_relative
 * Relative Date Strings
 *
 * @author Statamic
 */
use \Carbon\Carbon;

class Modifier_relative extends Modifier
{
    public function index($value, $parameters = array())
    {
        $use_smart_relative = (!empty($parameters[0]) && $parameters[0] == 'smart');
        
        
        if ($use_smart_relative) {
            // find today, yesterday, and beyond
            $today      = Date::resolve('today midnight');
            $yesterday  = Date::resolve('yesterday midnight');
            $tomorrow   = Date::resolve('tomorrow midnight');
            $two_days   = Date::resolve('+2 days midnight');
            $hour_ago   = Date::resolve('-1 hour');
            $in_an_hour = Date::resolve('+1 hour');
            $now        = time();
            
            // normalize date
            $timestamp  = Date::resolve($value);
            
            // now check
            if ($timestamp > $two_days || $timestamp < $yesterday) {
                // this is outside of an immediate window, just return date
                return Date::format(Config::getDateFormat(), $timestamp) . ' ' . __('at') . ' ' . Date::format(Config::getTimeFormat(), $timestamp);
            } elseif ($timestamp > $hour_ago && $timestamp < $in_an_hour) {
                // this is very near by, return relative date
                return Carbon::createFromTimestamp($timestamp)->diffForHumans();
            } elseif ($timestamp < $now) {
                if ($timestamp > $today) {
                    // this is today
                    return __('today') . ' ' . __('at') . ' ' . Date::format(Config::getTimeFormat(), $timestamp);
                } else {
                    // this is yesterday
                    return __('yesterday') . ' ' . __('at') . ' ' . Date::format(Config::getTimeFormat(), $timestamp);
                }
            } else {
                // this is tomorrow
                return __('tomorrow') . ' ' . __('at') . ' ' . Date::format(Config::getTimeFormat(), $timestamp);
            }
            
        } else {
            if (is_numeric($value)) {
                // this is a timestamp
                return Carbon::createFromTimestamp($value)->diffForHumans();
            }
            
            // this is a string
            return Carbon::parse($value)->diffForHumans();
        }
    }
}