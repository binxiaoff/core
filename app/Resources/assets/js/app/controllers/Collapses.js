/**
 * Collapse
 *
 * Show/hide elements using bootstrap, but with some extra behaviours.
 */

var $ = require('jquery')
var Utility = require('Utility')
var $doc = $(document)

$doc.on('ready', function () {
  // Mark on [data-toggle] triggers that the collapseable is/isn't collapsed
  $doc.on('shown.bs.collapse', function (event) {
    var targetTrigger = '[data-toggle="collapse"][data-target="#' + $(event.target).attr('id') + '"],[data-toggle="collapse"][href="#' + $(event.target).attr('id') + '"]'
    $(targetTrigger).addClass('ui-collapse-open')
  })
    .on('hidden.bs.collapse', function (event) {
      var targetTrigger = '[data-toggle="collapse"][data-target="#' + $(event.target).attr('id') + '"],[data-toggle="collapse"][href="#' + $(event.target).attr('id') + '"]'
      $(targetTrigger).removeClass('ui-collapse-open')
    })

  // Force a collapse open
  $doc.on(Utility.clickEvent, '.ui-collapse-set-open', function (event) {
    var $elem = $(this)
    var $target = $($elem.attr('target') || $elem.attr('html')).filter('.collapse')

    if ($target.length > 0) $target.collapse('show')
  })

  /*
   * Collapse Toggle Groups
   * Because bootstrap is quite opinionated and I don't need all their markup and specific classes
   * this here is a work-around to use the collapse within toggleable groups, i.e. accordians
   * minus all the reliance on certain element classes, e.g. `.panel`
   * To use this behaviour, the collapsable element must be within a `.ui-toggle-group`
   */
  $doc.on('show.bs.collapse', '.ui-toggle-group > .collapse', function (event) {
    var $target = $(event.target)

    // Only fire on direct descendants of the `.ui-toggle-group`
    if ($target.is('.ui-toggle-group > .collapse')) {
      var $group = $target.parents('.ui-toggle-group').first()
      var $siblings = $group.children().filter('.collapse').not($target)

      // @debug
      // console.log('ui-toggle-group hiding siblings', event.target, $siblings)

      // This one is already showing, so hide the others via bootstrap
      $siblings.collapse('hide')
    }
  })
})
