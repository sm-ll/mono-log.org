<?php
class Fieldtype_show_password extends Fieldtype
{
  public function render()
  {
    return '<div class="well"><a href="#" class="btn btn-small" data-bind="click: changePassword">' . Localization::fetch('change_password') . '</a></div>';
  }
}
