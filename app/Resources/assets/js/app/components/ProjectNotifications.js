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

    toggleNotification: function($notification, notificationId) {
        var notificationId = notificationId.toString()
        if (UserNotifications.getNotificationById(notificationId) === undefined) {
            $notification.toggleClass('ui-notification-open')
        }
    },

    markRead: function (notification) {
        var $notification = $(notification)
        var $projectDetails  = $notification.closest('[data-parent]')
        var $project = $projectDetails.prev() // Same as $('#' + $projectDetails.data('loan-id'))

        var projectId = $project.data('loan-id')
        var notificationId = $notification.data('notification-id')

        ProjectNotifications.toggleNotification($notification, notificationId)

        if ($notification.is('.ui-notification-status-unread')) {

            // Update notification class
            $notification.removeClass('ui-notification-status-unread').addClass('ui-notification-status-read')

            // Update PIP
            var currentUnreadCound = parseFloat($project.find('.pip').text())
            var updatedUnreadCount = currentUnreadCound - 1
            $project.find('.pip').text(updatedUnreadCount)
            $projectDetails.find('.pip').text(updatedUnreadCount)

            // Update DB
            if (!ProjectNotifications.isLoading) {
                ProjectNotifications.delayAjaxRead(projectId, notificationId)
            }
        }
    },

    ajaxTimer: 0,

    isLoading: false,

    delayAjaxRead: function(projectId, notificationId) {

        if (ProjectNotifications.isLoading) return

        clearTimeout(ProjectNotifications.ajaxTimer)
        UserNotifications.ajaxTimer = setTimeout(function () {

            // Set to loading
            UserNotifications.isLoading = true

            // Determine what kind of action to send
            var data = {}
            data.action = 'read'
            data.notification = notificationId
            data.project = projectId

            console.log(data)

            // Do AJAX
            $.ajax({
                url: '/notifications/update',
                method: 'POST',
                data: data,
                global: false,
                success: function (data, textStatus) {
                    if (textStatus === 'success' && data && data.hasOwnProperty('success') && data.success) {
                        // @debug
                        // console.log('ProjectNotifications successfully updated', data)

                        // @trigger document `UserNotifications:ajax:success` [UserNotifications, serverResponse]
                        $doc.trigger('ProjectNotifications:ajax:success', [ProjectNotifications, data])

                        return
                    }

                    // Potential error
                    if (data && data.hasOwnProperty('error') && data.error) {
                        console.warn('ProjectNotifications AJAX error: ' + data.error.details)
                    }

                    // @trigger document `UserNotifications:ajax:error` [UserNotifications, serverResponse]
                    $doc.trigger('ProjectNotifications:ajax:error', [UserNotifications, data])
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
                    $doc.trigger('ProjectNotifications:ajax:complete', [ProjectNotifications, data])

                    ProjectNotifications.isLoading = false
                }
            })

            // 2 second delay, as person might click a bunch of notifications instead of pressing "mark all read"
        }, 2000)
    }
}

module.exports = ProjectNotifications
