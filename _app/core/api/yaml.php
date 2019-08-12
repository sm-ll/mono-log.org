<?php
use Symfony\Component\Yaml\Yaml as sYaml;

/**
 * YAML
 * API for interacting with YAML
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class YAML
{
    /**
     * Gets the currently configured YAML parsing mode
     * 
     * @return string
     */
    public static function getMode()
    {
        return Config::get('yaml_mode', 'loose');
    }


    /**
     * Parse a block of YAML into PHP
     *
     * @param string  $yaml  YAML-formatted string to parse
     * @param string  $mode  Parsing mode to use
     * @return array
     */
    public static function parse($yaml, $mode = null)
    {
        // start measuring
        $hash = Debug::markStart('parsing', 'yaml');
        
        $mode = $mode ? $mode : self::getMode();

        switch ($mode) {            
            case 'loose':
                $result = Spyc::YAMLLoad($yaml);
                break;
            
            case 'strict': 
                $result = sYAML::parse($yaml);
                break;
            
            case 'transitional':
                try {
                    $result = sYaml::parse($yaml);
                } catch(Exception $e) {
                    Log::error($e->getMessage() . ' Falling back to loose mode.', 'core', 'yaml');
                    $result = Spyc::YAMLLoad($yaml);
                }
                break;
            
            default:
                // check for new lines, is this a file?
                $has_newline = (strpos($yaml, "\n") !== false);
                if (!$has_newline && File::exists($yaml)) {
                    // seems like it is
                    $yaml = File::get($yaml);
                }
                
                $result = Statamic\Dipper\Dipper::parse($yaml);
        }

        // end measuring
        Debug::markEnd($hash);
        Debug::increment('parses', 'yaml');
        
        return $result;
    }


    /**
     * Specifically parses a file
     *
     * @param string  $file  YAML-formatted File to parse
     * @return array
     */
    public static function parseFile($file)
    {
        if (File::exists($file)) {
            return self::parse($file);
        }

        return array();
    }


    /**
     * Send data back to YAML
     *
     * @param array  $array  Array of data to convert
     * @param string  $mode  Mode to parse (loose|transitional|strict)
     * @return string
     */
    public static function dump($array, $mode=null)
    {
        $mode = $mode ? $mode : self::getMode();

        switch ($mode) {
            case 'quick':
            case 'loose':
                return Spyc::YAMLDump($array, false, Config::get('yaml:wrap', 0));

            case 'strict':
                return sYaml::dump($array, 5, 2);

            case 'transitional':
                try {
                    return sYaml::dump($array, 5, 2);
                } catch(Exception $e) {
                    Log::warn($e->getMessage() . ' Falling back to loose mode.', 'core', 'YAML');
                    return Spyc::YAMLDump($array);
                }

            default:
                return Spyc::YAMLDump($array);
        }
    }
}