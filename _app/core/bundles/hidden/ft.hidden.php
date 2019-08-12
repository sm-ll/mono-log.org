<?php
class Fieldtype_hidden extends Fieldtype
{
  public function render()
  {
  	$value = htmlspecialchars($this->field_data, ENT_QUOTES);
    $html = "<input type='hidden' name='{$this->fieldname}' tabindex='{$this->tabindex}' value='{$value}' />";

    return $html;
  }

}
