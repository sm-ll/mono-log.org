<?php
/**
 * Modifier_spaced_list
 * Implodes an array into a spaced delimited string
 *
 * @author Statamic
 */

class Modifier_spaced_list extends Modifier
{
    public function index($value, $parameters=array())
    {
        return join(' ', $value);
    }
}