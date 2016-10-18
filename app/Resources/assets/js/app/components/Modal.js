/*
 * Unilend Modal
 * Display a modal using Fancybox, as well as supporting extra actions when the user confirms, cancels or closes the modal
 *
 * @componentName   Modal
 * @className       ui-modal
 * @attrPrefix      data-modal
 * @langName        MODAL
 *
 * @usage           <!-- Modals should be wrapped in elements with `display:none` -->
 *                  <div style="display:none">
 *                    <div id="modal-example" data-modal>
 *                      <header class="modal-header"><h2 class="modal-title">Example modal</h2></header>
 *                      <div class="modal-body">
 *                        <p>This is an example modal.</p>
 *                      </div>
 *                      <footer class="modal-footer">
 *                        <a href="javascript:;" class="btn-default" data-modal-doactionclose>Close</a>
 *                      </footer>
 *                    </div>
 *                  </div>
 *                  <!-- Open the modal by clicking an anchor or button element -->
 *                  <a href="javascript:;" data-modal-toggle="#modal-example">Open the example modal</a>
 *
 *                  Check the `self.settings` for extra settings to configure the `data-modal` element with.
 */

// Lib Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')

/*
 * Modal
 * @class
 */
var Modal = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0 || elem.hasOwnProperty('Modal')) return false

  /*
   * Settings
   */
  self.settings = Utility.inherit(
  // Modal default settings
  {
    // ID for the modal (used for Fancybox inline)
    id: (self.$elem.attr('id') || 'modal-' + Utility.randomString()),

    // Type of modal
    type: 'confirm', // `alert`, `dialog`, `confirm`, `prompt`

    // Title of the modal
    title: '',

    // HTML content for the header. By default it will show the title (see the Modal.prototype.templates object)
    header: '',

    // HTML content for the body
    body: '',

    // HTML content for the footer
    footer: '',

    /*
     * @note The title, header, body and footer properties are for if you are programmatically instantiating Modal
     *       or want the JS component to render based on the JS templates, *not* the HTML view content.
     *       Otherwise, set the content for the modal within the HTML element in the view, e.g.
     *
     *       ```html
     *       <div id="example-modal" data-modal data-modal-openonload="true">
     *         <div class="modal-body">
     *           <p>This is an example modal</p>
     *         </div>
     *         <footer class="modal-footer">
     *           <button class="btn-primary" data-modal-doactionclose>Close</button>
     *         </footer>
     *       </div>
     *       ```
     *
     * @note If you are setting the HTML content in the view, don't forget to set buttons with the attributes
     *       `data-modal-doactionconfirm`, `data-modal-doactioncancel` and `data-modal-doactionclose` in order
     *       to fire the modal's respective actions when user interacts with elements.
     */

    // Label for the confirm button
    buttonConfirmLabel: 'OK',

    // Label for the cancel button
    buttonCancelLabel: 'Cancel',

    // Label for the close button
    buttonCloseLabel: 'Close',

    // Show the fancybox close button (in top-right of modal window)
    showFancyboxClose: false,

    // Show background overlay
    showOverlay: true,

    // Close on click overlay
    closeOnClickOverlay: true,

    // Open on load (when the document is ready and this is true, the modal will automatically open)
    openOnLoad: false,

    // Focuses on a [data-modal-doconfirmaction] element after the modal was opened
    focusConfirmOnOpen: true,

    /*
     * The options below essentially mimic that which Fancybox allows
     */

    // Width of modal window
    width: 'auto',

    // Height of modal window
    height: 'auto',

    // Min width of modal window
    minWidth: 300,

    // Min height of modal window
    minHeight: 200,

    // Max width of modal window
    maxWidth: 500,

    // Max Height of modal window
    maxHeight: 500,

    // Allow the modal to autosize when window resized
    autoSize: true,

    // Custom events
    oninit: undefined, // function (elemModal) {}
    onbeforeopen: undefined, // function (elemModal, fancyBoxOptions) {}
    onopened: undefined, // function (elemModal) {}
    onclose: undefined, // function (elemModal) { return {Boolean} false to stop closing event on Fancybox }
    onconfirm: undefined, // function (event, elemModal) { return `{$.deferred:Promise}` }
    oncancel: undefined // function (event, elemModal) { return `{$.deferred:Promise}` }
  },
  // HTML element attribute overrides
  ElementAttrsObject(elem, {
    type: 'data-modal-type',
    title: 'data-modal-title',
    width: 'data-modal-width',
    height: 'data-modal-height',
    minWidth: 'data-modal-minwidth',
    minHeight: 'data-modal-minheight',
    maxWidth: 'data-modal-maxwidth',
    maxHeight: 'data-modal-maxheight',
    buttonConfirmLabel: 'data-modal-confirmlabel',
    buttonCancelLabel: 'data-modal-cancellabel',
    buttonCloseLabel: 'data-modal-closelabel',
    showFancyboxClose: 'data-modal-showfancyboxclose',
    showOverlay: 'data-modal-showoverlay',
    closeOnClickOverlay: 'data-modal-closeonclickoverlay',
    openOnLoad: 'data-modal-openonload',
    focusConfirmOnOpen: 'data-modal-focusconfirmonopen'
  }),
  // JS invocation overrides
  options)

  /*
   * UI
   */

  // Ensure elem shares same ID as the settings
  self.$elem.attr('id', self.settings.id)

  // Assign class to show component behaviours have been applied
  self.$elem.addClass('ui-modal')

  // Render the modal's content to the element to enable Fancybox HTML inlining feature
  if (self.$elem.children().length === 0) {
    self.render()
  }

  /*
   * Initialisation
   */

  // Assign instance of class to the element
  self.$elem[0].Modal = self

  // Init custom event
  if (typeof self.settings.oninit === 'function') {
    self.settings.oninit.call(self)
  }

  // @trigger elem `Modal:initialised` [elemModal, options]
  self.$elem.trigger('Modal:initialised', [self, options])

  // @debug
  // console.log('new Modal', options, self)

  // Open the modal when page loaded
  if (self.settings.openOnLoad) {
    self.open()
  }

  return self
}

/*
 * Prototype methods (shared between all class instances)
 */
Modal.prototype.templates = {
  frame: '<div id="modal-{{ id }}" class="modal-window">{{ header }}{{ body }}{{ footer }}</div>',
  header: '<header class="modal-header">{{ yield }}</header>',
  title: '<h2 class="modal-title">{{ yield }}</h2>',
  body: '<div class="modal-body">{{ yield }}</div>',
  footer: '<footer class="modal-footer">{{ yield }}</div>',
  buttonDefault: '<button class="btn-default" {{ attrs }}>{{ yield }}</button>',
  buttonPrimary: '<button class="btn-primary" {{ attrs }}>{{ yield }}</button>',
  buttonSecondary: '<button class="btn-secondary" {{ attrs }}>{{ yield }}</button>'
}

/*
 * Render the modal's templates
 *
 * @method render
 * @returns {Void}
 */
Modal.prototype.render = function () {
  var self = this

  // @trigger elem `Modal:render:before` [elemModal]
  self.$elem.trigger('Modal:render:before', [self])

  // Templates
  var templates = {
    id: self.settings.id,
    buttonCloseLabel: self.settings.buttonCloseLabel,
    buttonConfirmLabel: self.settings.buttonConfirmLabel,
    buttonCancelLabel: self.settings.buttonCancelLabel
  }

  // Title (only if one is set)
  if (self.settings.title) {
    templates.title = Templating.replace(self.templates.title, {
      yield: self.settings.title
    })
  }

  // Close button
  templates.buttonClose = Templating.replace(self.templates.buttonDefault, {
    yield: self.settings.buttonCloseLabel || 'Close',
    attrs: 'data-modal-doactionclose'
  })

  // Confirm button
  templates.buttonConfirm = Templating.replace(self.templates.buttonPrimary, {
    yield: self.settings.buttonConfirmLabel || 'OK',
    attrs: 'data-modal-doactionconfirm'
  })

  // Cancel button
  templates.buttonCancel = Templating.replace(self.templates.buttonDefault, {
    yield: self.settings.buttonCancelLabel || 'Cancel',
    attrs: 'data-modal-doactioncancel'
  })

  // Header (only if there's a title to show or user-set header content)
  if (self.settings.title && self.settings.header) {
    templates.header = Templating.replace(self.templates.header, [{
      // Show the title by default, only if no user-set header content available
      yield: (self.settings.header ? self.settings.header : self.settings.title),
      title: self.settings.title // if {{ title }} used in user-set header content within {String} `self.settings.header`
    }])
  }

  // Body (only if there's user-set body content)
  if (self.settings.body) {
    templates.body = Templating.replace(self.templates.body, {
      yield: self.settings.body
    })
  }

  // Footer
  // -- Using user-set footer content
  if (self.settings.footer) {
    templates.footer = Templating.replace(self.templates.footer, [{
      yield: self.settings.footer
    }])

    // -- Using default content (generated by type)
  } else {
    // Depending on the type of the modal...
    var footerTemplate = ''
    switch (self.settings.type) {
      case 'dialog':
        footerTemplate = '{{ buttonClose }}'
        break;
      case 'alert':
        footerTemplate = '{{ buttonConfirm }}'
        break;
      case 'confirm':
      case 'prompt':
        footerTemplate = '{{ buttonCancel }}{{ buttonConfirm }}'
        break;
    }

    // Build footer template
    templates.footer = Templating.replace(self.templates.footer, [{
      yield: footerTemplate
    }])
  }

  // Generate the content using the templates
  var modalContent = Templating.replace(self.settings.frame, templates)

  // Set the element's new HTML content (overrides previous)
  self.$elem.html(modalContent)

  // @trigger elem `Modal:rendered` [elemModal, modalContent]
  self.$elem.trigger('Modal:rendered', [self, modalContent])
}

/*
 * Open the Modal
 *
 * @method open
 * @returns {Void}
 */
Modal.prototype.open = function () {
  var self = this

  // Only open if element doesn't exist
  if ($('#' + self.settings.id + ':visible').length > 0) {
    return
  }

  // Setup the fancybox to open with these options
  var fancyBoxOptions = {
    href: '#' + self.$elem.attr('id'),
    type: 'inline',
    modal: true,
    closeBtn: self.settings.showFancyboxClose,
    scrollOutside: false,
    width: self.settings.width,
    height: self.settings.height,
    minWidth: self.settings.minWidth,
    minHeight: self.settings.minHeight,
    maxWidth: self.settings.maxWidth,
    maxHeight: self.settings.maxHeight,
    autoSize: self.settings.autoSize,
    helpers: {
      title: null,
      overlay: (self.settings.showOverlay ? {
        closeClick: self.settings.closeOnClickOverlay,
        locked: false
      } : null)
    },
    // After show
    afterShow: function () {
      if (typeof self.settings.onopened === 'function') {
        return self.settings.onopened.call(self)
      }

      // Focus the confirm button action
      if (self.settings.focusConfirmOnOpen) {
        self.$elem.find('[data-modal-doactionconfirm]').eq(0).focus()
      }

      // @trigger elem `Modal:opened` [elemModal]
      self.$elem.trigger('Modal:opened', [self])
    },
    // Close custom event
    beforeClose: function () {
      if (typeof self.settings.onclose === 'function') {
        return self.settings.onclose.call(self)
      }
    }
  }

  // @trigger elem `Modal:open:before` [elemModal, fancyBoxOptions]
  self.$elem.trigger('Modal:open:before', [self, fancyBoxOptions])

  // Custom event
  if (typeof self.settings.onbeforeopen === 'function') {
    self.settings.onbeforeopen.apply(self, [self, fancyBoxOptions])
  }

  // Open the modal with fancybox
  $.fancybox(self.$elem, fancyBoxOptions)

  // @debug
  // console.log('Modal.open', self)
}

/*
 * Confirm the modal
 *
 * @method confirm
 * @returns {Void}
 */
Modal.prototype.confirm = function () {
  var self = this

  // @debug
  // console.log('Modal.prototype.confirm')

  // @trigger elem `Modal:confirm:before`
  self.$elem.trigger('Modal:confirm:before', [self])

  // Fire the custom `onconfirm` action
  if (typeof self.settings.onconfirm === 'function') {
    // onconfirm should return a Promise made via jQuery's $.deferred API
    self.settings.onconfirm.call(self).always(function (deferredError) {
      if (!deferredError) {
        // @trigger elem `Modal:confirmed`
        self.$elem.trigger('Modal:confirmed', [self])

        // Close the modal
        self.close()
      } else {
        // @trigger elem `Modal:confirm:error`
        self.$elem.trigger('Modal:confirm:error', [self, deferredError])
      }
    })

  // Close the modal
  } else {
    // @trigger elem `Modal:confirmed`
    self.$elem.trigger('Modal:confirmed', [self])

    self.close()
  }
}

/*
 * Cancel the modal
 *
 * @method cancel
 * @returns {Void}
 */
Modal.prototype.cancel = function () {
  var self = this

  // @debug
  // console.log('Modal.prototype.cancel')

  // @trigger elem `Modal:cancel:before` [elemModal]
  self.$elem.trigger('Modal:cancel:before', [self])

  // Fire the custom `oncancel` action
  if (typeof self.settings.oncancel === 'function') {
    // oncancel should return a Promise made via jQuery's $.deferred API
    self.settings.oncancel.call(self).always(function (deferredError) {
      // @trigger elem `Modal:cancelled`
      self.$elem.trigger('Modal:cancelled', [self])

      // Close the modal
      self.close()
    })

  // Close the modal
  } else {
    // @trigger elem `Modal:cancelled`
    self.$elem.trigger('Modal:cancelled', [self])

    self.close()
  }
}

/*
 * Close the modal
 *
 * @method close
 * @returns {Void}
 */
Modal.prototype.close = function () {
  var self = this

  // @debug
  // console.log('Modal.prototype.close')

  // @trigger elem `Modal:close:before`
  self.$elem.trigger('Modal:close:before', [self])

  // Only close if the element exists
  if (self.$elem.is(':visible')) {
    $.fancybox.close()

    // @trigger elem `Modal:closed`
    self.$elem.trigger('Modal:closed', [self])
  }
}

/*
 * Reposition the modal
 *
 * @method position
 * @returns {Void}
 */
Modal.prototype.position = function () {
  var self = this

  // @trigger elem `Modal:position:before`
  self.$elem.trigger('Modal:position:before', [self])

  // Only position if the element exists
  if ($('#' + self.settings.id + ':visible').length > 0) {
    $.fancybox.reposition()

    // @trigger elem `Modal:positioned`
    self.$elem.trigger('Modal:positioned', [self])
  }
}

/*
 * Update the modal dimensions
 *
 * @method update
 * @returns {Void}
 */
Modal.prototype.update = function () {
  var self = this

  // @trigger elem `Modal:update:before`
  self.$elem.trigger('Modal:update:before', [self])

  // Only position if the element exists
  if ($('#' + self.settings.id + ':visible').length > 0) {
    $.fancybox.update()

    // @trigger elem `Modal:updated`
    self.$elem.trigger('Modal:updated', [self])
  }
}

/*
 * Destroy the Modal instance
 *
 * @method destroy
 * @returns {Void}
 */
Modal.prototype.destroy = function () {
  var self = this

  // @trigger elem `Modal:destroy:before`
  self.$elem.trigger('Modal:destroy:before', [self])

  self.$elem[0].Modal = false
  delete self
}

/*
 * jQuery Plugin
 */
$.fn.uiModal = function (op) {
  // Fire a command to the Modal object, e.g. $('[data-modal]').uiModal('publicMethod', {..})
  // @todo add in list of public methods that $.fn.uiModal can reference
  if (typeof op === 'string' && /^(open|confirm|cancel|close|position|update)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('Modal') && typeof elem.Modal[op] === 'function') {
        elem.Modal[op].apply(elem.Modal, args)
      }
    })

    // Set up a new Modal instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('Modal')) {
        new Modal(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
  // Auto-init `[data-modal]` elements
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-modal]').not('.ui-modal').uiModal()
  })

  // Perform confirm action if user clicks item with `[data-modal-doactionconfirm]`
  .on(Utility.clickEvent, '[data-modal-doactionconfirm]', function (event) {
    // @debug
    console.log('Clicked [data-modal-doactionconfirm]')
    var $modal = $(this).parents('.ui-modal')
    $modal.uiModal('confirm')
  })

  // Perform cancel action if user clicks item with `[data-modal-doactioncancel]`
  .on(Utility.clickEvent, '[data-modal-doactioncancel]', function (event) {
    // @debug
    console.log('Clicked [data-modal-doactioncancel]')
    var $modal = $(this).parents('.ui-modal')
    $modal.uiModal('cancel')
  })

  // Perform close action if user clicks item with `[data-modal-doactionclose]`
  .on(Utility.clickEvent, '[data-modal-doactionclose]', function (event) {
    // @debug
    console.log('Clicked [data-modal-doactionclose]')
    var $modal = $(this).parents('.ui-modal')
    $modal.uiModal('close')
  })

  // Open a modal by clicking another element
  .on(Utility.clickEvent, '[data-modal-toggle], .ui-modal-toggle', function (event) {
    var $elem = $(this)
    var targetModal = $elem.attr('data-target') || $elem.attr('data-modal-toggle')

    // Open the modal
    if (Utility.elemExists(targetModal)) {
      var $targetModal = $(targetModal)
      $targetModal.uiModal('open')
    }
  })