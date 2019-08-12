<?php
/**
 * Modifier_lower
 * Converts a variable to lowercase
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_lower extends Modifier
{
    public function index($value, $parameters=array()) {
        return strtolower($value);
    }
}