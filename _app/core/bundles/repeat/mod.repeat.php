<?php
/**
 * Modifier_repeats
 * Repeats the value of a variable a given number of times
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_repeat extends Modifier
{
    public function index($value, $parameters=array()) {
        $multiplier = (!isset($parameters[0]) || !is_numeric($parameters[0])) ? 1 : (int) $parameters[0];
        return str_repeat($value, $multiplier);
    }
}