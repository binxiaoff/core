/*
 * Lender Dashboard controller
 */

var $ = require('jquery')
var Utility = require('Utility')
var Templating = require('Templating')

var $doc = $(document)

// Make the user level graphic animate
$doc.on('ready', function () {
  var $userLevel = $('.dashboard-userlevel.ui-user-level-0')
  var userLevel = parseInt($userLevel.attr('data-userlevel'), 10)
  setTimeout(function () {
    if (userLevel) {
      $userLevel.addClass('ui-user-level-' + userLevel).removeClass('ui-user-level-0')
    }
  }, 250)
})
