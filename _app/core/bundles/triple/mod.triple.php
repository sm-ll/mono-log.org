<?php
/**
 * Modifier_triple
 * Triple a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_triple extends Modifier
{
    public function index($value, $parameters=array()) {
        if (is_numeric($value)) {
            return $value * 3;
        } elseif (!is_array($value)) {
            return str_repeat($value, 3);
        } else {
            return $value;
        }
    }
}