<?php
class Plugin_nav extends Plugin
{
    /**
     * Creates a navigation structure based on pages from a given folder
     * @return bool|string
     */
    public function index()
    {
        // grab parameters
        $from             = $this->fetchParam('from', URL::getCurrent());
        $exclude          = $this->fetchParam('exclude', false);
        $max_depth        = $this->fetchParam('max_depth', 1, 'is_numeric');
        $include_entries  = $this->fetchParam('include_entries', false, false, true);
        $folders_only     = $this->fetchParam('folders_only', true, false, true);
        $include_content  = $this->fetchParam('include_content', true, false, true);
        $show_hidden      = $this->fetchParam('show_hidden', false, null, true);

        // add in left-/ if not present
        if (substr($from, 0, 1) !== '/') {
            $from = '/' . $from;
        }

        // if this doesn't start with the site root, add the site root
        if (!Pattern::startsWith($from, Config::getSiteRoot())) {
            $from = Path::tidy(Config::getSiteRoot() . '/' . $from);
        }

        // standardize excludes
        if ($exclude && !is_array($exclude)) {
            $exclude = Helper::explodeOptions($exclude, array());
            
            foreach ($exclude as $key => $value) {
                $exclude[$key] = Path::tidy(Config::getSiteRoot() . '/' . $value);
            }
        }

        // option hash
        $hash = Helper::makeHash($from, $exclude, $max_depth, $include_entries, $folders_only, $include_content, $show_hidden);

        // load the content tree from cache
        if ($this->blink->exists($hash)) {
            $tree = $this->blink->get($hash);
        } else {
            $tree = ContentService::getContentTree($from, $max_depth, $folders_only, $include_entries, $show_hidden, $include_content, $exclude);
            $this->blink->set($hash, $tree);
        }

        // return the parsed tree
        if (count($tree)) {
            return Parse::tagLoop($this->content, $tree, true);
        }

        return false;
    }


    public function exists()
    {        
        // grab parameters
        $from             = $this->fetchParam('from', URL::getCurrent());
        $exclude          = $this->fetchParam('exclude', false);
        $max_depth        = $this->fetchParam('max_depth', 1, 'is_numeric');
        $include_entries  = $this->fetchParam('include_entries', false, false, true);
        $folders_only     = $this->fetchParam('folders_only', true, false, true);
        $include_content  = $this->fetchParam('include_content', false, false, true);
        $show_hidden      = $this->fetchParam('show_hidden', false, null, true);

        // add in left-/ if not present
        if (substr($from, 0, 1) !== '/') {
            $from = '/' . $from;
        }

        // if this doesn't start with the site root, add the site root
        if (!Pattern::startsWith($from, Config::getSiteRoot())) {
            $from = Path::tidy(Config::getSiteRoot() . '/' . $from);
        }

        // standardize excludes
        if ($exclude && !is_array($exclude)) {
            $exclude = Helper::explodeOptions($exclude, array());

            foreach ($exclude as $key => $value) {
                $exclude[$key] = Path::tidy(Config::getSiteRoot() . '/' . $value);
            }
        }

        // option hash
        $hash = Helper::makeHash($from, $exclude, $max_depth, $include_entries, $folders_only, $include_content, $show_hidden);

        // load the content tree from cache
        if ($this->blink->exists($hash)) {
            $tree = $this->blink->get($hash);
        } else {
            $tree = ContentService::getContentTree($from, $max_depth, $folders_only, $include_entries, $show_hidden, $include_content, $exclude);
            $this->blink->set($hash, $tree);
        }

        // return the parsed tree
        if (count($tree)) {
            return Parse::template($this->content, array());
        }

        return false;
    }


    public function breadcrumbs()
    {
        $url              = $this->fetchParam('from', URL::getCurrent());
        $include_home     = $this->fetchParam('include_home', true, false, true);
        $reverse          = $this->fetchParam('reverse', false, false, true);
        $backspace        = $this->fetchParam('backspace', false, 'is_numeric', false);
        $include_content  = $this->fetchParam('include_content', false, null, true);
        $trim             = $this->fetchParam('trim', true, null, true);
        $tag_content      = $trim ? trim($this->content) : $this->content;

        // add in left-/ if not present
        if (substr($url, 0, 1) !== '/') {
            $url = '/' . $url;
        }

        // if this doesn't start with the site root, add the site root
        if (!Pattern::startsWith($url, Config::getSiteRoot())) {
            $url = Path::tidy(Config::getSiteRoot(), $url);
        }

        // start crumbs
        $crumbs = array();

        // we only want to show breadcrumbs when we're not on the homepage
        if ($url !== Config::getSiteRoot()) {
            $segments = explode('/', ltrim(str_replace(Config::getSiteRoot(), '/', $url), '/'));
            $segment_count = count($segments);
            $segment_urls = array();

            // create crumbs from segments
            for ($i = 1; $i <= $segment_count; $i++) {
                $segment_urls[] = '/' . join($segments, '/');
                array_pop($segments);
            }

            // should we also include the homepage?
            if ($include_home) {
                $segment_urls[] = Config::getSiteRoot();
            }

            // grab data for each
            foreach ($segment_urls as $url) {
                $this_url = Path::tidy(Config::getSiteRoot() . '/' . $url);
                $content = Content::get($this_url, $include_content);

                if ($content) {
                    // standard stuff
                    $crumbs[$this_url] = $content;
                } else {
                    // no content found? this could be a taxonomy
                    $crumbs[$this_url] = array(
                        'url' => $this_url,
                        'title' => ucwords(substr($url, strrpos($url, '/') + 1))
                    );
                }

                $crumbs[$this_url]['is_current'] = (URL::getCurrent() == $this_url);
            }

            // correct order
            if (!$reverse) {
                $crumbs = array_reverse($crumbs);
            }

            // add first, last, and index
            $i = 1;
            $crumb_count = count($crumbs);
            foreach ($crumbs as $key => $crumb) {
                $crumbs[$key]['first']  = ($i === 1);
                $crumbs[$key]['last']   = ($i === $crumb_count);
                $crumbs[$key]['index']  = $i;

                $i++;
            }

            if (!count($crumbs)) {
                $output = Parse::template($tag_content, array('no_results' => true));

                if ($backspace) {
                    $output = substr($output, 0, -$backspace);
                }
            } else {
                $output = Parse::tagLoop($tag_content, $crumbs);

                if ($backspace) {
                    $output = substr($output, 0, -$backspace);
                }
            }
        } else {
            $output = Parse::template($tag_content, array('no_results' => true));
        }

        // parse the loop
        return $output;
    }


    public function count()
    {
        // grab parameters
        $from             = $this->fetchParam('from', URL::getCurrent());
        $exclude          = $this->fetchParam('exclude', false);
        $max_depth        = $this->fetchParam('max_depth', 1, 'is_numeric');
        $include_entries  = $this->fetchParam('include_entries', false, false, true);
        $folders_only     = $this->fetchParam('folders_only', true, false, true);
        $include_content  = $this->fetchParam('include_content', false, false, true);
        $show_hidden      = $this->fetchParam('show_hidden', false, null, true);

        // add in left-/ if not present
        if (substr($from, 0, 1) !== '/') {
            $from = '/' . $from;
        }

        // if this doesn't start with the site root, add the site root
        if (!Pattern::startsWith($from, Config::getSiteRoot())) {
            $from = Path::tidy(Config::getSiteRoot() . '/' . $from);
        }

        // standardize excludes
        if ($exclude && !is_array($exclude)) {
            $exclude = Helper::explodeOptions($exclude, array());

            foreach ($exclude as $key => $value) {
                $exclude[$key] = Path::tidy(Config::getSiteRoot() . '/' . $value);
            }
        }

        // option hash
        $hash = Helper::makeHash($from, $exclude, $max_depth, $include_entries, $folders_only, $include_content, $show_hidden);

        // load the content tree from cache
        if ($this->blink->exists($hash)) {
            $tree = $this->blink->get($hash);
        } else {
            $tree = ContentService::getContentTree($from, $max_depth, $folders_only, $include_entries, $show_hidden, $include_content, $exclude);
            $this->blink->set($hash, $tree);
        }

        if ($this->content) {
            return Parse::template($this->content, array('count' => count($tree)));
        } elseif (count($tree)) {
            return count($tree);
        }

        return '';
    }
}