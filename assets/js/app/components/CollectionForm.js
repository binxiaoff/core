let $ = require('jquery')
var __ = require('__')

let CollectionForm = function (options) {
    let self = this

    self.options = {
        autoCreate: false,
        initForm: true,
        initProjectFee: false,
        feeCollectionHolderSelector: null
    }

    self.options = {...self.options, ...options}

    self.add = function ($collectionHolder, $addButton) {
        let prototype = $collectionHolder.data('prototype')
        let prototypeName = $collectionHolder.data('prototype-name')
        let index = $collectionHolder.data('index')
        let $newForm = $(prototype.replace(new RegExp(prototypeName, 'g'), index))

        $collectionHolder.data('index', index + 1)
        $addButton.before($newForm)

        return $newForm
    }

    self.init = function ($newForm) {
        if (self.options.initForm) {
            self.initRemoveButton($newForm)
                .initProjectFees($newForm)
                .initDataPicker($newForm)
                .initLendingRate($newForm)
        }
    }

    self.iniFeesHolder = function ($collectionHolder) {
        if (self.options.feeCollectionHolderSelector) {
            let feeCollectionHolder = $collectionHolder.find(self.options.feeCollectionHolderSelector)
            if (feeCollectionHolder.length > 0) {
                feeCollectionHolder.uiInitCollectionHolder()
            }
        }
    }

    self.initLendingRate = function ($newForm) {
        let lendingRateInputs = $newForm.find("[id$='_rate_margin'], [id$='_rate_indexType'], [id$='_rate_floor']")
        if (lendingRateInputs.length === 3) {
            $newForm.uiInitLendingRate()
        }

        return self
    }

    self.initDataPicker = function ($newForm) {
        let dataPickerInputs = $newForm.find('.ui-has-datepicker, [data-ui-datepicker]')
        if (dataPickerInputs.length > 0) {
            dataPickerInputs.uiPikaday()
        }

        return self
    }

    self.initRemoveButton = function($newForm) {
        let $removeFormButton = $newForm.find(`[data-action='remove']`)
        $removeFormButton.on('click', function () {
            if (confirm(__.__('Voulez-vous vraiment supprimer cet élément ?', 'delete-confirmation'))) {
                $newForm.remove()
            }
        })

        return self
    }

    self.initProjectFees = function($newForm) {
        if (false === self.options.initProjectFee) {
            return self
        }
        let $feeTypeSelector = $newForm.find(`[data-fee-type]`)

        $feeTypeSelector.on('change', function () {
            let selectedType = $(this).children("option:selected").val()
            if (selectedType === '2') {
                $newForm.find('input[type=checkbox]').prop("checked", true)
            } else {
                $newForm.find('input[type=checkbox]').prop("checked", false)
            }
        })

        let $feeRateInput = $newForm.find("input[id$='_fee_rate']")

        $feeRateInput.on('focusout', function () {
            $(this).parent().find('.rate-amount').remove()
            if (false === $newForm.find('input[type=checkbox]').prop("checked") && $feeRateInput.val()) {
                let $referenceAmountInputs = $(`[data-fee-reference-amount]`)
                let referenceAmount = 0
                $referenceAmountInputs.each(function () {
                    let amount = parseFloat($(this).val().replace(' ', '').replace(',', '.'))
                    if ($.isNumeric(amount)) {
                        referenceAmount += amount
                    }
                })

                let rate = $feeRateInput.val().replace(',', '.')
                let rateAmount = rate * referenceAmount / 100
                if (rateAmount > 0) {
                    $feeRateInput.after('<small class="help-text rate-amount" style="display: block">soit ' + rateAmount.toFixed(2).replace('.', ',') + ' €</small>')
                }
            }
        })

        return self
    }
}

$.fn.uiInitCollectionHolder = function (options) {
    let self = this
    let collectionForm = new CollectionForm(options)
    let $addButton = $(
        '<a href="javascript:" class="btn-default btn-shape-sq-md margin-10-t">' +
        '    <span class="icon fa-plus-u16 c-t2"></span>' +
        '</a>'
    )

    self
        .append($addButton)
        .data('index', self.find(':input').length)

    $addButton.on('click', function () {
        let $newForm = collectionForm.add(self, $addButton)
        collectionForm.init($newForm)
        collectionForm.iniFeesHolder(self)
    })

    if (true === collectionForm.options.autoCreate && self.children('div').length === 0) {
        collectionForm.add(self, $addButton)
    }
}

$.fn.uiInitCollectionForm = function (options) {
    let self = this
    let collectionForm = new CollectionForm(options)

    collectionForm.init(self)
}
