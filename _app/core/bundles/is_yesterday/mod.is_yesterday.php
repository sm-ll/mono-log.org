<?php
/**
 * Modifier_is_yesterday
 * Checks to see if a date variable is yesterday
 *
 * @author Statamic
 */
use \Carbon\Carbon;

class Modifier_is_yesterday extends Modifier
{
    public function index($value, $parameters=array())
    {
        $time = Date::resolve($value);
        
        return Carbon::createFromTimestamp($time)->isYesterday();
    }
}