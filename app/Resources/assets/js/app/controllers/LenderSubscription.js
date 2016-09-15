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

    checkClientType()
    $doc.on('change', 'input[name="client_type"]:visible', function () {
        checkClientType()
    })

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

    checkAddressIsNotSame()
    $doc.on('change', '.form-preter-create:visible .toggle-correspondence-address', function () {
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

    $doc.on('change', '#form-lender-person-nationality', function () {
        if($('#form-lender-person-nationality').val() == 35){
            $("#error-message-selected-nationality-other").show();
        } else {
            $("#error-message-selected-nationality-other").hide();
        }
    })


    $doc.on('submit', '.form-preter-create', function (event) {
        hideAutocompleteErrors()
        var formValid = true

        if (checkPostCodeCity($('input[name="fiscal_address_zip"]:visible'), $('input[name="fiscal_address_city"]:visible'), $('select[name="fiscal_address_country"]:visible')) == false ){
            formValid = false
        }

        if ($('.toggle-correspondence-address').prop('checked') == false) {
            if (checkPostCodeCity($("input[name='postal_address_zip']:visible"), $('input[name="postal_address_city"]:visible'), $('select[name="postal_address_country"]:visible'), false) == false) {
                formValid = false
            }
        }

        if ($("#form-lender-person-nationality").is('visible')
            && '' == $("#form-lender-person-nationality").val()
            || ('' == $('#form-lender-person-birth-place-insee').val() && 1 == $('#form-lender-person-birth-place').val())
            || checkCity($('#form-lender-person-birth-place'), $('#form-lender-person-nationality')) == false) {
            $("#form-lender-person-birth-place").parent('.form-field').removeClass('ui-formvalidation-success').addClass('ui-formvalidation-error')
            formValid  = false;
        }

        if (formValid == false) {
            event.preventDefault()
            return
        }

        return
    })
})


function checkPostCodeCity($zip, $city, $country) {
    if ('' == $zip.val() || '' == $city.val()) {
        $zip.parents('.form-field').removeClass('ui-formvalidation-success').addClass('ui-formvalidation-error')
        $city.parents('.form-field').removeClass('ui-formvalidation-success').addClass('ui-formvalidation-error')
        return false;
    }

    var $errorField = $zip.parents('.panel').find('.address-autocomplete-error')
    var result = false

    $.ajax({
        url: '/inscription_preteur/ajax/check-city',
        method: 'GET',
        async : false,
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
            result = true;
        } else {
            $errorField.show()
            $zip.parents('.form-field').addClass('ui-formvalidation-error');
            $city.parents('.form-field').addClass('ui-formvalidation-error');
            result = false;
        }
    });

    return result;
}

function checkCity($city, $country) {

    var $errorField = $city.parents('.panel').find('.birth-place-autocomplete-error')

    $.ajax({
        url: '/inscription_preteur/ajax/check-city',
        method: 'GET',
        async : false,
        data: {
            city: $city.val(),
            country: $country.val()
        }
    }).done(function(data){
        if (data.status == true) {
            $city.parents('.form-field').removeClass('ui-formvalidation-error');
            $errorField.hide()
            return true;
        } else {
            $city.parents('.form-field').addClass('ui-formvalidation-error');
            $errorField.show()
            return false;
        }
    });
}

function hideAutocompleteErrors(){
    $('.address-autocomplete-error').each(function() {
        $(this).hide()
    })
    $('.birth-place-autocomplete-error').hide()
}

