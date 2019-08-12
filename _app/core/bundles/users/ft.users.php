<?php
class Fieldtype_users extends Fieldtype
{
  public function render()
  {
    $html = "<div class='input-select-wrap'><select name='{$this->fieldname}' tabindex='{$this->tabindex}'>";
    $html .= "<option value=''>- None Selected-</option>";

    $current_user = Auth::getCurrentMember();
    $current_username = $current_user->get_name();

    if ($this->field_data == '') {
      $this->field_data = $current_username;
    }

    foreach (Member::getList() as $key => $data) {

      if ( ! is_object($data)) {
        continue;
      }

      $selected = $this->field_data == $key ? " selected='selected'" : '';
      $html .= "<option {$selected} value='{$key}'>{$data->get('first_name')} {$data->get('last_name')}</option>";
    }

    $html .= "</select></div>";

    return $html;
  }

}
