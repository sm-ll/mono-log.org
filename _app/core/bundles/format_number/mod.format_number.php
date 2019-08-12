<?php
/**
 * Modifier_format_number
 * Formats a variable into a specified number format
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_format_number extends Modifier
{
    public function index($value, $parameters=array()) {
        $precision = (isset($parameters[0])) ? $parameters[0] : 0;
        return number_format($value, $precision);
    }
}