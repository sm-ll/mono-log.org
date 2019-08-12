<?php
/**
 * Modifier_img
 * Converts a variable's value to an HTML `img` tag
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_img extends Modifier
{
	public function index($value, $parameters=array()) {
		$dimensions_str = '';
		
		if ( ! empty($parameters)) {
			$dimensions = explode(',', $parameters[0]);
			$width = $dimensions[0];
			$dimensions_str .= " width=\"$width\"";

			if (isset($dimensions[1])) {
				$height = $dimensions[1];
				$dimensions_str .= " height=\"$height\"";
			}
		}

		return '<img src="' . Path::toAsset($value) . '"' . $dimensions_str . ' />';
	}
}