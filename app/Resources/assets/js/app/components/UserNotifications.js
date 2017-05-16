/*
 * User Notifications component
 * Manages the global notifications collection for updating UI user notifications components
 *
 * @model {NotificationObject}
 * {
 *    // Server
 *    'id':       {String}; default: Utility.randomString(),
 *    'type':     {String}; default: 'default',
 *    'status':   {String}; accepted: 'read', 'unread'; default: 'unread',
 *    'iso-8601': {String}; Must adhere to ISO 8601 date format
 *    'date':     {Date};
 *    'datetime': {Mixed}; accepted: {String}, {Date}; default: '',
 *    'title':    {String}; default: '',
 *    'image':    {String}; default: '',
 *    'content':  {String}; default: ''
 *
 *    // UI only
 *    'isOpen':   {Boolean}; default: false
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
var Templating = require('Templating')
var $doc = $(document)

// Normalise a {NotificationObject} as server version is different to JS version
function normaliseNotificationObject (notification) {
  // Make a copy
  var newNotification = $.extend({
    // Add UserNotifications collection specific info (managing additional UI state, etc.)
    isOpen: false
  }, notification)

  // Do necessary tests and transforms on the copy
  // -- Image
  if (!/^\<svg/.test(newNotification.image)) {
    newNotification.image = Utility.svgImage('#notification-' + newNotification.image, newNotification.title, 100, 100, 'none')
  }

  // -- Get date from iso string
  if (newNotification.hasOwnProperty('iso-8601')) {
    newNotification.date = Utility.getDate(newNotification['iso-8601'])
  }

  // Get the date from a {String} datetime, but only if the date's not already set
  if (typeof newNotification.datetime === 'string' && (!newNotification.hasOwnProperty('date') || !(newNotification.date instanceof Date))) {
    newNotification.date = Utility.getDate(newNotification.datetime)
  }

  // Set the datetime to be a {string} from datetime's own date property
  if (typeof newNotification.datetime === 'object' && newNotification.datetime.hasOwnProperty('date')) {
    newNotification.datetime = newNotification.datetime.date
  }

  // @debug
  // console.log('normaliseNotificationObject', notification, newNotification)

  // Return the copy
  return newNotification
}

// Normalise a collection of notification objects
function normaliseNotificationObjects (notifications) {
  // Always normalise the input notifications collection
  if (notifications && notifications.length > 0) {
    for (var i = 0; i < notifications.length; i++) {
      notifications[i] = normaliseNotificationObject(notifications[i])
    }
  }

  return notifications
}

// Sort a collection of notifications
// @note Assumes collection has already been normalised
function sortNotifications (notifications) {
  if (!notifications) return

  // Sort via which has a newer date
  function testDate (a, b) {
    if (a.hasOwnProperty('date') && b.hasOwnProperty('date')) {
      if (a.date > b.date) {
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
  notifications.sort(function (a, b) {
    return testDate(a, b) || testId(a, b)
  })

  return notifications
}

// Flatmap collection of notifications
function flatmapNotifications (notifications) {
  var notificationsFlatmap = {}

  // Ensure there's a collection to flatmap
  if (notifications) {
    for (var i = 0; i < notifications.length; i++) {
      if (notifications[i].hasOwnProperty('id')) {
        notificationsFlatmap['__' + notifications[i].id] = notifications[i]
      }
    }
  }

  // @debug
  // console.log('UserNotifications flatmap', notificationsFlatmap)

  return notificationsFlatmap
}

// Clean notifications
// @note convenience method for flatmap and sort
function cleanNotifications (notifications) {
  var notificationsFlatmapped = flatmapNotifications(notifications)
  var newNotifications = []

  // Nothing
  if (!notifications) return newNotifications

  // Flatmap by design removes duplicates
  for (var i in notificationsFlatmapped) {
    newNotifications.push(notificationsFlatmapped[i])
  }

  // Sort notifications
  sortNotifications(newNotifications)

  // @debug
  // console.log('cleanNotifications', notifications, newNotifications)

  return newNotifications
}

// UserNotifications
// @note Exposed to the global window so other components can reference it
var UserNotifications = window.UserNotifications = {
  // All the current user notifications being stored
  // @note this is referencing the variable defined near the bottom of the _layout.html.twig template
  // @note Ensure to normalise them
  collection: normaliseNotificationObjects(window.USERNOTIFICATIONS) || [],

  // The number of unread notifications
  unreadCount: 0,

  // A timer to delay pushing the updated event
  delayTimer: 0,

  // A timer to delay AJAX requests
  ajaxTimer: 0,

  // {Boolean} to check if loading actions are happening and prevents any further actions
  isLoading: false,

  // Templates
  templates: {
    // Pip to indicate unread notifications amount
    pip: '<span class="pip {{ classNames }}"><span class="pip-number">{{ amount }}</span></span>'
  },

  // Tracking vars for managing pagination
  track: {
    dateFrom: new Date(),
    dateTo: undefined,
    perPage: 20,
    currentPage: 1,
    actions: {
      markedRead: [],
      markedAllRead: false
    }
  },

  // Cleans the collection (sorts and prunes potential duplicates)
  // @method sort
  // @returns {Void}
  clean: function (doUpdate) {
    // No need to sort an empty collection
    if (UserNotifications.collection.length === 0) return

    // @debug
    // console.log('UserNotifications.sort')

    // Sort the notifications collection
    UserNotifications.collection = cleanNotifications(UserNotifications.collection)

    // @trigger `UserNotifications:cleaned`
    $doc.trigger('UserNotifications:cleaned')

    // Delay the updated custom event, as it re-rendered elements
    if (doUpdate) {
      UserNotifications.delayUpdated()
    }
  },

  // Pull notifications from server
  // @method pull
  // @param {Object} options
  // @returns {Void}
  pull: function (options) {
    if (UserNotifications.isLoading) return

    // Options for retrieving from server
    options = $.extend({
      perPage: UserNotifications.track.perPage,
      currentPage: UserNotifications.track.currentPage
    }, options)

    // Do AJAX
    UserNotifications.isLoading = true

    // Show spinner
    $('.spinner-usernotifications-loadingmore').parent().addClass('ui-is-loading')

    clearTimeout(UserNotifications.ajaxTimer)
    UserNotifications.ajaxTimer = setTimeout(function () {
      $.ajax({
        url: '/notifications/pagination',
        method: 'GET',
        data: options,
        global: false,
        success: function (data, textStatus) {
          if (textStatus === 'success' && data && data.hasOwnProperty('notifications') && data.notifications) {
            // Add more notifications to the collection
            if (data.notifications.length > 0) {
              // Push the received notifications to the collection
              if (options.currentPage !== UserNotifications.track.currentPage) {
                // Update the tracking values
                UserNotifications.track.currentPage = data.pagination.currentPage
                UserNotifications.track.perPage = data.pagination.perPage

                // Push the new notifications to the collection
                UserNotifications.push(data.notifications)
              }

            // Hide the view more buttons
            } else {
              $('.ui-usernotifications-loadmore').hide()
            }

            return
          }

          // Error
          if (data && data.hasOwnProperty('error') && data.error) {
            console.warn('UserNotifications.pull Error: ' + data.error.details)
          }
        },
        complete: function () {
          UserNotifications.isLoading = false

          // Hide spinner
          $('.spinner-usernotifications-loadingmore').parent().removeClass('ui-is-loading')
        }
      })
    }, 500)
  },

  // Push notifications to the collection
  // @method push
  // @returns {Void}
  push: function (notifications, options) {

    // @debug
    // console.log('UserNotifications.push', notifications, options)

    // Only operate on legal arrays
    if (!(notifications instanceof Array)) {
      console.warn('UserNotifications.push: param notifications given was not array')
      return
    }

    // Normalise all the notifications
    normaliseNotificationObjects(notifications)

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

    // Sort the collection to ensure notifications are in order
    sortNotifications(UserNotifications.collection)

    // Update unreadCount
    UserNotifications.updateUnreadCount()

    // @trigger document `UserNotifications:push:complete`
    $doc.trigger('UserNotifications:push:complete')

    // Delay the updated custom event, as it re-rendered elements
    UserNotifications.delayUpdated(true)
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

    // Normalise all the notifications
    normaliseNotificationObjects(notifications)

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

    // @trigger document `UserNotifications:replace:complete`
    $doc.trigger('UserNotifications:replace:complete')

    // Delay the updated custom event, as it re-rendered elements
    UserNotifications.delayUpdated(true)
  },

  // Patch notifications with updated ones
  // Only performs patch on {NotificationObject}s which share the same ID
  // @method patch
  // @param {Array} notifications
  // @returns {Void}
  patch: function (notifications, options) {
    // Get collection as a flatmap with keys represented as `__id`
    var flatmap = flatmapNotifications(UserNotifications.collection)

    // @debug
    // console.log('UserNotifications.patch', notifications, options)

    // Only operate on legal arrays
    if (!(notifications instanceof Array)) {
      console.warn('UserNotifications.patch: param notifications given was not array')
      return
    }

    // Normalise all the notifications
    normaliseNotificationObjects(notifications)

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

    // Delay the updated custom event, as it re-rendered elements
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

    // Delay the updated custom event, as it re-rendered elements
    UserNotifications.delayUpdated()
  },

  // Get notification by ID
  // @method getNotificationById
  // @param {String} id
  // @returns {NotificationObject}
  getNotificationById: function (id) {
    // Already object then assume {NotificationObject}
    if (typeof id === 'object') return id

    // String/number match on the ID
    for (var i = 0; i < UserNotifications.collection.length; i++) {
      if (UserNotifications.collection[i].id === id) {
        return UserNotifications.collection[i]
      }
    }

    return undefined
  },

  // Open a single notification
  openNotification: function (id) {
    var notification = UserNotifications.getNotificationById(id)

    if (notification) {
      notification.isOpen = true
      $('[data-notification-id="' + notification.id + '"]').addClass('ui-notification-open')
    }
  },

  // Close a single notification
  closeNotification: function (id) {
    var notification = UserNotifications.getNotificationById(id)

    if (notification) {
      notification.isOpen = false
      $('[data-notification-id="' + notification.id + '"]').removeClass('ui-notification-open')
    }
  },

  // Toggle a notification
  toggleNotification: function (id) {
    var notification = UserNotifications.getNotificationById(id)

    if (notification && notification.isOpen) {
      UserNotifications.closeNotification(notification)
    } else {
      UserNotifications.openNotification(notification)
    }
  },

  // Mark a single notification read
  // @method markRead
  // @param {String} id
  // @returns {Void}
  markRead: function (id) {
    // Get the single notification
    var notification = UserNotifications.getNotificationById(id)

    if (notification) {
      notification.status = 'read'

      // Mark in UI that notification is now unread
      $('[data-notification-id="' + id + '"]').removeClass('ui-notification-status-unread').addClass('ui-notification-status-read')

      // Track that the notification was marked read
      UserNotifications.track.actions.markedRead.push(id)

      // Minus 1 on unreadCount
      UserNotifications.updateUnreadCount(UserNotifications.unreadCount - 1)

      // Update via AJAX
      UserNotifications.delayAjaxRead()
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

    // Update items in DOM to show that they have been read
    $('[data-notification-id]').removeClass('ui-notification-status-unread').addClass('ui-notification-status-read')

    // Track marked all read
    UserNotifications.track.actions.markedAllRead = true

    // Update unreadCount
    UserNotifications.updateUnreadCount(0)

    // @trigger document `UserNotifications:markAllRead:complete`
    $doc.trigger('UserNotifications:markAllRead:complete')

    // Update via AJAX
    UserNotifications.delayAjaxRead()
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

    // @debug
    // console.log('UserNotifications.updateUnreadCount', unreadCount)

    // Update the unread count
    UserNotifications.unreadCount = unreadCount

    // Update any `[data-usernotifications-unreadcount]` pips
    UserNotifications.updatePip(unreadCount)

    // @trigger document `UserNotifications:updateUnreadCount:complete`
    $doc.trigger('UserNotifications:updateUnreadCount:complete', [unreadCount])
  },

  // Update the pip number in any element marked `[data-usernotifications-unreadcount]`
  // @method updatePip
  // @param {Int} amount
  // @returns {Void}
  updatePip: function (amount) {
    var pipHTML = ''

    // Generate the new pip HTML if there are any unread notifications specified by amount
    pipHTML = Templating.replace(UserNotifications.templates.pip, {
      amount: amount,
      // Mark if has none (changes color) or has many (changes amount number to circle)
      classNames: (amount === 0 ? 'pip-has-none' : (amount > 9 ? 'pip-has-many' : ''))
    })

    // Set the unreadcount element's HTML
    $('[data-usernotifications-unreadcount]').html(pipHTML)
  },

  // Trigger update to notifications after a short timer
  // @method delayUpdated
  // @returns {Void}
  delayUpdated: function (doClean) {
    clearTimeout(UserNotifications.delayTimer)
    UserNotifications.delayTimer = setTimeout(function () {
      // Clean the notifications collection
      if (doClean) {
        // Clean fires the delayUpdated, so we set it to inverse of doClean to not fire
        UserNotifications.clean(!doClean)
      }

      // @trigger document `UserNotifications:updated` [UserNotifications]
      $doc.trigger('UserNotifications:updated', [UserNotifications])
    }, 250)
  },

  // Trigger the AJAX event after a short timer
  // @method delayAjaxRead
  // @returns {Void}
  delayAjaxRead: function () {
    if (UserNotifications.isLoading) return

    clearTimeout(UserNotifications.ajaxTimer)
    UserNotifications.ajaxTimer = setTimeout(function () {
      var data = {}

      // Set to loading
      UserNotifications.isLoading = true

      // Determine what kind of action to send
      if (UserNotifications.track.actions.markedAllRead) {
        data.action = 'all_read'
      } else if (UserNotifications.track.actions.markedRead.length > 0) {
        data.action = 'read'
        data.list = UserNotifications.track.actions.markedRead
      }

      // Do AJAX
      $.ajax({
        url: '/notifications/update',
        method: 'POST',
        data: data,
        global: false,
        success: function (data, textStatus) {
          if (textStatus === 'success' && data && data.hasOwnProperty('success') && data.success) {
            // @debug
            // console.log('UserNotifications successfully updated', data)

            // Update tracking
            UserNotifications.track.actions.markedAllRead = false
            UserNotifications.track.actions.markedRead = []

            // @trigger document `UserNotifications:ajax:success` [UserNotifications, serverResponse]
            $doc.trigger('UserNotifications:ajax:success', [UserNotifications, data])

            return
          }

          // Potential error
          if (data && data.hasOwnProperty('error') && data.error) {
            console.warn('UserNotifications AJAX error: ' + data.error.details)
          }

          // @trigger document `UserNotifications:ajax:error` [UserNotifications, serverResponse]
          $doc.trigger('UserNotifications:ajax:error', [UserNotifications, data])
        },
        error: function (jqXHR, textStatus, errorThrown) {
          // @trigger document `UserNotifications:ajax:error` [UserNotifications, ajaxErrorObject]
          $doc.trigger('UserNotifications:ajax:error', [UserNotifications, {
            jqXHR: jqXHR,
            textStatus: textStatus,
            errorThrown: errorThrown
          }])
        },
        complete: function () {
          // @trigger document `UserNotifications:updated` [UserNotifications, serverResponse]
          $doc.trigger('UserNotifications:ajax:complete', [UserNotifications, data])

          UserNotifications.isLoading = false
        }
      })

    // 2 second delay, as person might click a bunch of notifications instead of pressing "mark all read"
    }, 2000)
  }
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

    // Focus a notification to show its details
    // .on('focus', '[data-notification-id]', function (event) {
    //   var $target = $(event.target)
    //
    //   // Toggle the notification's open class
    //   var $notification = $(this)
    //   var notificationId = $notification.attr('data-notification-id')
    //
    //   // Toggle notification
    //   UserNotifications.openNotification(notificationId)
    //
    //   // If notification unread, mark as read
    //   if ($notification.is('.ui-notification-status-unread')) {
    //     UserNotifications.markRead(notificationId)
    //   }
    // })

    // Click on an element to show/hide its details
    .on(Utility.inputStartEvent + ' keydown', '[data-notification-id]', function (event) {
      var $target = $(event.target)

      // If a link was interacted with, ignore the following actions and let the link event go through
      if ($target.is('a')) return true

      if (event.type === 'mousedown' || event.type === 'touchstart' || (event.type === 'keydown' && event.which === 13)) {
        // Toggle the notification's open class
        var $notification = $(this)
        var notificationId = $notification.attr('data-notification-id')

        // Toggle notification (if not a project or an ignore-toggle notification)
        if ($target.closest('[data-ignore-toggle]').length === 0 && $target.closest('[data-proj-notification]').length === 0) {
          UserNotifications.toggleNotification(notificationId)
          // If notification unread, mark as read
          if ($notification.is('.ui-notification-status-unread')) {
            UserNotifications.markRead(notificationId)
          }
        }
      }
    })

    // Load in more notifications
    .on(Utility.clickEvent, '.ui-usernotifications-loadmore', function (event) {
      var $elem = $(this)
      event.preventDefault()

      // Only do if not currently disabled
      if (!$elem.is(':disabled')) {
        UserNotifications.pull({
          currentPage: UserNotifications.track.currentPage + 1
        })
      }
    })
})

module.exports = UserNotifications
