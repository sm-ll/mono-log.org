<?php
/**
 * Modifier_double
 * Doubles a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_double extends Modifier
{
    public function index($value, $parameters=array()) {
        if (is_numeric($value)) {
            return $value * 2;
        } elseif (!is_array($value)) {
            return str_repeat($value, 2);
        } else {
            return $value;
        }
    }
}