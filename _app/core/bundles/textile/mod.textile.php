<?php
/**
 * Modifier_textile
 * Parses a variable for Textile
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_textile extends Modifier
{
    public function index($value, $parameters=array()) {
    	return Parse::textile($value);
    }
}