<?php
class Modifier_original extends Modifier
{

	public function index($value, $parameters=array())
	{
		$pi = pathinfo($value);

		if (Pattern::matches('resized$', $pi['dirname'])) {
			$dir = preg_replace('/resized$/', '', $pi['dirname']);
			$value = Path::assemble($dir, $pi['basename']);
		}

		return $value;
	}

}