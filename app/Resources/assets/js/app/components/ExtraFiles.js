/*
 * Extra Files component
 * The beginnings of refactoring the "Ajouter des fichiers" in the main.dev.js to be more scalable depending on context
 */

var $ = require('jquery')
var Utility = require('Utility')

var ExtraFiles = function (elem, options) {
  var self = this
  self.$elem = $(elem)
  if (self.$elem.length === 0) {
    console.warn('new ExtraFiles Error: No element specified')
    return
  }

  // Settings
  self.settings = $.extend({
    listElem: '.ui-extrafiles-list',
    toggleElem: '.ui-extrafiles-toggle',
    template: {
      listItem: self.$elem.find('.ui-extrafiles-template').html()
    }
  }, ElementAttrsObject(elem, {
    listElem: 'data-extrafiles-listelem',
    toggleElem: 'data-extrafiles-toggle'
  }), options)

  // Tracking vars
  self.track = {
    totalFiles: 0
  }

  // Elements
  // -- Toggle
  if (Utility.checkElemSelector(self.settings.toggleElem) && self.$elem.find(self.settings.toggleElem).length > 0) {
    self.$toggle = self.$elem.find(self.settings.toggleElem)
    self.$toggle.addClass('ui-extrafiles-toggle')
  } else {
    self.$toggle = $(self.templates.toggle)
    self.$toggle.appendTo(self.$elem)
  }

  // -- List
  if (Utility.checkElemSelector(self.settings.listElem) && self.$elem.find(self.settings.listElem).length > 0) {
    self.$list = self.$elem.find(self.settings.listElem)
    self.$toggle.addClass('ui-extrafiles-list')
  } else {
    self.$list = $(self.templates.list)
    self.$list.appendTo(self.$elem)
  }

  // UI
  self.$elem.addClass('ui-extrafiles')

  // Attach instance to element
  self.$elem[0].ExtraFiles = self

  self.init()

  return self
}

//
ExtraFiles.prototype.templates = {
  toggle: '<input type="checkbox" class="ui-extrafiles-toggle" value="true" />',
  list: '<div class="ui-extrafiles-list"></div>',
  // The below is here as an example. Ideally you'll use the `self.settings.templates.listItem` field to target a hidden HTML element and use its contents for translation and other HTML generation purposes (like generating the list of file types)
  listItem: '<div class="form-field form-field-multi file-upload-extra row row-multi-col-computer" data-formvalidation-input data-formvalidation-required="true" data-formvalidation-type="typedFile">\
  <div class="col-lg-6 col-md-6">\
    <select name="files[extra__NUM__]" class="input-field">\
      <option value="">{{ project-request_files-select-label }}</option>\
      <option value="{{ attachment.id }}">{{ attachment.label }}</option>\
    </select>\
  </div>\
  <div class="col-lg-6 col-md-6">\
    <div class="custom-input-file" id="form-extrafile" data-fileattach data-fileattach-inputname="extra__NUM__" data-fileattach-maxfiles="1" data-fileattach-filetypes="pdf jpg jpeg png tiff doc docx" data-formvalidation-required=\'input[type="file"][name="file[__NUM__]"]\'></div>\
  </div>\
</div>'
}

// Initialise the ExtraFiles component
// @method init
// @returns {Void}
ExtraFiles.prototype.init = function () {
  var self = this

  // Save the template specified in settings into the ExtraFiles instance
  if (self.settings.templates) {
    for (var i in self.settings.templates) {
      if (self.settings.templates.hasOwnProperty(i)) {
        self.templates[i] = self.settings.templates
      }
    }
  }

  // Check for existing files
  self.checkForFiles()
}

// Check if element has extra files already and shows/hides the list contents
// @method checkForFiles
// @returns {Void}
ExtraFiles.prototype.checkForFiles = function () {
  var self = this

  // Update total files count
  self.track.totalFiles = self.$list.find('.file-upload-extra').length

  // If no extra files added, add one to prompt user
  if (!self.track.totalFiles)
    self.addFile()
    self.$toggle.prop('checked', true)
  }

  // Show (or hide) the list depending on the files
  self.checkListVisibility()
}

// Check if element has extra files already and shows/hides the list contents
// @method checkForFiles
// @returns {Void}
ExtraFiles.prototype.checkListVisibility = function () {
  var self = this

  // Toggle visibility of list
  if (self.$toggle.is(':checked')) {
    self.$list.slideDown()
  } else {
    self.$list.slideUp()
  }
}

// Add an extra file
// @method addFile
// @returns {Void}
ExtraFiles.prototype.addFile = function () {
  var self = this

  // Update total files count
  self.track.totalFiles = self.$list.find('.file-upload-extra').length

  // Prepare the template
  var extraFileHTML = Templating.replace(self.templates.listItem, {
    NUM: self.track.totalFiles - 1
  }, {
    keywordPrefix: '__',
    keywordSuffix: '__'
  })

  // Make the new one and add to the list, as well as ensuring any necessary elements have UI behaviours applied
  var $extraFile = $(extraFileHTML)
  $extraFile.appendTo(self.$list).trigger('UI:visible')
}

// Remove a file
// @param {Int} index
// @returns {Void}
ExtraFiles.prototype.removeFile = function (index) {
  var self = this

  // Update total files count
  self.track.totalFiles = self.$list.find('.file-upload-extra').length

  // Prepare the template
  var extraFileHTML = Templating.replace(self.templates.listItem, {
    NUM: self.track.totalFiles - 1
  }, {
    keywordPrefix: '__',
    keywordSuffix: '__'
  })

  // Make the new one and add to the list, as well as ensuring any necessary elements have UI behaviours applied
  var $extraFile = $(extraFileHTML)
  $extraFile.appendTo(self.$list).trigger('UI:visible')
}

/*
 * jQuery Plugin
 */
$.fn.uiExtraFiles = function () {
  // Fire a command to the ExtraFiles object, e.g. $('[data-extrafiles]').uiExtraFiles('publicMethod', {..})
  // @todo add in list of public methods that $.fn.uiExtraFiles can reference
  if (typeof op === 'string' && /^(checkForFiles|addFile|removeFile)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('ExtraFiles') && typeof elem.ExtraFiles[op] === 'function') {
        elem.Modal[op].apply(elem.ExtraFiles, args)
      }
    })

    // Set up a new ExtraFiles instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('ExtraFiles')) {
        new ExtraFiles(elem, op)
      }
    })
  }
}

/*
 * jQuery events
 */
$doc
// Declaritive instantiation
  .on('ready UI:visible', function (event) {
    $(event.target).find('[data-extrafiles]').not('.ui-extrafiles').uiExtraFiles()
  })

  // Toggle extra files
  .on('change', '.ui-extrafiles-toggle', function (event) {
    $(this).parents('.ui-extrafiles').uiExtraFiles('checkForFiles')
  })

  // Add extra files item
  .on(Utility.clickEvent, '.ui-extrafiles-add', function (event) {
    event.preventDefault()
    $(this).parents('.ui-extrafiles').uiExtraFiles('add')
  })

module.exports = ExtraFiles
