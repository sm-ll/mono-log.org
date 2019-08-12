<?php

class API_html_caching extends API
{
    /**
     * Is the HTML cache enabled?
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return $this->tasks->isEnabled($this->getCurrentURL());
    }


    /**
     * Is the current URL cached and valid?
     * 
     * @return boolean
     */
    public function isPageCached()
    {
        return $this->tasks->isPageCached($this->getCurrentURL());
    }


    /**
     * Retrieve the currently cached page
     * 
     * @return string
     */
    public function getCachedPage()
    {
        return $this->tasks->getCachedPage($this->getCurrentURL());
    }


    /**
     * Store $html as the HTML for the current page in the cache
     * 
     * @param string  $html  HTML to store for this URL
     * @return void
     */
    public function putCachedPage($html)
    {
        $this->tasks->putCachedPage($this->getCurrentURL(), $html);
    }


    /**
     * Invalidates the entire current HTML cache
     *
     * @param string  $url  Optionally only invalidate cache for one URL
     * @return void
     */
    public function invalidateCache($url=null)
    {
        // url-specific
        if (!is_null($url) && $this->tasks->isPageCached($url)) {
            $this->tasks->invalidateCache($url);
            return;
        }
        
        // the whole thing
        $this->tasks->invalidateCache();
    }
    
    
    /**
     * Gets the current URL without needing the public API
     * 
     * @return string
     */
    private function getCurrentURL()
    {
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']) {
            $url = $_SERVER['REQUEST_URI'];
        } else {
            // doesn't exist, hand-make it
            
            // code ported from \Slim\Environment
            if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
                $script_name = $_SERVER['SCRIPT_NAME']; //Without URL rewrite
            } else {
                $script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']) ); //With URL rewrite
            }
        
            $url = '/' . ltrim(substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($script_name)), '/');
        }

        return URL::sanitize($url);
    }
}