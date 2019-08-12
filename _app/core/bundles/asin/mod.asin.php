<?php
/**
 * Modifier_asin
 * Gets the arc-sine of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_asin extends Modifier
{
    public function index($value, $parameters=array()) {
        return asin($value);
    }
}