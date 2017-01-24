// Lib Dependencies
var $ = require('jquery')

var setCookie = function() {

    $.ajax({
        type: 'POST',
        url: '/accept-cookies',
        global: false,
        success: function(response) {
            var CookieWrap = $('[data-cookies]')
            $(CookieWrap).hide()
        }
    })

}

function readCookie(name) {
  var nameEQ = encodeURIComponent(name) + "=";
  var ca = document.cookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
  }
  return null;
}

var displayCookieBox = function() {
  var accepted = readCookie('acceptCookies');
  if (accepted) {
    var CookieWrap = $('[data-cookies]')
    $(CookieWrap).hide()
  }
}

$(document).on('click', '[data-cookies-accept]', function() {
    setCookie()
})

$(document).on('ready', function () {
  displayCookieBox()
})
