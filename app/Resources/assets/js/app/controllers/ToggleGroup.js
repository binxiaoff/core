/**
 * Toggle Group
 *
 * Hooks into bootstrap collapse events to show/hide siblings or other related toggleable panels.
 *
 * Example HTML:
 *
 * ```
 *   <!-- Toggleable parent -->
 *   <div data-toggle-parent>
 *
 *     <!-- Toggleable group -->
 *     <div role="tablist" data-toggle-group>
 *       <div role="tabpanel">
 *         <!-- View section -->
 *       </div>
 *       <div role="tabpanel">
 *         <!-- Edit section -->
 *       </div>
 *     </div>
 *
 *     <!-- Toggleable group -->
 *     <div role="tablist" data-toggle-group>
 *       <div role="tabpanel">
 *         <!-- View section -->
 *       </div>
 *       <div role="tabpanel">
 *         <!-- Edit section: if this is $.collapse('show'), it will hide all of its immediate siblings, and then show view/hide edit in other toggle groups -->
 *       </div>
 *     </div>
 *
 *   </div>
 * ```
 */

var $ = require('jquery')
var $doc = $(document)

// Make sure that if any form area is opened, that any other opened ones are closed
$doc.on('show.bs.collapse', '[data-toggle-parent] [data-toggle-group][role="tablist"] > [role="tabpanel"]', function (event) {
  var $targetPanel = $(event.target)
  var $siblingPanels = $targetPanel.siblings().filter('[role="tabpanel"]')
  var $targetGroup = $targetPanel.parents('[data-toggle-group][role="tablist"]').first()
  var $parentPanel = $targetGroup.parents('[data-toggle-parent]').first()
  var groupId = $targetGroup.data('toggle-group-id')
  var $relatedGroups = $(groupId
    ? '[data-toggle-group-id="' + groupId + '"]'
    : '[role="tablist"].ui-toggle-group')

  if ($parentPanel.length) {
    $parentPanel.siblings().each(function (i, elem) {
      var $elemGroups = $(elem).find('[data-toggle-group][role="tablist"]')
      if ($elemGroups.length) {
        $relatedGroups.add($elemGroups)
      }
    })
  }

  // @debug
  // console.log('[data-toggle-group] show.bs.collapse', {
  //   event: event,
  //   $targetPanel: $targetPanel,
  //   $siblingPanels: $siblingPanels,
  //   $targetGroup: $targetGroup,
  //   $parentPanel: $parentPanel,
  //   groupId: groupId,
  //   $relatedGroups: $relatedGroups
  // })

  // Hide siblings
  $siblingPanels.collapse('hide')

  // Reset other groups to show the first panel and hide any others
  $relatedGroups.not($targetGroup).each(function (i, elem) {
    var $elemPanels = $(elem).find('> [role="tabpanel"]')
    $elemPanels.first().trigger('show.bs.collapse', {
      ignoreToggleGroup: true
    })
  })
})
