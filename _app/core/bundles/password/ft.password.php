<?php
class Fieldtype_password extends Fieldtype
{
  public function render()
  {
    $attributes = array(
      'name' => $this->fieldname,
      'id' => $this->field_id,
      'tabindex' => $this->tabindex,
      'value' => '',
      'autocomplete' => 'off',
      'data-bind' => "css: {required: showChangePassword}"
    );

    return HTML::makeInput('password', $attributes, $this->is_required);
  }
}
