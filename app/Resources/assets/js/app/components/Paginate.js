/*
 * Unilend Paginate JS
 *
 * @componentName   Paginate
 * @className       ui-pagination
 * @attrPrefix      data-paginate
 */

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var ProjectNotifications = require('../components/ProjectNotifications')

var $doc = $(document)

function Paginate(elem, options) {

    var self = this
    self.$el = $(elem)

    // Error: no element
    if (self.$el.length === 0 || elem.hasOwnProperty('Paginate')) return false

    self.$el[0].Paginate = self
    self.$el.addClass('ui-paginate')
    self.$items = self.$el.find('> div')

    // Settings
    self.settings = $.extend({
        perpage: 5
    }, ElementAttrsObject(elem, {
        perpage: 'data-paginate-count'
    }), options)

    // Track
    self.track = {
        total: function () {
            return self.$items.length
        },
        totalPage: function () {
            return Math.round(self.track.total() / self.settings.perpage)
        }
    }

    // Initialise
    self.init = function() {

        $('<ul class="pagination-index" />').appendTo(self.$el)

        var pageIndex = 1
        var pageIndexActiveClass = 'active'

        for (var i = 0; i < self.$items.length; i += self.settings.perpage) {

            // Add active class to first page only
            if (i > 0) { pageIndexActiveClass = '' }

            // Group items into separate pages
            self.$items.slice(i, i + self.settings.perpage).wrapAll('<div data-page-index="' + pageIndex + '" class="page '+ pageIndexActiveClass +'" />')

            // Append navigation items
            self.$el.find('.pagination-index').append('<li class="'+ pageIndexActiveClass +'"><a>' + pageIndex + '</a></li>')

            if (i % self.settings.perpage === 0) {
                pageIndex++;
            }
        }

    }
    self.init()

    // Set navigation
    self.$nav = self.$el.find('ul.pagination-index')

    // Go to page index
    self.goto = function(pageIndex) {

        // Find currently active and target pages
        var $targetPage  = self.$el.find('[data-page-index="' + pageIndex + '"]')
        var $currentPage = self.$el.find('.page.active');

        // Switch the active class
        $currentPage.removeClass('active')
        $targetPage.addClass('active')

        // Update pagination index
        self.$nav.find('.active').removeClass('active')
        self.$nav.find('li').eq(pageIndex - 1).addClass('active')

        // Trigger goto event
        self.$el.trigger('Paginate:goto', pageIndex)

    }

    // If there are notifications, mark them as read or open them
    self.markRead = function(pageIndex) {
        var $targetPage  = self.$el.find('[data-page-index="' + pageIndex + '"]')
        var $notifs = $targetPage.find('[data-proj-notification]')
        $notifs.each(function(){
            if ($(this).is('.ui-notification-status-unread')) {
                ProjectNotifications.markRead(this)
            } else {
                ProjectNotifications.openNotification(this)
            }
        })
    }

    // Trigger Initialised event
    self.$el.trigger('Paginate:initialised', 1)

}

/*
 * jQuery Plugin
 */
$.fn.uiPaginate = function(op) {
    if (typeof op === 'string' && /^(perpage|destroy|goto|markRead)$/.test(op)) {
        // Get further additional arguments to apply to the matched command method
        var args = Array.prototype.slice.call(arguments)
        args.shift()

        // Fire command on each returned elem instance
        return this.each(function(i, elem) {
            if (elem.hasOwnProperty('Paginate') && typeof elem.Paginate[op] === 'function') {
                elem.Paginate[op].apply(elem.Paginate, args)
            }
        })

        // Set up a new Pager instance per elem (if one doesn't already exist)
    } else {
        return this.each(function(i, elem) {
            if (!elem.hasOwnProperty('Paginate')) {
                new Paginate(elem, op)
            }
        })
    }
}

// Navigate to specific page within paginate component
$doc.on('click', '.ui-paginate .pagination-index a', function(){
    var index = parseInt($(this).text(), 10)
    $(this).closest('.ui-paginate').uiPaginate('goto', index)
})

// Open / mark read notifications on page 1
$doc.on('Paginate:initialised', '.ui-paginate', function(event, pageIndex){
    if ($(this).is('.list-notifications')) {
        $(this).uiPaginate('markRead', pageIndex)
    }
})
// Open / mark read notifications on any other page
$doc.on('Paginate:goto', '.ui-paginate', function(event, pageIndex){
    if ($(this).is('.list-notifications')) {
        $(this).uiPaginate('markRead', pageIndex)
    }
})

// Init the components when ready
$doc.ready(function() {
    $('[data-paginate]').uiPaginate()
})

module.exports = Paginate