<?php
/**
 * Modifier_empty
 * Checks to see if a variable is empty
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_empty extends Modifier
{
    public function index($value, $parameters=array()) {
        return (Helper::isEmptyArray($value)) ? "true" : "";
    }
}