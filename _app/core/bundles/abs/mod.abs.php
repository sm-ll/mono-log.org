<?php
/**
 * Modifier_abs
 * Gets the absolute value of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_abs extends Modifier
{
    public function index($value, $parameters=array()) {
        return abs($value);
    }
}