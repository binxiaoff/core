/*
 * Dashboard Panels
 * Supports saving Dashboard panel states via AJAX to backend
 *
 * @component `DashboardPanels`
 * @ui `ui-dashboardpanels`
 * @data `data-dashboardpanels`
 */

/*
 @note could migrate the `data-movable-content` to this component, since currently the dashboard is the only place using those draggable/sortable items
 */

var $ = require('jquery')
var ElementAttrsObject = require('ElementAttrsObject')
var Utility = require('Utility')

/* DashboardPanels
 * @class
 */
var DashboardPanels = function (elem, options) {
    var self = this

    /*
     * Element
     */
    self.$elem = $(elem)
    if (self.$elem.length === 0) {
        console.log('DashboardPanels: No element specified. Aborting...')
        return false
    }

    /*
     * Settings
     */
    self.settings = $.extend({
        ajaxUrl: false
    }, ElementAttrsObject(elem, {
        ajaxUrl: 'data-dashboardpanels-ajaxurl'
    }), options)

    /*
     * Properties
     */
    self.data = {
        // Panel order and status information
        panels: []
    }
    self.delayTimer = 0

    /*
     * UI
     */
    self.$elem.addClass('ui-dashboardpanels')

    /*
     * Initialise
     */
    self.$elem[0].DashboardPanels = self
    self.updateData()

    // @trigger elem `DashboardPanels:initialised` [elemDashboardPanels]
    self.$elem.trigger('DashboardPanels:initialised', [self])

    return self
}

/*
 * Prototype properties and methods (shared between all DashboardPanels instances)
 */
// @method getPanels
// @returns {jQueryObject}
DashboardPanels.prototype.getPanels = function () {
    var self = this
    return self.$elem.find('.ui-dashboard-panel')
}

// @method getDraggablePanels
// @returns {jQueryObject}
DashboardPanels.prototype.getDraggablePanels = function () {
    var self = this
    return self.$elem.find('.ui-dashboard-panel[data-draggable]')
}

// Get the information about a single panel
// @method getPanelData
// @param {Mixed} panelElem Can be {String} selector, {HTMLElement} or {JQueryObject}
// @returns {Object}
DashboardPanels.prototype.getPanelData = function (panelElem) {
    var self = this

    // Get the panel element
    var $panel = $(panelElem)
    if ($panel.length === 0) {
        // @debug
        console.log('DashboardPanels.getPanelData Error: Invalid panelElem given')
        return false
    }

    // Detect the panel data
    var panelData = {
        id: $panel.attr('id'),
        order: 0, // Default is 0, unless placed into a specific order (see `updateData`)
        hidden: $panel.hasClass('ui-dashboard-panel-hidden')
    }

    return panelData
}

// Retrieves from backend via AJAX the panels' order and status data
// @method pullData
// @returns {Void}
DashboardPanels.prototype.pullData = function () {
    var self = this

    // Error: no ajaxUrl set
    if (!self.settings.ajaxUrl) {
        // @debug
        console.log('DashboardPanels.pullData Error: no ajaxUrl was set', self.settings)
        return
    }

    // @trigger elem `DashboardPanels:pullData:before` [elemDashboard, ajaxData]
    self.$elem.trigger('DashboardPanels:pullData:before', [self, self.data])

    // Get the dashboard data
    $.ajax({
        url: self.settings.ajaxUrl,
        method: 'GET',
        data: self.data,
        success: function (responseData, textStatus, xhr) {
            // Success
            if (responseData.hasOwnProperty('success')) {
                // @debug
                console.log('Successfully pulled panel data', responseData)

                // @trigger elem `DashboardPanels:pullData:success` [elemDashboard, responseData]
                self.$elem.trigger('DashboardPanels:pullData:success', [self, responseData])

                // Update the dashboard's data with new data and refresh the view
                if (responseData.hasOwnProperty('data')) self.updateData(responseData.data, true)

                // Error backend
            } else {
                // @debug
                console.log('Error pulling panel data: ' + responseData.msg)

                // @trigger elem `DashboardPanels:pullData:error` [elemDashboard, textStatus, xhr]
                self.$elem.trigger('DashboardPanels:pullData:error', [self, textStatus, xhr])
            }
        },
        error: function (textStatus, xhr) {
            // @debug
            console.log('Error pulling panel data: Could not connect to the server')

            // @trigger elem `DashboardPanels:pullData:error` [elemDashboard, textStatus, xhr]
            self.$elem.trigger('DashboardPanels:pullData:error', [self, textStatus, xhr])
        }
    })
}

// Detects panels order and status and sends (pushes) information to backend via AJAX
// @method pushData
// @returns {Void}
DashboardPanels.prototype.pushData = function () {
    var self = this

    // Error: no ajaxUrl set
    if (!self.settings.ajaxUrl) {
        // @debug
        console.log('DashboardPanels.pushData Error: no ajaxUrl set', self)
        return
    }

    // Clear any delayed timer function
    clearTimeout(self.delayTimer)

    // Update the dashboard's data first
    var performPush = self.updateData()

    // Only do AJAX if a changing update was made
    if (performPush) {
        // @trigger elem `DashboardPanels:pushData:before` [elemDashboard, dashboardData]
        self.$elem.trigger('DashboardPanels:pushData:before', [self, self.data])

        // Send panel order/status data to backend
        $.ajax({
            url: self.settings.ajaxUrl,
            method: 'PUT',
            data: self.data,
            success: function (responseData, textStatus, xhr) {
                // Success
                if (responseData.hasOwnProperty('success')) {
                    // @debug
                    console.log('Successfully pushed panel data', responseData)

                    // @trigger elem `DashboardPanels:pushData:success` [elemDashboard, responseData]
                    self.$elem.trigger('DashboardPanels:pushData:success', [self, responseData])

                    // Error on backend
                } else if (responseData.hasOwnProperty('error')) {
                    // @debug
                    console.log('Error pushing panel data: ' + responseData.msg)

                    // @trigger elem `DashboardPanels:pushData:error` [elemDashboard, textStatus, xhr]
                    self.$elem.trigger('DashboardPanels:pushData:error', [self, textStatus, xhr])
                }
            },
            error: function (textStatus, xhr) {
                // @debug
                console.log('Error pushing panel data: Could not connect to server')

                // @trigger elem `DashboardPanels:pushData:error` [elemDashboard, textStatus, xhr]
                self.$elem.trigger('DashboardPanels:pushData:error', [self, textStatus, xhr])
            }
        })
    }
}

// Wait for 1s before pushing the data (same as `pushData` method)
// @method delayedPushData
// @returns {Void}
DashboardPanels.prototype.delayedPushData = function () {
    var self = this

    // Clear the previous function call
    clearTimeout(self.delayTimer)

    // Set the new function call
    self.delayTimer = setTimeout(function () {
        self.pushData()
    }, 1000)
}

// Update the DashboardPanels's data, either with new data or to update with any view changed data
// @method updateData
// @param {Mixed} newData The new data to update the existing data with (uses `$.extend`).
//                        Set to `false` or `null` if you want to update the data based on what exists in the view
// @param {Boolean} triggerRefresh If any changes to the data occurred, a view refresh will happen after update
// @returns {Boolean} If the data was updated or not
DashboardPanels.prototype.updateData = function (newData, triggerRefresh) {
    var self = this

    // Make a text clone of the current data state
    var oldDataState = JSON.stringify(self.data)

    // If no new data was given, detect current view state
    if (!newData) {
        newData = {
            panels: []
        }

        // Go through all panels
        self.getPanels().each(function (i, panelElem) {
            var panelData = self.getPanelData(panelElem)
            if (panelData) {
                // Set order in sequence
                panelData.order = newData.panels.length
                newData.panels.push(panelData)
            }
        })
    }

    // Create new object with updated data
    var updatedData = $.extend({}, self.data, newData)
    var updatedDataState = JSON.stringify(updatedData)

    // If there's a difference between the old and updated data, set the Dashboard's data to the updated data
    if (updatedDataState != oldDataState) {
        self.data = updatedData

        if (triggerRefresh) self.refresh()

        return true
    }

    return false
}

// Refreshes the Dashboard's content based on data (panel order, status, etc.)
// @method refresh
// @returns {Void}
DashboardPanels.prototype.refresh = function () {
    var self = this

    // @trigger elem `DashboardPanels:refresh:before` [elemDashboard, dashboardData]
    self.$elem.trigger('DashboardPanels:refresh:before', [self, self.data])

    // Panels
    if (self.data.panels instanceof Array) {
        var $panels = self.getPanels()
        var $reorderedPanels = $('<div></div>')

        // Sort the self.data.panels object first
        self.data.panels.sort(function (a, b) {
            if (a.order < b.order) return -1
            if (a.order > b.order) return 1
            return 0
        })

        // Process each panel in panels array order
        $.each(self.data.panels, function (i, panelData) {
            var $panel = $panels.filter('#' + panelData.id)
            if ($panel.length > 0) {
                // Show/hide panel (via DashboardPanel component)
                if (panelData.hidden) {
                    $panel.uiDashboardPanel('hide')
                } else {
                    $panel.uiDashboardPanel('show')
                }

                // Reorder panels by placing in seperate collection
                // If number is 0+, append to collection
                if (panelData.order >= 0) {
                    $panel.appendTo($reorderedPanels)

                    // else prepend to collection
                } else {
                    $panel.prependTo($reorderedPanels)
                }
            }
        })

        // Reorder the panels
        if ($reorderedPanels.length > 0) {
            // Append the new ones
            $reorderedPanels.children().appendTo(self.$elem)
            delete $reorderedPanels
        }

        // @trigger elem `DashboardPanels:refresh:complete` [elemDashboard, dashboardData]
        self.$elem.trigger('DashboardPanels:refresh:complete', [self, self.data])
    }
}

/*
 * jQuery Plugin
 */
$.fn.uiDashboardPanels = function (op) {
    // Fire a command to the DashboardPanels object, e.g. $('[data-dashboardpanels]').uiDashboardPanels('publicMethod', {..})
    // @todo add in list of public methods that $.fn.uiDashboardPanels can reference
    if (typeof op === 'string' && /^(pullData|pushData|delayedPushData|updateData|refresh)$/.test(op)) {
        // Get further additional arguments to apply to the matched command method
        var args = Array.prototype.slice.call(arguments)
        args.shift()

        // Fire command on each returned elem instance
        return this.each(function (i, elem) {
            if (elem.hasOwnProperty('DashboardPanels') && typeof elem.DashboardPanels[op] === 'function') {
                elem.DashboardPanels[op].apply(elem.DashboardPanels, args)
            }
        })

        // Set up a new DashboardPanels instance per elem (if one doesn't already exist)
    } else {
        return this.each(function (i, elem) {
            if (!elem.hasOwnProperty('DashboardPanels')) {
                new DashboardPanels(elem, op)
            }
        })
    }
}

/*
 * jQuery Events
 */
$(document)
    // Auto-init `[data-dashboardpanels]` elements
    .on('ready UI:visible', function (event) {
        $(event.target).find('[data-dashboardpanels]').not('.ui-dashboardpanels').uiDashboardPanels()
    })

    // Pull data from backend to establish user preferences
    // .on('DashboardPanels:initialised', function (event, elemDashboardPanels) {
    //     elemDashboardPanels.pullData()
    // })

    // Push data if any movable contents have changed
    // @note `sortupdate` is an event emitted by jQuery UI sortable
    .on('MovableContent:sortupdate', '.ui-dashboardpanels[data-movable-content]', function (event, itemMoved) {
        console.log('hiya')
        $(this).uiDashboardPanels('delayedPushData')
    })

    // Push data if any movable contents have changed
    .on('DashboardPanel:show DashboardPanel:hide', '.ui-dashboardpanels[data-movable-content]', function (event) {
        console.log('hide', event)
        $(this).uiDashboardPanels('delayedPushData')
    })
