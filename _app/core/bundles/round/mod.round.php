<?php
/**
 * Modifier_round
 * Returns the value of a variable rounded to the nearest precision
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_round extends Modifier
{
    public function index($value, $parameters=array()) {
        $precision = (!isset($parameters[0]) || !is_numeric($parameters[0])) ? 0 : (int) $parameters[0];
        return round($value, $precision);
    }
}