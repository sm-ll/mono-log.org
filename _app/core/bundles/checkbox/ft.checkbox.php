<?php
class Fieldtype_checkbox extends Fieldtype
{
    public function render_field()
    {
        $html  = "<div class='checkbox-block'>";
        $html .= $this->render();
        $html .= $this->render_label();
        $html .= $this->render_instructions_above();
        $html .= $this->render_instructions_below();
        $html .= "</div>";

        return $html;
    }

    public function render()
    {
        $attributes = array(
          'name' => $this->fieldname,
          'id' => $this->field_id,
          'class' => 'checkbox',
          'tabindex' => $this->tabindex,
          'value' => '1'
        );

        if ($this->field_data) {
          $attributes['checked'] = 'checked';
        }

        return HTML::makeInput('hidden', array('name' => $this->fieldname, 'value' => false)) .
               HTML::makeInput('checkbox', $attributes, $this->is_required);
    }
}
