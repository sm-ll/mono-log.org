<?php
class Plugin_taxonomy extends Plugin
{

    public function listing()
    {
        // grab a taxonomy set from the content service
        $taxonomy_set = ContentService::getTaxonomiesByType($this->fetchParam('type', null));

        // folders
        $folders = $this->fetchParam('folder', $this->fetchParam('folders', ltrim($this->fetchParam('from', URL::getCurrent()), "/")));

        // now filter that down to just what we want
        $taxonomy_set->filter(array(
            'folders'     => $folders,
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'since'       => $this->fetchParam('since', null),
            'until'       => $this->fetchParam('until', null),
            'min_count'   => $this->fetchParam('min_count', 1, 'is_numeric'),
            'show_future' => $this->fetchParam('show_future', false, null, true, false),
            'show_past'   => $this->fetchParam('show_past', true, null, true, false),
            'conditions'  => trim($this->fetchParam('conditions', null, false, false, false)),
            'where'       => trim($this->fetchParam('where', null, false, false, false))
        ));

        // sort as needed
        $taxonomy_set->sort($this->fetchParam('sort_by', 'name'), $this->fetchParam('sort_dir', 'asc'));

        // trim to limit the number of results
        $taxonomy_set->limit($this->fetchParam('limit', null, 'is_numeric'));

        // contextualize the urls to the given folder
        $taxonomy_set->contextualize($this->fetchParam('folder', null));
        $output = $taxonomy_set->get();

        // no results found, return so
        if (!count($output)) {
            return array('no_results' => true);

        }
        // results found, parse the tag loop with our content
        return Parse::tagLoop($this->content, $output, true, $this->context);
    }
}
