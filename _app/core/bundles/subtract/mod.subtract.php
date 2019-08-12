<?php
/**
 * Modifier_subtract
 * Subtracts a value from a variable, aliased by "-"
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_subtract extends Modifier
{
    public function index($value, $parameters=array()) {
        $number = (!isset($parameters[0]) || !is_numeric($parameters[0])) ? 0 : $parameters[0];
        return ($value - $number);
    }
}