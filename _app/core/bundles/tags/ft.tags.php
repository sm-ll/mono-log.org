<?php
class Fieldtype_tags extends Fieldtype
{
  public function render()
  {
    if (is_array($this->field_data)) {
      $this->field_data = implode(",", $this->field_data);
    }

    $attributes = array(
      'name' => $this->fieldname,
      'id' => $this->field_id,
      'tabindex' => $this->tabindex,
      'value' => $this->field_data,
      'class' => 'selectize'
    );

    return HTML::makeInput('text', $attributes, $this->is_required);
  }

  public function process()
  {
    $processed_data = '';
    if ($this->field_data !== '') {
      $processed_data = explode(',', $this->field_data);
    }

    return $processed_data;
  }

}
