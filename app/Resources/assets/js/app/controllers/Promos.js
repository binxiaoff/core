/*
 * Promos controller
 */

var $ = require('jquery')
var Utility = require('Utility')

var $doc = $(document)

$doc.on('ready', function () {
  // Ensure promos within row share same height/group name for title
  $('.row .promo').each(function (i, elem) {
    var $row = $(this).parents('.row').first()
    var rowEqualHeightGroup = $row.data('equal-height-group') || 'eh-' + Utility.randomString()
    var $promo = $(elem)
    var $promoTitle = $promo.find('h3').not('.round-number, .promo-highlight').first()

    // Skip this promo
    if (!$promoTitle.length) {
      return
    }

    // Ensure each promo within a row is grouped to only that row
    if (!$row.is('.promo-auto-equal-height')) {
      $row.addClass('promo-auto-equal-height')
      $row.data('equal-height-group', rowEqualHeightGroup)
    }

    // Ensure this promo has the equal heights attributes correctly set
    // This will set across all breakpoints. Change to breakpoint names as {String} if you want to control which breakpoints behaviours are actioned on
    $promoTitle.attr('data-equal-height', '')

    // Use the row's equal height group name
    $promoTitle.attr('data-equal-height-group', rowEqualHeightGroup)
  })

  // The Utility.debounceUpdateWindow should do the rest from here!
  Utility.debounceUpdateWindow()
})