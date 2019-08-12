<?php
/**
 * Modifier_unordered_list
 * Implodes an array into a string in the form of an unordered list
 *
 * @author Statamic
 */

class Modifier_unordered_list extends Modifier
{
    public function index($value, $parameters=array())
    {
        return "<ul><li>" . join("</li><li>", $value) . "</li></ul>";
    }
}