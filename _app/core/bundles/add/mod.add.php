<?php
/**
 * Modifier_add
 * Adds a value to a variable, aliased by "+"
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_add extends Modifier
{
    public function index($value, $parameters=array()) {
        $number = (!isset($parameters[0]) || !is_numeric($parameters[0])) ? 0 : $parameters[0];
        return ($value + $number);
    }
}