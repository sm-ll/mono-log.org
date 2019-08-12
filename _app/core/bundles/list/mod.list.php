<?php
/**
 * Modifier_list
 * Implodes an array into a comma delimited string
 *
 * @author Statamic
 */

class Modifier_list extends Modifier
{
    public function index($value, $parameters=array())
    {
        return join(", ", $value);
    }
}