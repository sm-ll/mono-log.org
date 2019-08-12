<?php
/**
 * Plugin
 * Abstract implementation for creating new tags for Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 *
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
abstract class Plugin extends Addon
{
    /**
     * An array of parameters passed by the user
     * @public array
     */
    public $attributes;

    /**
     * Content between the opening and closing tags of this plugin
     * @public string
     */
    public $content;

    /**
     * The contextual variables of the scope into which this plugin is being loaded
     * @public string
     */
    public $context;


    /**
     * Fetches a value from the user-passed parameters
     *
     * @param mixed  $keys  Key of value to retrieve, an array will allow fallback param names
     * @param mixed  $default  Default value if no value is found
     * @param callable  $validity_check  Allows a callback function to validate parameter
     * @param boolean  $is_boolean  Indicates parameter is boolean
     * @param boolean  $force_lower  Force the parameter's value to be lowercase?
     * @return mixed
     */
    protected function fetchParam($keys, $default=NULL, $validity_check=NULL, $is_boolean=FALSE, $force_lower=TRUE)
    {
        $keys = Helper::ensureArray($keys);

        foreach ($keys as $key) {

            if (isset($this->attributes[$key])) {

                $value = ($force_lower) ? strtolower($this->attributes[$key]) : $this->attributes[$key];

                if ( ! $validity_check || ($validity_check && is_callable($validity_check) && $validity_check($value) === TRUE)) {
                    // account for yes/no parameters
                    if ($is_boolean === TRUE) {
                        return !in_array(strtolower($value), array("no", "false", "0", "", "-1"));
                    }

                    // otherwise, standard return
                    return $value;
                }
            }
        }

        return $default;
    }


    /**
     * Legacy support for fetchParam
     * @deprecated
     *
     * @param mixed  $keys  Key of value to retrieve, an array will allow fallback param names
     * @param mixed  $default  Default value if no value is found
     * @param string  $validity_check  Allows a boolean callback function to validate parameter
     * @param boolean  $is_boolean  Indicates parameter is boolean
     * @param boolean  $force_lower  Force the parameter's value to be lowercase?
     * @return mixed
     */
    protected function fetch_param($keys, $default=NULL, $validity_check=NULL, $is_boolean=FALSE, $force_lower=TRUE)
    {
        $this->log->warn('Use of `$this->fetch_param()` is deprecated, please use `$this->fetchParam()` instead.');
        return $this->fetchParam($keys, $default, $validity_check, $is_boolean, $force_lower);
    }

    /**
     * Attempts to fetch a given $key's value from (in order): user-passed parameters, config file, default value
     *
     * @param string  $keys  Key of value to retrieve
     * @param mixed  $default  Default value if no value is found
     * @param callable  $validity_check  Allows a boolean callback function to validate parameter
     * @param boolean  $is_boolean  Indicates parameter is boolean
     * @param boolean  $force_lower  Force the parameter's value to be lowercase?
     * @return mixed
     */
    protected function fetch($keys, $default=NULL, $validity_check=NULL, $is_boolean=FALSE, $force_lower=TRUE)
    {
        return Helper::pick(
            $this->fetchParam($keys, NULL, $validity_check, $is_boolean, $force_lower),   // check for user-defined parameters first
            $this->fetchConfig($keys, NULL, $validity_check, $is_boolean, $force_lower),  // then config-file definitions section
            $default                                                                     // and finally, the passed default value if none found
        );
    }
}