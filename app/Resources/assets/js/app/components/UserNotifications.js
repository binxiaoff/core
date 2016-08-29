/*
 * User Notifications component
 * Manages the global notifications collection for updating UI user notifications components
 *
 * @model {NotificationObject}
 * {
 *    'id':       {String}; default: Utility.randomString(),
 *    'type':     {String}; default: 'default',
 *    'status':   {String}; accepted: 'read', 'unread'; default: 'unread',
 *    'datetime': {Mixed}; accepted: {String}, {Date}; default: '',
 *    'title':    {String}; default: '',
 *    'image':    {String}; default: '',
 *    'content':  {String}; default: ''
 * }
 *
 * @todo Add in any needed AJAX requests here to record when a notification (or all) have been read/cleared
 */

/*
 @testing
  Copy and paste into console, pressing <return> after each block.
  Any related elements (UserNotificationsList and UserNotificationsDrop) should update in response to these tests.

```
// ### Block 1 ###
var notificationObjects = [{
  id:       'test_1',
  type:     'default',
  status:   'unread',
  datetime: new Date(),
  title:    'I am test_1',
  image:    '',
  content:  'If you can see me second and marked as read, all tests were successful'
},{
 id:       'test_2',
 type:     'default',
 status:   'read',
 datetime: new Date(),
 title:    'I am test_2',
 image:    '',
 content:  'Unsuccessful test result'
},{
 id:       'test_3',
 type:     'default',
 status:   'unread',
 datetime: new Date(),
 title:    'I am test_3',
 image:    '',
 content:  'If you can see me last and I am marked read, all tests were successful'
}]
// This sets up a temporary notifications collections var that we can refer to in the following tests

// ### Block 2 ###
// Test replace
$(document).trigger('UserNotifications:replace', [notificationObjects.slice(1)])
// Takes only the second and third test items and replaces the global notifications with it
// Unread count should say 1

// ### Block 3 ###
// Test pushing
$(document).trigger('UserNotifications:push', [notificationObjects.slice(0, 1)])
// Pushes the first test item to the global notifications collection
// Unread count should now say 2

// ### Block 4 ###
// Test patch
var patchNotification = notificationObjects.slice(1, 2)[0]
patchNotification.status = 'unread'
patchNotification.content = 'My content was successfully patched! I should be marked read and first on the list, if all tests were successful'
patchNotification.datetime.setMinutes(patchNotification.datetime.getMinutes() + 5)
$(document).trigger('UserNotifications:patch', [[patchNotification]])
// Clones a copy of the second test item, modifies its contents (setting it unread too and changing its date)
// and then patches it back to the main collection
// Unread count should now say 3

// ### Block 5 ###
// Test markRead
$(document).trigger('UserNotifications:markRead', ['test_3'])
// Should mark the test item with id = 'test_3' as 'read'
// Unread count should now say 2

// ### Block 6 ###
// Test markAllRead
$(document).trigger('UserNotifications:markAllRead')
// All notifications should be marked as 'read'

```
 */

var $ = require('jquery')
var Utility = require('Utility')
var $doc = $(document)

// UserNotifications
// Expose to the window so other components can reference it
var UserNotifications = window.UserNotifications = {
  // All the current user notifications being stored
  // @note this is referencing the variable defined near the bottom of the _layout.html.twig template
  collection: window.USERNOTIFICATIONS || [],

  // The number of unread notifications
  unreadCount: 0,

  // A timer to delay pushing the updated event
  delayTimer: 0,

  // A timer to delay AJAX requests
  ajaxTimer: 0,

  // Get a flatmapped version of the USERNOTIFICATIONS collection
  // Each notification within the flatmap will be accessed via a `__x` key where x == notificationObject.id
  // @method flatmap
  // @returns {Object}
  flatmap: function () {
    var notificationsFlatmap = {}
    for (var i = 0; i < UserNotifications.collection.length; i++) {
      if (UserNotifications.collection[i].hasOwnProperty('id')) {
        notificationsFlatmap['__' + UserNotifications.collection[i].id] = UserNotifications.collection[i]
      }
    }

    // @debug
    // console.log('UserNotifications flatmap', notificationsFlatmap)

    return notificationsFlatmap
  },

  // Sort the collection
  // @method sort
  // @returns {Void}
  sort: function () {
    // No need to sort an empty collection
    if (UserNotifications.collection.length === 0) return

    // @debug
    // console.log('UserNotifications.sort')

    // Sort via which has a newer date
    function testDateTime (a, b) {
      if (a.hasOwnProperty('datetime') && b.hasOwnProperty('datetime')) {
        if (a.datetime > b.datetime) {
          return -1
        } else {
          return 1
        }
      }

      // No change
      return 0
    }

    // Sort via which has a larger ID (if going off numeric IDs this makes sense)
    function testId (a, b) {
      if (a.hasOwnProperty('id') && b.hasOwnProperty('id')) {
        if (a.id > b.id) {
          return -1
        } else {
          return 1
        }
      }

      // No change
      return 0
    }

    // Sort collection of {NotificationObject}s by datetime property first, then id property
    UserNotifications.collection.sort(function (a, b) {
      return testDateTime(a, b) || testId(a, b)
    })

    // @trigger `UserNotifications:sort:complete`
    $doc.trigger('UserNotifications:sort:complete')

    UserNotifications.delayUpdated()
  },

  // Push new notifications to the collection
  // @method markAllRead
  // @returns {Void}
  push: function (notifications, options) {

    // @debug
    // console.log('UserNotifications.push', notifications, options)

    // Only operate on legal arrays
    if (!(notifications instanceof Array)) {
      console.warn('UserNotifications.push: param notifications given was not array')
      return
    }

    // Create the new/updated list of notifications
    // -- Only push unread notifications to collection
    if (options && options.showOnlyUnread) {
      var onlyUnreadNotifications = []
      for (var i in notifications) {
        if (notifications[i].status === 'unread') {
          onlyUnreadNotifications.push(notifications[i])
        }
      }

      // Point it back for further process
      notifications = onlyUnreadNotifications
    }

    // Update the collection
    if (notifications.length > 0) {
      UserNotifications.collection = notifications.concat(UserNotifications.collection)
    }

    // @trigger document `UserNotifications:push:complete`
    $doc.trigger('UserNotifications:push:complete')

    UserNotifications.delayUpdated()
  },

  // Replace old notifications collection with new notifications
  // @method replaceNotifications
  // @param {Array} notifications
  // @param {Object} options
  // @returns {Void}
  replace: function (notifications, options) {

    // @debug
    // console.log('UserNotifications.replace', notifications, options)

    // Only operate on legal arrays
    if (!(notifications instanceof Array)) {
      console.warn('UserNotifications.replace: param notifications given was not array')
      return
    }

    // Create the new/updated list of notifications
    // -- Only show unread notifications in new collection
    if (options && options.showOnlyUnread) {
      var onlyUnreadNotifications = []
      for (var i = 0; i < notifications.length; i++) {
        if (notifications[i].hasOwnProperty('status') && notifications[i].status === 'unread') {
          onlyUnreadNotifications.push(notifications[i])
        }
      }

      // Replace the old collection with the new
      UserNotifications.collection = onlyUnreadNotifications

      // Update the unread count
      UserNotifications.updateUnreadCount(onlyUnreadNotifications.length)

    // -- Replace collection with all given notifications
    } else {
      UserNotifications.collection = notifications

      // Update the unread count
      UserNotifications.updateUnreadCount()
    }

    // Sort the array to show newest notifications first
    UserNotifications.sort()

    // @trigger document `UserNotifications:replace:complete`
    $doc.trigger('UserNotifications:replace:complete')

    UserNotifications.delayUpdated()
  },

  // Patch notifications with updated ones
  // Only performs patch on {NotificationObject}s which share the same ID
  // @method patch
  // @param {Array} notifications
  // @returns {Void}
  patch: function (notifications, options) {
    // Get collection as a flatmap with keys represented as `__id`
    var flatmap = UserNotifications.flatmap()

    // @debug
    // console.log('UserNotifications.patch', notifications, options)

    // Only operate on legal arrays
    if (!(notifications instanceof Array)) {
      console.warn('UserNotifications.patch: param notifications given was not array')
      return
    }

    // Iterate over the notifications to update and update via the flatmap
    for (var i = 0; i < notifications.length; i++) {
      // Patch notification in the collection, only if it shares an ID with an existing one
      if (notifications.hasOwnProperty(i) &&
          notifications[i].hasOwnProperty('id') &&
          flatmap.hasOwnProperty('__' + notifications[i]))
      {
        var existingVersion = flatmap['__' + notifications[i].id]
        var newVersion = notifications[i]

        // Use jQuery.extend to assign all patched notification's attributes to the one in the collection
        $.extend(existingVersion, newVersion)
      }
    }

    // After patching, sort the array in case dates change
    UserNotifications.sort()

    // Update unreadCount
    UserNotifications.updateUnreadCount()

    // @trigger document `UserNotifications:update:complete`
    $doc.trigger('UserNotifications:patch:complete')

    UserNotifications.delayUpdated()
  },

  // Clear all notifications from the collection
  // @method clearAll
  // @returns {Void}
  clearAll: function () {
    // @debug
    // console.log('UserNotifications.clearAll')

    UserNotifications.collection = []

    // Update unreadCount
    UserNotifications.updateUnreadCount(0)

    // @trigger document `UserNotifications:clearAll:complete`
    $doc.trigger('UserNotifications:clearAll:complete')

    UserNotifications.delayUpdated()
  },

  // Mark a single notification read
  // @method markRead
  // @param {String} id
  // @returns {Void}
  markRead: function (id) {
    // Get collection as a flatmap with keys represented as `__id`
    var flatmap = UserNotifications.flatmap()

    // @debug
    // console.log('UserNotifications.markRead', id, flatmap['__' + id])

    if (flatmap.hasOwnProperty('__' + id)) {
      flatmap['__' + id].status = 'read'

      // Mark in UI that notification is now unread
      $('[data-notification-id="' + id + '"]').removeClass('ui-notification-status-unread').addClass('ui-notification-status-read')

      // Minus 1 on unreadCount
      UserNotifications.updateUnreadCount(UserNotifications.unreadCount - 1)
    }
  },

  // Mark all unread notifications read
  // All other UserNotifications elements will subscribe to the event `UserNotifications:markAllRead:complete` to trigger re-rendering
  // @method markAllRead
  // @returns {Void}
  markAllRead: function () {
    // Don't update if there's no need
    if (UserNotifications.unreadCount === 0) return

    // @debug
    // console.log('UserNotifications.markAllRead')

    // Set all stored unread notifications to read
    if (UserNotifications.collection instanceof Array && UserNotifications.collection.length > 0) {
      for (var i = 0; i < UserNotifications.collection.length; i++) {
        // Mark notificationObject is read
        if (UserNotifications.collection[i].status === 'unread') {
          UserNotifications.collection[i].status = 'read'
        }
      }
    }

    // Update unreadCount
    UserNotifications.updateUnreadCount(0)

    // @trigger document `UserNotifications:markAllRead:complete`
    $doc.trigger('UserNotifications:markAllRead:complete')

    UserNotifications.delayUpdated()
  },

  // Update the unread count
  // @method updateUnreadCount
  // @param {Int} unreadCount The unread count to set to, otherwise it will run through collection to count
  // @returns {Void}
  updateUnreadCount: function (unreadCount) {

    // @debug
    // console.log('UserNotifications.updateUnreadCount', unreadCount)

    // Count unread if param was incorrect
    if (typeof unreadCount !== 'number') {
      unreadCount = 0
      for (var i = 0; i < UserNotifications.collection.length; i++) {
        if (UserNotifications.collection[i].hasOwnProperty('status') && UserNotifications.collection[i].status === 'unread') {
          unreadCount++
        }
      }
    }

    // Update the unread count
    UserNotifications.unreadCount = unreadCount

    // @trigger document `UserNotifications:updateUnreadCount:complete`
    $doc.trigger('UserNotifications:updateUnreadCount:complete', [unreadCount])
  },

  // Trigger the updated event after a short timer
  // @method delayUpdated
  // @returns {Void}
  delayUpdated: function () {
    clearTimeout(UserNotifications.delayTimer)
    UserNotifications.delayTimer = setTimeout(function () {
      // @debug
      // console.log('UserNotifications.delayUpdated: Triggering UserNotifications:updated event now...')

      // @trigger document `UserNotifications:updated`
      $doc.trigger('UserNotifications:updated', [UserNotifications])
    }, 100)
  }

  // @todo Add in AJAX request stuff here
  //       It would be best to manage actions queue on client side before pushing to server (e.g. marking individual items unread, clearing all, etc)
  //       See DEV-815 for any notes on this!
}

/*
 * jQuery Events
 */
$doc.on('ready', function () {
  // Update the unread count when JS ready
  UserNotifications.updateUnreadCount()

  $doc
    // Push notifications to the collection
    .on('UserNotifications:push', function (event, notifications, options) {
      UserNotifications.push(notifications, options)
    })

    // Replace notifications in collection
    .on('UserNotifications:replace', function (event, notifications, options) {
      UserNotifications.replace(notifications, options)
    })

    // Patch notifications in collection
    .on('UserNotifications:patch', function (event, notifications, options) {
      UserNotifications.patch(notifications, options)
    })

    // Sort collection
    .on('UserNotifications:sort', function (event) {
      UserNotifications.sort()
    })

    // Clear all notifications in collection
    .on('UserNotifications:clearAll', function () {
      UserNotifications.clearAll()
    })

    // Mark a single notification as read
    .on('UserNotifications:markRead', function (event, notificationId) {
      UserNotifications.markRead(notificationId)
    })

    // Mark all collection's notifications as read
    .on('UserNotifications:markAllRead', function () {
      UserNotifications.markAllRead()
    })

    // Click on element designated to trigger 'UserNotifications:markAllRead' event
    .on(Utility.clickEvent, '[data-usernotifications-markallread]', function (event) {
      event.preventDefault()

      // @trigger document `UserNotifications:markAllRead`
      // This will fire via the above bound event and trigger any other watchers
      $doc.trigger('UserNotifications:markAllRead')

      return false
    })

    // Click on an element which represents an unread notifications and mark it read (if it exists in the collection)
    .on(Utility.clickEvent, '[data-notification-id].ui-notification-status-unread', function (event) {
      var $notification = $(this)
      var notificationId = $notification.attr('data-notification-id')
      UserNotifications.markRead(notificationId)
    })
})

module.exports = UserNotifications
