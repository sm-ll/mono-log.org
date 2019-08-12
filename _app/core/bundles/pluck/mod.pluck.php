<?php
/**
 * Modifier_pluck
 * Plucks a specified group of keys from an associative array
 *
 * @author Statamic
 */

class Modifier_pluck extends Modifier
{
    public function index($value, $parameters=array())
    {
        if ($key = array_get($parameters, 0)) {
            $value = array_map(function($var) use ($key) {
                return $var[$key];
            }, $value);
        }
        
        return $value;
    }
}