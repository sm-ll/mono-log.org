<?php
/**
 * Fieldtype_location
 * Allows content to be plotted on a map
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 *
 * @copyright  2012
 * @link       http://statamic.com/docs/core-template-tags/entries
 * @license    http://statamic.com/license-agreement
 */
class Fieldtype_location extends Fieldtype
{
    /**
     * Meta data for this fieldtype
     * @var array
     */
    public $meta = array(
        'name'       => 'Location',
        'version'    => '1.0',
        'author'     => 'Statamic',
        'author_url' => 'http://statamic.com'
    );


    /**
     * Allowed settings
     * @var array
     */
    public $allowed_settings = array();


    /**
     * Initialize the location field
     *
     * @return void
     */
    function init()
    {
        $this->allowed_settings = array(
            'starting_latitude',
            'starting_longitude',
            'starting_zoom',
            'min_zoom',
            'max_zoom',
            'float_precision',
            'allow_geolocation',
            'geolocation_zoom',
            'use_foursquare',
            'foursquare_client_id',
            'foursquare_client_secret',
            'foursquare_radius',
            'interaction',
            'cloudmade_api_key',
            'cloudmade_tile_set_id',
            'detect_retina',
            'common_locations'
        );
    }


    /**
     * Renders the field
     *
     * @return string
     */
    function render()
    {
        $random_keys = array(
            'name'      => $this->field_id,
            'latitude'  => Helper::getRandomString(),
            'longitude' => Helper::getRandomString()
        );

        $values = array(
            'name'      => (isset($this->field_data['name'])) ? $this->field_data['name'] : '',
            'latitude'  => (isset($this->field_data['latitude'])) ? $this->field_data['latitude'] : '',
            'longitude' => (isset($this->field_data['longitude'])) ? $this->field_data['longitude'] : ''
        );

        $html = '<div class="map"';

        // add in per-field settings
        $settings = array();
        foreach ($this->field_config as $setting => $value) {
            if (!in_array($setting, $this->allowed_settings) || is_null($value)) {
                continue;
            }

            $settings[$setting] = $value;
        }

        // if we found something, add the configuration to the element
        if (count($settings)) {
            $html .= " data-location-configuration='" . json_encode($settings) . "'";
        }

        $field_name = str_replace('page[', '', $this->fieldname) . '[';

        $html .= '></div>';
        $html .= '<div class="entry">';

        $html .= '	<div class="name">';
        $html .= '		<p>';
        $html .= Fieldtype::render_fieldtype('text', $field_name . 'name', array('display' => Localization::fetch('location_name')), $values['name'], NULL, NULL, $random_keys['name']);
        $html .= '		</p>';
        $html .= '	</div>';

        $html .= '	<div class="coordinates">';
        $html .= '		<p class="latitude">';
        $html .= Fieldtype::render_fieldtype('text', $field_name . 'latitude', array('display' => Localization::fetch('latitude')), $values['latitude'], NULL, NULL, $random_keys['latitude']);
        $html .= '		</p>';
        $html .= '		<p class="longitude">';
        $html .= Fieldtype::render_fieldtype('text', $field_name . 'longitude', array('display' => Localization::fetch('longitude')), $values['longitude'], NULL, NULL, $random_keys['longitude']);
        $html .= '		</p>';
        $html .= '	</div>';

        $html .= '</div>';

        return $html;
    }


    /**
     * Processes the field data
     *
     * @return string
     */
    function process()
    {
        foreach ($this->field_data as $field => $data) {
            $this->field_data[$field] = Fieldtype::process_field_data('text', $data);
        }

        return $this->field_data;
    }
}
