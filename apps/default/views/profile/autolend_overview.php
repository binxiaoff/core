<div class="autobid-switch-w-notice autobid-block">
    <div class="col-left">
        <div class="switch-container <?= $this->bAutoBidOn ? 'checked' : '' ?>">
            <label class="label-on" for="autobid-switch-1"><?= $this->lng['autobid']['switch-status-on'] ?></label>
            <label class="label-off" for="autobid-switch-1"><?= $this->lng['autobid']['switch-status-off'] ?></label>
            <input type="checkbox" class="switch-input" id="autobid-switch-1" name="autobid-switch-1"
                   value="<?= ($this->bAutoBidOn)? \client_settings::AUTO_BID_ON : \client_settings::AUTO_BID_OFF ?>">
        </div>
    </div>
    <div class="col-right">
        <div class="switch-notice">
        <?php if ($this->bAutoBidOn) : ?>
            <p><?= $this->lng['autobid']['overview-text-active'] ?></p>
        <?php else : ?>
            <?php if ($this->bActivatedLender && $this->bFirstTimeActivation) : ?>
                <p><?= $this->lng['autobid']['overview-text-first-activation'] ?></p>
            <?php elseif ($this->bActivatedLender && false === $this->bFirstTimeActivation) : ?>
                <p><?= $this->lng['autobid']['overview-text-nth-activation'] ?></p>
            <?php elseif (false === $this->bActivatedLender) : ?>
                <p><?= $this->lng['autobid']['overview-text-completeness'] ?></p>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>
</div>


<script>
    <?php if($this->bActivatedLender) : ?>
    $(window).load(function () {
        // Switch On/Off handler
        if ($('.switch-input').length) {
            $('.switch-input').on('change', function () {
                var Settings = {
                    setting: $('#autobid-switch-1').val(),
                    id_lender: "<?= $this->oLendersAccounts->id_lender_account ?>"
                };
                if ($('#autobid-switch-1').val() == <?= \client_settings::AUTO_BID_ON ?>) {
                    $.post(add_url + "/profile/AutoBidSettingOff", Settings).done(function (data) {
                        if (data == "update_off_success") {
                            $('.switch-container').toggleClass('checked');
                            $('#autobid-switch-1').val('<?= \client_settings::AUTO_BID_OFF ?>');
                            $('#param').hide();
                            $('#tab-2').hide();
                        }
                    })
                } else {
                    $('#tab-2').addClass('visible');
                    $('#tab-1').removeClass('visible');
                    $('#param').addClass('active');
                    $('#param').show();
                    $('#consult').removeClass('active');
                    $('#param').trigger("click");
                    $('#settings_modifications_novice').hide();
                    $('#settings_instructions_novice').show();
                    $('#autobid-amount').prop('disabled', false);
                    $('#autobid-param-simple-taux-min-field').hide();
                    $('#select-autobid-taux').show();
                    $('.link-more').show();
                    $('#validate_settings_novice').show();
                    $('#cancel_modification_settings').show();
                }
            });
        }
    });
    <?php endif; ?>
</script>