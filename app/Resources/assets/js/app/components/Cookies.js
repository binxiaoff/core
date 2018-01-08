// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var $doc = $(document)

var COOKIE_BAR = {
    itemName: 'unilend.cookie-law-accepted',
    itemValue: 'accepted',

    showCookieBar: function () {
        this.cookieBarDiv.show()
    },

    hideCookieBar: function () {
        this.cookieBarDiv.hide()
    },

    shouldShowCookieBar: function () {
        return window.localStorage.getItem(this.itemName) !== this.itemValue
    },

    processCookieBar: function () {
        if (this.shouldShowCookieBar()) {
            this.showCookieBar()
        }
    },

    processCookieAccept: function () {
        var _this = this
        $.ajax({
            type: 'POST',
            url: '/accept-cookies',
            global: false,
            success: function (response) {
                window.localStorage.setItem(_this.itemName, _this.itemValue)
                _this.hideCookieBar()
            }
        })
    },

    init: function () {
        var _this = this

        this.cookieBarDiv = $('[data-cookies-law]')
        this.cookieAcceptButton = $('[data-cookies-law-accept]')

        this.processCookieBar()
        $doc.on(Utility.clickEvent, '[data-cookies-law-accept]', function () {
            _this.processCookieAccept()
        })
    }
}

$doc.on('ready', function () {
    COOKIE_BAR.init()
})

