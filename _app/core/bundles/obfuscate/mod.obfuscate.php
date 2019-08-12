<?php
/**
 * Modifier_obfuscate
 * Obfuscates a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_obfuscate extends Modifier
{
    public function index($value, $parameters=array()) {
        return HTML::obfuscateEmail($value);
    }
}