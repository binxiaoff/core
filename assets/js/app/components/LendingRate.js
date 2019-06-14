var $ = require('jquery')
var $doc = $(document)

$.fn.uiInitLendingRate = function (fixedIndexTypeConstant) {
    var $rateMarginInput = this.find("[id$='_rate_margin']")
    var $rateIndexTypeInput = this.find("[id$='_rate_indexType']")
    var $rateFloorInput = this.find("[id$='_rate_floor']")
    handleRateDisplaying($rateMarginInput, $rateIndexTypeInput, $rateFloorInput, fixedIndexTypeConstant)

    $doc.on('change', $rateIndexTypeInput, function () {
        handleRateDisplaying($rateMarginInput, $rateIndexTypeInput, $rateFloorInput, fixedIndexTypeConstant)
    })
}

function handleRateDisplaying($rateMarginInput, $rateIndexTypeInput, $rateFloorInput, fixedIndexTypeConstant) {
    if ($rateIndexTypeInput.length > 0) {
        let selectedType = $rateIndexTypeInput.find('option:selected').val()
        let noFloor = fixedIndexTypeConstant === selectedType || '' === selectedType

        $rateFloorInput
            .parents('.form-field')
            .parent()
            .toggleClass('hidden', noFloor)

        if (selectedType) {
            $rateMarginInput.prop('disabled', false)
        } else {
            $rateMarginInput.prop('disabled', true)
        }
    } else {
        $rateMarginInput.prop('disabled', false)
    }
}
