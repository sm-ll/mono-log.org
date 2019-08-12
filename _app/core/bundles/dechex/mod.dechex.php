<?php
/**
 * Modifier_dechex
 * Converts a variable from decimal to hexadecimal 
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_dechex extends Modifier
{
    public function index($value, $parameters=array()) {
        return dechex($value);
    }
}