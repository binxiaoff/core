/*
 * Unilend Modal TOS
 * @note I had written this to connect to server via AJAX as previous version had it, but since talking to Antoine
 *       he's mentioned that it's OK to do on a server load. It's still here in case you want to revive the AJAX method.
 * @note This also serves as an implementation example of using a Modal with AJAX requests and responses.
 */

// Lib Dependencies
var $ = require('jquery')
var $doc = $(document)

$doc
  // Disable the TOS's form submit event, since this is handled via AJAX in the onconfirm/oncancel actions below
  .on('submit', '#form-tos', function (event) {
    event.preventDefault()
    return false
  })

  // Because this modal is auto-instantiated, I'm just going to bind to element's events to set onconfirm and oncancel actions
  .on('Modal:initialised', '#modal-tos', function (event, elemModal) {

    // Fired when user confirms they have accepted the new Terms of Service...
    elemModal.settings.onconfirm = function () {
      var deferredResult = $.Deferred()

      // Show loading spinner via Fancybox
      $.fancybox.showLoading()

      // Perform AJAX request to the server
      $.ajax({
        // Setup AJAX
        url: $('#form-tos').attr('action'),
        method: $('#form-tos').attr('method'),
        global: false,
        data: {
          terms: 'true',
          id_legal_doc: $('#modal-tos input[name="id_legal_doc"]').val()
        },

        // Event: received server response
        success: function (data, textStatus, xhr) {
          // Success
          // @todo get translated output messages from server response data
          if (data) {
            deferredResult.resolve(0)

          // Connection error
          } else {
            alert('An error occurred when connecting to the server. Please try again!')
            deferredResult.reject(1)
          }
        },

        // Event: server error
        error: function (textStatus, error, xhr) {
          alert('An error occurred when connecting to the server. Please try again!')
          deferredResult.reject(1)
        },

        // Event: always fired after any resolve/reject
        complete: function () {
          // Hide loading spinner
          $.fancybox.hideLoading()
        }
      })

      return deferredResult
    }

    // Fired when user does not accept the new Terms of Service...
    elemModal.settings.oncancel = function () {
      var deferredResult = $.Deferred()

      // Confirm if user really wants to do this...
      if (confirm('By not accepting the updated terms of service you will not be able to use Unilend. Do you really want to continue?')) {

        // Show loading spinner via Fancybox
        $.fancybox.showLoading()

        // Perform AJAX request to the server
        $.ajax({
          // Setup AJAX
          url: $('#form-tos').attr('action'),
          method: $('#form-tos').attr('method'),
          global: false,
          data: {
            terms: 'false',
            id_legal_doc: $('#modal-tos input[name="id_legal_doc"]').val()
          },

          // Event: received server response
          success: function (data, textStatus, xhr) {
            // @todo get translated output messages from server AJAX response
            // Success
            if (data) {
              // @todo figure out what to do here
              // @note maybe log out the user?
              deferredResult.reject(0)
              window.location = '/logout'

            // Connection error
            } else {
              alert('An error occurred when connecting to the server. Please try again!')
              deferredResult.reject(1)
            }
          },

          // Event: server error
          error: function (textStatus, error, xhr) {
            alert('An error occurred when connecting to the server. Please try again!')
            deferredResult.reject(1)
          },

          // Event: always fired after any resolve/reject
          complete: function () {
            // Hide loading spinner
            $.fancybox.hideLoading()
          }
        })
      }

      return deferredResult
    }
  })