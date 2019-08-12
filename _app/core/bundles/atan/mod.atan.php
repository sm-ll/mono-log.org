<?php
/**
 * Modifier_atan
 * Gets the arc-tangent of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_atan extends Modifier
{
    public function index($value, $parameters=array()) {
        return atan($value);
    }
}