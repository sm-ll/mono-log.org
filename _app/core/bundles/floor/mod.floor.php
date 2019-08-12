<?php
/**
 * Modifier_floor
 * Returns the value of a variable rounded down
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_floor extends Modifier
{
    public function index($value, $parameters=array()) {
        return floor((float) $value);
    }
}