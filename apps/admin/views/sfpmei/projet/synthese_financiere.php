<div class="row">
    <div class="col-md-12">
        <?php if (count($this->lbilans) > 0) : ?>
            <?php if (in_array(company_tax_form_type::FORM_2033, array_column($this->aBalanceSheets, 'form_type'))) : ?>
                <?php $this->fireView('projet/profit_loss/2033'); ?>
            <?php endif; ?>
            <?php if (in_array(company_tax_form_type::FORM_2035, array_column($this->aBalanceSheets, 'form_type'))) : ?>
                <?php $this->fireView('projet/profit_loss/2035'); ?>
            <?php endif; ?>
        <?php else : ?>
            Aucun bilan comptable.
        <?php endif; ?>
    </div>
</div>
