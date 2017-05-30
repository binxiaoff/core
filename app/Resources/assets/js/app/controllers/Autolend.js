var Utility = require('Utility')
var Dictionary = require('Dictionary')
var __ = new Dictionary(window.UTILITY_LANG)

var $doc = $(document)

function getCellInfo(cellIndex) {
    var $inputRate = getInputRate(cellIndex)

    return {
        avgRateUnilend: parseFloat($('#' + cellIndex + '-param-advanced-unilend-rate').attr('data-value')),
        avgRateUnilendFormated: $('#' + cellIndex + '-param-advanced-unilend-rate').attr('value'),
        cellIndex: cellIndex,
        currentRate: $inputRate.val(),
        enable: $('#' + cellIndex + '-param-advanced-is-active').val(),
        max: $inputRate.attr('max'),
        min: $inputRate.attr('min'),
        rating: $('#' + cellIndex + '-param-advanced-evaluation').val(),
        convertedRating: $('#' + cellIndex + '-param-advanced-evaluation-converted').val(),
        periodsAsText: $('#' + cellIndex + '-param-advanced-period-as-text').val(),
        step: $inputRate.attr('step')
    }
}

function adjustInterestRate(cellData, amount) {
    var newInterest = parseInt(parseFloat(cellData.currentRate) * 10, 10) + parseInt(parseFloat(amount) * 10, 10)
    newInterest = newInterest / 10

    if (newInterest >= parseFloat(cellData.max)) {
        newInterest = cellData.max
    }
    if (newInterest <= parseFloat(cellData.min)) {
        newInterest = cellData.min
    }

    return newInterest
}

function changeCellColor(cellData, currentInterest){
    var $inputRate = getInputRate(cellData.cellIndex)
    var $selectedCell = $inputRate.parents('.cell-data').first()

    if (parseFloat(currentInterest, 1) < parseFloat(cellData.min, 1) || parseFloat(currentInterest, 1) > parseFloat(cellData.max, 1)) {
        $selectedCell.removeClass('ui-autolend-average-exceeds ui-autolend-average-within')
        $selectedCell.addClass('ui-autolend-out-of-range')
    } else if (parseFloat(currentInterest, 1) <= parseFloat(cellData.avgRateUnilend, 1)) {
        $selectedCell.removeClass('ui-autolend-average-exceeds ui-autolend-out-of-range')
        $selectedCell.addClass('ui-autolend-average-within')
    } else {
        $selectedCell.removeClass('ui-autolend-average-within ui-autolend-out-of-range')
        $selectedCell.addClass('ui-autolend-average-exceeds')
    }
}

function balanceEqual($infoEvaluation) {
    removeUnilendHigherItems($infoEvaluation)
    removeUnilendLowerItems($infoEvaluation)
    removeOutOfRangeItems($infoEvaluation)

    $infoEvaluation.addClass('ui-autolend-average-equal')
    $('.info-scale.info-scale-in-range').show()
    $('.info-scale.info-scale-out-of-range').hide()
    $('#title-equal-rates').show()
    $('#info-description-equal-rates').show()
}

function unilendRateHigher($infoEvaluation) {
    removeEqualItems($infoEvaluation)
    removeUnilendLowerItems($infoEvaluation)
    removeOutOfRangeItems($infoEvaluation)

    $infoEvaluation.addClass('ui-autolend-average-within')
    $('.info-scale.info-scale-in-range').show()
    $('.info-scale.info-scale-out-of-range').hide()
    $('#title-unilend-rate-higher').show()
    $('#info-description-unilend-rate-higher').show()
}

function unilendRateLower($infoEvaluation) {
    removeEqualItems($infoEvaluation)
    removeUnilendHigherItems($infoEvaluation)
    removeOutOfRangeItems($infoEvaluation)

    $infoEvaluation.addClass('ui-autolend-average-exceeds')
    $('.info-scale.info-scale-in-range').show()
    $('.info-scale.info-scale-out-of-range').hide()
    $('#title-unilend-rate-lower').show()
    $('#info-description-unilend-rate-lower').show()
}

function unilendRateOutOfRange($infoEvaluation) {
    removeEqualItems($infoEvaluation)
    removeUnilendLowerItems($infoEvaluation)
    removeUnilendHigherItems($infoEvaluation)

    $infoEvaluation.addClass('ui-autolend-out-of-range')
    $('.info-scale.info-scale-in-range').hide()
    $('.info-scale.info-scale-out-of-range').show()
    $('#title-unilend-rate-out-of-range').show()
    $('#info-description-unilend-rate-out-of-range').show()
}

function removeEqualItems($infoEvaluation){
    $('#title-equal-rates').hide()
    $('#info-description-equal-rates').hide()
    $infoEvaluation.removeClass('ui-autolend-average-equal')
}

function removeUnilendHigherItems($infoEvaluation){
    $infoEvaluation.removeClass('ui-autolend-average-within')
    $('#title-unilend-rate-higher').hide()
    $('#info-description-unilend-rate-higher').hide()
}

function removeUnilendLowerItems($infoEvaluation){
    $infoEvaluation.removeClass('ui-autolend-average-exceeds')
    $('#title-unilend-rate-lower').hide()
    $('#info-description-unilend-rate-lower').hide()
}

function removeOutOfRangeItems($infoEvaluation){
    $infoEvaluation.removeClass('ui-autolend-out-of-range')
    $('#title-unilend-rate-out-of-range').hide()
    $('#info-description-unilend-rate-out-of-range').hide()
}

function addCellDataToBalance(cellData){
    $('#scale-user-rate').text(__.formatNumber(cellData.currentRate))
    $('#scale-unilend-rate').text(cellData.avgRateUnilendFormated)
    $('#scale-min-rate').text(__.formatNumber(cellData.min))
    $('#scale-max-rate').text(__.formatNumber(cellData.max))
    $('.info-description-evaluation').text(cellData.convertedRating)
    $('.info-description-periods-as-text').text(cellData.periodsAsText)
    $('.col-info').attr('data-autolendtable-cell', cellData.cellIndex)

    if (cellData.enable == 1) {
        $('#autolend-cell-disable-switch').attr('checked', 'checked').prop('checked', true)
    } else {
        $('#autolend-cell-disable-switch').removeAttr('checked').prop('checked', false)
    }
}

function changeBalance(cellData) {
    var $infoEvaluation = $('.info-evaluation')

    if (parseFloat(cellData.currentRate, 1) == parseFloat(cellData.avgRateUnilend, 1)) {
        balanceEqual($infoEvaluation)
    } else if (parseFloat(cellData.currentRate, 1) < parseFloat(cellData.min, 1) || parseFloat(cellData.currentRate, 1) > parseFloat(cellData.max, 1)) {
        unilendRateOutOfRange($infoEvaluation)
    } else if (parseFloat(cellData.currentRate, 1) > parseFloat(cellData.avgRateUnilend, 1)) {
        unilendRateLower($infoEvaluation)
    } else {
        unilendRateHigher($infoEvaluation)
    }
}

function getInputRate(cellIndex) {
    return $('#' + cellIndex + '-param-advanced-interest')
}

function rateActivatedSwitch($checkbox, $inputStatus) {
    if ($inputStatus.attr('value') == 1) {
        deactivateSetting($inputStatus)
        $checkbox.removeAttr('checked').prop('checked', false)
    } else {
        activateSetting($inputStatus)
        $checkbox.attr('checked', 'checked').prop('checked', true)
    }
}

function activateSetting($inputStatus) {
    $inputStatus.val(1)
    $inputStatus.parents('.cell-data').first().addClass('ui-autolend-cell-enabled')
    $('.info-evaluation').addClass('ui-autolend-cell-enabled')
    activateCell($inputStatus)
}

function deactivateSetting($inputStatus) {
    $inputStatus.val(0)
    $inputStatus.parents('.cell-data').first().removeClass('ui-autolend-cell-enabled')
    $inputStatus.parents('.cell-data').first().removeClass('active')
    $('.info-evaluation').removeClass('ui-autolend-cell-enabled')
}

function showBalance(cellIndex) {
    $('.info-intro').hide()
    $('.info-evaluation').addClass('ui-autolend-cell-enabled').show()

    var cellData = getCellInfo(cellIndex)
    addCellDataToBalance(cellData)
    changeBalance(cellData)
}

function activateCell($input) {
    $('.col-data').children().filter('.active').removeClass('active')
    $input.parents('.cell-data').first().addClass('active')
}

function getCellFromInput($input){
    return $input.parents('.cell').first()
}

function getCellFromIndex(cellIndex){
    return $('div.cell-input[data-autolendtable-cell=' + cellIndex + ']')
}

function getAutolendTable($cell){
    return $cell.parents('.ui-autolendtable')
}

function emptyNotificationsDiv(){
    $('#form-info-notifications .message-success').hide()
    $('#form-info-notifications .message-error').text('').hide()
}

function directInputChangeCell (event) {
    event.preventDefault()

    var cellData = getCellInfo($(event.target).parents('[data-autolendtable-cell]').attr('data-autolendtable-cell'))
    var $inputRate = getInputRate(cellData.cellIndex)

    changeCellColor(cellData, cellData.currentRate)
    addCellDataToBalance(cellData)
    changeBalance(cellData)
}

function eventDecreaseCell (event) {
    event.preventDefault()

    var cellData = getCellInfo($(event.target).parents('[data-autolendtable-cell]').attr('data-autolendtable-cell'))
    var $inputRate = getInputRate(cellData.cellIndex)
    var newInterest = adjustInterestRate(cellData, -cellData.step)

    $inputRate.val(newInterest)
    cellData.currentRate = newInterest
    changeCellColor(cellData, newInterest)
    addCellDataToBalance(cellData)
    changeBalance(cellData)
}

function eventIncreaseCell (event) {
    event.preventDefault()

    var cellData = getCellInfo($(event.target).parents('[data-autolendtable-cell]').attr('data-autolendtable-cell'))
    var $inputRate = getInputRate(cellData.cellIndex)
    var newInterest = adjustInterestRate(cellData, cellData.step)

    if (newInterest >= 10) newInterest = parseFloat(newInterest).toFixed(0)

    $inputRate.val(newInterest)
    cellData.currentRate = newInterest
    changeCellColor(cellData, newInterest)
    addCellDataToBalance(cellData)
    changeBalance(cellData)
}

function setSettingsModeBasedOnButton ($button) {
    // If the button interacted with was simple, set the autolend mode to simple
    if ($button.is('#validate-simple-settings')) {
        $('#hidden-settings-mode-input').attr('value', 'simple')
    }

    // If the button interacted with was expert, set the autolend mode to expert
    if ($button.is('#validate-expert-settings')) {
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

    // Show cell info (side widget)
    .on(Utility.clickEvent, '.cell .cell-input', function (event) {
        var $cell = $(this).parent()
        var $input = $(this).find('input[type=number]')
        var cellIndex = $cell.data('autolendtable-cell')
        activateCell($input)
        showBalance(cellIndex)
    })

    // Close confirmation- dialog
    .on(Utility.clickEvent, '.ui-dialog-cancel', function (event) {
        var $dialog = $(this).parents('.autolend-table-dialog').first()
        $dialog.fadeOut()
    })

    //disable cell
    .on(Utility.clickEvent, '#autolend-cell-disable-switch', function (event) {
        var $checkbox = $(this)
        if ($checkbox.length > 0) {
            var cellIndex = $checkbox.parents().find('.col-info').attr('data-autolendtable-cell')
            var $inputStatus = $('#' + cellIndex + '-param-advanced-is-active')
            rateActivatedSwitch($checkbox, $inputStatus)
        }
    })

    // Enable cell
    .on(Utility.clickEvent, '.cell .btn-cell-enable', function (event) {
        var $cell = $(this).parents('.cell-data').first()
        var cellIndex = $cell.attr('data-autolendtable-cell')
        var $inputStatus = $('#' + cellIndex + '-param-advanced-is-active')
        rateActivatedSwitch($('#autolend-cell-disable-switch'), $inputStatus)
        showBalance(cellIndex)
    })

    // Click a button to validate autolend values
    .on(Utility.clickEvent, 'button#validate-simple-settings, button#validate-expert-settings', function (event) {
        setSettingsModeBasedOnButton($(this))
    })

    // Press enter on a button
    .on('keydown', 'button#validate-simple-settings, button#validate-expert-settings', function (event) {
        // Only match confirmation buttons (enter and space)
        if (event.which === 13 || event.which === 32) {
            setSettingsModeBasedOnButton($(this))
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

        $('.cell-input[data-autolendtable-cell]').each(function() {
            var cellData = getCellInfo($(this).attr('data-autolendtable-cell'))
            if (parseFloat(cellData.currentRate, 1) < parseFloat(cellData.min, 1) || parseFloat(cellData.currentRate, 1) > parseFloat(cellData.max, 1)) {
                $dialog = $('#autolend-out-of-range-table-dialog')
            }
        })

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
            addCellDataToBalance(cellData, cellData.avgRateUnilend)
            changeBalance(cellData)

        }
    })
