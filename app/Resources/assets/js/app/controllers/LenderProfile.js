/*
 * Specific UX events, behaviours and actions for `lender_profile`
 */
var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)
var $html = $('html')
var $body = $('body')

$doc.on('ready', function () {
    // Timers
    var ageTimer = 0
    var pwdTimer = 0
    var ajaxDelay = 2000
    // On document ready, check to see if any errors are in a panel, and then open up that panel's edit area to prompt user to submit correct information
    $('.panel .message-error').first().each(function () {
        var $formArea = $(this).parents('.panel').find('form').first().parents('.collapse')
        $formArea.collapse('show')
    })

    // Make sure that if any form area is opened, that any other opened ones are closed
    $doc.on('show.bs.collapse', '[role="tabpanel"] [role="tablist"].ui-toggle-group > [role="tabpanel"]', function (event) {
        var $target = $(event.target)
        var $targetParentCollapsable = $target.parents('[role="tablist"].ui-toggle-group')
        var $collapsables = $('[role="tabpanel"] [role="tablist"].ui-toggle-group').not($targetParentCollapsable)

        // Hide every second one (which is the edit form)
        $collapsables.each(function (i, elem) {
            $(elem).find('> [role="tabpanel"]:eq(0)').collapse('show')
            $(elem).find('> [role="tabpanel"]:eq(1)').collapse('hide')
        })
    })

    // If nationality or form_of_address (civilite/gender) inputs are modified, display message that ID files need to be updated (`#identity-change-alert-message`)
    $doc.on('change', '[name="nationality"], [name="form_of_address"]', function () {
        $('#identity-change-alert-message').collapse('show')

        // Additionally, mark the identity fileattach fields as requiring new files now
        $('#form-profile-info-identity-files-field .ui-fileattach').uiFileAttach('clear')
    })

    // Show message if any modifications have been made to the fiscal address form inputs
    $doc.on('change', '#form-profile-address-street, #form-profile-address-code, #form-profile-address-ville, #form-profile-address-pays', function () {
        $('#message-change-address').collapse('show')

        // Additionally, mark the justificatif de domicile (housing-certificate) field as requiring new files now
        $('#form-profile-info-domicile-files-field .ui-fileattach').uiFileAttach('clear')
    })

    // When a file has been attached, hide the #message-change-address
    $doc.on('FileAttach:attached', '#form-profile-info-domicile-files-field .ui-fileattach', function () {
        $('#message-change-address').collapse('hide')
    })

    // Update/hide elements if fiscal address form was reset
    $doc.on('reset', '#form-profile-address-edit', function () {
        // Update UI on reset
        // For some weird reason needs to have delay before checking
        setTimeout(function () {
            checkIsHousedByThirdPerson()
            checkIsLivingAbroad()
        }, 200)

        // Collapse the change message
        $('#message-change-address').collapse('hide')
    })

    // Validate the password via AJAX
    $doc.on('keyup', 'input[name="client_new_password"]', function () {
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

    // If enabled (checked), show the file input
    function checkIsHousedByThirdPerson() {
        if ($('#housed-by-third-person').prop('checked')) {
            $('#upload-housed-by-third-person').collapse('show')
        } else {
            $('#upload-housed-by-third-person').collapse('hide')
        }
    }

    checkIsHousedByThirdPerson()
    $doc.on('change', '#housed-by-third-person', function () {
        checkIsHousedByThirdPerson()
    })

    // If change of country to other than France display #us-person and #tax-certificate
    function checkIsLivingAbroad() {
        var checkFR = ($('#form-profile-address-pays').val() == 1)

        // France is selected, so dismiss the US person, tax certificate elements
        if (checkFR) {
            $('#us-person, #tax-certificate').collapse('hide')

            // France is not selected, so reveal the US person, tax certificate elements
        } else {
            $('#us-person, #tax-certificate').collapse('show')
        }
    }

    checkIsLivingAbroad()
    $doc.on('change', '#form-profile-address-pays', function () {
        checkIsLivingAbroad()
    })

    // If #us-person not checked, show #message-us-person
    function checkUSPerson() {
        if ($('#form-profile-no-us-person').prop('checked')) {
            $('#message-us-person').collapse('hide')
        } else {
            $('#message-us-person').collapse('show')
        }
    }

    checkUSPerson()
    $doc.on('change', '#form-profile-no-us-person', function () {
        checkUSPerson()
    })

    // If correspondence address is same as fiscal, show the form details
    function checkCorrespondenceIsSame() {
        if ($('#correspondence-is-same').prop('checked')) {
            $('.profile-correspondence-is-same').collapse('show')
            $('.profile-correspondence-not-same').collapse('hide')
        } else {
            $('.profile-correspondence-is-same').collapse('hide')
            $('.profile-correspondence-not-same').collapse('show')
        }
    }

    checkCorrespondenceIsSame()
    $doc.on('change', '#correspondence-is-same', function () {
        checkCorrespondenceIsSame()
    })

    // Update/hide elements if correspondence address form was reset
    $doc.on('reset', '#form-profile-correspondence-edit', function () {
        // Update UI on reset
        // For some weird reason needs to have delay before checking
        setTimeout(function () {
            checkCorrespondenceIsSame()
        }, 200)
    })

    // If the legal entity's status changes, show/hide certain areas
    // @note this will also affect any other page which has an input named `company_client_status`, such as `inscription_preteur` / `LenderSubscription`
    function checkLegalEntityStatus() {
        var legalEntityStatus

        // Get the value from the various radio buttons
        $('input[name="company_client_status"]').each(function (i, input) {
            if ($(input).prop('checked')) {
                legalEntityStatus = $(input).val()
                return true
            }
        })

        if (legalEntityStatus == 1) {
            $('.legal-entity-status-not-1').collapse('hide')
        } else {
            $('.legal-entity-status-not-1').collapse('show')
        }

        if (legalEntityStatus == 3) {
            $('.legal-entity-status-3').collapse('show')
        } else {
            $('.legal-entity-status-3').collapse('hide')
        }
    }

    checkLegalEntityStatus()
    $doc.on('change', 'input[name="company_client_status"]', function () {
        checkLegalEntityStatus()
    })

    // Show the "Autre" ("Other") text field if the company_external_counsel is equal to 3
    // @note this will also affect any other page which has an input named `company_client_status`, such as `inscription_preteur` / `LenderSubscription`
    function checkEntityExternalCounsel() {
        if ($('select[name="company_external_counsel"]').val() == 3) {
            $('.legal-entity-status-other-field').collapse('show')
        } else {
            $('.legal-entity-status-other-field').collapse('hide')
        }
    }

    checkEntityExternalCounsel()
    $doc.on('change', 'select[name="company_external_counsel"]', function () {
        checkEntityExternalCounsel()
    })

    function updateNotificationSettings() {
        $('#form-alerts input[type="checkbox"]').on('change', function () {
            var switcher = $(this);
            var inputName = switcher.attr('name').split('-');
            var period = inputName[0];
            var typeId = inputName[1];
            $.ajax({
                // Setup AJAX
                url: $('#form-alerts').attr('action'),
                method: $('#form-alerts').attr('method'),
                global: false,
                dataType: 'json',
                data: {
                    period: period,
                    type_id: typeId,
                    active: switcher.is(':checked')
                },
                // Event: received server response
                success: function (data) {
                    if (data == 'ko') {
                        switcher.prop('checked', !switcher.is(':checked'));
                    }
                },
                // Event: server error
                error: function () {
                    switcher.prop('checked', !switcher.is(':checked'));
                }
            })
        })
    }

    updateNotificationSettings()
})
