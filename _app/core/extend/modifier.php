<?php
/**
 * Modifier
 * Abstract implementation for creating new variable modifiers for Statamic
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 *
 * @copyright   2013 Statamic
 */
abstract class Modifier extends Addon
{
    /**
     * Executes the modifier, returning the new value
     *
     * @param string  $value  Value to modify
     * @param array  $parameters  Optional array of parameters for use in modification
     * @throws Exception
     * @return mixed
     */
    public function index($value, $parameters=array())
    {
        $this->log->error("A modifier exists, but the modification was not defined.");
        throw new Exception("A modifier exists, but the modification was not defined.");
    }
}