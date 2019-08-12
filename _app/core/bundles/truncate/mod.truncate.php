<?php
/**
 * Modifier_truncate
 * Truncates the value of a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_truncate extends Modifier
{
    public function index($value, $parameters=array()) {
        
        $length = array_get($parameters, 0, 30);
        
        if (strlen($value) > $length) {
            return substr($value, 0, $length) . "&hellip;";
        }
        
        return $value;
    }
}