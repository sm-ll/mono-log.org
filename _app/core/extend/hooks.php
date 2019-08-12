<?php

class Hooks extends Addon
{
    /**
     * Sets the HTTP status code to be returned with this hook's URL trigger
     * 
     * @param int  $status  HTTP status code to use
     * @return void
     */
    protected function setStatusCode($status)
    {
        $app = \Slim\Slim::getInstance();
        $app->response()->status($status);
    }
}
