<?php
/**
 * Plugin_entries
 * Display lists of entries
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 *
 * @copyright  2012-2014
 * @link       http://statamic.com/learn/documentation/tags/entries
 * @license    http://statamic.com/license-agreement
 */
class Plugin_entries extends Plugin
{
    /**
     * Combines numeric values of all entries found into one combined result
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
        
        // get total entries
        $total_entries = $content_set->count();

        // check for results
        if (!$total_entries) {
            return '0';
        }
        
        // total them up
        $total = 0;
        foreach ($content_set->get(false, false) as $content) {
            if (!isset($content[$field])) {
                continue;
            }

            // contains a comma? *might* be a number... strip them out.
            if (strpos($content[$field], ',')) {
                $content[$field] = str_replace(',', '', $content[$field]);
            }

            if (!is_numeric($content[$field])) {
                continue;
            }
            
            $total += $content[$field];
        }
        
        // set output
        $output = $total;
        
        // perform other actions
        if ($action === 'average') {
            $output = $output / $total_entries;
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
                // just limit or offset
                $content_set->limit($limit, $offset);
            }
        }

        // check for results
        if (!$content_set->count()) {
            return Parse::template($this->content, array('no_results' => true));
        }

        return Parse::tagLoop($this->content, $content_set->get(), false, $this->context);
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
        if (!empty($content)) {
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
     * Display the previous entry listing for the settings provided based $current URL
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
        if (!empty($content)) {
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
     * Displays entries on a map
     *
     * @return string
     */
    public function map()
    {
        // check for valid center point
        if (!preg_match(Pattern::COORDINATES, $this->fetchParam('center_point'), $matches)) {
            print_r($this->fetchParam('center_point'));
            $this->log->error("Could not create map, invalid center point coordinates given");
            return NULL;
        } else {
            $latitude  = $matches[1];
            $longitude = $matches[2];
        }

        // pop-up template
        $pop_up_template = NULL;

        // check for a valid pop_up template
        if (preg_match_all("/(?:\{\{\s*pop_up\s*\}\})\s*(.*)\s*(?:\{\{\s*\/pop_up\s*\}\})/ism", $this->content, $matches) && is_array($matches[1]) && isset($matches[1][0])) {
            $pop_up_template = trim($matches[1][0]);
        }

        $folders = $this->fetchParam('folder', ltrim($this->fetchParam('from', URL::getCurrent()), "/"));

        if ($this->fetchParam('taxonomy', false, null, true, null)) {
            $taxonomy  = Taxonomy::getCriteria(URL::getCurrent());
            $taxonomy_slug   = Config::get('_taxonomy_slugify') ? Slug::humanize($taxonomy['slug']) : urldecode($taxonomy['slug']);

            $content_set = ContentService::getContentByTaxonomyValue($taxonomy['type'], $taxonomy_slug, $folders);
        } else {
            $content_set = ContentService::getContentByFolders($folders);
        }

        // filter
        $content_set->filter(array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'since'       => $this->fetchParam('since'),
            'until'       => $this->fetchParam('until'),
            'show_past'   => $this->fetchParam('show_past', true, null, true),
            'show_future' => $this->fetchParam('show_future', false, null, true),
            'type'        => 'entries',
            'conditions'  => trim($this->fetchParam('conditions', null))
        ));
        
        // prepare if needed
        $parse_content = (bool) preg_match(Pattern::USING_CONTENT, $this->content);
        if ($parse_content) {
            $content_set->prepare();
        }

        // supplement
        $content_set->supplement(array(
            'locate_with'     => $this->fetchParam('locate_with'),
            'center_point'    => $this->fetchParam('center_point')
        ));

        // re-filter, we only want entries that have been found
        $content_set->filter(array(
            'located'     => true
        ));

        // sort
        $content_set->sort($this->fetchParam('sort_by', 'order_key'), $this->fetchParam('sort_dir'));

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

        // get content
        $content_set->prepare(false, true);
        $content = $content_set->get($parse_content);

        // set variables
        $map_id   = $this->fetchParam('map_id', Helper::getRandomString());
        $zoom     = $this->fetchParam('zoom', 12);

        // cluster options
        $clusters = $this->fetchParam('clusters', TRUE, NULL, TRUE);
        $clusters = ($clusters) ? "true" : "false";

        $spiderfy_on_max_zoom = $this->fetchParam('spiderfy_on_max_zoom', TRUE, NULL, TRUE);
        $spiderfy_on_max_zoom = ($spiderfy_on_max_zoom) ? "true" : "false";

        $show_coverage_on_hover = $this->fetchParam('show_coverage_on_hover', TRUE, NULL, TRUE);
        $show_coverage_on_hover = ($show_coverage_on_hover) ? "true" : "false";

        $zoom_to_bounds_on_click = $this->fetchParam('zoom_to_bounds_on_click', TRUE, NULL, TRUE);
        $zoom_to_bounds_on_click = ($zoom_to_bounds_on_click) ? "true" : "false";

        $single_marker_mode = $this->fetchParam('single_marker_mode', FALSE, NULL, TRUE);
        $single_marker_mode = ($single_marker_mode) ? "true" : "false";

        $animate_adding_markers = $this->fetchParam('animate_adding_markers', TRUE, NULL, TRUE);
        $animate_adding_markers = ($animate_adding_markers) ? "true" : "false";

        $disable_clustering_at_zoom = $this->fetchParam('disable_clustering_at_zoom', 15, 'is_numeric');
        $max_cluster_radius = $this->fetchParam('max_cluster_radius', 80, 'is_numeric');

        // create output
        $html  = '<div class="map" id="' . $map_id . '"></div>';
        $html .= "\n";

        // only render inline javascript if a valid pop_up template was found
        $html .= '<script type="text/javascript">';
        $html .= "try{_location_maps.length;}catch(e){var _location_maps={};}\n";
        $html .= '_location_maps["' . $map_id . '"] = { markers: [ ';

        $markers = array();
        foreach ($content as $item) {
            
            $marker = array(
                'latitude'       => $item['latitude'],
                'longitude'      => $item['longitude'],
                'marker_content' => Content::parse($pop_up_template, $item, 'html')
            );

            array_push($markers, json_encode($marker));
        }
        $html .= join(",\n", $markers);

        $html .= '    ], ';
        $html .= ' clusters: ' . $clusters . ',';

        // cluster options
        $html .= ' spiderfy_on_max_zoom: ' . $spiderfy_on_max_zoom . ',';
        $html .= ' show_coverage_on_hover: ' . $show_coverage_on_hover . ',';
        $html .= ' zoom_to_bounds_on_click: ' . $zoom_to_bounds_on_click . ',';
        $html .= ' single_marker_mode: ' . $single_marker_mode . ',';
        $html .= ' animate_adding_markers: ' . $animate_adding_markers . ',';
        $html .= ' disable_clustering_at_zoom: ' . $disable_clustering_at_zoom . ',';
        $html .= ' max_cluster_radius: ' . $max_cluster_radius . ',';

        $html .= ' starting_latitude: ' . $latitude . ',';
        $html .= ' starting_longitude: ' . $longitude . ',';
        $html .= ' starting_zoom: ' . $zoom . ' };';
        $html .= '</script>';

        return $html;
    }


    /**
     * Parses out all of the needed parameters for this plugin
     *
     * @return array
     */
    private function parseCommonParameters()
    {
        $current_folder = URL::getCurrent();
        
        // Strip taxonomy segments because they don't reflect physical folder locations
        if (Taxonomy::isTaxonomyUrl($current_folder)) {
            $current_folder = URL::stripTaxonomy($current_folder);
        }
        
        // determine folder
        $folders = array('folders' => $this->fetchParam(array('folder', 'folders', 'from'), $current_folder));

        // determine filters
        $filters = array(
            'show_hidden'   => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts'   => $this->fetchParam('show_drafts', false, null, true, false),
            'since'         => $this->fetchParam('since'),
            'until'         => $this->fetchParam('until'),
            'show_past'     => $this->fetchParam('show_past', true, null, true),
            'show_future'   => $this->fetchParam('show_future', false, null, true),
            'type'          => 'entries',
            'conditions'    => trim($this->fetchParam('conditions', null, false, false, false)),
            'where'         => trim($this->fetchParam('where', null, false, false, false))
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
            
            // post-sort supplement
            $content_set->supplement(array(
                'group_by_date' => trim($this->fetchParam("group_by_date", null, null, false, false))
            ), true);

            // store content as blink content for future use
            $this->blink->set($content_hash, $content_set->extract());
        }

        return $content_set;
    }
}