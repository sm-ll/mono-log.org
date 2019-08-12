<?php
/**
 * Modifier_deg2rad
 * Converts a variables from degrees to radians
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_deg2rad extends Modifier
{
    public function index($value, $parameters=array()) {
        return deg2rad($value);
    }
}