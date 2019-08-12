<?php
class Fieldtype_templates extends Fieldtype
{
  public function render()
  {
    $html = "<div class='input-select-wrap'><select name='{$this->fieldname}' tabindex='{$this->tabindex}'>";
    $html .= '<option value="">--Inherit--</option>';

    foreach (Theme::getTemplates() as $key) {
      $selected = $this->field_data == $key ? " selected='selected'" : '';
      $html .= "<option {$selected} value='{$key}'>".$key."</option>";
    }

    $html .= "</select></div>";

    return $html;
  }

}
