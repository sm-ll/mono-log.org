<?php
/**
 * Modifier_full_urls
 * Prepends the site's site root to any local absolute URLs
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_full_urls extends Modifier
{
    public function index($value, $parameters=array()) {
        $full_url_start = Config::getSiteURL() . Config::getSiteRoot();

        return preg_replace_callback('/="(\/[^"]+)"/ism', function($item) use ($full_url_start) {
            return '="' . Path::tidy($full_url_start . $item[1]) . '"';
        }, $value);
    }
}