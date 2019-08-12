<?php
/**
 * Fieldtype_location
 * Allows content to be plotted on a map
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 *
 * @copyright  2013
 * @link       http://statamic.com/docs/core-template-tags/entries
 * @license    http://statamic.com/license-agreement
 */
class Hooks_location extends Hooks
{
    /**
     * Adds items to control panel head for certain pages
     *
     * @return string
     */
    public function control_panel__add_to_head()
    {
        if ( ! URL::getCurrent(false) == '/publish') {
            return "";
        }

        $html  = $this->css->link('leaflet.css');
        $html .= "<!--[if lte IE 8]>";
        $html .= $this->css->link('leaflet.ie.css');
        $html .= "<![endif]-->";
        $html .= $this->css->link('override.css');

        return $html;
    }

    /**
     * Adds items to control panel footer for certain pages
     *
     * @return string
     */
    public function control_panel__add_to_foot()
    {
        if (URL::getCurrent(false) == '/publish') {
            $html    = $this->js->link(array('leaflet.js', 'jquery.location.js'));
            $options = json_encode($this->getConfig());

            // modal
            $html .= '
            <div id="location-selector" style="display: none;">
                <div class="modal" id="location-modal">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h3>Common Places</h3>
                    </div>
                    <div class="modal-body">
                        <ul></ul>
                    </div>
                    <div class="modal-footer">
                        &nbsp;
                    </div>
                </div>
            </div>

            <div id="address-lookup" style="display: none;">
                <div class="modal" id="address-modal">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h3>Locate an Address</h3>
                    </div>
                    <div class="modal-body">
                        <form>
                            <p>
                                <label for="modal-address-field">Address</label><br />
                                <input type="text" name="address" id="modal-address-field" value="" />
                                <input type="submit" name="lookup" value="Locate" />
                                <small>
                                    For example: 1120 Pine Grove Road, Gardners, PA
                                </small>
                            </p>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <small>
                            Geocoding Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="//developer.mapquest.com/content/osm/mq_logo.png"><br>
                            © OpenStreetMap contributors — <a href="http://www.openstreetmap.org/copyright">License</a>
                        </small>
                    </div>
                </div>
            </div>
            ';

            $html .= $this->js->inline("
                $(document).ready(function() {
                    var location_options = {$options};
                    $('.input-location').each(function() {
                        $(this).addClass('location-enabled').location(location_options);
                    });

                    // for dynamically loaded rows
                    $('body').on('addRow', '.grid', function() {
                        var input = $(this).find('input[type=text].location');

                        input
                            .each(function() {
                                $(this).location(location_options);
                            });
                    });

                    // now for replicator
                    $('body').on('addSet', function() {
                        $('.input-location').not('.location-enabled').addClass('location-enabled').location(location_options);
                    });
                });
            ");

            return $html;
        }

        return "";
    }
}
