<?php
/**
 * Plugin_pages
 * Display lists of entries
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 *
 * @copyright  2012-2014
 * @link       http://statamic.com/
 * @license    http://statamic.com/license-agreement
 */
class Plugin_pages extends Plugin
{
    /**
     * Combines numeric values of all pages found into one combined result
     *
     * @return string
     */
    public function meld()
    {
        // grab common parameters
        $settings = $this->parseCommonParameters();

        // grab extra parameters
        $field      = $this->fetchParam('field', null);
        $action     = $this->fetchParam('action', 'sum');
        $precision  = $this->fetchParam('precision', null);

        // grab content set based on the common parameters
        $content_set = $this->getContentSet($settings);

        // limit
        $limit     = $this->fetchParam('limit', null, 'is_numeric');
        $offset    = $this->fetchParam('offset', 0, 'is_numeric');
        $paginate  = $this->fetchParam('paginate', true, null, true, false);

        if ($limit || $offset) {
            if ($limit && $paginate && !$offset) {
                // pagination requested, isolate the appropriate page
                $content_set->isolatePage($limit, URL::getCurrentPaginationPage());
            } else {
                // just limit or offset
                $content_set->limit($limit, $offset);
            }
        }

        // get total pages
        $total_pages = $content_set->count();

        // check for results
        if (!$total_pages) {
            return '0';
        }

        // total them up
        $total = 0;
        foreach ($content_set->get(false, false) as $content) {
            if (!isset($content[$field]) || !is_numeric($content[$field])) {
                continue;
            }

            $total += $content[$field];
        }

        // set output
        $output = $total;

        // perform other actions
        if ($action === 'average') {
            $output = $output / $total_pages;
        }

        return (!is_null($precision)) ? number_format($output, $precision) : (string) $output;
    }



    /**
     * Lists entries based on passed parameters
     *
     * @return array|string
     */
    public function listing()
    {
        // grab common parameters
        $settings = $this->parseCommonParameters();

        // grab content set based on the common parameters
        $content_set = $this->getContentSet($settings);

        // limit
        $limit     = $this->fetchParam('limit', null, 'is_numeric');
        $offset    = $this->fetchParam('offset', 0, 'is_numeric');
        $paginate  = $this->fetchParam('paginate', true, null, true, false);

        if ($limit || $offset) {
            if ($limit && $paginate && !$offset) {
                // pagination requested, isolate the appropriate page
                $content_set->isolatePage($limit, URL::getCurrentPaginationPage());
            } else {
                // just limit
                $content_set->limit($limit, $offset);
            }
        }

        // check for results
        if (!$content_set->count()) {
            return Parse::template($this->content, array('no_results' => true));
        }

        return Parse::tagLoop($this->content, $content_set->get(), true, $this->context);
    }


    /**
     * Paginates a list of entries
     *
     * @return string
     */
    public function pagination()
    {
        // grab common parameters
        $settings = $this->parseCommonParameters();

        // grab content set based on the common parameters
        $content_set = $this->getContentSet($settings);

        // grab limit as page size
        $limit = $this->fetchParam('limit', 10, 'is_numeric'); // defaults to none

        // count the content available
        $count = $content_set->count();

        $pagination_variable = Config::getPaginationVariable();
        $page                = Request::get($pagination_variable, 1);

        $data                       = array();
        $data['total_items']        = (int) max(0, $count);
        $data['items_per_page']     = (int) max(1, $limit);
        $data['total_pages']        = (int) ceil($count / $limit);
        $data['current_page']       = (int) min(max(1, $page), max(1, $page));
        $data['current_first_item'] = (int) min((($page - 1) * $limit) + 1, $count);
        $data['current_last_item']  = (int) min($data['current_first_item'] + $limit - 1, $count);
        $data['previous_page']      = ($data['current_page'] > 1) ? "?{$pagination_variable}=" . ($data['current_page'] - 1) : FALSE;
        $data['next_page']          = ($data['current_page'] < $data['total_pages']) ? "?{$pagination_variable}=" . ($data['current_page'] + 1) : FALSE;
        $data['first_page']         = ($data['current_page'] === 1) ? FALSE : "?{$pagination_variable}=1";
        $data['last_page']          = ($data['current_page'] >= $data['total_pages']) ? FALSE : "?{$pagination_variable}=" . $data['total_pages'];
        $data['offset']             = (int) (($data['current_page'] - 1) * $limit);

        return Parse::template($this->content, $data);
    }


    /**
     * Display the next entry listing for the settings provided based on $current URL
     *
     * @param array  $passed_settings  Optional passed settings for reusing methods
     * @param boolean  $check_for_previous  Should we check for previous values?
     * @param boolean  $wrap_around  If there's no next, return the first item?
     * @return array
     */
    public function next($passed_settings=null, $check_for_previous=true, $wrap_around=null)
    {
        // grab common parameters
        $settings = Helper::pick($passed_settings, $this->parseCommonParameters());

        // grab content set based on the common parameters
        $content_set = $this->getContentSet($settings);

        // what is our base point?
        $current = $this->fetch('current', URL::getCurrent(), false, false, false);

        // should we wrap around?
        $wrap_around = Helper::pick($wrap_around, $this->fetch('wrap', false, false, true, false));

        // check for has_previous, used for method interoperability
        if ($check_for_previous) {
            $previous = $this->previous($settings, false, $wrap_around);
            $has_previous = !is_array($previous);
        } else {
            $has_previous = false;
        }

        // if current wasn't set, we can't determine the next content
        if (!$current) {
            return array('no_results' => true, 'has_previous' => $has_previous);
        }

        // get the content
        $content = $content_set->get(preg_match(Pattern::USING_CONTENT, $this->content));

        // set up iterator variables
        $current_found = false;
        $output_data   = null;

        // loop through content looking for current
        foreach ($content as $item) {
            // has current data already been found? then we want this one
            if ($current_found) {
                $output_data = $item;
                break;
            }

            // this should never happen, but just in case
            if (!isset($item['url'])) {
                continue;
            }

            if ($item['url'] == $current) {
                $current_found = true;
            }
        }

        // wrap around?
        if ($wrap_around && is_array($content) && !empty($content) && (!$output_data || !is_array($output_data))) {
            $output_data = array_shift($content);
        }

        // if no $output_data was found, tell'em so
        if (!$output_data || !is_array($output_data)) {
            return array('no_results' => true, 'has_previous' => $has_previous);
        }

        // does this context have a previous?
        $output_data['has_previous'] = $has_previous;

        // return the found data
        return Parse::template($this->content, $output_data);
    }


    /**
     * Display the previous entry listing for the settings provided based on $current URL
     *
     * @param array  $passed_settings  Optional passed settings for reusing methods
     * @param boolean  $check_for_next  Should we check for next values?
     * @param boolean  $wrap_around  If there's no previous, return the last item?
     * @return array
     */
    public function previous($passed_settings=null, $check_for_next=true, $wrap_around=null)
    {
        // grab common parameters
        $settings = Helper::pick($passed_settings, $this->parseCommonParameters());

        // grab content set based on the common parameters
        $content_set = $this->getContentSet($settings);

        // what is our base point?
        $current = $this->fetch('current', URL::getCurrent(), false, false, false);

        // should we wrap around?
        $wrap_around = Helper::pick($wrap_around, $this->fetch('wrap', false, false, true, false));

        // check for has_next, used for method interoperability
        if ($check_for_next) {
            $next = $this->next($settings, false, $wrap_around);
            $has_next = !is_array($next);
        } else {
            $has_next = false;
        }

        // if current wasn't set, we can't determine the previous content
        if (!$current) {
            return array('no_results' => true, 'has_next' => $has_next);
        }

        // get the content
        $content = $content_set->get(preg_match(Pattern::USING_CONTENT, $this->content));

        // set up iterator variables
        $previous_data = null;
        $output_data   = null;

        // loop through content looking for current
        foreach ($content as $item) {
            // this should never happen, but just in case
            if (!isset($item['url'])) {
                continue;
            }

            if ($item['url'] == $current) {
                $output_data = $previous_data;
                break;
            }

            // wasn't a match, set this item as previous data and do it again
            $previous_data = $item;
        }

        // wrap around?
        if ($wrap_around && is_array($content) && !empty($content) && (!$output_data || !is_array($output_data))) {
            $output_data = array_pop($content);
        }

        // if no $output_data was found, tell'em so
        if (!$output_data || !is_array($output_data)) {
            return array('no_results' => true, 'has_next' => $has_next);
        }

        // does this context have a previous?
        $output_data['has_next'] = $has_next;

        // return the found data
        return Parse::template($this->content, $output_data);
    }


    /**
     * Parses out all of the needed parameters for this plugin
     *
     * @return array
     */
    private function parseCommonParameters()
    {        
        // determine folder
        $folders = array('folders' => $this->fetchParam('folder', $this->fetchParam('folders', ltrim($this->fetchParam('from', URL::getCurrent()), "/"))));

        // determine filters
        $filters = array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'since'       => $this->fetchParam('since'),
            'until'       => $this->fetchParam('until'),
            'show_past'   => $this->fetchParam('show_past', true, null, true),
            'show_future' => $this->fetchParam('show_future', false, null, true),
            'type'        => 'pages',
            'conditions'  => trim($this->fetchParam('conditions', null, false, false, false)),
            'where'       => trim($this->fetchParam('where', null, false, false, false))
        );

        // determine supplemental data
        $supplements = array(
            'locate_with' => $this->fetchParam('locate_with', null, false, false, false),
            'center_point' => $this->fetchParam('center_point', null, false, false, false)
        );

        // determine other factors
        $other = array(
            'taxonomy'      => $this->fetchParam('taxonomy', false, null, true, null),
            'sort_by'       => $this->fetchParam('sort_by', 'order_key'),
            'sort_dir'      => $this->fetchParam('sort_dir')
        );
        $other['sort'] = $this->fetchParam('sort', $other['sort_by'] . ' ' . $other['sort_dir'], null, false, null);

        return $other + $supplements + $filters + $folders;
    }


    /**
     * Returns a ContentSet object with the appropriate content
     *
     * @param array  $settings  Settings for filtering content and such
     * @return ContentSet
     */
    private function getContentSet($settings)
    {
        // create a unique hash for these settings
        $content_hash = Helper::makeHash($settings);

        if ($this->blink->exists($content_hash)) {
            // blink content exists, use that
            $content_set = new ContentSet($this->blink->get($content_hash));
        } else {
            // no blink content exists, get data the hard way
            if ($settings['taxonomy']) {
                $taxonomy  = Taxonomy::getCriteria(URL::getCurrent());
                $taxonomy_slug   = Config::get('_taxonomy_slugify') ? Slug::humanize($taxonomy['slug']) : urldecode($taxonomy['slug']);

                $content_set = ContentService::getContentByTaxonomyValue($taxonomy['type'], $taxonomy_slug, $settings['folders']);
            } else {
                $content_set = ContentService::getContentByFolders($settings['folders']);
            }

            // filter
            $content_set->filter($settings);

            // grab total entries for setting later
            $total_entries = $content_set->count();

            // pre-sort supplement
            $content_set->supplement(array('total_found' => $total_entries) + $settings);

            // sort
            $content_set->multisort($settings['sort']);

            // store content as blink content for future use
            $this->blink->set($content_hash, $content_set->extract());
        }

        return $content_set;
    }
}