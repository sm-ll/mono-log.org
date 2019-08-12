<?php
/**
 * Modifier_exponent
 * Raises a variable to a given power, aliased by "^"
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_exponent extends Modifier
{
    public function index($value, $parameters=array()) {
        $power = (!isset($parameters[0]) || !is_numeric($parameters[0])) ? 1 : $parameters[0];
        return pow($value, $power);
    }
}