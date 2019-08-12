<?php

class API_cache extends API
{
    /**
     * Delete one or me $keys-worth of cache hashes
     * 
     * @param string|array  $keys  One or more keys worth of hashes to delete
     */
    public function invalidateByKey($keys)
    {
        $this->core->deleteByKey($keys);
    }
    
    
    /**
     * Force-trigger garbage collection
     * 
     * @param boolean  $force  Force this to run if it's not time?
     * @return void
     */
    public function collectGarbage($force=null)
    {
        $this->core->collectGarbage($force);
    }
}