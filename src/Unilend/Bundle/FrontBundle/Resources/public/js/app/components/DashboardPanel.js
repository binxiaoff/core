/*
 * Dashboard Panel
 */

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

var DashboardPanel = function (elem, options) {
  var self = this
  self.$elem = $(elem)
  if (self.$elem.length === 0) return

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

  // Show the panel
  self.show = function () {
    var self = this
    self.$elem.removeClass('ui-dashboard-panel-hidden')
    self.getToggles().removeClass('ui-dashboard-panel-hidden')
    self.refreshLayout()
  }

  // Hide the panel
  self.hide = function () {
    var self = this
    self.$elem.addClass('ui-dashboard-panel-hidden')
    self.getToggles().addClass('ui-dashboard-panel-hidden')
    self.refreshLayout()
  }

  // Toggle the panel
  self.toggle = function () {
    var self = this
    if (self.$elem.is('.ui-dashboard-panel-hidden')) {
      self.show()
    } else {
      self.hide()
    }
  }

  // Refresh any layout modules
  self.refreshLayout = function () {
    var self = this

    // Refresh packery
    if (self.$elem.parents('[data-packery]').length > 0) {
      self.$elem.parents('[data-packery]').packery()
    }
  }

  // Get any item which toggles this dashboard panel
  self.getToggles = function () {
    var self = this
    return $('[href="#' + self.id + '"].dashboard-panel-toggle')
  }

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
 * jQuery Plugin
 */
$.fn.uiDashboardPanel = function (op) {
  return this.each(function (i, elem) {
    new DashboardPanel(elem, op)
  })
}

/*
 * jQuery Initialisation
 */
$(document)
  .on('ready', function () {
    $('.dashboard-panel, [data-dashboardpanel]').uiDashboardPanel()
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
