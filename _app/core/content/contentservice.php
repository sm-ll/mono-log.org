<?php
/**
 * ContentService
 * An intermediary between the content cache and the system
 *
 * @package statamic
 */
class ContentService
{
    public static $cache;
    public static $structure;
    public static $cache_loaded = false;
    public static $structure_loaded = false;
    public static $parent_cache = array();


    /**
     * Loads the content cache into the local cache variable if not done yet
     *
     * @param boolean  $force  Force this to load?
     * @return void
     * @throws Exception
     */
    public static function loadCache($force=false)
    {
        if (!$force && self::$cache_loaded) {
            return;
        }

        self::$cache_loaded = true;
        self::$cache = unserialize(File::get(Path::tidy(BASE_PATH . "/_cache/_app/content/content.php")));

        if (!is_array(self::$cache)) {

            // Attempt to make the cache folder. It doesn't hurt to try.
            Folder::make(BASE_PATH . '/_cache/');

            // something has gone wrong, log a message and set to an empty array
            self::$cache = array();
            Log::fatal('Could not find or access your cache. Try checking your file permissions.', 'core', 'ContentService');
            throw new Exception('Could not find or access your cache. Try checking your file permissions.');
        }
    }


    /**
     * Reset the cached caches
     *
     * @return void
     */
    public static function resetCaches()
    {
        self::$cache = null;
        self::$structure = null;
        self::$parent_cache = array();
        self::$cache_loaded = false;
        self::$structure_loaded = false;
    }


    /**
     * Loads the structure cache into the local structure variable if not done yet
     *
     * @return void
     * @throws Exception
     */
    public static function loadStructure()
    {
        if (self::$structure_loaded) {
            return;
        }

        self::$structure_loaded = true;
        self::$structure = unserialize(File::get(Path::tidy(BASE_PATH . "/_cache/_app/content/structure.php")));

        if (!is_array(self::$structure)) {
            // something has gone wrong, log a message and set to an empty array
            self::$cache = array();
            Log::fatal('Could not find or access your cache. Try checking your file permissions.', 'core', 'ContentService');
            throw new Exception('Could not find or access your cache. Try checking your file permissions.');
        }
    }



    // content checking
    // ------------------------------------------------------------------------

    /**
     * Is a given URL content that exists?
     *
     * @param string  $url  URL to check
     * @return bool
     */
    public static function isContent($url)
    {
        self::loadCache();
        return (
            isset(self::$cache['urls'][$url]) &&
            isset(self::$cache["content"][self::$cache['urls'][$url]['folder']]) &&
            isset(self::$cache['content'][self::$cache['urls'][$url]['folder']][self::$cache['urls'][$url]['file']])
        );
    }



    // single content entry
    // ------------------------------------------------------------------------

    /**
     * Gets cached content for one page based on a given URL
     *
     * @param string  $url  URL of content to load
     * @return array
     */
    public static function getContent($url)
    {
        self::loadCache();
        if (!self::isContent($url)) {
            return array();
        }

        return self::$cache["content"][self::$cache['urls'][$url]['folder']][self::$cache['urls'][$url]['file']]['data'];
    }


    /**
     * Gets a given URL in a content set
     *
     * @param string  $url  URL of content to load
     * @return ContentSet
     */
    public static function getContentAsContentSet($url)
    {
        return new ContentSet(array(self::getContent($url)));
    }


    // tree structures
    // ------------------------------------------------------------------------

    /**
     * Gets a tree of content information
     *
     * @param string  $base_url  URL for the base of the tree to load
     * @param int  $depth  Number of levels deep to return
     * @param boolean  $folders_only  Folders only
     * @param boolean  $include_entries  Should we include entries in our tree?
     * @param boolean  $show_hidden  Should we not include hidden content
     * @param boolean  $include_content  Should we include content from the found info?
     * @param mixed  $exclude  Array of URLs to exclude
     * @return array
     */
    public static function getContentTree($base_url, $depth=12, $folders_only=true, $include_entries=false, $show_hidden=false, $include_content=false, $exclude=false)
    {
        // load structure and set up variables
        self::loadStructure();
        $output = array();

        // exclude URLs
        $exclude = Helper::ensureArray($exclude);

        // no depth asked for
        if ($depth == 0) {
            return array();
        }

        // make sure we can find the requested URL in the structure
        if (!isset(self::$structure[$base_url])) {
            Log::debug('Could not find URL in structure cache.', 'core', 'ContentService');
            return array();
        }

        // depth measurements
        $starting_depth  = self::$structure[$base_url]['depth'] + 1;   // start one deeper than the base URL's depth
        $current_depth   = $starting_depth;

        // recursively grab the tree
        foreach (self::$structure as $url => $data) {
            // is this the right depth and not the 404 page?
            if ($data['depth'] !== $current_depth || $url == "/404") {
                continue;
            }

            // is this under the appropriate parent?
            if (!Pattern::startsWith(Path::tidy($data['parent'] . '/'), Path::tidy($base_url . '/'))) {
                continue;
            }

            // is this hidden?
            if ($data['is_draft'] || (!$show_hidden && $data['is_hidden'])) {
                continue;
            }

            // is this an entry when we don't want them?
            if (!$include_entries && $data['is_entry'] && !$data['is_page']) {
                continue;
            }

            // is this a non-folder when all we want is folders?
            if ($folders_only && $data['type'] != 'folder') {
                continue;
            }

            // is this in the excluded URLs list?
            if (in_array($url, $exclude)) {
                continue;
            }
            
            // get parent url
            $parent_url = substr($url, 0, strrpos($url, '/'));
            $parent_url = ($parent_url == "") ? Config::getSiteRoot() : $parent_url;
            
            // look up parent data in cache
            if (!isset(self::$parent_cache[$parent_url])) {
                // doesn't exist, load it up
                $parent_data = Content::get($parent_url, $include_content, false);

                if ($include_content) {
                    // give them everything
                    $parent = $parent_data;
                } else {
                    // just the bare necessities 
                    $parent = array(
                        'title' => isset($parent_data['title']) ? $parent_data['title'] : '',
                        'url'   => isset($parent_data['url']) ? $parent_data['url'] : ''
                    );
                }
                
                // now stick this in the cache for next time
                self::$parent_cache[$parent_url] = $parent;
            }

            // get information
            $content = Content::get($url, $include_content, false);

            // data to be returned to the tree
            $for_output = array(
                'type' => $data['type'],
                'title' => isset($content['title']) ? $content['title'] : '',
                'slug' => $content['slug'],
                'url' => $url,
                'depth' => $current_depth,
                'children' => self::getContentTree($url, $depth - 1, $folders_only, $include_entries, $show_hidden, $include_content, $exclude),
                'is_current' => (URL::getCurrent() == $url),
                'is_parent' => (URL::getCurrent() != $url && Pattern::startsWith(URL::getCurrent(), $url . '/')),
                'is_entry' => $data['is_entry'],
                'is_page' => $data['is_page'],
                'is_folder' => ($data['type'] == 'folder'),
                'order_key' => $data['order_key'],
                'sub_order_key' => $data['sub_order_key'],
                'parent' => array(self::$parent_cache[$parent_url])
            );

            // if we're including content, merge that in
            if ($include_content) {
                $for_output = $content + $for_output;
            }

            // add it to the list
            $output[] = $for_output;
        }

        // now we need to sort the nav items
        uasort($output, function($a, $b) {
            // sort on order_key
            $result = Helper::compareValues($a['order_key'], $b['order_key']);

            // if those matched, sort on sub_order_key
            if ($result === 0) {
                $result = Helper::compareValues($a['sub_order_key'], $b['sub_order_key']);
            }

            // return 1 or 0 or -1, whatever we ended up with
            return $result;
        });

        // re-key the array
        $output = array_values($output);

        // return what we know
        return $output;
    }


    // taxonomies
    // ------------------------------------------------------------------------

    /**
     * Gets a list of taxonomy values by type
     *
     * @param string  $type  Taxonomy type to retrieve
     * @return TaxonomySet
     */
    public static function getTaxonomiesByType($type)
    {
        self::loadCache();
        $data = array();

        // taxonomy type doesn't exist, return empty
        if (!isset(self::$cache['taxonomies'][$type])) {
            return new TaxonomySet(array());
        }


        $url_root  = Config::getSiteRoot() . $type;
        $values    = self::$cache['taxonomies'][$type];
        $slugify   = Config::getTaxonomySlugify();

        // what we need
        // - name
        // - count of related content
        // - related
        foreach ($values as $key => $parts) {
            $set = array();

            $prepared_key = ($slugify) ? Slug::make($key) : rawurlencode($key);

            foreach ($parts['files'] as $url) {
                if (!isset(self::$cache['urls'][$url])) {
                    continue;
                }
                $set[$url] = self::$cache["content"][self::$cache['urls'][$url]['folder']][self::$cache['urls'][$url]['file']]['data'];
            }

            $data[$key] = array(
                'content' => new ContentSet($set),
                'name'    => $parts['name'],
                'url'     => $url_root . '/' . $prepared_key,
                'slug'    => $type . '/' . $prepared_key
            );
            $data[$key]['count'] = $data[$key]['content']->count();
        }

        return new TaxonomySet($data);
    }


    /**
     * Returns a taxonomy slug's name if stored in cache
     *
     * @param string  $taxonomy  Taxonomy to use
     * @param string  $taxonomy_slug  Taxonomy slug to use
     * @return mixed
     */
    public static function getTaxonomyName($taxonomy, $taxonomy_slug)
    {
        self::loadCache();

        if (Config::getTaxonomySlugify()) {
            $taxonomy_slug = Slug::humanize($taxonomy_slug);
        }

        if (!isset(self::$cache['taxonomies'][$taxonomy]) || !isset(self::$cache['taxonomies'][$taxonomy][$taxonomy_slug])) {
            return null;
        }

        return self::$cache['taxonomies'][$taxonomy][$taxonomy_slug]['name'];
    }


    // content
    // ------------------------------------------------------------------------

    /**
     * Gets cached content by URL
     *
     * @param string  $url  URL to use
     * @return ContentSet
     */
    public static function getContentByURL($url)
    {
        if (is_array($url)) {
            $content = array();
            foreach ($url as $single_url) {
                $content[] = ContentService::getContent($single_url);
            }
        } else {
            $content = ContentService::getContent($url);
            $content = (count($content)) ? array($content) : $content;
        }

        return new ContentSet($content);
    }


    /**
     * Gets cached content for pages for a certain taxonomy type and value
     *
     * @param string  $taxonomy  Taxonomy to use
     * @param string  $values  Values to match (single or array)
     * @param mixed  $folders  Optionally, folders to filter down by
     * @return ContentSet
     */
    public static function getContentByTaxonomyValue($taxonomy, $values, $folders=null)
    {
        self::loadCache();
        $case_sensitive = Config::getTaxonomyCaseSensitive();

        if ($folders) {
            $folders = Parse::pipeList($folders);
        }

        // if an array was sent
        if (is_array($values)) {
            $files = array();

            if (!$case_sensitive) {
                $values = array_map('strtolower', $values);
            }

            // loop through each of the values looking for files
            foreach ($values as $value) {
                if (!isset(self::$cache["taxonomies"][$taxonomy][$value])) {
                    continue;
                }

                // add these file names to the big file list
                $files = array_merge($files, self::$cache["taxonomies"][$taxonomy][$value]['files']);
            }

            // get unique list of files
            $files = array_unique($files);

            // if a single value was sent
        } else {
            if (!$case_sensitive) {
                $values = strtolower($values);
            }

            if (!isset(self::$cache["taxonomies"][$taxonomy][$values])) {
                $files = array();
            } else {
                $files = self::$cache["taxonomies"][$taxonomy][$values]['files'];
            }
        }

        // if no files, abort
        if (!count($files)) {
            return new ContentSet(array());
        }

        // still here? grab data from cache
        $data = array();
        foreach ($files as $file) {
            $data[] = ContentService::getContent($file);
        }

        // build a new ContentSet with the data we have
        $content_set = new ContentSet($data);

        // if there are folders to filter on, filter
        if ($folders) {
            $content_set->filter(array("folders" => $folders));
        }

        return $content_set;
    }


    /**
     * Gets cached content for pages from given folders
     *
     * @param array  $folders  Folders to grab from
     * @return ContentSet
     */
    public static function getContentByFolders($folders)
    {
        self::loadCache();
        
        $content = array();
        $folders = Parse::pipeList($folders);
        
        foreach ($folders as $folder) {
            foreach (self::$cache['content'] as $content_folder => $content_pages) {
                if (!Folder::matchesPattern($content_folder, $folder)) {
                    continue;
                }

                foreach ($content_pages as $data) {
                    $content[$data['path']] = $data['data'];
                }
            }
        }

        return new ContentSet($content);
    }
}