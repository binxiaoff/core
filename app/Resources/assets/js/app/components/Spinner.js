/*
 * Spinner
 * Display a spinner to show when AJAX is working
 * Assign a
 */
var $ = require('jquery')
var Utility = require('Utility')

$doc = $(document)
$body = $("body")

$doc
  // Some extra CSS classes to mark that the transition has ended
  .on(Utility.transitionEndEvent, '.ui-is-loading-end', function (event) {
    var $target = $(event.target)

    // console.log('Spinner loading end: complete')
    $target.removeClass('ui-is-loading-end')
  })

  // Show the spinner manually
  .on('Spinner:showLoading', '[data-has-spinner], .ui-has-spinner', function () {
    var $elem = $(this)
    var $spinnerTarget

    // Set a spinner target that is not the element
    if ($elem.attr('data-has-spinner') && Utility.elemExists($elem.attr('data-has-spinner'))) {
      $spinnerTarget = $($elem.attr('data-has-spinner'))

    // Default to the element itself
    } else {
      $spinnerTarget = $elem
    }

    // @debug
    // console.log('Spinner:showLoading', $elem[0], $spinnerTarget[0])

    if ($spinnerTarget) {
      $spinnerTarget.removeClass('ui-is-loading-end').addClass('ui-is-loading')
    }
  })

  // Hide the spinner manually
  .on('Spinner:hideLoading', '[data-has-spinner], .ui-has-spinner', function () {
    var $elem = $(this)
    var $spinnerTarget

    // Set a spinner target that is not the element
    if ($elem.attr('data-has-spinner') && Utility.elemExists($elem.attr('data-has-spinner'))) {
      $spinnerTarget = $($elem.attr('data-has-spinner'))

    // Default to the element itself
    } else {
      $spinnerTarget = $elem
    }

    // @debug
    // console.log('Spinner:hideLoading', $elem[0], $spinnerTarget[0])

    if ($spinnerTarget) {
      $spinnerTarget.removeClass('ui-is-loading').addClass('ui-is-loading-end')
    }
  })

  // Show the spinner when global AJAX event has started
  .on('ajaxStart', function (event) {
    var $activeElement = $(event.target.activeElement)
    var $spinnerTarget = $body // Default is body element
    var posX = 50
    var posY = 50

    // Get the specific spinner element
    if ($activeElement.attr('data-has-spinner') && Utility.elemExists($activeElement.attr('data-has-spinner'))) {
      $spinnerTarget = $($activeElement.attr('data-has-spinner'))
    }

    // Show spinner
    $spinnerTarget.addClass('ui-is-loading')

    // @debug
    // console.log('spinner ajaxStart', event, event.target.activeElement)
    // console.log('spinnerTarget', $spinnerTarget)

    // If spinner is fired on single project page
    if ($spinnerTarget.find('#alloffers-table').length) {
      var pixelFromLeftSide = $('#alloffers-table').width() / 2
      pixelFromLeftSide += $('#alloffers-table').offset().left
      posX = (pixelFromLeftSide / window.innerWidth) * 100
    }

    // Position the main body spinner
    if ($spinnerTarget.is('body') || $spinnerTarget.is('.modal-spinner')) {
      $('#floatingCirclesG').css({
        top: posY + '%',
        left: posX + '%'
      })
    }
  })

  // Hide the spinner when global AJAX event has stopped
  .on('ajaxStop', function (event) {
    var $activeElement = $(event.target.activeElement)
    var $spinnerTarget = $body // Default is body element

    // Get the specific spinner element
    if ($activeElement.attr('data-has-spinner') && Utility.elemExists($activeElement.attr('data-has-spinner'))) {
      $spinnerTarget = $($activeElement.attr('data-has-spinner'))
    }

    // @debug
    // console.log('spinner ajaxStop', event, event.target.activeElement)
    // console.log('spinnerTarget', $spinnerTarget)

    // Hide spinner
    $spinnerTarget.removeClass('ui-is-loading')
  })

