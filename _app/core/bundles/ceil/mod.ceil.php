<?php
/**
 * Modifier_ceil
 * Returns the value of a variable rounded up
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_ceil extends Modifier
{
    public function index($value, $parameters=array()) {
        return ceil((float) $value);
    }
}