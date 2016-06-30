/*
 * Unilend Autocomplete
 * @note this was the first JS component that I made, so it differs slightly from the others as
 *       I developed and improved my API planning and implentation as time went on
 */

/*
@todo support AJAX results
@todo finesse keyboard up/down on results
*/

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

// Case-insensitive selector `:Contains()`
jQuery.expr[':'].Contains = function(a, i, m) {
  return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
};

// AutoComplete Language
var Dictionary = require('Dictionary')
var AUTOCOMPLETE_LANG = require('../../../lang/AutoComplete.lang.json')
var __ = new Dictionary(AUTOCOMPLETE_LANG)

/*
 * AutoComplete
 * @class
 */
// var autoComplete = new AutoComplete(elemOrSelector, {..});
var AutoComplete = function (elem, options) {
  var self = this

  /*
   * Options
   */
  self.options = $.extend({
    input: elem, // The input element to take the text input
    target: false, // The target element to put the results
    ajaxUrl: false, // An ajax URL to send the term receive results from. If `false`, looks in target element for the text
    delay: 200, // A delay to wait before searching for the term
    minTermLength: 3, // The minimum character length of a term to find
    showEmpty: false, // Show autocomplete with messages if no results found
    showSingle: true // Show the autocomplete if only one result found
  },
  // Options set via the element attributes
  ElementAttrsObject(elem, {
    target: 'data-autocomplete-target',
    ajaxUrl: 'data-autocomplete-ajaxurl',
    delay: 'data-autocomplete-delay',
    minTermLength: 'data-autocomplete-mintermlength',
    showEmpty: 'data-autocomplete-showempty',
    showSingle: 'data-autocomplete-showsingle'
  }),
  // Options set via JS
  options)

  // Properties
  // -- Use jQuery to select elem, distinguish between string, HTMLElement and jQuery Object
  self.$input = $(self.options.input)
  self.$target = $(self.options.target)

  // Needs an input element to be valid
  if ( self.$input.length === 0 ) return self.error('input element doesn\'t exist')

  // Already has behaviours applied
  if (self.$input[0].hasOwnProperty('AutoComplete')) return false

  // Create a new target element for the results
  if ( !self.options.target || self.$target.length === 0 ) {
    self.$target = $('<div class="autocomplete" data-autocomplete-target><ul class="autocomplete-results"></ul></div>')
    self.$input.after(self.$target)
  }

  // Get base elem
  self.input = self.$input[0]
  self.target = self.$target[0]
  self.timer = undefined

  // UI
  self.$input.addClass('uni-autocomplete') // This is `uni-autocomplete` since `ui-autocomplete` is taken by jquery-ui

  /*
   * Events
   */
  // Type into the input elem
  self.$input.on('keydown', function ( event ) {
    clearTimeout(self.timer)
    self.timer = setTimeout( self.findTerm, self.options.delay )

    // Escape key - hide
    if ( event.which === 27 ) {
      self.hide()
    }
  })

  // Hide autocomplete
  self.$input.on('AutoComplete:hide, autocomplete-hide', function ( event ) {
    // console.log('autocomplete-hide', self.input)
    self.hide()
  })

  // Click result to complete the input
  self.$target.on(Utility.clickEvent, '.autocomplete-results a', function ( event ) {
    event.preventDefault()
    self.$input.val($(this).text())
    self.hide()
  })

  // Keyboard operations on results
  self.$target.on('keydown', '.autocomplete-results a:focus', function ( event ) {
    // Move between results and input
    // @todo finesse keyboard up/down on results
    // -- Up key
    if ( event.which === 38 ) {
      // Focus on input
      if ( $(this).parents('li').is('.autocomplete-results li:eq(0)') ) {
        self.$input.focus()

      // Focus on previous result anchor
      } else {
        event.preventDefault()
        $(this).parents('li').prev('li').find('a').focus()
      }

    // -- Down key
    } else if ( event.which === 40 ) {
      event.preventDefault()
      $(this).parents('li').next('li').find('a').focus()

    // -- Press esc to clear the autocomplete and go back to the search
    } else if ( event.which === 27 ) {
      self.$input.focus()
      self.hide()

    // -- Press enter or right arrow on highlighted result to complete the input
    } else if ( event.which === 39 || event.which === 13 ) {
      self.$input.val($(this).text()).focus()
      self.hide()
    }
  })

  /*
   * Methods
   */
  // Find a term
  self.findTerm = function (term) {
    var results = []

    // No term given? Assume term is val() of elem
    if ( typeof term === 'undefined' || term === false ) term = self.$input.val()

    // Term length not long enough, abort
    if ( term.length < self.options.minTermLength ) return

    // Perform ajax search
    if ( self.options.ajaxUrl ) {
      self.findTermViaAjax(term)

    // Perform search within target for an element's whose children contain the text
    } else {
      results = self.$target.find('.autocomplete-results li:Contains(\''+term+'\')');
      self.showResults(term, results)
    }
  }

  // Find a term via AJAX
  self.findTermViaAjax = function (term) {
    $.ajax({
      url: self.options.ajaxUrl,
      data: {
        term: term
      },
      success: function (data, textStatus, xhr) {
        if ( textStatus === 'success' ) {
          // @todo support AJAX results
          // @note AJAX should return JSON object as array, e.g.:
          //       [ "Comment ça va ?", "Comment ?", "Want to leave a comment?" ]
          //       AutoComplete will automatically highlight the results text as necessary
          self.showResults(term, data)
        } else {
          self.warning('Ajax Error: '+textStatus, xhr)
        }
      },
      error: function (textStatus, xhr) {
        self.warning('Ajax Error: '+textStatus, xhr)
      }
    })
  }

  // Display the results
  self.showResults = function (term, results) {
    var reTerm = new RegExp('('+self.reEscape(term)+')', 'gi')

    // Remove any messages
    self.$target.find('li.autocomplete-message').remove()

    // Populate the target element results as HTML
    // -- If ajaxUrl is set, assume results will be array
    if ( self.options.ajaxUrl ) {
      var resultsHTML = '';
      $(results).each( function (i, item) {
        resultsHTML += '<li><a href="javascript:void(0)" tabindex="1">' + self.highlightTerm(term, item) + '</a></li>'
      })
      self.$target.find('.autocomplete-results').html(resultsHTML)

      // Select all results as jQuery collection for further operations
      results = self.$target.find('.autocomplete-results li')

    // -- If ajaxUrl is false, assume target already contains items, and that results
    //    is a jQuery collection of those result elements which match the found term
    } else {
      self.removeHighlights()
      self.$target.find('.autocomplete-results li').hide()
    }

    // No results
    if ( results.length === 0 ) {

      // Show no results message
      if ( self.options.showEmpty ) {
        if ( self.$target.find('.autocomplete-results li.empty').length === 0 ) {
          self.$target.find('.autocomplete-results').append('<li class="autocomplete-message no-results">'+__.__('No results found!', 'noResults')+'</li>')
        }
        self.$target.find('.autocomplete-results li.no-results').show()
      } else {
        self.hide()
        return
      }

    // Results!
    } else {
      // Hide if only 1 result available and options.showSingle is disabled
      if ( results.length === 1 && !self.options.showSingle ) {
        self.hide()
        return
      }

      // Show the results
      self.highlightResults(term, results)
      results.show()
    }

    self.show()
  }

  // Escape a string for regexp purposes
  // See: http://stackoverflow.com/a/6969486
  self.reEscape = function (str) {
    return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&")
  }

  // Add highlights to the results
  self.highlightResults = function (term, results) {
    results.each( function (i, item) {
      var text = $(this).find('a').text()
      var newText = self.highlightTerm(term, text)
      $(this).find('a').html(newText)
    })
  }

  // Add highlight to string
  self.highlightTerm = function (term, str) {
    var reTerm = new RegExp( '('+self.reEscape(term)+')', 'gi')
    return str.replace(reTerm, '<span class="highlight">$1</span>')
  }

  // Remove highlights from the text
  self.removeHighlights = function () {
    self.$target.find('.highlight').contents().unwrap()
  }

  // Show the autocomplete
  self.show = function () {
    // @debug console.log( 'show AutoComplete')

    self.$target.show()

    // Accessibility
    self.$target.attr('aria-hidden', 'false').find('.autocomplete-results li a').attr('tabindex', 1)
  }

  // Hide the autocomplete
  self.hide = function () {
    // @debug console.log( 'hide AutoComplete')

    clearTimeout(self.timer)
    self.$target.hide()

    // Accesibility
    self.$target.attr('aria-hidden', 'true').find('.autocomplete-results li a').attr('tabindex', -1)
  }

  // Hard error
  self.error = function () {
    throw new Error.apply(self, arguments)
    return
  }

  // Soft error (console warning)
  self.warning = function () {
    // if ( window.console ) if ( console.log ) {
    //   console.log('[AutoComplete Error]')
    //   console.log.apply(self, arguments)
    // }
  }

  /*
   * Initialise
   */
  // Assign direct AutoComplete reference to the input and target elems
  self.$input[0].AutoComplete = self
  self.$target[0].AutoComplete = self

  // Return the AutoComplete object
  return self
}

/*
 * jQuery Plugin
 */
$.fn.uiAutoComplete = function (op) {
  return this.each(function (i, elem) {
    if (!elem.hasOwnProperty('AutoComplete')) {
      new AutoComplete(elem, op)
    }
  })
}

/*
 * jQuery Events
 */
$(document)
  // Auto-init component behaviours on document ready, or when parent element (or self) is made visible with `UI:visible` custom event
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-autocomplete]').not('.uni-autocomplete').uiAutoComplete()
  })

module.exports = AutoComplete
