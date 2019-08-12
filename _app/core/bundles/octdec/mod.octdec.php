<?php
/**
 * Modifier_octdec
 * Converts a variable from octal to decimal
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_octdec extends Modifier
{
    public function index($value, $parameters=array()) {
        return octdec($value);
    }
}