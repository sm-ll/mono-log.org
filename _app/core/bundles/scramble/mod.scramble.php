<?php
/**
 * Modifier_scramble
 * Randomly rearrange the letters in a string or list
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_scramble extends Modifier
{
    public function index($value, $parameters=array()) {
        if (is_array($value)) {
            shuffle($value);
        } else {
            $value = str_shuffle($value);
        }
        
        return $value;
    }
}