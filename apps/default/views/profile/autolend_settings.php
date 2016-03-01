<?php if (false === $this->bFirstTimeActivation) : ?>
    <p><?= str_replace('[#DATE#]', $this->dates->formatDateMysqltoFrTxtMonth($this->sValidationDate), $this->lng['autobid']['settings-page-date-last-update']) ?></p>
<?php endif; ?>


<div id="autobid_modify_parameters">
    <div class="row text-center">
        <?php if ($this->bIsNovice) : ?>
        <button class="btn" type="button" id="settings_modifications_novice">
            <?= ($this->bFirstTimeActivation) ? $this->lng['autobid']['settings-button-define-parameters'] : $this->lng['autobid']['settings-button-modify-parameters'] ?>
        </button>
        <?php else: ?>
        <button class="btn" type="button" id="settings_modifications_expert">
            <?= ($this->bFirstTimeActivation) ? $this->lng['autobid']['settings-button-define-parameters'] : $this->lng['autobid']['settings-button-modify-parameters'] ?>
        </button>
        <?php endif; ?>
    </div>
</div>

<div id="autobid_settings_form_errors" >
    <div class="row errors-autobid" id="errors-autobid-param-form-simple">
        <p id="error-simple-settings-both-wrong" style="<?= (isset($this->aErrors['taux-min']) && isset($this->aErrors['amount']))? '': 'display:none;'?>">
            <?= $this->lng['autobid']['error-message-simple-settings-both-wrong'] ?>
        </p>
        <p id="error-simple-setting-rate-wrong" style="<?= (isset($this->aErrors['taux-min']))? '': 'display:none;'?>">
            <?= $this->lng['autobid']['error-message-simple-setting-rate-wrong'] ?>
        </p>
        <p id="error-amount-wrong" style="<?= (isset($this->aErrors['amount']))? '': 'display:none;'?>">
            <?= str_replace('[#MIN_AMOUNT#]', $this->iMinimumBidAmount, $this->lng['autobid']['error-message-simple-setting-amount-wrong']) ?>
        </p>
    </div>
</div>

<div class="autobid-block"> <!-- autobid-param-simple-->

    <div id="settings_instructions_novice" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>">
        <p><?= $this->lng['autobid']['settings-simple-instructions'] ?></p>
    </div>
    <div id="settings_instructions_expert" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>">
        <p><?= $this->lng['autobid']['settings-expert-instructions'] ?></p>
    </div>

    <div class="autobid-param-form-simple">
        <form action="<?= $this->lurl ?>/profile/autolend" method="post" enctype="multipart/form-data">
            <div class="row">
                <label for=""><?= $this->lng['autobid']['settings-label-amount'] ?>
                    <span class="label-note"><?= $this->lng['autobid']['settings-example-amount'] ?></span>
                </label>
                <input type="text" name="autobid-amount" id="autobid-amount"
                       value="<?= (isset($this->aSettingsSubmitted)) ? $this->aSettingsSubmitted['amount'] : '' ?>"
                       onclick="value=''"
                       class="field required<?= (isset($this->aErrors['amount'])) ? ' LV_invalid_field' : '' ?>"
                       data-validators="Presence&amp;Numericality {minimum: <?= $this->iMinimumBidAmount ?>}"
                       onkeyup="noDecimale(this.value,this.id);"
                       <?= (empty($this->aErrors)) ? 'disabled="disabled"' : '' ?>/>
            </div>
            <div id="rate-settings-novice" style="<?= ($this->bIsNovice) ? '' : 'display:none;' ?>">
                <div class="row">
                    <label for=""><?= $this->lng['autobid']['settings-simple-label-rate'] ?>
                        <span class="label-note"><?= $this->lng['autobid']['settings-simple-example-rate'] ?></span>
                    </label>
                    <input type="text" name="autobid-param-simple-taux-min-field" id="autobid-param-simple-taux-min-field"
                           class="field required"
                           value="<?= (false === empty($this->aSettingsSubmitted['simple-taux-min'])) ? $this->ficelle->formatNumber($this->aSettingsSubmitted['simple-taux-min'], 1) : '' ?> %"
                           disabled="disabled"/>
                    <div id="select-autobid-taux" style="display:none;">
                        <select name="autobid-param-simple-taux-min" id="autobid-param-simple-taux-min" class="custom-select field-small required" >
                            <option value="0"><?= $this->lng['autobid']['settings-select-rate'] ?></option>
                            <?php foreach (range(\bids::BID_RATE_MAX, \bids::BID_RATE_MIN, -$this->fAutoBidStep) as $fRate) : ?>
                                <option value="<?= $fRate ?>" <?= (round($fRate,1) == round($this->aSettingsSubmitted['simple-taux-min'],1)) ? 'selected' : '' ?> >
                                    <?= $this->ficelle->formatNumber($fRate, 1) ?>%
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <a href="#" class="link-more" style="display:none;"><?= $this->lng['autobid']['settings-link-to-expert-mode'] ?></a>
            </div>
            <div class="row text-center" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>" id="validate_settings_novice">
                <button class="btn" type="submit" name="send-form-autobid-param-simple">
                    <?= $this->lng['autobid']['settings-button-validate-settings'] ?>
                </button>
                <button class="btn" style="display:none;" id="cancel_modification_settings" onClick="window.location.reload()" >
                    <?= $this->lng['autobid']['cancel-setting-modification-button'] ?>
                </button>
            </div>
        </form>
    </div>

    <div id="expert-settings" style="<?= ($this->bIsNovice) ? 'display:none;' : '' ?>">

        <div class="apply-global-medium-rate" style="display: none;">
            <p><?= str_replace('[#GLOBAL-AVG-RATE#]', $this->fAverageRateUnilend, $this->lng['autobid']['unilend-global-rate']) ?></p>
            <p><? $this->lng['autobid']['apply-unilend-global-rate-instruction'] ?></p>
            <button class="btn btn-small grise1" type="button" id="global-rate-Unilend"><?= $this->lng['autobid']['apply-unilend-global-rate-button'] ?></button>
        </div>

        <div class="autobid-param-advanced autobid-param-advanced-locked autobid-block" id="autobid-block">
            <div class="table-container left">
                <table class="autobid-param-advanced-table">
                    <tr>
                        <th class="empty"></th>
                        <th scope="col" colspan="5" class="table-title"><?= $this->lng['autobid']['expert-settings-table-title-risk'] ?></th>
                    </tr>
                    <tr>
                        <th scope="col" class="table-title"><?= $this->lng['autobid']['expert-settings-table-title-period'] ?></th>
                        <th scope="col"><div class="autobid-stars stars-30"></div></th>
                        <th scope="col"><div class="autobid-stars stars-35"></div></th>
                        <th scope="col"><div class="autobid-stars stars-40"></div></th>
                        <th scope="col"><div class="autobid-stars stars-45"></div></th>
                        <th scope="col"><div class="autobid-stars stars-50"></div></th>
                    </tr>
                    <?php foreach ($this->aAutoBidSettings as $iPeriodId => $aPeriodSettings) : ?>
                    <tr>
                        <th scope="row"><?= str_replace('[#SEPARATOR#]', '<br />', $this->lng['autobid']['autobid-period-' . $iPeriodId]) ?></th>
                        <?php foreach ($aPeriodSettings as $aSetting) : ?>
                                <td class="<?= (\autobid::STATUS_INACTIVE == $aSetting['status']) ? 'param-off' : '' ?>
                                <?= ($aSetting['rate_min'] < $aSetting['AverageRateUnilend'] || empty($aSetting['AverageRateUnilend'])) ? '' : 'param-over' ?>">
                                    <div class="cell-inner">
                                        <div class="param-advanced-switch" style="display: none;">
                                            <input type="checkbox" class="param-advanced-switch-input" name="<?= $aSetting['id_autobid'] ?>-param-advanced-switch"
                                                   id="<?= $aSetting['id_autobid'] ?>-param-advanced-switch"
                                                   value="<?= (\autobid::STATUS_ACTIVE == $aSetting['status']) ? \autobid::STATUS_ACTIVE : \autobid::STATUS_INACTIVE ?>" />
                                        </div>
                                        <div class="param-advanced-bottom" >
                                            <div class="param-advanced-buttons" style="display: none;">
                                                <button class="param-advanced-button" value="1">+</button>
                                                <button class="param-advanced-button" value="0">-</button>
                                            </div>
                                            <input type="hidden" id="<?= $aSetting['id_autobid'] ?>-param-advanced-value" class="param-advanced-value"
                                                   value="<?= $aSetting['rate_min'] ?>">
                                            <input type="hidden" name="param-advanced-unilend-rate" id="param-advanced-unilend-rate"
                                                   value="<?= $aSetting['AverageRateUnilend'] ?>">
                                            <label class="param-advanced-label"><?= $this->ficelle->formatNumber($aSetting['rate_min'], 1) ?>%</label>
                                            <input type="hidden" id="<?= $aSetting['id_autobid'] ?>-param-advanced-period"
                                                   value="<?= $aSetting['id_autobid_period'] ?>">
                                            <input type="hidden" id="<?= $aSetting['id_autobid'] ?>-param-advanced-evaluation"
                                                   value="<?= $aSetting['evaluation'] ?>">
                                        </div>
                                    </div>
                                </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="empty"></td>
                        <td colspan="5" class="empty">
                            <div class="table-legend">
                                <span><span class="rate-legend legend-green"></span><?= $this->lng['autobid']['expert-settings-legend-inferior-rate'] ?></span>
                                <span><span class="rate-legend legend-gray"></span><?= $this->lng['autobid']['expert-settings-legend-deactivated'] ?></span>
                                <span><span class="rate-legend legend-red"></span><?= $this->lng['autobid']['expert-settings-legend-superior-rate'] ?></span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

        <div class="table-infos right" style="display: none;" id="table-infos_right">
            <div class="param-advanced-tooltip">
                <span class="global-rate">8,2%</span>
                <p><strong>TAUX MOYEN</strong><br>
                    Pour une note de 3 <img src="img/single-star.png" alt=""><br>
                    et durée de 3 à 12 mois</p>

                <div class="global-progress-container">
                    <span id="param-advanced-global-progress-label"></span>
                    <canvas id="param-advanced-global-progress" width="109" height="109"></canvas>
                    <span class="global-progress-note">Taux auquel je souhaite prêter</span>
                </div>
                <div class="medium-rate-note">
                    <span>Je souhaite appliquer le taux moyen constaté ?</span>
                    <div class="medium-rate-buttons">
                        <button class="btn btn-small" type="button" onclick="">Oui</button>
                        <button class="btn btn-small grise1" type="button" onclick="">Non</button>
                    </div>
                </div>
            </div>
            <button class="btn" id="param-advanced-btn-submit" type="button" onclick="">Valider</button>
        </div>
    </div>
    <div class="row">
        <a href="#" class="link-less" style="display:none;"><?= $this->lng['autobid']['settings-link-to-novice-mode'] ?></a>
    </div>

    <div class="row text-center" >
        <button class="btn" id="validate_settings_expert" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>" >
            <?= $this->lng['autobid']['settings-button-validate-settings'] ?>
        </button>
        <button class="btn" style="display:none;" id="cancel_modification_settings" onClick="window.location.reload()" >
            Annuler
        </button>
    </div>
    </div>
</div>

<script type="text/javascript">
$(window).load(function(){
    // Adds € in after on input
    $("#autobid-amount").after('<span class="unit">€</span>');

    $('#autobid-amount').change(function() {
        var amount = $("#autobid-amount").val().replace(',','.');
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
            $('#error-amount-wrong').show();
        } else {
            $(this).addClass('LV_valid_field');
            $(this).removeClass('LV_invalid_field');
            $('#error-amount-wrong').hide();

        }
    });

    $('#settings_modifications_novice').click(function () {
        $('#settings_modifications_novice').hide();
        $('#settings_instructions_novice').show();
        $('#autobid-amount').prop('disabled', false);
        $('#autobid-param-simple-taux-min-field').hide();
        $('#select-autobid-taux').show();
        $('.link-more').show();
        $('#validate_settings_novice').show();
        $('#cancel_modification_settings').show();
    });

    $('.link-less').click(function () {
        $('#expert-settings').hide();
        $('#settings_instructions_novice').show();
        $('#settings_instructions_expert').hide();
        $('.link-more').show();
        $('#validate_settings_novice').show();
        $('#expert-settings-consult').show();
        $('.param-advanced-switch').hide();
        $('.param-advanced-buttons').hide();
        $('.apply-global-medium-rate').hide();
        $('#validate_settings').hide();
        $('#autobid-block').addClass('autobid-param-advanced-locked');
        $('.link-less').hide();
        $('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
        $('.c2-sb-text').html('Choisir');
        $('#rate-settings-novice').show();
        $('#select-autobid-taux').show();
        $('#autobid-param-simple-taux-min-field').hide();
    });


    $('#settings_modifications_expert').click(function () {
        $('#settings_modifications_expert').hide();
        $('#settings_instructions_expert').show();
        $('#autobid-amount').prop('disabled', false);
        $('#expert-settings-consult').hide();
        $('.param-advanced-switch').show();
        $('.param-advanced-buttons').show();
        $('.apply-global-medium-rate').show();
        $('#validate_settings_expert').show();
        $('#cancel_modification_settings_expert').show();
        $('#autobid-block').removeClass('autobid-param-advanced-locked');
        $('.link-less').show();
        $('#cancel_modification_settings').show();
    });

    $('.link-more').click(function () {
        $('#expert-settings').show();
        $('#settings_instructions_novice').hide();
        $('#settings_instructions_expert').show();
        $('#select-autobid-taux').hide();
        $('#rate-settings-novice').hide();
        $('.link-more').hide();
        $('#validate_settings_novice').hide();
        $('#expert-settings-consult').hide();
        $('.param-advanced-switch').show();
        $('.param-advanced-buttons').show();
        $('.apply-global-medium-rate').show();
        $('#validate_settings_expert').show();
        $('#cancel_modification_settings_expert').show();
        $('#autobid-block').removeClass('autobid-param-advanced-locked');
        $('.link-less').show();
    });


    $('.cell-inner').click(function () {
        $('.table-infos').show();



    });

// Block advanced params
    if($('.param-advanced-switch-input').length){
        $('.param-advanced-switch-input').on('change', function() {
            var rateSwitch = $(this);
            if (rateSwitch.val() == "<?= \autobid::STATUS_ACTIVE ?>") {
                rateSwitch.val('<?= \autobid::STATUS_INACTIVE ?>');
            } else {
                rateSwitch.val('<?= \autobid::STATUS_ACTIVE ?>');
            }
            $(this).closest('td').toggleClass('param-off');
        });
    }

    if($('.param-advanced-button').length){
        $('.param-advanced-button').on('click', function() {

            var that = $(this),
                input = that.parent().next(),
                inputUnilend = input.next(),
                currentVal = Number(parseFloat(input.val()).toFixed(1)),
                newVal,
                newValString,
                AvgRateUnilend = Number(parseFloat(inputUnilend.val()).toFixed(1));

            if($(this).text() === '+'){
                if (currentVal < 9.9 ){
                    newVal = Number(currentVal+=0.1).toFixed(1);
                } else {
                    newVal = currentVal;
                }
            } else {
                if (currentVal > 4 ){
                    newVal = Number(currentVal-=0.1).toFixed(1);
                } else {
                    newVal = currentVal;
                }
            }

            input.val(newVal);
            newValString = newVal.toString().replace(".", ",");
            var label = inputUnilend.next();
            label.html(newValString+'%');

            if (isNaN(AvgRateUnilend) === false ) {
                if (newVal <= AvgRateUnilend ){
                    $(this).closest('td').removeClass('param-over');
                } else {
                    $(this).closest('td').addClass('param-over');
                }
            }
        });
    }

    $('#validate_settings_expert').click(function () {
        var Settings = {
            id_client: "<?= $this->clients->id_client ?>"
        };
        $(':input').each(function(){
            Settings[$(this).attr('id')] = $(this).val();
        });

        $.post(add_url + "/profile/autoBidExpertForm", Settings).done(function (data) {
            if (data == 'settings_saved') {
                window.location.reload();
            }
        })
    });

    $('#global-rate-Unilend').click(function() {
        $('.param-advanced-value').each(function () {
            var that = $(this),
                input = that.next(),
                label = input.next(),
                GlobalRateUnilend  = <?= $this->fAverageRateUnilend ?>,
                StringGlobalRateUnilend = GlobalRateUnilend.toString().replace(".", ",");
            that.val(GlobalRateUnilend);
            label.html(StringGlobalRateUnilend+'%');
        })
    });

    // Semi circle progress bar
    if($('#param-advanced-global-progress').length){
        var bg = $('#param-advanced-global-progress'),
            ctx = ctx = bg[0].getContext('2d'),
            imd = null,
            circ = Math.PI,
            quart = Math.PI / 2;

        ctx.beginPath();
        ctx.strokeStyle = '#b10366';
        ctx.closePath();
        ctx.fill();
        ctx.lineWidth = 20.0;

        imd = ctx.getImageData(0, 0, 109, 109);

        var draw = function(current) {
            ctx.putImageData(imd, 0, 0);
            ctx.beginPath();
            ctx.arc(55, 55, 44, -(quart), ((circ) * current) - quart, false);

            ctx.stroke();
            $('#param-advanced-global-progress-label').html(current*100+'%');
        }

        // Draw progress bar: draw(arg) where arg = progress from 0 to 1
        draw(.75);
    }
});
</script>
