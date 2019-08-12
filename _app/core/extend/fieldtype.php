<?php
/**
 * Fieldtype
 * General abstract structure for a Fieldtype
 */
abstract class Fieldtype extends Addon
{
    /**
     * The field ID for this field
     * @var string
     */
    public $field_id = NULL;

    /**
     * A list of configured options for this fieldtype
     * @var array
     */
    public $field_config = array();

    /**
     * An error message to display on this field
     * @var string
     */
    public $field_error = null;

    /**
     * Data values for fields with this grid
     * @var mixed
     */
    public $field_data;

    /**
     * Is there an error associated with this field?
     * @var boolean
     */
    public $has_error = false;
    


    /**
     * init
     * Allows field setup, called before rendering happens
     */
    protected function init()
    {
        $this->field_id = Helper::getRandomString();
    }


    /**
     * render
     * Renders the field itself
     *
     * @throws Exception
     * @return string
     */
    public function render()
    {
        throw new Exception("The `render` method must be defined in your fieldtype.");
    }


    /**
     * render_label
     * Renders the HTML label for this field
     *
     * @return string
     */
    public function render_label()
    {
        if (!isset($this->field_config['display']) || !$this->field_config['display']) {
            return "";
        }

        // if a field ID was given, use that as the for attribute connector
        $for = (!is_null($this->field_id)) ? ' for="' . $this->field_id . '"' : '';

        return "<label{$for}>{$this->field_config['display']}</label>";
    }


    /**
     * Renders the error message
     * 
     * @return string
     */
    public function render_error()
    {
        if ($this->field_error) {
            return '<div class="error">' . $this->field_error . '</div>';
        }
    }


    /**
     * render_instructions_above
     * Renders instructions to be placed above form element
     *
     * @return string
     */
    public function render_instructions_above()
    {
        // check that instructions exist
        if (!isset($this->field_config['instructions'])) {
            return "";
        }

        // check that instructions is an array
        if (is_array($this->field_config['instructions'])) {
            if (isset($this->field_config['instructions']['above'])) {
                // above-specific instructions exist
                return "<small class='above'>" . nl2br(htmlspecialchars($this->field_config['instructions']['above'])) . "</small>";
            }

            // no above-specific instructions exist
            return "";
        } else {
            // instructions isn't an array, assume instructions belong above
            return "<small class='above'>" . nl2br(htmlspecialchars($this->field_config['instructions'])) . "</small>";
        }
    }


    /**
     * render_instructions_below
     * Renders instructions to be placed below form element
     *
     * @return string
     */
    public function render_instructions_below()
    {
        // check that instructions exist
        if (!isset($this->field_config['instructions'])) {
            return "";
        }

        // check that instructions is an array, if it isn't, instructions go above
        if (!is_array($this->field_config['instructions'])) {
            return "";
        }

        // no below-specific instructions exist
        if (!isset($this->field_config['instructions']['below'])) {
            return "";
        }

        // below-specific instructions exist
        return "<small class='below'>" . nl2br(htmlspecialchars($this->field_config['instructions']['below'])) . "</small>";
    }


    /**
     * render_field
     * Renders the full HTML for this field
     *
     * @return string
     */
    public function render_field()
    {
        return $this->render_label() . $this->render_instructions_above() . $this->render_error() . $this->render() . $this->render_instructions_below();
    }


    /**
     * render_fieldtype
     * Renders this fieldtype
     *
     * @param string  $fieldtype  Type of field to render
     * @param string  $fieldname  Name of field to use
     * @param array  $field_config  List of configuration options for this field
     * @param mixed  $field_data  Data value(s) for this field
     * @param mixed  $tabindex  Tab-index to use for this field
     * @param string  $input_key  Helps section out fields
     * @param string  $field_id  Field ID to use
     * @param string  $error_message Error message that needs displaying
     * @return string
     */
    public static function render_fieldtype($fieldtype, $fieldname, $field_config, $field_data, $tabindex = NULL, $input_key = '[yaml]', $field_id = NULL, $error_message=null)
    {
        $output = '';

        $fieldtype_folders = Config::getAddOnLocations();

        foreach ($fieldtype_folders as $folder) {
            if (is_dir($folder . $fieldtype) && is_file($folder . $fieldtype . '/ft.' . $fieldtype . '.php')) {

                $file = $folder . $fieldtype . '/ft.' . $fieldtype . '.php';
                break;

            } elseif (is_file($folder . '/ft.' . $fieldtype . '.php')) {

                $file = $folder . '/ft.' . $fieldtype . '.php';
                break;
            }
        }

        # fieldtype exists
        if (isset($file)) {

            require_once($file);
            $class = 'Fieldtype_' . $fieldtype;

            #formatted properly
            if (class_exists($class)) {
                $field = new $class();
            }

            # function exists
            if (method_exists($field, 'render')) {
                $field->field_config    = $field_config;
                $field->field           = "$fieldname";
                $field->fieldname       = "page{$input_key}[$fieldname]";
                $field->fieldnameremove = "page{$input_key}[{$fieldname}_remove]";
                $field->field_data      = $field_data;
                $field->tabindex        = $tabindex;
                $field->field_error     = $error_message;
                $field->has_error       = (bool) $error_message;
                $field->is_required     = array_get($field_config, 'required', false);

                if (method_exists($field, 'init')) {
                    $field->init();
                    if ($field_id) {
                        $field->field_id = $field_id;
                    }
                }

                $output = $field->render_field();
            }

        }

        return $output;
    }

    public static function process_field_data($fieldtype, $field_data, $settings = NULL, $fieldname = NULL)
    {
        $fieldtype_folders = Config::getAddOnLocations();

        foreach ($fieldtype_folders as $folder) {
            if (is_dir($folder . $fieldtype) && is_file($folder . $fieldtype . '/ft.' . $fieldtype . '.php')) {

                $file = $folder . $fieldtype . '/ft.' . $fieldtype . '.php';
                break;

            } elseif (is_file($folder . '/ft.' . $fieldtype . '.php')) {

                $file = $folder . '/ft.' . $fieldtype . '.php';
                break;
            }
        }

        # fieldtype exists
        if (isset($file)) {

            require_once($file);
            $class = 'Fieldtype_' . $fieldtype;

            #formatted properly
            if (class_exists($class)) {
                $field = new $class();
            }

            # function exists
            if (method_exists($field, 'process')) {
                $field->fieldname  = $fieldname;
                $field->field_data = $field_data;
                $field->settings   = $settings;
                $field_data        = $field->process($settings);
            }

        }

        return $field_data;
    }
}
