<?php
class Fieldtype_redactor extends Fieldtype {

    public function render() 
    {
        $options       = $this->getConfig();
        $field_options = array_get($this->field_config, 'options', array());
        $options       = array_merge($options, $field_options);

        // File options
        if ($file_dir = array_get($this->field_config, 'file_dir', false)) {
          $file_dir = trim(array_get($this->field_config, 'file_dir'), '/') . '/';
          $options['fileUpload'] = Config::getSiteRoot() . 'TRIGGER/redactor/upload?path=' . $file_dir;
          $options['fileManagerJson'] = Config::getSiteRoot() . 'TRIGGER/redactor/fetch_files?path=' . $file_dir;
          $options['plugins'][] = 'filemanager';
        }

        // Image options
        if ($image_dir = array_get($this->field_config, 'image_dir', false)) {
          $image_dir = trim(array_get($this->field_config, 'image_dir'), '/') . '/';
          $options['imageUpload'] = Config::getSiteRoot() . 'TRIGGER/redactor/upload?path=' . $image_dir;
          $options['imageManagerJson'] = Config::getSiteRoot() . 'TRIGGER/redactor/fetch_images?path=' . $image_dir;
          $options['plugins'][] = 'imagemanager';

          if ($resize = array_get($this->field_config, 'resize')) {
            $options['imageUpload'] .= '&resize=1&' . http_build_query($resize);
          }
        }

        // Enable plugins
        $supported_plugins = array('table', 'video', 'fullscreen', 'fontcolor', 'fontsize', 'fontfamily');
        foreach ($options['buttons'] as $button) {
          if (in_array($button, $supported_plugins)) {
            $options['plugins'][] = $button;
          }
        }

        $vars = array(
          'field_id'     => $this->field_id,
          'field_name'   => $this->fieldname,
          'tabindex'     => $this->tabindex,
          'field_data'   => $this->field_data,
          'field_config' => $this->field_config,
          'options'      => $options
        );

        $template = File::get($this->getAddonLocation() . 'views/fieldtype.html');

        return Parse::template($template, $vars);
    }

    public function process() {
        return trim($this->field_data);
    }
}
