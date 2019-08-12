<?php
/**
 * Modifier_is_today
 * Checks to see if a date value is today
 *
 * @author  Statamic
 */
use \Carbon\Carbon;

class Modifier_is_today extends Modifier
{
    public function index($value, $parameters=array())
    {
		$time = Date::resolve($value);

		return Carbon::createFromTimestamp($time)->isToday();
    }
}