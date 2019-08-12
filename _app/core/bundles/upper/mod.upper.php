<?php
/**
 * Modifier_upper
 * Converts a variable to uppercase
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_upper extends Modifier
{
    public function index($value, $parameters=array()) {
        return strtoupper($value);
    }
}