<?php
/**
 * Modifier_log
 * Gets the natural logarithmic value of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_log10 extends Modifier
{
    public function index($value, $parameters=array()) {
        return log10($value);
    }
}