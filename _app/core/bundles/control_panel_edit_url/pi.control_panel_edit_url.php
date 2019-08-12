<?php

class Plugin_control_panel_edit_url extends Plugin
{
    public function index()
    {
        // we need the local path
        if (!isset($this->context['_local_path'])) {
            return '';
        }
        
        // local path exists, return the correct format
        $path = Config::get('_admin_path') . '.php/publish?path=' . substr($this->context['_local_path'], 1, strrpos($this->context['_local_path'], '.')-1);
        return URL::assemble(Config::getSiteRoot(), $path);
    }
}
