<?php
/**
 * Modifier_divide
 * Divides a variable by a value, aliased by "/"
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_divide extends Modifier
{
    public function index($value, $parameters=array()) {
        $number = (!isset($parameters[0]) || !is_numeric($parameters[0])) ? 0 : $parameters[0];
        return ($value / $number);
    }
}