/**
 * # Autolend UI behaviours
 *
 * There's a lot of logic in this which is quite confusing.
 *
 * It might pay to refactor all this to not rely on all the data being stored in the DOM
 * but via some kind of JS object, of which the DOM is then modified to display from.
 *
 * It's very confusing and cumbersome to manage each individual element's state rather
 * than having a "single source of truth" for the data.
 *
 * -- Matt (2018-03-09)
 */

// No need to run any of the following code if the autolend table is not within the HTML
if ($('#form-user-autolend').length === 0) {
    return
}

var Utility = require('Utility')
var Dictionary = require('Dictionary')
var __ = new Dictionary(window.UTILITY_LANG)

var $doc = $(document)
var $colData = $('.col-data')
var $colInfo = $('.col-info')
var $infoIntro = $('.info-intro')
var $infoEvaluation = $('.info-evaluation')

/**
 * A snapshot of the cell's current data state.
 *
 * @typedef {Object} CellData
 * @param {number} enable
 * @param {number} cellIndex
 * @param {number} currentRate
 * @param {number} min
 * @param {number} max
 * @param {number} step
 * @param {number} avgRateUnilend
 * @param {string} avgRateUnilendFormated
 * @param {string} rating
 * @param {number} convertedRating
 * @param {string} periodsAsText
 */

/**
 * Get a cell.
 *
 * Will check if it is the number of a specific cell, or will get the closest element that is a data cell.
 *
 * @param {string|number|HTMLElement|jQuery} input
 * @return {jQuery}
 */
function getCell (input) {
    if (typeof input === 'string' && /^\d+$/.test(input) || typeof input === 'number') {
        return getCellFromIndex(input)
    } else {
        return $(input).closest('.cell-data[data-autolendtable-cell]').first()
    }
}

/**
 * Get a cell by its index number.
 *
 * @param {number} cellIndex
 * @returns {jQuery}
 */
function getCellFromIndex (cellIndex) {
    return $colData.find('.cell-data[data-autolendtable-cell="' + parseInt(cellIndex, 10) + '"]')
}

/**
 * Get the cell data for a specific cell.
 *
 * @param {number} cellIndex
 * @returns {CellData}
 */
function getCellInfo(cellIndex) {
    var $inputRate = getInputRate(cellIndex)

    return {
        enable: parseFloat($('#' + cellIndex + '-param-advanced-is-active').val()),
        cellIndex: parseFloat(cellIndex),
        currentRate: parseFloat($inputRate.val()),
        min: parseFloat($inputRate.attr('min')),
        max: parseFloat($inputRate.attr('max')),
        step: parseFloat($inputRate.attr('step')),
        avgRateUnilend: parseFloat($('#' + cellIndex + '-param-advanced-unilend-rate').attr('data-value')),
        avgRateUnilendFormated: $('#' + cellIndex + '-param-advanced-unilend-rate').attr('value'),
        rating: $('#' + cellIndex + '-param-advanced-evaluation').val(),
        convertedRating: parseFloat($('#' + cellIndex + '-param-advanced-evaluation-converted').val()),
        periodsAsText: $('#' + cellIndex + '-param-advanced-period-as-text').val()
    }
}

/**
 * Round and clamp the cell's interest number to its min/max values.
 *
 * @param {CellData} cellData
 * @param amount
 * @returns {number}
 */
function adjustInterestRate(cellData, amount) {
    var newInterest = (parseInt(cellData.currentRate * 10, 10) + parseFloat(amount) * 10) / 10

    if (newInterest >= cellData.max) {
        newInterest = cellData.max
    } else if (newInterest <= cellData.min) {
        newInterest = cellData.min
    }

    return newInterest
}

/**
 * Change the cell's colour depending on its new interest rate.
 *
 * @param {CellData} cellData
 * @param {number} newInterest
 */
function changeCellColor(cellData, newInterest) {
    var checkInterest = parseFloat(newInterest)
    var $cell = getCellFromIndex(cellData.cellIndex)

    if (newInterest < cellData.min || checkInterest > cellData.max) {
        $cell
            .removeClass('ui-autolend-average-exceeds ui-autolend-average-within')
            .addClass('ui-autolend-out-of-range')
    } else if (newInterest <= cellData.avgRateUnilend) {
        $cell
            .removeClass('ui-autolend-average-exceeds ui-autolend-out-of-range')
            .addClass('ui-autolend-average-within')
    } else {
        $cell
            .removeClass('ui-autolend-average-within ui-autolend-out-of-range')
            .addClass('ui-autolend-average-exceeds')
    }
}

/**
 * Update the info column with styles to show if the selected cell is equal, lower, higher
 * or outside allowed interest bounds.
 *
 * @param {CellData} cellData
 */
function changeBalance(cellData) {
    if (cellData.currentRate === cellData.avgRateUnilend) {
        balanceEqual()
    } else if (cellData.currentRate < cellData.min || cellData.currentRate > cellData.max) {
        unilendRateOutOfRange()
    } else if (cellData.currentRate > cellData.avgRateUnilend) {
        unilendRateLower()
    } else {
        unilendRateHigher()
    }
}

function balanceEqual() {
    removeUnilendHigherItems()
    removeUnilendLowerItems()
    removeOutOfRangeItems()

    $infoEvaluation.addClass('ui-autolend-average-equal')
    $('.info-scale.info-scale-in-range').show()
    $('.info-scale.info-scale-out-of-range').hide()
    $('#title-equal-rates').show()
    $('#info-description-equal-rates').show()
}

function unilendRateHigher() {
    removeEqualItems()
    removeUnilendLowerItems()
    removeOutOfRangeItems()

    $infoEvaluation.addClass('ui-autolend-average-within')
    $('.info-scale.info-scale-in-range').show()
    $('.info-scale.info-scale-out-of-range').hide()
    $('#title-unilend-rate-higher').show()
    $('#info-description-unilend-rate-higher').show()
}

function unilendRateLower() {
    removeEqualItems()
    removeUnilendHigherItems()
    removeOutOfRangeItems()

    $infoEvaluation.addClass('ui-autolend-average-exceeds')
    $('.info-scale.info-scale-in-range').show()
    $('.info-scale.info-scale-out-of-range').hide()
    $('#title-unilend-rate-lower').show()
    $('#info-description-unilend-rate-lower').show()
}

function unilendRateOutOfRange() {
    removeEqualItems()
    removeUnilendLowerItems()
    removeUnilendHigherItems()

    $infoEvaluation.addClass('ui-autolend-out-of-range')
    $('.info-scale.info-scale-in-range').hide()
    $('.info-scale.info-scale-out-of-range').show()
    $('#title-unilend-rate-out-of-range').show()
    $('#info-description-unilend-rate-out-of-range').show()
}

function removeEqualItems(){
    $('#title-equal-rates').hide()
    $('#info-description-equal-rates').hide()
    $infoEvaluation.removeClass('ui-autolend-average-equal')
}

function removeUnilendHigherItems(){
    $infoEvaluation.removeClass('ui-autolend-average-within')
    $('#title-unilend-rate-higher').hide()
    $('#info-description-unilend-rate-higher').hide()
}

function removeUnilendLowerItems(){
    $infoEvaluation.removeClass('ui-autolend-average-exceeds')
    $('#title-unilend-rate-lower').hide()
    $('#info-description-unilend-rate-lower').hide()
}

function removeOutOfRangeItems(){
    $infoEvaluation.removeClass('ui-autolend-out-of-range')
    $('#title-unilend-rate-out-of-range').hide()
    $('#info-description-unilend-rate-out-of-range').hide()
}

/**
 * Update the info column with information about the selected cell.
 *
 * @param {CellData} cellData
 */
function updateInfoWithCellData (cellData) {
    var $cell = getCellFromIndex(cellData.cellIndex)

    // Update the info only if the index does not match the current selected cell
    if (parseFloat($colInfo.attr('data-autolendtable-cell')) !== cellData.cellIndex) {
        $colInfo.attr('data-autolendtable-cell', cellData.cellIndex)
        $('#scale-unilend-rate').text(cellData.avgRateUnilendFormated)
        $('#scale-min-rate').text(__.formatNumber(cellData.min))
        $('#scale-max-rate').text(__.formatNumber(cellData.max))
        $('.info-description-evaluation').text(cellData.convertedRating)
        $('.info-description-periods-as-text').text(cellData.periodsAsText)
    }

    // Always update currentRate
    $('#scale-user-rate').text(__.formatNumber(cellData.currentRate))

    // Update the checkbox within the info column (only if this cell is currently selected)
    if (cellData.enable === 1) {
        $colInfo.addClass('ui-autolend-cell-enabled')
        $('.col-info[data-autolendtable-cell="' + cellData.cellIndex + '"] [data-autolend-cell-disable-switch]').attr('checked', 'checked').prop('checked', true)
    } else {
        $colInfo.removeClass('ui-autolend-cell-enabled')
        $('.col-info[data-autolendtable-cell="' + cellData.cellIndex + '"] [data-autolend-cell-disable-switch]').removeAttr('checked').prop('checked', false)
    }
}

/**
 * Get the advanced interest input element.
 *
 * @param {string|number} cellIndex
 * @returns {jQuery|HTMLElement}
 */
function getInputRate(cellIndex) {
    return $('#' + cellIndex + '-param-advanced-interest')
}

/**
 * Select the cell so it is visible within the info column.
 *
 * @param {string|number|HTMLElement|jQuery} $input
 */
function selectCell($input) {
    var $cell = getCell($input)
    var cellIndex = $cell.attr('data-autolendtable-cell')

    showBalance(cellIndex)

    // Already selected
    if ($cell.is('.active')) return

    // Deselect other cells
    $colData.find('.cell-data.active')
        .removeClass('active')

    // Make this cell look selected/active
    $cell.addClass('active')
}

/**
 * Deselect the cell.
 *
 * @param {string|number|HTMLElement|jQuery} $input
 */
function deselectCell ($input) {
    var $cell = getCell($input)
    var cellIndex = $cell.attr('data-autolendtable-cell')

    // Already deselected
    if (!$cell.is('.active')) return

    // Make this cell look deselected
    $cell.removeClass('active')

    // Update the col-info if it is selected
    $('.col-info[data-autolendtable-cell="' + cellIndex + '"]')
        .removeAttr('data-autolendtable-cell')
        .find('.info-evaluation')
            .removeClass('ui-autolend-cell-enabled')
}

/**
 * Enable the cell.
 *
 * @param {string|number|HTMLElement|jQuery} $inputStatus
 */
function enableCell ($inputStatus) {
    var $cell = getCell($inputStatus)
    var cellIndex = $cell.attr('data-autolendtable-cell')

    // Set status to 1
    $cell.find('input[name*="[is-active]"]').val(1)

    // If cell is enabled then it should always be selected and visible in the info column
    selectCell($cell)

    // Update cell UI
    $cell
        .addClass('ui-autolend-cell-enabled')
        .find('[data-autolend-cell-disable-switch]')
            .attr('checked', 'checked')
            .prop('checked', true)
}

/**
 * Disable the cell.
 *
 * @param {string|number|HTMLElement|jQuery} $inputStatus
 */
function disableCell ($inputStatus) {
    var $cell = getCell($inputStatus)
    var cellIndex = $cell.attr('data-autolendtable-cell')

    // Set status to 0
    $cell.find('input[name*="[is-active]"]').val(0)

    // Update cell UI
    $cell
        .removeClass('ui-autolend-cell-enabled')
        .find('[data-autolend-cell-disable-switch]')
            .removeAttr('checked')
            .prop('checked', false)

    // Update the evaluation (only if this cell is currently selected)
    $('.col-info[data-autolendtable-cell="' + cellIndex + '"]')
        .find('.info-evaluation')
            .removeClass('ui-autolend-cell-enabled')
}

/**
 * Update the info column with information about a specific cell.
 *
 * @param {number} cellIndex
 */
function showBalance (cellIndex) {
    var cellData = getCellInfo(cellIndex)

    $infoIntro.hide()
    $infoEvaluation.addClass('ui-autolend-cell-enabled').show()

    updateInfoWithCellData(cellData)
    changeBalance(cellData)
}

/**
 * Get the autolend table element from a target input element.
 *
 * @param {HTMLElement|jQuery} input
 * @returns {jQuery}
 */
function getAutolendTable (input) {
    return $(input).closest('.ui-autolendtable').first()
}

function emptyNotificationsDiv () {
    $('#form-info-notifications .message-success').hide()
    $('#form-info-notifications .message-error').text('').hide()
}

function directInputChangeCell (event) {
    event.preventDefault()

    var cellData = getCellInfo(getCell(event.target).attr('data-autolendtable-cell'))
    var $inputRate = getInputRate(cellData.cellIndex)

    changeCellColor(cellData, cellData.currentRate)
    updateInfoWithCellData(cellData)
    changeBalance(cellData)
}

function eventDecreaseCell (event) {
    event.preventDefault()

    var cellData = getCellInfo(getCell(event.target).attr('data-autolendtable-cell'))
    var $inputRate = getInputRate(cellData.cellIndex)
    var newInterest = adjustInterestRate(cellData, -cellData.step)

    // Do before updating cellData
    changeCellColor(cellData, newInterest)

    // Update the cellData
    $inputRate.val(newInterest)
    cellData.currentRate = newInterest
    updateInfoWithCellData(cellData)
    changeBalance(cellData)
}

function eventIncreaseCell (event) {
    event.preventDefault()

    var cellData = getCellInfo(getCell(event.target).attr('data-autolendtable-cell'))
    var $inputRate = getInputRate(cellData.cellIndex)
    var newInterest = adjustInterestRate(cellData, cellData.step)

    if (newInterest >= 10) newInterest = parseInt(newInterest, 10)

    // Do before updating cellData
    changeCellColor(cellData, newInterest)

    // Update cellData
    $inputRate.val(newInterest)
    cellData.currentRate = newInterest
    updateInfoWithCellData(cellData)
    changeBalance(cellData)
}

function setSettingsModeBasedOnButton () {
    // If the button interacted with was simple, set the autolend mode to simple
    if ($('#validate-simple-settings').is(':visible')) {
        $('#hidden-settings-mode-input').attr('value', 'simple')
    }

    // If the button interacted with was expert, set the autolend mode to expert
    if ($('#validate-expert-settings').is(':visible')) {
        $('#hidden-settings-mode-input').attr('value', 'expert')
    }
}

$doc
    // Toggle simple/expert settings
    .on('change', 'input#autolend-table-config-enable', function (event) {
        var $elem = $(this)
        var $interest = $('#div-interest-simple')
        var simpleValidateButton = $('#validate-simple-settings')

        // Disable the general interest rate
        if ($elem.is(':checked')) {
            $interest.hide()
            simpleValidateButton.hide()
        } else {
            $interest.show()
            simpleValidateButton.show()
        }
    })

    // Switch autolend on/off
    .on('change', 'input#form-autolend-enable', function (event) {
        var $elem = $(this)
        var $form = $('form#form-user-autolend');

        if ($elem.is(':checked')) {
            $('#autolend-config.collapse').collapse('show')
        } else {

            $('#autolend-config.collapse').collapse('hide')
            emptyNotificationsDiv()
            $.ajax({
                method: $form.attr('method'),
                url: $form.attr('action'),
                data: {
                    setting : 'autolend-off'
                },
                dataType: 'json'
            }).done(function (data) {

            })
        }
    })

    // Change cell rate by keys
    .on('keydown', '.cell .cell-input input[type="number"]', function (event) {
        // Press up arrow
        if (event.which === 38) {
            eventIncreaseCell(event)

        // Press down arrow
        } else if (event.which === 40) {
            eventDecreaseCell(event)
        }
    })
    .on('change', '.cell .cell-input input[type="number"]', function (event) {
        directInputChangeCell(event)
    })

    // Reduce cell rate
    .on(Utility.clickEvent, '.cell .cell-input .btn-cell-minus', eventDecreaseCell)

    // Increase cell rate
    .on(Utility.clickEvent, '.cell .cell-input .btn-cell-plus', eventIncreaseCell)

    // Show cell info
    .on('focus', '.cell .cell-input', function (event) {
        var $cell = getCell(event.target)
        selectCell($cell)
    })

    // Close confirmation- dialog
    .on(Utility.clickEvent, '.ui-dialog-cancel', function (event) {
        var $dialog = $(this).parents('.autolend-table-dialog').first()
        $dialog.fadeOut()
    })

    // Disable cell
    .on('change', '[data-autolend-cell-disable-switch]', function (event) {
        var $checkbox = $(this)

        // The parent could be a cell, or it could be the $colInfo element. Either way they both
        // share the same data-autolendtable-cell attribute which marks the currently selected cell
        var cellIndex = $checkbox.parents('[data-autolendtable-cell]').first().attr('data-autolendtable-cell')

        if ($checkbox.is(':checked')) {
            enableCell(cellIndex)
        } else {
            disableCell(cellIndex)
        }
    })

    // Enable cell
    .on(Utility.clickEvent, '.cell .btn-cell-enable', function (event) {
        var $cell = getCell(this)
        var cellIndex = $cell.attr('data-autolendtable-cell')
        enableCell($cell)
    })

    // Click a button to validate autolend values
    .on(Utility.clickEvent, 'button#validate-simple-settings, button#validate-expert-settings', function (event) {
        setSettingsModeBasedOnButton()
    })

    // Press enter on a button
    .on('keydown', 'button#validate-simple-settings, button#validate-expert-settings', function (event) {
        // Only match confirmation buttons (enter and space)
        if (event.which === 13 || event.which === 32) {
            setSettingsModeBasedOnButton()
        }
    })

    // Show confirmation dialog
    // We're capturing the submit event as users might press enter or submit the form otherwise by not clicking a button
    .on('submit', 'form#form-user-autolend', function (event) {
        var $elem = $(this)
        var form = event.target
        var $dialog = $('#autolend-table-dialog')

        emptyNotificationsDiv()

        // Always prevent the form from submitting as we will be processing via AJAX in the confirmed modal event
        event.preventDefault()

        if ($('#hidden-settings-mode-input').attr('value') == 'expert') {
            $('.cell-input[data-autolendtable-cell]').each(function () {
                var cellData = getCellInfo($(this).attr('data-autolendtable-cell'))
                if (cellData.currentRate < cellData.min || cellData.currentRate > cellData.max) {
                    $dialog = $('#autolend-out-of-range-table-dialog')
                }
            })
        }

        // Show dialog
        $dialog.uiModal('open')

        // Setup modal events
        $dialog.on('Modal:confirmed', function (event, elemModal) {
            form.submit()
            elemModal.close()
        })
        return false
    })

    // Apply unilend average rate to cell
    .on(Utility.clickEvent, '#apply-average-to-cell', function (event) {
        var $elem = $(this)
        var $cellRef = Utility.getElemIsOrHasParent(this, '[data-autolendtable-cell]')
        if ($cellRef.length > 0) {
            var cellIndex = ~~$cellRef.attr('data-autolendtable-cell')
            event.preventDefault()
            var cellData = getCellInfo(cellIndex)
            var $inputRate = getInputRate(cellData.cellIndex)
            $inputRate.val(cellData.avgRateUnilend)
            cellData.currentRate = cellData.avgRateUnilend;

            adjustInterestRate(cellData, cellData.avgRateUnilend)
            changeCellColor(cellData, cellData.avgRateUnilend)
            updateInfoWithCellData(cellData)
            changeBalance(cellData)
        }
    })
