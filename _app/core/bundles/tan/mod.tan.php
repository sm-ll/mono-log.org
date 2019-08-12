<?php
/**
 * Modifier_tan
 * Gets the tangent of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_tan extends Modifier
{
    public function index($value, $parameters=array()) {
        return tan($value);
    }
}