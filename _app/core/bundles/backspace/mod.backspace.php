<?php
/**
 * Modifier_backspace
 * Removes a given number of chracters from the end of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_backspace extends Modifier
{
    public function index($value, $parameters=array()) {
        if (is_array($value) || !isset($parameters[0]) || !is_numeric($parameters[0]) || $parameters[0] < 0) {
            return $value;
        }
        
        return substr($value, 0, -$parameters[0]);
    }
}