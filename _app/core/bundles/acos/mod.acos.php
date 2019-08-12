<?php
/**
 * Modifier_acos
 * Gets the arc-cosine of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_acos extends Modifier
{
    public function index($value, $parameters=array()) {
        return acos($value);
    }
}