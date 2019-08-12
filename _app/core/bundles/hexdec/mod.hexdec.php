<?php
/**
 * Modifier_hexdec
 * Converts a variable from hexadecimal to decimal
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_hexdec extends Modifier
{
    public function index($value, $parameters=array()) {
        return hexdec($value);
    }
}