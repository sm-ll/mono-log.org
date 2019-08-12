<?php

/////////////////////////////////////////////////////////////////////////////////////////////////
// ROUTING HOOKS
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->map('/TRIGGER/:namespace/:hook(/:segments+)', function ($namespace, $hook, $segments = array()) use ($app) {

    // process uploaded files
    if ($app->request()->isPost()) {
        $_FILES = _Upload::standardizeFileUploads($_FILES);
    }
    
    /*
    |--------------------------------------------------------------------------
    | Hook: Routes Before
    |--------------------------------------------------------------------------
    |
    | Useful for running your own route. Remember to use $app->pass() if
    | you're not doing anything with the current request.
    |
    */
    Hook::run('_routes', 'before');

    Hook::run($namespace, $hook, null, null, $segments);

})->via('GET', 'POST', 'HEAD');



/////////////////////////////////////////////////////////////////////////////////////////////////
// Static Asset Pipeline (for development only!)
/////////////////////////////////////////////////////////////////////////////////////////////////

if (Config::get('enable_static_pipeline', true)) {

    $app->get('/assets/(:segments+)', function($segments = array()) use ($app) {

        // clean segments
        $segments = URL::sanitize($segments);

        /*
        |--------------------------------------------------------------------------
        | Hook: Routes Before
        |--------------------------------------------------------------------------
        |
        | Useful for running your own route. Remember to use $app->pass() if
        | you're not doing anything with the current request.
        |
        */
        Hook::run('_routes', 'before');

        $file_requested = implode($segments, '/');
        $file = Theme::getPath() . $file_requested;

        $file = realpath($file);

        # Routes only if the file doesn't already exist (e.g. /assets/whatever.ext)
        if ( ! File::exists(array($file_requested, $file))) {

            Log::warn("The Static Asset Pipeline is deprecated. It may yet come back to fight another battle someday.", "core", "asset pipeline");

            $mime = File::resolveMime($file);

            header("Content-type: {$mime}");
            readfile($file);

            exit();

        } else {

            // Moving on. Not a valid asset.
            $app->pass();
        }

    });

}


/////////////////////////////////////////////////////////////////////////////////////////////////
// Bundle Asset Pipeline
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->get('/_add-ons/(:segments+)', function($segments = array()) use ($app) {

    // reset any content service caching that's been done
    ContentService::resetCaches();

    // clean segments
    $segments = URL::sanitize($segments);
    $file_requested = implode($segments, '/');
    
    $bundle_folder  = APP_PATH . "/core/bundles/" . $segments[0];
    $file = APP_PATH . "/core/bundles/" . $file_requested;

    $file = realpath($file);
    
    // prevent bad access of files
    if (strpos($file_requested, '../') !== false || File::getExtension($file) === 'php') {
        $app->pass();
        return;
    }

    if (Folder::exists($bundle_folder) && File::exists($file)) {
        // determine mime type
        $mime = File::resolveMime($file);

        // set last modified header
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

        // if configured, set expires header
        if (Config::get('http_cache_expires', false)) {
            header("Expires: " . gmdate("D, d M Y H:i:s", strtotime('+' . Config::get('http_cache_expires', '30 minutes'))) . " GMT");
        }

        // set mime-type
        header("Content-type: {$mime}");
        
        // read it out
        readfile($file);

        exit();
    }

});


/////////////////////////////////////////////////////////////////////////////////////////////////
// GLOBAL STATAMIC CONTENT ROUTING
/////////////////////////////////////////////////////////////////////////////////////////////////

$app->map('/(:segments+)', function ($segments = array()) use ($app) {

    // mark milestone for debug panel
    Debug::markMilestone('routes started');

    // process uploaded files
    if ($app->request()->isPost()) {
        $_FILES = _Upload::standardizeFileUploads($_FILES);
    }
    
    global $is_debuggable_route;
    $is_debuggable_route = true;

    /*
    |--------------------------------------------------------------------------
    | Hook: Request Post
    |--------------------------------------------------------------------------
    |
    | Do a thing with a POST request. Go ahead. Do it.
    |
    */
    if ($app->request()->isPost()) {
        Hook::run('request', 'post');
    }

    // clean segments
    $segments = URL::sanitize($segments);


    /*
    |--------------------------------------------------------------------------
    | Hook: Routes Before
    |--------------------------------------------------------------------------
    |
    | Useful for running your own route. Remember to use $app->pass() if
    | you're not doing anything with the current request.
    |
    */
    Hook::run('_routes', 'before');

    $requesting_xml = false;
    $content_found  = false;

    // segments
    foreach ($segments as $key => $seg) {
        $count                            = $key + 1;
        $app->config['segment_' . $count] = $seg;  // segments are already sanitized
    }
    $app->config['last_segment'] = end($segments);
    
    // mark milestone for debug panel
    Debug::markMilestone('segments determined');

    /*
    |--------------------------------------------------------------------------
    | Routes: Ignore Segment
    |--------------------------------------------------------------------------
    |
    | Globally ignore a specific URL segment. For example, "success".
    |
    */
    if (isset($app->config['_routes']['ignore']) && is_array($app->config['_routes']['ignore']) && count($app->config['_routes']['ignore']) > 0) {
        $ignore = $app->config['_routes']['ignore'];

        $remove_segments = array_intersect($ignore, $segments);
        $segments = array_diff($segments, $remove_segments);
    }

    /*
    |--------------------------------------------------------------------------
    | Routes: Ignore AFTER a Segment
    |--------------------------------------------------------------------------
    |
    | Globally ignore all URL segments after a specified one. For example,
    | "search" could let you use additional segments as match conditions.
    |
    */

    $ignore_after = array_get($app->config, '_routes:ignore_after', false);
    if ($ignore_after) {
        if ( ! is_array($ignore_after)) {
            $ignore_after = array($ignore_after);
        }

        foreach ($ignore_after as $segment) {
            $position = array_search($segment, $segments);

            if ($position !== false) {
                array_splice($segments, $position + 1);
            }
        }
    }

    // determine paths
    $path = '/' . implode($segments, '/');

    // let XML files through
    if (substr($path, -4) == '.xml') {
        $path = substr($path, 0, -4);
        $requesting_xml = true;
    }

    $current_url  = $path;
    $complete_current_url  = Path::tidy(Config::getSiteRoot() . "/" . $current_url);

    // allow mod_rewrite for .html file extensions
    if (substr($path, -5) == '.html') {
        $path = str_replace('.html', '', $path);
    }

    $app->config['current_path'] = $path;

    // init some variables for below
    $content_root  = Config::getContentRoot();
    $content_type  = Config::getContentType();
    $response_code = 200;
    $visible       = true;
    $add_prev_next = false;

    $template_list = array('default');

    // set up the app based on if a
    if (File::exists("{$content_root}/{$path}.{$content_type}") || Folder::exists("{$content_root}/{$path}")) {
        // endpoint or folder exists!
    } else {
//        $path                        = Path::resolve($path);
        $app->config['current_url']  = $app->config['current_path'];
//        $app->config['current_path'] = $path; # override global current_path
    }

    // check for routes
    // allows the route file to run without "route:" as the top level array key (backwards compatibility)
    $found_route        = null;
    $routes             = array_get($app->config, '_routes:routes', array_get($app->config, '_routes'));
    $parsed_route_data  = array();

    // look for matching routes
    if (is_array($routes)) {
        foreach ($routes as $route_url => $route_data) {
            // check for standard wildcards
            if (preg_match('#^' . str_replace(array('.', '*'), array('\.', '.*?'), $route_url) . '$#i', $current_url, $matches)) {
                // found a route, save it and get out
                $found_route = array(
                    'url' => $route_url,
                    'data' => $route_data
                );
                break;
                
            // check for named wildcards
            } elseif (strpos($route_url, '{') !== false) {
                // get segment names
                preg_match_all('/{\s*([a-zA-Z0-9_\-]+)\s*}/', $route_url, $matches);
                
                // nothing found, skip it
                if (!count($matches)) {
                    continue;
                }
                
                // these are the keys we're looking for
                $named_keys   = $matches[1];
                
                // create regex out of the route
                $fixed_route  = preg_replace('/{\s*[a-zA-Z0-9_\-]+\s*}/', '([^/]*)', str_replace(array('.', '*'), array('\.', '[^\/]*'), $route_url));
                
                // make me a match
                if (preg_match('#^' . $fixed_route . '$#i', $current_url, $new_matches)) {
                    // shift off the first item
                    array_shift($new_matches);
                    
                    // merge values with keys, these will be merged in later
                    $parsed_route_data = URL::sanitize(array_combine($named_keys, $new_matches));
                    
                    // found a route, save it and get ou
                    $found_route = array(
                        'url' => $route_url,
                        'data' => $route_data
                    );
                    break;
                }
            }
        }
    }

    // mark milestone for debug panel
    Debug::markMilestone('routes determined');

    
    // routes via routes.yaml
    if ($found_route) {
        $current_route = $found_route['data'];

        $route     = $current_route;
        $template  = $route;
        $data      = $parsed_route_data + Content::get($complete_current_url) + $app->config;

        if (is_array($route)) {
            $template = array_get($route, 'template', 'default');

            if (isset($route['layout'])) {
                $data['_layout'] = $route['layout'];
            }

            // merge extra vars into data
            $data = $route + $data;
        }

        $template_list = array($template);
        $content_found = true;

    // URL found in the cache
    } elseif ($data = Content::get($complete_current_url)) {
        $add_prev_next   = true;
        $page            = basename($path);

        $data['current_url'] = $current_url;
        $data['slug']        = basename($current_url);

        // if this is an entry, default to the `post` template
        if ($data['_is_entry']) {
            $template_list[] = array_get($data, '_template', 'default');
            $template_list[] = "post";
        }

        if ($path !== "/404") {
            $content_found = true;
        }

    // url is taxonomy-based
    } elseif (Taxonomy::isTaxonomyURL($path)) {
        $taxonomy = Taxonomy::getCriteria($path);

        // create data array
        $data = array_merge(Config::getAll(), array(
            'homepage'       => Config::getSiteRoot(),
            'raw_url'        => Request::getResourceURI(),
            'page_url'       => Request::getResourceURI(),
            'taxonomy_slug'  => $taxonomy['slug'],
            'taxonomy_name'  => Taxonomy::getTaxonomyName($taxonomy['type'], $taxonomy['slug'])
        ));

        $template_list[] = "taxonomies";
        $template_list[] = $taxonomy['type'];

        if ( ! $taxonomy['slug']) {
            $template_list[] = "taxonomy-index";
            $template_list[] = $taxonomy['type'] . "-index";
        }

        $content_found = true;
    }

    
    // content was found
    if ($content_found) {
        // mark milestone for debug panel
        Debug::markMilestone('content found');
        
        // protect
        if (is_array($data) && $data) {
            try {
                Addon::getAPI('protect')->hasAccess(URL::getCurrent());
            } catch (Slim\Exception\Stop $e) {
                throw $e;
            } catch (Exception $e) {
                // something went wrong with protect, 404 this
                Log::error('The following error occurred while trying to protect `' . htmlspecialchars($data['current_url']) . '`: ' . $e->getMessage() . ' — for extra precaution, we sent this use the 404 page.', 'core', 'protect');
                $content_found = false;
                $response_code = 404;
            }
        }

        // alter the response code if you want
        $response_code = (int) array_get($data, '_response', $response_code);
        
        // if the response_code was set to 404, show a 404
        if ($response_code === 404) {
            $content_found = false;
        }
    }

    // Nothing found. 404 O'Clock.
    if (!$content_found || ($requesting_xml && (!isset($data['_type']) || $data['_type'] != 'xml'))) {
        // determine where user came from for log message
        if (strstr($path, 'favicon.ico')) {
            // Favicons are annoying.
            Log::info("The site favicon could not be found.", "site", "favicon");
        } else {
            if (isset($_SERVER['HTTP_REFERER'])) {
                $url_parts = parse_url($_SERVER['HTTP_REFERER']);

                // get local referrer
                $local_referrer = $url_parts['path'];
                $local_referrer .= (isset($url_parts['query']) && $url_parts['query']) ? '?' . $url_parts['query'] : '';
                $local_referrer .= (isset($url_parts['fragment']) && $url_parts['fragment']) ? '#' . $url_parts['fragment'] : '';

                if (strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
                    // the call came from inside the house!
                    $more   = 'There is a bad link on <a href="' . $local_referrer . '">' . $local_referrer . '</a>.';
                    $aspect = 'page';
                } else {
                    // external site linked to here
                    $more   = 'User clicked an outside bad link at <a href="' . $_SERVER['HTTP_REFERER'] . '">' . $_SERVER['HTTP_REFERER'] . '</a>.';
                    $aspect = 'external';
                }
            } else {
                // user typing error
                $more   = 'Visitor came directly to this page and may have typed the URL incorrectly.';
                $aspect = 'visitor';
            }

            Log::error("404 - Page not found. " . $more, $aspect, "content");
        }

        $data          = Content::get(Path::tidy(Config::getSiteRoot() . "/404"));
        $template_list = array('404');
        $response_code = 404;
    }

    # We now have all the YAML content
    # Let's process action fields

    # Redirect
    if (isset($data['_redirect'])) {
        $response_code = 302;

        if (is_array($data['_redirect'])) {
            $url = isset($data['_redirect']['to']) ? $data['_redirect']['to'] : false;

            if (!$url) {
                $url = isset($data['_redirect']['url']) ? $data['_redirect']['url'] : false; #support url key as alt
            }

            $response_code = isset($data['_redirect']['response']) ? $data['_redirect']['response'] : $response_code;
        } else {
            $url = $data['_redirect'];
        }

        if ($url) {
            $app->redirect($url, $response_code);
        }
    }

    // status
    if (isset($data['_is_draft']) && $data['_is_draft'] && !$app->config['logged_in']) {
        $data          = Content::get(Path::tidy(Config::getSiteRoot() . "/404"));
        $template_list = array('404');
        $visible       = false;
        $response_code = 404;

    // legacy status
    } elseif (isset($data['status']) && $data['status'] != 'live' && $data['status'] != 'hidden' && !$app->config['logged_in']) {
        $data          = Content::get(Path::tidy(Config::getSiteRoot() . "/404"));
        $template_list = array('404');
        $visible       = false;
        $response_code = 404;
    }

    // mark milestone for debug panel
    Debug::markMilestone('status determined');

    // find next/previous
    if ($add_prev_next && $visible) {
        $folder = substr(preg_replace(Pattern::ORDER_KEY, "", substr($path, 0, (-1*strlen($page))-1)), 1);

        $relative     = Statamic::find_relative($current_url, $folder);
        $data['prev'] = $relative['prev'];
        $data['next'] = $relative['next'];
    }

    // grab data for this folder
    $folder_data = Content::get(Path::tidy('/' . Config::getSiteRoot() . '/' . dirname($current_url)));

    $fields_data = YAML::parseFile(Path::tidy(BASE_PATH . "/" . Config::getContentRoot() . dirname($current_url) . '/fields.yaml'));

    // Check for fallback template
    if ($content_found && empty($data['_template'])) {
        // check fields.yaml first
        if (array_get($fields_data, '_default_folder_template')) {
            $data['_template'] = $fields_data['_default_folder_template'];
        // fall back to the folder's page.md file
        } elseif (array_get($folder_data, '_default_folder_template')) {
            $data['_template'] = $folder_data['_default_folder_template'];
        }
    }

    // set template and layout
    if (isset($data['_template'])) {
        $template_list[] = $data['_template'];
    }

    // mark milestone for debug panel
    Debug::markMilestone('template picked');

    // Check for fallback layout
    if ($content_found && empty($data['_layout'])) {
        // check fields.yaml first
        if (array_get($fields_data, '_default_folder_layout')) {
            $data['_layout'] = $fields_data['_default_folder_layout'];
        // fall back to the folder's page.md file
        } elseif (array_get($folder_data, '_default_folder_layout')) {
            $data['_layout'] = $folder_data['_default_folder_layout'];
        }
    }

    if (isset($data['_layout'])) {
        Statamic_View::set_layout("layouts/{$data['_layout']}");
    }

    // mark milestone for debug panel
    Debug::markMilestone('layout picked');

    // set up the view
    Statamic_View::set_templates(array_reverse($template_list));

    // set type, allows for RSS feeds
    if (isset($data['_type'])) {
        if ($data['_type'] == 'rss' || $data['_type'] == 'xml') {
            $data['_xml_header']      = '<?xml version="1.0" encoding="utf-8" ?>';
            $response                 = $app->response();
            $response['Content-Type'] = 'application/xml';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hook: Render Before
    |--------------------------------------------------------------------------
    |
    | Allows actions to occur before the template is rendered and parsed.
    | For example, pre-process a POST or set global variables dynamically.
    |
    */

    Hook::run('_render', 'before');

    /*
    |--------------------------------------------------------------------------
    | HTTP Caching
    |--------------------------------------------------------------------------
    |
    | We'll always set the last modified header, but leave the
    | cache_expires option to people's discretion and configuration.
    |
    */

    if (array_get($data, '_http_cache_expires', Config::get('http_cache_expires', false))) {
        $app->lastModified(Cache::getLastCacheUpdate());
        $app->expires('+'.Config::get('http_cache_expires', '30 minutes'));
    }
    
    // append the response code
    $data['_http_status']  = $response_code;
    $data['_response']     = $response_code;

    // and go!
    $app->render(null, $data, $response_code);

    // mark milestone for debug panel
    Debug::markMilestone('page ready');

    $app->halt($response_code, ob_get_clean());

})->via('GET', 'POST', 'HEAD');


// a second route that captures all routes, but will always return the 404 page
$app->map('/(:segments+)', function ($segments = array()) use ($app) {
    global $is_debuggable_route;
    $is_debuggable_route = true;

    // clean segments
    $segments = URL::sanitize($segments);

    // segments
    foreach ($segments as $key => $seg) {
        $count                            = $key + 1;
        $app->config['segment_' . $count] = $seg;
    }
    $app->config['last_segment'] = end($segments);

    $path = '/404';

    $app->config['current_path'] = $path;

    // init some variables for below
    $app->config['current_url']  = $app->config['current_path'];
    $app->config['current_path'] = $path; # override global current_path

    // Nothing found. 404 O'Clock.
    // determine where user came from for log message
    if (isset($_SERVER['HTTP_REFERER'])) {
        $url_parts = parse_url($_SERVER['HTTP_REFERER']);

        // get local referrer
        $local_referrer = $url_parts['path'];
        $local_referrer .= (isset($url_parts['query']) && $url_parts['query']) ? '?' . $url_parts['query'] : '';
        $local_referrer .= (isset($url_parts['fragment']) && $url_parts['fragment']) ? '#' . $url_parts['fragment'] : '';

        if (strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
            // the call came from inside the house!
            $more   = 'There is a bad link on <a href="' . $local_referrer . '">' . $local_referrer . '</a>.';
            $aspect = 'page';
        } else {
            // external site linked to here
            $more   = 'User clicked an outside bad link at <a href="' . $_SERVER['HTTP_REFERER'] . '">' . $_SERVER['HTTP_REFERER'] . '</a>.';
            $aspect = 'external';
        }
    } else {
        // user typing error
        $more   = 'Visitor came directly to this page and may have typed the URL incorrectly.';
        $aspect = 'visitor';
    }

    Log::error("404 - Page not found. " . $more, $aspect, "content");

    $data          = Content::get(Path::tidy(Config::getSiteRoot() . "/404"));
    $template_list = array('404');
    $response_code = 404;

    // set template and layout
    if (isset($data['_template'])) {
        $template_list[] = $data['_template'];
    }

    if (isset($data['_layout'])) {
        Statamic_View::set_layout("layouts/{$data['_layout']}");
    }

    // set up the view
    Statamic_View::set_templates(array_reverse($template_list));

    /*
    |--------------------------------------------------------------------------
    | HTTP Caching
    |--------------------------------------------------------------------------
    |
    | We'll always set the last modified header, but leave the
    | cache_expires option to people's discretion and configuration.
    |
    */

    if (array_get($data, '_http_cache_expires', Config::get('http_cache_expires', false))) {
        $app->lastModified(Cache::getLastCacheUpdate());
        $app->expires('+'.Config::get('http_cache_expires', '30 minutes'));
    }

    // append the response code
    $data['_http_status']  = $response_code;
    $data['_response']     = $response_code;

    // and go!
    $app->render(null, $data, $response_code);

    // mark milestone for debug panel
    Debug::markMilestone('render end');

    $app->halt($response_code, ob_get_clean());

})->via('GET', 'POST', 'HEAD');
