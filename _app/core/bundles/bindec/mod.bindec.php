<?php
/**
 * Modifier_bindec
 * Converts a variable from binary to decimal
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_bindec extends Modifier
{
    public function index($value, $parameters=array()) {
        return bindec($value);
    }
}