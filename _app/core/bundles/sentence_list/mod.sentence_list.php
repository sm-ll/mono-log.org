<?php
/**
 * Modifier_sentence_list
 * Implodes an array into a string in the form of an unordered list
 *
 * @author Statamic
 */

class Modifier_sentence_list extends Modifier
{
    public function index($value, $parameters=array())
    {
        return Helper::makeSentenceList($value, Localization::fetch('and'));
    }
}