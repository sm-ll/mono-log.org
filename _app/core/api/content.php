<?php
use Symfony\Component\Finder\Finder as Finder;
/**
 * Content
 * API for interacting with content within the site
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Content
{
    private static $fetched_content = array();
    
    /**
     * Checks to see if a given $slug (and optionally $folder) exist
     *
     * @param string  $slug  Slug to check
     * @param mixed  $folder  Folder to look inside
     * @return bool
     */
    public static function exists($slug, $folder=NULL)
    {
        $folder        = (is_null($folder)) ? '' : $folder;
        $content_path  = Config::getContentRoot() . "/{$folder}";
        $content_type  = Config::getContentType();

        return file_exists("{$content_path}/{$slug}.{$content_type}");
    }


    /**
     * Parses given $template_data with $data, converts content to $type
     *
     * @param string  $template_data  Template data to parse
     * @param array  $data  List of variables to fill in
     * @param mixed  $type  Optional content type to render
     * @return string
     */
    public static function parse($template_data, $data, $type=NULL)
    {
        $app    = \Slim\Slim::getInstance();
        $config = $app->config;
        
        foreach ($config as $key => $item) {
            if (is_object($item)) {
                unset($config[$key]);
            }
        }

        $data  = $data + $config;

        $parse_order = Config::getParseOrder();

        if ($parse_order[0] == 'tags') {
            $output = Parse::template($template_data, $data);
            $output = self::transform($output, $type);
        } else {
            $output = self::transform($template_data, $type);
            $output = Parse::template($output, $data);
        }

        return $output;
    }


    /**
     * Render content via a given $content_type
     *
     * @param string  $content  Content to render
     * @param mixed  $content_type  Content type to use (overrides configured content_type)
     * @return string
     */
    public static function transform($content, $content_type=NULL) {
        $content_type = Helper::pick($content_type, Config::getContentType());

        // render HTML from the given $content_type
        switch (strtolower($content_type)) {
            case "markdown":
            case "md":
                $content = Parse::markdown($content);
                break;

            case "text":
            case "txt":
                $content = nl2br(strip_tags($content));
                break;

            case "textile":
                $content = Parse::textile($content);
        }

        if (Config::get('enable_smartypants', TRUE) === TRUE) {
            $content = Parse::smartypants($content);
        } elseif (Config::get('enable_smartypants', TRUE) === 'typographer') {
            $content = Parse::smartypants($content, TRUE);
        } 

        return trim($content);
    }


    /**
     * Fetch a single content entry or page
     *
     * @param string  $url  URL to fetch
     * @param bool  $parse_content  Should we parse content?
     * @param bool  $supplement  Should we supplement the content?
     * @return array
     */
    public static function get($url, $parse_content=true, $supplement=true)
    {
        $hash = Debug::markStart('content', 'getting');
        $url_hash = Helper::makeHash($url, $parse_content, $supplement);
        
        if (!isset(self::$fetched_content[$url_hash])) {
            $content_set  = ContentService::getContentByURL($url);
            $content      = $content_set->get($parse_content, $supplement);
            self::$fetched_content[$url_hash] = (isset($content[0])) ? $content[0] : array();
        }
        Debug::markEnd($hash);
        
        return self::$fetched_content[$url_hash];
    }
    
    
    /**
     * Finds content by path
     * 
     * @param string  $path  Path to use to look for content
     * @return array|false
     */
    public static function find($path)
    {
        $hash = Debug::markStart('content', 'finding');
        
        // ensure it starts with /
        $path = Path::tidy('/' . $path);
        
        ContentService::loadCache();
        $urls = ContentService::$cache['urls'];
        
        foreach ($urls as $url => $data) {
            if ($data['path'] === $path) {
                return Content::get($url, false, false);
            }
        }
        
        Debug::markEnd($hash);
        
        return false;
    }
}