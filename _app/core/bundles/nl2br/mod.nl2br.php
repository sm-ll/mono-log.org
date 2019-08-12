<?php

class Modifier_nl2br extends Modifier
{

	public function index($value, $parameters = array())
	{
		return nl2br($value);
	}

}