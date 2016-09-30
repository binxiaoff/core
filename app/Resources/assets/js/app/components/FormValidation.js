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
  var value = undefined

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
  var type = undefined

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

  // Settings
  self.settings = $.extend({
    // The form
    formElem: false,

    // An element that contains notifications (i.e. messages to inform to the user)
    notificationsElem: self.templates.notificationsElem,

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
    notificationsElem: 'data-formvalidation-notificationselem',
    render: 'data-formvalidation-render'
  }), options)

  // Elements
  // -- Get the target form that this validation component is referencing
  if (self.$elem.is('form')) self.$form = self.$elem
  if (self.settings.formElem) self.$form = $(self.settings.formElem)
  if (!self.$form || self.$form.length === 0) self.$form = self.$elem.parents('form').first()

  // -- Get/set the notifications element for this form/group
  self.$notifications = $(self.settings.notificationsElem)
  if (self.$notifications.length === 0) self.$notifications = $(self.templates.notifications)
  if (!Utility.elemExists(self.$notifications)) self.$elem.prepend(self.$notifications)

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
      if ($form.is('[data-formvalidation]')) $groups = $groups.add($form)

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
      if (!formValidation.isValid) return false
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

  // Field validation object
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

    // Options
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
          maxValue: 'data-formValidation-maxvalue'
        })
      ),

      // Options
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

      // Clear old messages
      inputValidation.$notifications = self.getNotificationsElem(inputValidation)
      inputValidation.$notifications.html('')

      // Render messages
      if (inputValidation.errors.length > 0) {
        self.renderErrorsToElem((inputValidation.options.showAllErrors
          ? inputValidation.errors
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
      inputValidation.$notifications.html('')

      // Render messages
      if (inputValidation.errors.length > 0) {
        self.renderMessagesToElem(inputValidation.messages, inputValidation.$notifications)
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

// Validate the element's fields
// @returns {Boolean}
FormValidation.prototype.validate = function (options) {
  var self = this

  // Validation object
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

    // Options
    options: $.extend({
      render: self.settings.render,
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

  // Error
  groupValidation.$notifications.html('')
  if (groupValidation.erroredFields.length > 0) {
    groupValidation.isValid = false
    if (typeof groupValidation.onerror === 'function') groupValidation.onerror.apply(self, [self, groupValidation])
    // @trigger group `FormValidation:validate:error`
    groupValidation.$elem.trigger('FormValidation:validate:error', [self, groupValidation])

    // Render to view
    if (groupValidation.options.render) {
      groupValidation.$notifications.html('<div class="message-error"><p>' + __.__('There are errors with the form below. Please ensure your information has been entered correctly before continuing.', 'error-group-has-errors') + '</p></div>')
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
  self.getInputs().each(function (i, input) {
    $(input)
      .parents('.form-field').removeClass('ui-formvalidation-error ui-formvalidation-success')
      .find('.ui-formvalidation-messages').html('')
  })
  self.$notifications.html('')

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
  messagesListItem: '<li class="{{ classNames }}">{{ targetLabel }}{{ description }}</li>'
}

// The field validation rules to apply
// You can add custom new rules by adding to the prototype object
FormValidation.prototype.rules = {
  // Default rules per field validation
  defaultRules: {
    required: false,  // if the input is required
    minLength: false, // the minimum length of the input
    maxLength: false, // the maximum length of the input
    setValues: false, // list of possible set values to match to, e.g. ['on', 'off']
    inputType: false, // a keyword that matches the input to a an input type, e.g. 'text', 'number', 'email', 'date', 'url', etc., or one of the custom input types
    sameValueAs: false, // {String} selector, {HTMLElement}, {jQueryObject}
    minValue: false, // the minimum value of the input
    maxValue: false, // the maximum value of the input
    custom: false // {Function} function (inputValidation) { ..perform validation via inputValidation object.. }
  },

  // Field must have value (i.e. not null or undefined)
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
    if (isRequired && (typeof inputValidation.value === 'undefined' || typeof inputValidation.value === 'null' || inputValidation.value === '')) {

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

  // Minimum length
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

  // Maximum length
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

  // Set Values
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

  // Input Type
  inputType: function (inputValidation, inputType) {
    // FormValidation
    var self = this

    // Default to inputValidation option rule value
    if (typeof inputType === 'undefined') inputType = inputValidation.options.rules.inputType

    if (inputType) {
      switch (inputType.toLowerCase()) {
        case 'number':
          if (/[^\d-\.]+/.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Field accepts only numbers', 'error-field-input-type-number')
            })
          }
          break

        case 'tel':
        case 'phone':
        case 'telephone':
        case 'mobile':
          // Allowed: +33 644 911 250
          //          (0) 12.34.56.78.90
          //          856-6688
          // if (!/^\+?[0-9\-\. \(\)]{6,}$/.test(inputValidation.value)) {

          // DEV-578 mobile phone number format upon input (exclude + before area code)
          if (/\D+/.test(inputValidation.value) || inputValidation.value.length < 6) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid telephone number', 'error-field-input-type-telephone')
            })
          }
          break

        case 'email':
          // Allowed: matt.scheurich@example.com
          //          mattscheurich@examp.le.com
          //          mattscheurich1983@example-email.co.nz
          //          matt_scheurich@example.email.address.net.nz
          if (!/^[-a-z0-9~!$%^&*_=+}{\'?]+(\.[-a-z0-9~!$%^&*_=+}{\'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid email address', 'error-field-input-type-email')
            })
          }
          break

        case 'date':
        case 'datetime':
          // Use built-in date object for validation
          var testDate = new Date(inputValidation.value)
          if (!testDate || testDate.toString() === 'Invalid Date' || testDate.getTime() === NaN) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid date', 'error-field-input-type-date')
            })
          }
          break

        case 'bic':
          // See: http://stackoverflow.com/a/19057449
          if (!/^[a-z]{6}[2-9a-z][0-9a-np-z]([a-z0-9]{3}|x{3})?$/i.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid BIC number. Please ensure you have entered your number in correctly', 'error-field-input-type-bic')
            })
          }
          break

        case 'iban':
          // Uses npm library `iban` to validate
          if (!Iban.isValid(inputValidation.value.replace(/\s+/g, ''))) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid IBAN number. Please ensure you have entered your number in correctly', 'error-field-input-type-iban')
            })
          }
          break

        case 'siret':
          // @debug
          // console.log('siret validation', inputValidation.value.replace(/\s+/g, '').length)

          // Siret just has to be 14 characters long
          if (inputValidation.value.replace(/\s+/g, '').length !== 14) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid SIRET number. Please ensure you have entered your number in correctly', 'error-field-input-type-siret')
            })
          }
          break

        case 'siren':
          // @debug
          // console.log('siren validation', inputValidation.value.replace(/\s+/g, '').length)

          // Siren just has to be 9 characters long
          if (inputValidation.value.replace(/\s+/g, '').length !== 9) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid SIREN number. Please ensure you have entered your number in correctly', 'error-field-input-type-siren')
            })
          }
          break

        case 'name':
        case 'firstname':
        case 'lastname':
          if (!/^[a-zA-Z\u00C0-\u017F]+$/i.test(inputValidation.value)) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid name. Please ensure you have only entered alphabetic characters', 'error-field-input-type-name')
            })
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
          }

          // No file attached
          if (!inputValidation.$formField.find('input[type=file]').val()) {
            inputValidation.errors.push({
              type: 'inputType',
              description: __.__('No file attached', 'error-field-input-type-file-field'),
              target: inputValidation.$formField.find('input[type=file]')
            })
          }

          console.log(inputValidation)
          break
      }
    }
  },

  // Same Value as
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
        }
      }
    }
  },

  // Minimum value allowed
  minValue: function (inputValidation, minValue) {
    var self = this

    if (typeof minValue === 'undefined') minValue = inputValidation.options.rules.minValue

    if (minValue && inputValidation.value < minValue) {
      inputValidation.errors.push({
        type: 'minValue',
        description: sprintf(__.__('Amounts below %s are not allowed', 'error-field-min-value'), __Utility.formatNumber(minValue, 0))
      })
    }
  },

  // Maximum value allowed
  maxValue: function (inputValidation, maxValue) {
    var self = this

    if (typeof maxValue === 'undefined') maxValue = inputValidation.options.rules.maxValue

    if (maxValue && inputValidation.value > maxValue) {
      inputValidation.errors.push({
        type: 'maxValue',
        description: sprintf(__.__('Amounts above %s are not allowed', 'error-field-max-value'), __Utility.formatNumber(maxValue, 0))
      })
    }
  },

  // Custom function to validate
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

// Show notifications on the field
// @TODO finish this
FormValidation.prototype.getNotificationsElem = function (validation) {
  var self = this
  var $notifications

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
  // console.log('getNotificationsElem', $notifications)
  return $notifications
}

// Render notification messages to the element
FormValidation.prototype.renderMessagesToElem = function (messages, elem) {
  var self = this
  var $elem = $(elem)
  // @debug
  // console.log('renderMessagesToElem', messages, elem)
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
  if (typeof op === 'string' && /^validate|validateInput|clear|clearAll$/.test(op)) {
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
    console.log('reset form with formvalidation')
    $(this).uiFormValidation('clearAll')
  })

module.exports = FormValidation
