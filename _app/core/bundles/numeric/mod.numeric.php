<?php
/**
 * Modifier_numeric
 * Checks to see if a variable is numeric
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_numeric extends Modifier
{
    public function index($value, $parameters=array()) {
        return (is_numeric($value)) ? "true" : "";
    }
}