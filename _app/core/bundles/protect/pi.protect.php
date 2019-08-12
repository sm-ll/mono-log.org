<?php

class Plugin_protect extends Plugin
{
    public function password_form()
    {
        // fetch parameters
        $return  = $this->fetch('return', filter_input(INPUT_GET, 'return', FILTER_SANITIZE_STRING), null, false, false);
        $attr    = $this->fetchParam('attr', false);
        
        // set up data to be parsed into content
        $data = array(
            'error' => $this->flash->get('error', ''),
            'field_name' => 'password'
        );

        // determine form attributes
        $attr_string = '';
        if ($attr) {
            $attributes_array = Helper::explodeOptions($attr, true);

            foreach ($attributes_array as $key => $value) {
                $attr_string .= ' ' . $key . '="' . $value . '"';
            }
        }
        
        // build the form
        $html  = '<form action="' . Path::tidy(Config::getSiteRoot() . '/TRIGGER/protect/password') . '" method="post"' . $attr_string . '>';
        $html .= '<input type="hidden" name="return" value="' . $return . '">';
        $html .= '<input type="hidden" name="token" value="' . $this->tokens->create() . '">';
        $html .= Parse::template($this->content, $data);
        $html .= '</form>';
        
        // return the HTML
        return $html;
    }
    
    
    public function require_password()
    {
        $password_list    = trim($this->fetchParam('allowed', '', null, false, false));
        $passwords        = explode('|', $password_list);
        $password_url     = $this->fetch('password_url', null, null, false, false);
        $no_access_url    = $this->fetch('no_access_url', '/', null, false, false);
        $return_variable  = $this->fetch('return_variable', 'return', null, false, false);

        // no passwords set? this is OK
        if (!$password_list) {
            return;
        }

        // determine form URL
        $form_url = Helper::pick($password_url, $no_access_url);
        
        if (!$this->tasks->hasPassword(URL::getCurrent(), $passwords)) {
            URL::redirect(URL::appendGetVariable($form_url, $return_variable, URL::getCurrent()), 302);
            exit();
        }
    }
    
    
    public function messages()
    {
        return Parse::template($this->content, array(
            'error' => $this->flash->get('error', null),
            'success' => $this->flash->get('success', null)
        ));
    }
}