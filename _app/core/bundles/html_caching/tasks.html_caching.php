<?php

class Tasks_html_caching extends Tasks
{
    /**
     * Is HTML-caching enabled (either globally or for $url)?
     * 
     * @param string  $url  URL to check specifically for
     * @return bool
     */
    public function isEnabled($url)
    {
        // check to see if html-caching is on
        $global_enable = (bool) $this->fetchConfig('enable', false, null, true);
        
        if (!$global_enable || !Cache::exists()) {
            return false;
        }

        // check that the URL being requested is a content file
        $data      = ContentService::getContent($this->getCleanUri($url));

        // not a content file, not enabled
        if (!$data) {
            return false;
        }
        
        // check for exclude on the current page
        $exclude_raw = $this->fetchConfig('exclude');
        
        // if excludes were set
        if ($exclude_raw) {
            $excluded = Parse::pipeList($exclude_raw);
            
            // loop through excluded options
            foreach ($excluded as $exclude) {
                // account for non-/-starting locations
                $this_url = (substr($exclude, 0, 1) !== "/") ? ltrim($url, '/') : $url;
                
                if ($exclude === "*" || $exclude === "/*") {
                    // exclude all
                    return false;
                } elseif (substr($exclude, -1) === "*") {
                    // wildcard check
                    if (strpos($this_url, substr($exclude, 0, -1)) === 0) {
                        return false;
                    }
                } else {
                    // plain check
                    if ($exclude == $this_url) {
                        return false;
                    }
                }
            }
        }

        // all is well, return true
        return true;
    }


    /**
     * Is the $url in our cache and still valid?
     * 
     * @param string  $url  URL to check for cache
     * @return bool
     */
    public function isPageCached($url)
    {        
        $cache_length = trim($this->fetchConfig('cache_length', false));
        
        // if no cache-length is set, this feature is off
        if (!(bool) $cache_length) {
            return false;
        }
        
        if ($this->fetchConfig('ignore_query_strings', false, null, true)) {
            $url = $this->removeQueryString($url);
        }

        // create the hash now so we don't have to do it many times below
        $url_hash = Helper::makeHash($url);
        
        // we're no longer allowing `on cache update` here, as its a flawed concept:
        // it only appeared to work because new pages were being hit, however, once
        // every page is hit and then HTML-cached, the cache will no longer update
        // because procedurally, that happens *after* we look for and load a version
        // that has been cached
        if ($cache_length == 'on cache update' || $cache_length == 'on last modified') {
            // ignore the cached version if the last modified time of this URL's
            // content file is newer than when the cached version was made

            // check that the URL being requested is a content file
            $bare_url  = (strpos($url, '?') !== false) ? substr($url, 0, strpos($url, '?')) : $url;
            $data      = ContentService::getContent($bare_url);
            $age       = time() - File::getLastModified($data['_file']);
            
            // return if the cache file exists and if it's new enough
            return ($this->cache->exists($url_hash) && $this->cache->getAge($url_hash) <= $age);
        } else {
            // purge any cache files older than the cache length
            $this->cache->purgeFromBefore('-' . $cache_length);
            
            // return if the file still exists
            return $this->cache->exists($url_hash);
        }
    }


    /**
     * Return the cached HTML for a $url
     * 
     * @param string  $url  URL to retrieve from cache
     * @return string
     */
    public function getCachedPage($url)
    {
        if ($this->fetchConfig('ignore_query_strings', false, null, true)) {
            $url = $this->removeQueryString($url);
        }
        
        return $this->cache->get(Helper::makeHash($url), '');
    }


    /**
     * Store the $html into the cache for a $url
     * 
     * @param string  $url  URL to store HTML for
     * @param string  $html  Rendered HTML to store
     * @return void
     */
    public function putCachedPage($url, $html)
    {
        if ($this->fetchConfig('ignore_query_strings', false, null, true)) {
            $url = $this->removeQueryString($url);
        }
        
        $this->cache->put(Helper::makeHash($url), $html);
    }


    /**
     * Invalidated the cache
     * 
     * @param string  $url  An optional URL to invalidate the HTML cache for
     * @return void
     */
    public function invalidateCache($url=null)
    {
        // url-specific
        if (!is_null($url) && $this->isPageCached($url)) {
            $this->cache->delete(Helper::makeHash($url));
            return;
        }
        
        // the whole thing
        $this->cache->destroy();
    }


    /**
     * Strips out the query string (except for pagination) from a URL
     *
     * eg. /blog?page=2&foo=bar will become /blog?page=2
     * 
     * @param string  $url  URL to remove query strings from
     * @return string
     */
    protected function removeQueryString($url)
    {
        if (strpos($url, '?') !== false) {
	        $split = explode('?', $url);
	        parse_str($split[1], $params);
	        $url = $split[0] . '?page=' . $params['page'];
        }

        return $url;
    }


	/**
	 * Completely removes the query string from a URL
	 *
	 * eg. /blog?page=2&foo=bar will become /blog
	 *
	 * @param string  $url  URL to remove query strings from
	 * @return string
	 */
	protected function getCleanUri($url)
	{
		if (strpos($url, '?') !== false) {
			$url = substr($url, 0, strpos($url, '?'));
		}

		return $url;
	}
}