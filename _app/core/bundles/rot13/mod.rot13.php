<?php
/**
 * Modifier_rot13
 * Performs ROT13 on a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_rot13 extends Modifier
{
    public function index($value, $parameters=array()) {
        return str_rot13($value);
    }
}