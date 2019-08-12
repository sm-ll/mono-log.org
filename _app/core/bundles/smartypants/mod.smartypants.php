<?php
/**
 * Modifier_smartypants
 * Applies SmartyPants to a variable's value
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_smartypants extends Modifier
{
    public function index($value, $parameters=array()) {
        return Parse::smartypants($value);
    }
}