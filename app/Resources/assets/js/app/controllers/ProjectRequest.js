/*
 * Project Request controller
 */

var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)

$doc.on('ready', function () {
  // Show/hide manager details panel and confirm TOS checkbox if user is/is not manager
  function checkIsManager() {
    if ($('#form-project-create input[name="manager"]:checked').val() === 'no') {
      $('#form-project-create .toggle-if-not-manager').collapse('show')
      $('#form-project-create .toggle-if-manager').collapse('hide')
    } else {
      $('#form-project-create .toggle-if-not-manager').collapse('hide')
      $('#form-project-create .toggle-if-manager').collapse('show')
    }
  }

  checkIsManager()

  $doc.on('change', '#form-project-create input[name="manager"]', function () {
    checkIsManager()
  })
})
