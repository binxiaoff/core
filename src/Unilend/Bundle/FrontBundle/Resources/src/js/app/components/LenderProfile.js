/*
 * Specific UX events, behaviours and actions for `lender_profile`
 */
var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)
var $html = $('html')
var $body = $('body')

$doc.on('ready', function () {
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
    
  // If enabled (checked), show the file input
  function checkIsHousedByThirdPerson () {
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
  function checkIsLivingAbroad () {
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
    console.log('checkUSPerson', $('#form-profile-no-us-person').prop('checked'))

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
  function checkCorrespondenceIsSame () {
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
})
