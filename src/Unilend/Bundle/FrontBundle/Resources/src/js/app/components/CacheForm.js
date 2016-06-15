//
// Unilend CacheForm
// Enables form input data to be cached to the browser storage
// Helps with testing and if anyone has an error with filling in forms and has to start again
// @note Forms must have an [id] attribute set and inputs to
//       save/restore within must have a [name] attribute set

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var CacheData = require('CacheData')

// CacheForm
// @public
var CacheForm = {
  /*
   * Settings
   */
  // Set the default storage type
  defaultStorageType: CacheData.defaultStorageType,

  // Supported form field types to save values from
  supportedFormFields: 'input[name], select[name], textarea[name]',

  // Specific unsupported form field types to not save values from
  unsupportedFormFields: '[type="password"], [type="file"]',

  /*
   * Methods
   */
  // Runs through a form's input values and saves all to an object
  // @method save
  // @param {Mixed} elem Either {String} selector, {HTMLElement} or {jQueryObject}
  // @param {String} version A unique version string to save within the storage
  // @param {String} storageType The type of storage to save to, either 'localStorage' or 'sessionStorage'; default storage type is set at top
  // @returns {Boolean}
  save: function (elem, version, storageType) {
    var self = this
    var $form = $(elem).first()
    var $fields
    var formId = $form.attr('id')
    var formData = []
    var dataKey

    // @debug
    // console.log('CacheForm.save', elem, version, storageType)

    // No form element
    if ($form.length === 0) return false

    // Form's already having work done
    if ($form.is('.ui-cacheform-saving-state, .ui-cacheform-restoring-state')) return false

    // Only supports forms with an ID
    if (!formId) return false

    // Set default storage type if value doesn't match
    if (!storageType) storageType = self.defaultStorageType

    // Set a version namespace
    if (Utility.isSet(version)) {
      version = ':' + version
    } else {
      version = ''
    }

    // Generate the dataKey
    dataKey = 'formState:' + formId + version

    // Get supported form fields, remove unsupported form fields
    $fields = $form.find(self.supportedFormFields).not(self.unsupportedFormFields)

    // Mark on the form that it is being saved
    $form.addClass('ui-cacheform-saving-state')

    // Collate all the form's input field values into an {Object} to save into the storage
    $fields.each(function (i, field) {
      var $field = $(field)
      var fieldId = $field.attr('id')
      var fieldName = $field.attr('name')
      var $relatedFields = $fields.filter('[name="' + fieldName + '"]')
      var fieldNameIndex = $relatedFields.index($field)
      var fieldValue = $field.val()
      // @bookmark fieldSetting
      var fieldSetting = {
        id: fieldId,
        name: fieldName,
        nameIndex: fieldNameIndex,
        value: fieldValue
      }
      var addFieldSetting = false

      // Do special stuff with checkboxes
      if ($field.is('[type="checkbox"]')) {
        addFieldSetting = true

        // Reset unchecked checkbox value
        if (!$field.is(':checked')) fieldSetting.value = ''

      // Only allow checked radios to be saved
      } else if ($field.is('[type="radio"]')) {
        if ($field.is(':checked')) addFieldSetting = true

      // Everything else is saved
      } else {
        addFieldSetting = true
      }

      // @debug
      // if ($field.is('[type="checkbox"]')) {
      //   console.log('CacheData.saveFormState: field', {
      //     $form: $form,
      //     $fields: $fields,
      //     dataKey: dataKey,
      //     formData: formData,
      //     $field: $field,
      //     $relatedFields: $relatedFields,
      //     fieldIndex: i,
      //     fieldSetting: fieldSetting
      //   })
      // }

      if (addFieldSetting) {
        // @trigger [input, select, textarea] `CacheForm:saveFormState:fieldSetting`
        $field.trigger('CacheForm:saveFormState:fieldSetting', [{
          $form: $form,
          $fields: $fields,
          dataKey: dataKey,
          formData: formData,
          $field: $field,
          $relatedFields: $relatedFields,
          fieldSetting: fieldSetting
        }])

        // Push the field's settings to the form data
        formData.push(fieldSetting)
      }
    })

    // @debug
    // console.log('CacheForm.save', {
    //   $form: $form,
    //   $fields: $fields,
    //   dataKey: dataKey,
    //   formData: formData
    // })

    // @trigger form `CacheForm:saveFormState:beforeSet`
    $form.trigger('CacheForm:saveFormState:beforeSave', [{
      $form: $form,
      $fields: $fields,
      dataKey: dataKey,
      formData: formData
    }])

    // Remove saving mark on the form
    $form.removeClass('ui-cacheform-saving-state')

    return CacheData.setTo(storageType, dataKey, formData)
  },

  // Restores a form's input values to the saved state
  // @method restore
  // @param {Mixed} elem Either {String} selector, {HTMLElement} or {jQueryObject}
  // @param {String} version A unique version string to save within the storage
  // @param {String} storageType The type of storage to save to, either 'localStorage' or 'sessionStorage'; default storage type is set at top
  // @returns {Mixed} Either {Object} containing form data or {Boolean} false on any errors
  restore: function (elem, version, storageType) {
    var self = this
    var $form = $(elem).first()
    var $fields
    var formId = $form.attr('id')
    var formData = {}

    // No form element
    if ($form.length === 0) return false

    // Only supports forms with an ID
    if (!formId) return false

    // Form's already having work done
    if ($form.is('.ui-cacheform-saving-state, .ui-cacheform-restoring-state')) return false

    // Set default storage type if value doesn't match
    if (!storageType) storageType = self.defaultStorageType

    // Set a version namespace
    if (Utility.isSet(version)) {
      version = ':' + version
    } else {
      version = ''
    }

    // Mark on the form that it is being restored
    $form.addClass('ui-cacheform-restoring-state')

    // Get supported form fields, remove unsupported form fields
    $fields = $form.find(self.supportedFormFields).not(self.unsupportedFormFields)

    // Generate the dataKey
    dataKey = 'formState:' + formId + version

    // Get the form data
    formData = CacheData.getFrom(storageType, dataKey)

    // @debug
    // console.log('CacheForm.restore', {
    //   $form: $form,
    //   $fields: $fields,
    //   dataKey: dataKey,
    //   formData: formData
    // })

    // No data retrieved
    if (Utility.isEmpty(formData)) {
      // Remove restoring mark on the form
      $form.removeClass('ui-cacheform-restoring-state')

      return false
    }

    // @trigger form `CacheForm:restoreFormState:beforeRestore`
    $form.trigger('CacheForm:restoreFormState:beforeRestore', [{
      $form: $form,
      $fields: $fields,
      dataKey: dataKey,
      formData: formData
    }])

    // Restore the values of each field
    $.each(formData, function (i, fieldSetting) {
      var $field = $fields.filter('[name="' + fieldSetting.name + '"]')
      self.restoreField($field, fieldSetting)
    })

    // Remove restoring mark on the form
    $form.removeClass('ui-cacheform-restoring-state')

    // @trigger form `CacheForm:restoreFormState:restored`
    $form.trigger('CacheForm:restoreFormState:restored', [{
      $form: $form,
      $fields: $fields,
      dataKey: dataKey,
      formData: formData
    }])

    return formData
  },

  // Restore an individual form field base on the fieldSetting object
  // Fields can also have multiple inputs
  // @method restoreField
  // @param {Mixed} field Can be {String} selector, {HTMLElement} or {jQueryObject}
  // @param {Object} fieldSetting The settings for the field to restore to (see @bookmark fieldSetting)
  // @return {Void}
  restoreField: function (field, fieldSetting) {
    var self = this
    var $field = $(field)
    var $relatedFields

    if ($field.length === 0) return

    $relatedFields = $field.parents('form').find('[name="' + fieldSetting.name + '"]').not(self.unsupportedFormFields)
    $field.each(function (i, input) {
      if (i === fieldSetting.nameIndex) {
        var $input = $(input)
        var inputValue = $input.val()

        // @debug
        // console.log('CacheData.restoreField', {
        //   $field: $field,
        //   $input: $input,
        //   fieldSetting: fieldSetting
        // })

        // @trigger [input, select, textarea] `CacheForm:restoreFormState:beforeRestore`
        $input.trigger('CacheForm:restoreField:beforeRestore', [{
          $field: $field,
          $input: $input,
          fieldSetting: fieldSetting
        }])

        // Select
        if ($input.is('select')) {
          $input.find('option').each(function (j, option) {
            var $option = $(option)
            if ($option.val() == fieldSetting.value) {
              $option.attr('selected', 'selected')
              return true
            }
          })

        // Checkboxes
        } else if ($input.is('[type="checkbox"]')) {
          // Check if field's setting's value matches the input field's value
          if (fieldSetting.value == inputValue) {
            $input.attr('checked', 'checked')
          } else {
            $input.removeAttr('checked')
          }

        // Radio buttons
        } else if ($input.is('[type="radio"]')) {
          if (fieldSetting.value == inputValue) {
            // Uncheck any related fields
            $relatedFields.not($input).removeAttr('checked')

            // Check if field's setting's value matches the input field's value
            $input.attr('checked', 'checked')
          } else {
            $input.removeAttr('checked')
          }

        // Everything else
        } else {
          $input.val(fieldSetting.value)
        }
      }
    })

    // @trigger [input, select, textarea] `CacheForm:restoreFormState:restored`
    $field.trigger('CacheForm:restoreField:restored', [{
      $field: $field,
      fieldSetting: fieldSetting
    }])
  },

  // Clear the form state
  // @method clear
  // @param {Mixed} form Either {String} selector, {HTMLElement} or {jQueryObject}
  // @param {String} version A unique version string to clear within the storage
  // @param {String} storageType The type of storage to clear, either 'localStorage' or 'sessionStorage'; default storage type is set at top
  // @returns {Void}
  clear: function (elem, version, storageType) {
    var self = this
    var $form = $(elem)
    var formId = $form.attr('id')
    var dataKey

    // No form element
    if ($form.length === 0) return false

    // Only supports forms with an ID
    if (!formId) return false

    // Form's already having work done
    if ($form.is('.ui-cacheform-saving-state, .ui-cacheform-restoring-state')) return false

    // Set default storage type if value doesn't match
    if (!storageType) storageType = self.defaultStorageType

    // Set a version namespace
    if (Utility.isSet(version)) {
      version = ':' + version
    } else {
      version = ''
    }

    // Generate the dataKey
    dataKey = 'formState:' + formId + version

    // Remove from the storage
    CacheData.removeFrom(storageType, dataKey)

    // @trigger [form] `CacheForm:clearFormState:cleared`
    $form.trigger('CacheForm:clearFormState:cleared', [{
      $form: $form,
      dataKey: dataKey
    }])

    return true
  }
}

/*
 * jQuery Plugin for caching a form's inputted data (save, restore and clear)
 */
$.fn.uiCacheForm = function (op) {
  // Test if valid operation to run
  if (typeof op === 'string' && /^(save|restore|clear)$/i.test(op) && CacheForm.hasOwnProperty(op)) {
    var cacheFormOp = CacheForm[op]
    if (typeof cacheFormOp === 'function') {
      // Get any additional args to pass to the functions
      var args = Array.prototype.slice.call(arguments)
      // Get rid of the op arg
      args.shift()

      // Run the operation per element
      return this.each(function (i, elem) {
        var params = args.slice(0, args.length)
        params.unshift(elem)

        // @debug
        // console.log('uiCacheForm', op, params)

        cacheFormOp.apply(CacheForm, params)
      })
    }
  }
}

/*
 * jQuery Events
 */
$(document)
  // If form changes, save its state
  .on('change', 'form[data-cacheform]', function (event) {
    $(this).uiCacheForm('save')
  })
  // If a form is reset, restore its state
  // @todo need to test if use-case supports this
  .on('reset', 'form[data-cacheform]', function (event) {
    $(this).uiCacheForm('restore')
  })
  // On submit, clears the form state from browser cache
  .on('submit', 'form[data-cacheform]', function (event) {
    var $form = $(this)
    if (Utility.convertToPrimitive($form.attr('data-cacheform-clearonsubmit]'))) {
      $form.uiCacheForm('clear')
    }
  })
  // By default restores form states on ready (if there is a state to restore)
  .on('ready', function () {
    $('form[data-cacheform]').uiCacheForm('restore')
  })

module.exports = CacheForm
