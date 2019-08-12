<?php
class Debug
{
    private static $starts = array();
    private static $time_log = array();
    private static $totals = array();
    private static $counts = array();
    private static $values = array();
    private static $milestones = array();
    private static $enabled = true;


    /**
     * values
     * -------------------------------------------------------------------------------------- */

    /**
     * Set a value
     * 
     * @param string  $key  Key of value
     * @param mixed  $value  Value of value
     * @return void
     */
    public static function setValue($key, $value)
    {
        self::$values[$key] = $value;
    }


    /**
     * check for enabled
     * -------------------------------------------------------------------------------------- */

    /**
     * Disables debug checking
     * 
     * @return void
     */
    public static function disable()
    {
        self::$enabled = false;
    }


    /**
     * counting
     * -------------------------------------------------------------------------------------- */

    /**
     * Increment a count to a given $action
     * 
     * @param string  $namespace  Namespace of action
     * @param string  $action  Action to count
     * @return void
     */
    public static function increment($namespace, $action)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        $self_hash = self::markStart('debug', 'counting');
        
        $namespace = strtolower($namespace);
        $action = strtolower($action);
        
        if (!isset(self::$counts[$namespace]) || !is_array(self::$counts[$namespace]) || !isset(self::$counts[$namespace][$action])) {
            self::$counts[$namespace][$action] = 1;
        } else {
            self::$counts[$namespace][$action]++;
        }
        
        self::markEnd($self_hash);
    }


    /**
     * marking
     * -------------------------------------------------------------------------------------- */

    /**
     * Marks a starting point for an operation, returns unique hash
     * 
     * @param string  $namespace  Namespacing of operation
     * @param string  $type  Type of operation being measured
     * @param int  $time  Optional start time to use
     * @param bool  $self  Is Debug measuring itself?
     * @return string|null|void
     */
    public static function markStart($namespace, $type, $time=null, $self=false)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        // is debug measuring itself?
        if (!$self) {
            $self_hash = self::markStart('debug', 'timing', null, true);
        }
        
        $namespace = strtolower($namespace);
        $type = strtolower($type);
        $time = (!$time) ? time() : $time;
        
        $hash = $namespace . '---' . $type . '---' . crc32($time);
        
        self::$starts[$hash] = microtime(true);

        // is debug measuring itself?
        if (isset($self_hash)) {
            self::markEnd($self_hash, true);
        }
        
        return $hash;
    }


    /**
     * Marks an ending point for an operation, calculates totals, returns time operation took
     * 
     * @param string  $hash  Operation hash to end
     * @param bool  $self  Is Debug measuring itself?
     * @return float|bool|void
     */
    public static function markEnd($hash, $self=false)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }

        // is debug measuring itself?
        if (!$self) {
            $self_hash = self::markStart('debug', 'timing', null);
        }
        
        // do this immediately to skew results as little as possible
        $end = microtime(true);
        
        // we're not measuring this
        if ($hash === 0) {
            return null;
        }

        // we don't know about this start, abort
        if (!isset(self::$starts[$hash])) {
            // we're done
            return false;
        }
        
        // calculate diff
        $diff = $end - self::$starts[$hash];
        
        // parse hash
        list($namespace, $type, $hash) = explode('---', $hash);
        
        // ensure time_log exists for type
        if (!isset(self::$time_log[$namespace]) || !is_array(self::$time_log[$namespace])) {
            self::$time_log[$namespace] = array();
        }
        
        if (!isset(self::$time_log[$namespace][$type])) {
            self::$time_log[$namespace][$type] = array();
        }
        
        // add to time_log
        array_push(self::$time_log[$namespace][$type], $diff);
        
        // ensure totals exist
        if (!isset(self::$totals[$namespace]) || !is_array(self::$totals[$namespace])) {
            self::$totals[$namespace] = array();
        }
        
        if (!isset(self::$totals[$namespace][$type])) {
            self::$totals[$namespace][$type] = 0.0;
        }
        
        // add to totals
        self::$totals[$namespace][$type] += $diff;
        
        // we can delete start now
        unset(self::$starts[$hash]);

        // is debug measuring itself?
        if (isset($self_hash)) {
            self::markEnd($self_hash, true);
        }
        
        // return diff in case dev wants to do something with it
        return $diff;
    }


    /**
     * Marks a milestone in the code
     * 
     * @param string  $label  Text to display as the lap-label
     * @return void
     */
    public static function markMilestone($label)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }

        // measure
        $self_hash = self::markStart('debug', 'timelining');
        
        $time = microtime(true) - STATAMIC_START;

        // mark start
        if (empty(self::$milestones)) {
            self::$milestones['0.0000s'] = '<em>---------</em> start';
        }

        self::$milestones[number_format($time, 8)] = $label;

        // measure
        self::markEnd($self_hash);
    }


    /**
     * reporting
     * -------------------------------------------------------------------------------------- */
    
    /**
     * Get a time log for a given type
     * 
     * @param string  $namespace  Namespace of operation to get log for
     * @param string  $type  Type of operation to get log for
     * @return array|void
     */
    public static function getTimeLog($namespace, $type)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        if (!isset(self::$time_log[$namespace]) || !is_array(self::$time_log[$namespace])) {
            return array();
        }
        
        return array_get(self::$time_log[$namespace], strtolower($type), array());
    }


    /**
     * Get a time log for all types
     *
     * @param boolean  $refer_to_config  Should we refer to configured options when nothing was timed?
     * @return array|void
     */
    public static function getAllTimeLogs($refer_to_config=false)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        $time_logs = self::$time_log;

        if (count($time_logs) === 0 && $refer_to_config) {
            return "None of the configured events occurred.";
        }

        foreach ($time_logs as $key => $value) {
            $time_logs[$key] = $value;
            
            foreach ($value as $sub_key => $sub_value) {
                $time_logs[$key][$sub_key] = number_format($sub_value, 4) . 's';
            }
        }

        return $time_logs;
    }
    
    
    /**
     * Get the total time for a given type
     * 
     * @param string  $type  Type of operation to get total for
     * @return float|void
     */
    public static function getTotalTime($type)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        return array_get(self::$totals, strtolower($type), 0.0);
    }
    
    
    /**
     * Get the total time for all types
     * 
     * @param boolean  $refer_to_config  Should we refer to configured options when nothing was timed?
     * @return array|string|void
     */
    public static function getAllTotals($refer_to_config=false)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        $totals = self::$totals;
        $output = array();
        
        if (count($totals) === 0 && $refer_to_config) {
            return "None of the configured events occurred.";
        }
        
        foreach ($totals as $namespace => $operations) {
            $temp = $operations;
            $total_ms = 0;
            
            // sort, most expensive on top
            asort($temp);
            $temp = array_reverse($temp);
            
            // walk through array, formatting numbers
            $i = 0;
            array_walk($temp, function(&$item, $key) use (&$i, &$total_ms) {
                $total_ms += $item * 1000;
                $item = $i++ . '_' . number_format($item, 4) . 's';    
            });

            $output[$namespace . ' <em>(' . round($total_ms) . 'ms)</em>'] = array_flip($temp);
        }
        
        return $output;
    }
    
    
    /**
     * Get the count for a given action
     * 
     * @param string  $action  Action to retrieve a count for
     * @return int|void
     */
    public static function getCount($action)
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        return array_get(self::$counts, strtolower($action), 0);
    }
    
    
    /**
     * Get counts for all actions
     * 
     * @return array|void
     */
    public static function getAllCounts()
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        return self::$counts;
    }
    
    
    /**
     * Get a value for a given key
     * 
     * @param string  $key  Key of value to retrieve
     * @return mixed
     */
    public static function getValue($key)
    {
        return array_get(self::$values, $key, null);
    }
    
    
    /**
     * Get all values
     * 
     * @return array|void
     */
    public static function getAllValues()
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        return self::$values;
    }


    /**
     * Get all milestones
     * 
     * @return array|void
     */
    public static function getAllMilestones()
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        $last = 0.0;
        $output = array();
        $i = 0;
        foreach (self::$milestones as $time => $event) {
            if ((float) $time == 0) {
                $output['0.0000s'] = $event;
                continue;
            }
            
            $time_float = (float) $time;
            $diff = $time_float - $last;
            $last = $time_float;
            $formatted_diff = number_format($diff * 1000, 0);
            
            if ($formatted_diff != 0) {
                $output[$i++ . '_' . number_format($time_float, 4) . 's'] = '<em>' . str_pad('<span>~</span>' . $formatted_diff . 'ms', 21, ' ', STR_PAD_LEFT) . '</em>  ' . $event;
            } else {
                $output[$i++ . '_' . number_format($time_float, 4) . 's'] = '<em>' . str_pad('<span>~' . $formatted_diff . 'ms</span>', 21, ' ', STR_PAD_LEFT) . '</em>  ' . $event;
            }
        }
        
        return $output;
    }


    /**
     * Get everything
     * 
     * @return array|void
     */
    public static function getAll()
    {
        // short-circuit when not enabled
        if (!self::$enabled) {
            return;
        }
        
        return array(
            'timeline' => self::getAllMilestones(),
            'values' => self::getAllValues(),
            'time_spent' => self::getAllTotals(),
            'counts' => self::getAllCounts()
        );
    }
}