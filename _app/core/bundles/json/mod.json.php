<?php
/**
 * Modifier_json
 * JSON encodes an array
 *
 * @author Statamic
 */

class Modifier_json extends Modifier
{
    public function index($value, $parameters=array())
    {
        return json_encode($value);
    }
}