// Lib Dependencies
var $ = require('jquery')

$('#project-single-login-form').submit(function() {
  var csrfField = $('input[name=_csrf_token]')

  if (csrfField.length > 0 && '' == csrfField.val()) {
    $.ajax({
      type: 'GET',
      url: '/security/csrf-token/authenticate',
      global: false,
      async: false,
      success: function (response) {
        csrfField.val(response)
      }
    })
  }
})
