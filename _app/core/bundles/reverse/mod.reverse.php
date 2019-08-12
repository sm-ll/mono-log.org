<?php
/**
 * Modifier_reverse
 * Reverses the order of a string or list
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_reverse extends Modifier
{
    public function index($value, $parameters=array()) {
        if (is_array($value)) {
            return array_reverse($value);   
        } else {
            return strrev($value);
        }
    }
}