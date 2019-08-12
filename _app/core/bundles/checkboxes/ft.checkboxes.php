<?php
class Fieldtype_checkboxes extends Fieldtype
{
    public function render_field()
    {
        $html  = "<div class='checkboxes'>";
        $html .= $this->render_label();
        $html .= $this->render_instructions_above();
        $html .= $this->render();
        $html .= $this->render_instructions_below();
        $html .= "</div>";

        return $html;
    }

    public function render()
    {
        $options = array_get($this->field_config, 'options', array());

        $html = '<input type="hidden" name="' . $this->fieldname . '" value="false" />';

        foreach ($options as $key => $option) {
            $attributes = array(
                'name'     => $this->fieldname . '[]',
                'id'       => $this->field_id . '_' . $key,
                'class'    => 'checkbox',
                'tabindex' => $this->tabindex,
                'value'    => $key,
                'checked'  => ''
            );

            if ($this->field_data && in_array($key, Helper::ensureArray($this->field_data))) {
                $attributes['checked'] = 'checked';
            }

            $html .= '<div class="checkbox-block">';
            $html .= HTML::makeInput('checkbox', $attributes, $this->is_required);
            $html .= '<label for="' . $this->field_id . '_' . $key . '">' . $option . '</label>';
            $html .= '</div>';
        }

        return $html;
    }

    public function process()
    {
        // if nothing is checked, the value will be a string "false"
        // we need to convert that to a boolean
        return ($this->field_data == 'false') ? false : $this->field_data;
    }
}