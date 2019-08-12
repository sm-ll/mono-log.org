<?php
class Fieldtype_radio extends Fieldtype
{
	public function render()
	{
		$html = '';

		$options = $this->field_config['options'];
		$is_indexed = array_values($options) === $options;

		$i = 1;
		foreach ($this->field_config['options'] as $key => $option) {
			
			// Allow setting custom values and labels
			$value = $is_indexed ? $option : $key;
			$current_selection = ($this->field_data === $value);

			$html .= "<div class='radio-block'>";

			$attributes = array(
				'name' => $this->fieldname,
				'tabindex' => $this->tabindex,
				'class' => 'radio',
				'id' => $this->field_id . '-radio-'.$i,
				'value' => $value
			);

			if ($current_selection) {
				$attributes['checked'] = 'checked';
			}

			$html .= HTML::makeInput('radio', $attributes);

			$html .= "<label class='radio-label' for='{$this->field_id}-radio-{$i}'>{$option}</label>";
			$html .= "</div>";
			$i++;
		}

		return $html;
	}
}
