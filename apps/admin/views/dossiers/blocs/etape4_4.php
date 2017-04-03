<?php if (count($this->lbilans) > 0) : ?>
    <a class="tab_title" id="section-financial-summary" href="#section-financial-summary">4.4. Synthèse financière</a>
    <div class="tab_content" id="etape4_4">
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/dossiers/export/<?= $this->projects->id_project ?>" class="btn_link">CSV données financières</a>
        </div>
        <h1>Déclaration client</h1>
        <table class="form">
            <tbody>
            <tr>
                <th style="width: 17%;"><label for="ca_declara_client">Chiffe d'affaires declaré</label></th>
                <td style="width: 17%;"><?= $this->ficelle->formatNumber($this->projects->ca_declara_client, 0) ?>&nbsp;€</td>
                <th style="width: 16%;"><label for="resultat_exploitation_declara_client">Résultat d'exploitation declaré</label></th>
                <td style="width: 16%;"><?= $this->ficelle->formatNumber($this->projects->resultat_exploitation_declara_client, 0) ?>&nbsp;€</td>
                <th style="width: 17%;"><label for="fonds_propres_declara_client">Fonds propres declarés</label></th>
                <td style="width: 17%;"><?= $this->ficelle->formatNumber($this->projects->fonds_propres_declara_client, 0) ?>&nbsp;€</td>
            </tr>
            </tbody>
        </table>
        <br>
        <h1>Comptes financiers</h1>
        <?php if (in_array(company_tax_form_type::FORM_2033, array_column($this->aBalanceSheets, 'form_type'))) : ?>
            <?php $this->fireView('blocs/profit_loss/2033'); ?>
        <?php endif; ?>
        <?php if (in_array(company_tax_form_type::FORM_2035, array_column($this->aBalanceSheets, 'form_type'))) : ?>
            <?php $this->fireView('blocs/profit_loss/2035'); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
