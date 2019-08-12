<?php
/**
 * Modifier_sanitize
 * Converts any questionable characters to their HTML entities
 *
 * @author  Statamic
 */
class Modifier_sanitize extends Modifier
{
    public function index($value, $parameters=array()) {
        return HTML::convertSpecialCharacters($value);
    }
}