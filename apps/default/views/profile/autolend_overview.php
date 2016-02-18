

<div class="autobid-switch-w-notice autobid-block">
    <div class="col-left">
        <div class="switch-container <?= $this->bAutoBidOn ? 'checked' : '' ?>">
            <label class="label-on" for="autobid-switch-1"><?= $this->lng['autobid']['switch-status-on'] ?></label>
            <label class="label-off" for="autobid-switch-1"><?= $this->lng['autobid']['switch-status-off'] ?></label>
            <input type="checkbox" class="switch-input" id="autobid-switch-1" name="autobid-switch-1" <?= $this->bAutoBidOn ? 'checked' : '' ?>>
        </div>
    </div>
    <div class="col-right">
        <?php if (false === $this->bAutoBidOn) : ?>
        <div class="switch-notice">
            <?php if ($this->bActivatedLender && $this->bFirstTimeActivation) : ?>
                <p><?= $this->lng['autobid']['overview-text-first-activation'] ?></p>
            <?php elseif ($this->bActivatedLender && false === $this->bFirstTimeActivation) : ?>
                <p><?= $this->lng['autobid']['overview-text-nth-activation'] ?></p>
            <?php elseif (false === $this->bActivatedLender) : ?>
                <p><?= $this->lng['autobid']['overwiew-text-completeness'] ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>


<script>
    <?php if($this->bActivatedLender) : ?>
    $(window).load(function () {
        // Switch On/Off handler
        if ($('.switch-input').length) {
            $('.switch-input').on('change', function () {
                var Settings = {
                    setting: $('.switch-input').is(":checked"),
                    id_client: "<?= $this->clients->id_client ?>"
                };
                $.post(add_url + "/ajax/changeAutoBidSetting", Settings).done(function (data) {
                    console.log(data);
                    if (data == "update_on_success") {
                        $('.switch-container').toggleClass('checked');
                        $('#consultation').removeClass('active');
                        $('#parametrage').addClass('active');
                        $('.autobid-tab').removeClass('visible');
                        $('#tab-2').addClass('visible');
                    } else if (data == "update_off_success") {
                        $('.switch-container').toggleClass('checked');
                    }
                })
            });
        }
    });
    <?php endif; ?>
</script>