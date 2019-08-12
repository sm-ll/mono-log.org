<?php
class Fieldtype_status extends Fieldtype
{
  public function render()
  {
    $html = "<div class='input-select-wrap'><select name='{$this->fieldname}' tabindex='{$this->tabindex}'>";

    foreach (Statamic::$publication_states as $key => $value) {
      $selected = $this->field_data == $key ? " selected='selected'" : '';

      $html .= "<option {$selected} value='{$key}'>{$value}</option>";
    }

    $html .= "</select></div>";

    return $html;
  }

}
