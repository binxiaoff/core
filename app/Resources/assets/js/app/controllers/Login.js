/*
 * Login page controller
 */

var $ = require('jquery')
var Utility = require('Utility')
var TimeCount = require('TimeCount')

var $doc = $(document)

// Elements
var $captcha = $('[data-captcha]')
var $captchaTimerComplete = $('[data-captcha-timercomplete]')
var $countdown = $('[data-countdown]')

// Wait until ready to do the following...
$doc.on('ready', function () {
  // Do captcha stuff
  if ($captcha.length > 0 && $countdown.length > 0) {
    // Setup TimeCount instance
    var duration = parseInt($countdown.attr('data-duration'), 10)
    var endDateTime = (new Date()).getTime() + ((duration + 1) * 1000)
    var endDate = new Date(endDateTime)
    endDate.setTime(endDateTime)

    var loginCountdown = new TimeCount($countdown, {
      endDate: endDate,
      relative: true,
      onupdate: function (timeDiff) {
        this.$elem.text((timeDiff.minutes * 60) + timeDiff.seconds)
      },
      oncomplete: function () {
        // Once the timer is complete, do these things to the related elements
        $captchaTimerComplete.filter('[data-captcha-timercomplete*="show"]').show()
        $captchaTimerComplete.filter('[data-captcha-timercomplete*="hide"]').hide()
        $captchaTimerComplete.filter('[data-captcha-timercomplete*="disable"]').prop('disabled', true).attr('disabled', 'disabled')
        $captchaTimerComplete.filter('[data-captcha-timercomplete*="enable"]').removeProp('disabled').removeAttr('disabled')
      }
    })
  }
})