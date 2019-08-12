<?php
class Plugin_theme extends Plugin
{
    public function __construct()
    {
        parent::__construct();

        $this->theme_assets_path = Config::getThemeAssetsPath();
        $this->theme_path        = Config::getCurrentthemePath();
        $this->theme_root        = Config::getTemplatesPath();
        $this->site_root         = Config::getSiteRoot();
    }

    # Usage example: {{ theme:partial src="sidebar" }}
    public function partial()
    {        
        $start      = time();
        $src        = $this->fetchParam('src', null, null, false, false);
        $extensions = array(".html", ".md", ".markdown", ".textile");
        $html       = null;

        // measurement
        $hash = Debug::markStart('partials', $src, $start);
        Debug::increment('partials', $src);
        
        if ($src) {
            foreach ($extensions as $extension) {
                $full_src = Path::assemble(BASE_PATH, $this->theme_root, 'partials', ltrim($src . $extension, '/'));

                if (File::exists($full_src)) {
                    Statamic_View::$_dataStore = $this->attributes + Statamic_View::$_dataStore;

                    if ($this->fetchParam('use_context', false, false, true, false)) {
                        // to use context, we only want to pass the attributes as
                        // the current data, as those will override into the context
                        // set of data; if we were to include all of ::$_dataStore, 
                        // we run into the issue where all of the global-level variables
                        // are overriding variables in context, when the variables in
                        // context are more accurate scope-wise at this point in the parse
                        $html = Parse::contextualTemplate(file_get_contents($full_src), $this->attributes, $this->context, array('statamic_view', 'callback'), true);
                    } else {
                        $html = Parse::template(file_get_contents($full_src), Statamic_View::$_dataStore);
                    }

                    // parse contents if needed
                    if ($extension == ".md" || $extension == ".markdown") {
                        $html = Parse::markdown($html);
                    } elseif ($extension == ".textile") {
                        $html = Parse::textile($html);
                    }
                }
            }

            if (Config::get('enable_smartypants', TRUE)) {
                $html = Parse::smartypants($html);
            }
        }
        
        Debug::markEnd($hash);

        return $html;
    }

    # Usage example: {{ theme:asset src="file.ext" }}
    public function asset()
    {
        $src  = $this->fetchParam('src', Config::getTheme() . '.js', null, false, false);
        $file = $this->theme_path . $this->theme_assets_path . $src;

        return URL::assemble($this->site_root, $file);
    }

    # Usage example: {{ theme:js src="jquery" }}
    public function js()
    {
        $src        = $this->fetchParam('src', Config::getTheme() . '.js', null, false, false);
        $file       = $this->theme_path . $this->theme_assets_path . 'js/' . $src;
        $cache_bust = $this->fetchParam('cache_bust', Config::get('theme_cache_bust', false), false, true, true);
        $tag        = $this->fetchParam('tag', false, null, true, false);
        $extension  = $this->fetchParam('extension', true, null, true, false);

        # Add '.js' to the end if not present.
        if (!preg_match("(\.js)", $file)) {
            $file .= '.js';
        }

        if ($cache_bust && File::exists($file)) {
            $file .= '?v=' . $last_modified = filemtime($file);
        }
        
        $filename = URL::assemble($this->site_root, $file);

        if (!$extension) {
            $filename = substr($filename, 0, strrpos($filename, '.'));
        }

        return ($tag) ? '<script src="' . $filename . '"></script>' : $filename;
    }

    # Usage example: {{ theme:css src="primary" }}
    public function css()
    {
        $src        = $this->fetchParam('src', Config::getTheme() . '.css', null, false, false);
        $file       = $this->theme_path . $this->theme_assets_path . 'css/' . $src;
        $cache_bust = $this->fetchParam('cache_bust', Config::get('theme_cache_bust', false), false, true, true);
        $tag        = $this->fetchParam('tag', false, null, true, false);
        $extension  = $this->fetchParam('extension', true, null, true, false);

        # Add '.css' to the end if not present.
        if (!preg_match("(\.css)", $file)) {
            $file .= '.css';
        }

        // Add cache busting query string
        if ($cache_bust && File::exists($file)) {
            $file .= '?v=' . $last_modified = filemtime($file);
        }
        
        $filename = URL::assemble($this->site_root, $file);
        
        if (!$extension) {
            $filename = substr($filename, 0, strrpos($filename, '.'));
        }

        return ($tag) ? '<link href="' . $filename . '" rel="stylesheet">' : $filename;
    }

    # Usage example: {{ theme:img src="logo.png" }}
    public function img()
    {
        $src        = $this->fetchParam('src', null, null, false, false);
        $file       = $this->theme_path . $this->theme_assets_path . 'img/' . $src;
        $alt        = $this->fetchParam('alt', null, null, false, false);
        $tag        = $this->fetchParam('tag', false, null, true, false);
        $cache_bust = $this->fetchParam('cache_bust', Config::get('theme_cache_bust', false), false, true, true);
        $extension  = $this->fetchParam('extension', true, null, true, false);

        if ($alt) {
            $alt = ' alt="' . $alt . '"';
        }

        if ($cache_bust && File::exists($file)) {
            $file .= '?v=' . $last_modified = filemtime($file);
        }
        
        $filename = URL::assemble($this->site_root, $file);

        if (!$extension) {
            $filename = substr($filename, 0, strrpos($filename, '.'));
        }

        return ($tag) ? '<img src="' . $filename . '" ' . $alt . '>' : $filename;
    }
}
