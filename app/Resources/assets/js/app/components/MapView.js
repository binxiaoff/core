/*
 * Unilend MapView
 * Generic map view component that can show mapbox, show markers & cluster them in groups
 * @note I've tried to develop without bias toward projects
 */

// @todo fix group click interaction when multiple groups share same location
// @todo hide group filter if group has no markers
// @todo set maxBounds to region

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')

// Dictionary
var Dictionary = require('Dictionary')
var __ = new Dictionary(window.MAPVIEW_LANG)

// Other depencendies
// See: https://github.com/mapbox/mapbox.js/#usage-with-browserify
// require('leaflet.markercluster')
// var L = require('mapbox.js')
var L = window.L

// Unilend access token
if (L && L.mapbox) {
  L.mapbox.accessToken = 'pk.eyJ1IjoidW5pbGVuZCIsImEiOiJjaXJiczA4ejQwMDVvaWZsdzdmMmpnOGRtIn0._IBtG2TUy17m7S6jwVBrcg'
}

// Private variables
var groupColors = {
  'all': '#b20066', // @scss-color $c-purple
  'active': '#b20066', // @scss-color $c-purple
  'expired': '#dedcdf' // @scss-color $c-grey-l2
}

// Private methods
function sanitiseName (input) {
  return (input + '').replace(/\W+/g, '').toLowerCase().trim()
}

/*
 * MapView
 * @class
 */
var MapView = function (elem, options) {
  var self = this
  self.$elem = $(elem).first()

  // Default options to empty object if none given
  options = options || {}

  // Error: no element to apply mapview to
  if (self.$elem.length === 0 || elem.hasOwnProperty('MapView')) return false

  // Settings
  self.settings = Utility.inherit({
    // The target element (assume elem given)
    target: elem,

    // Mapbox.js style/tilejson URL
    style: 'mapbox://styles/unilend/cirbsyowb001sh2m19mq5rjwr',

    // Show group filters: only shows for groups which have `showFilter == true`
    showFilters: true,

    // Object with all Mapbox.js options (correspond to options in `L.mapbox.map` call)
    mapbox: {
      center: [46.584350, 2.570801], // France
      zoom: 6, // starting zoom
      maxZoom: 18, // Required for MarkerCluster
      shareControl: false,
      infoControl: false,
      legendControl: false,
      zoomControl: true,
      attributionControl: {
        compact: true
      }
    },

    // Extra
    mapboxExtras: {
      zoom: {
        options: {
          position: 'topleft'
        }
      },
      geocoder: {
        url: 'mapbox.places',
        options: {
          keepOpen: false,
          position: 'topright'
        }
      }
    },

    // Specify cluster groups to put markers into. Default is `active` and `expired`
    groups: [{
      name: 'active',
      label: __.__('Active', 'group-label-active'),
      showFilter: true,
      visible: true,

      // Filter options to pass to MapViewFilter
      filterOptions: {},

      // Options to pass to the L.MarkerClusterGroup
      options: {
        iconCreateFunction: function (cluster) {
          return new L.DivIcon({
            iconSize: [30, 30],
            iconAnchor: [0, 0],
            html: '<div class="mapview-marker-group mapview-marker-group-active">' + cluster.getChildCount() + '</div>'
          });
        },
        polygonOptions: {
          fillColor: '#b20066',
          color: '#b20066',
          weight: 2,
          opacity: 1,
          fillOpacity: 0.5
        }
      }
    },{
      name: 'expired',
      label: __.__('Expired', 'group-label-expired'),
      showFilter: true,
      visible: true,

      // Filter options to pass to MapViewFilter
      filterOptions: {},

      // Options to pass to the L.MarkerClusterGroup
      options: {
        iconCreateFunction: function (cluster) {
          return new L.DivIcon({
            iconSize: [30, 30],
            iconAnchor: [0, 0],
            html: '<div class="mapview-marker-group mapview-marker-group-expired">' + cluster.getChildCount() + '</div>'
          });
        },
        polygonOptions: {
          fillColor: '#dedcdf',
          color: '#dedcdf',
          weight: 2,
          opacity: 1,
          fillOpacity: 0.5
        }
      }
    }],

    // Specify any markers to load into the map on initialisation
    markers: [],

    // Events
    // -- onclick fires when a layer's marker is clicked
    onclick: function (mouseEvent) {
      // @debug
      // console.log(mouseEvent)
      // console.log('map onclick', mouseEvent)

      // I've saved ID info in mouseEvent.layer<L.marker>.options.mapviewItemId
      if (mouseEvent.layer.options.hasOwnProperty('mapviewItemId')) {
        // @debug
        // console.log('MapViewMarker', mouseEvent.layer, mouseEvent.layer.__parent)

        // Select the mapviewItem element
        self.selectMapviewItem(mouseEvent.layer.options.mapviewItemId)
      }
    },

    // -- onclusterclick fires when a layer's cluster group marker is clicked
    onclusterclick: function (mouseEvent) {
      // @debug
      // console.log(mouseEvent)
      // console.log('map onclusterclick', mouseEvent)
    }
  },
  // Whole options object inherited from the element itself
  ElementAttrsObject(elem, 'data-mapview'),
  // Options inherited from element override defaults
  ElementAttrsObject(elem, {
    style: 'data-mapview-style',
    showFilters: 'data-mapview-showfilters',
    mapbox: 'data-mapview-mapbox',
    ajaxUrl: 'data-mapview-ajaxurl',
    ajaxPaginationUrl: 'data-mapview-ajaxpaginationurl'
  }),
  // Options specified in JS call override all the previous
  options)

  // Set this as default value for attribution
  Utility.setObjProp(options, 'mapbox.attributionControl.compact', true)

  // Properties
  // self.initialised = false
  self.map = undefined // Mapbox map
  self.$target = $(self.settings.target)

  // MapViewMarker collection
  self.markers = {}

  // Groups layer and collection (for clustering markers of same status)
  self.groupsLayer = undefined
  self.groupsVisibleLayer = undefined
  self.groups = {}

  // Filters collection (for showing/hiding groups)
  self.$filters = $('<div class="mapview-filters"></div>')
  self.filters = {}

  // Assign class to show component behaviours have been applied
  self.$elem.addClass('ui-mapview')

  // Assign instance of class to the element
  self.$elem[0].MapView = self

  // Initialise MapView's Mapbox (set any markers if necessary)
  self.init(self.settings.markers)

  // @debug
  // console.log('new MapView', self)

  return self
}

/*
 * Prototype properties and methods (shared between all class instances)
 */

/*
 * Initialise the MapView instance with Mapbox
 *
 * @method init
 * @returns {Void}
 */
MapView.prototype.init = function (markerData) {
  var self = this

  // @trigger elem `MapView:init:before` [{MapView}]
  self.$elem.trigger('MapView:init:before', [self])

  // Set the target element to contain the map
  self.$target = $(self.settings.target)

  // Ensure the $target has an ID
  if (!self.$target.attr('id')) self.$target.attr('id', 'mapview' + Utility.randomString())

  // Show the spinner
  // @trigger elem `Spinner:showLoading`
  self.$elem.trigger('Spinner:showLoading')

  // Initialise the map
  // -- Using link to Mapbox style (`mapbox://styles/...`)
  if (/^mapbox\:\/\/styles\//i.test(self.settings.style)) {
    // @debug
    // console.log('MapView.init: load mapbox studio style', self.settings.style)

    self.map = L.mapbox.map(self.$target[0], undefined, self.settings.mapbox)
    L.mapbox.styleLayer(self.settings.style).addTo(self.map)

    // @debug
    // console.log(self.map, self.styleLayer)

  // -- Using TileJSON shorthand ('mapbox.streets') or URL ('http://...')
  } else {
    // @debug
    // console.log('MapView.init: load tilejson', self.settings.style)
    self.map = L.mapbox.map(self.$target[0], self.settings.style, self.settings.mapbox)
  }

  // Error
  if (!self.map) {
    // @trigger elem `MapView:init:error` [elemMapView, errorMsg]
    self.$elem.trigger('MapView:init:error', [self, 'Couldn\'t initialise mapbox'])

    // Hide the spinner
    // @trigger elem `Spinner:hideLoading`
    self.$elem.trigger('Spinner:hideLoading')

    return false
  }

  // Extra options
  // -- Zoom
  if (self.settings.mapbox.zoomControl && self.settings.mapboxExtras.zoom) {
    // Position the zoom
    self.map.zoomControl.setPosition(self.settings.mapboxExtras.zoom.options.position)
  }

  // -- Geocoder
  if (self.settings.mapboxExtras.geocoder) {
    // Create the geocoder
    self.geocoder = L.mapbox.geocoderControl(self.settings.mapboxExtras.geocoder.url, self.settings.mapboxExtras.geocoder.options)
    self.map.addControl(self.geocoder)

    // Position the geocoder
    if (self.settings.mapboxExtras.geocoder.options && self.settings.mapboxExtras.geocoder.options.position) {
      self.geocoder.setPosition(self.settings.mapboxExtras.geocoder.options.position)
    }
  }

  // Add the groups to the map
  self.groupsLayer = L.layerGroup()
  self.groupsVisibleLayer = L.layerGroup().addTo(self.map)

  // Add each group
  if (self.settings.groups.length > 0) {
    $.each(self.settings.groups, function (i, group) {
      // Adding group via group object
      if (typeof group === 'object') {
        self.addGroup(group.name, group)

      // Adding group via string
      } else if (typeof group === 'string') {
        self.addGroup(group)
      }
    })
  }

  // Chech if data needs to be loaded via ajax
  if (self.settings.ajaxUrl) {
    self.getAjaxData(self.settings.ajaxUrl)
  // Complete the mapview initialisation
  } else {
    self.completeInit(markerData)
  }
}

/*
 * Completes Initialisation after Ajax-loaded marker data
 *
 * @method completeInit
 * @returns {Void}
 */
MapView.prototype.completeInit = function (markerData) {
  var self = this

  // Find [data-mapview-item] to compile/generate the marker data to populate with
  if (!markerData || markerData.length === 0) {
    // @debug
    // console.log('Find [data-mapview-item]')

    markerData = []
    $('[data-mapview-item]').each(function (i, elem) {
      var markerItemData = Utility.convertToPrimitive($(elem).attr('data-mapview-item'))
      if (markerItemData) markerData.push(markerItemData)
    })
  }

  // Use markerData to populate markers
  if (markerData) {
    // @debug
    // console.log('Found markerData', markerData)
    self.populateMarkers(markerData)
  }

  // Add the filters DOM element (if not already added)
  if (self.settings.showFilters && !Utility.elemExists(self.$filters)) {
    self.$target.append(self.$filters)
  }

  // Hide the spinner
  // @trigger elem `Spinner:hideLoading`
  self.$elem.trigger('Spinner:hideLoading')

  // @trigger elem `MapView:initialised` [{MapView}]
  self.$elem.trigger('MapView:initialised', [self])
}

/*
 * Load marker data through ajax
 *
 * @method getAjaxData
 * @returns {Void}
 */
MapView.prototype.getAjaxData = function (ajaxUrl) {
  var self = this
  // @debug
  // console.log('Ajax loading url' + ajaxUrl)

  // Retrieve marker data
  $.ajax({
    url: ajaxUrl,
    method: 'POST',
    beforeSend: function() {
      // Using a custom spinner so remove the default one
      $('body').removeClass('ui-is-loading');
    },
    success: function (markerData) {
      // Complete the mapview initialisation
      self.completeInit(markerData)
    },
    error: function(jqXHR) {
      // Handle server errors
      self.completeInit()
      console.log('Error retrieving marker data. Error code: ' + jqXHR.status)
    }
  });
}

/*
 * Pan to a point on the map
 *
 * @method panTo
 * @param {Array} latLng The latitude and logitude of the point
 * @param {Int} zoom The level of zoom
 * @param {Object} options An object containing extra options for the pan
 * @returns {Void}
 */
MapView.prototype.panTo = function (latLng, zoom, options) {
  var self = this
  if (!self.map) return

  // Pan/zoom defaults
  options = $.extend({
    animate: true
  }, options)

  self.map.setView(latLng, zoom, options)
}

/*
 * Zoom the map
 *
 * @method zoom
 * @param {Array} latLng The latitude and logitude of the point
 * @param {Int} zoom The level of zoom
 * @param {Object} options An object containing extra options for the zoom
 * @returns {Void}
 */
MapView.prototype.zoom = function (zoom, options) {
  var self = this
  if (!self.map) return

  // Zoom defaults
  options = $.extend({
    animate: true
  }, options)

  self.map.setZoom(zoom, options)
}

/*
 * Populate map with markers. Can also remove the previous ones first
 *
 * @method populateMarkers
 * @param
 * @returns {Void}
 */
MapView.prototype.populateMarkers = function (markerData, removePreviousMarkers) {
  var self = this

  // @debug
  // console.log('MapView.populateMarkers', markerData)

  // @trigger elem `MapView:populateMarkers:before` [{MapView}, {Array} markerData, {Boolean} removePreviousMarkers]
  self.$elem.trigger('MapView:populateMarkers:before', [self, markerData, removePreviousMarkers])

  // Look within to see if there are any [data-mapview-item] elements with information to display
  if (!markerData || !(markerData instanceof Array)) return

  // Remove any previous markers
  if (removePreviousMarkers) self.removeMarkers()

  // Add markers to the map
  var addedMarkers = []
  $.each(markerData, function (i, markerItemData) {
    var markerItem = self.addMarker(markerItemData)
    if (markerItem) addedMarkers.push(markerItem)
  })

  // If there's only 1 added marker, show it in the map (set zoom and showPopup)
  if (addedMarkers.length === 1) {
    self.showMarker(addedMarkers[0], 13, true)
  }

  // @debug
  // console.log('MapView.populateMarkers', addedMarkers)

  // @trigger elem `MapView:populateMarkers:after` [{MapView} self, {Array} markerData, {Boolean} removePreviousMarkers, {Array} addedMarkers]
  self.$elem.trigger('MapView:populateMarkers:after', [self, markerData, removePreviousMarkers, addedMarkers])
}

/*
 * Add marker
 *
 * @method addMarker
 * @param {Object} options The marker's options
 * @returns {MapViewMarker}
 */
MapView.prototype.addMarker = function (markerData) {
  var self = this

  // Set reference to map in markerData
  markerData.map = self.map

  // Create the MapViewMarker
  var mapViewMarker = new MapViewMarker(self, markerData)

  // Add in the MapView's markers collection
  if (mapViewMarker) {
    self.markers[mapViewMarker.id] = mapViewMarker

    // Add to group
    self.addMarkerToGroup(mapViewMarker)

    return mapViewMarker
  }
}

/*
 * Remove all markers attached to the mapview
 *
 * @method removeMarkers
 * @returns {Void}
 */
MapView.prototype.removeMarkers = function () {
  var self = this

  $.each(self.markers, function (i, mapViewMarker) {
    self.removeMarker(mapViewMarker.id)
  })
}

/*
 * Remove single marker
 *
 * @method removeMarker
 * @param {String} markerId The ID string of the marker
 * @returns {Void}
 */
MapView.prototype.removeMarker = function (markerId) {
  var self = this

  if (self.markers.hasOwnProperty(markerId)) {
    var mapViewmarker = self.markers[markerId]

    // Close popup
    if (mapViewMarker.popup) mapViewMarker.marker.closePopup()

    // Remove marker from group
    self.removeMarkerFromGroup(mapViewMarker)

    // Remove marker from map
    self.map.removeLayer(mapViewMarker.marker)

    // Remove marker
    delete mapViewMarker
    delete self.markers[markerId]
  }
}

/*
 * Get marker
 *
 * @method getMarker
 * @param {String} markerId
 * @returns {Mixed} Either {Boolean} false or {MapViewMarker}
 */
MapView.prototype.getMarker = function (markerId) {
  var self = this

  // Get the MapViewMarker
  if (self.markers.hasOwnProperty(markerId)) return self.markers[markerId]

  return false
}

/*
 * Show marker by panning to it
 *
 * @method showMarker
 * @param {Mixed} marker Can be {String}, {MapViewMarker}, or {Object} to create and add a new marker
 * @param {Int} zoom The level of zoom
 * @param {Boolean} showPopup Show the marker's popup
 * @returns {Void}
 */
MapView.prototype.showMarker = function (marker, zoom, showPopup) {
  var self = this
  var mapViewMarker

  // console.log('MapView.showMarker', marker)

  // Get an existing marker
  if (marker instanceof MapViewMarker) {
    mapViewMarker = marker

  // Get by ID
  } else if (typeof marker === 'string') {
    mapViewMarker = self.getMarker(marker)

  // Create new marker
  } else if (typeof marker === 'object') {
    mapViewMarker = self.addMarker(marker)
  }

  // Close all popups
  self.map.closePopup()

  // Show the marker in the map
  if (mapViewMarker) {
    // @debug
    // console.log('MapView.showMarker', mapViewMarker)

    function showMapViewMarkerPopup () {
      // @debug
      // console.log('show the popup', mapViewMarker)

      // Show the popup by default if not set
      if ((showPopup || typeof showPopup === 'undefined') && mapViewMarker.popup) mapViewMarker.marker.openPopup()
    }

    // At max zoom (clusters need to be expanded/spiderfied)
    if (mapViewMarker.group._map.getZoom() === mapViewMarker.group._maxZoom) {
      // @debug
      // console.log('MapView.showMarker: already at maxZoom', mapViewMarker.group._map.getZoom(), mapViewMarker.group._maxZoom)

      // Pan to the marker's position
      self.panTo(mapViewMarker.latLng, mapViewMarker.group._map.getZoom(), {animate: false})

      // Cluster is different to the group (plus the marker may not be clustered)
      var cluster = mapViewMarker.marker.__parent

      // @debug
      // console.log('mapViewMarker cluster', cluster, mapViewMarker)

      // Unspiderfy any already spiderfied clusters in the groups
      // @note for some reason `$.each` is not working with the self.groups collection, so I'm using regular `for` loop
      for (i in self.groups) {
        if (self.groups[i].hasOwnProperty('_spiderfied') && self.groups[i]._spiderfied !== null && cluster !== self.groups[i]._spiderfied) {
          // No animation since animation times throw out popup invocation
          self.groups[i]._spiderfied._noanimationUnspiderfy()
        }
      }

      // Ensure cluster is spiderfied before showing the marker popup
      // @note Only spiderfy clusters which have no child clusters
      // @note When zoomed in at maxZoom, any non-visible markers are clustered when
      //       `spiderfy` is invoked. Not what we want.
      if (cluster.hasOwnProperty('_childClusters') && cluster._childClusters.length === 0) {
        cluster.spiderfy()
      }

      // Show the marker's popup
      showMapViewMarkerPopup()

    // Zoom to group/marker/layer
    } else {
      mapViewMarker.group.zoomToShowLayer(mapViewMarker.marker, function () {
        showMapViewMarkerPopup()
      })
    }
  }

  // @debug
  // console.log('MapView.showMarker', marker, zoom, showPopup, mapViewMarker)
}

/*
 * Add cluster group to add markers to
 *
 * @method addGroup
 * @param {String} groupName
 * @param {Object} options See code for how to set options for the group
 *                         and how to include L.MarkerClusterGroup options
 * @returns {Mixed} {Boolean} false on error, or {L.MarkerClusterGroup}
 */
MapView.prototype.addGroup = function (groupName, options) {
  var self = this

  // Needs a group name
  if (!groupName) return false
  var groupLabel = groupName
  groupName = sanitiseName(groupName)

  // Check if hasn't already been added
  if (self.groups.hasOwnProperty(groupName)) {
    return self.groups[groupName]
  }

  // @debug
  // console.log('MapView.addGroup', groupName, options)

  // Ensure there's a groupsLayer to add the group to
  if (!self.groupsLayer) {
    console.log('MapView.addGroup: no groupsLayer to add the group to. Are you sure the MapView has been initialised yet?')
    return false
  }

  // Default group settings
  var groupSettings = $.extend({
    name: groupName,
    label: groupLabel, // label for filter button
    showFilter: false, // {Boolean} to enable a filter for the group
    visible: true, // {Boolean} to show the group in the mapview

    // Filter options to pass to MapViewFilter
    filterOptions: {
      name: groupName,
      label: groupLabel,
      type: 'checkbox',
      enabled: true
    },

    // For `L.MarkerClusterGroup` options see:
    // https://github.com/Leaflet/Leaflet.markercluster
    options: {
      // zoomToBoundsOnClick: true,
      iconCreateFunction: function (cluster) {
        return new L.DivIcon({
          // iconSize: [30, 30],
          // iconAnchor: [0, 0],
          html: '<div class="mapview-marker-group mapview-marker-group-' + groupName + '">' + cluster.getChildCount() + '</div>'
        });
      },
      polygonOptions: {
        fillColor: '#dedcdf',
        color: '#dedcdf',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.5
      }
    },

    // Events
    onclick: self.settings.onclick,
    onclusterclick: self.settings.onclusterclick
  }, options)

  // Compulsory group options
  groupSettings.options.zoomToBoundsOnClick = true
  groupSettings.options.spiderfyOnMaxZoom = true

  // Create the group
  var group = new L.MarkerClusterGroup(groupSettings.options)

  // Assign mapView references
  group.MapView = self
  group.mapViewSettings = groupSettings

  // Add to the groupsLayer (so it can be shown/hidden via filters)
  group.addTo(self.groupsLayer)

  // Add to the MapView's groups collection
  self.groups[groupName] = group

  // Enable click event on marker layer within group
  if (groupSettings.onclick && typeof groupSettings.onclick === 'function') {
    group.on('click', groupSettings.onclick)
  }

  // Enable clusterclick event on group later
  if (groupSettings.onclusterclick && typeof groupSettings.onclusterclick === 'function') {
    group.on('clusterclick', groupSettings.onclusterclick)
  }

  // Add to the map
  // group.addTo(self.map)

  // Create the filter for the group
  if (self.settings.showFilters && groupSettings.showFilter) {
    groupSettings.filterOptions.group = group
    var filter = self.addFilter(groupSettings.name, groupSettings.filterOptions)
  }

  // Show the group
  if (groupSettings.visible) self.showGroup(group)

  // @trigger elem `MapView:addGroup:added` [{MapView}, {group}]
  self.$elem.trigger('MapView:addGroup:added', [self, group])

  // @debug
  // console.log('MapView.addGroup', groupName, groupSettings, group)

  return group
}

/*
 * Get a cluster group
 *
 * @method getGroup
 * @param {Mixed} group {String} groupName or {L.MarkerClusterGroup}
 * @returns {Mixed} Either {Boolean} false or {L.MarkerClusterGroup}
 */
MapView.prototype.getGroup = function (groupName) {
  var self = this

  // @debug
  // console.log('MapView.getGroup', groupName)

  if (!groupName) return false

  // Group's already a group!
  if (groupName instanceof L.MarkerClusterGroup) {
    return groupName
  }

  // Get group by group name
  if (typeof groupName === 'string' && self.groups.hasOwnProperty(groupName)) {
    return self.groups[groupName]
  }

  return false
}

/*
 * Show group in the map
 *
 * @method showGroup
 * @param {Mixed} group {String} groupName or {L.MarkerClusterGroup}
 * @returns {Void}
 */
MapView.prototype.showGroup = function (groupName) {
  var self = this
  var group = self.getGroup(groupName)
  if (!group) return false

  // Update group/filter
  group.mapViewSettings.visible = true
  if (group.hasOwnProperty('MapViewFilter')) group.MapViewFilter.switchOn()

  // Show on the map
  self.groupsVisibleLayer.addLayer(group)
  // console.log('showGroup', group)
}

/*
 * Hide group in the map
 *
 * @method hideGroup
 * @param {Mixed} group {String} groupName or {L.MarkerClusterGroup}
 * @returns {Void}
 */
MapView.prototype.hideGroup = function (groupName) {
  var self = this
  var group = self.getGroup(groupName)
  if (!group) return false

  // Update group/filter
  group.mapViewSettings.visible = false
  if (group.hasOwnProperty('MapViewFilter')) group.MapViewFilter.switchOff()

  // Hide from the map
  self.groupsVisibleLayer.removeLayer(group)
  // console.log('hideGroup', group)
}

/*
 * Add a marker to a cluster group.
 * If group doesn't exist, it will add marker to `all` group
 *
 * @method addMarkerToGroup
 * @param {Mixed} marker Can be {String} markerId, {MapViewMarker} or {L.Marker}
 * @param {String} groupName
 * @returns {Boolean}
 */
MapView.prototype.addMarkerToGroup = function (marker, groupName) {
  var self = this
  var group

  // Get an existing marker if it is a string
  if (typeof marker === 'string') {
    marker = self.getMarker(marker)
  }

  // Already added to group
  if (marker.group || (marker.hasOwnProperty('__parent') && marker.__parent.hasOwnProperty('_childClusters'))) {
    console.log('MapView.addMarkerToGroup: Marker has already been added to a group', marker, marker.group)
    return false
  }

  // Get the group
  // -- Inherit from the MapViewMarker if not set
  if (!groupName) groupName = marker.groupName
  group = self.getGroup(groupName)

  // No group found? Use `all`
  if (!group) {
    groupName = 'all'

    // Get the `all` group
    if (self.groups.hasOwnProperty('all')) {
      group = self.getGroup('all')

    // Create default group of `all`
    } else {
      group = self.addGroup('all')
    }
  }

  // @debug
  // console.log('MapView.addMarkerToGroup', marker.id, groupName, group, group.mapViewSettings.name)

  // Reference the Leaflet marker object within the MapViewMarker object
  if (marker instanceof MapViewMarker) {
    // Save reference to the group to avoid it being added to another group later
    marker.group = group

    // Get the L.Marker instance to add to the group's layer
    marker = marker.marker
  }

  // Add marker to the group
  if (marker) group.addLayer(marker)

  return true
}

/*
 * Remove a marker from a cluster group
 *
 * @method removeMarkerFromGroup
 * @param {Mixed} marker Can be {String} markerId, {MapViewMarker} or {L.Marker}
 * @param {String} groupName
 * @returns {Boolean}
 */
MapView.prototype.removeMarkerFromGroup = function (marker, groupName) {
  var self = this
  var group

  // Get the marker
  if (typeof marker === 'string') {
    marker = self.getMarker(marker)
  }

  // Get the group
  // -- Inherit from the MapViewMarker if not set
  if (!groupName) groupName = marker.group
  group = self.getGroup(groupName)

  // Try `all` group if above doesn't work
  if (!group) {
    group = self.getGroup('all')
  }

  if (!group) return false

  // Reference the Leaflet marker object within the MapViewMarker object
  if (marker instanceof MapViewMarker) {
    marker = marker.marker
  }

  // Remove marker from the group
  if (marker) group.removeLayer(marker)
}

/*
 * Get filter
 *
 * @method getFilter
 * @param {Mixed} {String} groupName
 * @returns {MapViewFilter}
 */
MapView.prototype.getFilter = function (filterName) {
  var self = this
  filterName = sanitiseName(filterName)

  if (self.filters.hasOwnProperty(filterName)) {
    return self.filters[filterName]
  }

  return false
}

/*
 * Add filter to the mapview (enables turning on/off groups)
 *
 * @method addFilter
 * @param {String} filterName The name of the filter (most likely the name
 *                            of the group the filter will control)
 * @param {Object} options The filter's options
 * @returns {Mixed} Returns {Boolean} false on error, or {MapViewFilter}
 */
MapView.prototype.addFilter = function (filterName, options) {
  var self = this
  var mapViewFilter = self.getFilter(filterName)

  // Filter already exists
  if (mapViewFilter instanceof MapViewFilter) {
    return mapViewFilter
  }

  // Build the filter
  var mapViewFilter = new MapViewFilter(options)

  // Error happened when making the filter
  if (!mapViewFilter) return false

  // Add the mapViewFilter's DOM element to the MapView's DOM filters element
  if (self.$filters.length > 0 && mapViewFilter.$filter.length > 0) {
    self.$filters.append(mapViewFilter.$filter)
  }

  // @debug
  // console.log('MapView.addFilter', mapViewFilter)

  // @trigger elem `MapView:addFilter:added` [{MapView}, {MapViewFilter}]
  self.$elem.trigger('MapView:addFilter:added', [self, mapViewFilter])

  return mapViewFilter
}

/*
 * Refresh filters on the MapView
 * Essentially gets the filters' values and shows/hides the groups layers and stuff
 * @todo Needs to be finished
 *
 * @method refreshFilters
 * @returns {Void}
 */
MapView.prototype.refreshFilters = function () {
  var self = this

  // @debug
  // console.log('MapView.refreshFilters')

  // Go through each filter
  var appliedFilters = {}
  $.each(self.filters, function (i, filter) {
    switch (filter.type) {
      // Checkbox filter
      case 'checkbox':
        if (filter.$filter.is(':checked')) {
          appliedFilters[filter.name] = filter
        }
        break

      // Text filter
      case 'text':
        if (filter.$filter.val()) {
          // @note still need to figure out use-case for this one, so consider this a placeholder
          // @note if option is just for geocoding to move map's position, then it's already built into Mapbox and can ignore this. If it needs to do more (i.e. interact with the list of projects) then it's going to be trickier
        }
        break
    }
  })

  //
  if (appliedFilters.length > 0) {
    self.groupsVisibleLayer.clearLayers()
    $.each(appliedFilters, function (i, filter) {
      // Add the filtered groups to the groupsLayer
      // console.log('filter.group', filter.group)
      // self.groupsVisibleLayer.addLayer()
    })
  }
}

/*
 * MapViewMarker
 * @class
 * @private
 */
var MapViewMarker = function (mapView, options) {
  var self = this

  // Error
  if (!mapView) {
    console.log('new MapViewMarker Error: needs reference to MapView instance')
    return false
  }
  self.MapView = mapView

  // MapViewMarker properties
  self.id = (options.id || Utility.randomString(16))
  self.latLng = options.latLng || [options.lat || 0, options.lng || 0]
  self.title = options.title || ''
  self.description = options.description || ''
  self.status = sanitiseName(options.status || 'active')
  self.offerStatus = sanitiseName(options.offerStatus || '')
  self.groupName = sanitiseName(options.groupName || 'all')

  // @debug
  // console.log('MapViewMarker', self)

  // Marker
  self.marker = L.marker(self.latLng, $.extend({
    icon: L.divIcon({
      'iconSize': [30, 40],
      'iconAnchor': [15, 40],
      'popupAnchor': [0, -40],
      'className': 'mapview-marker-icon mapview-marker-icon-' + self.status + ' mapview-marker-icon-' + (self.offerStatus || self.groupName)
    }),
    clickable: true,
    keyboard: true,
    title: options.title || false,
    alt: options.title || false,
    riseOnHover: true,

    // Not part of Mapbox spec, but I need it to have inter-relation between UI [data-mapview-item] and the marker
    mapviewItemId: self.id
  }, options.markerOptions))

  // Popup
  self.popup = false
  if (options.hasPopup || typeof self.description === 'string' || typeof options.hasPopup === 'undefined') {
    // Create popup
    self.popup = L.popup($.extend({
      autoPan: true,
      zoomAnimation: true,
      closeButton: false
    }, options.popupOptions))

    // Generate the popup's content based on the templates
    self.popup.setContent(Templating.replace(self.templates.popupContent, [{
      title: options.popupTitle || options.title || '',
      content: options.popupContent || options.description || ''
    }, __]))

    // Bind popup to the marker (popup shows when clicked)
    self.marker.bindPopup(self.popup)
  }

  return self
}

/*
 * MapViewMarker prototype properties and methods
 */
MapViewMarker.prototype.templates = {
  popupContent: '<div class="mapview-marker-popup" data-parent="{{ id }}"><div class="mapview-marker-popup-title">{{ title }}</div><div class="mapview-marker-popup-content">{{ content }}</div></div>'
}

/*
 * MapViewFilter
 * @class MapViewFilter
 * @private
 * @note this is very much a first implementation and may need to be changed depending
 *       on the actual functional spec (please Black Pizza, tell me...)
 */
var MapViewFilter = function (options) {
  var self = this

  // Filter needs a group to work
  if (!options.group || !(options.group instanceof L.MarkerClusterGroup) || !options.group.hasOwnProperty('mapViewSettings')) {
    console.log('new MapViewFilter: invalid group object given. Must be an L.MarkerClusterGroup with a mapViewSettings property', options.group)
    return false
  }

  // Get the group settings
  var groupSettings = options.group.mapViewSettings

  // Filter settings
  self.settings = $.extend({
    group: options.group,
    name: sanitiseName(groupSettings.name),
    label: groupSettings.label,
    type: 'checkbox',
    enabled: groupSettings.visible,

    // Event triggered when the filter is changed
    onchange: undefined
  }, options)

  // Create a DOM element for the filter
  var filterElemProps = {
    groupName: sanitiseName(self.settings.group.mapViewSettings.name),
    name: sanitiseName(self.settings.name),
    label: self.settings.label,
    value: ''
  }
  switch (self.settings.type) {
    // Checkbox filter
    default:
    case 'checkbox':
      filterElemProps.value = 'true'
      filterElemProps.enabled = (self.settings.enabled ? 'checked="checked"' : '')
      self.$filter = $(Templating.replace(self.templates.inputCheckbox, [filterElemProps, __]))
      self.$input = self.$filter.find('input[type="checkbox"]')

      // The default checkbox change event
      if (typeof self.settings.onchange !== 'function') {
        self.settings.onchange = function (event) {
          if (self.$input.is(':checked')) {
            self.on()
          } else {
            self.off()
          }
        }
      }

      // Turn on the filter
      self.on = function () {
        self.settings.group.MapView.showGroup(self.settings.group)
        self.switchOn()
      }

      // Turn off the filter
      self.off = function () {
        self.settings.group.MapView.hideGroup(self.settings.group)
        self.switchOff()
      }

      // Show on DOM element that filter is on
      self.switchOn = function () {
        self.$input.attr('checked', 'checked')
      }

      // Show on DOM element that filter is off
      self.switchOff = function () {
        self.$input.removeAttr('checked')
      }
      break

    // Text filter
    case 'text':
      self.$filter = $(Templating.replace(self.templates.inputText, [filterElemProps, __]))
      self.$input = self.$filter.find('input[type="checkbox"]')

      // The default checkbox change event
      if (typeof self.settings.onchange !== 'function') {
        self.settings.onchange = function (event) {
          // Nothing here
        }
      }

      // Turn on the filter
      self.on = function () {
        self.switchOn()
      }

      // Turn off the filter
      self.off = function () {
        self.switchOff()
      }

      // Show on DOM element that filter is on
      self.switchOn = function () {
        // self.$input.select()
      }

      // Show on DOM element that filter is off
      self.switchOff = function () {
        self.$input.val('')
      }
      break
  }

  // Attach the `onchange` event to the filter's DOM element
  if (self.$filter && self.$filter.length > 0) {
    self.$filter.on('change', self.settings.onchange)
  }

  // Attach MapViewFilter instance to the group
  if (self.settings.group) self.settings.group.MapViewFilter = self

  // @debug
  // console.log('new MapViewFilter', self, options)

  // Return the filter
  return self
}

/*
 * Templates for the MapViewFilter
 */
MapViewFilter.prototype.templates = {
  inputCheckbox: '<label class="mapview-filter mapview-group-{{ groupName }} mapview-filter-type-checkbox" data-mapview-group="{{ groupName }}">\
    <input type="checkbox" name="mapview_filter[{{ name }}]" value="{{ value }}" {{ enabled }}/><span class="label">{{ label }}</span>\
  </label>',
  inputText: '<label class="mapview-filter mapview-group-{{ groupName }} mapview-filter-type-text" data-mapview-group="{{ groupName }}">\
    <input type="text" name="mapview_filter[{{ name }}]" class="input-field" placeholder="{{ label }}" value="{{ value }}" />\
  </label>'
}

/*
 * Debug the MapView
 *
 * @method debug
 * @returns {Void}
 */
MapView.prototype.debug = function () {
  var self = this

  console.log(self)
}

/*
 * Destroy the MapView instance
 *
 * @method destroy
 * @returns {Void}
 */
MapView.prototype.destroy = function () {
  var self = this

  self.$elem[0].MapView = false
  delete self
}

/*
 * Select the map item or load it if it is not in the list
 *
 * @method selectMapviewItem
 * @param {String} markerId
 * @returns {Void}
 */
MapView.prototype.selectMapviewItem = function (markerId) {
  var self = this
  var $mapviewItem = $('[data-mapview-markerid="' + markerId + '"]')

  // Not found
  if ($mapviewItem.length === 0) {
    var currentPage = self.$elem.parent().find('.pagination-next').attr('href').match(/\d+/)
    // @debug
    // console.log('Current page: ' + currentPage)
    self.selectMapviewItemLoad(currentPage, markerId)
  } else {
    self.selectMapviewItemUi($mapviewItem)
  }
}

/*
 * Select (highlight) the mapviewItem on the project map list using a markerId
 *
 * @method selectMapviewItemUi
 * @param {element} $mapviewItem
 * @returns {Void}
 */
MapView.prototype.selectMapviewItemUi = function ($mapviewItem) {
  // UI
  $('[data-mapview-item].ui-project-list-map-item-selected').not($mapviewItem).removeClass('ui-project-list-map-item-selected')
  $mapviewItem.addClass('ui-project-list-map-item-selected')

  // Scroll to the element within the list
  $mapviewItem.parents().each(function (i, parent) {
    var $parent = $(parent)
    // Only scroll the first parent with `overflow: auto`
    if ($parent.css('overflow') === 'auto') {
      $parent.uiScrollTo($mapviewItem)
      return false
    }
  })
}

/*
 * Ajax load the next page of map items or the page containing the markerId
 *
 * @method selectMapviewItemLoad
 * @param {String} page
 * @param {String} markerId
 * @returns {Void}
 */
MapView.prototype.selectMapviewItemLoad = function (page, markerId) {
  var self = this

  if (!page) {
    console.log('Missing page attribute')
    return
  }

  var ajaxUrl = self.settings.ajaxPaginationUrl + '/' + page.toString()
  // @debug
  // console.log('Ajax loading page from ' + ajaxUrl + ' | marker: ' + markerId)

  $.ajax({
    url: ajaxUrl,
    method: 'POST',
    data: {
      markerId: markerId
    },
    beforeSend: function() {
      // Using a custom spinner so remove the default one
      $('body').removeClass('ui-is-loading');
      self.$elem.trigger('Spinner:showLoading')
    },
    success: function (response) {
      self.$elem.trigger('Spinner:hideLoading')

      // Append results
      var $list = self.$elem.parent().find('.list-projects')
      var $lastItemIndex = $list.children().length
      $list.append(response)

      // If click is on a pin inside the map
      if (markerId) {
        var $mapviewItem = $('[data-mapview-markerid="' + markerId + '"]')
        self.selectMapviewItemUi($mapviewItem)
      // Else click is on the pagination next link
      } else {
        var $firstResponseItem = $list.find('li:nth-child(' + ($lastItemIndex + 1) + ')')
        $list.parent().uiScrollTo($firstResponseItem)
      }

      // Update the next page link
      var $next = $list.parent().find('.pagination-next')
      var next = parseInt($next.attr('href').split(/[/ ]+/).pop()) + 1
      var href = self.settings.ajaxPaginationUrl + '/' +  + next.toString()
      // @debug
      // console.log(href)
      $next.attr('href', href)
    },
    error: function(jqXHR) {
      // Handle server errors
      self.$elem.trigger('Spinner:hideLoading')
      console.log('Server error: ' + jqXHR.status)
    }
  });
}



/*
 * Refreshes the mapbox to recalculate its bounds and things (for responsive)
 *
 * @method refreshMapbox
 * @param {Object} options See options accepted for L.Map.invalidateSize() method
 * @returns {Void}
 */
MapView.prototype.refreshMapbox = function (options) {
  var self = this
  self.map.invalidateSize(options)
}

/*
 * jQuery Plugin
 */
$.fn.uiMapView = function (op) {
  // Fire a command to the MapView object, e.g. $('[data-mapview]').uiMapView('addMarker', {..})
  if (typeof op === 'string' && /^(panTo|zoom|populateMarkers|addMarker|removeMarker|showMarker|refreshMapbox|refreshFilters|showGroup|hideGroup|selectMapviewItemLoad|debug)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('MapView') && typeof elem.MapView[op] === 'function') {
        elem.MapView[op].apply(elem.MapView, args)
      }
    })

  // Set up a new MapView instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('MapView')) {
        new MapView(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
  // Auto-init component behaviours on document ready, or when parent element (or self) is made visible with `UI:visible` custom event
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-mapview]').not('.ui-mapview').uiMapView()
  })

  // Only initialise when viewed
  .on('UI:visible', function (event) {
    $(event.target).find('.ui-has-mapview').not('.ui-mapview').uiMapView()
  })

  // Click on item in map list
  .on(Utility.clickEvent, '[data-mapview-item]', function (event) {
    var $target = $(event.target)
    var $mapviewItem = $(this)

    // When interacting with things that aren't an anchor link
    if (!Utility.getElemIsOrHasParent(event.target, 'a')) {
      // Show the marker in the map
      var markerItemData = Utility.convertToPrimitive($mapviewItem.attr('data-mapview-item'))

      // @debug
      // console.log(markerItemData)
      var $map

      // Get the mapview element
      if (!markerItemData.hasOwnProperty('mapId')) {
        $map = $(document).find('[data-mapview], .ui-mapview').first()
      } else {
        $map = $(markerItemData.mapId)
      }

      // Show the marker in the mapview
      if (markerItemData) {
        $map.uiMapView('showMarker', markerItemData.id, 13, true)

        // UI
        MapView.prototype.selectMapviewItem(markerItemData.id)
      }
    }
  })

  // Ajax-load more items inside map projects list
  .on(Utility.clickEvent, '.project-list-map .pagination-next', function(event) {
    event.preventDefault()

    var currentPage = $(this).attr('href').match(/\d+/)[0]
    var $map = $(document).find('[data-mapview], .ui-mapview, .ui-has-mapview').first()
    $map.uiMapView('selectMapviewItemLoad', currentPage)
  })

  // Refresh the MapView's map when the window has been updated or when made visible
  .on('UI:update UI:visible', function (event) {
    $('.ui-mapview').uiMapView('refreshMapbox')
  })