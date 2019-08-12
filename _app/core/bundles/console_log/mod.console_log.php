<?php
class Modifier_console_log extends Modifier
{

    /**
     * Create a script tag that logs as JSON
     */
    public function index($value, $parameters=array())
    {
        return $this->js->inline('
            window.log=function(a){if(this.console){console.log(a);}};
            log('.json_encode($value).');
        ');
    }

}
