<?php
/**
 * Modifier_in_past
 * Checks to see if a variable is in the past
 *
 * @author Statamic
 */
use \Carbon\Carbon;

class Modifier_in_past extends Modifier
{
    public function index($value, $parameters=array())
    {
        $time = Date::resolve($value);
        
        return Carbon::createFromTimestamp($time)->isPast();
    }
}