var Utility = require('Utility')
var Dictionary = require('Dictionary')
var __ = new Dictionary(window.UTILITY_LANG)

$(document)
    // Toggle simple/expert settings
    .on('change', 'input#autolend-table-config-enable', function (event) {
        var $elem = $(this)
        console.log($elem)
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

    //switch autolend on/off
    .on('change', 'input#form-autolend-enable', function (event) {
        var $elem = $(this)

        if ($elem.is(':checked')) {
            $('#autolend-config.collapse').collapse('show')
        } else {
            var form = $('#form-user-autolend');
            $('#autolend-config.collapse').collapse('hide')
            emptyNotificationsDiv()
            $.ajax({
                method: form.attr('method'),
                url: form.attr('action'),
                data: {setting : 'autolend-off'},
                dataType: 'json'
            }).done(function (data) {

            });
        }
    })

    //reduce cell rate
    .on(Utility.clickEvent, '.cell .cell-input .btn-cell-minus', function (event) {
        var cellData = getCellInfo($(this).parent().attr('data-autolendtable-cell'))
        var $inputRate = getInputRate(cellData.cellIndex)
        var newInterest = adjustInterestRate(cellData, -cellData.step)

        $inputRate.val(newInterest)
        cellData.currentRate = newInterest
        changeCellColor(cellData, newInterest)
        addCellDataToBalance(cellData)
        changeBalance(cellData)
    })

    //increase cell rate
    .on(Utility.clickEvent, '.cell .cell-input .btn-cell-plus', function (event) {
        var cellData = getCellInfo($(this).parent().attr('data-autolendtable-cell'))
        var newInterest = adjustInterestRate(cellData, cellData.step)
        var $inputRate = getInputRate(cellData.cellIndex)

        $inputRate.val(newInterest)
        cellData.currentRate = newInterest
        changeCellColor(cellData, newInterest)
        addCellDataToBalance(cellData)
        changeBalance(cellData)
    })

    // Show cell info (side widget)
    .on(Utility.clickEvent, '.cell .cell-input', function (event) {
        var $cell = $(this).find('input')
        activateCell($cell)
        showBalance($cell.parent().attr('data-autolendtable-cell'))
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
        var cellIndex = ~~$cell.attr('data-autolendtable-cell')
        var $inputStatus = $('#' + cellIndex + '-param-advanced-is-active')
        rateActivatedSwitch($('#autolend-cell-disable-switch'), $inputStatus)
        showBalance(cellIndex)
    })

    // Show confirmation dialog
    .on(Utility.clickEvent, 'form#form-user-autolend button[type="submit"]', function (event) {
        var $elem = $(this)
        var button = $elem.attr('id')
        emptyNotificationsDiv()

        if (button == 'validate-simple-settings') {
            $('#hidden-settings-mode-input').attr('value', 'simple')
        }

        if (button == 'validate-expert-settings') {
            $('#hidden-settings-mode-input').attr('value', 'expert')
        }

        // Show dialog
        if ($elem.is('.ui-dialog-confirm')) {
            event.preventDefault()
            var form = $('#form-user-autolend');

            $.ajax({
                method: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json'
            }).done(function (data) {
                if (data.result == 'ko') {
                    displayErrorMessages(data.errors)
                } else {
                    updateSuccess(data.dateText)
                }
                $('#autolend-table-dialog').fadeOut()
            });

        } else {
            $('#autolend-table-dialog').fadeIn().focus()
            event.preventDefault()
            return false
        }
    })

    //apply unilend average rate to cell
    .on(Utility.clickEvent, '#apply-average-to-cell', function (event) {
        var $elem = $(this)
        var $cellRef = Utility.getElemIsOrHasParent(this, '[data-autolendtable-cell]')
        if ($cellRef.length > 0) {
            var cellIndex = ~~$cellRef.attr('data-autolendtable-cell')
            event.preventDefault()
            var cellData = getCellInfo(cellIndex)
            var $inputRate = getInputRate(cellData.cellIndex)
            $inputRate.val(cellData.avgRateUnilend)

            adjustInterestRate(cellData, cellData.avgRateUnilend)
            changeCellColor(cellData, cellData.avgRateUnilend)
            addCellDataToBalance(cellData, cellData.avgRateUnilend)
            changeBalance(cellData)

        }
    })


function displayErrorMessages(errors){
    var messageDiv = $('#form-info-notifications .message-error')
    for (var i = 0, len = errors.length; i < len; i++) {
        messageDiv.append('<p>' + errors[i] + '</p>')
    }
    messageDiv.show()
}

function updateSuccess(dateText){
    var dateDiv = $('#settings-last-updated')
    dateDiv.text(dateText)
    var messageDiv = $('#update-success-message')
    messageDiv.show()
}

function getCellInfo(cellIndex) {

    var $inputRate = getInputRate(cellIndex)

    return {
        avgRateUnilend : parseFloat($('#' + cellIndex + '-param-advanced-unilend-rate').attr('data-value')),
        avgRateUnilendFormated : $('#' + cellIndex + '-param-advanced-unilend-rate').attr('value'),
        cellIndex : cellIndex,
        currentRate : $inputRate.val(),
        enable : $('#' + cellIndex + '-param-advanced-is-active').val(),
        max : $inputRate.attr('max'),
        min : $inputRate.attr('min'),
        rating : $('#' + cellIndex + '-param-advanced-evaluation').val(),
        convertedRating : $('#' + cellIndex + '-param-advanced-evaluation-converted').val(),
        periodsAsText : $('#' + cellIndex + '-periods-as-text').val(),
        step : $inputRate.attr('step')
    }
}

function adjustInterestRate(cellData, amount) {
    var newInterest = parseInt(parseFloat(cellData.currentRate) * 10, 10) + parseInt(parseFloat(amount) * 10, 10)
    newInterest = newInterest / 10

    if (newInterest >= parseFloat(cellData.max)) {
        newInterest = cellData.max;
    }
    if (newInterest <= parseFloat(cellData.min)) {
        newInterest = cellData.min;
    }

    return newInterest
}

function changeCellColor(cellData, currentInterest){

    var $inputRate = getInputRate(cellData.cellIndex)
    var $selectedCell = $inputRate.parents('.cell-data').first()

    if (parseFloat(currentInterest, 1) <= parseFloat(cellData.avgRateUnilend, 1)) {
        $selectedCell.removeClass('ui-autolend-average-exceeds');
        $selectedCell.addClass('ui-autolend-average-within');
    } else {
        $selectedCell.removeClass('ui-autolend-average-within');
        $selectedCell.addClass('ui-autolend-average-exceeds');
    }
}

function balanceEqual($infoEvaluation) {
    removeUnilendHigherItems($infoEvaluation)
    removeUnilendLowerItems($infoEvaluation)

    $infoEvaluation.addClass('ui-autolend-average-equal')
    $('#title-equal-rates').show()
    $('#info-description-equal-rates').show()
}

function unilendRateHigher($infoEvaluation) {
    removeEqualItems($infoEvaluation)
    removeUnilendLowerItems($infoEvaluation)

    $infoEvaluation.addClass('ui-autolend-average-within')
    $('#title-unilend-rate-higher').show()
    $('#info-description-unilend-rate-higher').show()
}

function unilendRateLower($infoEvaluation) {
    removeEqualItems($infoEvaluation)
    removeUnilendHigherItems($infoEvaluation)

    $infoEvaluation.addClass('ui-autolend-average-exceeds')
    $('#title-unilend-rate-lower').show()
    $('#info-description-unilend-rate-lower').show()
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

function addCellDataToBalance(cellData){

    $('#scale-user-rate').text(__.formatNumber(cellData.currentRate))
    $('#scale-unilend-rate').text(cellData.avgRateUnilendFormated)
    $('.info-description-evaluation').text(cellData.convertedRating)
    $('.info-description-periods-as-text').text(cellData.periodsAsText)
    $('.col-info').attr('data-autolendtable-cell', cellData.cellIndex)

    if (cellData.enable == 1) {
        $('#autolend-cell-disable-switch').attr('checked', 'checked')
    } else {
        $('#autolend-cell-disable-switch').removeAttr('checked')
    }
}

function changeBalance(cellData) {
    var $infoEvaluation = $('.info-evaluation');

    if (parseFloat(cellData.currentRate, 1) == parseFloat(cellData.avgRateUnilend, 1)) {
        balanceEqual($infoEvaluation)
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
        $checkbox.removeAttr('checked')
    } else {
        activateSetting($inputStatus)
        $checkbox.attr('checked', 'checked')
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


