<?php
/**
 * Modifier_link
 * Converts a variable's value to an HTML `a` tag
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_link extends Modifier
{
    public function index($value, $parameters=array()) {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            // email address
            return '<a href="mailto:' . $value . '" />' . $value . '</a>';
        } else {
            return '<a href="' . $value . '" />' . $value . '</a>';
        }
    }
}