<?php
/**
 * Modifier_entities
 * Converts any questionable characters to their HTML entities
 *
 * @author  Statamic
 */
class Modifier_entities extends Modifier
{
    public function index($value, $parameters=array()) {
        return HTML::convertEntities($value);
    }
}