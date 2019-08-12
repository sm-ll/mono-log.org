<?php
/**
 * Plugin_log
 * Allows front-end logging
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 */
class Plugin_log extends Plugin
{
    private function getMessage()
    {
        $this->what          = $this->fetchParam('what', 'log', null, false, false);
        $this->specifically  = $this->fetchParam('specifically', 'message', null, false, false);
        $this->message       = Parse::contextualTemplate(trim($this->fetchParam('message', $this->content, null, false, false)), array(), $this->context);
    }
    
    public function debug()
    {
        $this->getMessage();
        Log::debug($this->message, $this->what, $this->specifically);
    }
    
    public function info()
    {
        $this->getMessage();
        Log::info($this->message, $this->what, $this->specifically);
    }
    
    public function warn()
    {
        $this->getMessage();
        Log::warn($this->message, $this->what, $this->specifically);
    }
    
    public function error()
    {
        $this->getMessage();
        Log::error($this->message, $this->what, $this->specifically);
    }
    
    public function fatal()
    {
        $this->getMessage();
        Log::fatal($this->message, $this->what, $this->specifically);
    }
}