/*
 * Unilend File Attach
 * Enables extended UI for attaching/removing files to a form
 */

// @TODO integrate Dictionary
// @TODO may need AJAX functionality

var $ = require('jquery')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')

function randomString (stringLength) {
  var output = ''
  var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
  stringLength = stringLength || 8
  for (var i = 0; i < stringLength; i++) {
    output += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return output
}

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

var FileAttach = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0) return

  // Settings
  // -- Defaults
  self.settings = $.extend({
    // Properties
    maxFiles: 0,
    maxSize: (1024 * 1024 * 8),
    fileTypes: 'pdf jpg jpeg png doc docx',
    inputName: 'fileattach',

    // Template
    templates: {
      fileItem: '<label class="ui-fileattach-item"><span class="label">{{ fileName }}</span><input type="file" name="{{ inputName }}[{{ fileId }}]" value=""/><a href="javascript:;" class="ui-fileattach-remove-btn"><span class="sr-only"> Remove</span></a></label>',
      errorMessage: '<div class="ui-fileattach-error"><span class="c-error">{{ errorMessage }}</span> {{ message }}</div> <span class="ui-fileattach-add-btn">Select file...</span>'
    },

    // Labels
    lang: {
      emptyFile: '<span class="ui-fileattach-add-btn">Select file...</span>',
      errors: {
        incorrectFileType: {
          errorMessage: 'File type <strong>{{ fileType }}</strong> not accepted',
          message: 'Accepting only: <strong>{{ acceptedFileTypes }}</strong>'
        },
        incorrectFileSize: {
          errorMessage: 'File size exceeds maximum <strong>{{ acceptedFileSize }}</strong>',
          message: ''
        }
      }
    },

    // Events
    onadd: function () {},
    onremove: function () {}
  },
  // -- Options (via element attributes)
  ElementAttrsObject(elem, {
    maxFiles: 'data-fileattach-maxfiles',
    maxSize: 'data-fileattach-maxsize',
    fileTypes: 'data-fileattach-filetypes',
    inputName: 'data-fileattach-inputname'
  }),
  // -- Options (via JS method call)
  options)

  // Elements
  self.$elem.addClass('ui-fileattach')

  // Add a file item to the list
  self.add = function (inhibitPrompt) {
    var self = this
    var $files = self.getFiles()
    var $emptyItems = self.$elem.find('.ui-fileattach-item').not('[data-fileattach-item-type]')

    // See if any are empty and select that instead
    if ($emptyItems.length > 0) {
      if (!inhibitPrompt || typeof inhibitPrompt === 'undefined') $emptyItems.first().click()
      return
    }

    // Prevent more files being added
    if (self.settings.maxFiles > 0 && $files.length >= self.settings.maxFiles) return

    // Append a new file input
    var $file = $(Templating.replace(self.settings.templates.fileItem, {
      fileName: self.settings.lang.emptyFile,
      inputName: self.settings.inputName,
      fileId: randomString()
    }))
    $file.appendTo(self.$elem)

    // @debug
    // console.log('add fn', inhibitPrompt, $file.find('[name]').first().attr('name'))

    if (!inhibitPrompt || typeof inhibitPrompt === 'undefined') $file.click()
  }

  // Attach a file
  self.attach = function (fileElem) {
    var self = this
    var $file = $(fileElem)

    // Get the file name
    var fileName = $file.val().split('\\').pop()
    var fileType = $file.val().split('.').pop().toLowerCase()
    var fileSize = 0

    // HTML5 Files support
    if (typeof $file[0].files !== 'undefined' && $file[0].files.length > 0) {
      if (typeof $file[0].files[0].type !== 'undefined') fileType = $file[0].files[0].type.split('/').pop()
      if (typeof $file[0].files[0].size !== 'undefined') fileSize = $file[0].files[0].size
    }

    // Reject file because of type
    if (self.settings.fileTypes !== '*' && !(new RegExp(' ' + fileType + ' ', 'i').test(' ' + self.settings.fileTypes + ' '))) {
      $file.val('')
        .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type')
        .find('.label').html(Templating.replace(self.settings.templates.errorMessage, [self.settings.lang.errors.incorrectFileType, {
          fileType: fileType.toUpperCase(),
          acceptedFileTypes: self.settings.fileTypes.split(/[, ]+/).join(', ').toUpperCase()
        }]))
      return
    }

    // Reject file because of size
    if (self.settings.maxSize && fileSize && fileSize > self.settings.maxSize) {
      $file.val('')
        .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type')
        .find('.label').html(Templating.replace(self.settings.templates.errorMessage, [self.settings.lang.errors.incorrectFileSize, {
          fileSize: getFileSizeWithUnits(fileSize),
          acceptedFileSize: getFileSizeWithUnits(self.settings.maxSize)
        }]))
      return
    }

    // Attach the file
    $file.parents('.ui-fileattach-item').attr({
      title: fileName,
      'data-fileattach-item-type': fileType
    }).find('.label').text(fileName)

    // @debug
    // console.log('attach fn')

    // If can add another...
    if (self.getFiles().length < self.settings.maxFiles) self.add(true)
  }

  // Remove a file
  self.remove = function (fileIndex) {
    var self = this
    if (typeof fileIndex === 'undefined') return

    // Get the file and label elements
    if (self.getFiles().length > 1) {
      self.getFile(fileIndex).parents('label').remove()
    } else {
      self.getFiles().val('')
        .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type')
        .find('.label').html(self.settings.lang.emptyFile)
    }

    // @debug
    // console.log('remove fn')

    // If can add another...
    if (self.getFiles().length < self.settings.maxFiles) self.add(true)

    self.$elem.trigger('FileAttach:removed', [self, fileIndex])
  }

  // Clear all files
  self.clear = function () {
    var self = this

    // Clear the list of files
    self.getFiles().each(function (i, file) {
      $(file).parents('.ui-fileattach-item').remove()
    })

    // Add new file
    self.add(true)

    self.$elem.trigger('FileAttached:cleared', [self])
  }

  // Get file elements
  self.getFiles = function () {
    var self = this
    return self.$elem.find('.ui-fileattach-item input[type="file"]')
  }

  // Get single file element
  self.getFile = function (fileIndex) {
    var self = this
    return self.getFiles().eq(fileIndex)
  }

  // Init
  if (self.getFiles().length === 0) {
    self.add(true)
  }
  self.$elem[0].FileAttach = self
}

// Events
$(document)
  // -- Click add button
  .on('click', '.ui-fileattach-add-btn', function (event) {
    event.preventDefault()
    $(this).parents('.ui-fileattach').uiFileAttach('add')
  })

  // -- Click remove button
  .on('click', '.ui-fileattach-remove-btn', function (event) {
    event.preventDefault()
    event.stopPropagation()

    // @debug
    // console.log('click remove btn')

    var $fa = $(this).parents('.ui-fileattach')
    var fa = $fa[0].FileAttach
    var $file = $(this).parents('.ui-fileattach-item').find('input[type="file"]')
    var fileIndex = fa.getFiles().index($file)
    $fa.uiFileAttach('remove', fileIndex)
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
  if (typeof op === 'string' && /^add|attach|remove|clear$/.test(op)) {
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
