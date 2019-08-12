<?php
class Fieldtype_markitup extends Fieldtype
{

  public function render()
  {
    $vars = array(
      'field_id'     => $this->field_id,
      'field_name'   => $this->fieldname,
      'tabindex'     => $this->tabindex,
      'field_data'   => $this->field_data,
      'field_config' => $this->field_config,
      'file_dir'     => str_replace('//', '/', ltrim(array_get($this->field_config, 'file_dir').'/', '/')),
      'image_dir'    => str_replace('//', '/', ltrim(array_get($this->field_config, 'image_dir').'/', '/')),
      'buttons'      => array_get($this->field_config, 'buttons', $this->config['buttons']),
      'resize'       => array_get($this->field_config, 'resize')
    );

    $template = File::get($this->getAddonLocation() . 'views/fieldtype.html');

    return Parse::template($template, $vars);
  }

}
