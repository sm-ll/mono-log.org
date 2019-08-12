<?php
/**
 * Modifier_cos
 * Gets the cosine of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_cos extends Modifier
{
    public function index($value, $parameters=array()) {
        return cos($value);
    }
}