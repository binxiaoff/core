/*
 * User Notifications Drop
 * Manages updating any HTML component which is tied to the UserNotifications component
 * 
 * @component `UserNotificationsList`
 * @ui `ui-usernotificationslist`
 * @data `data-usernotificationslist`
 * @lang `USERNOTIFICATIONS_LANG`
 *
 * @todo think about using this one as the base and having UserNotificationsList inherit it as a sub/super class
 */

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')
var UserNotifications = require('UserNotifications')

/*
 * Dictionary
 */
var Dictionary = require('Dictionary')
var USERNOTIFICATIONS_LANG_LEGACY = require('../../../lang/UserNotifications.lang.json')
// -- Support new translation dictionary language format, e.g. `example-translation-key-name`
if (window.USERNOTIFICATIONS_LANG) {
  __ = new Dictionary(window.USERNOTIFICATIONS_LANG)
  // @debug
  // console.log('UserNotificationsList: using window.USERNOTIFICATIONS_LANG for Dictionary')

// -- Support new legacy dictionary language format for fallbacks, e.g. `exampleTranslationKeyName`
} else {
  __ = new Dictionary(USERNOTIFICATIONS_LANG_LEGACY, {
    legacyMode: true
  })
  // @debug
  console.log('UserNotificationsList: using USERNOTIFICATIONS_LANG_LEGACY for Dictionary. Please ensure window.USERNOTIFICATIONS_LANG is correctly set.')
}

/*
 * UserNotificationsList
 * @class
 */
var UserNotificationsList = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error: invalid element
  if (self.$elem.length === 0) {
    console.log('UserNotificationsList Error: Invalid element selected', elem)
    return
  }

  // Don't instantiate another if it already has one
  if (self.$elem[0].hasOwnProperty('UserNotificationsList')) return

  // Settings
  self.settings = Utility.inherit({
    notifications: undefined,
    emptyLabel: __.__('user-notifications_empty-label', 'emptyLabel'),
    markAllReadLabel: __.__('user-notifications_mark-all-read-label', 'markAllReadLabel'),
    showOnlyUnread: false, // default is false = shows all

    // Custom events
    onbeforerender: undefined, // function (notifications) {}
    onrender: undefined, // function (notifications) {}
    onrendernotification: undefined // function (notification) {}
  }, ElementAttrsObject(elem, {
    emptyLabel: 'data-usernotificationslist-emptylabel',
    markAllReadLabel: 'data-usernotificationslist-markallreadlabel',
    showOnlyUnread: 'data-usernotificationslist-showonlyunread'
  }), options)

  // UI
  self.$elem.addClass('ui-usernotificationslist')

  // Initialise yo'self
  self.$elem[0].UserNotificationsList = self

  // Render any notifications on init (references UserNotifications component for notifications to render)
  self.render()

  // @trigger elem `UserNotificationsList:initialised` [elemUserNotificationsList]
  self.$elem.trigger('UserNotificationsList:initialised', [self])

  return self
}

// Render the list of notifications
// @method render
// @returns {Void}
UserNotificationsList.prototype.render = function () {
  var self = this

  // Make a copy of the collection since this component is read-only and we don't want it to mess with the collection
  var notifications = UserNotifications.collection.slice(0)
  var unreadNotifications = 0

  // @trigger `UserNotificationsList:render:before` [elemUserNotificationsList, notifications]
  self.$elem.trigger('UserNotificationsList:render:before', [self, notifications])

  // Custom event `onbeforerender`
  if (self.settings.onbeforerender === 'function') {
    self.settings.onbeforerender.apply(self, [notifications])
  }

  // Customise output depending on settings
  if (self.settings.showOnlyUnread) {
    var onlyUnreadNotifications = []
    for (var i = 0; i < notifications.length; i++) {
      if (notifications[i].status === 'unread') {
        onlyUnreadNotifications.push(notifications[i])
      }
    }
    notifications = onlyUnreadNotifications
    unreadNotifications = notifications.length

    // Count unread
  } else {
    for (var i in notifications) {
      if (notifications[i].status === 'unread') unreadNotifications += 1
    }
  }

  // Render the array of {NotificationObjects}s to HTML
  var notificationsHTML = ''

  // -- Show the empty message
  if (notifications.length === 0) {
    notificationsHTML = Templating.replace(self.templates.listItemEmpty)

    // -- Render the list
  } else {
    // Use custom event `onrender`
    if (typeof self.settings.onrender === 'function') {
      notificationsHTML = self.settings.onrender.apply(self, [notifications])

      // Default rendering
    } else {
      if (notifications instanceof Array && notifications.length > 0) {
        for (var i = 0; i < notifications.length; i++) {
          notificationsHTML += self.renderNotification(notifications[i])
        }
      }
    }
  }

  // @debug
  console.log('UserNotificationsList.render', self.$elem[0])

  // Place notifications into the list
  notificationsHTML = Templating.replace(self.templates.list, {
    listItems: notificationsHTML,
    emptyLabel: self.settings.emptyLabel,
    markAllReadLabel: self.settings.markAllReadLabel
  })

  // Replace the drop's content with the new html
  self.$elem.html(notificationsHTML)
    
  // @trigger elem `UserNotificationsList:render:complete`, [elemUserNotificationsList]
  self.$elem.trigger('UserNotificationsList:render:complete', [self])
}

// Render a single notification from a {NotificationObject}
// @method renderNotification
// @param {NotificationObject} notificationObject
// @returns {String}
UserNotificationsList.prototype.renderNotification = function (notificationObject) {
  var self = this

  // Custom event `onrendernotification`
  if (typeof self.settings.onrendernotification === 'function') {
    return self.settings.onrendernotification.apply(self, [notificationObject])
  }

  // Default rendering notification operation
  return Templating.replace(self.templates.listItem, {
    id: notificationObject.id || Utility.randomString(),
    type: notificationObject.type || 'default',
    status: notificationObject.status || 'unread',
    datetime: (typeof notificationObject.datetime === 'object' ? Utility.getRelativeTime(notificationObject.datetime) : notificationObject.datetime || ''),
    title: notificationObject.title || '',
    image: notificationObject.image || '',
    content: notificationObject.content || ''
  })
}

// Templates
UserNotificationsList.prototype.templates = {
  // The list, holds all the notification items
  list: '<ul class="list-notifications">\
      {{ listItems }}\
    </ul>',

  // The drop's notification list item, which represents a single notification
  listItem: '<li class="notification notification-type-{{ type }} ui-notification-status-{{ status }}" data-notification-id="{{ id }}">\
      <header class="notification-header">\
        <h5 class="notification-datetime">{{ datetime }}</h5>\
        <h4 class="notification-title">{{ title }}</h4>\
        <div class="notification-image"><span class="svg-icon-wrap">{{ image }}</span></div>\
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
$.fn.uiUserNotificationsList = function (op) {
  // Fire a command to the UserNotificationsList object, e.g. $('[data-usernotificationslist]').uiUserNotificationsList('publicMethod', {..})
  if (typeof op === 'string' && /^(render)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('UserNotificationsList') && typeof elem.UserNotificationsList[op] === 'function') {
        elem.UserNotificationsList[op].apply(elem.UserNotificationsList, args)
      }
    })

  // Set up a new UserNotificationsList instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('UserNotificationsList')) {
        new UserNotificationsList(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
  // Auto-init `[data-usernotificationslist]` elements
  .on('ready UI:visible', function () {
    $(event.target).find('[data-usernotificationslist]').not('.ui-usernotificationslist').uiUserNotificationsList()
  })

  // Re-render UI notifications drop elements after global markAllRead complete event
  .on('UserNotifications:updated', function (event) {
    // @debug
    // console.log('After UserNotifications:updated, re-render the lists')
    $('.ui-usernotificationslist').uiUserNotificationsList('render')
  })

module.exports = UserNotificationsList
