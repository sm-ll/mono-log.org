<?php

class Plugin_404 extends Plugin
{
    // preventing infinite loops since 2014
    private static $called = false;
    
    public function index()
    {
        // we were already here once, let's prevent an ugly infinite loop
        if (self::$called) {
            $this->log->error('Using the `{{ 404 }}` tag to trigger a 404 *on* the 404 page’s template itself.');
            return;
        }
        
        self::$called = true;
        
        // what is this magic!?
        // the main `routes` file has two paths that capture all routes, 
        // if we're in the first one and want to show the 404 page, we can
        // use $app->pass() to skip to the second request (which will
        // always show the 404 page)
        $app = \Slim\Slim::getInstance();
        $app->pass();
    }
}