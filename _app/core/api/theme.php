<?php

use Symfony\Component\Finder\Finder as Finder;

/**
 * Theme
 * API for interacting with the site's themes
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Theme
{
    // theme
    // ------------------------------------------------------------------------

    /**
     * Returns the current theme folder name
     *
     * @return string
     */
    public static function getName()
    {
        return Config::getTheme();
    }


    /**
     * Returns the path to the current theme
     *
     * @return string
     */
    public static function getPath()
    {
        return Config::getCurrentThemePath();
    }



    // templates
    // ------------------------------------------------------------------------

    /**
     * Fetches the contents of a template
     *
     * @param string  $template  Name of template to retrieve
     * @return string
     */
    public static function getTemplate($template)
    {
        return File::get(Path::assemble(BASE_PATH, self::getTemplatePath(), $template . '.html'));
    }


    /**
     * Returns a given $template parsed with given $data
     *
     * @param string  $template  Template to parse
     * @param array  $data  Associative array of data to fill into template
     * @return string
     */
    public static function getParsedTemplate($template, Array $data=array())
    {
        $parser         = new Lex\Parser();
        $template_path  = Config::getTemplatesPath() . '/templates/' . ltrim($template, '/') . '.html';

        return $parser->parse(File::get($template_path, ""), $data, array('statamic_view', 'callback'));
    }



    /**
     * Returns a list of templates for this theme
     *
     * @param string  $theme  Optional theme to list from, otherwise, current theme
     * @return array
     */
    public static function getTemplates($theme=NULL)
    {
        $templates = array();

        $finder = new Finder();

        $files = $finder->files()
          ->in(Path::assemble(BASE_PATH, Config::getThemesPath(), Config::getTheme(), 'templates'))
          ->name('*.html')
          ->followLinks();

        if (iterator_count($files) > 0) {
          foreach ($files as $file) {
            $templates[] = str_replace('.' . $file->getExtension(), '', $file->getRelativePathname());
          }
        }

        return $templates;
    }


    /**
     * Returns the path to the current theme's template directory
     *
     * @return string
     */
    public static function getTemplatePath()
    {
        return self::getPath() . 'templates/';
    }



    // layouts
    // ------------------------------------------------------------------------

    /**
     * Returns a list of layouts for a given $theme, or current theme if no $theme passed
     *
     * @param mixed  $theme  Theme to list layouts from, or current if none is passed
     * @return array
     */
    public static function getLayouts($theme=NULL)
    {
        $layouts = array();
        $list = glob("_themes/" . Helper::pick($theme, Config::getTheme()) . "/layouts/*");

        if ($list) {
            foreach ($list as $name) {
                $start = strrpos($name, "/")+1;
                $end = strrpos($name, ".");
                $layouts[] = substr($name, $start, $end-$start);
            }
        }

        return $layouts;
    }
}