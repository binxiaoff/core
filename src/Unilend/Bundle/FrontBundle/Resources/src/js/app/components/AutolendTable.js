//
// Autolend Table
//

// @todo lots of AJAX stuff
// @todo Probably lots of refactoring?
// @note I did some initial work on the CacheForm restore form state in case the table needs to support
//       restoring browser-saved data. For the most part it probably doesn't, so I stopped

var $ = require('jquery')
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')

var Dictionary = require('Dictionary')
var AUTOLEND_LANG = require('../../../lang/AutolendTable.lang.json')
var __ = new Dictionary(AUTOLEND_LANG)

// @class AutolendTable
var AutolendTable = function (elem, options) {
  var self = this
  self.$elem = $(elem)
  if (self.$elem.length === 0) return

  // Settings
  self.settings = $.extend({
    data: [],
    average: []
  }, ElementAttrsObject(elem, {
    'data': 'data-autolendtable-data',
    'average': 'data-autolendtable-average'
  }), options)

  // Convert to JSON if necessary
  self.settings.data = Utility.convertStringToJson(self.settings.data)
  self.settings.average = Utility.convertStringToJson(self.settings.average)

  // Remove HTML attributes
  self.$elem.removeAttr('data-autolendtable-data data-autolendtable-average')

  // Rendering and elements
  var $table = $(Templating.replace(self.templates.table, [{
    info: self.templates.infoIntro
  }, __]))
  self.$info = $table.find('.col-info').first()
  self.$data = $table.find('.col-data').first()
  self.$elem.html('').append($table)

  // UI
  self.$elem.addClass('ui-autolendtable')

  // Initialise
  self.$elem[0].AutolendTable = self
  self.renderTableCellData()
  return self
}

/*
 * Shared between all instances
 */
AutolendTable.prototype.templates = {
  // Main table structure
  table: '<div class="row">\
    <div class="col-table">\
      <div class="row row-header">\
        <div class="cell">&nbsp;</div>\
        <div class="cell cell-header">\
          <span class="rating rating-3"><span class="sr-only">{{ ratingHeader3 }}</span></span>\
        </div>\
        <div class="cell cell-header">\
          <span class="rating rating-3-5"><span class="sr-only">{{ ratingHeader35 }}</span></span>\
        </div>\
        <div class="cell cell-header">\
          <span class="rating rating-4"><span class="sr-only">{{ ratingHeader4 }}</span></span>\
        </div>\
        <div class="cell cell-header">\
          <span class="rating rating-4-5"><span class="sr-only">{{ ratingHeader45 }}</span></span>\
        </div>\
        <div class="cell cell-header">\
          <span class="rating rating-5"><span class="sr-only">{{ ratingHeader5 }}</span></span>\
        </div>\
      </div>\
      <div class="row">\
        <div class="col-header">\
          <div class="cell cell-header"><span>{{ periodHeader3To12 }}</span></div>\
          <div class="cell cell-header"><span>{{ periodHeader18To24 }}</span></div>\
          <div class="cell cell-header"><span>{{ periodHeader36 }}</span></div>\
          <div class="cell cell-header"><span>{{ periodHeader48To60 }}</span></div>\
          <div class="cell cell-header"><span>{{ periodHeader60Plus }}</span></div>\
        </div>\
        <div class="col-data">{{ cellDataLoading }}</div>\
      </div>\
    </div>\
    <div class="col-info">{{ info }}</div>\
  </div>',

  // Cell data template
  cellData: '<div class="cell cell-data {{ classAverage }} {{ classEnabled }}" data-autolendtable-cell="{{ cellIndex }}">\
    <div class="cell-period">{{ cellPeriod }}</div>\
    <div class="cell-rating"><span class="rating {{ classRating }}"><span class="sr-only">{{ cellRatingLabel }}</span></span></div>\
    <div class="cell-input">\
      <a href="javascript:;" tabindex="-1" class="btn-cell-minus"><span class="sr-only">{{ cellInputButtonMinusLabel }}</span></a>\
      <input type="number" name="data[{{ cellIndex }}][interest]" min="1" max="10" step="0.1" value="{{ cellInterest }}" />\
      <a href="javascript:;" tabindex="-1" class="btn-cell-plus"><span class="sr-only">{{ cellInputButtonPlusLabel }}</span></a>\
    </div>\
    <a href="javascript:;" class="btn-cell-enable"><span class="sr-only">{{ cellButtonEnableLabel }}</span></a>\
    <input type="hidden" name="data[{{ cellIndex }}][enable]" value="{{ cellEnable }}" />\
  </div>',

  // Info intro
  infoIntro: '<div class="info-intro">\
    <h4 class="info-title">{{ infoIntroTitle }}</h4>\
    <div class="info-icon"><span class="icon icon-huge fa-cogs"></span></div>\
    <div class="info-description">\
      <p>{{ infoIntroDescription }}</p>\
    </div>\
  </div>',

  // Info Evaluation
  infoEvaluation: '<div class="info-evaluation {{ classEvaluation }}">\
    <h4 class="info-title">{{ infoEvaluationTitle }}</h4>\
    <div class="info-scale">\
      <div class="info-scale-user">\
        <h5 class="label">{{ infoEvaluationInterestLabelUser }}</h5>\
        <div class="value">{{ cellInterest }}<sup>%</sup></div>\
      </div>\
      <div class="info-scale-unilend">\
        <h5 class="label">{{ infoEvaluationInterestLabelUnilend }}</h5>\
        <div class="value">{{ cellInterestAverage }}<sup>%</sup></div>\
      </div>\
      <div class="info-scale-balance">\
        <div class="info-scale-balance-board"></div>\
        <div class="info-scale-balance-pin"></div>\
      </div>\
    </div>\
    <div class="info-description">\
      <p>{{ infoEvaluationDescription }}</p>\
      <p><a href="javascript:;" class="btn-cell-reset">{{ cellButtonResetLabel }}</a></p>\
      <div class="info-cell-disable">\
        <label class="custom-input-switch custom-input-switch-sm btn-cell-disable-toggle"><input type="checkbox" id="autolend-cell-disable-switch" value="true" {{ switchCellDisableChecked }} /><span class="label"><span class="sr-only">{{ switchCellDisableLabel }}</span></span></label>\
        <label for="autolend-cell-disable-switch">{{ cellButtonDisableLabel }}</label>\
      </div>\
    </div>\
  </div>'
}

// Render the cell data
AutolendTable.prototype.renderTableCellData = function () {
  var self = this

  if (self.settings.data.length > 0) {
    var cellDataHtml = ''
    for (var i = 0; i < self.settings.data.length; i++) {
      var cellData = self.settings.data[i]

      // These parameters are processed in the template first
      var tplParams = {
        classEnabled: (cellData.enable ? 'ui-autolend-cell-enabled' : ''),
        classAverage: (self.settings.average[i] && self.settings.average[i].hasOwnProperty('interest') && cellData.interest > self.settings.average[i].interest
          ? 'ui-autolend-average-exceeds'
          : 'ui-autolend-average-within'),
        classRating: 'rating-' + (parseFloat(cellData.rating)+'').replace('.', '-')
      }

      // These are processed after as the ones before can reference their values
      var cellTplParams = {
        cellIndex: i,
        cellInterest: cellData.interest,
        cellPeriodMin: cellData.periodMin,
        cellPeriodMax: cellData.periodMax,
        cellRating: cellData.rating,
        cellEnable: cellData.enable,
        cellPeriod: self.getCellDurationLangString(cellData.periodMin, cellData.periodMax)
      }

      // Get the cell's HTML to render
      var cellHtml = Templating.replace(self.templates.cellData, [tplParams, cellTplParams, __])
      cellDataHtml += cellHtml
    }
    self.$data.html(cellDataHtml)

    // @trigger elem `AutolendTable:renderTableCellData:complete`
    self.$elem.trigger('AutolendTable:renderTableCellData:complete', [self])
  }
}

// Get the lang string to use to determine a cell's duration label
AutolendTable.prototype.getCellDurationLangString = function(cellPeriodMin, cellPeriodMax) {
  var output = __.__key('cellDurationMinToMax', "from {{ cellPeriodMin }} to {{ cellPeriodMax }} months")

  // Min is max
  if (cellPeriodMin === cellPeriodMax) {
    output = __.__key('cellDurationMinIsMax', "at {{ cellPeriodMin }} months")
  }

  // Min to infinity/indeterminate time
  if (cellPeriodMin > cellPeriodMax) {
    output = __.__key('cellDurationMinToInfinity', "for more than {{ cellPeriodMin }} months")
  }

  // Replace the values
  output = Templating.replace(output, {
    cellPeriodMin: cellPeriodMin,
    cellPeriodMax: cellPeriodMax
  })

  return output
}

AutolendTable.prototype.getCells = function () {
  var self = this
  return self.$data.find('.cell-data')
}

// Get a cell's data, average and HTML element
// @returns {Object} Special object which also has the cell's jQuery elem, data and average {Object}s
AutolendTable.prototype.getCell = function (cellIndex) {
  var self = this
  var cell = {
    $elem: self.getCells().eq(cellIndex),
    data: self.settings.data[cellIndex],
    average: self.settings.average[cellIndex]
  }
  return cell
}

/*
 * Data functions
 */
// Update a cell with an {Object} of new data
AutolendTable.prototype.updateCell = function (cellIndex, newData, activate) {
  var self = this
  var cell = self.getCell(cellIndex)
  var oldData = $.extend({}, cell.data)

  // @debug
  // console.log('AutolendTable.updateCell', cellIndex, newData, oldData)

  // Merge the updated newData into the cell data
  for (var i in newData) {
    cell.data[i] = newData[i]
  }

  // Ensure types
  if (typeof cell.data.interest !== 'number') cell.data.interest = parseFloat(cell.data.interest)
  if (cell.data.interest < 1) cell.data.interest = 1
  if (cell.data.interest > 10) cell.data.interest = 10

  // Update the cell view
  if (oldData.interest != cell.data.interest) {
    self.viewEvaluateCell(cellIndex)
    cell.$elem.find('input').first().val(cell.data.interest)
  }
  if (oldData.enable != cell.data.enable) {
    self.viewEnableCell(cellIndex, cell.data.enable)
  }

  // Update current info
  if (self.$info.is('[data-autolendtable-cell="' + cellIndex + '"]') || activate) {
    self.viewCellInfo(cellIndex)
  }
}

// Set the cell interest
AutolendTable.prototype.setCellInterest = function (cellIndex, amount) {
  var self = this

  // Set the value
  amount = parseFloat(amount).toFixed(1)

  // Update the cell
  self.updateCell(cellIndex, {
    interest: amount
  })
}

// Adjust a cell's interest value
AutolendTable.prototype.adjustCellInterest = function (cellIndex, amount) {
  var self = this
  var cell = self.getCell(cellIndex)

  // Adjust the value
  // @note Arrrgggh binary floating points!
  var newInterest = parseInt(parseFloat(cell.data.interest) * 10, 10) + parseInt(parseFloat(amount) * 10, 10)
  // 4.1 + 0.1
  // 41 + 1
  // 42 / 10
  // 4.2
  newInterest = newInterest / 10

  // @debug
  // console.log('adjustCellInterest', cellIndex, amount, newInterest)

  // Update the cell
  self.updateCell(cellIndex, {
    interest: newInterest
  })
}

// Reset a cell's interest value
AutolendTable.prototype.resetCellInterest = function (cellIndex) {
  var self = this
  var cell = self.getCell(cellIndex)

  // Update the cell
  self.updateCell(cellIndex, {
    interest: cell.average.interest
  })
}

// Set all cells to the average interest value
AutolendTable.prototype.setTableDataToAverageInterest = function () {
  var self = this

  for (var i = 0; i < self.settings.data.length; i++) {
    self.updateCell(i, {
      enable: true,
      interest: self.settings.average[i].interest
    }, false)
  }
}

/*
 * View functions
 */
// Enables/disables a cell
AutolendTable.prototype.viewEnableCell = function (cellIndex, enabled, activate) {
  var self = this
  var cell = self.getCell(cellIndex)

  // @debug
  // console.log('viewEnableCell', cellIndex, enabled)

  // Nothing given, default is to enable cell
  if (!enabled && typeof enabled !== 'undefined') {
    cell.$elem.removeClass('ui-autolend-cell-enabled').find('input[type="hidden"]').val(false)
    return
  }

  // @debug
  // console.log('viewEnableCell: enable', cell.$elem)

  // Default is to enable it
  cell.$elem.addClass('ui-autolend-cell-enabled').find('input[type="hidden"]').val(true)
  if (activate) self.viewCellInfo(cellIndex)

  // Select it to show the keyboard
  if ($('html').is('.has-touchevents')) {
    cell.$elem.find('input').first().select()
  }
}

// Evaluates a cell's interest rate compared to the Unilend averages
AutolendTable.prototype.viewEvaluateCell = function (cellIndex, cellValue) {
  var self = this
  var cell = self.getCell(cellIndex)

  // Evaluate cell value
  if (cell.data.interest) {
    if (cell.data.interest > cell.average.interest) {
      // Update cell class
      cell.$elem.removeClass('ui-autolend-average-within').addClass('ui-autolend-average-exceeds')

      // Update cell info evaluation
      // @todo
    } else {
      // Update cell class
      cell.$elem.removeClass('ui-autolend-average-exceeds').addClass('ui-autolend-average-within')

      // Update cell info evaluation
      // @todo
    }
  }
}

// Show information about a single cell
AutolendTable.prototype.viewCellInfo = function (cellIndex) {
  var self = this

  // Get the cell's info
  var cell = self.getCell(cellIndex)

  // Set unset any active cells
  self.getCells().filter('.active').not(cell.$elem).removeClass('active')

  // Mark the current cell as active
  cell.$elem.addClass('active')

  // -- Cell data and stuff
  var cellTplParams = {
    cellIndex: cellIndex,
    cellInterest: __.localizedNumber(cell.data.interest, 1),
    cellInterestAverage: __.localizedNumber(cell.average.interest, 1),
    cellPeriodMin: cell.data.periodMin,
    cellPeriodMax: cell.data.periodMax,
    cellRating: cell.data.rating,
    cellEnable: cell.data.enable,
    cellDuration: self.getCellDurationLangString(cell.data.periodMin, cell.data.periodMax)
  }

  // -- Extra template stuff
  var tplParams = {
    classRating: 'rating-' + (parseFloat(cell.data.rating)+'').replace('.', '-'),
    switchCellDisableChecked: (cell.data.enable ? 'checked="checked"' : '')
  }

  // Interest equal/within/exceeds
  // -- Equal
  if (cell.data.interest === cell.average.interest) {
    tplParams.classEvaluation = 'ui-autolend-average-equal'
    tplParams.infoEvaluationTitle = __.__key('infoEvaluationTitleEqual', "Matches Unilend")
    tplParams.infoEvaluationDescription = __.__key('infoEvaluationDescriptionEqual', "Your offer is equal to Unilend's average accepted interest rate")

  // -- Exceeds
  } else if (cell.data.interest > cell.average.interest) {
    tplParams.classEvaluation = 'ui-autolend-average-exceeds'
    tplParams.infoEvaluationTitle = __.__key('infoEvaluationTitleExceeds', "Your offer exceeds...")
    tplParams.infoEvaluationDescription = __.__key('infoEvaluationDescriptionExceeds', "Your offer exceeds Unilend's average accepted interest rate")

  // -- Within
  } else {
    tplParams.classEvaluation = 'ui-autolend-average-within'
    tplParams.infoEvaluationTitle = __.__key('infoEvaluationTitleWithin', "Your offer is competitive!")
    tplParams.infoEvaluationDescription = __.__key('infoEvaluationDescriptionWithin', "Your offer is within Unilend's average accepted interest rate")
  }

  // Disabled cell
  if (!cell.data.enable) {
    tplParams.infoEvaluationTitle = __.__key('infoEvaluationTitleDisabled', "Make an offer...")
    tplParams.infoEvaluationDescription = __.__key('infoEvaluationDescriptionDisabled', "Enable this option for a rating of {{ cellRating }} and a duration {{ cellDuration }} to configure its Autolend interest rate")

  // Enabled cell
  } else {
    tplParams.classEvaluation += ' ui-autolend-cell-enabled'
  }

  // Change the table's info panel
  // @todo Use some other framework! One that supports two-way binding or something else, e.g. React
  //       This just does a repaint and also nullifies any CSS animations too due to repaint
  self.$info.html(Templating.replace(self.templates.infoEvaluation, [tplParams, cellTplParams, __])).attr('data-autolendtable-cell', cellIndex)
}

/*
 * jQuery Plugin
 */
$.fn.uiAutolendTable = function (op) {
  // Fire a command to the AutolendTable object, e.g. $('[data-fileattach]').uiFileAttach('add', {..})
  if (typeof op === 'string' && /^(renderCellEvaluate)$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.hasOwnProperty('AutolendTable') && elem.AutolendTable && typeof elem.AutolendTable[op] === 'function') {
        elem.AutolendTable[op].apply(elem.AutolendTable, args)
      }
    })

  // Set up a new FileAttach instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.hasOwnProperty('AutolendTable')) {
        new AutolendTable(elem, op)
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
  // Initialise on read
  .on('ready', function () {
    $('[data-autolendtable-data]').uiAutolendTable()
  })

  /*
   * Generic UI events
   */
  .on(Utility.clickEvent, '.btn-cell-reset', function (event) {
    var $elem = $(this)
    var $cellRef = Utility.getElemIsOrHasParent(this, '[data-autolendtable-cell]')
    if ($cellRef.length > 0) {
      var cellIndex = ~~$cellRef.attr('data-autolendtable-cell')
      var $autolendTable = $cellRef.parents('.ui-autolendtable')
      var at = $autolendTable[0].AutolendTable
      event.preventDefault()
      at.resetCellInterest(cellIndex)
      return false
    }
  })

  .on(Utility.clickEvent, '.btn-cell-disable-toggle', function (event) {
    var $elem = $(this)
    var $cellRef = Utility.getElemIsOrHasParent(this, '[data-autolendtable-cell]')
    if ($cellRef.length > 0) {
      var cellIndex = ~~$cellRef.attr('data-autolendtable-cell')
      var $autolendTable = $cellRef.parents('.ui-autolendtable')
      var at = $autolendTable[0].AutolendTable
      var cell = at.getCell(cellIndex)
      event.preventDefault()

      // Update the cell details
      at.updateCell(cellIndex, {
        enable: !cell.data.enable
      }, false)

      // Update the switch
      if ($elem.is('.custom-input-switch')) {
        if (cell.data.enable) {
          $elem.find('input[type="checkbox"]').removeAttr('checked')
        } else {
          $elem.find('input[type="checkbox"]').attr('checked', 'checked')
        }
      }
      return false
    }
  })

  // -- Set all the cells to Unilend's average interest rate
  .on(Utility.clickEvent, '.btn-table-set-average', function (event) {
    var $elem = $(this)
    var $autolendTable = $($elem.attr('data-target') || $elem.attr('href') || '.ui-autolendtable')

    // Get the AutolendTable
    if ($autolendTable.length === 0) return
    $autolendTable = $autolendTable.first()
    var at = $autolendTable[0].AutolendTable

    // Enable all cells and set to average interest rate
    if ($elem.is('.custom-input-switch')) {
      // Only if the custom switch input is checked
      if ($elem.find('input[type="checkbox"]').is(':checked')) {
        at.setTableDataToAverageInterest()
      }
    } else {
      at.setTableDataToAverageInterest()
    }
  })

  /*
   * Cell events
   */

  // Enable cell
  .on(Utility.clickEvent, '.cell .btn-cell-enable', function (event) {
    var $cell = $(this).parents('.cell').first()
    var cellIndex = ~~$cell.attr('data-autolendtable-cell')
    var $autolendTable = $cell.parents('.ui-autolendtable').first()
    var at = $autolendTable[0].AutolendTable
    event.preventDefault()

    at.updateCell(cellIndex, {
      enable: true
    }, true)

    return false
  })

  // Show cell info
  .on('focus', '.cell .cell-input input', function (event) {
    var $input = $(this)
    var $cell = $input.parents('.cell').first()
    var cellIndex = ~~$cell.attr('data-autolendtable-cell')
    var $autolendTable = $input.parents('.ui-autolendtable').first()
    var at = $autolendTable[0].AutolendTable
    // $autolendTable.uiAutolendTable('renderCellInfo', ~~$cell.attr('data-autolendtable-cell'))
    // at.evaluateCell(cellIndex)
    at.viewCellInfo(cellIndex)
  })

  // Evaluate cell input
  // @note Not watching on event 'change' due to the plus/minus buttons invoking that
  .on('change', '.cell .cell-input input', function (event) {
    // @debug
    // console.log('change .cell .cell-input input', event.target)
    if (event.target === this) {
      var $input = $(this)
      var $cell = $input.parents('.cell').first()
      var cellIndex = ~~$cell.attr('data-autolendtable-cell')
      var $autolendTable = $input.parents('.ui-autolendtable').first()
      var at = $autolendTable[0].AutolendTable
      at.setCellInterest(cellIndex, $input.val())
    }
  })

  // Add 0.1 to the input value
  .on(Utility.clickEvent, '.cell .cell-input .btn-cell-plus', function (event) {
    var $input = $(this).parents('.cell-input').first().find('input').first()
    var $cell = $input.parents('.cell').first()
    var cellIndex = ~~$cell.attr('data-autolendtable-cell')
    var $autolendTable = $input.parents('.ui-autolendtable').first()
    var at = $autolendTable[0].AutolendTable
    event.preventDefault()
    at.adjustCellInterest(cellIndex, 0.1)

    // View the cell info
    if (!at.$info.is('[data-autolendtable-cell="' + cellIndex + '"]')) at.viewCellInfo(cellIndex)
    return false
  })

  // Minus 0.1 to the input value
  .on(Utility.clickEvent, '.cell .cell-input .btn-cell-minus', function (event) {
    var $input = $(this).parents('.cell-input').first().find('input').first()
    var $cell = $input.parents('.cell').first()
    var cellIndex = ~~$cell.attr('data-autolendtable-cell')
    var $autolendTable = $input.parents('.ui-autolendtable').first()
    var at = $autolendTable[0].AutolendTable
    event.preventDefault()
    at.adjustCellInterest(cellIndex, -0.1)

    // View the cell info
    if (!at.$info.is('[data-autolendtable-cell="' + cellIndex + '"]')) at.viewCellInfo(cellIndex)
    return false
  })

  // AJAX submit
  .on('submit', 'form#form-user-autolend', function (event) {
    // @TODO enable AJAX submission
    event.preventDefault()
    // console.log('AutolendTable form submit')
    return false
  })

  // Hook into CacheForm events
  // Because the AutolendTable is rendered after this event goes, we need to updated to the restored the data before it renders
  .on('CacheForm:restoreFormState:restored', 'form#form-user-autolend', function (event, params) {
    var $form = $(this)
    var $autolendTable = $form.find('.autolendtable').first()

    // AutolendTable hasn't initialised yet, so change the data in the [data-autolendtable-data] attribute
    if ($autolendTable.is('[data-autolendtable-data]')) {
      // Only process fields which represent autolend table cells, e.g. 'data[\d+][interest]'
      var autolendTableData = Utility.convertStringToJson($autolendTable.attr('data-autolendtable-data'))
      for (var i = 0; i < params.formData.length; i++) {
        if (/data\[\d+\]\[(interest|enable)\]/.test(params.formData[i].name)) {
          var cellIndex = ~~(params.formData[i].name.replace(/\D+/g, ''))

          // Interest
          if (/interest/.test(params.formData[i].name)) {
            autolendTableData[cellIndex].interest = params.formData[i].value

          // Enable
          } else if (/enable/.test(params.formData[i].name)) {
            autolendTableData[cellIndex].enable = Utility.convertToBoolean(params.formData[i].value)
          }
        }
      }

      // Recompile the data into the attribute
      $autolendTable.attr('data-autolendtable-data', JSON.stringify(autolendTableData))

      // @debug
      // console.log('CacheForm:restoreFormState:restored: before AutolendTable initialisation', autolendTableData)

    // AutolendTable has initialised, so let's quickly populate
    } else if ($autolendTable.is('.ui-autolendtable')) {
      var at = $autolendTable[0].AutolendTable

      // Only process fields which represent autolend table cells, e.g. 'data[\d+][interest]'
      for (var i = 0; i < params.formData.length; i++) {
        if (/data\[\d+\]\[(interest|enable)\]/.test(params.formData[i].name)) {
          var cellIndex = ~~(params.formData[i].name.replace(/\D+/g, ''))

          // Interest
          if (/interest/.test(params.formData[i].name)) {
            at.updateCell(cellIndex, {
              interest: params.formData[i].value
            })

          // Enable
          } else if (/enable/.test(params.formData[i].name)) {
            at.updateCell(cellIndex, {
              enable: Utility.convertToPrimitive(params.formData[i].value)
            })
          }
        }
      }

      // @debug
      // console.log('CacheForm:restoreFormState:restored: after AutolendTable initialisation')
    }
  })

  .on('CacheForm:saveFormState:beforeSave', 'form#form-user-autolend', function (event, formState) {
    // console.log('CacheForm:saveFormState:beforeSave Autolend', formState)
  })

module.exports = AutolendTable
