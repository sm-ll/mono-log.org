<?php
/**
 * Modifier_sin
 * Gets the sine of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_sin extends Modifier
{
    public function index($value, $parameters=array()) {
        return sin($value);
    }
}