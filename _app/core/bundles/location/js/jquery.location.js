/**
 * Location
 * for the Statamic Control Panel
 *
 * @author  Fred LeBlanc  <fred@statamic.com>
 * @copyright  2012
 */
;
(function ($) {
	"use strict";

	var defaults, internalData, methods;

	defaults = {
		starting_latitude: 44.33,
		starting_longitude: -68.21,
		starting_zoom: 15,

		allow_geolocation: true,
		geolocation_zoom: 17,
		use_foursquare: false,
		foursquare_client_id: "",
		foursquare_client_secret: "",
		foursquare_radius: 200,

		attribution: '',
		min_zoom: 4,
		max_zoom: 18,
		float_precision: 4,
		common_locations: [],

		interaction_scroll_wheel_zoom: true,
		interaction_double_click_zoom: true,
		interaction_box_zoom: true,
		interaction_touch_zoom: true,
		interaction_draggable: true,
		interaction_tap: true,

		mapping_service: 'mapbox',
		mapping_service_api_key: 'examples.map-zr0njcqy',
		mapping_service_style: null,
		mapping_service_attribution: '',

		detect_retina: true
	};

	internalData = {
		map: {
			element: null,
			object: null
		},
		marker: {
			latitude: null,
			longitude: null,
			object: null
		},
		timeouts: {
			latitude: null,
			longitude: null
		},
		version_date: "20121226"
	};

	methods = {

		// init
		// starts up plugin
		init: function (options) {
			var pluginSettings = $.extend({}, defaults, options, internalData);

			return this
				.each(function () {
					var self = $(this);

					// set settings
					self.data("location", $.extend(true, {}, pluginSettings));

					try {
						methods.verify.apply(self);
						methods.overrideConfiguration.apply(self);
						methods.startMap.apply(self);
						methods.createHelperLinks.apply(self);
						methods.bindInputEvents.apply(self);
					} catch (e) {
						alert("An error occurred trying to initialized the map.");
					}
				});
		},

		// set up methods
		// --------------------------------------------------------------------

		// verify
		// verifies that the structure given is the structure expected
		verify: function () {
			// input-location check
			if (!$(this).is(".input-location")) {
				throw "Cannot verify location input, not an .input-location element.";
			}

			// map check
			if (!$(this).children(".map").length) {
				throw "Cannot verify location input, missing map.";
			}

			// verify that common_locations is an array
			if (!$.isArray($(this).data("location").common_locations)) {
				$(this).data("location").common_locations = [];
			}

			return true;
		},

		// overrideConfiguration
		// overrides the global configuration with field options
		overrideConfiguration: function () {
			var configuration,
				self = $(this),
				settings = self.data("location");

			// grab per-field configuration
			configuration = self.find(".map").data("location-configuration");

			// if no field configuration, get out
			if (!configuration) {
				return;
			}

			self.data("location", $.extend({}, settings, configuration));
		},

		// startMap
		// initialized the map
		startMap: function () {
			var starting_latlng, tiles, options, subdomains, osmLink, mapquestLink, service_attr,
			    mapId = _.uniqueId("map-"),
			    self = $(this),
			    settings = self.data("location");

			if (self.find(".latitude > input").val() !== "" && self.find(".longitude > input").val() !== "") {
				starting_latlng = [parseFloat(self.find(".latitude > input").val()), parseFloat(self.find(".longitude > input").val())];
			} else {
				starting_latlng = [settings.starting_latitude, settings.starting_longitude];
			}

			settings.map.element = self.children(".map").attr("id", mapId);
			settings.map.object = L.map(mapId, {
				scrollWheelZoom: settings.interaction_scroll_wheel_zoom,
				doubleClickZoom: settings.interaction_double_click_zoom,
				boxZoom: settings.interaction_box_zoom,
				touchZoom: settings.interaction_touch_zoom,
				dragging: settings.interaction_draggable,
				tap: settings.interaction_tap
			}).setView(starting_latlng, settings.starting_zoom);

			// attribution re-use
			osmLink = '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
			mapquestLink = 'Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="//developer.mapquest.com/content/osm/mq_logo.png" style="width: auto !important; height: auto !important; display: inline !important; margin: 0 !important; vertical-align: text-bottom;">';

			// mapping service
			if (settings.mapping_service === 'mapbox') {
				tiles = 'https://{s}.tiles.mapbox.com/v3/{key}/{z}/{x}/{y}.png';
				service_attr = ''; 
			} else if (settings.mapping_service === 'cloudmade') {
				tiles = 'http://{s}.tile.cloudmade.com/{key}/{styleId}/256/{z}/{x}/{y}.png';
				service_attr = 'Map data ' + osmAttr + ', Imagery &copy; <a href="http://cloudmade.com">CloudMade</a>';
			} else if (settings.mapping_service === 'opencyclemap') {
				tiles = 'http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png';
				service_attr = '&copy; OpenCycleMap, ' + 'Map data ' + osmLink;
			} else if (settings.mapping_service === 'stamen') {
				tiles = 'http://{s}.tile.stamen.com/{styleId}/{z}/{x}/{y}.jpg';
				service_attr = 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. Data by <a href="http://openstreetmap.org">OpenStreetMap</a>, under <a href="http://creativecommons.org/licenses/by-sa/3.0">CC BY SA</a>.';
			} else if (settings.mapping_service === 'mapquest') {
				tiles = 'http://otile{s}.mqcdn.com/tiles/1.0.0/{styleId}/{z}/{x}/{y}.png';
				service_attr = (settings.mapping_service_style === 'sat') ? 'Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency, ' + mapquestLink : osmLink + ' ' + mapquestLink;
				subdomains = '1234';
			} else {
				tiles = '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
				service_attr = osmLink;
			}
			
			// set up options
			options = {
				attribution: (settings.mapping_service_attribution) ? settings.mapping_service_attribution : service_attr,
				maxZoom: settings.max_zoom,
				minZoom: settings.min_zoom,
				key: settings.mapping_service_api_key,
				styleId: settings.mapping_service_style,
				detectRetina: settings.detect_retina
			};
			
			if (subdomains) {
				options.subdomains = subdomains;
			}
			
			// create tile layer
			L.tileLayer(tiles, options).addTo(settings.map.object);

			// bind map events
			methods.bindMapEvents.apply(self);

			if (self.find(".latitude > input").val() && self.find(".longitude > input").val()) {
				methods.placeMarker.apply(self, [parseFloat(self.find(".latitude > input").val()), parseFloat(self.find(".longitude > input").val())]);
			}
		},

		// createHelperLinks
		// creates and binds events to helper links
		createHelperLinks: function () {
			var self = $(this),
				settings = self.data("location");

			// make sure we have a place to put links
			if (self.find(".helpers").length) {
				return;
			}

			self
				.find(".entry")
				.append('<div class="helpers"><section><label>Helpful Map Tools</label><ul></ul></section></div>');

			// optionally add common locations
			if (settings.common_locations.length && $("#location-selector").length) {
				self.find(".helpers ul").append('<li class="common-locations"><a href="#">Common Places</a></li>');
			}

			// add address finder
			self.find(".helpers ul").append('<li class="locate-address"><a href="#">Locate an Address</a></li>');

			// optionally add my location
			if (settings.allow_geolocation && methods.supportsGeolocation.apply(self)) {
				self.find(".helpers ul").append('<li class="use-my-location"><a href="#">Use My Location</a><span></span></li>');
			}

			self.find(".helpers").append('<section><label>Reset</label><ul></ul></section>');
			self.find(".helpers ul").eq(1).append('<li class="remove-marker"><a href="#">Remove Marker</a></li>');

			// check for existing marker
			if (!settings.marker.object) {
				methods.disableHelperLink.apply(self, ["remove-marker"]);
			}

			self
				.find(".helpers .common-locations a")
				.on("click", function () {
					methods.showCommonPlaces.apply(self);
					return false;
				})
				.end()
				.find(".helpers .remove-marker a")
				.on("click", function () {
					methods.removeMarker.apply(self);
					return false;
				})
				.end()
				.find(".helpers .locate-address a")
				.on("click", function () {
					methods.showAddressLookup.apply(self);
					return false;
				})
				.end()
				.find(".helpers .use-my-location a")
				.on("click", function () {
					methods.findMe.apply(self);
					return false;
				});
		},

		// bindInputEvents
		// binds events to inputs
		bindInputEvents: function () {
			var self = $(this);

			self
				.find(".latitude > input, .longitude > input")
				.on("keyup", function () {
					methods.attemptPlaceMarker.apply(self, []);
				});
		},

		// internal helper methods
		// --------------------------------------------------------------------

		// enableHelper
		// enables a helper link
		enableHelperLink: function (helper) {
			$(this).find("." + helper + " a").removeClass("disabled");
		},

		// disableHelper
		// disabled a helper link
		disableHelperLink: function (helper) {
			$(this).find("." + helper + " a").addClass("disabled");
		},

		// supportsGeolocation
		// does this user's browser support geolocation?
		supportsGeolocation: function () {
			return 'geolocation' in navigator;
		},

		// fillForm
		// fills the form with a given data object
		fillForm: function (data) {
			var zoom = data.zoom || null,
				self = $(this);

			if (data.name) {
				self.find(".name input").val(data.name);
			}

			if (data.latitude) {
				self.find(".latitude > input").val(data.latitude);
			}

			if (data.longitude) {
				self.find(".longitude > input").val(data.longitude);
			}

			methods.attemptPlaceMarker.apply(self, [zoom]);
		},

		// standardizeCoordinates
		// standardizes coordinates to float values
		standardizeCoordinate: function (coordinate) {
			if (_.isNumber(coordinate) && !isNaN(coordinate)) {
				return coordinate;
			} else if (_.isString(coordinate)) {
				return parseFloat(coordinate);
			}

			return 0;
		},

		// helper link actions
		// ----------------------------------------------------------------------

		// findMe
		// find user's current location
		findMe: function () {
			var self = $(this),
				settings = self.data("location"),
				spinElement = self.find(".use-my-location span");

			if (!methods.supportsGeolocation.apply(self)) {
				return false;
			}

			// start up the spinner
			spinElement.spin();

			navigator.geolocation.getCurrentPosition(function (position) {
				var latitude = position.coords.latitude.toFixed(settings.float_precision),
					longitude = position.coords.longitude.toFixed(settings.float_precision);

				// we found something, should we grab the nearest name from foursquare?
				try {
					methods.getNearestFoursquareVenue.apply(self, [latitude, longitude]);
				} catch (e) {
					methods.placeMarker.apply(self, [latitude, longitude, true, settings.geolocation_zoom]);
				}

				// shut off the spinner
				spinElement.spin(false);

			}, function (error) {
				switch (error.code) {
					case 1:
						alert("We can’t find you, you wouldn’t let us.");
						break;

					case 2:
						alert("We can’t find you, the network isn’t available.");
						break;

					case 3:
						alert("We can’t find you, the network is taking too long.");
						break;
				}
			});
		},

		// getNearestFoursquareVenue
		// looks up the nearest foursquare venue
		getNearestFoursquareVenue: function (latitude, longitude) {
			var self = $(this),
				settings = self.data("location");

			if (!settings.foursquare_client_id || !settings.foursquare_client_secret) {
				throw "Missing Foursquare credentials.";
			}

			$.getJSON(
				'https://api.foursquare.com/v2/venues/search',
				{
					ll: latitude + ',' + longitude,
					client_id: settings.foursquare_client_id,
					client_secret: settings.foursquare_client_secret,
					radius: settings.foursquare_radius,
					v: settings.version_date
				},
				function (data) {
					console.log(data);
					if (data.meta.code === 200 && data.response.venues.length && data.response.venues[0].name) {
						self.find(".name input").val(data.response.venues[0].name);
					}
					methods.placeMarker.apply(self, [latitude, longitude, true, settings.geolocation_zoom]);
				}
			);
		},

		// showCommonPlaces
		// shows a list of common places as defined in the options
		showCommonPlaces: function () {
			var self = $(this),
				settings = self.data("location");

			// place modal if not already on screen
			if (!$("#modal-placement").length) {
				$("#wrap").after('<div id="modal-placement"></div>');
			}

			if (!$("#location-modal .modal-body ul li").length) {
				$.each(settings.common_locations, function () {
					$("#location-modal .modal-body ul")
						.append('<li><a href="#" data-name="' + this.name + '" data-latitude="' + this.latitude + '" data-longitude="' + this.longitude + '" data-zoom="' + this.zoom + '">' + this.name + '</a></li>');
				});
			}

			// bind events
			$("#modal-placement")
				.unbind(".location")
				.on("click.location", ".modal-body ul a", function () {
					methods.fillForm.apply(self, [
						{
							name: $(this).data("name"),
							latitude: $(this).data("latitude"),
							longitude: $(this).data("longitude"),
							zoom: $(this).data("zoom")
						}
					]);

					// close
					$("#location-modal").modal("hide");

					return false;
				})
				.html($("#location-selector").html());

			$("#location-modal").modal();
		},

		// showAddressLookup
		// shows the address-lookup modal
		showAddressLookup: function () {
			var self = $(this),
				settings = self.data("location");

			// place modal if not already on screen
			if (!$("#modal-placement").length) {
				$("#wrap").after('<div id="modal-placement"></div>');
			}

			// bind events
			$("#modal-placement")
				.unbind(".location")
				.on("submit.location", "form", function () {
					var address = $(this).find("input#modal-address-field").val();
					
					// loading
					$("#modal-placement form small")
						.stop()
						.animate({
							opacity: 0
						}, 150, function () {
							$(this)
								.text("Looking up your address…")
								.animate({
									opacity: 1
								}, 100);
						});

					$.getJSON(
						"http://open.mapquestapi.com/geocoding/v1/address?key=Fmjtd%7Cluur29ubn9%2C2s%3Do5-908n16&callback=?",
						{
							location: address,
							maxResults: 1,
							thumbMaps: false
						},
						function (data) {
							if (data.results.length && data.results[0].locations.length) {
								methods.fillForm.apply(self, [
									{
										name: data.results[0].providedLocation.location,
										latitude: data.results[0].locations[0].latLng.lat.toFixed(settings.float_precision),
										longitude: data.results[0].locations[0].latLng.lng.toFixed(settings.float_precision)
									}
								]);

								// close
								$("#address-modal").modal("hide");
							} else {
								$("#modal-placement form small")
									.stop()
									.animate({
										opacity: 0
									}, 150, function () {
										$(this)
											.css("color", "#BF1D2D")
											.text("Sorry, we could not find that address.")
											.animate({
												opacity: 1
											}, 150);
									});
							}
						}
					);

					return false;
				})
				.html($("#address-lookup").html());

			$("#address-modal").modal();
		},

		// map actions
		// ----------------------------------------------------------------------

		// attemptPlaceMarker
		// tries to place a marker based on text inputs
		attemptPlaceMarker: function () {
			var latitude, longitude,
				zoom = arguments[0] || null,
				self = $(this);

			latitude = parseFloat(self.find(".latitude > input").val());
			longitude = parseFloat(self.find(".longitude > input").val());

			if (!isNaN(latitude) && !isNaN(longitude)) {
				methods.placeMarker.apply(self, [latitude, longitude, true, zoom]);
			}
		},

		// placeMarker
		// places a marker on the map
		placeMarker: function (latitude, longitude) {
			var recenter = (arguments[2]),
				zoom = (arguments[3]),
				self = $(this),
				settings = self.data("location"),
				markerExists = !!(settings.marker.object);

			// parse floats
			latitude = methods.standardizeCoordinate.apply(self, [latitude]);
			longitude = methods.standardizeCoordinate.apply(self, [longitude]);

			// update display
			if (!self.find(".latitude > input").val().match(/^\d+\.[0]*$/)) {
				self.find(".latitude > input").val(latitude);
			}

			if (!self.find(".longitude > input").val().match(/^\d+\.[0]*$/)) {
				self.find(".longitude > input").val(longitude);
			}

			// create a new marker if one doesn't yet exist
			if (!markerExists) {
				settings.marker.object = L.marker([latitude, longitude], { draggable: true }).addTo(settings.map.object);
				methods.enableHelperLink.apply(self, ["remove-marker"]);
				methods.bindMarkerEvents.apply(self);
			} else {
				settings.marker.object.setLatLng(new L.LatLng(latitude, longitude));
			}

			// optionally center the map around the marker
			if (recenter && latitude && longitude) {
				methods.recenterMap.apply(self, [latitude, longitude, zoom]);
			}
		},

		// recenterMap
		// recenters map around a given latitude and longitude
		recenterMap: function (latitude, longitude) {
			var latlng = new L.LatLng(parseFloat(latitude), parseFloat(longitude)),
				self = $(this),
				settings = self.data("location"),
				zoom = arguments[2] || settings.starting_zoom;

			settings.map.object.panTo(latlng).setZoom(zoom);
		},

		// removeMarker
		// removes a marker from the map
		removeMarker: function () {
			var self = $(this),
				settings = self.data("location");

			// check that there's a marker to remove
			if (!settings.marker.object) {
				return;
			}

			// turn off the 'remove marker' link
			methods.disableHelperLink.apply(self, ["remove-marker"]);

			// remove the marker from the map itself
			settings.map.object.removeLayer(settings.marker.object);

			// unset our internal reference to the marker
			settings.marker.object = null;

			// update the display
			self.find(".latitude > input, .longitude > input").val("");
		},

		// bindMapEvents
		// binds events to the map
		bindMapEvents: function () {
			var self = $(this),
				settings = self.data("location");

			settings.map.object.on("click", function (event) {
				if (!settings.marker.object) {
					methods.placeMarker.apply(self, [event.latlng.lat.toFixed(settings.float_precision), event.latlng.lng.toFixed(settings.float_precision)]);
				}
			});
		},

		// bindMarkerEvents
		// binds events to the marker
		bindMarkerEvents: function () {
			var self = $(this),
				settings = self.data("location");

			// update display while dragging
			settings.marker.object.on("drag", function (event) {
				var latitude = event.target._latlng.lat.toFixed(settings.float_precision),
					longitude = event.target._latlng.lng.toFixed(settings.float_precision);

				methods.placeMarker.apply(self, [latitude, longitude]);
			});

			// update display when marker is dropped
			settings.marker.object.on("dragend", function (event) {
				var latitude = event.target._latlng.lat.toFixed(settings.float_precision),
					longitude = event.target._latlng.lng.toFixed(settings.float_precision);

				methods.placeMarker.apply(self, [latitude, longitude]);
			});
		}

	};

	// start the plugin
	$.fn.location = function (method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof methods === "object" || $.isFunction(method) || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error("Method " + method + " does not exist for jQuery.location");
		}
	};
})(jQuery);