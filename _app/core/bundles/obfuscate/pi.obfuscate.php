<?php

class Plugin_Obfuscate extends Plugin
{
    public function index()
    {
        // parse the content just in case
        $content = Parse::contextualTemplate(trim($this->content), array(), $this->context);
        
        // return the obfuscated contents
        return HTML::obfuscateEmail($content);
    }
}