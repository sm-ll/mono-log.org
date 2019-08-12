<?php
class Core extends Addon
{
    /**
     * We should skip loading tasks so as to not infinite loop
     * @public bool
     */
    protected $skip_core = true;
}