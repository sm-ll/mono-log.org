<?php
/**
 * Modifier_length
 * Get the length of a string or the number of items in a list
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_length extends Modifier
{
    public function index($value, $parameters=array()) {
        return (is_array($value)) ? count($value) : strlen($value);
    }
}