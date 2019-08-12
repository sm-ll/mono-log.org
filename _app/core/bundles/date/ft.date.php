<?php
class Fieldtype_date extends Fieldtype
{
	public function render()
	{
		$attributes = array(
			'name'       => $this->fieldname,
			'id'         => $this->field_id,
			'class'      => 'datepicker',
			'tabindex'   => $this->tabindex,
			'value'      => HTML::convertSpecialCharacters($this->field_data),
			'data-value' => HTML::convertSpecialCharacters($this->field_data)
		);

		$html  = '<div class="field">';
		$html .=  "<span class='ss-icon'>date</span>";
		$html .= HTML::makeInput('text', $attributes, $this->is_required);
		$html .= "</div>";

		return $html;
	}
}
