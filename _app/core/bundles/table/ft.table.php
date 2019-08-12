<?php
class Fieldtype_table extends Fieldtype
{

	public function render()
	{
		$data = ($this->field_data) ? $this->field_data : array(array('cells' => array('')));

		$vars = array(
			'field_id' => $this->field_id,
			'field_name' => $this->fieldname,
			'height' => array_get($this->field_config, 'height'),
			'rows' => json_encode($data)
		);
		$template = File::get($this->getAddonLocation() . 'views/fieldtype.html');
		
		return Parse::template($template, $vars);
	}

}