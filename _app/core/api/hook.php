<?php
use Symfony\Component\Finder\Finder as Finder;

/**
 * Hook
 * API for hooking into events triggered by the site
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Hook
{
    private static $hook_files = array();
    private static $hooks_found = false;
    
    /**
     * Run the instance of a given hook
     *
     * @param string  $namespace  The namespace (addon/aspect) calling the hook
     * @param string  $hook       Name of hook
     * @param string  $type       Cumulative/replace/call
     * @param mixed   $return     Pass-through values
     * @param mixed   $data       Data to pass to hooked method
     * @return mixed
     */
    public static function run($namespace, $hook, $type = NULL, $return = NULL, $data = NULL)
    {
        $mark_as_init = !self::$hooks_found;
        
        if (!self::$hooks_found) {
            $hash = Debug::markStart('hooks', 'finding');
            
            // we went finding
            self::$hooks_found = true;

            // set paths
            $addons_path   = BASE_PATH . Config::getAddOnsPath();
            $bundles_path  = APP_PATH . '/core/bundles';
            $pattern       = '/*/hooks.*.php';
            
            // globbing with a brace doesn't seem to work on some system,
            // it's not just Windows-based servers, seems to affect some
            // linux-based ones too
            $bundles  = glob($bundles_path . $pattern);
            $addons   = glob($addons_path . $pattern);
            
            $bundles  = (empty($bundles)) ? array() : $bundles;
            $addons   = (empty($addons)) ? array() : $addons;
            
            self::$hook_files = array_merge($bundles, $addons);
            
            Debug::markEnd($hash);
        }

        $hash = Debug::markStart('hooks', 'running');
        
        if (self::$hook_files) {
            foreach (self::$hook_files as $file) {
                $name = substr($file, strrpos($file, '/') + 7);
                $name = substr($name, 0, strlen($name) - 4);
    
                $class_name = 'Hooks_' . $name;
                
                if (!is_callable(array($class_name, $namespace . '__' . $hook), false)) {
                    continue;
                }
                
                try {
                    $hook_class = Resource::loadHooks($name);
    
                    $method = $namespace . '__' . $hook;
        
                    if ($type == 'cumulative') {
                        $response = $hook_class->$method($data);
                        if (is_array($response)) {
                            $return = is_array($return) ? $return + $response : $response;
                        } else {
                            $return .= $response;
                        }
                    } elseif ($type == 'replace') {
                        $return = $hook_class->$method($data);
                    } else {
                        $hook_class->$method($data);
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        if ($mark_as_init) {
            Debug::markMilestone('hooks initialized');
        }
        
        Debug::markEnd($hash);

        return $return;
    }
}
