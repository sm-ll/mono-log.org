<?php

class Plugin_switch extends Plugin
{
    public static $container = array();

    public function index()
    {
        $between = $this->fetchParam('between', false, false, false, false);

        if ($between) {

            // Expanded mode lets you set the number of times a value is repeated
            $expanded_mode = strstr($between, ':');

            /*
            |--------------------------------------------------------------------------
            | Unique Hash
            |--------------------------------------------------------------------------
            |
            | Here we create a unique hash based on the parameters to provide 
            | users a method of using multiple switch tags in a single template
            |
            */

            $hash = md5(implode(",",$this->attributes));

            if ( ! isset(self::$container[$hash])) {
                // Instance counter
                self::$container[$hash] = 0;
            }

            $switch_vars = Helper::explodeOptions($between);

            // Expand those thangs!
            if ($expanded_mode) {
                $switch_vars = $this->expand($switch_vars);
            }

            $switch = $switch_vars[(self::$container[$hash]) % count($switch_vars)];

            // Iterate!
            self::$container[$hash]++;

            return $switch;
        }

        return null;
    }

    /**
     * Expand switch values with : colon syntax to 
     * let you set the number of times you want a
     * specific value repeated
     *
     * @return array
     **/
    private function expand($values)
    {
        $switch_vars = array();

        foreach ($values as $key => $value) {
            $repeating_values = explode(':',  $value);
            $repeat_count = array_get($repeating_values, 1, 1);

            for ($i = 1; $i <= $repeat_count; $i++) {
                $switch_vars[] = $repeating_values[0];
            }
        }

        return $switch_vars;
    }
}
