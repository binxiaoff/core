<div class="tab_title" id="title_etape4_4">Etape 4.4 - Synthèse financière</div>
<div class="tab_content" id="etape4_4">
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/dossiers/export/<?= $this->projects->id_project ?>" class="btn_link">CSV données financières</a>
    </div>
    <br/>
    <?php if (in_array(company_tax_form_type::FORM_2033, array_column($this->aBalanceSheets, 'form_type'))) : ?>
        <?php $this->fireView('blocs/profit_loss/2033'); ?>
    <?php endif; ?>
    <?php if (in_array(company_tax_form_type::FORM_2035, array_column($this->aBalanceSheets, 'form_type'))) : ?>
        <?php $this->fireView('blocs/profit_loss/2035'); ?>
    <?php endif; ?>
</div>
