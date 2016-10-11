/*
 * Unilend Spinner Button
 * Button which displays a spinner to show that something is loading after pressed
 * Utilises Spinner component's behaviours
 *
 * @componentName   SpinnerButton
 * @className       ui-spinnerbutton
 * @attrPrefix      data-spinnerbutton
 * @langName        SPINNERBUTTON
 */

/*
  Usage:
  ======

  The SpinnerButton component is a shorthand convenience component that utilises the Spinner component, plus some extra behaviours, such as automated watching start/stop events to show/hide the spinner.

  Ideally SpinnerButton is only used for form submit buttons.

  The most basic implementation will by default assign the parent form as the SpinnerButton's target and hook into the form's `submit` event to show the spinner within the button:

  ```
    <form action="/" method="get">
      <input type="text" name="example" value="ABC!" />
      <button type="submit" data-spinnerbutton>Submit the form</button>
    </form>
  ```

  If you need to target a specific element, use the `targetelem` attribute.

  If you need or want to target another element to act as the spinner, use the `spinnerelem` attribute.

  The `targetstartevents` and `targetstopevents` allow you to control which events the target emits to show/hide the spinner within the button. It's advised that if you are using the FormValidation component that you add the `FormValidation:error` to the target's stop events. By default 'submit' and 'FormValidation:error' are the default start/stop events, so you don't need to apply it to each SpinnerButton element.

  The example below illustrates all points above:

  ```
    <form id="spinnerbutton-target" action="/" method="get">
      <input type="text" name="example" value="ABC!" />
      <span id="spinnerbutton-spinner" class="ui-is-spinner">Loading...</span>
      <button type="submit" data-spinnerbutton data-spinnerbutton-spinnerelem="#spinnerbutton-spinner" data-spinnerbutton-target="#spinnerbutton-target" data-spinnerbutton-targetstartevent="submit" data-spinnerbutton-targetstopevent="FormValidation:error">Submit the form</button>
    </form>
  ```

  The visibility of the spinner is controlled via the `_spinner.scss` stylesheet. Any spinner classed `ui-is-spinner` will be visible if any of its parents has the class `ui-is-loading`. There might be issues if you're trying to display a spinner outside of a target element (the target element will have the `ui-is-loading` class applied when its start events have been launched).

  If you need something a little more detailed than the SpinnerButton allows (e.g. to display state of one or many AJAX operations), consider just using the Spinner component and setting up further behaviours through the Spinner's custom events.

 */

// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

/*
 * SpinnerButton
 * @class
 */
var SpinnerButton = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error: element not specified or already has an instance of this component
  if (self.$elem.length === 0 || elem.hasOwnProperty('SpinnerButton')) return false

  // Component's instance settings
  self.settings = $.extend({
    // The element to use as the spinner
    // If falsey, component will make it's own and place within the button
    spinnerElem: undefined,

    // The target element to watch for special events
    targetElem: undefined,

    // The target's events to watch to start the spinner
    targetStartEvents: 'submit',

    // The target's events to watch to stop the spinner
    targetStopEvents: 'ajaxStop FormValidation:error',

    // Disable the button if loading (means if user tries to press the button while loading it won't do anything)
    disable: true,

    // Disable the target if loading
    disableTarget: true

  }, ElementAttrsObject(elem, {
    spinnerElem: 'data-has-spinner',
    targetElem: 'data-spinnerbutton-targetelem',
    targetStartEvents: 'data-spinnerbutton-targetstartevents',
    targetStopEvents: 'data-spinnerbutton-targetstopevents',
    disable: 'data-spinnerbutton-disable',
    disableTarget: 'data-spinnerbutton-disabletarget'
  }), options)

  // Elements
  // -- Target
  if (!self.settings.targetElem || !Utility.elemExists(self.settings.targetElem)) {
    // Default to the closest form
    self.settings.targetElem = self.$elem.closest('form').eq(0)

    // Error
    if (self.settings.targetElem.length === 0) {
      console.warn('new SpinnerButton: no valid targetElem could be found')
      return
    }
  }
  self.$target = $(self.settings.targetElem)

  // -- Spinner
  if (!self.settings.spinnerElem || !Utility.elemExists(self.settings.spinnerElem)) {
    self.settings.spinnerElem = $(self.templates.spinner).appendTo(self.$elem)
  }
  self.$spinner = $(self.settings.spinnerElem)

  // Assign class to show component behaviours have been applied (required)
  self.$elem.addClass('ui-spinnerbutton')

  // Assign instance of class to the element (required)
  self.$elem[0].SpinnerButton = self

  // Initialise the SpinnerButton
  self.init()

  return self
}

/*
 * Prototype properties and methods (shared between all class instances)
 */

// Templates
SpinnerButton.prototype.templates = {
  // Default spinner HTML if no spinner was specified
  spinner: '<span class="ui-spinnerbutton-spinner ui-is-spinner"></span>'
}

/*
 * Initialise the SpinnerButton
 *
 * @method destroy
 * @returns {Void}
 */
SpinnerButton.prototype.init = function () {
  var self = this

  // Add an ID to the spinner if it doesn't already have one
  if (!self.$spinner.attr('id')) {
    self.$spinner.attr('id', 'spinner-' + Utility.randomString())
  }

  // Connect the spinner dots
  self.$elem.attr('data-has-spinner', self.$spinner.attr('id'))

  // Watch the target for specific start events
  $(document).on(self.settings.targetStartEvents, self.$target, function (event) {
    self.$elem.trigger('Spinner:showLoading')

    // Disable the button
    if (self.settings.disable) {
      self.$elem.prop('disabled', true)
    }

    // Disable the target too
    if (self.settings.disableTarget) {
      self.$target.prop('disabled', true)
    }
  })

  // Watch the target for specific stop events
  $(document).on(self.settings.targetStopEvents, self.$target, function (event) {
    if (event.type !== 'Spinner:hideLoading') {
      self.$elem.trigger('Spinner:hideLoading')
    }

    // Enable the button
    if (self.settings.disable) {
      self.$elem.removeProp('disabled')
    }

    // Enable the target too
    if (self.settings.disableTarget) {
      self.$target.removeProp('disabled')
    }
  })
}

/*
 * Destroy the SpinnerButton instance
 *
 * @method destroy
 * @returns {Void}
 */
SpinnerButton.prototype.destroy = function () {
  var self = this

  // Do other necessary teardown things here, like destroying other related plugin instances, etc. Most often used to reduce memory leak

  self.$elem[0].SpinnerButton = null
  delete self
}

/*
 * jQuery Plugin
 */
$.fn.uiSpinnerButton = function (op) {
  // Fire a command to the SpinnerButton object, e.g. $('[data-spinnerbutton]').uiSpinnerButton('publicMethod', {..})
  // @todo add in list of public methods that $.fn.uiSpinnerButton can reference
  if (typeof op === 'string' && /^(publicMethod|anotherPublicMethod|destroy)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('SpinnerButton') && typeof elem.SpinnerButton[op] === 'function') {
        elem.SpinnerButton[op].apply(elem.SpinnerButton, args)
      }
    })

    // Set up a new SpinnerButton instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('SpinnerButton')) {
        new SpinnerButton(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
// Auto-init `[data-spinnerbutton]` elements through declarative instantiation
.on('ready UI:visible', function (event) {
  $(event.target).find('[data-spinnerbutton]').not('.ui-spinnerbutton').uiSpinnerButton()
})
