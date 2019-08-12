<?php
/**
 * Plugin_relate
 * Fetch content based on a relationship.
 *
 * @author  Statamic
 * @copyright  2012-2014
 */
class Plugin_relate extends Plugin
{

    public function __call($method, $arguments)
    {
        return $this->getContent($method);
    }
    
    /**
     * Lists entries based on passed parameters
     *
     * @return array|string
     */
    public function getContent($variable)
    {
        $urls = array_get($this->context, $variable);

        if ( ! $urls) return null;

        // grab common parameters
        $settings = $this->parseCommonParameters();
        
        // grab content set based on the common parameters
        // $content_set = $this->getContentSet($settings);

        $content_set = ContentService::getContentByURL($urls);

        $content_set->filter(array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'show_past'   => $this->fetchParam('show_past', true, null, true),
            'show_future' => $this->fetchParam('show_future', false, null, true),
            'type'        => 'all',
            'conditions'  => trim($this->fetchParam('conditions', null, false, false, false))
        ));

        // limit
        $limit     = $this->fetchParam('limit', null, 'is_numeric');
        $offset    = $this->fetchParam('offset', 0, 'is_numeric');

        if ($limit || $offset) {
            $content_set->limit($limit, $offset);
        }

        // sort
        $sort_by  = $this->fetchParam('sort_by');
        $sort_dir = $this->fetchParam('sort_dir');

        if ($sort_by || $sort_dir) {
            $content_set->sort($sort_by, $sort_dir);
        }

        // check for results
        if (!$content_set->count()) {
            return Parse::template($this->content, array('no_results' => true));
        }

        return Parse::tagLoop($this->content, $content_set->get(), false, $this->context);
    }
}