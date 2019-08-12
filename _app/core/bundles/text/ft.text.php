<?php
class Fieldtype_text extends Fieldtype
{
	public function render()
	{
		$type = array_get($this->field_config, 'input_type', 'text');
		
		$attributes = array(
			'name'     => $this->fieldname,
			'id'       => $this->field_id,
			'tabindex' => $this->tabindex,
			'value'    => HTML::convertSpecialCharacters($this->field_data)
		);

		return HTML::makeInput($type, $attributes, $this->is_required);
	}

}
