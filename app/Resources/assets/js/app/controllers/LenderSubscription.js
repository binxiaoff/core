/*
 * Specific UX events, behaviours and actions for `lender_subscription`
 * @todo this page is disgustingly messy. Needs a refactor
 */

var $ = require('jquery')
var Utility = require('Utility')
var FormValidation = require('FormValidation')

var $doc = $(document)

// Timers
var ageTimer = 0
var pwdTimer = 0
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

// Validate address by code/postcode/zip, city and country values
function checkPostCodeCity(elem) {

  var $zip = $($(elem).attr('data-autocomplete-address-zipelem'))
  var $city = $($(elem).attr('data-autocomplete-address-cityelem'))
  var $country = $($(elem).attr('data-autocomplete-address-countryelem'))

  if (!$zip.val()) {
    $zip.parents('.form-field').removeClass('ui-formvalidation-success').addClass('ui-formvalidation-error')
    return false
  }
  if (!$city.val()) {
    $city.parents('.form-field').removeClass('ui-formvalidation-success').addClass('ui-formvalidation-error')
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
function checkBirthCity() {

  var $insee = $('#form-lender-person-birth-city-insee')
  var $country = $('#form-lender-person-birth-country')

  var $errorField = $insee.parents('.panel').find('.autocomplete-error')
  $insee.parents('.form-field').addClass('ajax-validating')

  $.ajax({
    url: '/inscription_preteur/ajax/check-city-insee',
    method: 'GET',
    data: {
      insee: $insee.val(),
      country: $country.val()
    }
  }).done(function(data){
    if (data.status == true) {
      $insee.parents('.form-field').removeClass('ui-formvalidation-error')
      $errorField.hide()
    } else {
      $insee.parents('.form-field').addClass('ui-formvalidation-error')
      $errorField.show()
    }
  }).complete(function () {
      $insee.parents('.form-field').removeClass('ajax-validating')
  })
}

// Split Birthplace value to City and Insee
function splitBirthplaceValue(elemAutoComplete, elemItem, newValue) {
    // The newValue is the insee code, so get the city from the element item
    var cityValue = $(elemItem).text().replace(/ ?\(.*$/, '')
    var codeValue = newValue

    cached.birthPlace.city = cityValue
    cached.birthPlace.insee = codeValue

    // Set this element's value to the city value
    elemAutoComplete.$input.val(cityValue)
    $('#form-lender-person-birth-city-insee').val(codeValue);

    // console.log('set city ' + cityValue)
    // console.log('set insee ' + codeValue)
}

// Hide AutoComplete errors
function hideAutocompleteError(elem) {
    elem.parents('fieldset').find('.autocomplete-error').hide()
    $(elem.attr('data-autocomplete-address-cityelem')).parents('.form-field').removeClass('ui-formvalidation-error')
    $(elem.attr('data-autocomplete-address-zipelem')).parents('.form-field').removeClass('ui-formvalidation-error')
}

// Prevent submission of blank file uploads
function removeBlankFileField() {
  var form = document.getElementById('form-lender-step-2');
  var childNodes = form.querySelectorAll('input[type=file]');
  for (var i = 0; i < childNodes.length; i++) {
    if (childNodes[i].files.length === 0) {
      childNodes[i].parentElement.removeChild(childNodes[i]);
    }
  }
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

  // Validate address city and zip if country is France
  $doc.on('change', '#form-lender-person-fiscal-address-country, #form-lender-legal-entity-fiscal-address-country, #form-lender-person-postal-address-country, #form-lender-legal-entity-postal-address-country', function () {
    var $dataAddressElem = $(this).parents('.panel').find('[data-autocomplete-address]').first()
    checkPostCodeCity($dataAddressElem[0])
  })

  // Validate birthplace city and insee if country is France
  $doc.on('change', '#form-lender-person-birth-country', function () {
    if ($(this).val() === '1') {
      $('#form-lender-person-birth-city').uiAutoComplete('enable')
      checkBirthCity()
    } else {
      $('#form-lender-person-birth-city').uiAutoComplete('disable')
    }
  })

  // Set value for birthplace city and insee
  $doc.on('AutoComplete:setInputValue:complete', '#form-lender-person-birth-city', function (event, elemAutoComplete, newValue, elemItem) {
    // Empty value given
    newValue = (newValue + '').trim()
    if (!newValue) return
      splitBirthplaceValue(elemAutoComplete, elemItem, newValue)
  })

  // Handling outside click while birthplace results are open
  $doc.on('AutoComplete:showResults:complete', '#form-lender-person-birth-city', function (event, elemAutoComplete) {

    // Bind outside click event - user didn't finish the autocomplete
    $doc.bind('click.outsideAutoComplete',function(event) {

        if ($(event.target).parents('.autocomplete-results').length === 0) {

            // Set Post Code and City based on first value in the results
            var elemItem = elemAutoComplete.$target.find('ul li:first-child')
            var newValue = elemItem.find('a').attr('data-value')
            splitBirthplaceValue(elemAutoComplete, elemItem[0], newValue)

            // Unbind outside click event
            $doc.unbind('click.outsideAutoComplete')
        }
    });

    // Unbind outside click if user clicks on a result - user finished the autocomplete
    elemAutoComplete.$target.on('click', 'a', function () {
        $doc.unbind('click.outsideAutoComplete')
    })

    // Unbind outside click if user presses Enter or Right arrow  - user finished the autocomplete
    elemAutoComplete.$target.on('keydown', '.autocomplete-results a:focus', function (event) {
        if (event.which === 39 || event.which === 13) {
            $doc.unbind('click.outsideAutoComplete')
        }
    })
  })

  // Validate address on blur (if the autocomplete results is closed)
  $doc.on('change', '[data-autocomplete-address]', function (event) {
    if (this.AutoComplete.track.resultsOpen === false) {
      checkPostCodeCity(this)
    }
  })

  // Validate birthplace on blur (if the autocomplete results is closed)
  $doc.on('change', '#form-lender-person-birth-city', function (event) {
    if (this.AutoComplete.track.resultsOpen === false) {
      checkBirthCity()
    }
  })

  // Refresh autocomplete error status when new pair values (post code - city) are set
  $doc.on('AutoComplete:setInputValue:complete', '[data-autocomplete-address], #form-lender-person-birth-city', function () {
      hideAutocompleteError($(this))
  })

  // Prevent tab key to force users to use the autocomplete dropdown
  $doc.on('keydown', '[data-autocomplete-address], #form-lender-person-birth-city', function (event) {
      if (event.which === 9) {
        event.preventDefault()
      }
      if ($(this).attr('id') === 'form-lender-person-birth-city') {
        $('#form-lender-person-birth-city-insee').val('')
      }
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

  $doc.on('submit', '#form-lender-step-2', function () {
    removeBlankFileField();
  })

})



