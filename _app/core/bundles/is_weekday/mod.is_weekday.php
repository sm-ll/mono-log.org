<?php
/**
 * Modifier_is_weekday
 * Checks to see if a date variable is a weekday
 *
 * @author Statamic
 */
use \Carbon\Carbon;

class Modifier_is_weekday extends Modifier
{
    public function index($value, $parameters=array())
    {
        $time = Date::resolve($value);
        
        return Carbon::createFromTimestamp($time)->isWeekday();
    }
}