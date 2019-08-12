<?php
/**
 * Modifier_trim
 * Trims space off the ends of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 */
class Modifier_trim extends Modifier
{
    public function index($value, $parameters=array()) {
    	$charlist = implode($parameters);
        
        return trim($value, $charlist);
    }
}