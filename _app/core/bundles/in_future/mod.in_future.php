<?php
/**
 * Modifier_in_future
 * Checks to see if a variable is in the future
 *
 * @author  Statamic
 */
use \Carbon\Carbon;

class Modifier_in_future extends Modifier
{
    public function index($value, $parameters=array())
    {
        $time = Date::resolve($value);
        
        return Carbon::createFromTimestamp($time)->isFuture();
    }
}