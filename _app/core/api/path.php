<?php
/**
 * Path
 * API for manipulating and working with paths
 *
 * @author      Statamic
 * @package     API
 * @copyright   2014 Statamic
 */
use Symfony\Component\Filesystem\Filesystem as Filesystem;

class Path
{
    /**
     * Finds a given path on the server, adding in any ordering elements missing
     *
     * @param string  $path  Path to resolve
     * @return string
     */
    public static function resolve($path)
    {
        $content_root = Config::getContentRoot();
        $content_type = Config::getContentType();

        if (strpos($path, "/") === 0) {
            $parts = explode("/", substr($path, 1));
        } else {
            $parts = explode("/", $path);
        }

        $fixedpath = "/";
        foreach ($parts as $part) {
            if (! File::exists(Path::assemble($content_root,$path . '.' . $content_type))
                && ! is_dir(Path::assemble($content_root, $part))) {

                // check folders
                $list = Statamic::get_content_tree($fixedpath, 1, 1, FALSE, TRUE, FALSE);
                foreach ($list as $item) {
                    $t = basename($item['slug']);
                    if (Slug::isNumeric($t)) {
                        $nl = strlen(Slug::getOrderNumber($t)) + 1;
                        if (strlen($part) >= (strlen($item['slug']) - $nl) && Pattern::endsWith($item['slug'], $part)) {
                            $part = $item['slug'];
                            break;
                        }
                    } else {
                        if (Pattern::endsWith($item['slug'], $part)) {
                            if (strlen($part) >= strlen($t)) {
                                $part = $item['slug'];
                                break;
                            }
                        }
                    }
                }

                // check files

                $list = Statamic::get_file_list($fixedpath);

                foreach ($list as $key => $item) {
                    if (Pattern::endsWith($key, $part)) {
                        $t = basename($item);

                        $offset = 0;
                        if (Pattern::startsWith($key, '__')) {
                            $offset = 2;
                        } elseif (Pattern::startsWith($key, '_')) {
                            $offset = 1;
                        }

                        if (Config::getEntryTimestamps() && Slug::isDateTime($t)) {
                            if (strlen($part) >= (strlen($key) - 16 - $offset)) {
                                $part = $key;
                                break;
                            }
                        } elseif (Slug::isDate($t)) {
                            if (strlen($part) >= (strlen($key) - 12 - $offset)) {
                                $part = $key;
                                break;
                            }
                        } elseif (Slug::isNumeric($t)) {
                            $nl = strlen(Slug::getOrderNumber($key)) + 1;
                            if (strlen($part) >= (strlen($key) - $nl - $offset)) {
                                $part = $key;
                                break;
                            }
                        } else {
                            $t = basename($item);
                            if (strlen($part) >= strlen($t) - $offset) {
                                $part = $key;
                                break;
                            }
                        }
                    }
                }
            }

            if ($fixedpath != '/') {
                $fixedpath .= '/';
            }

            $fixedpath .= $part;
        }

        return $fixedpath;
    }


    /**
     * Removes occurrences of "//" in a $path (except when part of a protocol)
     *
     * @param string  $path  Path to remove "//" from
     * @return string
     */
    public static function tidy($path)
    {
        return preg_replace("#(^|[^:])//+#", "\\1/", $path);
    }


    /**
     * Trim slashes from either end of a given $path
     *
     * @param string  $path  Path to trim slashes from
     * @return string
     */
    public static function trimSlashes($path)
    {
        return trim($path, '/');
    }


    /**
     * Cleans up a given $path, removing any order keys (date-based or number-based)
     *
     * @param string  $path  Path to clean
     * @return string
     */
    public static function clean($path)
    {
        // remove draft and hidden flags
        $path = preg_replace("#/_[_]?#", "/", $path);

        // if we don't want entry timestamps, handle things manually
        if (!Config::getEntryTimestamps()) {
            $file     = substr($path, strrpos($path, "/"));
            
            // trim path if needed
            if (-strlen($file) + 1 !== 0) {
                $path = substr($path, 0, -strlen($file) + 1);
            }
            
            $path     = preg_replace(Pattern::ORDER_KEY, "", $path);
            $pattern  = (preg_match(Pattern::DATE, $file)) ? Pattern::DATE : Pattern::ORDER_KEY;
            $file     = preg_replace($pattern, "", $file);

            return Path::tidy($path . $file);
        }
        
        // otherwise, just remove all order-keys
        return preg_replace(Pattern::ORDER_KEY, "", $path);
    }


    /**
     * Pretty, end user paths
     *
     * @param string  $path  Path to clean
     * @return string
     */
    public static function pretty($path)
    {
        return self::tidy(self::clean('/' . $path));
    }


    /**
     * Checks if a given path is non-public content
     *
     * @param string  $path  Path to check
     * @return boolean
     */
    public static function isNonPublic($path)
    {
        if (substr($path, 0, 1) !== "/") {
            $path = "/" . $path;
        }

        return (strpos($path, "/_") !== false);
    }


    /**
     * Removes any filesystem path outside of the site root
     *
     * @param string  $path  Path to trim
     * @return string
     */
    public static function trimFilesystem($path)
    {
        return str_replace(self::standardize(BASE_PATH) . "/", "", $path);
    }


    /**
     * Removes any filesystem path outside of the content root
     *
     * @param string  $path  Path to trim
     * @return string
     */
    public static function trimFileSystemFromContent($path)
    {
        return str_replace(self::standardize(BASE_PATH) . "/" . Config::getContentRoot(), "", $path);
    }


    /**
     * Removes the _site_root from a path if the site is in a subdirectory
     *
     * @param string  $path  Path to trim
     * @return string
     */
    public static function trimSubdirectory($path)
    {
        $site_root = Config::getSiteRoot();

        if ($site_root != '/') {
            $path = str_replace($site_root, '/', $path);
        }

        return $path;
    }


    /**
     * Creates a URL-friendly path to an asset
     *
     * @param string  $path        Full path to asset
     * @param boolean $as_variable Prefix path with the {{ _site_root }} var (for _content files)
     * @param boolean $relative    Returns only the relative part of the path relative to {{ _site_root }}
     * @return string
     */
    public static function toAsset($path, $as_variable = false, $relative = false)
    {
        $asset_path = Path::trimFilesystem($path);

        if (Pattern::startsWith($asset_path, '{{ _site_root }}')) {
            $asset_path = str_replace('{{ _site_root }}', '/', $asset_path);
        }

        if ( ! Pattern::startsWith($asset_path, Config::getSiteRoot())) {
            $asset_path = ($as_variable)
                          ? '{{ _site_root }}' . $asset_path
                          : ($relative ? '' : Config::getSiteRoot()) . '/' . $asset_path;
        }

        $asset_path = rtrim(self::tidy($asset_path), '/');

        return ($relative ? ltrim($asset_path, '/') : $asset_path);
    }


    /**
     * Creates a full system path from an asset URL
     *
     * @param string  $path  Path to start from
     * @return string
     */
    public static function fromAsset($path, $with_variable=false)
    {
        if ($with_variable) {
            return self::tidy(BASE_PATH . '/' . str_replace('{{ _site_root }}', '/', $path));
        }

        return self::tidy(BASE_PATH . '/' . str_replace(Config::getSiteRoot(), '/', $path));
    }


    /**
     * Standardizes a filesystem path between *nix and windows
     *
     * @param string  $path  Path to standardize
     * @return string
     */
    public static function standardize($path)
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $path = ltrim(str_replace('\\', '/', $path), '/');
        }

        return $path;
    }


    /**
     * Assembles a URL from an ordered list of segments
     *
     * @param string  Open ended number of arguments
     * @return string
     */
    public static function assemble()
    {
        $args = func_get_args();

        if (!is_array($args) || !count($args)) {
            return NULL;
        }

        $path = self::tidy('/' . join($args, '/'));

        return self::standardize($path);
    }


    /**
     * Given an existing path, convert it to a path relative to a given starting path
     *
     * @param string $endPath   Absolute path of target
     * @param string $startPath Absolute path where traversal begins
     *
     * @return string Path of target relative to starting path
     */
    public static function makeRelative($endPath, $startPath)
    {
        $fs = new Filesystem();

        return $fs->makePathRelative($endPath, $startPath);
    }


    /**
     * Prepends a / to a given $path if it's not there
     *
     * @param string  $path  path to check
     * @return string
     */
    public static function addStartingSlash($path)
    {
        return (substr($path, 0, 1) !== '/') ? '/' . $path : $path;
    }


    /**
     * Removes the / from the beginning of a given $path if it's there
     *
     * @param string  $path  path to check
     * @return string
     */
    public static function removeStartingSlash($path)
    {
        return (substr($path, 0, 1) === '/' && strlen($path) > 1) ? substr($path, 1) : $path;
    }


    /**
     * Checks to see if this path is a draft
     *
     * @param string  $path  Path to check
     * @return boolean
     */
    public static function isDraft($path)
    {
        return (strpos($path, '/__') !== false);
    }


    /**
     * Checks to see if this path is hidden
     *
     * @param string  $path  Path to check
     * @return boolean
     */
    public static function isHidden($path)
    {
        return (bool) (preg_match("#/_[^_]#", $path));
    }
}