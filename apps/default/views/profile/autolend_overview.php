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
            <p><?= $this->lng['autobid']['overwiew-text-active'] ?></p>
        <?php else : ?>
            <?php if ($this->bActivatedLender && $this->bFirstTimeActivation) : ?>
                <p><?= $this->lng['autobid']['overview-text-first-activation'] ?></p>
            <?php elseif ($this->bActivatedLender && false === $this->bFirstTimeActivation) : ?>
                <p><?= $this->lng['autobid']['overview-text-nth-activation'] ?></p>
            <?php elseif (false === $this->bActivatedLender) : ?>
                <p><?= $this->lng['autobid']['overwiew-text-completeness'] ?></p>
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
                console.log(Settings);

                if ($('#autobid-switch-1').val() == <?= \client_settings::AUTO_BID_ON ?>) {
                    $.post(add_url + "/profile/AutoBidSettingOff", Settings).done(function (data) {
                        if (data == "update_off_success") {
                            $('.switch-container').toggleClass('checked');
                            $('#autobid-switch-1').val('<?= \client_settings::AUTO_BID_OFF ?>');
                            $('#parametrage').hide();
                            $('#tab-2').hide();
                        }
                    })
                } else {
                    $('.header-autobid li').removeClass('active');
                    $(this).parent().addClass('active');
                    $('.autobid-tab').removeClass('visible');
                    $('#tab-'+$(this).attr('data-dest')).addClass('visible');
                    $('#parametrage').show();
                    $('#tab-2').show();
                }
            });
        }
    });
    <?php endif; ?>
</script>