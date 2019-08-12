<?php
/**
 * Modifier_deslugify
 * Replaces hyphens in a variable with spaces
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_deslugify extends Modifier
{
    public function index($value, $parameters=array()) {
        return trim(preg_replace('~[-_]~', ' ', $value), " ");
    }
}