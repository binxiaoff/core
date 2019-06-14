let $ = require('jquery')

function handleRateDisplaying($rateMarginInput, $rateIndexTypeInput, $rateFloorInput) {
    if ($rateIndexTypeInput.length > 0) {
        let selectedType = $rateIndexTypeInput.find('option:selected')
        let noFloor = typeof selectedType.data('no-floor') !== 'undefined' || '' === selectedType.val()

        $rateFloorInput.toggleClass('hidden', noFloor)
        $("label[for='" + $rateFloorInput.attr('id') + "']").toggleClass('hidden', noFloor)

        if (selectedType.val()) {
            $rateMarginInput.prop('disabled', false)
        } else {
            $rateMarginInput.prop('disabled', true)
        }
    } else {
        $rateMarginInput.prop('disabled', false)
    }
}

$.fn.uiInitLendingRate = function () {
    let $rateMarginInput = this.find("[id$='_rate_margin']")
    let $rateIndexTypeInput = this.find("[id$='_rate_indexType']")
    let $rateFloorInput = this.find("[id$='_rate_floor']")
    handleRateDisplaying($rateMarginInput, $rateIndexTypeInput, $rateFloorInput)

    $rateIndexTypeInput.on('change', function () {
        handleRateDisplaying($rateMarginInput, $rateIndexTypeInput, $rateFloorInput)
    })
}
