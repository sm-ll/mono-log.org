<?php
/**
 * Modifier_log
 * Gets the logarithmic value of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_log extends Modifier
{
    public function index($value, $parameters=array()) {
        $base = (!isset($parameters[0]) || !is_numeric($parameters[0])) ? M_E : (int) $parameters[0];
        return log($value, $base);
    }
}