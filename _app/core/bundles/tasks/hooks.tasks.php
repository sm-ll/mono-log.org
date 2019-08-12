<?php
use Symfony\Component\Finder\Finder as Finder;

/**
 * Task Scheduler
 * Allows for automating some tasks behind the scenes
 *
 * @author  Fred LeBlanc  <fred@statamic.com>
 * @copyright  2013
 */
class Hooks_tasks extends Hooks
{
    /**
     * One tick of the timer, probably one minute from the last
     *
     * @return void
     */
    public function tasks__tick()
    {
        $now   = time();
        $ticks = $this->cache->getYAML('ticks.yaml', array('last-tick' => 0));

        // only about once per minute
        if ($now - $ticks['last-tick'] < 52) {
            $this->log->debug("Tick skipped, interval happening too frequently.");
            return;
        }

        // mark that a tick happened
        $this->log->debug("Tick.");

        // grab all task files
        $finder     = new Finder();
        $task_files = $finder->files()
            ->in(BASE_PATH . Config::getAddOnsPath())
            ->name("tasks.*.php")
            ->followLinks();

        // mark this tick as having happened
        $this->cache->putYAML('ticks.yaml', array("last-tick" => $now));

        // if no task files were found, we're all set
        if (!$task_files->count()) {
            return;
        }

        // loop through task files
        foreach ($task_files as $task_file) {
            require_once($task_file->getRealPath());

            $object_name = $task_file->getRelativePath();
            $class_name  = "Tasks_" . $object_name;
            $last_fired  = $this->cache->getYAML('last-fired/' . $object_name . ".yaml", array());
            $task_object = new $class_name();

            // make sure that this is a Task-extending object
            if (!($task_object instanceof Tasks)) {
                return;
            }

            // set up the task object
            $task_object->define();
            $tasks = $task_object->get();

            // loop through defined tasks, calling them if their age is too old
            foreach ($tasks as $task => $interval) {
                // could not run task, warn and move on
                if (!method_exists($task_object, $task)) {
                    $this->log->warn("Could not run task `" . $task . "` from `Tasks_" . $object_name . "`.");
                    continue;
                }

                // check for age in the last-fired list
                if (isset($last_fired[$task]) && is_numeric($last_fired[$task]) && floor(($now - $last_fired[$task]) / 60) < $interval) {
                    $this->log->debug("Not firing task `" . $task . "`, interval has not fully passed yet.");
                    continue;
                }

                // attempt to run the task, if FALSE is returned, don't count this run
                if (!(bool) $task_object->$task()) {
                    $this->log->warn("Task `" . $task . "` ran but failed.");
                    continue;
                }

                // update the time in which tasks fired
                $last_fired[$task] = $now;
            }

            // store the last-fired list back to cache
            $this->cache->putYAML('last-fired/' . $object_name . ".yaml", $last_fired);
        }
    }
}