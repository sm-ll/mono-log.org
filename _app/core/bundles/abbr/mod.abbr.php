<?php

class Modifier_abbr extends Modifier
{
    public function index($value, $parameters=array())
    {
        $characters = (isset($parameters[0])) ? $parameters[0] : $this->fetchConfig('consecutive_characters', 2);
        
        return preg_replace('/([A-Z]{' . $characters . ',})/', '<abbr>$1</abbr>', $value);
    }
}