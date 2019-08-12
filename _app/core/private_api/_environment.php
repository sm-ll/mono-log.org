<?php
/**
 * _Environment
 * Private API to inspect and set environments
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     Private_API
 * @copyright   2013 Statamic
 */
class _Environment
{
    /**
     * Detects the current environment
     *
     * @param array  $config  Config to look through
     * @return mixed
     */
    public static function detect($config)
    {        
        // get current URL, this is probably called before the config is ready,
        // so we cannot simply use Request::getURL()
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://';
        $port   = (int) $_SERVER['SERVER_PORT'];
        $host   = $_SERVER['HTTP_HOST'];
        
        
        $url = $scheme . $host;
        if (($scheme === 'https://' && $port !== 443) || ($scheme === 'http://' && $port !== 80)) {
            $url .= ':' . $port;
        }
        
        // get configured environments
        $environments = array_get($config, '_environments', null);

        if (is_array($environments)) {
            foreach ($environments as $environment => $patterns) {
                foreach ($patterns as $pattern) {
                    if (Pattern::matches($pattern, $url)) {
                        return $environment;
                    }
                }
            }
        }

        return null;
    }


    /**
     * Sets the current environment to the given $environment
     *
     * @param string  $environment  Environment to set
     * @param array  $config  Config to set to
     * @return void
     */
    public static function set($environment, &$config)
    {
        $config['environment'] = $environment;
        $config['is_' . $environment] = true;
        $environment_config = YAML::parse("_config/environments/{$environment}.yaml");
        
        if (is_array($environment_config)) {
            $config = array_merge($config, $environment_config);
        }
    }


    /**
     * Detects and sets the current environment in one call
     *
     * @param array  $config  Config array to add to
     * @return void
     */
    public static function establish(&$config)
    {
        $environment = self::detect($config);

        if ($environment) {
            self::set($environment, $config);
        }
    }
}