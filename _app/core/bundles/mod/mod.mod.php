<?php
/**
 * Modifier_mod
 * Performs modulus division on a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_mod extends Modifier
{
    public function index($value, $parameters=array()) {
        if (!isset($parameters[0]) || !is_numeric($parameters[0])) {
            return $value;
        }
        
        return ($value % $parameters[0]);
    }
}