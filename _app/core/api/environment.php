<?php
/**
 * Environment
 * API to inspect and set environments
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2014 Statamic
 */
class Environment
{
    /**
     * Returns the name of the current environment
     * 
     * @param mixed  $default  Default value if no environment is set
     * @return mixed
     */
    public static function get($default=null)
    {
        return Config::get('environment', $default);
    }
}