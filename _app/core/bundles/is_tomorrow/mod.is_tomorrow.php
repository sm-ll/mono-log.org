<?php
/**
 * Modifier_is_tomorrow
 * Checks to see if a date variable is tomorrow
 *
 * @author Statamic
 */
use \Carbon\Carbon;

class Modifier_is_tomorrow extends Modifier
{
    public function index($value, $parameters=array())
    {
        $time = Date::resolve($value);
        
        return Carbon::createFromTimestamp($time)->isTomorrow();
    }
}