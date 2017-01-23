// Lib Dependencies
var $ = require('jquery')

var getLoginToken = function (async) {
  $('.project-single-form-access input').unbind("focus.loginForm");
  var csrfField = $("input[name=_csrf_token]");
  if ('' == csrfField.val()) {
    $.ajax({
      type: 'GET',
      url: '/security/csrf-token/authenticate',
      global: false,
      async: async,
      success: function (response) {
        csrfField.val(response);
      }
    })
  }
}

$('.project-single-form-access input').bind('focus.loginForm', function() {
  getLoginToken(true)
});

$('.project-single-form-access input[type=submit]').click(function() {
  getLoginToken(false)
});


