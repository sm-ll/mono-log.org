<?php
/**
 * Modifier_is_weekend
 * Checks to see if a date variable is a weekend
 *
 * @author Statamic
 */
use \Carbon\Carbon;

class Modifier_is_weekend extends Modifier
{
    public function index($value, $parameters=array())
    {
        $time = Date::resolve($value);
        
        return Carbon::createFromTimestamp($time)->isWeekend();
    }
}