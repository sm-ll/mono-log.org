<?php
class Plugin_get_content extends Plugin
{
    public function index()
    {
        $from = $this->fetchParam('from', false);

        if ( ! $from) return null;

        // account for subfolder
        if (strpos($from, Config::getSiteRoot()) !== 0) {
            $from = Path::tidy(Config::getSiteRoot() . $from);
        }
        
        $from = Path::addStartingSlash($from);

        $content_set = ContentService::getContentByURL($from);

        // filter
        $content_set->filter(array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'show_past'   => $this->fetchParam('show_past', true, null, true),
            'show_future' => $this->fetchParam('show_future', false, null, true),
            'type'        => 'all',
            'conditions'  => trim($this->fetchParam('conditions', null, false, false, false))
        ));
        
        // limit
        $limit  = $this->fetchParam('limit', 1, 'is_numeric');
        $offset = $this->fetchParam('offset', 0, 'is_numeric');
        
        $content_set->limit($limit, $offset);
        
        // check for results
        if (!$content_set->count()) {
            return Parse::tagLoop($this->content, array(array('no_results' => true)));
        }

        // if content is used in this entries loop, parse it
        $parse_content = (bool) preg_match(Pattern::USING_CONTENT, $this->content);
        return Parse::tagLoop($this->content, $content_set->get($parse_content), false, $this->context);
    }

}
