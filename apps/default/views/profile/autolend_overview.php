<div class="autobid-switch-w-notice autobid-block">
    <div class="col-left">
        <div class="switch-container">
            <label class="label-on" for="autobid-switch-1"><?= $this->lng['autobid']['switch-status-on'] ?></label>
            <label class="label-off" for="autobid-switch-1"><?= $this->lng['autobid']['switch-status-off'] ?></label>
            <input type="checkbox" class="switch-input" id="autobid-switch-1" name="autobid-switch-1" value="<?= \client_settings::AUTO_BID_OFF ?>">
        </div>
    </div>
    <div class="col-right">
        <div class="switch-notice">
            <p id="switch-notice-active" style="display: none"><?= str_replace('[#LULR#]', $this->lurl, $this->lng['autobid']['overview-text-active']) ?></p>
            <p id="switch-notice-first-active" style="display: none"><?= str_replace('[#LULR#]', $this->lurl,$this->lng['autobid']['overview-text-first-activation']) ?></p>
            <p id="switch-notice-nth-active" style="display: none"><?= str_replace('[#LULR#]', $this->lurl, $this->lng['autobid']['overview-text-nth-activation']) ?></p>
            <p id="switch-notice-lender-inactive" style="display: none"><?= str_replace('[#LULR#]', $this->lurl, $this->lng['autobid']['overview-text-completeness']) ?></p>
        </div>
    </div>
</div>