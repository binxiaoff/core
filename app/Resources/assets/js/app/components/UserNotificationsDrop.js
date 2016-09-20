/*
 * User Notifications Drop
 * Shows user's notifications drop-down when clicked
 *
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
var UserNotifications = require('UserNotifications')
var Tether = require('tether')
var Drop = require('tether-drop')

/*
 * Dictionary
 */
var Dictionary = require('Dictionary')
__ = new Dictionary(window.USERNOTIFICATIONS_LANG)

// @debug
// console.log('UserNotificationsList: using window.USERNOTIFICATIONS_LANG for Dictionary')

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

  // Don't instantiate another if it already has one
  if (self.$elem[0].hasOwnProperty('UserNotificationsDrop')) return

  // Decouple options.dropOptions to inherit separately
  var optionsDropOptions = {}
  if (options && options.hasOwnProperty('dropOptions')) {
    optionsDropOptions = Utility.inherit({}, options.dropOptions || {})
    delete options.dropOptions
  }

  // Settings
  self.settings = Utility.inherit({
    notifications: [],
    emptyLabel: __.__('Aucune notification', 'empty-label'),
    markAllReadLabel: __.__('Marquer comme lu', 'mark-all-read-label'),
    showOnlyUnread: false, // default is false = shows all

    // The default options for the drop
    // The value of this option matches what Drop JS plugin takes
    dropOptions: Utility.inheritNested({
      target: elem,
      content: '',
      classes: 'ui-usernotificationsdrop-drop-element',
      position: 'bottom center',
      openOn: 'click',

      // Stop the drop from auto-closing when "mark all read" is clicked
      beforeClose: function (event) {
        if ($(event.target).is('[data-usernotifications-markallread]')) {
          return false
        }
      },

      tetherOptions: {
        attachment: 'top right',
        targetAttachment: 'bottom center',
        offset: '-15px -25px'
      }
    }, optionsDropOptions),

    // Custom events
    onbeforerender: undefined, // function (notifications) {}
    onrender: undefined, // function (notifications) {}
    onrendernotification: undefined, // function (notification) {}
  },
  // Get options from the element
  ElementAttrsObject(elem, {
    emptyLabel: 'data-usernotificationsdrop-emptylabel',
    markAllReadLabel: 'data-usernotificationsdrop-markallreadlabel',
    showOnlyUnread: 'data-usernotificationsdrop-showonlyunread'
  }),
  // Override options through JS call
  options)

  /*
   * Properties
   */
  // DROP THA BAAAASSSSSSSS~~~~~~!!!
  self.drop = new Drop(self.settings.dropOptions)

  // UI
  self.$elem.addClass('ui-usernotificationsdrop')

  // Initialise
  self.$elem[0].UserNotificationsDrop = this

  // Render any notifications on init (references UserNotifications component for notifications to render)
  self.render()

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
UserNotificationsDrop.prototype.show = function () {
  var self = this

  // @trigger `UserNotificationsDrop:show:before` [elemUserNotificationsDrop]
  self.$elem.trigger('UserNotificationsDrop:show:before', [self])

  // Ensure notifications are rendered
  self.render()

  // Open the drop UI
  if (!self.drop.isOpened()) self.drop.open()
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
// This will generate new HTML to output into the drop element
// @method render
// @returns {Void}
UserNotificationsDrop.prototype.render = function () {
  var self = this
  // Make a copy since this component is read-only
  var notifications = UserNotifications.collection.slice(0)
  var unreadNotifications = 0

  // @trigger `UserNotificationsDrop:render:before` [elemUserNotificationsDrop, notifications]
  self.$elem.trigger('UserNotificationsDrop:render:before', [self, notifications])

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

  // Update the pip with the amount of new notifications
  self.updatePip(unreadNotifications)

  // @debug
  // console.log('UserNotificationsDrop.render', self.$elem[0])

  // Place notifications into the list
  notificationsHTML = Templating.replace(self.templates.list, {
    listItems: notificationsHTML
  })

  // Replace the drop's content with the new html
  self.drop.content.innerHTML = Templating.replace(self.templates.frame, {
    content: notificationsHTML,
    emptyLabel: self.settings.emptyLabel,
    markAllReadLabel: self.settings.markAllReadLabel
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
    id: notificationObject.id || Utility.randomString(),
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
  var pipHTML = ''

  // Generate the new pip HTML if there are any unread notifications specified by amount
  if (amount > 0) {
    pipHTML = Templating.replace(self.templates.pip, {
      amount: amount,
      classNames: amount > 9 ? 'pip-has-many' : ''
    })
  }

  // Set the elem's HTML
  self.$elem.html(pipHTML)
}

// Update the Drop's position. Do this if the contents ever change, or page view repaints somehow
// @method position
// @returns {Void}
UserNotificationsDrop.prototype.position = function () {
  var self = this
  if (self.drop && self.drop.isOpened()) self.drop.position()
}

// Templates for rendering
UserNotificationsDrop.prototype.templates = {
  // The pip to show how many unread notifications there are
  pip: '<span class="pip {{ classNames }}"><span class="pip-number">{{ amount }}</span></span>',

  // The drop's frame, holds all the content for the drop
  frame: '<div class="notifications-drop">\
      <div class="notifications-drop-controls">\
        <a href="javascript:;" class="ui-usernotifications-markallread" data-usernotifications-markallread>{{ markAllReadLabel }}</a>\
      </div>\
      {{ content }}\
    </div>',

  // The list, holds all the notification items
  list: '<ul class="notifications-drop-list list-notifications">\
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
$.fn.uiUserNotificationsDrop = function (op) {
  // Fire a command to the UserNotificationsDrop object, e.g. $('[data-usernotificationsdrop]').uiUserNotificationsDrop('publicMethod', {..})
  // @todo add in list of public methods that $.fn.uiUserNotificationsDrop can reference
  if (typeof op === 'string' && /^(show|hide|render|position|updatePip)$/.test(op)) {
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

  // Reposition any drops on UI:update
  .on('UI:update', function () {
    $('.ui-usernotificationsdrop').uiUserNotificationsDrop('position')
  })

  // Prevent notifications drop showing when in unsupported breakpoints
  .on(Utility.clickEvent, '.site-user a.profile-notifications[href]', function () {
    // Show/hide of drop is handled with "openOn: click" above
    // This behaviour below is if user on mobile/tablet
    if (/(xs|mobile)/.test(Utility.getActiveBreakpoints())) {
      event.preventDefault()
      event.stopPropagation()
      window.location = $(this).attr('href')
      return false
    }
  })

  // Update the unread notifications count on the global event
  .on('UserNotifications:updateUnreadCount:complete', function (event, unreadCount) {
    $('.ui-usernotificationsdrop').uiUserNotificationsDrop('updatePip', unreadCount)
  })

  // Re-render UI notifications drop elements after global markAllRead complete event
  .on('UserNotifications:updated', function (event) {
    // @debug
    // console.log('After UserNotifications:updated, re-render the drops')
    $('.ui-usernotificationsdrop').uiUserNotificationsDrop('render')
  })

module.exports = UserNotificationsDrop
