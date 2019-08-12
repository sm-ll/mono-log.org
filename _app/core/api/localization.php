<?php
/**
 * HTML
 * API for using localized strings
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Localization
{
    /**
     * Fetch an L10n content string
     *
     * @param $key      string  YAML key of the desired text string
     * @param $language string  Optionally override the desired language
     * @param $lower    boolean  Convert word to lowercase?
     * @return mixed
     */
    public static function fetch($key, $language = null, $lower = false)
    {
        $app = \Slim\Slim::getInstance();

        $language = $language ? $language : Config::getCurrentLanguage();

        $value = $key;

        /*
        |--------------------------------------------------------------------------
        | Check for new language
        |--------------------------------------------------------------------------
        |
        | English is loaded by default. If requesting a language not already
        | cached, go grab it.
        |
        */

        if ( ! isset($app->config['_translations'][$language])) {
            if (File::exists(Config::getTranslation($language))) {
                $app->config['_translations'][$language] = YAML::parse(Config::getTranslation($language));
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve translation
        |--------------------------------------------------------------------------
        |
        | If the set language is found and the key exists, return it. Falls back to
        | English, and then falls back to the slug-style key itself.
        |
        */

        if (array_get($app->config['_translations'], $language.':translations:'.$value, false)) {
            $value = array_get($app->config['_translations'][$language]['translations'], $value);
        } else {
            $value = array_get($app->config['_translations']['en']['translations'], $value, $value);
        }

        return ($lower) ? strtolower($value) : $value;
    }
    
    
    /**
     * Initializes localization, overwriting any in-code defaults
     * 
     * @return void
     */
    public static function initialize()
    {
        // update publication states
        Statamic::$publication_states = array(
            'live'   => self::fetch('live'),
            'hidden' => self::fetch('hidden'),
            'draft'  => self::fetch('draft')
        );
    }
}
