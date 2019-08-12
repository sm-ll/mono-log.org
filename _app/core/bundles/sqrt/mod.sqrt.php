<?php
/**
 * Modifier_sqrt
 * Gets the square-root of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_sqrt extends Modifier
{
    public function index($value, $parameters=array()) {
        return sqrt($value);
    }
}