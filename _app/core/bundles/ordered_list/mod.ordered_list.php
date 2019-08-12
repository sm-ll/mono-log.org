<?php
/**
 * Modifier_ordered_list
 * Implodes an array into a string in the form of an unordered list
 *
 * @author Statamic
 */

class Modifier_ordered_list extends Modifier
{
    public function index($value, $parameters=array())
    {
        return "<ol><li>" . join("</li><li>", $value) . "</li></ol>";
    }
}