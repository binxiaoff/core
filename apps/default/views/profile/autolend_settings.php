<?php if (false === $this->bFirstTimeActivation) : ?>
    <p><?= str_replace('[#DATE#]', $this->dates->formatDateMysqltoFrTxtMonth($this->sValidationDate), $this->lng['autobid']['settings-page-date-last-update']) ?></p>
<?php endif; ?>


<?php if ($this->bIsNovice) : ?>

    <?php if (empty($this->aErrors)) : ?>
        <div class="row text-center">
            <button class="btn" type="button" id="settings_modifications"><?= ($this->bFirstTimeActivation) ? $this->lng['autobid']['settings-button-define-parameters'] : $this->lng['autobid']['settings-button-modify-parameters'] ?></button>
        </div>
    <?php else :?>
        <div class="row" id="errors-autobid-param-form-simple" class="errors-autobid-param-form-simple">
            <?php if (isset($this->aErrors['taux-min']) && isset($this->aErrors['amount'])) : ?>
                <p><?= $this->lng['autobid']['error-message-simple-settings-both-wrong'] ?></p>
            <?php elseif (isset($this->aErrors['taux-min'])) : ?>
                <p><?= $this->lng['autobid']['error-message-simple-setting-rate-wrong'] ?></p>
            <?php elseif (isset($this->aErrors['amount'])) : ?>
                <p><?= str_replace('[#MIN_AMOUNT#]', $this->iMinimumBidAmount, $this->lng['autobid']['error-message-simple-setting-amount-wrong']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="autobid-param-simple autobid-block">
        <div id="settings_instructions" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>">
            <p><?= $this->lng['autobid']['settings-simple-instructions'] ?></p>
        </div>
        <p id="error_message_amount" style="display: none;" class="errors-autobid-param-form-simple"><?= str_replace('[#MIN_AMOUNT#]', $this->iMinimumBidAmount, $this->lng['autobid']['error-message-simple-setting-amount-wrong']) ?></p>

        <div class="autobid-param-form-simple">
            <form action="<?= $this->lurl ?>/profile/autolend" method="post" enctype="multipart/form-data">
                <div class="row">
                    <label for=""><?= $this->lng['autobid']['settings-simple-label-amount'] ?>
                        <span class="label-note"><?= $this->lng['autobid']['settings-simple-example-amount'] ?></span>
                    </label>
                    <input type="text" name="autobid-param-simple-amount" id="autobid-param-simple-amount"
                           value="<?= (isset($this->aSettingsSubmitted)) ? $this->aSettingsSubmitted['simple-amount'] : '' ?>"
                           onclick="value=''"
                           class="field required<?= (isset($this->aErrors['amount'])) ? ' LV_invalid_field' : '' ?>"
                           data-validators="Presence&amp;Numericality {minimum: <?= $this->iMinimumBidAmount ?>}"
                           onkeyup="noDecimale(this.value,this.id);"
                           <?= (empty($this->aErrors)) ? 'disabled="disabled"' : '' ?>/>
                </div>
                <div class="row">
                    <label for=""><?= $this->lng['autobid']['settings-simple-label-rate'] ?>
                        <span class="label-note"><?= $this->lng['autobid']['settings-simple-example-rate'] ?></span>
                    </label>
                    <input type="text" name="autobid-param-simple-taux-min-field" id="autobid-param-simple-taux-min-field"
                           class="field required"
                           value="<?= (isset($this->aSettingsSubmitted)) ? $this->ficelle->formatNumber($this->aSettingsSubmitted['simple-taux-min'], 1) : '' ?> %"
                           disabled="disabled"/>
                    <div id="select-autobid-taux" style="display:none;">
                        <select name="autobid-param-simple-taux-min" id="autobid-param-simple-taux-min" class="custom-select field-small required" >
                            <option value="0"><?= $this->lng['autobid']['settings-select-rate'] ?></option>
                            <?php foreach (range($this->iBidMaximumRate, $this->iBidMinimumRate, -$this->fAutoBidStep) as $fRate) : ?>
                                <option value="<?= $fRate ?>" <?= (round($fRate,1) == round($this->aSettingsSubmitted['simple-taux-min'],1)) ? 'selected' : '' ?> >
                                    <?= $this->ficelle->formatNumber($fRate, 1) ?>%
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <a href="#" class="link-more" style="display:none;"><?= $this->lng['autobid']['settings-link-to-expert-mode'] ?></a>
                </div>
                <div class="row text-center" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>" id="validate_settings">
                    <button class="btn" type="submit" name="send-form-autobid-param-simple">
                        <?= $this->lng['autobid']['settings-button-validate-settings'] ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php elseif (false === $this->bIsNovice) : ?>
<?php endif; ?>

<script type="text/javascript">
    $('#settings_modifications').click(function () {
        $('#settings_instructions').show();
        $('#validate_settings').show();
        $('.link-more').show();
        $('#select-autobid-taux').show();
        $('#settings_modifications').hide();
        $('#autobid-param-simple-taux-min-field').hide();
        $('#autobid-param-simple-amount').prop('disabled', false);
        $('#autobid-param-simple-taux-min').prop('disabled', false);
    });

    $('#autobid-param-simple-amount').change(function() {
        var amount = $("#autobid-param-simple-amount").val().replace(',','.');
        amount = amount.replace(' ','');

        var val_amount = true;
        if(isNaN(amount) == true){
            val_amount = false
        }
        else if(amount > 10000 || amount < 20){
            val_amount = false
        }

        if(val_amount == false) {
            $(this).addClass('LV_invalid_field');
            $(this).removeClass('LV_valid_field');
            $('#error_message_amount').show();
        } else {
            $(this).addClass('LV_valid_field');
            $(this).removeClass('LV_invalid_field');
        }
    });

</script>
