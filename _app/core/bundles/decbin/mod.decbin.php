<?php
/**
 * Modifier_decbin
 * Converts a variable from decimal to binary
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_decbin extends Modifier
{
    public function index($value, $parameters=array()) {
        return decbin($value);
    }
}