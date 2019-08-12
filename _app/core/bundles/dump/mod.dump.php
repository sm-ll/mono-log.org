<?php

class Modifier_dump extends Modifier
{
    public function index($value, $parameters=array())
    {
        if (!is_array($value)) {
            return $value;
        } else {
            return trim($this->buildDump($value));
        }
    }
    
    public function buildDump($value, $depth=0) {
        $output = '';
        
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $output .= "\n" . str_repeat(" ", $depth * 2) . $key . ": " . $this->buildDump($val, $depth + 1);
            }
            
            return $output;
        } else {
            return $value;
        }
    }
}