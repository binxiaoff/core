// Lib Dependencies
var $ = require('jquery')

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

        this.cookieBarDiv = $('[data-cookies]')
        this.cookieAcceptButton = $('[data-cookies-accept]')

        this.processCookieBar()
        this.cookieAcceptButton.click(function () {
            _this.processCookieAccept()
        })
    }
}

document.addEventListener("DOMContentLoaded", function () {
    COOKIE_BAR.init()
})

