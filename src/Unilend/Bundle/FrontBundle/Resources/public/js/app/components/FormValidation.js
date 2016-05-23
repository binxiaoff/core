/*
 * Unilend Form Validation
 */

// @TODO Dictionary integration

var $ = require('jquery')
var sprintf = require('sprintf-js').sprintf
var Iban = require('iban')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')
var Dictionary = require('Dictionary')
var __ = new Dictionary({
  "en": {
    "errorFieldRequired": "Field cannot be empty",
    "errorFieldRequiredCheckbox": "Please check the box to continue",
    "errorFieldRequiredCheckboxes": "Please select an option to continue",
    "errorFieldRequiredRadio": "Please select an option to continue",
    "errorFieldRequiredSelect": "Please select an option to continue",
    "errorFieldMinLength": "Please ensure field is at least %d characters long",
    "errorFieldMaxLength": "Please ensure field does not exceed %d characters",
    "errorfieldInputTypeNumber": "Not a valid number",
    "errorfieldInputTypeEmail": "Not a valid email address",
    "errorfieldInputTypeTelephone": "Not a valid email telephone number"
  }
}, 'en')

function getLabelForElem (elem) {
  var $elem = $(elem)
  var label = ''
  var labelledBy = $elem.attr('aria-labelledby')
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

    $.each(labelledBy, function (i, label) {
      var $labelledBy = $('#' + label).first()
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

var FormValidation = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0) return

  // Settings
  self.settings = $.extend({
    // The form
    formElem: false,

    // An element that contains notifications (i.e. messages to send to the user)
    notificationsElem: false,

    // Whether to validate on the form or on the individual field event
    validateOnFormEvents: true,
    validateOnFieldEvents: true,

    // The specific events to watch to trigger the form/field validation
    watchFormEvents: 'submit',
    watchFieldEvents: 'keydown blur change',

    // Show successful/errored validation on field
    showSuccessOnField: true,
    showErrorOnField: true,
    showAllErrors: false,

    // Update the view (disable if you have your own rendering callbacks)
    render: true,

    // The callback to fire before validating a field
    onfieldbeforevalidate: function () {},

    // The callback to fire after validating a field
    onfieldaftervalidate: function () {},

    // The callback to fire when a field passed validation successfully
    onfieldsuccess: function () {},

    // The callback to fire when a field did not pass validation
    onfielderror: function () {},

    // The callback to fire before form/group validation
    onbeforevalidate: function () {},

    // The callback to fire after form/group validation
    onaftervalidate: function () {},

    // The callback to fire when the form/group passed validation successfully
    onsuccess: function () {},

    // The callback to fire when the form/group did not pass validation
    onerror: function () {},

    // The callback to fire when the form/group completed validation
    oncomplete: function () {}
  }, ElementAttrsObject(elem, {
    formElem: 'data-formvalidation-formelem',
    notificationsElem: 'data-formvalidation-notificationselem',
    render: 'data-formvalidation-render'
  }), options)

  // Properties
  // -- Get the target form that this validation component is referencing
  if (self.$elem.is('form')) self.$form = self.$elem
  if (self.settings.formElem) self.$form = $(self.settings.formElem)
  if (!self.$form || self.$form.length === 0) self.$form = self.$elem.parents('form').first()
  // -- Get the notifications element
  self.$notifications = $(self.settings.notificationsElem)
  if (self.$notifications.length > 0 && !$.contains(document, self.$notifications[0])) self.$form.prepend(self.$notifications)

  // @debug
  // console.log({
  //   $elem: self.$elem,
  //   $form: self.$form,
  //   settings: self.settings
  // })

  // Setup UI
  self.$elem.addClass('ui-formvalidation')

  /*
   * Methods
   */
  // Validate a single form field
  self.validateField = function (elem, options) {
    var self = this
    var $elem = $(elem).first()

    // Error
    if ($elem.length === 0) return false

    // Field is group of related inputs: checkbox, radio
    if (!$elem.is('input, textarea, select')) {
      // @debug
      // console.log('FormValidation.validateField Error: make sure the elem is—or contains—a input, textarea or select')
      if ($elem.find('input, textarea, select').length === 0) return false
    }

    // Ignore disabled fields
    if ($elem.is(':disabled')) return false

    // Field validation object
    var fieldValidation = {
      isValid: false,
      $elem: $elem,
      $formField: $elem.parents('.form-field').first(),
      value: getFieldValue($elem),
      type: 'auto', // Set to auto-detect type
      errors: [],
      messages: [],
      options: $.extend({
        // Rules to validate this field by
        rules: $.extend({}, self.rules.defaultRules,
        // Inherit attribute values
        ElementAttrsObject(elem, {
          required: 'data-formvalidation-required',
          minLength: 'data-formvalidation-minlength',
          maxLength: 'data-formvalidation-maxlength',
          setValues: 'data-formvalidation-setvalues',
          inputType: 'data-formvalidation-type',
          sameValueAs: 'data-formvalidation-samevalueas'
        })),

        // Options
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
      }, ElementAttrsObject(elem, {
        rules: 'data-formvalidation-rules',
        showSuccess: 'data-formvalidation-showsuccess',
        showError: 'data-formvalidation-showerror',
        render: 'data-formvalidation-render'
      }), options)
    }

    // If rules was set (JSON), expand out into the options
    if (typeof fieldValidation.rules === 'string') {
      var checkRules = JSON.parse(fieldValidation.rules)
      if (typeof checkRules === 'object') {
        fieldValidation.options = $.extend(fieldValidation.options, checkRules)
      }
    }

    // Auto-select inputType validation
    if (fieldValidation.type === 'auto') {
      fieldValidation.type = getFieldType($elem)
    }

    // Make sure to always check non-text input types with the inputType rule
    if (fieldValidation.type !== 'text' && (typeof fieldValidation.options.rules.inputType === 'undefined' || fieldValidation.options.rules.inputType === false)) {
      fieldValidation.options.rules.inputType = fieldValidation.type
    }

    // @debug
    // console.log('fieldValidation', fieldValidation)

    // Before validation you can run a custom callback to process the field value
    if (typeof fieldValidation.options.onbeforevalidate === 'function') {
      fieldValidation.options.onbeforevalidate.apply($elem[0], [self, fieldValidation])
    }
    fieldValidation.$elem.trigger('FormValidation:validateField:beforevalidate', [self, fieldValidation])

    /*
     * Apply the validation rules
     */
    for (var i in fieldValidation.options.rules) {
      // @debug
      // console.log('fieldValidation rules: ' + i, fieldValidation.options.rules[i])
      if (fieldValidation.options.rules[i] && self.rules.hasOwnProperty(i) && typeof self.rules[i] === 'function') {
        // @debug
        // console.log('validating via rule: ' + i, 'condition: ', fieldValidation.options.rules[i])
        self.rules[i].apply(self, [fieldValidation, fieldValidation.options.rules[i]])
      }
    }

    // After validation you can run a custom callback on the results before shown in UI
    if (typeof fieldValidation.options.onaftervalidate === 'function') {
      fieldValidation.options.onaftervalidate.apply($elem[0], [self, fieldValidation])
    }
    fieldValidation.$elem.trigger('FormValidation:validateField:aftervalidate', [self, fieldValidation])

    // Field validation errors
    fieldValidation.$formField.removeClass('ui-formvalidation-error ui-formvalidation-success')
    if (fieldValidation.errors.length > 0) {
      fieldValidation.isValid = false

      // Trigger error
      if (typeof fieldValidation.options.onerror === 'function') {
        fieldValidation.options.onerror.apply(self, [self, fieldValidation])
      }
      fieldValidation.$elem.trigger('FormValidation:validateField:error', [self, fieldValidation])

      // Show error messages on the field
      if (fieldValidation.options.showError && fieldValidation.options.render) {
        // @debug
        // console.log('Validation error', fieldValidation.errors)
        fieldValidation.$formField.addClass('ui-formvalidation-error')
        fieldValidation.$formField.find('.ui-formvalidation-messages').html('')
        if (fieldValidation.errors.length > 0) {
          if (fieldValidation.$formField.find('.ui-formvalidation-messages').length === 0) {
            fieldValidation.$formField.append('<ul class="ui-formvalidation-messages"></ul>')
          }
          var formFieldErrorsHtml = ''
          $.each(fieldValidation.errors, function (i, item) {
            formFieldErrorsHtml += '<li>' + item.description + '</li>'
            if (!fieldValidation.options.showAllErrors) return false
          })
          fieldValidation.$formField.find('.ui-formvalidation-messages').html(formFieldErrorsHtml)
        }
      }

    // Field validation success
    } else {
      fieldValidation.isValid = true

      // Trigger error
      if (typeof fieldValidation.options.onsuccess === 'function') {
        fieldValidation.options.onsuccess.apply(self, [self, fieldValidation])
      }
      fieldValidation.$elem.trigger('FormValidation:validateField:success', [self, fieldValidation])

      // Show success messages on the field
      if (fieldValidation.options.showSuccess && fieldValidation.options.render) {
        // @debug
        // console.log('Validation success', fieldValidation.messages)
        fieldValidation.$formField.addClass('ui-formvalidation-success')
        fieldValidation.$formField.find('.ui-formvalidation-messages').html('')
        if (fieldValidation.messages.length > 0) {
          if (fieldValidation.$formField.find('.ui-formvalidation-messages').length === 0) {
            fieldValidation.$formField.append('<ul class="ui-formvalidation-messages"></ul>')
          }
          var formFieldMessagesHtml = ''
          $.each(fieldValidation.messages, function (i, item) {
            formFieldMessagesHtml += '<li>' + item.description + '</li>'
          })
          fieldValidation.$formField.find('.ui-formvalidation-messages').html(formFieldMessagesHtml)
        }
      }
    }

    // @debug
    // console.log('fieldValidation', fieldValidation)

    // Trigger complete
    if (typeof fieldValidation.options.oncomplete === 'function') {
      fieldValidation.options.oncomplete.apply(self, [self, fieldValidation])
    }
    fieldValidation.$elem.trigger('FormValidation:validateField:complete', [self, fieldValidation])

    return fieldValidation
  }

  // Validate the element's fields
  // @returns {Boolean}
  self.validate = function (options) {
    var self = this

    var groupValidation = $.extend({
      // Properties
      isValid: false,
      $elem: self.$elem,
      $notifications: self.$notifications,
      fields: [],
      validFields: [],
      erroredFields: [],

      // Options
      render: self.settings.render,

      // Events
      onbeforevalidate: self.settings.onbeforevalidate,
      onaftervalidate: self.settings.onaftervalidate,
      onerror: self.settings.onerror,
      onsuccess: self.settings.onsuccess,
      oncomplete: self.settings.oncomplete
    }, options)

    // Trigger before validate
    if (typeof groupValidation.onbeforevalidate === 'function') groupValidation.onbeforevalidate.apply(self, [self, groupValidation])
    groupValidation.$elem.trigger('FormValidation:validate:beforevalidate', [self, groupValidation])

    // Validate each field
    self.getFields().each(function (i, input) {
      var fieldValidation = self.validateField(input)
      groupValidation.fields.push(fieldValidation)

      // Filter collection via valid/errored
      if (fieldValidation.isValid) {
        groupValidation.validFields.push(fieldValidation)
      } else {
        groupValidation.erroredFields.push(fieldValidation)
      }
    })

    // Trigger after validate
    if (typeof groupValidation.onaftervalidate === 'function') groupValidation.onaftervalidate.apply(self, [self, groupValidation])
    groupValidation.$elem.trigger('FormValidation:validate:aftervalidate', [self, groupValidation])

    // Error
    groupValidation.$notifications.html('')
    if (groupValidation.erroredFields.length > 0) {
      groupValidation.isValid = false
      if (typeof groupValidation.onerror === 'function') groupValidation.onerror.apply(self, [self, groupValidation])
      groupValidation.$elem.trigger('FormValidation:validate:error', [self, groupValidation])

      // Render to view
      if (groupValidation.render) {
        groupValidation.$notifications.html('<div class="message-error"><p>There are errors with the form below. Please check ensure your information has been entered correctly before continuing.</p></div>')
      }

    // Success
    } else {
      groupValidation.isValid = true
      if (typeof groupValidation.onsuccess === 'function') groupValidation.onsuccess.apply(self, [self, groupValidation])
      groupValidation.$elem.trigger('FormValidation:validate:success', [self, groupValidation])
    }

    // Trigger complete
    if (typeof groupValidation.oncomplete === 'function') groupValidation.oncomplete.apply(self, [self, groupValidation])
    groupValidation.$elem.trigger('FormValidation:validate:complete', [self, groupValidation])

    return groupValidation
  }

  // Clears group's form fields of errors
  self.clear = function () {
    var self = this
    self.getFields().each(function (i, input) {
      $(elem)
        .parents('.form-field').removeClass('ui-formvalidation-error ui-formvalidation-success')
        .find('.ui-formvalidation-messages').html('')
    })
    self.$notifications.html('')
    self.$elem.trigger('FormValidation:clear', [self])
  }

  // Clears whole form (all groups)
  self.clearAll = function () {
    var self = this
    self.$form.uiFormValidation('clear')
      .find('[data-formvalidation]').uiFormValidation('clear')
  }

  // Get the collection of fields
  self.getFields = function () {
    var self = this
    return self.$elem.find('[data-formvalidation-field]')
  }

  // Events on the form
  self.$form.on(self.settings.watchFormEvents, function (event) {
    if (self.settings.validateOnFormEvents) {
      var formValidation = {
        isValid: false,
        groups: [],
        validGroups: [],
        erroredGroups: []
      }

      // Validate each group within the form
      $(this).find('[data-formvalidation]').each(function (i, elem) {
        var groupValidation = elem.FormValidation.validate()
        formValidation.groups.push(groupValidation)

        // Valid group
        if (groupValidation.isValid) {
          formValidation.validGroups.push(groupValidation)

        // Invalid group
        } else {
          formValidation.erroredGroups.push(groupValidation)
        }
      })

      // Error
      if (formValidation.erroredGroups.length > 0) {
        formValidation.isValid = false
        if (typeof self.settings.onerror === 'function') self.settings.onerror.apply(self, [self, formValidation])
        $(this).trigger('FormValidation:error', [self, formValidation])

      // Success
      } else {
        formValidation.isValid = true
        if (typeof self.settings.onsuccess === 'function') self.settings.onsuccess.apply(self, [self, formValidation])
        $(this).trigger('FormValidation:success', [self, formValidation])
      }

      // Stop any submitting happening
      if (!formValidation.isValid) return false
    }
  })

  // Events on the fields
  self.getFields().on(self.settings.watchFieldEvents, function (event) {
    if (self.settings.validateOnFieldEvents) {
      self.validateField(event.target)
    }
  })

  // console.log(self.getFields())

  // Attach FormValidation instance to element
  self.$elem[0].FormValidation = self
  return self
}

/*
 * Prototype functions and properties shared between all instances of FormValidation
 */
// The field validation rules to apply
// You can add custom new rules by adding to the prototype object
FormValidation.prototype.rules = {
  // Default rules per field validation
  defaultRules: {
    required: false,  // if the input is required
    minLength: false, // the minimum length of the input
    maxLength: false, // the maximum length of the input
    setValues: false, // list of possible set values to match to, e.g. ['on', 'off']
    inputType: false, // a keyword that matches the input to a an input type, e.g. 'text', 'number', 'email', 'date', 'url', etc.
    sameValueAs: false, // {String} selector, {HTMLElement}, {jQueryObject}
    custom: false // {Function} function (fieldValidation) { ..perform validation via fieldValidation object.. }
  },

  // Field must have value (i.e. not null or undefined)
  required: function (fieldValidation, isRequired) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof isRequired === 'undefined') isRequired = fieldValidation.options.rules.isRequired

    if (isRequired && (typeof fieldValidation.value === 'undefined' || typeof fieldValidation.value === 'null' || fieldValidation.value === '')) {

      // Checkbox is empty
      if (fieldValidation.type === 'checkbox') {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Please check the box to continue', 'errorFieldRequiredCheckbox')
        })

      // Multiple checkboxes are empty
      } else if (fieldValidation.type === 'multi checkbox') {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'errorFieldRequiredCheckboxes')
        })

      // Radio is empty
      } else if (fieldValidation.type === 'radio' || fieldValidation.type === 'multi radio') {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'errorFieldRequiredRadio')
        })

      } else if (fieldValidation.type === 'select') {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'errorFieldRequiredSelect')
        })

      // Other type of input is empty
      } else {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Field value cannot be empty', 'errorFieldRequired')
        })
      }
    }

    // @debug
    // console.log('rules.required', fieldValidation)
  },

  // Minimum length
  minLength: function (fieldValidation, minLength) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof minLength === 'undefined') minLength = fieldValidation.options.rules.minLength

    if (minLength && fieldValidation.value.length < minLength) {
      fieldValidation.errors.push({
        type: 'minLength',
        description: sprintf(__.__('Please ensure field is at least %d characters long', 'errorFieldMinLength'), minLength)
      })
    }
  },

  // Maximum length
  maxLength: function (fieldValidation, maxLength) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof maxLength === 'undefined') maxLength = fieldValidation.options.rules.maxLength

    if (maxLength && fieldValidation.value.length > maxLength) {
      fieldValidation.errors.push({
        type: 'maxLength',
        description: sprintf(__.__('Please ensure field does not exceed %d characters', 'errorFieldMaxLength'), maxLength)
      })
    }
  },

  // Set Values
  setValues: function (fieldValidation, setValues) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof setValues === 'undefined') setValues = fieldValidation.options.rules.setValues

    if (setValues) {
      // Convert string to array
      if (typeof setValues === 'string') {
        if (/[\s,]+/.test(setValues)) {
          setValues = setValues.split(/[\s,]+/)
        } else {
          setValues = [setValues]
        }
      }

      // Check if value corresponds to one of the set values
      if (!$.inArray(fieldValidation.value, fieldValidation.options.setValues)) {
        fieldValidation.errors.push({
          type: 'setValues',
          description: __.__('Field value not accepted', 'errorFieldSetValues')
        })
      }
    }
  },

  // Input Type
  inputType: function (fieldValidation, inputType) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof inputType === 'undefined') inputType = fieldValidation.options.rules.inputType

    if (inputType) {
      switch (inputType.toLowerCase()) {
        case 'number':
          if (/[^\d-\.]+/.test(fieldValidation.value)) {
            fieldValidation.errors.push({
              type: 'inputType',
              description: __.__('Field accepts only numbers', 'errorFieldInputTypeNumber')
            })
          }
          break

        case 'phone':
        case 'telephone':
        case 'mobile':
          // Allowed: +33 644 911 250
          //          (0) 12.34.56.78.90
          //          856-6688
          if (!/^\+?[0-9\-\. ]{6,}$/.test(fieldValidation.value)) {
            fieldValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid telephone number', 'errorFieldInputTypePhone')
            })
          }
          break

        case 'email':
          // Allowed: matt.scheurich@example.com
          //          mattscheurich@examp.le.com
          //          mattscheurich1983@example-email.co.nz
          //          matt_scheurich@example.email.address.net.nz
          if (!/^[a-z0-9\-_\.]+\@[a-z0-9\-\.]+\.[a-z0-9]{2,}(?:\.[a-z0-9]{2,})*$/i.test(fieldValidation.value)) {
            fieldValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid email address', 'errorFieldInputTypeEmail')
            })
          }
          break

        case 'iban':
          // Uses npm library `iban` to validate
          if (!Iban.isValid(fieldValidation.value.replace(/\s+/g, ''))) {
            fieldValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid IBAN number. Please ensure you have entered your number in correctly', 'errorFieldInputTypeIban')
            })
          }
          break
      }
    }
  },

  // Same Value as
  sameValueAs: function (fieldValidation, sameValueAs) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof sameValueAs === 'undefined') sameValueAs = fieldValidation.options.rules.sameValueAs

    if (sameValueAs) {
      var $compareElem = $(sameValueAs)
      if ($compareElem.length > 0) {
        if ($compareElem.val() != fieldValidation.value) {
          var compareElemLabel = getLabelForElem($compareElem).replace(/\*.*$/i, '')
          fieldValidation.errors.push({
            type: 'sameValueAs',
            description: sprintf(__.__('Field doesn\'t match %s', 'errorFieldSameValueAs'), '<label for="' + $compareElem.attr('id') + '"><strong>' + compareElemLabel + '</strong></label>')
          })
        }
      }
    }
  },

  // Custom
  custom: function (fieldValidation, custom) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof custom === 'undefined') custom = fieldValidation.options.rules.custom

    if (typeof custom === 'function') {
      // For custom validations, ensure you modify the fieldValidation object accordingly
      custom.apply(self, [self, fieldValidation])
    }
  }
}

/*
 * jQuery Plugin
 */
$.fn.uiFormValidation = function (op) {
  // Fire a command to the FormValidation object, e.g. $('[data-formvalidation]').uiFormValidation('validate', {..})
  if (typeof op === 'string' && /^validate|validateField|clear|clearAll$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.FormValidation && typeof elem.FormValidation[op] === 'function') {
        elem.FormValidation[op].apply(elem.FormValidation, args)
      }
    })

  // Set up a new FormValidation instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.FormValidation) {
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
  // -- Instatiate any element with [data-formvalidation] on ready
  .on('ready', function () {
    $('[data-formvalidation]').uiFormValidation()
  })

  // --

module.exports = FormValidation
