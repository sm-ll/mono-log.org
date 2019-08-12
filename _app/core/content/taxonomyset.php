<?php
/**
 * TaxonomySet
 * Special content container for dealing with taxonomies
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @package     Core
 * @copyright   2014 Statamic
 */
class TaxonomySet
{
    private $data = array();
    private $prepared = FALSE;


    /**
     * Create TaxonomySet
     *
     * @param array  $data  List of taxonomies
     * @return TaxonomySet
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->tallyResults();
    }


    /**
     * Gets a count of the content contained in this set
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Tally Results
     *
     * Tally up the result counts for this set
     * @return void
     **/
    public function tallyResults()
    {
        foreach ($this->data as $key => $item) {
            $this->data[$key]['results'] = $item['content']->count();
        }
    }


    /**
     * Filter
     *
     * @param array  $filters  Filter list
     * @return void
     */
    public function filter($filters)
    {
        $min_count = 0;
        
        $given_filters = $filters;
        
        $filters = array(
            'min_count'    => (isset($given_filters['min_count']))    ? $given_filters['min_count']        : null,
            'show_drafts'  => (isset($given_filters['show_drafts']))  ? $given_filters['show_drafts']      : null,
            'show_hidden'  => (isset($given_filters['show_hidden']))  ? $given_filters['show_hidden']      : null,
            'since'        => (isset($given_filters['since']))        ? $given_filters['since']            : null,
            'until'        => (isset($given_filters['until']))        ? $given_filters['until']            : null,
            'show_past'    => (isset($given_filters['show_past']))    ? $given_filters['show_past']        : null,
            'show_future'  => (isset($given_filters['show_future']))  ? $given_filters['show_future']      : null,
            'type'         => (isset($given_filters['type']))         ? strtolower($given_filters['type']) : null,
            'conditions'   => (isset($given_filters['conditions']))   ? $given_filters['conditions']       : null,
            'where'        => (isset($given_filters['where']))        ? $given_filters['where']            : null,
            'folders'      => (isset($given_filters['folders']))      ? $given_filters['folders']          : null,
            'located'      => (isset($given_filters['located']))      ? $given_filters['located']          : null
        );

        // fix folders to be an array
        if (!is_null($filters['folders'])) {
            $filters['folders'] = Helper::ensureArray($filters['folders']);
        }

        if (!is_null($filters['min_count'])) {
            $min_count = (int) $filters['min_count'];
        }

        $data = $this->data;
        foreach ($data as $value => $parts) {
            $parts['content']->filter($filters);
            $parts['count'] = $parts['content']->count();

            if ($parts['count'] < $min_count) {
                unset($data[$value]);
            }
        }

        $this->data = $data;
        
        // re-tally results
        $this->tallyResults();
    }


    /**
     * Contextualizes taxonomy links for a given folder
     *
     * @param string  $folder  Folder to insert
     * @return void
     */
    public function contextualize($folder=NULL)
    {
        // this may be empty, if so, abort
        if (!$folder) {
            return;
        }

        // strip out asterisks
        $folder = str_replace('*', '', $folder);

        // create the contextual URL root that we'll append the slug to
        $contextual_url_root = Config::getSiteRoot() . $folder . "/";

        // append the slug
        foreach ($this->data as $value => $parts) {
            $this->data[$value]['url'] = Path::tidy($contextual_url_root . $parts['slug']);
        }
    }


    /**
     * Sort
     *
     * @param string  $field  Field to sort by
     * @param string  $direction  Direction to sort
     * @return void
     */
    public function sort($field="name", $direction=NULL)
    {
        if ($field == "random") {
            shuffle($this->data);
            return;
        }

        usort($this->data, function($item_1, $item_2) use ($field) {
            
            $value_1 = array_get($item_1, $field);
            $value_2 = array_get($item_2, $field);
        
            return Helper::compareValues($value_1, $value_2);
        });

        // do we need to flip the order?
        if (Helper::pick($direction, "asc") == "desc") {
            $this->data = array_reverse($this->data);
        }

    }


    /**
     * Limits the number of items kept in the set
     *
     * @param int  $limit  The maximum number of items to keep
     * @param int  $offset  Offset the starting point of the chop
     * @return void
     */
    public function limit($limit, $offset=0)
    {
        $this->data = array_slice($this->data, $offset, $limit, TRUE);
    }


    /**
     * Prepare for use
     *
     * @return void
     */
    public function prepare()
    {
        if ($this->prepared) {
            return;
        }

        $this->prepared = true;
        $count = $this->count();
        $i = 1;

        foreach ($this->data as $key => $item) {
            $this->data[$key]['first']         = ($i === 1);
            $this->data[$key]['last']          = ($i === $count);
            
            $this->data[$key]['count']         = $i;
            $this->data[$key]['total_results'] = $count;

            $i++;
        }
    }


    /**
     * Get the data stored within
     *
     * @return array
     */
    public function get()
    {
        $this->prepare();
        return $this->data;
    }
}