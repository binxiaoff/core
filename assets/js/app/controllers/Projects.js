/*
 * Projects controller
 */

var $ = require('jquery')

var $doc = $(document)
var $html = $('html')

$doc.on('ready', function () {
  // Clicking on a project list item should take a user to the single project details page
  $doc.on('click', '.project-list-item', function (event) {
    var $target = $(event.target)
    var href = $target.closest('.project-list-item').find('.project-list-item-title a').first().attr('href')

    // Not an anchor link? Let's go...
    if ($target.closest('a, [data-toggle="tooltip"]').length === 0) {
      event.preventDefault()

      // Go to the project page
      window.location = href
    }
  })

  /*
   * Sticky Project Single Menu
   */
  // @todo probably needs a lot of refactoring. Trickiest thing is all the responsive stuff

  // Offset sticky by marginTop
  var doStickyOffset = function ($elem, amount) {
    if (amount !== false) {
      $elem.css('marginTop', amount + 'px')
    } else {
      $elem.css('marginTop', '')
    }
  }

  // Offset sticky by CSS transform
  if ($html.is('.has-csstransforms')) {
    doStickyOffset = function ($elem, amount) {
      if (amount !== false) {
        $elem.css('transform', 'translateY(' + amount + 'px)')
      } else {
        $elem.css('transform', '')
      }
    }
  }
})
