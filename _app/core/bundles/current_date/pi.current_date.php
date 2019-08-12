<?php
/**
 * Displays the current date.
 */
class Plugin_current_date extends Plugin
{
    public function index()
    {
        $format = $this->fetchParam('format', 'Y-m-d', null, FALSE, FALSE);
        return Date::format($format);
    }
}
