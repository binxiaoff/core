/*
 * ProjectNotifications
 *
 */

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var UserNotifications = require('UserNotifications')

var ProjectNotifications = {

    markRead: function (notification) {

        var $notification = $(notification)
        var notificationId = $notification.data('notification-id').toString()

        // Check if the notification is in the user notifications collection
        // (window.UserNotifications)
        var isUserNotification = UserNotifications.getNotificationById(notificationId)

        // Toggle notification visibility
        ProjectNotifications.toggleNotification(notification)
        if (isUserNotification) {
            UserNotifications.toggleNotification(notificationId)
        }

        if ($notification.is('.ui-notification-status-unread')) {

            // Calculate unread count and update pip
            var $projectDetails  = $notification.closest('[data-parent]')
            var $project = $('#loan-' + $projectDetails.data('loan-id'))
            var currentUnreadCount = parseFloat($project.find('.pip-number').text())

            $project.find('.pip-number').text(currentUnreadCount - 1)
            $projectDetails.find('.pip-number').text(currentUnreadCount - 1)

            // Mark as read
            if (isUserNotification) {
                // Use UserNotifications method
                UserNotifications.markRead(notificationId)
            } else {
                // Use ProjectNotifications method
                $notification.removeClass('ui-notification-status-unread').addClass('ui-notification-status-read')
                ProjectNotifications.track.actions.markedRead.push(notificationId)
                ProjectNotifications.delayAjaxRead(notificationId)
            }
        }
    },

    toggleNotification: function(notification) {
        var $notification = $(notification)
        $notification.toggleClass('ui-notification-open')
    },

    track: {
        actions: {
            markedRead: [],
        }
    },

    ajaxTimer: 0,

    isLoading: false,

    delayAjaxRead: function () {
        if (ProjectNotifications.isLoading) return

        clearTimeout(ProjectNotifications.ajaxTimer)
        ProjectNotifications.ajaxTimer = setTimeout(function () {
            var data = {}

            // Set to loading
            ProjectNotifications.isLoading = true

            data.action = 'read'
            data.list = ProjectNotifications.track.actions.markedRead
            
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
                        ProjectNotifications.track.actions.markedRead = []

                        // @trigger document `UserNotifications:ajax:success` [ProjectNotifications, serverResponse]
                        $doc.trigger('ProjectNotifications:ajax:success', [ProjectNotifications, data])

                        return
                    }

                    // Potential error
                    if (data && data.hasOwnProperty('error') && data.error) {
                        console.warn('ProjectNotifications AJAX error: ' + data.error.details)
                    }

                    // @trigger document `UserNotifications:ajax:error` [ProjectNotifications, serverResponse]
                    $doc.trigger('ProjectNotifications:ajax:error', [ProjectNotifications, data])
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    // @trigger document `UserNotifications:ajax:error` [ProjectNotifications, ajaxErrorObject]
                    $doc.trigger('ProjectNotifications:ajax:error', [ProjectNotifications, {
                        jqXHR: jqXHR,
                        textStatus: textStatus,
                        errorThrown: errorThrown
                    }])
                },
                complete: function () {
                    // @trigger document `UserNotifications:updated` [ProjectNotifications, serverResponse]
                    $doc.trigger('ProjectNotifications:ajax:complete', [ProjectNotifications, data])

                    ProjectNotifications.isLoading = false
                }
            })

            // 2 second delay, as person might click a bunch of notifications instead of pressing "mark all read"
        }, 2000)
    }
}

$(document).on(Utility.clickEvent, '[data-proj-notification]', function (event) {

    // If a link was interacted with, ignore the following actions and let the link event go through
    var $target = $(event.target)
    if ($target.is('a')) return true

    // Mark as read
    ProjectNotifications.markRead(this)
})

module.exports = ProjectNotifications
