<?php
/**
 * Plugin_location
 * Display helper for locations and maps
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 *
 * @copyright  2014
 * @link       http://statamic.com/docs/
 * @license    http://statamic.com/license-agreement
 */
class Plugin_location extends Plugin {

    var $meta = array(
        'name'       => 'Location',
        'version'    => '1.1',
        'author'     => 'Statamic',
        'author_url' => 'http://statamic.com'
    );


    public function map()
    {
        // parse settings
        $settings = $this->parseParameters();

        $latitude = $this->fetchParam('latitude');
        $longitude = $this->fetchParam('longitude');

        // check for valid center point
        if (preg_match(Pattern::COORDINATES, $this->fetchParam('center_point'), $matches)) {
            $settings['starting_latitude']  = $matches[1];
            $settings['starting_longitude'] = $matches[2];
        } else {
            $settings['starting_latitude']  = $latitude;
            $settings['starting_longitude'] = $longitude;
        }

        // overrides
        $settings['auto_center'] = 'false';
        $settings['clusters'] = 'false';

        $content['marker'] = array(
            'latitude' => $latitude,
            'longitude' => $longitude
        );

        return $this->buildScript($content, $settings);
    }
    
    
    public function map_url()
    {
        // parse settings
        $settings = $this->parseParameters();
        
        // we need to set auto_center to false, here's why:
        // this tag will only ever place one marker on the map, and because of
        // the situation, we'll always know its latitude and longitude; by not
        // setting auto_center to false, starting_zoom no longer works (as the
        // auto_center function will try to determine the best zoom within the
        // allowed min_zoom and max_zoom); setting this to false allows users
        // to start using starting_zoom again without having to worry about any
        // automagic stuff taking over and wrestling away control
        $settings['auto_center'] = 'false';
        
        $content_set = ContentService::getContentAsContentSet($settings['url']);
        
        // supplement
        $content_set->supplement(array(
            'locate_with'     => $settings['locate_with'],
            'center_point'    => $settings['center_point'],
            'pop_up_template' => $this->content
        ));

        // re-filter, we only want entries that have been found
        $content_set->filter(array('located' => true));
        $content = $content_set->get();

        // no results? no results.
        if (!count($content)) {
            return Parse::template($this->content, array('no_results' => true));
        }
        
        // check for valid center point
        if (!preg_match(Pattern::COORDINATES, $settings['center_point'], $matches)) {
            if (
                $settings['locate_with'] && 
                isset($content[0][$settings['locate_with']]) && 
                is_array($content[0][$settings['locate_with']]) &&
                isset($content[0][$settings['locate_with']]['latitude']) &&
                isset($content[0][$settings['locate_with']]['longitude'])
            ) {
                $settings['starting_latitude'] = $content[0][$settings['locate_with']]['latitude'];
                $settings['starting_longitude'] = $content[0][$settings['locate_with']]['longitude'];
            }
        } else {
            $settings['starting_latitude']  = $matches[1];
            $settings['starting_longitude'] = $matches[2];
        }
        
        // other overrides
        $settings['clusters'] = 'false';
        
        return $this->buildScript($content, $settings);
    }


    /**
     * List entries
     */
    public function map_listing()
    {
        // parse settings
        $settings = $this->parseParameters();

        // check for valid center point
        if (preg_match(Pattern::COORDINATES, $settings['center_point'], $matches)) {
            $settings['starting_latitude']  = $matches[1];
            $settings['starting_longitude'] = $matches[2];
        }

        // get related content
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

        $content_set->prepare(false, true);
        $content = $content_set->get();
        
        return $this->buildScript($content, $settings);
    }
    
    
    private function buildScript($content, $settings)
    {
        $classes = ($settings['map_class']) ? ' class="' . $settings['map_class'] . '"' : '';
        
        $html  = '<div class="map' . $classes . '" id="' . $settings['map_id'] . '"></div>';
        $html .= "\n";

        $html .= '<script type="text/javascript">';
        $html .= "try{_location_maps.length;}catch(e){var _location_maps={};}\n";
        $html .= '_location_maps["' . $settings['map_id'] . '"] = { markers: [ ';

        $markers = array();
        foreach ($content as $item) {
            $marker = array(
                'latitude'       => $item['latitude'],
                'longitude'      => $item['longitude'],
                'marker_content' => Content::parse($this->content, $item, "html")
            );

            array_push($markers, json_encode($marker));
        }
        $html .= join(",\n", $markers);

        $html .= '    ], ';
        $html .= ' clusters: ' . $settings['clusters'] . ',';

        // cluster options
        $html .= ' spiderfy_on_max_zoom: ' . $settings['spiderfy_on_max_zoom'] . ',';
        $html .= ' show_coverage_on_hover: ' . $settings['show_coverage_on_hover'] . ',';
        $html .= ' zoom_to_bounds_on_click: ' . $settings['zoom_to_bounds_on_click'] . ',';
        $html .= ' single_marker_mode: ' . $settings['single_marker_mode'] . ',';
        $html .= ' animate_adding_markers: ' . $settings['animate_adding_markers'] . ',';
        $html .= ' disable_clustering_at_zoom: ' . $settings['disable_clustering_at_zoom'] . ',';
        $html .= ' max_cluster_radius: ' . $settings['max_cluster_radius'] . ',';

        // map settings
        $html .= ' starting_latitude: ' . $settings['starting_latitude'] . ',';
        $html .= ' starting_longitude: ' . $settings['starting_longitude'] . ',';
        $html .= ' starting_zoom: ' . $settings['starting_zoom'] . ',';

        $html .= ' mapping_tiles: \'' . $settings['tiles'] . '\',';
        $html .= ' mapping_subdomains: \'' . $settings['subdomains'] . '\',';
        $html .= ' attribution: \'' . $settings['attribution'] . '\',';
        $html .= ' mapping_api_key: \'' . $settings['mapping_service_api_key'] . '\',';
        $html .= ' mapping_style: \'' . $settings['mapping_service_style'] . '\',';

        $html .= ' min_zoom: ' . $settings['min_zoom'] . ',';
        $html .= ' max_zoom: ' . $settings['max_zoom'] . ',';

        $html .= ' scroll_wheel_zoom: ' . $settings['interaction_scroll_wheel_zoom'] . ',';
        $html .= ' double_click_zoom: ' . $settings['interaction_double_click_zoom'] . ',';
        $html .= ' box_zoom: ' . $settings['interaction_box_zoom'] . ',';
        $html .= ' touch_zoom: ' . $settings['interaction_touch_zoom'] . ',';
        $html .= ' draggable: ' . $settings['interaction_draggable'] . ',';
        $html .= ' tap: ' . $settings['interaction_tap'] . ',';
        $html .= ' open_popup: ' . $settings['open_popup'] . ',';
        $html .= ' auto_center: ' . $settings['auto_center'] . '};';

        $html .= '</script>';
        
        // mark that the build script has run, and thus, smart_include should include
        $this->blink->set('maps_used', true);
        
        return $html;
    }
    
    
    private function parseParameters()
    {
        // determine folder
        $folders = array('folders' => $this->fetchParam('folder', $this->fetchParam('folders', ltrim($this->fetchParam('from', URL::getCurrent()), "/"))));
        
        // url
        $url = array(
            'url' => $this->fetchParam('url', URL::getCurrent())  
        );
        
        // filters
        $filters = array(
            'show_hidden' => $this->fetchParam('show_hidden', false, null, true, false),
            'show_drafts' => $this->fetchParam('show_drafts', false, null, true, false),
            'since'       => $this->fetchParam('since'),
            'until'       => $this->fetchParam('until'),
            'show_past'   => $this->fetchParam('show_past', true, null, true),
            'show_future' => $this->fetchParam('show_future', false, null, true),
            'type'        => $this->fetchParam('type', 'all', false, false, true),
            'conditions'  => trim($this->fetchParam('conditions', null, false, false, false))
        );

        // other
        $other = array(
            'taxonomy'  => $this->fetchParam('taxonomy', false, null, true, null),
            'sort_by'   => $this->fetchParam('sort_by', 'order_key'),
            'sort_dir'  => $this->fetchParam('sort_dir')
        );
        
        // map settings
        $map = array(
            'map_id'       => $this->fetchParam('map_id', Helper::getRandomString(), false, false, false),
            'map_class'    => $this->fetchParam('map_class', null, false, false, false),
            'locate_with'  => $this->fetch('locate_with', null, false, false, true),
            'center_point' => $this->fetch('center_point', null, false, false, false),
            
            // starting position
            'starting_zoom'      => $this->fetch('starting_zoom', 12, 'is_numeric', false, false),
            'starting_latitude'  => $this->fetch('starting_latitude', null, 'is_numeric', false, false),
            'starting_longitude' => $this->fetch('starting_longitude', null, 'is_numeric', false, false),
            
            // display
            'mapping_service'             => $this->fetch('mapping_service', 'openstreetmap', null, false, true),
            'mapping_service_api_key'     => $this->fetch('mapping_service_api_key', null, null, false, true),
            'mapping_service_style'       => $this->fetch('mapping_service_style', null, null, false, true),
            'mapping_service_attribution' => $this->fetch('mapping_service_attribution', null, null, false, true),
  
            // limitations
            'min_zoom' => $this->fetch('min_zoom', 4, 'is_numeric', false, false),
            'max_zoom' => $this->fetch('max_zoom', 18, 'is_numeric', false, false),
            
            // interactions
            'interaction_scroll_wheel_zoom' => ($this->fetch('interaction_scroll_wheel_zoom', false, null, true)) ? 'true' : 'false',
            'interaction_double_click_zoom' => ($this->fetch('interaction_double_click_zoom', true, null, true)) ? 'true' : 'false',
            'interaction_box_zoom'          => ($this->fetch('interaction_box_zoom', true, null, true)) ? 'true' : 'false',
            'interaction_touch_zoom'        => ($this->fetch('interaction_touch_zoom', true, null, true)) ? 'true' : 'false',
            'interaction_draggable'         => ($this->fetch('interaction_draggable', true, null, true)) ? 'true' : 'false',
            'interaction_tap'               => ($this->fetch('interaction_tap', true, null, true)) ? 'true' : 'false',
            'open_popup'                    => ($this->fetchParam('open_popup', false, null, true)) ? 'true' : 'false',
            'auto_center'                   => ($this->fetch('auto_center', true, null, true)) ? 'true' : 'false',
            
            // clustering
            'clusters'                   => ($this->fetch('clusters', true, null, true)) ? 'true' : 'false',
            'spiderfy_on_max_zoom'       => ($this->fetch('spiderfy_on_max_zoom', true, null, true)) ? 'true' : 'false',
            'show_coverage_on_hover'     => ($this->fetch('show_coverage_on_hover', true, null, true)) ? 'true' : 'false',
            'zoom_to_bounds_on_click'    => ($this->fetch('zoom_to_bounds_on_click', true, null, true)) ? 'true' : 'false',
            'single_marker_mode'         => ($this->fetch('single_marker_mode', false, null, true)) ? 'true' : 'false',
            'animate_adding_markers'     => ($this->fetch('animate_adding_markers', true, null, true)) ? 'true' : 'false',
            'disable_clustering_at_zoom' => $this->fetch('disable_clustering_at_zoom', 15, 'is_numeric', false, false),
            'max_cluster_radius'         => $this->fetch('max_cluster_radius', 80, 'is_numeric', false, false)
        );

        // mapping service
        $service = $this->getMappingServiceVariables($map);
        $mapping = array(
            'tiles' => $service['tiles'],
            'attribution' => ($map['mapping_service_attribution']) ? $map['mapping_service_attribution'] : $service['service_attr'],
            'subdomains' => (isset($service['subdomains'])) ? $service['subdomains'] : null
        );

        return $other + $filters + $folders + $url + $map + $mapping;
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

            // supplement
            $content_set->supplement(array(
                'locate_with'     => $settings['locate_with'],
                'center_point'    => $settings['center_point'],
                'pop_up_template' => $this->content
            ));

            // re-filter, we only want entries that have been found
            $content_set->filter(array('located' => true));

            // sort
            $content_set->sort($settings['sort_by'], $settings['sort_dir']);

            // store content as blink content for future use
            $this->blink->set($content_hash, $content_set->extract());
        }

        return $content_set;
    }
    
    
    public function getMappingServiceVariables($settings)
    {
        $osm      = '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
        $mapquest = 'Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="//developer.mapquest.com/content/osm/mq_logo.png" style="width: auto !important; height: auto !important; display: inline !important; margin: 0 !important; vertical-align: text-bottom;">';
        
        switch ($settings['mapping_service']) {
            case 'mapbox':
                return array(
                    'tiles' => 'https://{s}.tiles.mapbox.com/v3/{key}/{z}/{x}/{y}.png',
                    'service_attr' => ''
                );
                break;
            
            case 'cloudmade':
                return array(
                    'tiles' => 'http://{s}.tile.cloudmade.com/{key}/{styleId}/256/{z}/{x}/{y}.png',
                    'service_attr' => 'Map data ' . $osm . ', Imagery &copy; <a href="http://cloudmade.com">CloudMade</a>'
                );
                break;
            
            case 'opencyclemap':
                return array(
                    'tiles' => 'http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png',
                    'service_attr' => '&copy; OpenCycleMap, ' . 'Map data ' . $osm
                );
                break;
            
            case 'stamen':
                return array(
                    'tiles' => 'http://{s}.tile.stamen.com/{styleId}/{z}/{x}/{y}.png',
                    'service_attr' => 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. Data by <a href="http://openstreetmap.org">OpenStreetMap</a>, under <a href="http://creativecommons.org/licenses/by-sa/3.0">CC BY SA</a>.'
                );
                break;
            
            case 'mapquest':
                return array(
                    'tiles' => 'http://otile{s}.mqcdn.com/tiles/1.0.0/{styleId}/{z}/{x}/{y}.png',
                    'service_attr' => ($settings['mapping_service_style'] === 'sat') ? 'Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency, ' . $mapquest : $osm . ' ' . $mapquest,
                    'subdomains' => '1234'
                );
                break;
            
            default:
                return array(
                    'tiles' => '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    'service_attr' => $osm
                );
                break;
        }
    }


    /**
     * Initialized maps by adding dependencies to the screen
     *
     * @return string
     */
    public function start_maps() {
        $smart_include = $this->fetchParam('smart_include', false, null, true, false);
        
        // if smart_include is on and maps haven't been used on this page,
        // short-circuit this method and return nothing
        if ($smart_include && !$this->blink->get('maps_used')) {
            return '';
        }
        
        $add_on_path = Path::tidy(Config::getSiteRoot() . Config::getAddOnPath("location"));
        $override    = (File::exists($add_on_path . '/css/override.css')) ? '<link href="' . $add_on_path . '/css/override.css" rel="stylesheet" />' : '';

        return '
            <!-- leaflet maps -->
            <link rel="stylesheet" href="' . ENVIRONMENT_PATH_PREFIX . $add_on_path . '/css/leaflet.css">
            <!--[if lte IE 8]>
                <link rel="stylesheet" href="' . ENVIRONMENT_PATH_PREFIX . $add_on_path . '/css/leaflet.ie.css">
            <![endif]-->
            ' . $override . '
            <script type="text/javascript" src="' . ENVIRONMENT_PATH_PREFIX . $add_on_path . '/js/leaflet.js"></script>
            <script>
                try {
                    if (typeof _location_maps !== "object") {
                        throw ("Out.");
                    }

                    for (var id in _location_maps) {
                        if (!_location_maps.hasOwnProperty(id)) {
                            continue;
                        }

                        try {
                            _location_maps_maps.length;
                        } catch(e) {
                            var _location_maps_maps = {}
                        }
                        
                        var _map = _location_maps[id];
                        
                        // variables
                        var bounds, mapOptions = {}, points = [];
                        
                        // do we need to determine the center point?
                        if (_map.auto_center && _map.markers.length) {
                            for (var i = 0; i < _map.markers.length; i++) {
                                points.push([_map.markers[i].latitude, _map.markers[i].longitude]);
                            }
                            bounds = new L.LatLngBounds(points);
                        }

                        _location_maps_maps[id] = L.map(id, {
                            scrollWheelZoom: _map.scroll_wheel_zoom,
                            doubleClickZoom: _map.double_click_zoom,
                            boxZoom: _map.box_zoom,
                            touchZoom: _map.touch_zoom,
                            dragging: _map.draggable,
                            tap: _map.tap
                        });
                        
                        if (bounds) {
                            _location_maps_maps[id].fitBounds(bounds, {
                                padding: [60, 48],
                                maxZoom: _map.max_zoom
                            });
                        } else {
                            _location_maps_maps[id].setView([_map.starting_latitude, _map.starting_longitude], _map.starting_zoom);
                        }
                        
                        mapOptions = {
                            attribution: _map.attribution,
                            key: _map.mapping_api_key,
                            styleId: _map.mapping_style,
                            minZoom: _map.min_zoom,
                            maxZoom: _map.max_zoom
                        };
                        
                        if (_map.mapping_subdomains) {
                            mapOptions.subdomains = _map.mapping_subdomains;
                        }

                        L.tileLayer(_map.mapping_tiles, mapOptions).addTo(_location_maps_maps[id]);

                        // markers
                        if (_map.markers.length) {
                            // use cluster markers
                            if (_map.clusters) {
                                var _marker_clusters = new L.MarkerClusterGroup({
                                    spiderfy_on_max_zoom: _map.spiderfy_on_max_zoom,
                                    show_coverage_on_hover: _map.show_coverage_on_hover,
                                    zoom_to_bounds_on_click: _map.zoom_to_bounds_on_click,
                                    single_marker_mode: _map.single_marker_mode,
                                    animate_adding_markers: _map.animate_adding_markers,
                                    disable_clustering_at_zoom: _map.disable_clustering_at_zoom,
                                    max_cluster_radius: _map.max_cluster_radius
                                });

                                for (var i = 0; i < _map.markers.length; i++) {
                                    var _marker_data  = _map.markers[i],
                                        _local_marker = new L.marker([_marker_data.latitude, _marker_data.longitude]);

                                    if (_marker_data.marker_content) {
                                        _local_marker.bindPopup(_marker_data.marker_content);
                                    }
                                    
                                    if (_map.open_popup) {
                                        _local_marker.openPopup();
                                    }

                                    _marker_clusters.addLayer(_local_marker);
                                }
                                _location_maps_maps[id].addLayer(_marker_clusters);

                            // use regular markers
                            } else {
                                var _local_marker;
                                for (var i = 0; i < _map.markers.length; i++) {
                                    var _marker_data = _map.markers[i];
                                    _local_marker = L.marker([_marker_data.latitude, _marker_data.longitude]).addTo(_location_maps_maps[id]);

                                    if (_marker_data.marker_content) {
                                        _local_marker.bindPopup(_marker_data.marker_content);
                                    }
                                    
                                    if (_map.open_popup) {
                                        _local_marker.openPopup();
                                    }
                                }
                            }
                        }
                    }
                } catch(e) {}
            </script>
        ';
    }
}