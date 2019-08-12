<?php

/**
 * Cache
 * API for caching content
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @package     API
 * @copyright   2013 Statamic
 */
class Cache
{
    /**
     * Get last cache update time
     *
     * @return int
     */
    public static function getLastCacheUpdate()
    {
        return filemtime(BASE_PATH . '/_cache/_app/content/content.php');
    }
    

    /**
     * Checks to see if the cache file exists
     * 
     * @return boolean
     */
    public static function exists()
    {
        $caches = array(
            'content'   => BASE_PATH . '/_cache/_app/content/content.php',
            'settings'  => BASE_PATH . '/_cache/_app/content/settings.php',
            'structure' => BASE_PATH . '/_cache/_app/content/structure.php',
            'time'      => BASE_PATH . '/_cache/_app/content/last.php'
        );
        
        foreach ($caches as $key => $cache) {
            if (!File::exists($cache) || !strlen(File::get($cache))) {
                return false;
            }
        }
        
        return true;
    }
}