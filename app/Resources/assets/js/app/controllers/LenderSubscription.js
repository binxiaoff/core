/*
 * Specific UX events, behaviours and actions for `lender_subscription`
 * @todo this page is disgustingly messy. Needs a refactor
 */

var $ = require('jquery')
var Utility = require('Utility')
var FormValidation = require('FormValidation')

var $doc = $(document)
var $html = $('html')
var $body = $('body')

// Timers
var ageTimer = 0
var pwdTimer = 0
var fiscalAddrTimer = 0
var postalAddrTimer = 0
var birthPlaceTimer = 0
var debounceAjaxDelay = 1000
var ajaxDelay = 2000

var cached = {
  fiscalAddress: {
    city: '',
    zip: ''
  },
  postalAddress: {
    city: '',
    zip: ''
  },
  birthPlace: {
    city: '',
    insee: ''
  }
}

// Change from person to society form
function checkClientType() {
  var $clientTypePerson = $('input[name="client_type"][value="person"]:visible')

  // @debug
  // console.log('checkClientType', $clientTypePerson.prop('checked'))

  // Show person form
  if ($clientTypePerson.prop('checked')) {
    // Clear form validation messages on hiding form
    $('#form-lender-legal-entity .ui-formvalidation').uiFormValidation('clearAll')

    // Show person form, hide legal entity form
    $('#form-lender-person').show()
    $('#form-lender-legal-entity').hide()

    // Change values across inputs on both forms
    $('input[name="client_type"][value="person"]').prop('checked', true)
    $('input[name="client_type"][value="legal_entity"]').removeProp('checked')

    // Show legal entity form
  } else {
    // Clear form validation messages on hiding form
    $('#form-lender-person .ui-formvalidation').uiFormValidation('clearAll')

    // Hide person form, show legal entity form
    $('#form-lender-person').hide()
    $('#form-lender-legal-entity').show()

    // Change values across inputs on both forms
    $('input[name="client_type"][value="person"]').removeProp('checked')
    $('input[name="client_type"][value="legal_entity"]').prop('checked', true)
  }
}

// Show/hide postal address section
function checkAddressIsNotSame() {
  // @debug
  // console.log('checkAddressIsNotSame', $('.form-preter-create:visible .toggle-correspondence-address').prop('checked'))

  $('.form-preter-create').each(function () {
    var form = $(this)

    if (form.find('.toggle-correspondence-address').prop('checked')) {
      form.find('.form-lender-fieldset-postal-address').collapse('hide')
    } else {
      form.find('.form-lender-fieldset-postal-address').collapse('show')
    }
  })
}

function debounceAjax (ajaxTimer, ajaxFunction, delayDuration) {
  clearTimeout(ajaxTimer)
  ajaxTimer = setTimeout(ajaxFunction, delayDuration || debounceAjaxDelay)
}

// Validate address by code/postcode/zip, city and country values
function checkPostCodeCity($zip, $city, $country) {
  if (!$zip.val() || !$city.val()) {
    $($zip, $city).parents('.form-field').removeClass('ui-formvalidation-success').addClass('ui-formvalidation-error')
    return false
  }

  var $errorField = $zip.parents('.panel').find('.autocomplete-error')
  $city.parents('.form-field').addClass('ajax-validating')

  $.ajax({
    url: '/inscription_preteur/ajax/check-city',
    method: 'GET',
    data: {
      city: $city.val(),
      zip: $zip.val(),
      country: $country.val()
    }
  }).done(function (data) {
    if (data.status == true) {
      $errorField.hide()
      $zip.parents('.form-field').removeClass('ui-formvalidation-error')
      $city.parents('.form-field').removeClass('ui-formvalidation-error')
    } else {
      $errorField.show()
      $zip.parents('.form-field').addClass('ui-formvalidation-error')
      $city.parents('.form-field').addClass('ui-formvalidation-error')
    }
  }).complete(function () {
    $city.parents('.form-field').removeClass('ajax-validating')
  })
}

// Validate address by city and country values
function checkCity($city, $country) {

  var $errorField = $city.parents('.panel').find('.autocomplete-error')
  $city.parents('.form-field').addClass('ajax-validating')

  $.ajax({
    url: '/inscription_preteur/ajax/check-city',
    method: 'GET',
    data: {
      city: $city.val(),
      country: $country.val()
    }
  }).done(function(data){
    if (data.status == true) {
      $city.parents('.form-field').removeClass('ui-formvalidation-error')
      $errorField.hide()
    } else {
      $city.parents('.form-field').addClass('ui-formvalidation-error')
      $errorField.show()
    }
  }).complete(function () {
    $city.parents('.form-field').removeClass('ajax-validating')
  })
}

// Hide AutoComplete errors
function hideAutocompleteErrors() {
  $('.autocomplete-error').hide()
}

$doc.on('ready', function () {
  /*
   * Step 1
   */

  checkClientType()
  $doc.on('change', '#devenir-preteur input[name="client_type"]:visible', function () {
    checkClientType()
  })

  checkAddressIsNotSame()
  $doc.on('change', '#devenir-preteur .form-preter-create:visible .toggle-correspondence-address', function () {
    checkAddressIsNotSame()
    setTimeout(checkAddressIsNotSame, 500)
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
            $elem.parents('.ui-formvalidation').uiFormValidation('validateInputCustom', $elem, function (inputValidation) {
              inputValidation.isValid = false
              inputValidation.errors.push({
                type: 'date',
                description: data.error
              })
            })
          }
        }
      })
    }, ajaxDelay)
  })

  // Validate the password via AJAX
  $doc.on('keyup', 'input[name="client_password"]', function () {
    var $elem = $(this)

    // Do quick JS validation before doing AJAX validation
    // @note FormValidation already supports checking with the minLength rule
    if ($elem.val().length >= 6) return false

    // Debounce AJAX
    clearTimeout(pwdTimer)
    pwdTimer = setTimeout(function () {
      // Talk to AJAX
      $.ajax({
        url: '/security/ajax/password',
        method: 'post',
        data: {
          client_password: $elem.val()
        },
        global: false,
        success: function (data) {
          if (data && data.hasOwnProperty('error')) {
            $elem.parents('.ui-formvalidation').uiFormValidation('validateInputCustom', $elem, function (inputValidation) {
              inputValidation.isValid = false
              inputValidation.errors.push({
                type: 'minLength',
                description: data.error
              })
            })
          }
        }
      })
    }, ajaxDelay)
  })

  // When AutoComplete has set a value for the address fields, cache the values to then re-check they haven't changed before form submit
  $doc.on('AutoComplete:address:city', '#form-lender-person-fiscal-address-city, #form-lender-legal-entity-fiscal-address-city', function (event, cityValue) {
    cached.fiscalAddress.city = cityValue
  })
  $doc.on('AutoComplete:address:code', '#form-lender-person-fiscal-address-zip, #form-lender-legal-entity-fiscal-address-code', function (event, codeValue) {
    cached.fiscalAddress.zip = codeValue
  })
  $doc.on('AutoComplete:address:city', '#form-lender-person-postal-address-city, #form-lender-legal-entity-postal-address-city', function (event, cityValue) {
    cached.postalAddress.city = cityValue
  })
  $doc.on('AutoComplete:address:code', '#form-lender-person-postal-address-zip, #form-lender-legal-entity-postal-address-code', function (event, codeValue) {
    cached.postalAddress.zip = codeValue
  })

    // When AutoComplete has set a value for the commune de naissance / birthplace field, ensure the hidden insee value is set by extracting the value from the AutoComplete's result item
    $doc.on('AutoComplete:setInputValue:complete', '#form-lender-person-birth-city', function (event, elemAutoComplete, newValue, elemItem) {
      // Empty value given
      newValue = (newValue + '').trim()
      if (!newValue) return

      // The newValue is the insee code, so get the city from the element item
      var cityValue = $(elemItem).text()
      var codeValue = newValue

      cached.birthPlace.city = cityValue
      cached.birthPlace.insee = codeValue

      // Set this element's value to the city value
      $(this).val(cityValue)

      // @debug
      // console.log(elemItem, newValue, cached)
    })

    // If a user changes to a US nationality, show the error message
    $doc.on('change', '#form-lender-person-nationality', function () {
        if ($('#form-lender-person-nationality').val() == 35) {
            $("#error-message-selected-nationality-other").show()
        } else {
            $("#error-message-selected-nationality-other").hide()
        }
    })

    // Validate fiscal address city/code on blur
    $doc.on('change', '#devenir-preteur input[name="fiscal_address_zip"]:visible, #devenir-preteur input[name="fiscal_address_city"]:visible', function (event) {
      debounceAjax(fiscalAddrTimer, function () {
        checkPostCodeCity($('input[name="fiscal_address_zip"]:visible'), $('input[name="fiscal_address_city"]:visible'), $('input[name="fiscal_address_country"]:visible'))
      })
    })

    // Validate postal address city/code on blur
    $doc.on('change', '#devenir-preteur input[name="postal_address_zip"]:visible, #devenir-preteur input[name="postal_address_city"]:visible', function (event) {
      debounceAjax(postalAddrTimer, function () {
        checkPostCodeCity($('input[name="postal_address_zip"]:visible'), $('input[name="postal_address_city"]:visible'), $('input[name="postal_address_country"]:visible'))
      })
    })

    // Validate birthplace city/code on blur
    // @todo this should also check the insee code to ensure it hasn't been tampered, but better to do after submit (server side validation)
    $doc.on('change', '#form-lender-person-birth-city, #form-lender-person-birth-country', function (event) {
      debounceAjax(birthPlaceTimer, function () {
        checkCity($('#form-lender-person-birth-city'), $('#form-lender-person-birth-country'))
      })
    })

    // Validate that the person has filled in all their information correctly
    $doc.on('submit', '#devenir-preteur .form-preter-create-info-person', function (event) {
        var formValid = true
        if ($('.ajax-validating').length > 0) {
          formValid = false
        }

        if ($(this).find('.autocomplete-error:visible').length > 0) {
          formValid = false
        }

        if (formValid == false) {
            event.preventDefault()
            return false
        }
    })
})



