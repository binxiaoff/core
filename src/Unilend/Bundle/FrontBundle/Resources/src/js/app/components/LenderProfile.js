
saveChanges = function () {
    event.preventDefault()
    var form = $(this)
    var formData = new FormData($(this)[0])

    console.log('Is this even being referenced?')

    $.ajax({
        method: form.attr('method'),
        url: form.attr('action'),
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function (data) {
            console.log('success');
            // $('article#' + data.target).html(data.template);
            form.after('sucess')
        },
        error: function (jqXHR, responseText) {
            form.after('responseText');
            // $('#lender-operations-loans').find(':input').unbind().on('change', reloadTabContent);
        }
    })
};
// $('#save-bank-details').submit(saveChanges)

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
  $doc.on('change', '[name="nationality"], [name="form_of_address"]', function (event) {
    Utility.revealElem('#identity-change-alert-message')
    
    // Additionally, mark the identity fileattach fields as requiring new files now
    $('#form-profile-info-identity-files-field .ui-fileattach').uiFileAttach('clear')
  })
})
