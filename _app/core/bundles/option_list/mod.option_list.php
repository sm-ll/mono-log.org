<?php
/**
 * Modifier_option_list
 * Implodes an array into a pipe delimited string
 *
 * @author Statamic
 */

class Modifier_option_list extends Modifier
{
    public function index($value, $parameters=array())
    {
        return join('|', $value);
    }
}