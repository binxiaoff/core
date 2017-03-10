/*
 * Unilend Autocomplete
 * Display potential matches to what a user is typing into an input field
 */

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')
var Tether = require('tether')

// Case-insensitive selector `:Contains()`
jQuery.expr[':'].Contains = function(a, i, m) {
  return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
};

// AutoComplete Language
var Dictionary = require('Dictionary')
var AUTOCOMPLETE_LANG_LEGACY = require('../../../lang/AutoComplete.lang.json')
var __

// -- Support new translation dictionary language format, e.g. `example-section-name_example-translation-key-name`
if (window.AUTOCOMPLETE_LANG) {
  __ = new Dictionary(window.AUTOCOMPLETE_LANG)
  // @debug
  // console.log('AutoComplete: using window.AUTOCOMPLETE_LANG for Dictionary')

// -- Support new legacy dictionary language format for fallbacks, e.g. `exampleTranslationKeyName`
} else {
  __ = new Dictionary(AUTOCOMPLETE_LANG_LEGACY, {
    legacyMode: true
  })
  // @debug
  console.log('FormValidation: using AUTOCOMPLETE_LANG_LEGACY for Dictionary. Please ensure window.AUTOCOMPLETE_LANG is correctly set.')
}

/*
 * AutoComplete
 * @class
 */
// var autoComplete = new AutoComplete(elemOrSelector, {..});
var AutoComplete = function (elem, options) {
  var self = this

  /*
   * Settings
   */
  self.settings = $.extend({
    enable: true, // Whether the AutoComplete functionality is enabled or not
    input: elem, // The input element to take the text input
    target: false, // The target element to put the results
    ajaxUrl: false, // An ajax URL to send the term receive results from. If `false`, looks in target element for the text
    ajaxProp: 'term', // The name of the property to send to the ajax URL endpoint
    delay: 500, // A delay to wait before searching for the term
    minTermLength: 3, // The minimum character length of a term to find
    showEmpty: false, // Show autocomplete with messages if no results found
    showSingle: true, // Show the autocomplete if only one result found
    attachTargetAfter: false, // Whether to apply the target to be directly after the input, or at the bottom in the body
    constrainTargetWidth: 'input', // Constrain the target's width. Accepted values: {Boolean} false, {String} 'input', or {Int} specific width in pixels
    useTether: true, // Use tether to attach the target element
    optimised: false, // If true, it'll retrieve AJAX for the first call, and then search within the results for further drilling down (reduces server IO and hopefully speeds up UI for user)

    // Special events
    onbeforeajax: undefined, // function (AutoComplete) { return {Boolean} if you want it to continue }
    onrender: undefined, // function (results) { return {String} representing all items },
    onrenderitem: undefined, // function (item) { return {String} representing single item }
    onsetinputvalue: undefined // function (value, itemElement) {}
  },
  // Options set via the element attributes
  ElementAttrsObject(elem, {
    target: 'data-autocomplete-target',
    ajaxUrl: 'data-autocomplete-ajaxurl',
    ajaxProp: 'data-autocomplete-ajaxprop',
    delay: 'data-autocomplete-delay',
    minTermLength: 'data-autocomplete-mintermlength',
    showEmpty: 'data-autocomplete-showempty',
    showSingle: 'data-autocomplete-showsingle',
    attachTargetAfter: 'data-autocomplete-attachtargetafter',
    constrainTargetWidth: 'data-autocomplete-constraintargetwidth',
    useTether: 'data-autocomplete-usetether'
  }),
  // Options set via JS
  options)

  // Properties
  // -- Use jQuery to select elem, distinguish between string, HTMLElement and jQuery Object
  self.$input = $(self.settings.input)
  self.$target = undefined
  self.track = {
    lastSearch: '',
    results: [],
    isLoading: false,
    changedWhileLoading: false,
    newSearch: ''
  }

  // Needs an input element to be valid
  if (self.$input.length === 0) return self.error('input element doesn\'t exist')

  // Already has behaviours applied
  if (self.$input[0].hasOwnProperty('AutoComplete')) return false

  // Create a new target element for the results
  if (!self.settings.target || !Utility.elemExists(self.settings.target)) {
    self.$target = $(self.templates.target)

    // Place the target after the input
    if (self.settings.attachTargetAfter) {
      self.$input.after(self.$target)

    // Place the target at the end of the body
    } else {
      self.$target.appendTo('body')
    }
  // Use an existing target to display results
  } else {
    self.$target = $(self.settings.target)
  }


  // Properties
  self.timer = 0
  self.searchTimer = 0
  self.hideTimer = 0
  self.Tether = undefined

  // UI
  self.$input.addClass('ui-autocomplete')

  /*
   * Events
   */
  // Type into the input elem

  // For Android Chrome keycode fix
  var getKeyCode = function (str) {
    return str.charCodeAt(str.length - 1);
  }

  self.$input.on('keyup', function ( event ) {
    clearTimeout(self.timer)

    // Only if it is enabled
    if (self.settings.enable) {

      // For Android Chrome keycode fix
      var keyCode = event.keyCode || event.which;
      if (keyCode == 0 || keyCode == 229) {
        keyCode = getKeyCode(this.value);
      }

      // Escape key - hide
      if (keyCode === 27) {
        self.hide()

      // Arrow key - down
      } else if (keyCode === 40) {
        if (self.$target.is(':visible')) {
          event.preventDefault()
          self.$target.find('li').first().find('a').focus()

          // Pressing down when not visible means user wants to see the autocomplete results
        } else {
          self.timer = setTimeout(self.findTerm, self.settings.delay)
        }

      // Press enter when there is one result
      } else if (keyCode === 13 && self.$target.find('li:visible').length === 1) {
        event.preventDefault()
        self.$target.find('li:visible').first().find('a').click()

      // Tab
      } else if (keyCode === 9 && self.$target.find('li:visible').length > 0) {
        event.preventDefault()
        self.$target.find('li:visible').first().find('a').focus()

      // Search for the term if user presses any letter/number/punctuation/delete key
      } else if (keyCode === 8 || keyCode === 46 || (keyCode >= 48 && keyCode <= 90) || (keyCode >= 186 && keyCode <= 222)) {
        self.timer = setTimeout(self.findTerm, self.settings.delay)
      }
    }
  })

  // Hide AutoComplete after time on blur
  self.$input.on('blur', function () {
    self.hideResultsAfterDelay()
  })
  self.$target.on('blur', function () {
    self.hideResultsAfterDelay()
  })

  // Cancel hide if any thing is focused
  self.$input.on('focus', function () {
    clearTimeout(self.hideTimer)
  })
  self.$target.on('focus', function () {
    clearTimeout(self.hideTimer)
  })

  // Click result to complete the input
  self.$target.on(Utility.clickEvent, '.autocomplete-results a', function (event) {
    event.preventDefault()
    self.setInputValue($(this).data('value') || $(this).text(), this)
    self.hide()
  })

  // Keyboard operations on results
  self.$target.on('keydown', '.autocomplete-results a:focus', function (event) {
    clearTimeout(self.hideTimer)

    // Move between results and input
    if (self.settings.enable) {

      // -- Press shift+tab on first item to go back to the input
      if (event.which === 9 && event.shiftKey && self.$target.find('.autocomplete-results a').index(this) === 0) {
        event.preventDefault()
        self.$input.focus()
        return
      }

      // -- Up key
      if (event.which === 38) {
        // Focus on input
        if ( $(this).parents('li').is('.autocomplete-results li:eq(0)') ) {
          event.preventDefault()
          self.$input.focus()
          return

        // Focus on previous result anchor
        } else {
          event.preventDefault()
          $(this).parents('li').prev('li').find('a').focus()
        }

      // -- Down key
      } else if (event.which === 40) {
        event.preventDefault()
        $(this).parents('li').next('li').find('a').focus()

      // -- Press esc to clear the autocomplete and go back to the search
      } else if (event.which === 27) {
        self.$input.focus()
        self.hide()

      // -- Press enter or right arrow on highlighted result to complete the input
      } else if (event.which === 39 || event.which === 13) {
        event.preventDefault()
        self.setInputValue($(this).data('value') || $(this).text(), this)
        self.hide()
      }
    }
  })

  /*
   * Methods
   */
  // Find a term
  self.findTerm = function (term) {
    if (!self.settings.enable) return

    // No term given? Assume term is val() of elem
    if (typeof term === 'undefined' || term === false) term = self.$input.val()

    // Term length not long enough, abort
    if (term.length < self.settings.minTermLength) return

    // Trim whitespace from start/end of term
    term = (term + '').trim()

    // User is attempting to search while it is loading
    if (self.track.isLoading) {
      self.track.newSearch = term
      self.track.changedWhileLoading = true

      // @debug
      // console.log('currently loading, try again later...', self.track.newSearch)
      return
    }

    // @debug
    // console.log('finding term', term)

    // Optimise the search
    if (self.settings.optimised) {
      // Make comparison between last search and new search
      var reLastSearch = new RegExp('^' + Utility.reEscape(self.track.lastSearch), 'i')

      // @debug
      // console.log('optimised', term, self.track.lastSearch, reLastSearch.test(term))

      // Only check if there was a lastSearch and any results exist, otherwise use default behaviours below
      if (self.track.lastSearch && self.track.results && self.track.results.length > 0) {
        // If this term is similar to last term, look for the term in the last retrieved results
        if (reLastSearch.test(term)) {
          self.findTermInResults(term, self.track.results)
          return
        }
      }
    }

    // Perform ajax search
    if (self.settings.ajaxUrl) {
      self.findTermViaAjax(term)

    // Perform search within target for an element's whose children contain the text
    } else {
      self.findTermInResults(term)
    }
  }

  // Find a term within results array (or element)
  self.findTermInResults = function (term, results) {
    // Trim whitespace from start/end of term
    term = (term + '').trim()

    // Get last retrieved results
    if (!results) results = self.track.results

    // Still none? Test if results element already has results
    if ((!results || results.length === 0) && self.$target.find('.autocomplete-results li').length > 0) {
      results = self.$target.find('.autocomplete-results li')
    }

    // Filter the results which match the term
    if (results instanceof jQuery) {
      // @debug
      // console.log('filter existing results', results)

      results = results.filter(':Contains(\'' + term + '\')')

    // Accepts array of results
    } else if (results instanceof Array) {
      // @debug
      // console.log('filter results array', results)

      var newResults = []
      var reTerm = new RegExp(Utility.reEscape(term), 'i')

      for (var i = 0; i < results.length; i++) {
        // Check object properties
        if (typeof results[i] === 'object') {
          for (var j in results[i]) {
            if (results[i].hasOwnProperty(j) && reTerm.test(results[i][j])) {
              newResults.push(results[i])
              break
            }
          }

        // Check single values
        } else if (typeof results[i] === 'string' || typeof results[i] === 'number') {
          if (reTerm.test(results[i])) {
            newResults.push(results[i])
          }
        }
      }

      // @debug
      // console.log('filtered results for term', term, newResults)

      results = newResults
    }

    // Show the results
    self.showResults(term, results)
  }

  // Find a term via AJAX
  self.findTermViaAjax = function (term) {
    if (!self.settings.enable) return
    var ajaxData = {}

    // @trigger `AutoComplete:findTermViaAjax:before` [elemAutoComplete, term]
    self.$input.trigger('AutoComplete:findTermViaAjax:before', [self, term])

    // Set the property correctly within the data object to send to the AJAX endpoint
    ajaxData[self.settings.ajaxProp] = term

    // @trigger input `Spinner:showLoading`
    self.$input.trigger('Spinner:showLoading')

    // Mark that it is loading
    self.track.isLoading = true

    // Do the ajax operation
    $.ajax({
      url: self.settings.ajaxUrl,
      method: 'GET',
      data: ajaxData,
      global: false,
      success: function (data, textStatus, xhr) {
        // Complete loading
        self.track.isLoading = false

        if (textStatus === 'success') {
          // Results, huzzah!
          if (data instanceof Array) {
            // Show the results
            self.showResults(term, data)

          } else {
            self.warning('Ajax Error: Data is not an array')
            console.log(data, textStatus, xhr)

            // @trigger `AutoComplete:findTermViaAjax:errored` [elemAutoComplete, term, data, textStatus, xhr]
            self.$input.trigger('AutoComplete:findTermViaAjax:errored', [self, term, data, textStatus, xhr])
          }
        } else {
          self.warning('Ajax Error: ' + textStatus)
          console.log(textStatus, xhr)

          // @trigger `AutoComplete:findTermViaAjax:errored` [elemAutoComplete, term, data, textStatus, xhr]
          self.$input.trigger('AutoComplete:findTermViaAjax:errored', [self, term, data, textStatus, xhr])
        }
      },
      error: function (textStatus, xhr) {
        self.warning('Ajax Error: Could not connect to the server')
        console.log(textStatus, xhr)

        // @trigger `AutoComplete:findTermViaAjax:errored` [elemAutoComplete, term, data, textStatus, xhr]
        self.$input.trigger('AutoComplete:findTermViaAjax:errored', [self, term, undefined, textStatus, xhr])
      },
      complete: function () {
        // Complete loading
        self.track.isLoading = false

        // @trigger input `Spinner:hideLoading`
        self.$input.trigger('Spinner:hideLoading')
      }
    })
  }

  // Display the results
  self.showResults = function (term, results) {
    clearTimeout(self.hideTimer)

    // @debug
    // console.log('showing results', term, results.length)

    // Track results are open
    self.track.resultsOpen = true

    // Remove any messages
    self.$target.find('li.autocomplete-message').remove()

    // @trigger input `AutoComplete:showResults:before` [elemAutoComplete, results]
    self.$input.trigger('AutoComplete:showResults:before', [self, results])

    // Results is collection of existing elements so show/hide as necessary
    if (results instanceof jQuery) {
      // @debug
      // console.log('modify existing results')

      self.removeHighlights()
      self.$target.find('.autocomplete-results li').hide()

    // Build target element results in HTML
    } else if (results instanceof Array) {
      // @debug
      // console.log('build new results')

      var resultsHTML = ''

      // Custom render function
      if (typeof self.settings.onrender === 'function') {
        resultsHTML = self.settings.onrender.apply(self, [results])

        // Default render function
      } else {
        for (var i = 0; i < results.length; i++) {
          var item = results[i]
          var itemLabel = item.label || item
          var itemValue = item.value || item.label || item

          // Render the item
          var itemHTML = ''

          // Use custom function
          if (typeof self.settings.onrenderitem === 'function') {
            itemHTML = self.settings.onrenderitem.apply(self, [item])

            // Default function
          } else {
            itemHTML = Templating.replace(self.templates.targetItem, {
              text: self.highlightTerm(term, itemLabel),
              label: itemLabel,
              value: itemValue
            })
          }

          // Add item to results HTML
          resultsHTML += itemHTML
        }
      }
      self.$target.find('.autocomplete-results').html(resultsHTML)

      // Select all results as jQuery collection for further operations
      results = self.$target.find('.autocomplete-results li')
    }

    // Set the last search term and results (for optimised operations)
    self.track.lastSearch = term
    self.track.results = results

    // If the term changed while it was loading, fire the findTerm operation again
    if (self.track.changedWhileLoading) {
      var newSearch = self.track.newSearch+''
      self.track.newSearch = ''
      self.track.changedWhileLoading = false

      // @debug
      // console.log('search changed when it was loading', self.track.lastSearch, newSearch)

      self.findTerm(newSearch)
      return
    }

    // No results
    if (results.length === 0) {

      // Show no results message
      if (self.settings.showEmpty) {
        if (self.$target.find('.autocomplete-results li.empty').length === 0) {
          self.$target.find('.autocomplete-results').append(Templating.replace(self.templates.message, {
            classNames: 'no-results',
            text: __.__('No results found!', 'noResults')
          }))
        }
        self.$target.find('.autocomplete-results li.no-results').show()
      } else {
        self.hide()
        return
      }

      // @trigger input `AutoComplete:showResults:noResults`, [elemAutoComplete, results]
      self.$input.trigger('AutoComplete:showResults:noResults', [self, results])

    // Results!
    } else {
      // Hide if only 1 result available and options.showSingle is disabled
      if (results.length === 1 && !self.settings.showSingle) {
        self.hide()
        return
      }

      // Highlight then show each result
      self.highlightResults(term, results)
      results.show()
    }

    // Show the results
    self.show()

    // @trigger input `AutoComplete:showResults:complete`, [elemAutoComplete, results]
    self.$input.trigger('AutoComplete:showResults:complete', [self, results])
  }

  // Hide the results after a delay
  self.hideResultsAfterDelay = function () {
    clearTimeout(self.hideTimer)
    self.hideTimer = setTimeout(function () {
      self.hide()
    }, 2000)
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
    var reTerm = new RegExp( '(' + Utility.reEscape(term) + ')', 'gi')
    return str.replace(reTerm, '<span class="highlight">$1</span>')
  }

  // Remove highlights from the text
  self.removeHighlights = function () {
    self.$target.find('.highlight').contents().unwrap()
  }

  // Set the input's value
  self.setInputValue = function (newValue, item) {
    // Custom event
    if (typeof self.settings.onsetinputvalue === 'function') {
      self.settings.onsetinputvalue.apply(self, [newValue, item])
    }

    // Set the new value to the input
    self.$input.val(newValue).focus()

    // @trigger input `AutoComplete:setInputValue:complete` [elemAutoComplete, newValue, itemElement]
    self.$input.trigger('AutoComplete:setInputValue:complete', [self, newValue, item])
  }

  // Show the autocomplete
  self.show = function () {
    if (!self.settings.enable) return

    // Ensure tethered
    if (self.settings.useTether) {
      if (!self.Tether) {
        self.Tether = new Tether({
          element: self.$target[0],
          target: self.$input[0],
          attachment: 'top left',
          targetAttachment: 'bottom left'
        })
      } else {
        self.positionTarget()
      }
    }

    // Constrain the target's width
    if (self.settings.constrainTargetWidth) {
      // Set to same width as input
      if (self.settings.constrainTargetWidth === 'input') {
        self.$target.width(self.$input.outerWidth() + 'px')

      // Set to fixed pixel width
      } else if (typeof self.settings.constrainTargetWidth === 'number') {
        self.$target.width(self.settings.constrainTargetWidth + 'px')

      // Set to other width
      } else if (/pt|r?em|\%/i.test(self.settings.constrainTargetWidth)) {
        self.$target.width(self.settings.constrainTargetWidth)
      }
    }

    // Show the target element
    self.$target.show()

    // Accessibility
    self.$target.attr('aria-hidden', 'false').find('.autocomplete-results li a').attr('tabindex', 1)

    // @trigger [input, target] `AutoComplete:show:complete`, [elemAutoComplete]
    self.$input.trigger('AutoComplete:show:complete', [self])
    self.$target.trigger('AutoComplete:show:complete', [self])
  }

  // Update the target's position if it has been tethered
  self.positionTarget = function () {
    if (self.settings.useTether && self.Tether) {
      self.Tether.position()
    }
  }

  // Hide the autocomplete
  self.hide = function () {
    clearTimeout(self.timer)
    clearTimeout(self.hideTimer)
    self.$target.hide()

    // Accesibility
    self.$target.attr('aria-hidden', 'true').find('.autocomplete-results li a').attr('tabindex', -1)

    // @trigger [input, target] `AutoComplete:hide:complete`, [elemAutoComplete]
    self.$input.trigger('AutoComplete:hide:complete', [self])
    self.$target.trigger('AutoComplete:hide:complete', [self])
    // Track results are closed
    self.track.resultsOpen = false

  }

  // Enable AutoComplete
  self.enable = function () {
    self.settings.enable = true
  }

  // Disable AutoComplete
  self.disable = function () {
    self.settings.enable = false
    self.hide()
  }

  // Hard error
  self.error = function () {
    throw new Error.apply(self, arguments)
    return
  }

  // Soft error (console warning)
  self.warning = function () {
    if (window.console) if (console.log) console.log.apply(self, arguments)
  }

  /*
   * Initialise
   */
  // Assign direct AutoComplete reference to the input and target elems
  self.$input[0].AutoComplete = self
  self.$target[0].AutoComplete = self

  // @trigger input `AutoComplete:initialised` [elemAutoComplete]
  self.$input.trigger('AutoComplete:initialised', [self])

  // Return the AutoComplete object
  return self
}

/*
 * Prototype properties and methods
 */
AutoComplete.prototype.templates = {
  target: '<div class="autocomplete" data-autocomplete-target><ul class="autocomplete-results"></ul></div>',
  targetItem: '<li><a href="javascript:void(0)" tabindex="1" data-label="{{ label|attr }}" data-value="{{ value|attr }}">{{ text }}</a></li>',
  message: '<li class="autocomplete-message {{ classNames }}">{{ text }}</li>',
}

/*
 * jQuery Plugin
 */
$.fn.uiAutoComplete = function (op) {
  // Fire a command to the AutoComplete object, e.g. $('[data-autocomplete]').uiAutoComplete('show')
  if (typeof op === 'string' && /^(show|hide|enable|disable|findTerm|positionTarget)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('AutoComplete') && typeof elem.AutoComplete[op] === 'function') {
        elem.AutoComplete[op].apply(elem.AutoComplete, args)
      }
    })

    // Set up a new AutoComplete instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('AutoComplete')) {
        new AutoComplete(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
var $doc = $(document)

$doc
  // Auto-init component behaviours on document ready, or when parent element (or self) is made visible with `UI:visible` custom event
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-autocomplete]').not('.ui-autocomplete').uiAutoComplete()

    // Update any tethered targets
    $doc.on('UI:update', function () {
      // Update the position of any tethered AutoComplete targets
      $('.ui-autocomplete').uiAutoComplete('positionTarget')
    })

    // Special modifications for address AutoComplete fields
    $('[data-autocomplete-address]').each(function (i, elem) {
      // Ignore if already has AutoComplete
      if (elem.hasOwnProperty('AutoComplete')) return

      var ajaxProp = $(elem).attr('data-autocomplete-ajaxprop')

      // Instantiate AutoComplete with specific address values
      new AutoComplete(elem, {
        minTermLength: 2,
        ajaxProp: ajaxProp ? ajaxProp : 'zip',
        // Don't output the item's value, as that will be extracted from the text and applied to the code/city inputs (it currently differs)
        onrenderitem: function (item) {
          var self = this
          return Templating.replace(this.templates.targetItem, {
            value: '',
            text: item.label
          })
        }
      })

      // If the countryelem has been set to something other than France, disable the AutoComplete functionality
      // @todo this should be updated/removed when new countries with autocomplete details are enabled
      var countryElemSelector = $(elem).attr('data-autocomplete-address-countryelem')
      if (countryElemSelector && Utility.elemExists(countryElemSelector)) {
        var $countryElem = $(countryElemSelector)
        $countryElem.on('change', function (event) {
          // France === '1'
          if ($(this).val() === '1') {
            $(elem).uiAutoComplete('enable')
          } else {
            $(elem).uiAutoComplete('disable')
          }
        }).change() // Trigger the event to apply when document ready
      }
    })

    // Set the new text value of the input and of the ville element
    $(document).on('AutoComplete:setInputValue:complete', '[data-autocomplete-address]', function (event, elemAutoComplete, newValue) {
      // Empty value given
      newValue = (newValue + '').trim()
      if (!newValue) return

      // Separate the values from the city and the code
      // Takes a value like `PARIS 2E ARRONDISSMENT (75002)` and splits it into two
      var codeValue = newValue.replace(/^.*\((\d+)\)$/, '$1')
      var cityValue = newValue.replace(/ ?\(.*$/, '')

      // @trigger elem `AutoComplete:address:city` [cityValue]
      $(this).trigger('AutoComplete:address:city', [cityValue])

      // @trigger elem `AutoComplete:address:code` [codeValue]
      $(this).trigger('AutoComplete:address:code', [codeValue])

      // Set the new code value
      // elemAutoComplete.$input.val(codeValue)

      // Get the city element to set it with the city value
      var cityElemSelector = elemAutoComplete.$input.attr('data-autocomplete-address-cityelem')
      if (Utility.elemExists(cityElemSelector)) {
        var $cityElem = $(cityElemSelector)
        if ($cityElem.val() !== cityValue) {
          $cityElem.val(cityValue)

          // @debug
          console.log('set city', cityValue, $cityElem)
        }
      }

      // Get the zip element to set it with the zip value
      var zipElemSelector = elemAutoComplete.$input.attr('data-autocomplete-address-zipelem')
      if (Utility.elemExists(zipElemSelector)) {
        var $zipElem = $(zipElemSelector)
        if ($zipElem.val() !== codeValue) {
          $zipElem.val(codeValue)

          // @debug
          console.log('set code', codeValue, $zipElem)
        }
      }
    })

  })

module.exports = AutoComplete
