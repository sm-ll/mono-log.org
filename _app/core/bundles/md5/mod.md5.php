<?php
/**
 * Modifier_md5
 * Gets the md5 hash for a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_md5 extends Modifier
{
    public function index($value, $parameters=array()) {
        return md5($value);
    }
}