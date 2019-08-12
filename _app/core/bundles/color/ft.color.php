<?php

class Fieldtype_color extends Fieldtype
{
	public function render()
	{
		$config = json_encode($this->field_config);

		return "<input type='text' data-spectrum='{$config}' name='{$this->fieldname}' tabindex='{$this->tabindex}' value='{$this->field_data}' class='colorpicker' />";
	}
}
