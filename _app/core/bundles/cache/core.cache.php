<?php

class Core_cache extends Core
{
    /**
     * Check if this is enabled
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return $this->fetchConfig('enable', false, null, true, false);
    }
    
    
    /**
     * With each call, clean up any really old caches that are no longer needed
     * 
     * @param bool  $force  Force a collection even if it's not time
     * @return void
     */
    public function collectGarbage($force=false)
    {
        // only garbage collect once per week
        $last = $this->cache->get('last_garbage_collection', null);
        
        // is it time for garbage collection?
        if (!$force && $last && $last >= Date::resolve('-1 week')) {
            // not time yet, we're good
            return;
        }
        
        // log that we're coming through
        $this->log->debug('Collecting garbage.');
        
        // set last time
        $this->cache->put('last_garbage_collection', time());
        
        // clean up troves
        $threshold = $this->fetchConfig('garbage_threshold', null, false, false, false);

        if ($threshold) {
            $this->cache->purgeFromBefore('-' . $threshold);
        }
        
        // clean up keys
        $keys = $this->cache->listAll('keys');
        
        foreach ($keys as $key) {
            $hashes = $this->cache->getYAML('keys/' . $key);
            
            foreach ($hashes as $hash_key => $hash) {
                if (!$this->cache->exists('troves/' . $hash)) {
                    unset($hashes[$hash_key]);
                }
            }

            if (!count($hashes)) {
                $this->cache->delete('keys/' . $key);
            } else {
                $this->cache->putYAML('keys/' . $key, $hashes);
            }
        }
    }


    /**
     * Delete one or more hashes
     * 
     * @param string|array  $hashes  Hash or hashes to delete
     * @return bool
     */
    public function deleteByHash($hashes)
    {
        $hashes = Helper::ensureArray($hashes);
        
        foreach ($hashes as $hash) {
            if ($this->cache->exists('troves/' . $hash)) {
                $this->cache->delete('troves/' . $hash);
            }
        }
    }

    /**
     * Delete one or more keys' hashes
     * 
     * @param string|array  $keys  Key or keys whose hashes should be deleted
     * @return bool
     */
    public function deleteByKey($keys)
    {
        $keys = Helper::ensureArray($keys);
        
        foreach ($keys as $key) {
            if (!$this->cache->exists('keys/' . $key)) {
                continue;
            }

            // grab the hashes to invalidate
            $hashes = $this->cache->getYAML('keys/' . $key);
            
            foreach ($hashes as $hash) {
                // delete it
                if ($this->cache->exists('troves/' . $hash)) {
                    $this->cache->delete('troves/' . $hash);
                }
            }
        }
        
        return true;
    }
}