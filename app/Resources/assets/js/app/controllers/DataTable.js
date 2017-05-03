var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var DataTable = require('DataTable')

var DataTable = function (elem, options) {
    var self = this
    self.$elem = $(elem)

    // Error
    if (self.$elem.length === 0) return false

    // Return existing instance
    if (elem.hasOwnProperty('DataTable')) return elem.DataTable

    /*
     * Settings
     */
    self.settings = Utility.inherit(
        // Default
        {
            info: false,
            paging: false,
            lengthChange: false,
            tableLanguagesearch: 'Rechercher&nbsp;:',
            tableLanguagezerorecords: '',
            processing: "Traitement en cours...",
            zeroRecords: "Aucun &eacute;l&eacute;ment &agrave; afficher"
        },
        // Data Attributes
        ElementAttrsObject(elem, {
            info: 'data-table-info',
            paging: 'data-table-paging',
            lengthChange: 'data-table-lengthchange',
            tableLanguagezerorecords: 'data-table-languagezerorecords'
        }),
        // JS invocation overrides
        options)

    // Assign class to show component behaviours have been applied
    self.$elem.addClass('ui-datatable')

    /*
     * Initialisation
     */

    // Assign instance of class to the element
    self.$elem[0].DataTable = self

    // @trigger elem `datatable:initialised` [elemdatatable, settings]
    self.$elem.trigger('datatable:initialised', [self, self.settings])

    // @debug
    console.log('new DataTable', self.settings)

    self.$elem.DataTable(self.settings)

    return self
}

/*
 * jQuery Plugin
 */
$.fn.uiDatatable = function (op) {

    // Fire a command to the Modal object, e.g. $('[data-table]').uiDataTable('publicMethod', {..})
    if (typeof op === 'string' && /^(open|confirm|cancel|close|position|update)$/.test(op)) {
        // Get further additional arguments to apply to the matched command method
        var args = Array.prototype.slice.call(arguments)
        args.shift()

        // Fire command on each returned elem instance
        return this.each(function (i, elem) {
            if (elem.hasOwnProperty('DataTable') && typeof elem.DataTable[op] === 'function') {
                elem.DataTable[op].apply(elem.DataTable, args)
            }
        })

        // Set up a new DataTable instance per elem (if one doesn't already exist)
    } else {

        return this.each(function (i, elem) {
            if (!elem.hasOwnProperty('DataTable')) {
                new DataTable(elem, op)
            }
        })

    }
}

/*
 * jQuery Events
 */
$('[data-table]').not('.ui-datatable').uiDatatable()





