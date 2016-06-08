/*
 * Unilend File Attach
 * Enables extended UI for attaching/removing files to a form
 */

// @TODO integrate Dictionary
// @TODO may need AJAX functionality

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')

// Dictionary
var Dictionary = require('Dictionary')
var FILEATTACH_LANG = require('../../../lang/FileAttach.lang.json')
var __ = new Dictionary(FILEATTACH_LANG)

// Private functions
function getFileSizeUnits (fileSizeInBytes) {
  if (fileSizeInBytes < 1024) {
    return 'B'
  } else if (fileSizeInBytes >= 1024 && fileSizeInBytes < 1048576) {
    return 'KB'
  } else if (fileSizeInBytes >= 1048576 && fileSizeInBytes < 1073741824) {
    return 'MB'
  } else if (fileSizeInBytes >= 1073741824) {
    return 'GB'
  }
}

function getFileSizeWithUnits (fileSizeInBytes) {
  var units = getFileSizeUnits(fileSizeInBytes)
  var fileSize = fileSizeInBytes

  switch (units) {
    case 'B':
      return fileSize + ' ' + units

    case 'KB':
      fileSize = fileSize / 1024
      return Math.floor(fileSize) + ' ' + units

    case 'MB':
      fileSize = fileSize / 1048576
      return fileSize.toFixed(1) + ' ' + units

    case 'GB':
      fileSize = fileSize / 1073741824
      return fileSize.toFixed(2) + ' ' + units
  }
}

// @class FileAttach
var FileAttach = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0) return

  // Settings
  // -- Defaults
  self.settings = $.extend({
    // Properties
    files: [],
    maxFiles: 0,
    maxSize: (1024 * 1024 * 8), // 8 MB
    fileTypes: 'pdf jpg jpeg png doc docx',
    inputName: 'fileattach',
    emptyFileLabel: __.__('', 'emptyFileLabel'),

    // Events
    onadd: function () {},
    onremove: function () {}
  },
  // -- Options (via element attributes)
  ElementAttrsObject(elem, {
    files: 'data-fileattach-files',
    maxFiles: 'data-fileattach-maxfiles',
    maxSize: 'data-fileattach-maxsize',
    fileTypes: 'data-fileattach-filetypes',
    inputName: 'data-fileattach-inputname',
    emptyFileLabel: 'data-fileattach-emptyfilelabel'
  }),
  // -- Options (via JS method call)
  options)

  // Parse JSON if self.settings.files set via string
  if (typeof self.settings.files === 'string' && /^[\{\[]/.test(self.settings.files)) {
    self.settings.files = JSON.parse(self.settings.files)
  }

  // UI
  self.$elem.addClass('ui-fileattach')
  if (Utility.isEmpty(self.$elem.attr('id'))) self.$elem.attr('id', Utility.randomString())
  // -- Add class to denote if single or multi file field (helps with styling)
  if (self.settings.maxFiles === 1) {
    self.$elem.addClass('ui-fileattach-single')
  } else {
    self.$elem.addClass('ui-fileattach-multi')
  }

  // Populate with any files passed through
  if (self.settings.files) {
    self.populate(self.settings.files)
  }

  // @debug
  // console.log('new FileAttach', self)

  // Init
  if (self.getFiles().length === 0) {
    self.add(true)
  }
  self.$elem[0].FileAttach = self
}

/*
 * Shared between all instances
 */
// Add a file item to the list
FileAttach.prototype.add = function (inhibitPrompt) {
  var self = this
  var $files = self.getFiles()
  var $emptyItems = self.$elem.find('.ui-fileattach-item').not('[data-fileattach-item-type]')
  var fileId = $files.length

  // See if any are empty and select that instead
  if ($emptyItems.length > 0) {
    if (!inhibitPrompt || typeof inhibitPrompt === 'undefined') $emptyItems.first().click()
    return
  }

  // Prevent more files being added
  if (self.settings.maxFiles > 0 && $files.length >= self.settings.maxFiles) return

  // Append a new file input
  var $file = $(Templating.replace(self.templates.fileItem, [{
    fileName: self.templates.emptyFile,
    inputName: self.settings.inputName,
    fileId: (self.settings.maxFiles !== 1 ? '[' + fileId + ']' : '')
  }, {
    attachFile: self.templates.attachFile,
    removeFile: self.templates.removeFile,
    emptyFileLabel: self.settings.emptyFileLabel
  }, __]))
  $file.appendTo(self.$elem)

  // @debug
  // console.log('add fn', inhibitPrompt, $file.find('[name]').first().attr('name'))

  if (!inhibitPrompt || typeof inhibitPrompt === 'undefined') $file.click()
}

// File info from name
FileAttach.prototype.getFileInfo = function (file) {
  var self = this
  var $file = $(file)
  var fileInfo = {
    name: undefined,
    type: undefined,
    size: 0
  }

  // Param given is {String} file name and doesn't match selector
  if (typeof file === 'string' && $file.length === 0) {
    fileInfo.name = file

  // Get the file name from the element
  } else {
    fileInfo.name = $file.val()
  }

  // No filename
  if (typeof fileInfo.name === 'undefined') return fileInfo

  // Set the name and type
  fileInfo.name = fileInfo.name.split('\\').pop()
  fileInfo.type = fileInfo.name.split('.').pop().toLowerCase()

  // HTML5 Files support
  if ($file.length > 0 && $file[0].hasOwnProperty('files')) {
    if (typeof $file[0].files !== 'undefined' && $file[0].files.length > 0) {
      // if (typeof $file[0].files[0].type !== 'undefined') fileType = $file[0].files[0].type.split('/').pop()
      if (typeof $file[0].files[0].size !== 'undefined') fileInfo.size = $file[0].files[0].size
    }
  }

  return fileInfo
}

// Attach a file
FileAttach.prototype.attach = function (fileElem) {
  var self = this
  var $file = $(fileElem)

  // Get the file name
  var fileInfo = self.getFileInfo(fileElem)

  // console.log('FileAttach.attach', fileName, fileType, fileSize)

  // Reject file because of type
  if (self.settings.fileTypes !== '*' && !(new RegExp(' ' + fileInfo.type + ' ', 'i').test(' ' + self.settings.fileTypes + ' '))) {
    // Generate errorHTML output
    var errorHTML = Templating.replace(self.templates.errorMessage, [{
      error: __.__('File type <strong>{{ fileType }}</strong> not accepted', 'errorIncorrectFileTypeError'),
      message: __.__('Accepting only: <strong>{{ acceptedFileTypes }}</strong>', 'errorIncorrectFileTypeMessage')
    }, {
      fileType: fileInfo.type.toUpperCase(),
      acceptedFileTypes: self.settings.fileTypes.split(/[, ]+/).join(', ').toUpperCase()
    }, __])

    // Reset file values and output error
    $file.val('')
      .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type').addClass('ui-fileattach-errored')
      .find('.ui-fileattach-filename').html(errorHTML)
    return
  }

  // Reject file because of size
  if (self.settings.maxSize && fileInfo.size && fileInfo.size > self.settings.maxSize) {
    // Generate errorHTML output
    var errorHTML = Templating.replace(self.templates.errorMessage, [{
      error: __.__('File size exceeds maximum <strong>{{ acceptedFileSize }}</strong>', 'errorIncorrectFileSizeError'),
      message: __.__('', 'errorIncorrectFileSizeMessage')
    }, {
      fileSize: getFileSizeWithUnits(fileInfo.size),
      acceptedFileSize: getFileSizeWithUnits(self.settings.maxSize)
    }, __])

    // Reset file values and output error
    $file.val('')
      .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type').addClass('ui-fileattach-errored')
      .find('.ui-fileattach-filename').html(errorHTML)
    return
  }

  // Attach the file
  $file.parents('.ui-fileattach-item').removeClass('ui-fileattach-errored').attr({
    title: fileInfo.name,
    'data-fileattach-item-type': fileInfo.type
  }).find('.ui-fileattach-filename').text(fileInfo.name)

  // @debug
  // console.log('attach fn')

  // If can add another...
  if (self.getFiles().length < self.settings.maxFiles) self.add(true)
}

// Populate the element using an array of fileInfo objects
// This will overwrite any other elements within the element
// @method populate
// @params {Array} fileInfos An {Array} containing {Object}s which specify each file's `name`, `type`, `url` and `size`
// @returns {Void}
FileAttach.prototype.populate = function (fileInfos, appendFiles) {
  var self = this

  if (fileInfos instanceof Array && fileInfos.length > 0) {
    // Overwrite element's contents
    if (!appendFiles) {
      self.$elem.html('')
    }

    // Generate HTML for each file
    var $files = $()
    $.each(fileInfos, function (i, fileInfo) {
      if (typeof fileInfo === 'object') {
        var $file = $(Templating.replace(self.templates.attachedFileItem, [{
          fileName: fileInfo.name || '',
          fileType: fileInfo.type || '',
          fileSize: fileInfo.size || '',
          fileUrl: fileInfo.url || '',
          inputName: self.settings.inputName,
          fileId: '[' + i + ']'
        }, {
          attachFile: self.templates.attachFile,
          removeFile: self.templates.removeFile,
          emptyFileLabel: self.settings.emptyFileLabel
        }, __]))
        $files = $files.add($file)
      }
    })

    // @debug
    // console.log('FileAttach.populate', $files)

    // Append the files
    self.$elem.append($files)

    // Can support more files?
    if (self.settings.maxFiles === 0 || self.getFiles().length < self.settings.maxFiles) {
      self.add(true)
    }

    // @trigger elem `FileAttach:populated` [FileAttach, fileInfos]
    self.$elem.trigger('FileAttach:populated', [self, fileInfos, $files])
  }
}

// Remove a file
// @method remove
// @param {Int} fileIndex The zero-index number of the file element to remove from the element
// @returns {Void}
FileAttach.prototype.remove = function (fileIndex) {
  var self = this
  if (typeof fileIndex === 'undefined') return

  // Get the file and label elements
  if (self.getFiles().length > 1) {
    self.getFile(fileIndex).parents('label').remove()
  } else {
    self.getFiles().val('')
      .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type')
      .find('.ui-fileattach-filename').html(Templating.replace(self.templates.emptyFile, [{
        // fileName: self.settings.emptyFileLabel,
        emptyFileLabel: self.settings.emptyFileLabel
      }, __]))
  }

  // If can add another...
  if (self.settings.maxFiles === 0 || self.getFiles().length < self.settings.maxFiles) self.add(true)

  // @trigger elem `FileAttach:removed` [FileAttach, fileIndex]
  self.$elem.trigger('FileAttach:removed', [self, fileIndex])
}

// Clear all files within the element
// @method clear
// @returns {Void}
FileAttach.prototype.clear = function () {
  var self = this

  // Clear the list of files
  self.getFiles().each(function (i, file) {
    self.remove(i)
  })

  // @trigger elem `FileAttach:removed` [FileAttach]
  self.$elem.trigger('FileAttached:cleared', [self])
}

// Get file elements
FileAttach.prototype.getFiles = function () {
  var self = this
  return self.$elem.find('.ui-fileattach-item input[type="file"]')
}

// Get single file element
FileAttach.prototype.getFile = function (fileIndex) {
  var self = this
  return self.getFiles().eq(fileIndex)
}

FileAttach.prototype.templates = {
  // The generic file item
  fileItem: '<label class="ui-fileattach-item"><span class="ui-fileattach-filename">{{ fileName }}</span><input type="file" name="{{ inputName }}{{ fileId }}" value=""/>{{ attachFile }}{{ removeFile }}</label>',

  // A file item which has already been attached (i.e. located on the server)
  attachedFileItem: '<label class="ui-fileattach-item" data-fileattach-item-type="{{ fileType }}"><span class="ui-fileattach-filename">{{ fileName }}</span><input type="file" name="{{ inputName }}{{ fileId }}" value="{{ fileUrl }}"/>{{ attachFile }}{{ removeFile }}</label>',

  // Attach File Button
  attachFile: '<span class="ui-fileattach-add-btn"><span class="label">{{ attachFileLabel }}</span></span>',

  // Remove File Button
  removeFile: '<a href="javascript:;" class="ui-fileattach-remove-btn"><span class="label">{{ removeFileLabel }}</span></a>',

  // Empty file label
  emptyFile: '{{ emptyFileLabel }}',

  // Error message
  errorMessage: '<div class="ui-fileattach-error"><div class="ui-fileattach-error-title">{{ error }}</div><div class="ui-fileattach-error-message">{{ message }}</div></div>'
}

// Events
$(document)
  // -- Click add button
  .on(Utility.clickEvent, '.ui-fileattach-add-btn', function (event) {
    event.preventDefault()
    $(this).parents('.ui-fileattach').uiFileAttach('add')
  })

  // -- Click remove button
  .on(Utility.clickEvent, '.ui-fileattach-remove-btn', function (event) {
    event.preventDefault()
    event.stopPropagation()

    // @debug
    // console.log('click remove btn')

    var $fa = $(this).parents('.ui-fileattach')
    var fa = $fa[0].FileAttach
    var $file = $(this).parents('.ui-fileattach-item').find('input[type="file"]')
    var fileIndex = fa.getFiles().index($file)
    fa.remove(fileIndex)
  })

  // -- When the file input has changed
  .on('change', '.ui-fileattach input[type="file"]', function (event) {
    if ($(this).val()) $(this).parents('.ui-fileattach').uiFileAttach('attach', this)
  })

  // -- Document ready: auto-apply functionality to elements with [data-fileattach] attribute
  .on('ready', function () {
    $('[data-fileattach]').uiFileAttach()
  })

$.fn.uiFileAttach = function (op) {
  // Fire a command to the FileAttach object, e.g. $('[data-fileattach]').uiFileAttach('add', {..})
  if (typeof op === 'string' && /^add|attach|remove|clear|populate$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.FileAttach && typeof elem.FileAttach[op] === 'function') {
        elem.FileAttach[op].apply(elem.FileAttach, args)
      }
    })

  // Set up a new FileAttach instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.FileAttach) {
        new FileAttach(elem, op)
      }
    })
  }
}

module.exports = FileAttach
