/*
 * Swipers controller
 */

var $ = require('jquery')
var Swiper = require('Swiper')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

var $doc = $(document)

$doc.ready(function () {
  // Initialise swipers
  $('.swiper-container').each(function (i, elem) {
    var $elem = $(elem)
    var swiperOptions = $.extend({
      direction: 'horizontal',
      loop: 'true',
      effect: 'fade',
      speed: 250,
      autoplay: 5000,
      // Lazy loading
      lazyLoading: true,
      preloadImages: false,
      // ARIA keyboard functionality
      a11y: true
    }, ElementAttrsObject(elem, {
      direction: 'data-swiper-direction',
      loop: 'data-swiper-loop',
      effect: 'data-swiper-effect',
      speed: 'data-swiper-speed',
      autoplay: 'data-swiper-autoplay',
      a11y: 'data-swiper-aria'
    }))

    // Extra breakpoint options if promo-swiper
    if ($elem.is('.promo-swiper')) {
      swiperOptions.loop = false
      swiperOptions.autoplay = false
      swiperOptions.effect = 'slide'
      swiperOptions.slidesPerView = 3
      swiperOptions.breakpoints = {
        800: {
          slidesPerView: 1
        }
      }

      // Insert a left/right arrow to prompt user there are more slides
      if ($elem.find('.swiper-nav-custom').length === 0) {
        $elem.append('<div class="swiper-nav-custom">\
          <a href="javascript:;" class="btn-nav-left"></a>\
          <a href="javascript:;" class="btn-nav-right"></a>\
        </div>')
      }
    }

    // Extra breakpoint options if promo-swiper
    if ($elem.is('.promo-battenberg-lender, .promo-battenberg-borrower')) {
      swiperOptions.noSwiping = true
      swiperOptions.onlyExternal = true
      swiperOptions.loop = true
      swiperOptions.autoplay = 7000
      swiperOptions.effect = 'fade'
    }

    // Fade / Crossfade in Swiper compatible format
    if (swiperOptions.effect === 'fade') {
      swiperOptions.fade = {
        crossFade: $elem.attr('data-swiper-crossfade') === 'true'
      }
    }

    // Dynamically test if has pagination
    if ($elem.find('.swiper-custom-pagination').length > 0 && $elem.find('.swiper-custom-pagination > *').length > 0) {
      swiperOptions.paginationType = 'custom'
    }

    // Events
    swiperOptions.runCallbacksOnInit = true

    // -- slideChangeEnd
    swiperOptions.onSlideChangeEnd = function (swiper) {
      // Instigate or trigger any items within the slide now it's in view
      $(swiper.slides[swiper.activeIndex]).trigger('UI:visible')

      // Update the window (watchWindow needs to be notified of any elements now being visible)
      Utility.debounceUpdateWindow()

      // @debug
      // console.log('swiper-slide in view', swiper.activeIndex)
    }

    // The element's swiper instance
    var elemSwiper = new Swiper(elem, $.extend({}, swiperOptions))

    // @debug
    // console.log({
    //   elem: elem,
    //   swiper: elemSwiper,
    //   swiperOptions: swiperOptions,
    //   isPromoSwiper: $elem.is('.promo-swiper'),
    //   isBattenberg: $elem.is('.promo-battenberg')
    // })

    // Add event to hook up custom pagination to appropriate slide
    if (swiperOptions.paginationType === 'custom') {
      // Hook into sliderMove event to update custom pagination
      elemSwiper.on('slideChangeStart', function () {
        // Unactive any active pagination items
        $elem.find('.swiper-custom-pagination li.active').removeClass('active')

        // Activate the current pagination item
        $elem.find('.swiper-custom-pagination li:eq(' + elemSwiper.activeIndex + ')').addClass('active')

        // console.log('sliderMove', elemSwiper.activeIndex)
      })

      // Connect user interaction with custom pagination
      $elem.find('.swiper-custom-pagination li').on('click', function (event) {
        var $elem = $(this).parents('.swiper-container')
        var $target = $(this)
        var swiper = $elem[0].swiper
        var newSlideIndex = $elem.find('.swiper-custom-pagination li').index($target)

        event.preventDefault()
        swiper.pauseAutoplay()
        swiper.slideTo(newSlideIndex)
      })
    }

    // Specific swipers
    // -- Homepage Acquisition Video Hero
    if ($elem.is('#homeacq-video-hero-swiper')) {
      elemSwiper.on('slideChangeStart', function () {
        var emprunterName = $elem.find('.swiper-slide:eq(' + elemSwiper.activeIndex + ')').attr('data-emprunter-name')
        var preterName = $elem.find('.swiper-slide:eq(' + elemSwiper.activeIndex + ')').attr('data-preter-name')
        if (emprunterName) $elem.parents('.cta-video-hero').find('.ui-emprunter-name').text(emprunterName)
        if (preterName) $elem.parents('.cta-video-hero').find('.ui-preter-name').text(preterName)
      })
    }
  })

  // Navigate to previous slide
  $doc.on(Utility.clickEvent, '.swiper-nav-custom a.btn-nav-left, .swiper-nav-custom a.btn-nav-back', function (event) {
    event.preventDefault()
    $(this).parents('.swiper-container')[0].swiper.slidePrev()
  })

  // Navigate to next slide
  $doc.on(Utility.clickEvent, '.swiper-nav-custom a.btn-nav-right, .swiper-nav-custom a.btn-nav-next', function (event) {
    event.preventDefault()
    $(this).parents('.swiper-container')[0].swiper.slideNext()
  })
})
