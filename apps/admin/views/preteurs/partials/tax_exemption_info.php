<td rowspan="6" style="vertical-align: top">
    <?php if (false === in_array($this->nextYear, $this->exemptionYears)) : ?>
        <a id="confirm_exemption" href="<?= $this->lurl ?>/thickbox/confirm_tax_exemption/<?= $this->nextYear ?>/check" class="thickbox cboxElement">
            <input type="checkbox" id="tax_exemption_<?= $this->nextYear ?>" name="tax_exemption[<?= $this->nextYear ?>]" value="1">
        </a>
        <label for="tax_exemption_<?= $this->nextYear ?>"><?= $this->nextYear ?></label>
        <br>
    <?php endif; ?>
    <?php foreach ($this->exemptionYears as $exemptionYear) : ?>
        <?php if ($this->nextYear == $exemptionYear) : ?>
            <a id="confirm_exemption" href="<?= $this->lurl ?>/thickbox/confirm_tax_exemption/<?= $exemptionYear ?>/uncheck" class="thickbox cboxElement">
                <input type="checkbox" id="tax_exemption_<?= $exemptionYear ?>" name="tax_exemption[<?= $exemptionYear ?>]" value="1" checked>
            </a>
        <?php else: ?>
            <input type="checkbox" id="tax_exemption_<?= $exemptionYear ?>" name="tax_exemption[<?= $exemptionYear ?>]" value="1" checked disabled>
        <?php endif; ?>
        <label for="tax_exemption_<?= $exemptionYear ?>"><?= $exemptionYear ?></label>
        <br>
    <?php endforeach; ?>
</td>