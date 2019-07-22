var $ = require('jquery')
var __ = require('__')

var CollectionForm = function (options) {
    var self = this

    self.options = $.extend({
        autoCreate: false,
        initForm: true,
        initProjectFee: false,
        feeCollectionHolderSelector: null
    }, options)

    self.add = function ($collectionHolder, $addButton) {
        var prototype = $collectionHolder.data('prototype')
        var prototypeName = $collectionHolder.data('prototype-name')
        var index = $collectionHolder.data('index')
        var $newForm = $(prototype.replace(new RegExp(prototypeName, 'g'), index))

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
            var $feeCollectionHolder = $collectionHolder.find(self.options.feeCollectionHolderSelector)
            if ($feeCollectionHolder.length > 0) {
                $feeCollectionHolder.uiInitCollectionHolder()
            }
        }
    }

    self.initLendingRate = function ($newForm) {
        var $lendingRateInputs = $newForm.find("[id$='_rate_margin'], [id$='_rate_indexType'], [id$='_rate_floor']")
        if ($lendingRateInputs.length === 3) {
            $newForm.uiInitLendingRate()
        }

        return self
    }

    self.initDataPicker = function ($newForm) {
        var $dataPickerInputs = $newForm.find('.ui-has-datepicker, [data-ui-datepicker]')
        if ($dataPickerInputs.length > 0) {
            $dataPickerInputs.uiPikaday()
        }

        return self
    }

    self.initRemoveButton = function ($newForm) {
        var $removeFormButton = $newForm.find('[data-action="remove"]')
        $removeFormButton.on('click', function () {
            if (confirm(__.__('Voulez-vous vraiment supprimer cet élément ?', 'delete-confirmation'))) {
                $newForm.remove()
            }
        })

        return self
    }

    self.initProjectFees = function ($newForm) {
        if (false === self.options.initProjectFee) {
            return self
        }
        var $feeTypeSelector = $newForm.find('[data-fee-type]')

        $feeTypeSelector.on('change', function () {
            var selectedType = $(this).children('option:selected').val()
            if (selectedType === '2') {
                $newForm.find('input[type=checkbox]').prop('checked', true)
            } else {
                $newForm.find('input[type=checkbox]').prop('checked', false)
            }
        })

        var $feeRateInput = $newForm.find("input[id$='_fee_rate']")

        $feeRateInput.on('focusout', function () {
            $(this).parent().find('.rate-amount').remove()
            if (false === $newForm.find('input[type=checkbox]').prop('checked') && $feeRateInput.val()) {
                var $referenceAmountInputs = $('[data-fee-reference-amount]')
                var referenceAmount = 0
                $referenceAmountInputs.each(function () {
                    var amount = parseFloat($(this).val().replace(' ', '').replace(',', '.'))
                    if ($.isNumeric(amount)) {
                        referenceAmount += amount
                    }
                })

                var rate = $feeRateInput.val().replace(',', '.')
                var rateAmount = rate * referenceAmount / 100
                if (rateAmount > 0) {
                    $feeRateInput.after('<small class="help-text rate-amount" style="display: block">soit ' + __.formatNumber(rateAmount) + ' €</small>')
                }
            }
        })

        return self
    }
}

$.fn.uiInitCollectionHolder = function (options) {
    var self = this
    var collectionForm = new CollectionForm(options)
    var $addButton = $(
        '<a href="javascript:" class="btn-default btn-shape-sq-md margin-10-t">' +
        '    <span class="icon fa-plus-u16 c-t2"></span>' +
        '</a>'
    )

    self
        .append($addButton)
        .data('index', self.find(':input').length)

    $addButton.on('click', function () {
        var $newForm = collectionForm.add(self, $addButton)
        collectionForm.init($newForm)
        collectionForm.iniFeesHolder(self)
    })

    if (true === collectionForm.options.autoCreate && self.children('div').length === 0) {
        collectionForm.add(self, $addButton)
    }
}

$.fn.uiInitCollectionForm = function (options) {
    var self = this
    var collectionForm = new CollectionForm(options)

    collectionForm.init(self)
}
