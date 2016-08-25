/*
 * Specific UX events, behaviours and actions for `lender_subscription`
 */

var $ = require('jquery')
var Utility = require('Utility')
var FormValidation = require('FormValidation')

var $doc = $(document)
var $html = $('html')
var $body = $('body')

$doc.on('ready', function () {
  // Timers
  var ageTimer = 0
  var pwdTimer = 0
  var ajaxDelay = 2000
  
  /*
   * Step 1
   */

  // Change from person to society form
  function checkClientType() {
    var $clientTypePerson = $('input[name="client_type"][value="person"]:visible')

    // @debug
    // console.log('checkClientType', $clientTypePerson.prop('checked'))

    // Show person form
    if ($clientTypePerson.prop('checked')) {
      // Clear form validation messages on hiding form
      $('#form-lender-legal_entity .ui-formvalidation').uiFormValidation('clearAll')

      // Show person form, hide legal entity form
      $('#form-lender-person').show()
      $('#form-lender-legal_entity').hide()

      // Change values across inputs on both forms
      $('input[name="client_type"][value="person"]').prop('checked', true)
      $('input[name="client_type"][value="legal_entity"]').removeProp('checked')

    // Show legal entity form
    } else {
      // Clear form validation messages on hiding form
      $('#form-lender-person .ui-formvalidation').uiFormValidation('clearAll')

      // Hide person form, show legal entity form
      $('#form-lender-person').hide()
      $('#form-lender-legal_entity').show()

      // Change values across inputs on both forms
      $('input[name="client_type"][value="person"]').removeProp('checked')
      $('input[name="client_type"][value="legal_entity"]').prop('checked', true)
    }
  }
  checkClientType()
  $doc.on('change', 'input[name="client_type"]:visible', function () {
    checkClientType()
  })
  
  // Show/hide postal address section
  function checkAddressIsNotSame() {
    // @debug
    // console.log('checkAddressIsNotSame', $('.form-preter-create:visible .toggle-correspondence-address').prop('checked'))

    if ($('.form-preter-create:visible .toggle-correspondence-address').prop('checked')) {
      $('.form-preter-create:visible .form-lender-fieldset-postal-address').collapse('hide')
    } else {
      $('.form-preter-create:visible .form-lender-fieldset-postal-address').collapse('show')
    }
  }
  checkAddressIsNotSame()
  $doc.on('change', '.form-preter-create:visible .toggle-correspondence-address', function () {
    checkAddressIsNotSame()
  })

  // Validate the birthdate via AJAX
  $doc.on('change', '#form-lender-person-birthdate', function () {
    var $elem = $(this)

    var date = {
      day_of_birth: $elem.find('[data-formvalidation-date="day"]').val(),
      month_of_birth: $elem.find('[data-formvalidation-date="month"]').val(),
      year_of_birth: $elem.find('[data-formvalidation-date="year"]').val()
    }

    // Debounce AJAX
    clearTimeout(ageTimer)
    ageTimer = setTimeout(function () {
      // Talk to AJAX
      $.ajax({
        url: '/inscription_preteur/ajax/age',
        method: 'post',
        data: date,
        global: false,
        success: function (data) {
          if (data && data.hasOwnProperty('error')) {
            $elem.parents('.ui-formvalidation').uiFormValidation('validateInput', $elem, {
              rules: {
                // Use custom rule to invoke an error on the field
                custom: function (inputValidation) {
                  inputValidation.isValid = false
                  inputValidation.errors.push({
                    type: 'date',
                    description: data.error
                  })
                }
              }
            })
          }
        }
      })
    }, ajaxDelay)
  })

  // Validate the password via AJAX
  $doc.on('change', 'input[name="client_password"]', function () {
    var $elem = $(this)

    // Do quick JS validation before doing AJAX validation
    // @note FormValidation already supports checking with the minLength rule
    if ($elem.val().length >= 6) return false

    // Debounce AJAX
    clearTimeout(pwdTimer)
    pwdTimer = setTimeout(function () {
      // Talk to AJAX
      $.ajax({
        url: '/inscription_preteur/ajax/pwd',
        method: 'post',
        data: {
          client_password: $elem.val()
        },
        global: false,
        success: function (data) {
          if (data && data.hasOwnProperty('error')) {
            $elem.parents('.ui-formvalidation').uiFormValidation('validateInput', $elem, {
              rules: {
                // Use custom rule to invoke an error on the field
                custom: function (inputValidation) {
                  inputValidation.isValid = false
                  inputValidation.errors.push({
                    type: 'minLength',
                    description: data.error
                  })
                }
              }
            })
          }
        }
      })
    }, ajaxDelay)
  })
  
  // When AutoComplete has set a value for the commune de naissance / birthplace field, ensure the hidden insee value is set by extracting the value from the AutoComplete's result item
  $doc.on('AutoComplete:setInputValue:complete', '#form-lender-person-birth-place', function (event, elemAutoComplete, newValue, item) {
    var $item = $(item)
    var itemLabel = $item.text()
    var itemValue = $item.attr('data-value') || $item.text()
    $('#form-lender-person-birth-place').val(itemLabel)
    $('#form-lender-person-birth-place-insee').val(itemValue)
  })

  /*
   * Step 2
   */
  
  
})