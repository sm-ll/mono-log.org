<?php
class Tasks extends Addon
{
    /**
     * List of tasks defined by this object
     * @private array
     */
    private $defined_tasks = array();

    /**
     * We should skip loading tasks so as to not infinite loop
     * @public bool
     */
    public $skip_tasks = true;


    /**
     * Defines a list of tasks to schedule at given intervals
     *
     * @throws Exception
     * @return void
     */
    public function define()
    {
        $this->log->debug("A task file exists, but no tasks were defined.");
    }


    /**
     * Adds a task to the internal task list
     *
     * @param int  $interval  Interval, in minutes
     * @param string  $method  Name of method to call
     * @return void
     */
    public function add($interval, $method)
    {
        $this->defined_tasks[$method] = $interval;
    }


    /**
     * Gets the internal list of tasks
     *
     * @return array
     */
    public function get()
    {
        return $this->defined_tasks;
    }
}