<div class="tax_exemption">
    <p>
        <?= $this->lng['lender-dashboard']['hello'] . ' ' . $this->clients->prenom ?>,
    </p>
    <?php if (true === empty($this->exemptedLastYear)): ?>
        <p>
            <?= str_replace('%nextYear%', $this->nextYear, $this->lng['lender-dashboard']['tax-exemption-question']) ?>
        </p>
        <a href="<?= $this->lurl ?>/fiscalite" target="_blank">
            > <?= $this->lng['lender-dashboard']['tax-exemption-more-info'] ?>
        </a>
        <br>
        <a class="popup-link" href="<?= $this->lurl ?>/thickbox/signTaxExemption">
            > <?= $this->lng['lender-dashboard']['tax-exemption-sign-online-request'] ?>
        </a>
    <?php else: ?>
        <p>
            <?= str_replace(['%currentYear%', '%taxExemptionRequestLimitDate%'], [$this->currentYear, $this->taxExemptionRequestLimitDate], $this->lng['lender-dashboard']['tax-exemption-renew-declaration']) ?>
            <a class="popup-link" href="<?= $this->lurl ?>/thickbox/signTaxExemption">
                <?= ' ' . $this->lng['lender-dashboard']['tax-exemption-clicking-here'] ?>
            </a>
        </p>
    <?php endif; ?>
</div>
<br>