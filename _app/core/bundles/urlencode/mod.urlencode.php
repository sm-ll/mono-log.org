<?php
/**
 * Modifier_urlencode
 * URL-encodes a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_urlencode extends Modifier
{
    public function index($value, $parameters=array()) {
        return urlencode($value);
    }
}