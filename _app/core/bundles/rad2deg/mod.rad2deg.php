<?php
/**
 * Modifier_rad2deg
 * Converts a variables from radians to degrees
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_rad2deg extends Modifier
{
    public function index($value, $parameters=array()) {
        return rad2deg($value);
    }
}