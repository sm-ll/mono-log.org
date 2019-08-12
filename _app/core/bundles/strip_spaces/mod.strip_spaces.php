<?php
class Modifier_strip_spaces extends Modifier
{

	public function index($value, $parameters=array()) {
		return str_replace(' ', '', $value);
	}

}