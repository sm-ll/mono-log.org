<?php
/**
 * Modifier_markdown
 * Parses a variable for Markdown
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_markdown extends Modifier
{
	public function index($value, $parameters=array()) {
		return Parse::markdown($value);
	}
}