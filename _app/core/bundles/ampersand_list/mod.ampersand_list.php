<?php
/**
 * Modifier_ampersand_list
 * Implodes an array into an comma and apersand delimited list
 *
 * @author Statamic
 */

class Modifier_ampersand_list extends Modifier
{
    public function index($value, $parameters=array())
    {
        return Helper::makeSentenceList($value, "&", false);
    }
}