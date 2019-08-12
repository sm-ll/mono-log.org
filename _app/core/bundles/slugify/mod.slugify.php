<?php

/**
 * Modifier_slugify
 * Replaces non-letter-characters in a variable with a separator
 */
class Modifier_slugify extends Modifier
{
    public function index($value, $parameters=array()) {
        $separator = array_get($parameters, 0, '-');

        return Slug::make($value, array('separator' => $separator));
    }
}