<?php
class Fieldtype_select extends Fieldtype
{
	public function render()
	{
		$html = "<div class='input-select-wrap'><select name='{$this->fieldname}' tabindex='{$this->tabindex}'>";

		$options = $this->field_config['options'];
		$is_indexed = (array_values($options) === $options);

		foreach ($this->field_config['options'] as $key => $option) {

			// Allow setting custom values and labels
			$value = ($is_indexed) ? $option : $key;
			$selected = $this->field_data == $value ? " selected='selected'" : '';

			$html .= '<option value="'. $value .'" ' . $selected .'>' . $option .'</option>';
		}

		$html .= "</select></div>";

		return $html;
	}

}