<?php
/**
 * Modifier_format
 * Formats a variable into a specified date format
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_format extends Modifier
{
    public function index($value, $parameters=array()) {
        $format = (isset($parameters[0])) ? $parameters[0] : Config::getDateFormat();
        return Date::format($format, $value);
    }
}