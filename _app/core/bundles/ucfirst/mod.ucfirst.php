<?php

class Modifier_ucfirst extends Modifier
{
    public function index($value, $parameters=array())
    {
        return ucfirst($value);
    }
}