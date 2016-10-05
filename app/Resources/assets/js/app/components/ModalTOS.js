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

  .ready(function () {
    $('#form-tos').load('/cgv-popup', function() {
      $.fancybox.update()
    });
  })
  // Because this modal is auto-instantiated, I'm just going to bind to element's events to set onconfirm actions
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
          terms: $('#modal-tos input[name="terms"]').is(':checked')
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
  })