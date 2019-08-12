<?php

class Modifier_conditions_escape extends Modifier
{
    public function index($value, $parameters=array())
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = str_replace(',', '\\,', $item);
            }
        } else {
            $value = str_replace(',', '\\,', $value);
        }
        
        return $value;
    }
}