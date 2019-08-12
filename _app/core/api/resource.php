<?php
/**
 * Resource
 * Interact with resources
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Resource
{
    const _PLUGIN     = 1;
    const _FIELDTYPE  = 2;
    const _HOOKS      = 4;
    const _TASKS      = 8;
    const _MODIFIER   = 16;
    const _API        = 32;
    const _CORE       = 64;
    const _INTERFACE  = 128;
    
    
    /**
     * Attempts to load a modifier
     * 
     * @param string  $name  Name of modifier to load
     * @return Modifier
     * @throws Exception
     */
    public static function loadModifier($name)
    {        
        return self::loadAddonResource(self::_MODIFIER, $name);
    }
    
    
    /**
     * Attempts to load a plugin
     * 
     * @param string  $name  Name of plugin to load
     * @return Plugin
     * @throws Exception
     */
    public static function loadPlugin($name)
    {        
        return self::loadAddonResource(self::_PLUGIN, $name);
    }
    
    
    /**
     * Attempts to load a fieldtype
     * 
     * @param string  $name  Name of fieldtype to load
     * @return Plugin
     * @throws Exception
     */
    public static function loadFieldtype($name)
    {        
        return self::loadAddonResource(self::_FIELDTYPE, $name);
    }
    
    
    /**
     * Attempts to load hooks
     * 
     * @param string  $name  Name of hooks to load
     * @return Hooks
     * @throws Exception
     */
    public static function loadHooks($name)
    {        
        return self::loadAddonResource(self::_HOOKS, $name);
    }
    
    
    /**
     * Attempts to load tasks
     * 
     * @param string  $name  Name of tasks to load
     * @return Tasks
     * @throws Exception
     */
    public static function loadTasks($name)
    {        
        return self::loadAddonResource(self::_TASKS, $name);
    }
    
    
    /**
     * Attempts to load core
     * 
     * @param string  $name  Name of core to load
     * @return Core
     * @throws Exception
     */
    public static function loadCore($name)
    {        
        return self::loadAddonResource(self::_CORE, $name);
    }
    
    
    /**
     * Attempts to load API
     * 
     * @param string  $name  Name of API to load
     * @return Tasks
     * @throws Exception
     */
    public static function loadAPI($name)
    {        
        return self::loadAddonResource(self::_API, $name);
    }
    
    
    /**
     * Attempts to load Interface
     * 
     * @param string  $name  Name of Interface to load
     * @return Tasks
     * @throws Exception
     */
    public static function loadInterface($name)
    {        
        return self::loadAddonResource(self::_INTERFACE, $name);
    }
    
    
    /**
     * Attempts to load an add-on file
     * 
     * @param integer  $type  Type of add-on file to load
     * @param string  $addon  Add-on to load
     * @return Addon
     * @throws Exception
     */
    public static function loadAddonResource($type, $addon)
    {
        $folders  = Config::getAddOnLocations();
        $file     = null;
        $type_map = array(
            self::_PLUGIN => array(
                'abbreviation' => 'pi',
                'name' => 'plugin'
            ),
            self::_FIELDTYPE => array(
                'abbreviation' => 'ft',
                'name' => 'fieldtype'
            ),
            self::_HOOKS => array(
                'abbreviation' => 'hooks',
                'name' => 'hooks'
            ),
            self::_TASKS => array(
                'abbreviation' => 'tasks',
                'name' => 'tasks'
            ),
            self::_MODIFIER => array(
                'abbreviation' => 'mod',
                'name' => 'modifier'
            ),
            self::_API => array(
                'abbreviation' => 'api',
                'name' => 'API'
            ),
            self::_CORE => array(
                'abbreviation' => 'core',
                'name' => 'Core'
            ),
            self::_INTERFACE => array(
                'abbreviation' => 'interface',
                'name' => 'Interface'
            )
        );
        
        if (!isset($type_map[$type])) {
            Log::error("Unknown add-on type.", "API", "Resource");
            throw new Exception("Unknown add-on type.");
        }
        
        // grab the abbreviation and name
        $addon_details = $type_map[$type];
        $abbr = $addon_details['abbreviation'];
        $name = $addon_details['name'];

        // loop through folders looking for addon
        foreach ($folders as $folder) {
            if (File::exists(BASE_PATH.'/'.$folder.$addon.'/'.$abbr.'.'.$addon.'.php')) {
                $file = $folder.$addon.'/'.$abbr.'.'.$addon.'.php';
                break;
            }
        }

        if (!$file) {
//            Log::error("Could not find files to load the `{$addon}` {$name}.", "API", "Resource");
            throw new ResourceNotFoundException("Could not find files to load the `{$addon}` {$name}.");
        }

        $class = ucwords($name) . "_" . $addon;

        if (!class_exists($class)) {
            throw new ResourceNotFoundException("Improperly formatted {$name} object.");
        }

        return new $class();
    }
}