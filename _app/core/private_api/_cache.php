<?php
use Symfony\Component\Finder\Finder as Finder;

/**
 * _Cache
 * Private API for caching content
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @package     Private_API
 * @copyright   2013 Statamic
 */
class _Cache
{    
    /**
     * Updates the internal content cache
     *
     * @return boolean
     */
    public static function update()
    {
        // start measuring
        $content_hash = Debug::markStart('caching', 'content');

        // track if any files have changed
        $files_changed     = false;
        $settings_changed  = false;
        $members_changed   = false;

        // grab length of content type extension
        $content_type         = Config::getContentType();
        $full_content_root    = rtrim(Path::tidy(BASE_PATH . "/" . Config::getContentRoot()), "/");
        $content_type_length  = strlen($content_type) + 1;

        // the cache files we'll use
        $cache_file      = BASE_PATH . '/_cache/_app/content/content.php';
        $settings_file   = BASE_PATH . '/_cache/_app/content/settings.php';
        $structure_file  = BASE_PATH . '/_cache/_app/content/structure.php';
        $time_file       = BASE_PATH . '/_cache/_app/content/last.php';
        $members_file    = BASE_PATH . '/_cache/_app/members/members.php';
        $now             = time();

        // start measuring settings hash
        $settings_hash = Debug::markStart('caching', 'settings');

        // check for current and new settings
        $settings = unserialize(File::get($settings_file));
        if (!is_array($settings)) {
            $settings = array(
                'site_root' => '',
                'site_url'  => '',
                'timezone'  => '',
                'date_format' => '',
                'time_format' => '',
                'content_type' => '',
                'taxonomy'  => '',
                'taxonomy_case_sensitive' => '',
                'taxonomy_force_lowercase' => '',
                'entry_timestamps' => '',
                'base_path' => '',
                'app_version' => ''
            );
        }

        // look up current settings
        $current_settings = array(
            'site_root'                 => Config::getSiteRoot(),
            'site_url'                  => Config::getSiteURL(),
            'timezone'                  => Config::get('timezone'),
            'date_format'               => Config::get('date_format'),
            'time_format'               => Config::get('time_format'),
            'content_type'              => Config::get('content_type'),
            'taxonomy'                  => Config::getTaxonomies(),
            'taxonomy_case_sensitive'   => Config::getTaxonomyCaseSensitive(),
            'taxonomy_force_lowercase'  => Config::getTaxonomyForceLowercase(),
            'entry_timestamps'          => Config::getEntryTimestamps(),
            'base_path'                 => BASE_PATH,
            'app_version'               => STATAMIC_VERSION
        );

        // have cache-altering settings changed?        
        if ($settings !== $current_settings) {
            // settings have changed
            $settings_changed = true;

            // clear the cache and set current settings
            $cache     = self::getCleanCacheArray();
            $settings  = $current_settings;
            $last      = null;
        } else {
            // grab the existing cache
            $cache = unserialize(File::get($cache_file));
            if (!is_array($cache)) {
                $cache = self::getCleanCacheArray();
            }
            $last = File::get($time_file);
        }

        // mark end of settings hash measuring
        Debug::markEnd($settings_hash);

        // grab a list of all content files        
        $files = File::globRecursively(Path::tidy(BASE_PATH . '/' . Config::getContentRoot() . '/*'), Config::getContentType());

        // grab a separate list of files that have changed since last check
        $updated = array();
        $current_files = array();

        // loop through files, getting local paths and checking for updated files
        foreach ($files as $file) {
            $local_file = Path::trimFilesystemFromContent(Path::standardize($file));
            
            // add to current files
            $current_files[] = $local_file;
            
            // is this updated?
            if ($last && File::getLastModified($file) >= $last) {
                $updated[] = $local_file;
            }
        }

        // get a diff of files we know about and files currently existing
        $known_files = array();
        foreach ($cache['urls'] as $url_data) {
            array_push($known_files, $url_data['path']);
        }
        $new_files = array_diff($current_files, $known_files);

        // create a master list of files that need updating
        $changed_files = array_unique(array_merge($new_files, $updated));
        
        // store a list of changed URLs
        $changed_urls = array();

        // add to the cache if files have been updated
        if (count($changed_files)) {
            $files_changed = true;

            // build content cache
            foreach ($changed_files as $file) {
                $file           = $full_content_root . $file;
                $local_path     = Path::trimFilesystemFromContent($file);

                // before cleaning anything, check for hidden or draft content
                $is_hidden      = Path::isHidden($local_path);
                $is_draft       = Path::isDraft($local_path);

                // now clean up the path
                $local_filename = Path::clean($local_path);

                // file parsing
                $content       = substr(File::get($file), 3);
                $divide        = strpos($content, "\n---");
                $front_matter  = trim(substr($content, 0, $divide));
                $content_raw   = trim(substr($content, $divide + 4));

                // parse data
                $data = YAML::parse($front_matter);

                if ($content_raw) {
                    $data['content']      = 'true';
                    $data['content_raw']  = 'true';
                }

                // set additional information
                $data['_file']          = $file;
                $data['_local_path']    = $local_path;

                $data['_order_key']     = null;
                $data['datetimestamp']  = null;  // legacy
                $data['datestamp']      = null;
                $data['date']           = null;
                $data['time']           = null;
                $data['numeric']        = null;
                $data['last_modified']  = filemtime($file);
                $data['_is_hidden']     = $is_hidden;
                $data['_is_draft']      = $is_draft;

                // get initial slug (may be changed below)
                $data['slug'] = ltrim(basename($file, "." . $content_type), "_");

                // folder
                $instance = ($data['slug'] == 'page') ? 1 : 0;
                $data['_folder'] = Path::clean($data['_local_path']);
                $slash = Helper::strrpos_count($data['_folder'], '/', $instance);
                $data['_folder'] = (!$slash) ? '' : substr($data['_folder'], 1, $slash - 1);
                $data['_folder'] = (!strlen($data['_folder'])) ? "/" : $data['_folder'];

                $data['_basename'] = $data['slug'] . '.' . $content_type;
                $data['_filename'] = $data['slug'];
                $data['_is_entry'] = preg_match(Pattern::ENTRY_FILEPATH, $data['_basename']);
                $data['_is_page']  = preg_match(Pattern::PAGE_FILEPATH,  $data['_basename']);

                // 404 is special
                if ($data['_local_path'] === "/404.{$content_type}") {
                    $local_filename = $local_path;
                // order key: date or datetime         
                } elseif (preg_match(Pattern::DATE_OR_DATETIME, $data['_basename'], $matches)) {
                    // order key: date or datetime  
                    $date = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                    $time = null;

                    if (Config::getEntryTimestamps() && isset($matches[4])) {
                        $time = substr($matches[4], 0, 2) . ":" . substr($matches[4], 2);
                        $date = $date . " " . $time;

                        $data['slug']           = substr($data['slug'], 16);
                        $data['datetimestamp']  = $data['_order_key'];
                    } else {
                        $data['slug']           = substr($data['slug'], 11);
                    }

                    $data['_order_key'] = strtotime($date);
                    $data['datestamp']  = $data['_order_key'];
                    $data['date']       = Date::format(Config::getDateFormat(), $data['_order_key']);
                    $data['time']       = ($time) ? Date::format(Config::getTimeFormat(), $data['_order_key']) : null;

                // order key: slug is page, back up a level
                } elseif ($data['slug'] == 'page' && preg_match(Pattern::NUMERIC, substr($data['_local_path'], Helper::strrpos_count($data['_local_path'], '/', 1)), $matches)) {
                    // order key: slug is page, back up a level
                    $data['_order_key'] = $matches[1];
                    $data['numeric']    = $data['_order_key'];

                // order key: numeric
                } elseif (preg_match(Pattern::NUMERIC, $data['_basename'], $matches)) {
                    // order key: numeric
                    $data['_order_key'] = $matches[1];
                    $data['numeric']    = $data['_order_key'];
                    $data['slug']       = substr($data['slug'], strlen($matches[1]) + 1);

                // order key: other
                } else {
                    // order key: other
                    $data['_order_key'] = $data['_basename'];
                }

                // determine url
                $data['url'] = preg_replace('#/__?#', '/', $local_filename);

                // remove any content type extensions from the end of filename
                if (substr($data['url'], -$content_type_length) === '.' . $content_type) {
                    $data['url'] = substr($data['url'], 0, strlen($data['url']) - $content_type_length);
                }

                // remove any base pages from filename
                if (substr($data['url'], -5) == '/page') {
                    $data['url'] = substr($data['url'], 0, strlen($data['url']) - 5);
                }

                // add the site root
                $data['url'] = Path::tidy(Config::getSiteRoot() . $data['url']);

                // add the site URL to get the permalink
                $data['permalink'] = Path::tidy(Config::getSiteURL() . $data['url']);

                // new content
                if (!isset($cache['content'][$data['_folder']]) || !is_array($cache['content'][$data['_folder']])) {
                    $cache['content'][$data['_folder']] = array();
                }

                $slug_with_extension = ($data['_filename'] == 'page') ? substr($data['url'], strrpos($data['url'], '/') + 1) . '/' . $data['_filename'] . "." . $content_type : $data['_filename'] . "." . $content_type;
                $cache['content'][$data['_folder']][$slug_with_extension] = array(
                    'folder' => $data['_folder'],
                    'path'   => $local_path,
                    'file'   => $slug_with_extension,
                    'url'    => $data['url'],
                    'data'   => $data
                );

                $cache['urls'][$data['url']] = array(
                    'folder' => $data['_folder'],
                    'path'   => $local_path,
                    'file'   => $slug_with_extension
                );
                
                $changed_urls[$data['url']] = true;
            }
        }

        // loop through all cached content for deleted files
        // this isn't as expensive as you'd think in real-world situations
        foreach ($cache['content'] as $folder => $folder_contents) {
            foreach ($folder_contents as $path => $data) {
                if (File::exists($full_content_root . $data['path'])) {
                    // still here, keep it
                    continue;
                }

                $files_changed = true;

                // get URL
                $url = (isset($cache['content'][$folder][$path]['url'])) ? $cache['content'][$folder][$path]['url'] : null;
                
                // only remove from URLs list if not in changed URLs list
                if (!isset($changed_urls[$url]) && !is_null($url)) {
                    // remove from url cache
                    unset($cache['urls'][$url]);
                }

                // remove from content cache
                unset($cache['content'][$folder][$path]);
            }
        }

        // build taxonomy cache
        // only happens if files were added, updated, or deleted above
        if ($files_changed) {
            $taxonomies           = Config::getTaxonomies();
            $force_lowercase      = Config::getTaxonomyForceLowercase();
            $case_sensitive       = Config::getTaxonomyCaseSensitive();
            $cache['taxonomies']  = array();

            // rebuild taxonomies
            if (count($taxonomies)) {
                // set up taxonomy array
                foreach ($taxonomies as $taxonomy) {
                    $cache['taxonomies'][$taxonomy] = array();
                }

                // loop through content to build cached array
                foreach ($cache['content'] as $pages) {
                    foreach ($pages as $item) {
                        $data = $item['data'];

                        // loop through the types of taxonomies
                        foreach ($taxonomies as $taxonomy) {
                            // if this file contains this type of taxonomy
                            if (isset($data[$taxonomy])) {
                                $values = Helper::ensureArray($data[$taxonomy]);

                                // add the file name to the list of found files for a given taxonomy value
                                foreach ($values as $value) {
                                    if (!$value) {
                                        continue;
                                    }

                                    $key = (!$case_sensitive) ? strtolower($value) : $value;

                                    if (!isset($cache['taxonomies'][$taxonomy][$key])) {
                                        $cache['taxonomies'][$taxonomy][$key] = array(
                                            'name' => ($force_lowercase) ? strtolower($value) : $value,
                                            'files' => array()
                                        );
                                    }

                                    array_push($cache['taxonomies'][$taxonomy][$key]['files'], $data['url']);
                                }
                            }
                        }
                    }
                }
            }

            // build structure cache
            $structure = array();
            $home = Path::tidy('/' . Config::getSiteRoot() . '/');

            foreach ($cache['content'] as $pages) {
                foreach ($pages as $item) {
                    // set up base variables
                    $parent = null;

                    // Trim off home and any /page.md ending so that all URLs are treated
                    // equally regardless of page type.

                    $order_key = str_replace('/page.md', '', str_replace($home, '', $item['path']));

                    $sub_order_key = $item['data']['_order_key'];

                    // does this have a parent (and if so, what is it?)
                    if ($item['url'] !== $home) {
                        $parent = $home;
                        $depth = substr_count(str_replace($home, '/', $item['url']), '/');
                        $last_slash = strrpos($item['url'], '/', 1);
                        $last_order_slash = strrpos($order_key, '/', 0);

                        if ($last_slash !== false) {
                            $parent = substr($item['url'], 0, $last_slash);
                        }

                        if ($last_order_slash !== false) {
                            $order_key = substr($order_key, 0, $last_order_slash);
                        }

                        if ($item['data']['_is_page']) {
                            $type = ($item['data']['slug'] == 'page') ? 'folder' : 'page';
                        } else {
                            $type = 'entry';
                        }
                    } else {
                        $depth = 0;
                        $type = 'folder';
                        $order_key = $home;
                    }

                    $structure[$item['url']] = array(
                        'parent' => $parent,
                        'is_entry' => $item['data']['_is_entry'],
                        'is_page' => $item['data']['_is_page'],
                        'is_hidden' => $item['data']['_is_hidden'],
                        'is_draft' => $item['data']['_is_draft'],
                        'depth' => $depth,
                        'order_key' => ($order_key) ? $order_key : $sub_order_key,
                        'sub_order_key' => $sub_order_key,
                        'type' => $type
                    );
                }
            }
        }

        // mark ending of content cache measuring
        Debug::markEnd($content_hash);

        if (!Config::get('disable_member_cache')) {
            // build member cache
            // ----------------------------------------------------------------
    
            // start measuring
            $member_hash = Debug::markStart('caching', 'member');
    
            // grab a list of existing members
            $users = File::globRecursively(Path::tidy(Config::getConfigPath() . '/users/*'), 'yaml');
    
            // clone for reuse, set up our list of updated users
            $updated = array();
            $current_users = array();
            
            foreach ($users as $user) {
                $local_file = Path::trimFilesystemFromContent(Path::standardize($user));
                
                // add to current users
                $current_users[] = $local_file;
                
                // is this updated?
                if ($last && File::getLastModified($user) >= $last) {
                    $updated[] = $local_file;
                }
            }
            
            // get users from the file
            $members = unserialize(File::get($members_file));
    
            // get a diff of users we know about and files currently existing
            $known_users = array();
            if (!empty($members)) {
                foreach ($members as $username => $member_data) {
                    $known_users[$username] = $member_data['_path'];
                }
            }
    
            // create a master list of users that need updating
            $changed_users = array_unique(array_merge(array_diff($current_users, $known_users), $updated));
            $removed_users = array_diff($known_users, $current_users);
    
            if (count($changed_users)) {
                $members_changed = true;
    
                foreach ($changed_users as $user_file) {
                    // file parsing
                    $last_slash  = strrpos($user_file, '/') + 1;
                    $last_dot    = strrpos($user_file, '.');
                    $username    = substr($user_file, $last_slash, $last_dot - $last_slash);
                    $content     = substr(File::get($user_file), 3);
                    $divide      = strpos($content, "\n---");
                    $data        = YAML::parse(trim(substr($content, 0, $divide)));
                    $bio_raw     = trim(substr($content, $divide + 4));
    
                    $data['_path'] = $user_file;
    
                    if ($bio_raw) {
                        $data['biography'] = 'true';
                        $data['biography_raw'] = 'true';
                    }
    
                    $members[$username] = $data;
                }
            }
    
            // loop through all cached content for deleted files
            // this isn't as expensive as you'd think in real-world situations
            if (!empty($removed_users)) {
                $members_changed = true;
                $members = array_diff_key($members, $removed_users);
            }
    
            // mark ending of member cache measuring
            Debug::markEnd($member_hash);
        }



        // write to caches
        // --------------------------------------------------------------------

        // add file-writing to content-cache actions
        $content_hash = Debug::markStart('caching', 'content');

        if ($files_changed) {
            // store the content cache
            if (File::put($cache_file, serialize($cache)) === false) {
                if (!File::isWritable($cache_file)) {
                    Log::fatal('Cache folder is not writable.', 'core', 'content-cache');
                }

                Log::fatal('Could not write to the cache.', 'core', 'content-cache');
                return false;
            }

            // store the structure cache
            if (File::put($structure_file, serialize($structure)) === false) {
                if (!File::isWritable($structure_file)) {
                    Log::fatal('Structure cache file is not writable.', 'core', 'structure-cache');
                }

                Log::fatal('Could not write to the structure cache.', 'core', 'structure-cache');
                return false;
            }
        }

        // mark ending of content cache file write measuring
        Debug::markEnd($content_hash);

        // add file-writing to settings-cache actions
        $settings_hash = Debug::markStart('caching', 'settings');

        // store the settings cache
        if ($settings_changed) {
            if (File::put($settings_file, serialize($settings)) === false) {
                if (!File::isWritable($settings_file)) {
                    Log::fatal('Settings cache file is not writable.', 'core', 'settings-cache');
                }

                Log::fatal('Could not write to the settings cache file.', 'core', 'settings-cache');
                return false;
            }
        }

        // mark ending of settings cache file write measuring
        Debug::markEnd($settings_hash);

        if (!Config::get('disable_member_cache')) {
            // add file-writing to settings-cache actions
            $member_hash = Debug::markStart('caching', 'member');
    
            // store the members cache
            if ($members_changed) {
                if (File::put($members_file, serialize($members)) === false) {
                    if (!File::isWritable($members_file)) {
                        Log::fatal('Member cache file is not writable.', 'core', 'member-cache');
                    }
    
                    Log::fatal('Could not write to the member cache file.', 'core', 'member-cache');
                    return false;
                }
            }
    
            // mark ending of member cache file write measuring
            Debug::markEnd($member_hash);
        }

        File::put($time_file, $now - 1);
        return true;
    }


    /**
     * Grabs content for a given content file, caching it if necessary
     * 
     * @param array  $content_item  Content item as stored in the system cache
     * @return string
     */
    public static function retrieveContent($content_item)
    {
        $content_file  = (isset($content_item['_file'])) ? $content_item['_file'] : null;
        $content       = array('raw' => '', 'parsed' => '');
        
        // content file doesn't exist
        if (!$content_file || !File::exists($content_file)) {
            // return nothing
            return $content;
        }
        
        // make this
        $raw_file  = substr(File::get($content_file), 3);
        $divide    = strpos($raw_file, "\n---");

        $content['raw']     = trim(substr($raw_file, $divide + 4));
        $content['parsed']  = Content::parse($content['raw'], $content_item);
                    
        return $content;
    }
    


    /**
     * Dumps the current content of the content cache to the screen
     * 
     * @return void
     */
    public static function dump()
    {
        $cache_file = BASE_PATH . '/_cache/_app/content/content.php';
        rd(unserialize(File::get($cache_file)));
    }
    
    
    /**
     * Returns a clean cache array for filling
     * 
     * @return array
     */
    public static function getCleanCacheArray()
    {
        return array(
            'urls' => array(),
            'content' => array(),
            'taxonomies' => array(),
            'structure' => array()
        );
    }
}