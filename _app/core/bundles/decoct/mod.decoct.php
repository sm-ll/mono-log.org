<?php
/**
 * Modifier_decoct
 * Converts a variable from decimal to octal
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_decoct extends Modifier
{
    public function index($value, $parameters=array()) {
        return decoct($value);
    }
}