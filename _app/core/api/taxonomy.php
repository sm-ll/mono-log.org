<?php
/**
 * Taxonomy
 * API for interacting with taxonomies
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Taxonomy
{
    /**
     * Attempts to grab the taxonomy type and value from the current URL
     * 
     * @param string  $path  Path to query
     * @return mixed
     */
    public static function getCriteria($url)
    {
        $taxonomies = Config::getTaxonomies();
        
        $segments = explode('/', ltrim($url, '/'));

        foreach ($taxonomies as $taxonomy) {
            if (in_array($taxonomy, $segments)) {
                $data = array_split($segments, $taxonomy);
                return array(
                    'type' => array_get($data, 0),
                    'slug' => array_get($data, 1)
                );
            }
        }

        return FALSE;
    }


    /**
     * Checks to see if the given $url is a taxonomy URL
     *
     * @param string  $url  URL to check
     * @return bool
     */
    public static function isTaxonomyURL($url)
    {
        $taxonomies = Config::getTaxonomies();
        $segments = explode('/', ltrim($url, '/'));

        if (count(array_intersect($segments, $taxonomies)) > 0) {
            return TRUE;
        }

        return FALSE;
    }


    /**
     * Checks if a given $field is a taxonomy
     *
     * @param string  $field  Field to check
     * @return bool
     */
    public static function isTaxonomy($field)
    {
        return in_array($field, Config::getTaxonomies());
    }


    /**
     * Returns the URL for a given $taxonomy and $taxonomy_slug
     *
     * @param string  $folder  Folder to use
     * @param string  $taxonomy  Taxonomy to use
     * @param string  $taxonomy_slug  Taxonomy slug to use
     * @return string
     */
    public static function getURL($folder, $taxonomy, $taxonomy_slug)
    {
        $url  = Config::getSiteRoot() . '/' . $folder . '/' . $taxonomy . '/';
        $url .= (Config::getTaxonomySlugify()) ? Slug::make($taxonomy_slug) : $taxonomy_slug;
        
        // if taxonomies are not case-sensitive, make it lowercase
        if (!Config::getTaxonomyCaseSensitive()) {
            $url = strtolower($url);
        }

        return Path::tidy($url);
    }
    
    
    /**
     * Returns the stored name for a $taxonomy and $taxonomy_slug if exists in cache
     * 
     * @param string  $taxonomy  Taxonomy to use
     * @param string  $taxonomy_slug  Taxonomy slug to look up
     * @return string
     */
    public static function getTaxonomyName($taxonomy, $taxonomy_slug)
    {
        return Helper::pick(ContentService::getTaxonomyName($taxonomy, $taxonomy_slug), $taxonomy_slug);
    }
}