/*
 * Dashboard Panel
 */

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

// DashboardPanel
// @class
// @param {Mixed} elem Either a {String} selector, {HTMLElement} or a {jQueryObject} representing an element in the view
// @param {Object} options
var DashboardPanel = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0 || elem.hasOwnProperty('DashboardPanel')) return false

  // Needs an ID number
  self.id = self.$elem.attr('id') || randomString()
  if (!self.$elem.attr('id')) self.$elem.attr('id', self.id)
  self.title = self.$elem.find('.dashboard-panel-title').text()

  // Settings
  self.settings = $.extend({
    draggable: true
  }, ElementAttrsObject(elem, {
    draggable: 'data-draggable'
  }), options)

  // Assign the class to show the UI functionality has been applied
  self.$elem.addClass('ui-dashboard-panel')

  // Trigger hide
  if (self.$elem.is('.ui-dashboard-panel-hidden')) {
    self.hide()
  } else {
    self.show()
  }

  self.$elem[0].DashboardPanel = self
  return self
}

/*
 * Prototype properties and methods
 */
// Show the panel
DashboardPanel.prototype.show = function () {
  var self = this
  self.$elem.removeClass('ui-dashboard-panel-hidden')
  self.getToggles().removeClass('ui-dashboard-panel-hidden')
  self.refreshLayout()
}

// Hide the panel
DashboardPanel.prototype.hide = function () {
  var self = this
  self.$elem.addClass('ui-dashboard-panel-hidden')
  self.getToggles().addClass('ui-dashboard-panel-hidden')
  self.refreshLayout()
}

// Toggle the panel
DashboardPanel.prototype.toggle = function () {
  var self = this
  if (self.$elem.is('.ui-dashboard-panel-hidden')) {
    self.show()
  } else {
    self.hide()
  }
}

// Refresh any layout modules
DashboardPanel.prototype.refreshLayout = function () {
  var self = this

  // Refresh packery
  // @note no more packery, doesn't suit the use-case
  //       using jquery-ui draggable now
  // if (self.$elem.parents('[data-packery]').length > 0) {
  //   self.$elem.parents('[data-packery]').packery()
  // }
}

// Get any item which toggles this dashboard panel
DashboardPanel.prototype.getToggles = function () {
  var self = this
  return $('[href="#' + self.id + '"].dashboard-panel-toggle')
}

/*
 * jQuery Plugin
 */
$.fn.uiDashboardPanel = function (op) {
  return this.each(function (i, elem) {
    if (!elem.hasOwnProperty('DashboardPanel')) {
      new DashboardPanel(elem, op)
    }
  })
}

/*
 * jQuery Events
 */
$(document)
  // Auto-init component behaviours on document ready, or when parent element (or self) is made visible with `UI:visible` custom event
  .on('ready UI:visible', function (event) {
    $(event.target).find('.dashboard-panel, [data-dashboardpanel]').not('.ui-dashboard-panel').uiDashboardPanel()
  })

  // Toggle the panel via the toggle option
  .on(Utility.clickEvent, '.dashboard-panel-toggle', function (event) {
    event.preventDefault()
    var $panel = $($(this).attr('href'))
    if ($panel.length > 0) $panel[0].DashboardPanel.toggle()
  })

  // Hide the panel via the close button
  .on(Utility.clickEvent, '.dashboard-panel .btn-close', function (event) {
    event.preventDefault()
    var $panel = $(this).parents('.dashboard-panel')
    $panel[0].DashboardPanel.hide()
  })

module.exports = DashboardPanel
