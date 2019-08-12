<?php
/**
 * Modifier_urldecode
 * URL-decodes a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_urldecode extends Modifier
{
    public function index($value, $parameters=array()) {
        return urldecode($value);
    }
}