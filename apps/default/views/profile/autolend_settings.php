<h3><?= $this->lng['autobid']['settings-subtitle'] ?></h3>

<p id="last-upadated-date" style="display: none"></p>

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
    <input type="hidden" name="settings-mode" id="settings-mode" value="novice"/>

    <div id="settings_instructions_novice" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>">
        <p><?= $this->lng['autobid']['settings-simple-instructions'] ?></p>
    </div>
    <div id="settings_instructions_expert" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>">
        <p><?= $this->lng['autobid']['settings-expert-instructions'] ?></p>
    </div>
    <div>


    </div>
    <div class="autobid-param-form">
        <form action="<?= $this->lurl ?>/profile/autolend" method="post" enctype="multipart/form-data">
            <div class="row">
                <label for=""><?= $this->lng['autobid']['settings-label-amount'] ?>
                    <span class="label-note"><?= $this->lng['autobid']['settings-example-amount'] ?></span>
                </label>
                <span id="autobid-amount-no-input"><?= (isset($this->aSettingsSubmitted)) ? $this->aSettingsSubmitted['amount'] : '' ?> €</span>
                <input type="text" name="autobid-amount" id="autobid-amount"
                       value="<?= (isset($this->aSettingsSubmitted)) ? $this->aSettingsSubmitted['amount'] : '' ?>"
                       class="field required<?= (isset($this->aErrors['amount'])) ? ' LV_invalid_field' : '' ?>"
                       data-validators="Presence&amp;Numericality {minimum: <?= $this->iMinimumBidAmount ?>}"
                       onkeyup="noDecimale(this.value,this.id);"
                       style="display: none;"
                    <?= (empty($this->aErrors)) ? 'disabled="disabled"' : '' ?>/>
            </div>
            <div id="novice-settings" class="autobid-param-form-simple" style="display:none">
                <div id="rate-settings-novice" style="'display:none;">
                    <div class="row">
                        <label for=""><?= $this->lng['autobid']['settings-simple-label-rate'] ?>
                            <span class="label-note"><?= $this->lng['autobid']['settings-simple-example-rate'] ?></span>
                        </label>
                        <span id="autobid-param-simple-taux-min-field-no-input">
                            <?= (false === empty($this->aSettingsSubmitted['simple-taux-min'])) ? $this->ficelle->formatNumber($this->aSettingsSubmitted['simple-taux-min'], 1) : '' ?> %
                        </span>
                        <div id="select-autobid-taux" style="display:none;">
                            <select name="autobid-param-simple-taux-min" id="autobid-param-simple-taux-min" class="custom-select field-small required" >
                                <option value="0"><?= $this->lng['autobid']['settings-select-rate'] ?></option>
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
                    <button class="btn" style="display:none;" id="cancel_modification_settings_novice" onClick="window.location.reload()" >
                        <?= $this->lng['autobid']['cancel-setting-modification-button'] ?>
                    </button>
                    <button class="btn" type="submit" name="send-form-autobid-param-simple">
                        <?= $this->lng['autobid']['settings-button-validate-settings'] ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="expert-settings" style="display:none">
        <div class="apply-global-medium-rate" style="display: none;">
            <p><?= str_replace('[#GLOBAL-AVG-RATE#]', $this->ficelle->formatNumber($this->fAverageRateUnilend, 1), $this->lng['autobid']['unilend-global-rate']) ?></p>
            <p><?= $this->lng['autobid']['apply-unilend-global-rate-instruction'] ?></p>
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
                                                <button class="param-advanced-button" value="0.1">+</button>
                                                <button class="param-advanced-button" value="-0.1">-</button>
                                            </div>
                                            <label class="param-advanced-label"><?= $this->ficelle->formatNumber($aSetting['rate_min'], 1) ?>%</label>
                                            <input type="hidden" id="<?= $aSetting['id_autobid'] ?>-param-advanced-value" class="param-advanced-value" value="<?= $aSetting['rate_min'] ?>">
                                            <input type="hidden" name="param-advanced-unilend-rate" value="<?= $aSetting['AverageRateUnilend'] ? $this->ficelle->formatNumber($aSetting['AverageRateUnilend'], 1) : ''; ?>">
                                            <input type="hidden" id="<?= $aSetting['id_autobid'] ?>-param-advanced-period" value="<?= $aSetting['id_autobid_period'] ?>">
                                            <input type="hidden" id="<?= $aSetting['id_autobid'] ?>-param-advanced-evaluation" value="<?= $aSetting['evaluation'] ?>">
                                            <input type="hidden" value="<?= $aSetting['note'] ?>" name="param-advanced-note">
                                            <input type="hidden" value="<?= $aSetting['period_min'] ?>" name="param-advanced-period-min">
                                            <input type="hidden" value="<?= $aSetting['period_max'] ?>" name="param-advanced-period-max">
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
                    <span class="global-rate"></span>
                    <p class="indice-rate"></p>
                    <div class="medium-rate-note">
                        <span><?=$this->lng['autobid']['apply-average-rate'] ?></span>
                        <div class="medium-rate-buttons">
                            <button class="btn btn-small btn-apply-avg-rate" type="button"><?=$this->lng['autobid']['yes'] ?></button>
                            <button class="btn btn-small grise1 btn-close-widget" type="button"><?=$this->lng['autobid']['no'] ?></button>
                        </div>
                    </div>
                    <span class="global-progress-note"><?=$this->lng['autobid']['widget-title-acceptation-rate'] ?></span>
                    <div class="global-progress-container">
                        <span id="param-advanced-global-progress-label"></span>
                        <canvas id="param-advanced-global-progress" width="109" height="109"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <a href="#" class="link-less" style="display:none;"><?= $this->lng['autobid']['settings-link-to-novice-mode'] ?></a>
        </div>

        <div class="row text-center" id="buttons-expert-settings">
            <button class="btn" style="display:none;" id="cancel_modification_settings_expert" onClick="window.location.reload()" >
                <?= $this->lng['autobid']['cancel-setting-modification-button'] ?>
            </button>
            <button class="btn" id="validate_settings_expert" style="<?= (empty($this->aErrors)) ? 'display:none;' : '' ?>" >
                <?= $this->lng['autobid']['settings-button-validate-settings'] ?>
            </button>
        </div>
    </div>

    <div id="autobid_modify_parameters">
        <div class="row text-center">
            <button class="btn" type="button" id="settings_modifications_novice">
                <?= $this->lng['autobid']['settings-button-modify-parameters'] ?>
            </button>
            <button class="btn" type="button" id="settings_modifications_expert">
                <?= $this->lng['autobid']['settings-button-modify-parameters'] ?>
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

    $('#validate_settings_novice').click(function (e) {
        if ($('#autobid-param-simple-taux-min').val() <= 0) {
            $("#autobid-param-simple-taux-min").addClass("field-error");
            e.preventDefault();
        } else {
            $("#autobid-param-simple-taux-min").removeClass("field-error");
        }
    });


    $('.cell-inner').click(function () {
        disableSimpleRateSetting();

        if (!$('#autobid-block').hasClass('autobid-param-advanced-locked')) {
            var cell = $(this);
            var widget = $('#table-infos_right');
            var note = $(this).find('input[name=param-advanced-note]').val();
            var period_min = $(this).find('input[name=param-advanced-period-min]').val();
            var period_max = $(this).find('input[name=param-advanced-period-max]').val();
            var avg_rate_indice = '<?= $this->lng['autobid']['widget-average-rate-indice'] ?>'
                .replace('[#note#]', note)
                .replace('[#period_min#]', period_min)
                .replace('[#period_max#]', period_max);
            var e_avg_rate_cell = cell.find('input[name=param-advanced-unilend-rate]');
            var avg_rate = e_avg_rate_cell.val();
            if (avg_rate.length === 0) {
                avg_rate = '<?= $this->ficelle->formatNumber($this->fAverageRateUnilend, 1) ?>';
                avg_rate_indice = '<?= $this->lng['autobid']['widget-platform-average-rate-indice'] ?>';
            }
            widget.find('.global-rate').html(avg_rate+'%');
            widget.find('.indice-rate').html(avg_rate_indice);

            widget.find('.btn-apply-avg-rate').off().click(function(e){
                e.stopPropagation();
                cell.find('.param-advanced-label').html(avg_rate+'%');
                cell.find('.param-advanced-value').val(avg_rate.replace(",", "."));
                var rate = cell.find('.param-advanced-value').val();
                drawPercentage(rate);
            });

            widget.find('.btn-close-widget').click(function(){
                widget.hide();
            });

            var rate = cell.find('.param-advanced-value').val();
            drawPercentage(rate);

            widget.show();
        }
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

    if ($('.param-advanced-button').length) {
        $('.param-advanced-button').on('click', function () {
            var cell = $(this).parents('.cell-inner')
            var inputRate = cell.find('.param-advanced-value');
            var labelRate = cell.find('.param-advanced-label');
            var AvgRateUnilend = parseFloat(cell.find('input[name=param-advanced-unilend-rate]').val().replace(",", "."));
            var currentVal = Number(parseFloat(inputRate.val()).toFixed(1));
            var newVal = Number(currentVal + parseFloat($(this).val())).toFixed(1);

            if (newVal >= 10.0) {
                newVal = '10.0';
            }
            if (newVal <= 4.0) {
                newVal = '4.0';
            }

            inputRate.val(newVal);
            labelRate.html(newVal.toString().replace(".", ",") + '%');
            parseFloat(newVal).toFixed(1);
            drawPercentage(newVal);

            if (isNaN(AvgRateUnilend) === false) {
                if (newVal <= AvgRateUnilend) {
                    $(this).closest('td').removeClass('param-over');
                } else {
                    $(this).closest('td').addClass('param-over');
                }
            }
        });
    }

    $('#global-rate-Unilend').click(function() {
        $('.param-advanced-value').each(function () {
            $(this).val(<?= $this->fAverageRateUnilend ?>);
            $(this).parents('.param-advanced-bottom').find('.param-advanced-label').html(<?= $this->fAverageRateUnilend ?>.toString().replace(".", ",")+'%');
        })
    });

    function drawPercentage(rate){
        var all_acceptation = $.parseJSON('<?= $this->sAcceptationRate; ?>');
        var percentage = all_acceptation[rate];
        var canvas = $('#param-advanced-global-progress')[0];
        canvas.width = canvas.width;
        var bg = $('#param-advanced-global-progress'),
            ctx = bg[0].getContext('2d'),
            circ = Math.PI,
            quart = Math.PI / 2;

        ctx.beginPath();
        ctx.strokeStyle = '#b10366';
        ctx.closePath();
        ctx.fill();
        ctx.lineWidth = 20.0;

        var imd = ctx.getImageData(0, 0, 109, 109);

        ctx.putImageData(imd, 0, 0);
        ctx.beginPath();
        ctx.arc(55, 55, 44, -(quart), ((circ) * (percentage / 100)) - quart, false);

        ctx.stroke();
        $('#param-advanced-global-progress-label').html(percentage +'%');
    }

    function disableSimpleRateSetting(){
        $('#autobid-param-simple-taux-min').val('<?= $this->lng['autobid']['settings-select-rate'] ?>');
        $('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
        $('.c2-sb-text').html('<?= $this->lng['autobid']['settings-select-rate'] ?>');
        $('.c2-sb-wrap').addClass("field-error");
    }
});
</script>
