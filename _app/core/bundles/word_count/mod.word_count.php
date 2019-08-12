<?php
/**
 * Modifier_word_count
 * Count the number of words in a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_word_count extends Modifier
{
    public function index($value, $parameters=array()) {
        return str_word_count($value);
    }
}