<?php
class Fieldtype_time extends Fieldtype
{
  public function render()
  {
    $attributes = array(
      'name' => $this->fieldname,
      'id' => $this->field_id,
      'class' => 'timepicker',
      'tabindex' => $this->tabindex,
      'value' => HTML::convertSpecialCharacters($this->field_data)
    );

    $html  = '<div class="field">';
    $html .= "<span class='ss-icon'>clock</span>";
    $html .= "<div class='bootstrap-timepicker'>";
    $html .= HTML::makeInput('text', $attributes, $this->is_required);
    $html .= "</div>";
    $html .= "</div>";

    return $html;
  }

}
