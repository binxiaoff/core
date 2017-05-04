var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var DataTable = require('DataTable')

var DataTables = function (elem, options) {
    var self = this
    self.$elem = $(elem)

    // Error
    if (self.$elem.length === 0) return false

    // Return existing instance
    if (elem.hasOwnProperty('DataTables')) return elem.DataTables

    /*
     * Settings
     */
    self.settings = Utility.inherit(
        // Default
        {
            info: false,
            paging: false,
            lengthChange: false,
            language: {
                search: '_INPUT_',
                searchPlaceholder: 'Rech&eacute;rcher',
                zeroRecords: "Aucun &eacute;l&eacute;ment &agrave; afficher",
                processing: "Traitement en cours..."
            }
        }, {
            info: self.$elem.data('table-info'),
            paging: self.$elem.data('table-paging'),
            lengthChange: self.$elem.data('table-lengthchange'),
            language: {
                search: self.$elem.data('table-languagesearch'),
                searchPlaceholder: self.$elem.data('table-languagesearchplaceholder'),
                zeroRecords: self.$elem.data('table-languagezerorecords'),
                processing: self.$elem.data('table-languageprocessing')
            }
        },
        // JS invocation overrides
        options)

    // Assign class to show component behaviours have been applied
    self.$elem.addClass('ui-datatable')

    /*
     * Initialisation
     */

    // Adjust datatables plugin class names
    $.fn.dataTableExt.oStdClasses["sWrapper"] = "table-scroll";
    $.fn.dataTableExt.oStdClasses["sFilter"] = "form-field col-sm-6 col-md-4 col-lg-3";
    $.fn.dataTableExt.oStdClasses["sFilterInput"] = "input-field";

    // Plugin init function
    self.$elem.DataTable(self.settings)

    // Assign instance of class to the element
    self.$elem[0].DataTables = self

    // @trigger elem `DataTables:initialised` [elemdatatable, settings]
    self.$elem.trigger('DataTables:initialised', [self, self.settings])

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
            if (elem.hasOwnProperty('DataTables') && typeof elem.DataTable[op] === 'function') {
                elem.DataTable[op].apply(elem.DataTable, args)
            }
        })

        // Set up a new DataTable instance per elem (if one doesn't already exist)
    } else {

        return this.each(function (i, elem) {
            if (!elem.hasOwnProperty('DataTables')) {
                new DataTables(elem, op)
            }
        })

    }
}

/*
 * jQuery Events
 */
$('[data-table]').not('.ui-datatable').uiDatatable()







