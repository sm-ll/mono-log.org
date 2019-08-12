<?php
/**
 * ContentSet
 * Special content container for dealing with content display
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     Core
 * @copyright   2013 Statamic
 */
class ContentSet
{
    private $content = array();
    private $prepared = false;
    private $content_parsed = false;
    private $supplemented = false;
    private $folder_data = array();
    static private $known_content = array();


    /**
     * Create ContentSet
     *
     * @param array  $content  List of content to start with
     * @return ContentSet
     */
    public function __construct($content)
    {
        $this->content = $this->makeUnique($content);
    }


    /**
     * Gets a count of the content contained in this set
     *
     * @return int
     */
    public function count()
    {
        return count($this->content);
    }


    /**
     * Ensures that the given content array is unique
     *
     * @param array  $content  Content to loop through
     * @return array
     */
    public function makeUnique($content)
    {
        if (!is_array($content)) {
            return array();
        }

        $urls = array();

        foreach ($content as $key => $item) {
            if (!isset($item['url']) || in_array($item['url'], $urls)) {
                unset($content[$key]);
                continue;
            }

            array_push($urls, $item['url']);
        }

        return array_values($content);
    }


    /**
     * Filters the current content set down based on the filters given
     *
     * @param array  $filters  Filters to use to narrow down content
     * @return void
     * @throws Exception
     */
    public function filter($filters)
    {
        $hash = Debug::markStart('content', 'filtering');
        $filters = Helper::ensureArray($filters);

        // nothing to filter, abort
        if (!$this->count()) {
            return;
        }

        $since_date     = null;
        $until_date     = null;
        $remove_hidden  = null;
        $remove_drafts  = null;
        $keep_type      = "all";
        $folders        = null;
        $conditions     = null;
        $located        = false;
        $where          = null;


        // standardize filters
        // -------------------
        $given_filters = $filters;
        $filters = array(
            'show_hidden' => (isset($given_filters['show_hidden']))    ? $given_filters['show_hidden']      : null,
            'show_drafts' => (isset($given_filters['show_drafts']))    ? $given_filters['show_drafts']      : null,
            'since'       => (isset($given_filters['since']))          ? $given_filters['since']            : null,
            'until'       => (isset($given_filters['until']))          ? $given_filters['until']            : null,
            'show_past'   => (isset($given_filters['show_past']))      ? $given_filters['show_past']        : null,
            'show_future' => (isset($given_filters['show_future']))    ? $given_filters['show_future']      : null,
            'type'        => (isset($given_filters['type']))           ? strtolower($given_filters['type']) : null,
            'folders'     => (isset($given_filters['folders']))        ? $given_filters['folders']          : null,
            'conditions'  => (isset($given_filters['conditions']))     ? $given_filters['conditions']       : null,
            'located'     => (isset($given_filters['located']))        ? $given_filters['located']          : null,
            'where'       => (isset($given_filters['where']))          ? $given_filters['where']            : null
        );


        // determine filters
        // -----------------
        if (!is_null($filters['show_hidden'])) {
            $remove_hidden = !((bool) $filters['show_hidden']);
        }

        if (!is_null($filters['show_drafts'])) {
            $remove_drafts = !((bool) $filters['show_drafts']);
        }

        if ($filters['since']) {
            $since_date = Date::resolve($filters['since']);
        }

        if ($filters['show_past'] === false && (!$since_date || $since_date < time())) {
            $since_date = (Config::getEntryTimestamps()) ? time() : Date::resolve("today midnight");
        }

        if ($filters['until']) {
            $until_date = Date::resolve($filters['until']);
        }

        if ($filters['show_future'] === false && (!$until_date || $until_date > time())) {
            $until_date = (Config::getEntryTimestamps()) ? time() : Date::resolve("tomorrow midnight") - 1;
        }

        if ($filters['type'] === "entries" || $filters['type'] === "pages") {
            $keep_type = $filters['type'];
        }

        if ($filters['folders']) {
            $folders = Parse::pipeList($filters['folders']);
        }

        if ($filters['conditions']) {
            $conditions = Parse::conditions($filters['conditions']);
        }

        if ($filters['located']) {
            $located = true;
        }
        
        if ($filters['where']) {
            $where = $filters['where'];
        }
        
        
        // before we run filters, we need to look through conditions if they
        // were set to see if we're going to need content or content_raw
        // -----------
        
        if ($conditions) {
            // check for conditions involving content
            $uses_content = false;
            foreach ($conditions as $field => $instructions) {
                if (strtolower($field) === 'content') {
                    $uses_content = true;
                    break;
                }
            }

            // this uses content, which means we need to load it for all content
            if ($uses_content) {
                $this->prepare(true, false);
                $this->content_parsed = false;
            }
        }


        // run filters
        // -----------
        foreach ($this->content as $key => $data) {
            // entry or page removal
            if ($keep_type === "pages" && !$data['_is_page']) {
                unset($this->content[$key]);
                continue;
            } elseif ($keep_type === "entries" && !$data['_is_entry']) {
                unset($this->content[$key]);
                continue;
            }

            // check for non-public content
            if ($remove_drafts && $data['_is_draft']) {
                unset($this->content[$key]);
                continue;
            }
            
            if ($remove_hidden && $data['_is_hidden']) {
                unset($this->content[$key]);
                continue;
            }

            // folder
            if ($folders) {
                $keep = false;
                foreach ($folders as $folder) {
                    if ($folder === "*" || $folder === "/*") {
                        // include all
                        $keep = true;
                        break;
                    } elseif (substr($folder, -1) === "*") {
                        // wildcard check
                        if (strpos($data['_folder'], substr($folder, 0, -1)) === 0) {
                            $keep = true;
                            break;
                        }
                    } else {
                        // plain check
                        if ($folder == $data['_folder']) {
                            $keep = true;
                            break;
                        }
                    }
                }

                if (!$keep) {
                    unset($this->content[$key]);
                    continue;
                }
            }

            // since & show past
            if ($since_date && $data['datestamp'] && $data['datestamp'] < $since_date) {
                unset($this->content[$key]);
                continue;
            }

            // until & show future
            if ($until_date && $data['datestamp'] && $data['datestamp'] > $until_date) {
                unset($this->content[$key]);
                continue;
            }

            // where
            if ($where && !(bool) Parse::template("{{ if " . $where . " }}1{{ else }}0{{ endif }}", $data)) {
                unset($this->content[$key]);
                continue;
            }

            // conditions
            if ($conditions) {
                $case_sensitive_taxonomies  = Config::getTaxonomyCaseSensitive();

                foreach ($conditions as $field => $instructions) {
                    $optional = (substr($field, -1, 1) == '?');
                    $field    = ($optional) ? trim(substr($field, 0, -1)) : $field;
                    
                    try {                        
                        // are we looking for existence?
                        if ($instructions['kind'] === "existence") {
                            if ($instructions['type'] === "has") {
                                if (!isset($data[$field]) || !$data[$field]) {
                                    throw new Exception("Does not fit condition");
                                }
                            } elseif ($instructions['type'] === "lacks") {
                                if (isset($data[$field]) && $data[$field]) {
                                    throw new Exception("Does not fit condition");
                                }
                            } else {
                                throw new Exception("Unknown existence type");
                            }

                        // are we looking for a comparison?
                        } elseif ($instructions['kind'] === "comparison") {
                            $is_taxonomy     = Taxonomy::isTaxonomy($field);
                            $case_sensitive  = ($is_taxonomy && $case_sensitive_taxonomies);

                            if (!isset($data[$field])) {
                                $field = false;
                                $values = null;
                            } else {
                                if ($case_sensitive) {
                                    $field  = $data[$field];
                                    $values = $instructions['value'];
                                } else {
                                    $field  = (is_array($data[$field]))          ? array_map('strtolower', $data[$field])          : strtolower($data[$field]);
                                    $values = (is_array($instructions['value'])) ? array_map('strtolower', $instructions['value']) : strtolower($instructions['value']);
                                }
                            }

                            // convert boolean-like statements to boolean values
                            if (is_array($values)) {
                                foreach ($values as $item => $value) {
                                    if ($value == "true" || $value == "yes") {
                                        $values[$item] = true;
                                    } elseif ($value == "false" || $value == "no") {
                                        $values[$item] = false;
                                    }
                                }
                            } else {
                                if ($values == "true" || $values == "yes") {
                                    $values = true;
                                } elseif ($values == "false" || $values == "no") {
                                    $values = false;
                                }
                            }
                            
                            // convert date-like statements to timestamps for qualitative comparisons (not equals)
                            if (in_array($instructions['type'], array('greater than or equal to', 'greater than', 'less than or equal to', 'less than'))) {
                                if (!is_array($field) && !is_numeric($field) && Date::resolve($field) !== false) {
                                    $field = Date::resolve($field);
                                }
                                
                                if (!is_array($values) && !is_numeric($values) && Date::resolve($values) !== false) {
                                    $values = Date::resolve($values);
                                }
                            }

                            // equal comparisons
                            if ($instructions['type'] == "equal") {
                                // if this isn't set, it's not equal
                                if (!$field) {
                                    throw new Exception("Field not set", 1);
                                }

                                if (!is_array($field)) {
                                    if ($field != $values) {
                                        throw new Exception("Does not fit condition", 0);
                                    }
                                } elseif (!in_array($values, $field)) {
                                    throw new Exception("Does not fit condition", 0);
                                }
                                
                            // greater than or equal to comparisons
                            } elseif ($instructions['type'] == "greater than or equal to") {
                                // if this isn't set, it's not greater than or equal to
                                if (!$field) {
                                    throw new Exception("Field not set", 1);
                                }

                                if (is_array($field) || is_array($values)) {
                                    throw new Exception("Does not fit condition", 0);
                                }

                                if (!is_numeric($field) && Date::resolve($field) !== false) {
                                    $field = Date::resolve($field);
                                }

                                if (!is_numeric($values) && Date::resolve($values) !== false) {
                                    $values = Date::resolve($values);
                                }
                                
                                if (!is_numeric($field) || !is_numeric($values) || $this->toNumber($field) < $this->toNumber($values)) {
                                    throw new Exception("Does not fit condition", 0);
                                }
                                
                            // greater than to comparisons
                            } elseif ($instructions['type'] == "greater than") {
                                // if this isn't set, it's not less than
                                if (!$field) {
                                    throw new Exception("Field not set", 1);
                                }
                                
                                if (is_array($field) || is_array($values)) {
                                    throw new Exception("Does not fit condition", 0);
                                }
                                
                                if (!is_numeric($field) && Date::resolve($field) !== false) {
                                    $field = Date::resolve($field);
                                }
                                
                                if (!is_numeric($values) && Date::resolve($values) !== false) {
                                    $values = Date::resolve($values);
                                }
                                
                                if (!is_numeric($field) || !is_numeric($values) || $this->toNumber($field) <= $this->toNumber($values)) {
                                    throw new Exception("Does not fit condition", 0);
                                }
                                
                            // less than or equal to comparisons
                            } elseif ($instructions['type'] == "less than or equal to") {
                                // if this isn't set, it's not less than or equal to
                                if (!$field) {
                                    throw new Exception("Field not set", 1);
                                }

                                if (is_array($field) || is_array($values)) {
                                    throw new Exception("Does not fit condition", 0);
                                }

                                if (!is_numeric($field) && Date::resolve($field) !== false) {
                                    $field = Date::resolve($field);
                                }

                                if (!is_numeric($values) && Date::resolve($values) !== false) {
                                    $values = Date::resolve($values);
                                }
                                
                                if (!is_numeric($field) || !is_numeric($values) || $this->toNumber($field) > $this->toNumber($values)) {
                                    throw new Exception("Does not fit condition", 0);
                                }
                                
                            // less than to comparisons
                            } elseif ($instructions['type'] == "less than") {
                                // if this isn't set, it's not less than
                                if (!$field) {
                                    throw new Exception("Field not set", 1);
                                }

                                if (is_array($field) || is_array($values)) {
                                    throw new Exception("Does not fit condition", 0);
                                }

                                if (!is_numeric($field) && Date::resolve($field) !== false) {
                                    $field = Date::resolve($field);
                                }

                                if (!is_numeric($values) && Date::resolve($values) !== false) {
                                    $values = Date::resolve($values);
                                }
                                
                                if (!is_numeric($field) || !is_numeric($values) || $this->toNumber($field) >= $this->toNumber($values)) {
                                    throw new Exception("Does not fit condition", 0);
                                }

                            // not-equal comparisons
                            } elseif ($instructions['type'] == "not equal") {
                                // if this isn't set, it's not equal, continue
                                if (!$field) {
                                    continue;
                                }

                                if (!is_array($field)) {
                                    if ($field == $values) {
                                        throw new Exception("Does not fit condition", 0);
                                    }
                                } elseif (in_array($values, $field)) {
                                    throw new Exception("Does not fit condition", 0);
                                }

                            // contains array comparisons
                            } elseif ($instructions['type'] == "in") {
                                if (!$field) {
                                    throw new Exception("Field not set", 1);
                                }
                                
                                if (!count(array_intersect(Helper::ensureArray($field), $values))) {
                                    throw new Exception("Does not fit condition", 0);
                                }
                                
                            // doesn't contain array comparisons
                            } elseif ($instructions['type'] == "not in") {
                                if (!$field) {
                                    throw new Exception("Field not set", 1);
                                }
                                
                                if (count(array_intersect(Helper::ensureArray($field), $values))) {
                                    throw new Exception("Does not fit condition", 0);
                                }           
                                
                            // contains contains-text comparisons
                            } elseif ($instructions['type'] == 'contains text') {
                                if (!$field) {
                                    throw new Exception("Field not set", 1);
                                }
                                
                                $field = Helper::ensureArray($field);
                                $found = false;
                                
                                foreach ($field as $option) {
                                    // do we need to loop through values?
                                    if (is_array($values)) {
                                        // we do
                                        foreach ($values as $value) {
                                            if (strpos($option, strtolower($value)) !== false) {
                                                $found = true;
                                                break;
                                            }
                                        }
                                        
                                        if ($found) {
                                            break;
                                        }
                                    } else {
                                        // we don't
                                        if (strpos($option, strtolower($values)) !== false) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                }

                                if (!$found) {
                                    throw new Exception("Does not fit condition", 0);
                                }
                            }

                        // we don't know what this is
                        } else {
                            throw new Exception("Unknown kind of condition", -1);
                        }

                    } catch (Exception $e) {
                        if ($optional && $e->getCode() === 1) {
                            // we were only making the comparison if the field exists,
                            // otherwise, this is ok
                            continue;
                        }
                        
                        // this was not an optional field, and something went wrong
                        unset($this->content[$key]);
                        continue;
                    }
                }
            }

            // located
            if ($located && (!isset($data['coordinates']))) {
                unset($this->content[$key]);
                continue;
            }
        }
        
        Debug::markEnd($hash);
    }
    
    private function toNumber($value) {
        if (!is_numeric($value)) {
            return false;
        }
        
        return (strpos($value, '.') !== false) ? (float) $value : (int) $value;
    }


    /**
     * Sorts the current content by $field and $direction
     *
     * @param string  $fields  Fields to sort on
     * @return void
     */
    public function multisort($fields="order_key")
    {
        $hash = Debug::markStart('content', 'sorting');

        // no content, abort
        if (!count($this->content)) {
            return;
        }
        
        // determine type of things being sorted
        reset($this->content);
        $sample = $this->content[key($this->content)];
        $is_date_based = false;

        // if we're sorting by order_key and it's date-based order, default sorting is 'desc'
        if ($sample['_order_key'] && $sample['datestamp']) {
            $is_date_based = true;
        }
        
        // determine fields
        $chunks = explode(',', $fields);
        foreach ($chunks as &$chunk) {
            $chunk = explode(' ', trim($chunk));
            
            if (count($chunk) === 1) {
                $chunk[1] = ($is_date_based && $chunk[0] === "order_key") ? 'desc' : 'asc';
            }
        }

        // sort by field
        usort($this->content, function($item_1, $item_2) use ($chunks) {
            foreach ($chunks as $chunk) {
                $field = $chunk[0];
                $direction = $chunk[1];
                
                // grab values, translating some user-facing names into internal ones
                switch ($field) {
                    case "order_key":
                        $value_1 = $item_1['_order_key'];
                        $value_2 = $item_2['_order_key'];
                        break;

                    case "number":
                        $value_1 = $item_1['_order_key'];
                        $value_2 = $item_2['_order_key'];
                        break;

                    case "datestamp":
                        $value_1 = $item_1['datestamp'];
                        $value_2 = $item_2['datestamp'];
                        break;

                    case "date":
                        $value_1 = $item_1['datestamp'];
                        $value_2 = $item_2['datestamp'];
                        break;

                    case "folder":
                        $value_1 = $item_1['_folder'];
                        $value_2 = $item_2['_folder'];
                        break;

                    case "distance":
                        $value_1 = $item_1['distance_km'];
                        $value_2 = $item_2['distance_km'];
                        break;
                    
                    case "random":
                        return rand(-1, 1);
                        break;

                    // not a special case, grab the field values if they exist
                    default:
                        $value_1 = (isset($item_1[$field])) ? $item_1[$field] : null;
                        $value_2 = (isset($item_2[$field])) ? $item_2[$field] : null;
                        break;
                }

                // compare the two values
                // ----------------------------------------------------------------
                $result = Helper::compareValues($value_1, $value_2);
                
                if ($result !== 0) {
                    return ($direction === 'desc') ? $result * -1 : $result;
                }
            }
            
            return 0;
        });

        Debug::markEnd($hash);
    }


    /**
     * Sorts the current content by $field and $direction
     *
     * @param string  $field  Field to sort on
     * @param string  $direction  Direction to sort
     * @return void
     */
    public function sort($field="order_key", $direction=null)
    {
        $hash = Debug::markStart('content', 'sorting');
        
        // no content, abort
        if (!count($this->content)) {
            return;
        }

        // sort by random, short-circuit
        if ($field == "random") {
            shuffle($this->content);
            return;
        }

        // sort by field
        usort($this->content, function($item_1, $item_2) use ($field) {
            // grab values, translating some user-facing names into internal ones
            switch ($field) {
                case "order_key":
                    $value_1 = $item_1['_order_key'];
                    $value_2 = $item_2['_order_key'];
                    break;

                case "number":
                    $value_1 = $item_1['_order_key'];
                    $value_2 = $item_2['_order_key'];
                    break;

                case "datestamp":
                    $value_1 = $item_1['datestamp'];
                    $value_2 = $item_2['datestamp'];
                    break;

                case "date":
                    $value_1 = $item_1['datestamp'];
                    $value_2 = $item_2['datestamp'];
                    break;

                case "folder":
                    $value_1 = $item_1['_folder'];
                    $value_2 = $item_2['_folder'];
                    break;

                case "distance":
                    $value_1 = $item_1['distance_km'];
                    $value_2 = $item_2['distance_km'];
                    break;

                // not a special case, grab the field values if they exist
                default:
                    $value_1 = (isset($item_1[$field])) ? $item_1[$field] : null;
                    $value_2 = (isset($item_2[$field])) ? $item_2[$field] : null;
                    break;
            }

            // compare the two values
            // ----------------------------------------------------------------
            return Helper::compareValues($value_1, $value_2);
        });

        // apply sort direction
        if (is_null($direction)) {
            reset($this->content);
            $sample = $this->content[key($this->content)];

            // if we're sorting by order_key and it's date-based order, default sorting is 'desc'
            if ($field == "order_key" && $sample['_order_key'] && $sample['datestamp']) {
                $direction = "desc";
            } else {
                $direction = "asc";
            }
        }

        // do we need to flip the order?
        if (Helper::pick($direction, "asc") == "desc") {
            $this->content = array_reverse($this->content);
        }
        
        Debug::markEnd($hash);
    }


    /**
     * Limits the number of items kept in the set
     *
     * @param int  $limit  The maximum number of items to keep
     * @param int  $offset  Offset the starting point of the chop
     * @return void
     */
    public function limit($limit=null, $offset=0)
    {
        $hash = Debug::markStart('content', 'limiting');
        if (is_null($limit) && $offset === 0) {
            return;
        }

        $this->content = array_slice($this->content, $offset, $limit, true);
        Debug::markEnd($hash);
    }


    /**
     * Grabs one page from a paginated set
     *
     * @param int  $page_size  Size of page to grab
     * @param int  $page  Page number to grab
     * @return void
     */
    public function isolatePage($page_size, $page)
    {
        $count = $this->count();

        // return the last page of results if $page is out of range
        if (Config::getFixOutOfRangePagination()) {
            if ($page_size * $page > $count) {
                $page = ceil($count / $page_size);
            } elseif ($page < 1) {
                $page = 1;
            }
        }

        $offset = ($page - 1) * $page_size;
        $this->limit($page_size, $offset);
    }



    /**
     * Prepares the data for use in loops
     *
     * @param bool  $parse_content  Parse content? This is a performance hit.
     * @param bool  $override_flag  Override `prepared` flag and re-loop?
     * @return void
     */
    public function prepare($parse_content=true, $override_flag=false)
    {
        $hash = Debug::markStart('content', 'preparing');
        if ($this->prepared && !$override_flag) {
            return;
        }

        $this->prepared = true;
        $count = $this->count();
        $i = 1;

        // loop through the content adding contextual data
        foreach ($this->content as $key => $item) {
            $this->content[$key]['first']         = ($i === 1);
            $this->content[$key]['last']          = ($i === $count);
            $this->content[$key]['count']         = $i;
            $this->content[$key]['total_results'] = $count;

            // parse full content if that's been requested and is needed
            if ($parse_content && isset($item['_file']) && (!$this->content_parsed || $override_flag)) {
                // check to see if we know about this content
                if (!isset(self::$known_content[$item['url']])) {
                    // we haven't seen this item before in this page-load
                    // retrieve this content
                    $content_file  = (isset($item['_file'])) ? $item['_file'] : null;
                    $item_content  = array('content_raw' => '', 'content' => '');

                    // content file exists
                    if ($content_file && File::exists($content_file)) {
                        // make this
                        $raw_file  = substr(File::get($content_file), 3);
                        $divide    = strpos($raw_file, "\n---");

                        $item_content['content_raw']  = trim(substr($raw_file, $divide + 4));
                        $item_content['content']      = Content::parse($item_content['content_raw'], $item);
                    }

                    // update the cache
                    self::$known_content[$item['url']] = $item_content;
                }

                // pull the content from the known-content cache
                $this->content[$key]['content_raw'] = self::$known_content[$item['url']]['content_raw'];
                $this->content[$key]['content']     = self::$known_content[$item['url']]['content'];
            }

            // iterate the counter
            $i++;
        }

        // mark that we've parsed content so that we don't do it again
        if ($parse_content) {
            $this->content_parsed = true;
        }
        
        Debug::markEnd($hash);
    }


    /**
     * Supplements the content in the set
     *
     * @param array  $context  Context for supplementing
     * @param bool  $override  Override the check to see if this has already been supplemented
     * @return void
     */
    public function supplement($context=array(), $override=false)
    {
        $hash = Debug::markStart('content', 'supplementing');
        
        if ($this->supplemented && !$override) {
            return;
        }

        $this->supplemented = true;
        $context = Helper::ensureArray($context);

        // determine context
        $given_context = $context;
        $context = array(
            'locate_with'         => (isset($given_context['locate_with']))         ? $given_context['locate_with']         : null,
            'center_point'        => (isset($given_context['center_point']))        ? $given_context['center_point']        : null,
            'list_helpers'        => (isset($given_content['list_helpers']))        ? $given_context['list_helpers']        : true,
            'context_urls'        => (isset($given_context['context_urls']))        ? $given_context['context_urls']        : true,
            'total_found'         => (isset($given_context['total_found']))         ? $given_context['total_found']         : null,
            'group_by_date'       => (isset($given_context['group_by_date']))       ? $given_context['group_by_date']       : null,
            'inherit_folder_data' => (isset($given_context['inherit_folder_data'])) ? $given_context['inherit_folder_data'] : true,
            'merge_with_data'     => (isset($given_context['merge_with_data']))     ? $given_context['merge_with_data']     : true
        );

        // set up helper variables
        $center_point = false;
        if ($context['center_point'] && preg_match(Pattern::COORDINATES, $context['center_point'], $matches)) {
            $center_point = array($matches[1], $matches[2]);
        }

        // contextual urls are based on current page, not individual data records
        // we can figure this out once and then set it with each one
        if ($context['context_urls']) {
            $raw_url   = Request::getResourceURI();
            $page_url  = Path::tidy($raw_url);
        }
        
        // iteration memory
        $last_date = null;

        // loop through content, supplementing each record with data
        foreach ($this->content as $content_key => $data) {

            // locate
            if ($context['locate_with']) {
                $location_data = array_get($data, $context['locate_with']);

                // check that location data is fully set
                if (is_array($location_data) && isset($location_data['latitude']) && $location_data['latitude'] && isset($location_data['longitude']) && $location_data['longitude']) {
                    $data['latitude']     = $location_data['latitude'];
                    $data['longitude']    = $location_data['longitude'];
                    $data['coordinates']  = $location_data['latitude'] . "," . $location_data['longitude'];

                    // get distance from center
                    if ($center_point) {
                        $location = array($data['latitude'], $data['longitude']);
                        $data['distance_km'] = Math::getDistanceInKilometers($center_point, $location);
                        $data['distance_mi'] = Math::convertKilometersToMiles($data['distance_km']);
                    }
                }
            }

            // contextual urls
            if ($context['context_urls']) {
                $data['raw_url']  = $raw_url;
                $data['page_url'] = $page_url;
            }
            
            // total entries
            if ($context['total_found']) {
                $data['total_found']  = (int) $context['total_found']; 
            }
            
            // group by date
            if ($context['group_by_date'] && $data['datestamp']) {
                $formatted_date = Date::format($context['group_by_date'], $data['datestamp']);
                
                if ($formatted_date !== $last_date) {
                    $last_date            = $formatted_date;
                    $data['grouped_date'] = $formatted_date;
                } else {
                    $data['grouped_date'] = '';
                }
            }

            // loop through content to add data for variables that are arrays
            foreach ($data as $key => $value) {

                // Only run on zero indexed arrays/loops
                if (is_array($value) && isset($value[0]) && ! is_array($value[0])) {

                    // list helpers
                    if ($context['list_helpers']) {
                        // make automagic lists
                        $data[$key . "_list"]                    = join(", ", $value);
                        $data[$key . "_spaced_list"]             = join(" ", $value);
                        $data[$key . "_option_list"]             = join("|", $value);
                        $data[$key . "_ordered_list"]            = "<ol><li>" . join("</li><li>", $value) . "</li></ol>";
                        $data[$key . "_unordered_list"]          = "<ul><li>" . join("</li><li>", $value) . "</li></ul>";
                        $data[$key . "_sentence_list"]           = Helper::makeSentenceList($value);
                        $data[$key . "_ampersand_sentence_list"] = Helper::makeSentenceList($value, "&", false);

                        // handle taxonomies
                        if (Taxonomy::isTaxonomy($key)) {
                            $url_list = array_map(function($item) use ($data, $key, $value) {
                                return '<a href="' . Taxonomy::getURL($data['_folder'], $key, $item) . '">' . $item . '</a>';
                            }, $value);

                            $data[$key . "_url_list"]                    = join(", ", $url_list);
                            $data[$key . "_spaced_url_list"]             = join(" ", $url_list);
                            $data[$key . "_ordered_url_list"]            = "<ol><li>" . join("</li><li>", $url_list) . "</li></ol>";
                            $data[$key . "_unordered_url_list"]          = "<ul><li>" . join("</li><li>", $url_list) . "</li></ul>";
                            $data[$key . "_sentence_url_list"]           = Helper::makeSentenceList($url_list);
                            $data[$key . "_ampersand_sentence_url_list"] = Helper::makeSentenceList($url_list, "&", false);
                        }
                    }
                }
            }

            // update content with supplemented data merged with global config data
            if ($context['merge_with_data'] || $context['inherit_folder_data']) {
                $folder_data = array();
                $all_config  = array();
                
                if ($context['inherit_folder_data']) {
                    $folder_data = $this->getFolderData($data['_file']);
                }
                
                if ($context['merge_with_data']) {
                    $all_config = Config::getAll();
                }
                
                // merge them all together
                $this->content[$content_key] = $data + $folder_data + $all_config;
		    } else {
                $this->content[$content_key] = $data;
            }
        }
        
        Debug::markEnd($hash);
    }



    // custom adjustments
    // ------------------------------------------------------------------------

    /**
     * Custom-supplement content
     *
     * @param string  $key  Key to supplement
     * @param callable  $callback  Callback that returns the value to set
     * @return void
     */
    public function customSupplement($key, $callback)
    {
        foreach ($this->content as $content_key => $content_value) {
            $this->content[$content_key][$key] = call_user_func($callback, $this->content[$content_key]);
        }
    }


    /**
     * Custom-filter content
     *
     * @param string  $callback  Callback that, when returning false, removes content from list
     * @return void
     */
    public function customFilter($callback)
    {
        foreach ($this->content as $content_key => $content_value) {
            if (!call_user_func($callback, $this->content[$content_key])) {
                unset($this->content[$content_key]);
            }
        }
    }
    
    
    
    // folder data
    // ------------------------------------------------------------------------

    /**
     * Get folder data for a given path
     * 
     * @param string  $path  Path to retrieve data for
     * @return array
     */
    protected function getFolderData($path)
    {
        $local_path = str_replace(Path::tidy(BASE_PATH . '/' . Config::getContentRoot()), '', $path);
        
        $segments = explode('/', $local_path);
        $path = Path::tidy(BASE_PATH . '/' . Config::getContentRoot());
        $data = array();
        $content_type = Config::getContentType();
        
        foreach ($segments as $segment) {
            $path = Path::tidy($path . '/' . $segment);  
            
            if (strpos($path, '.' . $content_type) !== false) {
                continue;
            }
            
            $data = $this->loadFolderData($path) + $data;
        }
        
        return $data;
    }


    /**
     * Loads data from a given path from either the folder-data cache or from the filesystem
     * 
     * @param string  $path  File path to look up
     * @return array
     */
    protected function loadFolderData($path)
    {
        if (isset($this->folder_data[$path])) {
            return $this->folder_data[$path];
        } elseif (File::exists($path . '/folder.yaml')) {
            $folder = YAML::parseFile($path . '/folder.yaml');
            $this->folder_data[$path] = $folder;
            return $folder;
        } else {
            return array();
        }
    }


    // retrieve data
    // ------------------------------------------------------------------------

    /**
     * Get the data stored within
     *
     * @param bool  $parse_content  Parse content?
     * @param bool  $supplement  Supplement content?
     * @return array
     */
    public function get($parse_content=true, $supplement=true)
    {
        if ($supplement) {
            $this->supplement();
        }
        $this->prepare($parse_content);
        return $this->content;
    }
    
    
    /**
     * Extracts data without altering it
     * 
     * @return array
     */
    public function extract()
    {
        return $this->content;
    }
}