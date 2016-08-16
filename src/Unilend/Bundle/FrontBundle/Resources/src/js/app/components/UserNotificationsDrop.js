/*
 * User Notifications Drop
 * Shows user's notifications drop-down when clicked
 * @component `UserNotificationsDrop`
 * @ui `ui-usernotificationsdrop`
 * @data `data-usernotificationsdrop`
 * @lang `USERNOTIFICATIONSDROP_LANG`
 */

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')
var Tether = require('tether')
var Drop = require('tether-drop')

/*
 * Dictionary
 */
var Dictionary = require('Dictionary')
var USERNOTIFICATIONSDROP_LANG_LEGACY = {
  "fr": {
    "emptyLabel": "Aucun des notifications nouveaux"
  },
  "en": {
    "emptyLabel": "No unread notifications"
  }
}
// -- Support new translation dictionary language format, e.g. `example-translation-key-name`
if (window.USERNOTIFICATIONSDROP_LANG) {
  __ = new Dictionary(window.USERNOTIFICATIONSDROP_LANG)
  // @debug
  // console.log('UserNotificationsDrop: using window.USERNOTIFICATIONSDROP_LANG for Dictionary')

// -- Support new legacy dictionary language format for fallbacks, e.g. `exampleTranslationKeyName`
} else {
  __ = new Dictionary(USERNOTIFICATIONSDROP_LANG_LEGACY, {
    legacyMode: true
  })
  // @debug
  console.log('UserNotificationsDrop: using USERNOTIFICATIONSDROP_LANG_LEGACY for Dictionary. Please ensure window.USERNOTIFICATIONSDROP_LANG is correctly set.')
}

/*
 * UserNotificationsDrop
 * @class
 */
var UserNotificationsDrop = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error: invalid element
  if (self.$elem.length === 0) {
    console.log('UserNotificationsDrop Error: Invalid element selected', elem)
    return
  }

  // Decouple options.dropOptions to inherit separately
  var optionsDropOptions = {}
  if (options && options.hasOwnProperty('dropOptions')) {
    optionsDropOptions = Utility.inherit({}, options.dropOptions || {})
    delete options.dropOptions
  }

  // Settings
  self.settings = Utility.inherit({
    notifications: window.USERNOTIFICATIONS || [],
    emptyLabel: 'No new activity',

    // The default options for the drop
    // The value of this option matches what Drop JS plugin takes
    dropOptions: Utility.inheritNested({
      target: elem,
      content: '',
      classes: 'ui-usernotificationsdrop-drop-element',
      position: 'bottom center',
      openOn: 'click',
      tetherOptions: {
        attachment: 'top right',
        targetAttachment: 'bottom center',
        offset: '-15px -25px'
      }
    }, optionsDropOptions),

    // Custom events
    onbeforerender: undefined, // function (notifications, pushOrReplace) {}
    onrender: undefined, // function (notifications, pushOrReplace) {}
    onrendernotification: undefined, // function (notification) {}
  },
  // Get options from the element
  ElementAttrsObject(elem, {
    emptyLabel: 'data-usernotificationsdrop-emptylabel'
  }),
  // Override options through JS call
  options)

  // Properties
  // A collection of all the notifications being shown in this component
  self.notifications = []

  // DROP THA BAAAASSSSSSSS~~~~~~!!!
  self.drop = new Drop(self.settings.dropOptions)

  // UI
  self.$elem.addClass('ui-usernotificationsdrop')

  // Initialise
  self.$elem[0].UserNotificationsDrop = this

  // Render any notifications
  self.render(self.settings.notifications, false)

  // @trigger elem `UserNotificationsDrop:initialised` [elemUserNotificationsDrop]
  self.$elem.trigger('UserNotificationsDrop:initialised', [self])

  // @debug
  // console.log('new UserNotificationsDrop', self)

  return self
}

/*
 * Prototype methods and properties shared between all instances
 */

// Show the user notifications
// @method show
// @param {Array} notifications An array of {NotificationObject}s
// @param {Boolean} pushOrReplace Whether to push or to replace the notifications to the list
// @returns {Void}
UserNotificationsDrop.prototype.show = function (notifications, pushOrReplace) {
  var self = this

  // @trigger `UserNotificationsDrop:show:before` [elemUserNotificationsDrop, notifications, pushOrReplace]
  self.$elem.trigger('UserNotificationsDrop:show:before', [self, notifications, pushOrReplace])

  self.render(notifications, pushOrReplace)

  // Open the drop UI
  self.drop.open()
}

// Hide the user notifications
// @method hide
// @returns {Void}
UserNotificationsDrop.prototype.hide = function () {
  var self = this

  // @trigger `UserNotificationsDrop:hide:before` [elemUserNotificationsDrop]
  self.$elem.trigger('UserNotificationsDrop:hide:before', [self])

  // Close the drop UI
  self.drop.close()
}

// Render notifications
// @method render
// @param {Array} notifications An array of {NotificationObject}s
// @param {Boolean} pushOrReplace Whether to push or to replace the notifications to the list
// @returns {Void}
UserNotificationsDrop.prototype.render = function (notifications, pushOrReplace) {
  var self = this
  var newNotifications = notifications.length || 0

  // @trigger `UserNotificationsDrop:render:before` [elemUserNotificationsDrop, notifications, pushOrReplace]
  self.$elem.trigger('UserNotificationsDrop:render:before', [self, notifications, pushOrReplace])

  // Custom event `onbeforerender`
  if (self.settings.onbeforerender === 'function') {
    self.settings.onbeforerender.apply(self, [notifications, pushOrReplace])
  }

  // Create the new list of notifications
  if (notifications instanceof Array) {
    // Only show unread notifications
    if (self.settings.showOnlyUnread) {
      var onlyUnreadNotifications = []
      for (var i in notifications) {
        if (notifications[i].status === 'unread') {
          onlyUnreadNotifications.push(notifications[i])
        }
      }

      // Update the number of notifications
      newNotifications = onlyUnreadNotifications.length

      // Point it back for further process
      notifications = onlyUnreadNotifications
    }

    // -- Push
    if (pushOrReplace && notifications.length > 0) {
      self.notifications = notifications.concat(self.notifications)

    // -- Replace
    } else {
      self.notifications = notifications
    }
  }

  // Render the array of {NotificationObjects}s to HTML
  var notificationsHTML = ''

  // Use custom event `onrender`
  if (typeof self.settings.onrender === 'function') {
    notificationsHTML = self.settings.onrender.apply(self, [notifications, pushOrReplace])

  // Default rendering
  } else {
    if (self.notifications instanceof Array && self.notifications.length > 0) {
      for (var i in self.notifications) {
        notificationsHTML += self.renderNotification(self.notifications[i])
      }
    }
  }

  // Show the empty message, only if pushOrReplace == false
  if (!notificationsHTML && !pushOrReplace) {
    notificationsHTML = Templating.replace(self.templates.listItemEmpty, {
      emptyLabel: __.__(self.settings.emptyLabel, 'emptyLabel')
    })

    // Remove the pip!
    self.$elem.html('')
  }

  // Update the pip if there are notifications!
  if (newNotifications > 0) {
    self.updatePip(newNotifications)
  }

  // Don't bother rendering if nothing to render and in push mode
  if (!notificationsHTML && pushOrReplace) return

  // Place notifications into the list
  notificationsHTML = Templating.replace(self.templates.list, {
    listItems: notificationsHTML
  })

  // Replace the drop's content with the new html
  self.drop.content.innerHTML = Templating.replace(self.templates.frame, {
    content: notificationsHTML
  })

  // Refresh the drop if contents have changed
  self.position()
}

// Render a single notification from a {NotificationObject}
// @method renderNotification
// @param {NotificationObject} notificationObject
// @returns {String}
UserNotificationsDrop.prototype.renderNotification = function (notificationObject) {
  var self = this

  // Custom event `onrendernotification`
  if (typeof self.settings.onrendernotification === 'function') {
    return self.settings.onrendernotification.apply(self, [notificationObject])
  }

  // Default rendering notification operation
  return Templating.replace(self.templates.listItem, {
    type: notificationObject.type || 'default',
    status: notificationObject.status || 'unread',
    datetime: (typeof notificationObject.datetime === 'object' ? Utility.getRelativeTime(notificationObject.datetime) : notificationObject.datetime || ''),
    title: notificationObject.title || '',
    image: notificationObject.image || '',
    content: notificationObject.content || ''
  })
}

// Update the pip number
// @method updatePip
// @param {Int} amount
// @returns {Void}
UserNotificationsDrop.prototype.updatePip = function (amount) {
  var self = this

  // Generate the new pip HTML
  var pipHTML = Templating.replace(self.templates.pip, {
    amount: amount,
    classNames: amount > 9 ? 'pip-has-many' : ''
  })

  // Set the elem's HTML
  self.$elem.html(pipHTML)
}

// Update the Drop's position. Do this if the contents ever change, or page view repaints somehow
// @method position
// @returns {Void}
UserNotificationsDrop.prototype.position = function () {
  if (self.drop && self.drop.isOpened()) self.drop.position()
}

// Templates for rendering
UserNotificationsDrop.prototype.templates = {
  // The pip to show how many unread notifications there are
  pip: '<span class="pip {{ classNames }}"><span class="pip-number">{{ amount }}</span></span>',

  // The drop's frame, holds all the content for the drop
  frame: '<div class="user-notifications-drop">\
      {{ content }}\
    </div>',

  // The list, holds all the notification items
  list: '<ul class="user-notifications-drop-list list-notifications">\
      {{ listItems }}\
    </ul>',

  // The drop's notification list item, which represents a single notification
  listItem: '<li class="notification notification-type-{{ type }} ui-notification-status-{{ status }}">\
      <header class="notification-header">\
        <h5 class="notification-datetime">{{ datetime }}</h5>\
        <h4 class="notification-title">{{ title }}</h4>\
        <div class="notification-image">{{ image }}</div>\
      </header>\
      <div class="notification-content">\
        <p>{{ content }}</p>\
      </div>\
    </li>',

  // A variation on the list item which shows when the notifications list is empty
  listItemEmpty: '<li class="notification notification-type-empty">\
      <div class="notification-content">\
        <p>{{ emptyLabel }}</p>\
      </div>\
    </li>'
}

/*
 * jQuery Plugin
 */
$.fn.uiUserNotificationsDrop = function (op) {
  // Fire a command to the UserNotificationsDrop object, e.g. $('[data-usernotificationsdrop]').uiUserNotificationsDrop('publicMethod', {..})
  // @todo add in list of public methods that $.fn.uiUserNotificationsDrop can reference
  if (typeof op === 'string' && /^(show|hide|render|position)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('UserNotificationsDrop') && typeof elem.UserNotificationsDrop[op] === 'function') {
        elem.UserNotificationsDrop[op].apply(elem.UserNotificationsDrop, args)
      }
    })

    // Set up a new UserNotificationsDrop instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('UserNotificationsDrop')) {
        new UserNotificationsDrop(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
  // Auto-init `[data-usernotificationsdrop]` elements
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-usernotificationsdrop]').not('.ui-usernotificationsdrop').uiUserNotificationsDrop()
  })

  // Prevent notifications drop showing when in unsupported breakpoints
  .on(Utility.clickEvent, '.site-user a.profile-notifications[href]', function (event) {
    // Show/hide of drop is handled with "openOn: click" above
    // This behaviour below is if user on mobile/tablet
    if (/(xs|mobile)/.test(Utility.getActiveBreakpoints())) {
      event.preventDefault()
      event.stopPropagation()
      window.location = $(this).attr('href')
      return false
    }
  })

module.exports = UserNotificationsDrop
