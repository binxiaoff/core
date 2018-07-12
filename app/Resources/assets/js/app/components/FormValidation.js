/*
 * Unilend Form Validation
 */

// Dependencies
var $ = require('jquery')
var sprintf = require('sprintf-js').sprintf
var Iban = require('iban')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')
var Sanity = require('Sanity')

// Dictionary
var __Utility = require('__')
var Dictionary = require('Dictionary')
var __ = new Dictionary(window.FORMVALIDATION_LANG)

/*
 * Private Values and Operations
 */

// Get the label identifying the element
function getLabelForElem (elem) {
  var $elem = $(elem)
  var label = ''
  var labelledBy = $elem.closest('[aria-labelledby]').eq(0).attr('aria-labelledby')
  var $label = $('label[for="' + $elem.attr('id') + '"]').first()

  // Labelled by other elements
  if (labelledBy) {
    label = []

    // Get elements that element has been labelled by
    if (/ /.test(labelledBy)) {
      labelledBy = labelledBy.split(' ')
    } else {
      labelledBy = [labelledBy]
    }

    $.each(labelledBy, function (i, labelItem) {
      var $labelledBy = $('#' + labelItem).first()
      if ($labelledBy.length > 0) {
        label.push($labelledBy.text())
      }
    })

    // Labels go in reverse order?
    label = label.reverse().join(' ')

  // Labelled by traditional method, e.g. label[for="id-of-element"]
  } else {
    if ($label.length > 0) {
      label = $label.text()

      // Label label
      if ($label.find('.label').length > 0) {
        label = $label.find('.label').text()
      }
    }
  }

  return label.replace(/\s+/g, ' ').trim()
}

// Get the field's value
function getFieldValue (elem) {
  var $elem = $(elem)
  var value

  // No elem
  if ($elem.length === 0) return value

  // Elem is a single input
  if ($elem.is('input, textarea, select')) {
    if ($elem.is('[type="radio"], [type="checkbox"]')) {
      if ($elem.is(':checked, :selected')) {
        value = $elem.val()
      }
    } else {
      value = $elem.val()
    }

  // Elem contains multiple inputs
  } else {
    // Search inside for inputs
    var $inputs = $elem.find('input, textarea, select')

    // No inputs
    if ($inputs.length === 0) return value

    // Get input values
    var inputNames = []
    var inputValues = {}
    $inputs.each(function (i, input) {
      var $input = $(input)
      var inputName = $input.attr('name')
      var inputValue = getFieldValue(input)

      if (typeof inputValue !== 'undefined') {
        if ($.inArray(inputName, inputNames) === -1) {
          inputNames.push(inputName)
        }
        inputValues[inputName] = $input.val()
      }
    })

    // @debug
    // console.log('getFieldValue:groupedinputs', {
    //   $inputs: $inputs,
    //   inputNames: inputNames,
    //   inputValues: inputValues
    // })

    // The return value
    if (inputNames.length === 1) {
      value = inputValues[inputNames[0]]
    } else if (inputNames.length > 1) {
      value = inputValues
    }
  }

  // @debug
  // console.log('getFieldValue', value)

  return value
}

// Get the field's input type
function getFieldType (elem) {
  var $elem = $(elem)
  var type

  // Error
  if ($elem.length === 0) return undefined

  // Single inputs
  if ($elem.is('input')) {
    type = $elem.attr('type')
  } else if ($elem.is('textarea')) {
    type = 'text'
  } else if ($elem.is('select')) {
    type = 'select'

  // Grouped inputs
  } else if (!$elem.is('input, select, textarea')) {
    // Get all the various input types within this element
    var $inputs = $elem.find('input, select, textarea')
    if ($inputs.length > 0) {
      var inputTypes = []
      $inputs.each(function (i, input) {
        var inputType = getFieldType(input)
        if (inputType && $.inArray(inputType, inputTypes) === -1) inputTypes.push(inputType)
      })

      // Put into string to return
      if ($inputs.length > 1) inputTypes.unshift('multi')
      if (inputTypes.length > 0) type = inputTypes.join(' ')

      // @debug
      // console.log('getFieldType:non_input', {
      //   inputTypes: inputTypes,
      //   $inputs: $inputs
      // })
    }
  }

  // @debug
  // console.log('getFieldType', {
  //   elem: elem,
  //   type: type
  // })

  return type
}

// @class FormValidation
// Use this class to apply to a group of formvalidation fields, e.g. new FormValidation('form')
//
// You can mark a form, fieldset or other element with the attribute [data-formvalidation]
// and then mark each input to be validated with the attribute [data-formvalidation-input]
//
// It relies on each field being contained within an element that has the class `.form-field`
// for outputting notification messages (errors, etc.)
//
// * It's recommended for multi-sectioned forms that each fieldset be marked [data-formvalidation]
// * For forms spanning multiple tabs, it's recommended that each tab be marked [data-formvalidation]
var FormValidation = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0 || elem.hasOwnProperty('FormValidation')) return false

  /**
   * The main settings for the FormValidation.
   *
   * @typedef {object} FormValidationSettings
   * @param {string|HTMLElement|jQuery} formElem,
   * @param {boolean} showNotifications - Whether to show notifications or not
   * @param {string|HTMLElement|jQuery} notificationsElem - The element to output notifications to
   * @param {boolean} validateOnFormEvents - Validate on form events like `submit`
   * @param {boolean} validateOnFieldEvents - Validate on field events like `change` and `blur`
   * @param {string} [watchFormEvents="submit"] - What form event names to validate on
   * @param {string} [watchFieldEvents="blur change"] - What form event names to validate on
   * @param {boolean} showSuccessOnField - Whether to mark that the field succeeded validation
   * @param {boolean} showErrorOnField - Whether to mark that the field errored
   * @param {boolean} showAllErrors - Whether to show errors on all fields (`true`) or stop after the first one (`false`)
   * @param {boolean} render - Render messages to the form/group/field
   * @param {Function} onfieldbeforevalidate
   * @param {Function} onfieldaftervalidate
   * @param {Function} onfieldsuccess
   * @param {Function} onfielderror
   * @param {Function} onfieldcomplete
   * @param {Function} onbeforevalidate - Fired before validating all contents
   * @param {Function} onaftervalidate - Fired after validating all contents
   * @param {Function} onsuccess - Fired when all validation has succeeded
   * @param {Function} onerror - Fired if any validation errored
   * @param {Function} oncomplete - Fired at the end of the validation process
   */
  self.settings = $.extend({
    // The form
    formElem: false,

    // Show notifications
    showNotifications: true,

    // An element that will contain main notifications (i.e. general messages to inform the user)
    notificationsElem: false,

    // Whether to validate on the form or on the individual field event
    validateOnFormEvents: true,
    validateOnFieldEvents: true,

    // The specific events to watch to trigger the form/field validation
    watchFormEvents: 'submit',
    watchFieldEvents: 'blur change',

    // Show successful/errored validation on field
    showSuccessOnField: true,
    showErrorOnField: true,
    showAllErrors: false,

    // Update the view (disable if you have your own rendering callbacks)
    render: true,

    // The callback to fire before validating a field
    onfieldbeforevalidate: null,

    // The callback to fire after validating a field
    onfieldaftervalidate: null,

    // The callback to fire when a field passed validation successfully
    onfieldsuccess: null,

    // The callback to fire when a field did not pass validation
    onfielderror: null,

    // The callback to fire when a field completed validation
    onfieldcomplete: null,

    // The callback to fire before form/group validation
    onbeforevalidate: null,

    // The callback to fire after form/group validation
    onaftervalidate: null,

    // The callback to fire when the form/group passed validation successfully
    onsuccess: null,

    // The callback to fire when the form/group did not pass validation
    onerror: null,

    // The callback to fire when the form/group completed validation
    oncomplete: null
  }, ElementAttrsObject(elem, {
    formElem: 'data-formvalidation-formelem',
    showNotifications: 'data-formvalidation-shownotifications',
    notificationsElem: 'data-formvalidation-notificationselem',
    render: 'data-formvalidation-render'
  }), options)

  // Elements
  // -- Get the target form that this validation component is referencing
  if (self.$elem.is('form')) self.$form = self.$elem
  if (self.settings.formElem) self.$form = $(self.settings.formElem)
  if (!self.$form || self.$form.length === 0) self.$form = self.$elem.parents('form').first()

  // -- Get/set the notifications element for this form/group
  self.$notifications = false
  if (Utility.elemExists(self.settings.notificationsElem)) {
    self.$notifications = $(self.settings.notificationsElem)
  }
  // -- Default notification element
  if (self.settings.showNotifications && !self.$notifications) {
    self.$notifications = $(self.templates.notifications)
    if (!Utility.elemExists(self.$notifications)) self.$elem.prepend(self.$notifications)
  }

  // @debug
  // console.log('new FormValidation', {
  //   $elem: self.$elem,
  //   $form: self.$form,
  //   settings: self.settings
  // })

  // UI
  self.$elem.addClass('ui-formvalidation')

  // Events on the form
  self.$form.on(self.settings.watchFormEvents, function (event) {
    if (self.settings.validateOnFormEvents) {
      var $form = $(this)
      var $groups = $form.find('[data-formvalidation]')

      /**
       * @typedef {object} FormValidationForm
       * @param {string} validation - The type of validation
       * @param {jQuery} $elem - The element of the input
       * @param {jQuery} $groups - A collection of FormValidation elements within the form
       * @param {boolean} isValid - If the form and its groups are all valid
       * @param {Array<FormValidationGroup>} groups - A collection of all the FormValidationGroup results
       * @param {Array<FormValidationGroup>} validGroups
       * @param {Array<FormValidationGroup>} erroredGroups
       */
      var formValidation = {
        validation: 'form',
        $elem: $form,
        $groups: $groups,

        // Properties
        isValid: false,
        groups: [],
        validGroups: [],
        erroredGroups: []
      }

      // Validate the form itself
      if ($form.is('[data-formvalidation]')) {
        $groups = $groups.add($form)
      }

      // Validate each group within the form
      if ($groups.length > 0) {
        $groups.each(function (i, elem) {
          // Validate each group
          if (elem.hasOwnProperty('FormValidation')) {
            var groupValidation = elem.FormValidation.validate()
            formValidation.groups.push(groupValidation)

            // Valid group
            if (groupValidation.isValid) {
              formValidation.validGroups.push(groupValidation)

            // Errored group
            } else {
              formValidation.erroredGroups.push(groupValidation)
            }
          }
        })
      }

      // Error
      if (formValidation.erroredGroups.length > 0) {
        formValidation.isValid = false
        // @trigger form `FormValidation:error`
        $form.trigger('FormValidation:error', [self, formValidation])

      // Success
      } else {
        formValidation.isValid = true
        // @trigger form `FormValidation:success`
        $form.trigger('FormValidation:success', [self, formValidation])
      }

      // @trigger form `FormValidation:complete`
      $form.trigger('FormValidation:complete', [self, formValidation])

      // Stop any submitting happening
      if (!formValidation.isValid) {
        return false
      }
    }
  })

  // Events on the fields
  self.getInputs().on(self.settings.watchFieldEvents, function (event) {
    if (self.settings.validateOnFieldEvents) {
      self.validateInput(event.target)
    }
  })

  // console.log(self.getInputs())

  // Attach FormValidation instance to element
  self.$elem[0].FormValidation = self
  return self
}

/*
 * Prototype functions and properties shared between all instances of FormValidation
 */
// Validate a single input
FormValidation.prototype.validateInput = function (elem, options) {
  var self = this
  var $elem = $(elem).first()

  // Error
  if ($elem.length === 0) return false

  // Input is group of related inputs: checkbox, radio, etc.
  if (!$elem.is('.form-field, input, textarea, select')) {
    // @debug
    // console.log('FormValidation.validateInput Error: make sure the elem is, or contains, at least one input, textarea or select element')
    if ($elem.find('.form-field, input, textarea, select').length === 0) return false
  }

  /**
   * Field validation input object.
   *
   * @typedef {object} FormValidationInput
   * @param {string} validation - The type of validation
   * @param {boolean} isValid - If the field/input is valid
   * @param {jQuery} $elem - The element of the input
   * @param {jQuery} $formField - The element of the field (the container holding the input)
   * @param {jQuery} $notifications - The notifications element to output input's validation messages
   * @param {*} value - The value of the input
   * @param {string} type="auto" - The type of input
   * @param {Array} errors - An array of validation errors
   * @param {Array} messages - An array of validation success messages
   * @param {FormValidationInputOptions} options
   */
  var inputValidation = {
    validation: 'input',

    // Properties
    isValid: false,
    $elem: $elem,
    $formField: $elem.closest('.form-field'),
    $notifications: $elem.closest('.form-field').find('.ui-formvalidation-messages').first(),
    value: getFieldValue($elem),
    type: 'auto', // Set to auto-detect type
    errors: [],
    messages: [],

    /**
     * Options to modify the form validation behaviours on the single input.
     *
     * @typedef {object} FormValidationInputOptions
     * @param {object} rules - An object with the names and functions to perform the validation rules
     * @param {boolean} showNotifications - show the notifications
     * @param {string|HTMLElement|jQuery} notificationsElem - The element to output any validation messages
     * @param {boolean} render - Whether to render validation messages
     * @param {boolean} showSuccess - Render success messages
     * @param {boolean} showError - Render error messages
     * @param {boolean} showAllError - Stop on first error (`false`) or show all errors (`true`) for this input
     * @param {Function} onbeforevalidate - Fired before validating input
     * @param {Function} onaftervalidate - Fired after validating input
     * @param {Function} onsuccess - Fired when input validation has succeeded
     * @param {Function} onerror - Fired if any validation errored on the input
     * @param {Function} oncomplete - Fired at the end of the input's validation process
     */
    options: Utility.inheritNested({
      // Rules to validate this field by
      rules: Utility.inherit({},
        // Use default rules as a base
        self.rules.defaultRules,

        // Inherit attribute values
        ElementAttrsObject(elem, {
          required: 'data-formvalidation-required',
          minLength: 'data-formvalidation-minlength',
          maxLength: 'data-formvalidation-maxlength',
          setValues: 'data-formvalidation-setvalues',
          inputType: 'data-formvalidation-type',
          sameValueAs: 'data-formvalidation-samevalueas',
          minValue: 'data-formvalidation-minvalue',
          maxValue: 'data-formvalidation-maxvalue'
        })
      ),

      // Options
      showNotifications: self.settings.showNotifications,
      notificationsElem: '.ui-formvalidation-messages',
      render: self.settings.render, // render the errors/messages to the UI
      showSuccess: self.settings.showSuccessOnField, // show that the input successfully validated
      showError: self.settings.showErrorOnField, // show that the input errored on validation
      showAllErrors: self.settings.showAllErrors, // show all the errors on the field, or show one by one

      // Events (in order of firing)
      onbeforevalidate: self.settings.onfieldbeforevalidate,
      onaftervalidate: self.settings.onfieldaftervalidate,
      onsuccess: self.settings.onfieldsuccess,
      onerror: self.settings.onfielderror,
      oncomplete: self.settings.onfieldcomplete
    },
    // Get options set in the HTML element's attributes
    ElementAttrsObject(elem, {
      rules: 'data-formvalidation-rules',
      notificationsElem: 'data-formvalidation-notificationselem',
      showSuccess: 'data-formvalidation-showsuccess',
      showError: 'data-formvalidation-showerror',
      render: 'data-formvalidation-render'
    }),
    // Override with JS invocation options
    options)
  }

  // Get the notifications element
  inputValidation.$notifications = self.getNotificationsElem(inputValidation)

  // Ignore disabled/hidden fields by saying they're valid
  if ($elem.is(':disabled') || Utility.elemIsHidden(elem)) {
    inputValidation.isValid = true
    return inputValidation
  }

  // If rules was set (JSON), expand out into the options
  if (typeof inputValidation.rules === 'string') {
    var checkRules = JSON.parse(inputValidation.rules)
    if (typeof checkRules === 'object') {
      inputValidation.options = Utility.inherit(inputValidation.options, checkRules)
    }
  }

  if (typeof inputValidation.options.rules.inputType === 'undefined' && inputValidation.options.rules.inputType !== false) {
    // Auto-select inputType validation
    if (inputValidation.type === 'auto') {
      inputValidation.type = getFieldType($elem)
    }

    // Make sure to always check non-text input types with the inputType rule
    if (inputValidation.type !== 'text') {
      inputValidation.options.rules.inputType = inputValidation.type
    }
  } else {
    inputValidation.type = inputValidation.options.rules.inputType
  }

  // @debug
  // console.log('inputValidation', inputValidation)

  // Before validation you can run a custom callback to process the field value
  if (typeof inputValidation.options.onbeforevalidate === 'function') {
    inputValidation.options.onbeforevalidate.apply($elem[0], [self, inputValidation])
  }
  // @trigger field `FormValidation:validateInput:beforeValidate`
  inputValidation.$elem.trigger('FormValidation:validateInput:beforeValidate', [self, inputValidation])

  /*
   * Apply the validation rules
   */
  for (var i in inputValidation.options.rules) {
    // Skip prototype values
    if (!inputValidation.options.rules.hasOwnProperty(i)) {
      continue
    }

    // @debug
    // console.log('inputValidation rules: ' + i, inputValidation.options.rules[i])
    if (inputValidation.options.rules[i] && self.rules.hasOwnProperty(i) && typeof self.rules[i] === 'function') {
      // @debug
      // console.log('validating via rule: ' + i, 'condition: ', inputValidation.options.rules[i])
      self.rules[i].apply(self, [inputValidation, inputValidation.options.rules[i]])
    }
  }

  // After validation you can run a custom callback on the results before shown in UI
  if (typeof inputValidation.options.onaftervalidate === 'function') {
    inputValidation.options.onaftervalidate.apply($elem[0], [self, inputValidation])
  }
  // @trigger field `FormValidation:validateInput:afterValidate`
  inputValidation.$elem.trigger('FormValidation:validateInput:afterValidate', [self, inputValidation])

  // Remove classes that indicate errors
  inputValidation.$elem.removeClass('ui-formvalidation-error ui-formvalidation-success')
  inputValidation.$formField.removeClass('ui-formvalidation-error ui-formvalidation-success')

  // Clear old messages
  if (inputValidation.$notifications) {
    inputValidation.$notifications.html('')
  }

  // Field validation errors
  if (inputValidation.errors.length > 0) {
    inputValidation.isValid = false

    // Trigger error
    if (typeof inputValidation.options.onerror === 'function') {
      inputValidation.options.onerror.apply(self, [self, inputValidation])
    }
    // @trigger field `FormValidation:validateInput:error`
    inputValidation.$elem.trigger('FormValidation:validateInput:error', [self, inputValidation])

    // Show error messages on the field
    if (inputValidation.options.showError && inputValidation.options.render) {
      // @debug
      // console.log('Validation error', inputValidation)
      inputValidation.$formField.addClass('ui-formvalidation-error')

      // Render messages
      if (inputValidation.errors.length > 0) {
        self.renderErrorsToElem((inputValidation.options.showAllErrors
          // Render all errors
          ? inputValidation.errors
          // Render only one error
          : inputValidation.errors.slice(0, 1)),
        inputValidation.$notifications
        )
      }
    }

  // Field validation success
  } else {
    inputValidation.isValid = true

    // Trigger error
    if (typeof inputValidation.options.onsuccess === 'function') {
      inputValidation.options.onsuccess.apply(self, [self, inputValidation])
    }
    // @trigger field `FormValidation:validateInput:success`
    inputValidation.$elem.trigger('FormValidation:validateInput:success', [self, inputValidation])

    // Show success messages on the field
    if (inputValidation.options.showSuccess && inputValidation.options.render) {
      // @debug
      // console.log('Validation success', inputValidation.messages)
      inputValidation.$formField.addClass('ui-formvalidation-success')

      // Clear old messages
      inputValidation.$notifications = self.getNotificationsElem(inputValidation)

      // -- Only if a notifications elem is available
      if (inputValidation.$notifications) {
        inputValidation.$notifications.html('')

        // Render messages
        if (inputValidation.errors.length > 0) {
          self.renderMessagesToElem(inputValidation.messages, inputValidation.$notifications)
        }
      }
    }
  }

  // @debug
  // console.log('inputValidation', inputValidation)

  // Trigger complete
  if (typeof inputValidation.options.oncomplete === 'function') {
    inputValidation.options.oncomplete.apply(self, [self, inputValidation])
  }
  // @trigger field `FormValidation:validateInput:complete`
  inputValidation.$elem.trigger('FormValidation:validateInput:complete', [self, inputValidation])

  return inputValidation
}

// Quickly validate a single input by a custom rule
FormValidation.prototype.validateInputCustom = function (elem, customRule) {
  var self = this
  return self.validateInput(elem, {
    rules: {
      custom: customRule
    }
  })
}

// Validate the element's fields
// @returns {Boolean}
FormValidation.prototype.validate = function (options) {
  var self = this

  // Validation object
  /**
   * Form group validation object.
   *
   * @typedef {object} FormValidationGroup
   * @param {string} validation - The type of validation
   * @param {boolean} isValid
   * @param {jQuery} $elem - The group element which is being validated
   * @param {jQuery} $notifications - The notifications element to put messages
   * @param {Array} fields - An array of the group's input fields to validate
   * @param {Array} validFields - An array of the group's validated input fields
   * @param {Array} messages - An array of the successful validation messages
   * @param {Array} erroredFields - An array of the group's errored input fields
   * @param {Array} errors - An array of the group's errored validation messages
   * @param {FormValidationGroupOptions} options - Further options to configure the validation
   */
  var groupValidation = {
    validation: 'group',

    // Properties
    isValid: false,
    $elem: self.$elem,
    $notifications: self.$notifications,
    fields: [],
    validFields: [],
    messages: [],
    erroredFields: [],
    errors: [],

    /**
     * A subset of the main {FormValidationSettings} for the group validation.
     *
     * @typedef {object} FormValidationGroupOptions
     * @param {boolean} render
     * @param {boolean} showNotifications
     * @param {jQuery} notificationsElem
     * @param {boolean} showSuccess
     * @param {boolean} showError
     * @param {boolean} showAllErrors
     * @param {Function} onbeforevalidate
     * @param {Function} onaftervalidate
     * @param {Function} onerror
     * @param {Function} onsuccess
     * @param {Function} oncomplete
     */
    options: $.extend({
      render: self.settings.render,
      showNotifications: self.settings.showNotifications,
      notificationsElem: self.settings.notificationsElem,
      showSuccess: self.settings.showSuccessOnField, // show success messages
      showError: self.settings.showErrorOnField, // show error messages
      showAllErrors: self.settings.showAllErrors, // show all error messages

      // Events
      onbeforevalidate: self.settings.onbeforevalidate,
      onaftervalidate: self.settings.onaftervalidate,
      onerror: self.settings.onerror,
      onsuccess: self.settings.onsuccess,
      oncomplete: self.settings.oncomplete
    }, options)
  }

  // Get the notifications element
  groupValidation.$notifications = self.getNotificationsElem(groupValidation)

  // Trigger before validate
  if (typeof groupValidation.options.onbeforevalidate === 'function') groupValidation.options.onbeforevalidate.apply(self, [self, groupValidation])
  // @trigger group `FormValidation:validate:beforeValidate`
  groupValidation.$elem.trigger('FormValidation:validate:beforeValidate', [self, groupValidation])

  // Validate each field
  self.getInputs().each(function (i, input) {
    var inputValidation = self.validateInput(input)
    groupValidation.fields.push(inputValidation)

    // Filter collection via valid/errored
    if (inputValidation.isValid) {
      groupValidation.validFields.push(inputValidation)
    } else {
      groupValidation.erroredFields.push(inputValidation)
    }
  })

  // Trigger after validate
  if (typeof groupValidation.options.onaftervalidate === 'function') groupValidation.options.onaftervalidate.apply(self, [self, groupValidation])
  // @trigger group `FormValidation:validate:afterValidate`
  groupValidation.$elem.trigger('FormValidation:validate:afterValidate', [self, groupValidation])

  // Clear previous notifications
  if (groupValidation.$notifications) {
    groupValidation.$notifications.html('')
  }

  // Error
  if (groupValidation.erroredFields.length > 0) {
    groupValidation.isValid = false
    if (typeof groupValidation.onerror === 'function') groupValidation.onerror.apply(self, [self, groupValidation])
    // @trigger group `FormValidation:validate:error`
    groupValidation.$elem.trigger('FormValidation:validate:error', [self, groupValidation])

    // Render to view
    if (groupValidation.options.render && groupValidation.options.showNotifications && groupValidation.$notifications) {
      groupValidation.$notifications.html(Templating.replace(self.templates.errorMessage, {
        description: __.__('There are errors with the form below. Please ensure your information has been entered correctly before continuing.', 'error-group-has-errors')
      }))
    }

  // Success
  } else {
    groupValidation.isValid = true
    if (typeof groupValidation.onsuccess === 'function') groupValidation.onsuccess.apply(self, [self, groupValidation])
    // @trigger group `FormValidation:validate:success`
    groupValidation.$elem.trigger('FormValidation:validate:success', [self, groupValidation])
  }

  // Trigger complete
  if (typeof groupValidation.oncomplete === 'function') groupValidation.oncomplete.apply(self, [self, groupValidation])
  // @trigger group `FormValidation:validate:complete`
  groupValidation.$elem.trigger('FormValidation:validate:complete', [self, groupValidation])

  return groupValidation
}

// Clears group's form fields of errors
// Relies on notifications elements to have class of `.ui-formvalidation-messages` applied
FormValidation.prototype.clear = function () {
  var self = this

  // Clear messages on each field
  self.getInputs().each(function (i, input) {
    $(input)
      .parents('.form-field').removeClass('ui-formvalidation-error ui-formvalidation-success')
      .find('.ui-formvalidation-messages').html('')
  })

  // Clear notifications
  if (self.$notifications) {
    self.$notifications.html('')
  }

  // @trigger group `FormValidation:clear` [elemFormValidation]
  self.$elem.trigger('FormValidation:clear', [self])
}

// Clears whole form (all groups)
FormValidation.prototype.clearAll = function () {
  var self = this
  self.$form.uiFormValidation('clear')
    .find('[data-formvalidation]').uiFormValidation('clear')
}

// Get the collection of inputs
FormValidation.prototype.getInputs = function () {
  var self = this
  return self.$elem.find('[data-formvalidation-input]')
}

// Templates
FormValidation.prototype.templates = {
  notificationsElem: '<div class="ui-formvalidation-notifications"></div>',
  messagesList: '<ul class="ui-formvalidation-messages"></ul>',
  messagesListItem: '<li class="{{ classNames }}">{{ targetLabel }}{{ description }}</li>',
  errorMessage: '<div class="message-error"><p>{{ description }}</p></div>'
}

// The field validation rules to apply
// You can add custom new rules by adding to the prototype object
FormValidation.prototype.rules = {
  // Default rules per field validation
  defaultRules: {
    required: false, // if the input is required
    minLength: false, // the minimum length of the input
    maxLength: false, // the maximum length of the input
    setValues: false, // list of possible set values to match to, e.g. ['on', 'off']
    inputType: false, // a keyword that matches the input to a an input type, e.g. 'text', 'number', 'email', 'date', 'url', etc., or one of the custom input types
    sameValueAs: false, // {String} selector, {HTMLElement}, {jQueryObject}
    minValue: false, // the minimum value of the input
    maxValue: false, // the maximum value of the input
    custom: false // {Function} function (inputValidation) { ..perform validation via inputValidation object.. }
  },

  /**
   * Field must have value (i.e. not null or undefined)
   *
   * @param {FormValidationInput} inputValidation
   * @param {boolean} isRequired
   */
  required: function (inputValidation, isRequired) {
    // FormValidation
    var self = this

    // Default to inputValidation option rule value
    if (typeof isRequired === 'undefined') isRequired = inputValidation.options.rules.isRequired

    // @debug
    // console.log(isRequired)

    // Can also match to a {String} selector to test if field is required
    // e.g. '#checkbox:checked'
    if (typeof isRequired === 'string' && isRequired !== 'true' && isRequired !== 'false') {
      // @debug
      // console.log(isRequired, $(isRequired).length, getFieldValue(isRequired))
      // @note if the element is hidden, then the field is not required
      isRequired = (Utility.elemExists(isRequired) || Utility.isEmpty(getFieldValue(isRequired))) && !Utility.elemIsHidden(isRequired)
      // @debug
      // console.log('isRequired:', isRequired)
    }

    // Field is required
    if (isRequired && (typeof inputValidation.value === 'undefined' || inputValidation.value === null || inputValidation.value === '')) {
      // Project duration
      if (inputValidation.options.rules.inputType === 'duration') {
        inputValidation.errors.push({
          type: 'inputType',
          description: __.__('Need to choose project duration', 'error-field-input-type-duration')
        })

      // Project motive
      } else if (inputValidation.options.rules.inputType === 'motive') {
        inputValidation.errors.push({
          type: 'inputType',
          description: __.__('Need to choose project motive', 'error-field-input-type-motive')
        })

      // Checkbox is empty
      } else if (inputValidation.type === 'checkbox') {
        inputValidation.errors.push({
          type: 'required',
          description: __.__('Please check the box to continue', 'error-field-required-checkbox')
        })

      // Multiple checkboxes are empty
      } else if (inputValidation.type === 'multi checkbox') {
        inputValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'error-field-required-checkboxes')
        })

      // Radio is empty
      } else if (inputValidation.type === 'radio' || inputValidation.type === 'multi radio') {
        inputValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'error-field-required-radio')
        })

      // Select value is empty
      } else if (inputValidation.type === 'select') {
        inputValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'error-field-required-select')
        })

      // Other type of input is empty
      } else {
        inputValidation.errors.push({
          type: 'required',
          description: __.__('Field value cannot be empty', 'error-field-required')
        })
      }
    }

    // @debug
    // console.log('rules.required', inputValidation)
  },

  /**
   * Minimum length
   *
   * @param {FormValidationInput} inputValidation
   * @param {number} minLength
   */
  minLength: function (inputValidation, minLength) {
    // FormValidation
    var self = this

    // Default to inputValidation option rule value
    if (typeof minLength === 'undefined') minLength = inputValidation.options.rules.minLength

    if (minLength && inputValidation.value.length < minLength) {
      inputValidation.errors.push({
        type: 'minLength',
        description: sprintf(__.__('Please ensure field is at least %d characters long', 'error-field-min-length'), minLength)
      })
    }
  },

  /**
   * Maximum length
   *
   * @param {FormValidationInput} inputValidation
   * @param {number} maxLength
   */
  maxLength: function (inputValidation, maxLength) {
    // FormValidation
    var self = this

    // Default to inputValidation option rule value
    if (typeof maxLength === 'undefined') maxLength = inputValidation.options.rules.maxLength

    if (maxLength && inputValidation.value.length > maxLength) {
      inputValidation.errors.push({
        type: 'maxLength',
        description: sprintf(__.__('Please ensure field does not exceed %d characters', 'error-field-max-length'), maxLength)
      })
    }
  },

  /**
   * Must match one in a set of values
   *
   * @param {FormValidationInput} inputValidation
   * @param {number|string|array} setValues
   */
  setValues: function (inputValidation, setValues) {
    // FormValidation
    var self = this

    // Default to inputValidation option rule value
    if (typeof setValues === 'undefined') setValues = inputValidation.options.rules.setValues

    if (setValues) {
      // Convert string to array
      if (typeof setValues === 'string') {
        // @TODO if needed, could match selector of another field to match value
        // String is set of values: "apple, pear, banana" or "apple pear banana"
        if (/[\s,]+/.test(setValues)) {
          setValues = setValues.split(/[\s,]+/)
        } else {
          setValues = [setValues]
        }
      }

      // Check if value corresponds to one of the set values
      if (!$.inArray(inputValidation.value, inputValidation.options.setValues)) {
        inputValidation.errors.push({
          type: 'setValues',
          description: __.__('Field value not accepted', 'error-field-set-values')
        })
      }
    }
  },

  /**
   * Value must match a specific type, e.g. email, phone, etc.
   *
   * @param {FormValidationInput} inputValidation
   * @param {string} inputType
   */
  inputType: function (inputValidation, inputType) {
    // Non empty values should be check using required form validation attribute
    if (undefined === inputValidation.value || inputValidation.value.length === 0) return

    // FormValidation
    var self = this
    var reValidation
    var testValue

    // Default to inputValidation option rule value
    if (typeof inputType === 'undefined') inputType = inputValidation.options.rules.inputType

    if (inputType) {
      switch (inputType.toLowerCase()) {
        // Supports numbers in FR locale notation as well: 1,0 === 1.0
        case 'number':
          testValue = Sanity(inputValidation.value).normalise.whitespace('')

          // @debug
          // console.log('test number', testValue)

          if (/[^\d.,-]+/.test(testValue)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Field accepts only numbers', 'error-field-input-type-number')
            })

            return false
          }
          break

        case 'integer':
          testValue = Utility.convertStringToFloat(inputValidation.value)

          // @debug
          // console.log('test integer', testValue)

          if (isNaN(testValue) || typeof testValue !== 'number' || testValue % 1 !== 0) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Field accepts only integers', 'error-field-input-type-integer')
            })

            return false
          }
          break

        // Supports numbers in FR locale notation as well: 1,0 === 1.0
        case 'currency':
          if (!(/^[\d]+([,.]([\d]{1,2}))?$/.test(inputValidation.value))) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Field accepts only numbers', 'error-field-input-type-currency')
            })

            return false
          }
          break

        // @note this only assumes FR phone
        case 'tel':
        case 'phone':
        case 'telephone':
          // Use the `pattern` attribute as RegExp if exists
          reValidation = inputValidation.$elem.attr('pattern')
            ? new RegExp(inputValidation.$elem.attr('pattern'))
            // RegExp defined in clients.yml (over complicated)
            // : /((?![^0-9\s+-]).)*/
            // Basic RegExp to target numbers, spaces, hyphens and pluses
            : /^[\d\s+-]+$/
            // More specific RegExp to target French phone numbers
            // : /^(?:(?:\+|00)33|0)[0-9]{9}$/

          // @debug
          // console.log('phone validation', { reValidation: reValidation })

          if (!reValidation.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid telephone number', 'error-field-input-type-telephone')
            })

            return false
          }
          break

        // @note this only assumes FR mobile
        case 'mobile':
          // Use the `pattern` attribute as RegExp if exists
          reValidation = inputValidation.$elem.attr('pattern')
            ? new RegExp(inputValidation.$elem.attr('pattern'))
            // RegExp defined in clients.yml (over complicated)
            // : /((?![^0-9\s+-]).)*/
            // Basic RegExp to target numbers, spaces, hyphens and pluses
            : /^[\d\s+-]+$/
            // More specific RegExp to target French mobile phone numbers
            // : /^(?:(?:\+|00)33|0)[67][0-9]{8}$/

          // @debug
          // console.log('mobile validation', { reValidation: reValidation })

          if (!reValidation.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid mobile number', 'error-field-input-type-mobile')
            })

            return false
          }
          break

        case 'email':
          // New RegExp (http://emailregex.com/)
          if (!/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid email address', 'error-field-input-type-email')
            })

            return false
          }
          break

        case 'date':
        case 'datetime':
          // Use built-in date object for validation
          testValue = new Date(inputValidation.value)
          if (!testValue || testValue.toString() === 'Invalid Date' || isNaN(testValue.getTime())) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid date', 'error-field-input-type-date')
            })

            return false
          }
          break

        case 'bic':
          // See: http://stackoverflow.com/a/19057449
          if (!/^[a-z]{6}[2-9a-z][0-9a-np-z]([a-z0-9]{3}|x{3})?$/i.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid BIC number. Please ensure you have entered your number in correctly', 'error-field-input-type-bic')
            })

            return false
          }
          break

        case 'iban':
          // Uses npm library `iban` to validate
          testValue = Sanity(inputValidation.value).normalise.whitespace('')

          // @debug
          // console.log('validate iban', testValue)

          if (!Iban.isValid(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid IBAN number. Please ensure you have entered your number in correctly', 'error-field-input-type-iban')
            })

            return false
          }
          break

        case 'siret':
          testValue = Sanity(inputValidation.value).normalise.whitespace('')

          // @debug
          // console.log('test siret', testValue)

          // Siret just has to be 14 characters long
          if (testValue.length !== 14) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid SIRET number. Please ensure you have entered your number in correctly', 'error-field-input-type-siret')
            })

            return false
          }
          break

        case 'siren':
          testValue = Sanity(inputValidation.value).normalise.whitespace('')

          // @debug
          // console.log('test siren', testValue)

          // Siren just has to be 9 characters long (or 14 if it's a SIRET)
          if (!/^\d{9}|\d{14}$/.test(testValue)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid SIREN number. Please ensure you have entered your number in correctly', 'error-field-input-type-siren')
            })

            return false
          }
          break

        case 'name':
        case 'firstname':
        case 'lastname':
          if (!/^[A-Za-z\u00C0-\u017F]+([ \-'][A-Za-z\u00C0-\u017F]+)*$/i.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid name. Please ensure you have only entered alphabetic characters', 'error-field-input-type-name')
            })

            return false
          }
          break

        case 'typedfile':
          // Invalid type specified
          if (!inputValidation.$formField.find('select').val()) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Invalid file type', 'error-field-input-type-file-select'),
              target: inputValidation.$formField.find('select')
            })

            return false
          }

          // No file attached
          if (!inputValidation.$formField.find('input[type=file]').val()) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('No file attached', 'error-field-input-type-file-field'),
              target: inputValidation.$formField.find('input[type=file]')
            })

            return false
          }
          break

        case 'password':
          if (!/^(?=.*[a-z])(?=.*[A-Z]).{8,}$/.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Password needs to be at least 8 characters and needs to contain at least one lowercase and one uppercase letter', 'error-field-input-type-password')
            })

            return false
          }
          break
      }
    }

    return true
  },

  /**
   * Value must be the same value as another element's value.
   *
   * @param {FormValidationInput} inputValidation
   * @param {string|HTMLElement|jQuery} sameValueAs - Selector or HTML/jQuery element
   */
  sameValueAs: function (inputValidation, sameValueAs) {
    // FormValidation
    var self = this

    // Default to inputValidation option rule value
    if (typeof sameValueAs === 'undefined') sameValueAs = inputValidation.options.rules.sameValueAs

    if (sameValueAs) {
      var $compareElem = $(sameValueAs)
      if ($compareElem.length > 0) {
        if ($compareElem.val() != inputValidation.value) {
          var compareElemLabel = getLabelForElem($compareElem).replace(/\*.*$/i, '')
          inputValidation.errors.push({
            type: 'sameValueAs',
            description: sprintf(__.__('Field doesn\'t match %s', 'error-field-same-value-as'), '<label for="' + $compareElem.attr('id') + '"><strong>' + compareElemLabel + '</strong></label>')
          })

          return false
        }
      }
    }

    return true
  },

  /**
   * Value must be greater than or equal to a minimum value
   *
   * @param {FormValidationInput} inputValidation
   * @param {number} minValue
   */
  minValue: function (inputValidation, minValue) {
    var self = this

    if (typeof minValue === 'undefined') minValue = inputValidation.options.rules.minValue

    var valueToCheck = Utility.convertStringToFloat(inputValidation.value)

    if (minValue && parseFloat(valueToCheck) < minValue) {
      inputValidation.errors.push({
        type: 'minValue',
        description: sprintf(__.__('Amounts below %s are not allowed', 'error-field-min-value'), __Utility.formatNumber(minValue, 0))
      })

      return false
    }

    return true
  },

  /**
   * Value must be less than or than or equal to a maximum value
   *
   * @param {FormValidationInput} inputValidation
   * @param {number} maxValue
   */
  maxValue: function (inputValidation, maxValue) {
    var self = this

    if (typeof maxValue === 'undefined') maxValue = inputValidation.options.rules.maxValue

    var valueToCheck = Utility.convertStringToFloat(inputValidation.value)

    if (maxValue && parseFloat(valueToCheck) > maxValue) {
      inputValidation.errors.push({
        type: 'maxValue',
        description: sprintf(__.__('Amounts above %s are not allowed', 'error-field-max-value'), __Utility.formatNumber(maxValue, 2))
      })

      return false
    }

    return true
  },

  /**
   * Value is processed using a custom function within the inputValidation's options' rules.
   *
   * @param {FormValidationInput} inputValidation
   * @param {Function} custom
   */
  custom: function (inputValidation, custom) {
    // FormValidation
    var self = this

    // Default to inputValidation option rule value
    if (typeof custom === 'undefined') custom = inputValidation.options.rules.custom

    if (typeof custom === 'function') {
      // For custom validations, ensure you modify the inputValidation object accordingly
      custom.apply(self, [inputValidation, self])
    }
  }
}

/**
 * Show notifications on the group/input.
 *
 * @param {FormValidationGroup|FormValidationInput} validation
 */
FormValidation.prototype.getNotificationsElem = function (validation) {
  var self = this
  var $notifications

  // Explicitly not set
  if (validation.$notifications === false) {
    return false
  }

  // Already set
  if (Utility.elemExists(validation.$notifications)) {
    // @debug
    // console.log('getNotificationsElem: already set')
    return validation.$notifications
  }

  // Find in the form field
  if (validation.validation === 'input') {
    $notifications = validation.$formField.find(validation.options.notificationsElem).first()

  // Find it anywhere else
  } else {
    $notifications = $(validation.options.notificationsElem).first()
  }

  // Build it
  if ($notifications.length === 0) {
    // @debug
    // console.log('getNotificationsElem: build')
    $notifications = $(self.templates.messagesList)
  }

  // Add to DOM
  if (!Utility.elemExists(validation.$notifications)) {
    if (validation.validation === 'input') {
      // @debug
      // console.log('getNotificationsElem: append to field', validation.$formField)
      validation.$formField.append($notifications)
    } else {
      // @debug
      // console.log('getNotificationsElem: prepend to elem')
      validation.$elem.prepend($notifications)
    }
  }

  // @debug
  // console.log('getNotificationsElem', { validation: validation, $notifications: $notifications })

  return $notifications
}

// Render notification messages to the element
FormValidation.prototype.renderMessagesToElem = function (messages, elem) {
  var self = this

  // @debug
  // console.log('renderMessagesToElem', { messages: messages, elem: elem, self: self })

  // Invalid selector
  if (!elem || !Utility.checkSelector(elem)) return

  var $elem = $(elem)
  if ($elem.length === 0 || messages.length === 0) return

  // Remove previous messages
  $elem.html('')

  // Generate messages HTML
  if (messages.length > 0) {
    var messagesHtml = ''
    $.each(messages, function (i, message) {
      // Check if message has a target element
      var targetLabel = ''
      if (message.hasOwnProperty('target') && $(message.target).length > 0) {
        var $target = $(message.target)

        // If no ID set, create a random string ID to enable the label to target the element
        if (!$target.attr('id')) {
          $target.attr('id', Utility.randomString())
        }

        targetLabel = '<label for="' + $target.attr('id') + '">' + getLabelForElem(message.target) + ': </label>'
      }

      // Output message HTML
      messagesHtml += Templating.replace(self.templates.messagesListItem, [{
        targetLabel: targetLabel,
        classNames: 'ui-formvalidation-message ' + (message.hasOwnProperty('type') && message.type ? 'ui-formvalidation-message-{{ type }}' : '')
      }, message])
    })
    $elem.html(messagesHtml)
  }
}

// Render notification errors to the element
FormValidation.prototype.renderErrorsToElem = function (errors, elem) {
  var self = this
  var $elem = $(elem)
  // @debug
  // console.log('renderErrorsToElem', errors, elem)
  if ($elem.length === 0 || errors.length === 0) return

  // Remove previous messages
  $elem.html('')

  // Generate messages HTML
  if (errors.length > 0) {
    var errorsHtml = ''
    $.each(errors, function (i, error) {
      // Check if error has a target element
      var targetLabel = ''
      if (error.hasOwnProperty('target') && $(error.target).length > 0) {
        var $target = $(error.target)

        // If no ID set, create a random string ID to enable the label to target the element
        if (!$target.attr('id')) {
          $target.attr('id', Utility.randomString())
        }

        targetLabel = '<label for="' + $target.attr('id') + '" class="ui-formvalidation-target">' + getLabelForElem(error.target) + ': </label>'
      }

      // Output error message HTML
      errorsHtml += Templating.replace(self.templates.messagesListItem, [{
        targetLabel: targetLabel,
        classNames: 'ui-formvalidation-error ' + (error.hasOwnProperty('type') && error.type ? 'ui-formvalidation-error-{{ type }}' : '')
      }, error])
    })
    $elem.html(errorsHtml)
  }
}

/*
 * jQuery Plugin
 */
$.fn.uiFormValidation = function (op) {
  // Fire a command to the FormValidation object, e.g. $('[data-formvalidation]').uiFormValidation('validate', {..})
  if (typeof op === 'string' && /^validate|validateInput|validateInputCustom|clear|clearAll$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('FormValidation') && typeof elem.FormValidation[op] === 'function') {
        elem.FormValidation[op].apply(elem.FormValidation, args)
      }
    })

  // Set up a new FormValidation instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('FormValidation')) {
        new FormValidation(elem, op)
      } else {
        $(elem).uiFormValidation('validate')
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
    $(event.target).find('[data-formvalidation]').not('.ui-formvalidation').uiFormValidation()
  })

  // Clear notifications on form reset
  .on('reset', 'form.ui-formvalidation', function () {
    $(this).uiFormValidation('clearAll')
  })

module.exports = FormValidation

/*
 * @debug Testing
 *
 * If you change the above, ensure to uncomment and run the below tests to check it all works.
 */
// console.log('### TESTING FORM VALIDATION ###')

// var checkTestStatus = Utility.checkTestStatus

// var validationRules = FormValidation.prototype.rules

// function mockFormField (elem) {
//   return $(elem).wrap('<div class="form-field"></div>')
// }

// function mockInput (type, value) {
//   var inputType = type || 'text'
//   return mockFormField($('<input type="' + inputType + '" id="mock-input-' + inputType + '-' + Utility.randomString() + '" data-formvalidation-input>').val(value))
// }

// function mockTextarea (value) {
//   return mockFormField($('<textarea id="mock-input-' + Utility.randomString() + '" data-formvalidation-input></textarea>').val(value))
// }

// function mockSelect (values, selected) {
//   var selectValues = Utility.convertStringToArray(values)

//   var selectOptions = ''
//   for (var i = 0; i < selectValues.length; i++) {
//     selectOptions += '<option>' + selectValues[i] + '</option>'
//   }

//   return mockFormField($('<select id="mock-select-' + Utility.randomString() + '" data-formvalidation-input>' + selectOptions + '</select>').val(selected))
// }

// function mockFormValidationInputValidation (elem) {
//   var $elem = $(elem)

//   return {
//     validation: 'input',
//     isValid: false,
//     $elem: $elem,
//     $formField: $elem.closest('.form-field'),
//     $notifications: undefined,
//     value: getFieldValue($elem),
//     type: 'auto',
//     errors: [],
//     messages: [],
//     options: {
//       rules: Utility.inherit({},
//         // Use default rules as a base
//         FormValidation.prototype.rules.defaultRules,

//         // Inherit attribute values
//         ElementAttrsObject(elem, {
//           required: 'data-formvalidation-required',
//           minLength: 'data-formvalidation-minlength',
//           maxLength: 'data-formvalidation-maxlength',
//           setValues: 'data-formvalidation-setvalues',
//           inputType: 'data-formvalidation-type',
//           sameValueAs: 'data-formvalidation-samevalueas',
//           minValue: 'data-formvalidation-minvalue',
//           maxValue: 'data-formvalidation-maxvalue'
//         })
//       ),
//       showNotifications: false,
//       notificationsElem: undefined,
//       render: false,
//       showSuccess: false,
//       showError: false,
//       showAllErrors: true,
//       onbeforevalidate: undefined,
//       onaftervalidate: undefined,
//       onsuccess: undefined,
//       onerror: undefined,
//       oncomplete: undefined
//     }
//   }
// }

// // Build elements to test
// var $testForm = $('<form id="mock-form-' + Utility.randomString() + '" data-formvalidation/>')
// var $testEmail1 = mockInput('email', 'firstname@example.com').appendTo($testForm)
// var $testEmail2 = mockInput('email', 'firstname.lastname@gmail.com').appendTo($testForm)
// var $testEmail3 = mockInput('email', 'firstname.lastname+test@example.com').appendTo($testForm)
// var $testEmail4 = mockInput('email', 'firstname.lastname_123-456@example.co.nz').appendTo($testForm)
// var $testEmail5 = mockInput('email', 'firstname.lastname_123-456@example.co').appendTo($testForm)
// var $testEmail6 = mockInput('email', 'firstname lastname').appendTo($testForm)
// var $testEmail7 = mockInput('email', 'firstnamelastname@').appendTo($testForm)
// var $testEmail8 = mockInput('email', 'firstname lastname @ example . com').appendTo($testForm)
// var $testEmail9 = mockInput('email', 'firstname.lastname@example.c').appendTo($testForm)

// // Check email validation
// var testRuleInputTypeEmail1 = validationRules.inputType(mockFormValidationInputValidation($testEmail1), 'email')
// var testRuleInputTypeEmail2 = validationRules.inputType(mockFormValidationInputValidation($testEmail2), 'email')
// var testRuleInputTypeEmail3 = validationRules.inputType(mockFormValidationInputValidation($testEmail3), 'email')
// var testRuleInputTypeEmail4 = validationRules.inputType(mockFormValidationInputValidation($testEmail4), 'email')
// var testRuleInputTypeEmail5 = validationRules.inputType(mockFormValidationInputValidation($testEmail5), 'email')
// var testRuleInputTypeEmail6 = validationRules.inputType(mockFormValidationInputValidation($testEmail6), 'email')
// var testRuleInputTypeEmail7 = validationRules.inputType(mockFormValidationInputValidation($testEmail7), 'email')
// var testRuleInputTypeEmail8 = validationRules.inputType(mockFormValidationInputValidation($testEmail8), 'email')
// var testRuleInputTypeEmail9 = validationRules.inputType(mockFormValidationInputValidation($testEmail9), 'email')

// console.log('Email 1', $testEmail1, $testEmail1/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail1, true)
// console.log('Email 2', $testEmail2, $testEmail2/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail2, true)
// console.log('Email 3', $testEmail3, $testEmail3/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail3, true)
// console.log('Email 4', $testEmail4, $testEmail4/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail4, true)
// console.log('Email 5', $testEmail5, $testEmail5/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail5, true)
// console.log('Email 6', $testEmail6, $testEmail6/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail6, false)
// console.log('Email 7', $testEmail7, $testEmail7/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail7, false)
// console.log('Email 8', $testEmail8, $testEmail8/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail8, false)
// console.log('Email 9', $testEmail9, $testEmail9/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeEmail9, false)

// // Check number validation
// var $testNumber1 = mockInput('text', '1234567890').appendTo($testForm)
// var $testNumber2 = mockInput('text', '12345.67890').appendTo($testForm)
// var $testNumber3 = mockInput('text', '1 234 567 890,00 €').appendTo($testForm)
// var $testNumber4 = mockInput('text', '-1234e+5').appendTo($testForm)
// var $testNumber5 = mockInput('text', 'nothing to do with numbers').appendTo($testForm)
// var testRuleInputTypeNumber1 = validationRules.inputType(mockFormValidationInputValidation($testNumber1), 'number')
// var testRuleInputTypeNumber2 = validationRules.inputType(mockFormValidationInputValidation($testNumber2), 'number')
// var testRuleInputTypeNumber3 = validationRules.inputType(mockFormValidationInputValidation($testNumber3), 'number')
// var testRuleInputTypeNumber4 = validationRules.inputType(mockFormValidationInputValidation($testNumber4), 'number')
// var testRuleInputTypeNumber5 = validationRules.inputType(mockFormValidationInputValidation($testNumber5), 'number')
// console.log('Number 1', $testNumber1, $testNumber1/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeNumber1, true)
// console.log('Number 2', $testNumber2, $testNumber2/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeNumber2, true)
// console.log('Number 3', $testNumber3, $testNumber3/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeNumber3, true)
// console.log('Number 4', $testNumber4, $testNumber4/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeNumber4, true)
// console.log('Number 5', $testNumber5, $testNumber5/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeNumber5, false)

// // Check integer validation
// var $testInteger1 = mockInput('text', '1234567890').appendTo($testForm)
// var $testInteger2 = mockInput('text', '12345.67890').appendTo($testForm)
// var $testInteger3 = mockInput('text', '1 234 567 890,00 €').appendTo($testForm)
// var $testInteger4 = mockInput('text', '-1234e+5').appendTo($testForm)
// var $testInteger5 = mockInput('text', 'nothing to do with integers').appendTo($testForm)
// var testRuleInputTypeInteger1 = validationRules.inputType(mockFormValidationInputValidation($testInteger1), 'integer')
// var testRuleInputTypeInteger2 = validationRules.inputType(mockFormValidationInputValidation($testInteger2), 'integer')
// var testRuleInputTypeInteger3 = validationRules.inputType(mockFormValidationInputValidation($testInteger3), 'integer')
// var testRuleInputTypeInteger4 = validationRules.inputType(mockFormValidationInputValidation($testInteger4), 'integer')
// var testRuleInputTypeInteger5 = validationRules.inputType(mockFormValidationInputValidation($testInteger5), 'integer')
// console.log('Integer 1', $testInteger1, $testInteger1/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeInteger1, true)
// console.log('Integer 2', $testInteger2, $testInteger2/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeInteger2, false)
// console.log('Integer 3', $testInteger3, $testInteger3/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeInteger3, false)
// console.log('Integer 4', $testInteger4, $testInteger4/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeInteger4, true)
// console.log('Integer 5', $testInteger5, $testInteger5/* .find('input') */.val())
// checkTestStatus(testRuleInputTypeInteger5, false)

// // Check SIREN
// var $testSiren1 = mockInput('text', '123456789').appendTo($testForm)
// var $testSiren2 = mockInput('text', '123 456 789').appendTo($testForm)
// var $testSiren3 = mockInput('text', '123 456 789 asd').appendTo($testForm)
// var testRuleSiren1 = validationRules.inputType(mockFormValidationInputValidation($testSiren1), 'siren')
// var testRuleSiren2 = validationRules.inputType(mockFormValidationInputValidation($testSiren2), 'siren')
// var testRuleSiren3 = validationRules.inputType(mockFormValidationInputValidation($testSiren3), 'siren')
// console.log('Siren 1', $testSiren1, $testSiren1/* .find('input') */.val())
// checkTestStatus(testRuleSiren1, true)
// console.log('Siren 2', $testSiren2, $testSiren2/* .find('input') */.val())
// checkTestStatus(testRuleSiren2, true)
// console.log('Siren 3', $testSiren3, $testSiren3/* .find('input') */.val())
// checkTestStatus(testRuleSiren3, false)

// // Check SIRET
// var $testSiret1 = mockInput('text', '12345678901234').appendTo($testForm)
// var $testSiret2 = mockInput('text', '123 456 789 01234').appendTo($testForm)
// var $testSiret3 = mockInput('text', '123 456 789 01234 asd').appendTo($testForm)
// var testRuleSiret1 = validationRules.inputType(mockFormValidationInputValidation($testSiret1), 'siret')
// var testRuleSiret2 = validationRules.inputType(mockFormValidationInputValidation($testSiret2), 'siret')
// var testRuleSiret3 = validationRules.inputType(mockFormValidationInputValidation($testSiret3), 'siret')
// console.log('Siret 1', $testSiret1, $testSiret1/* .find('input') */.val())
// checkTestStatus(testRuleSiret1, true)
// console.log('Siret 2', $testSiret2, $testSiret2/* .find('input') */.val())
// checkTestStatus(testRuleSiret2, true)
// console.log('Siret 3', $testSiret3, $testSiret3/* .find('input') */.val())
// checkTestStatus(testRuleSiret3, false)
