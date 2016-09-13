<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/emprunteurs" title="Emprunteur">Emprunteur</a> -</li>
        <li><a href="<?=$this->lurl?>/product" title="Gestion des produits">Gestion des produits</a> -</li>
        <li>Consultation d'un produit</li>
    </ul>
    <h1>Consulter des produits</h1>
    <table class="form">
        <tr>
            <th>Nom</th>
            <td><?= $this->translator->trans('product_label_' . $this->product->label) ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <?php
            switch ( $this->product->status) {
                case \product::STATUS_DISABLED_FO:
                    $status = 'Desactivé FO';
                    break;
                case \product::STATUS_ACTIVE:
                    $status = 'Activé';
                    break;
                case \product::STATUS_DISABLED:
                    $status = 'Desactivé total';
                    break;
            }
            ?>
            <td><?= $status ?></td>
        </tr>
        <tr>
            <th>Type d'échéancier</th>
            <td><?= $this->translator->trans('repayment-type-label_' . $this->repaymentType->label) ?></td>
        </tr>
        <tr>
            <th>Contrat(s) sous-jacent</th>
            <td>
                <ul>
                    <?php foreach ($this->contracts as $contract): ?>
                    <li><?= $this->translator->trans('contract-type-label_' . $contract['label']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
        <tr>
            <th>Durée min (mois)</th>
            <td><?= isset($this->duration['min'][0]) ? $this->duration['min'][0] : 'pas de limitation' ?></td>
        </tr>
        <tr>
            <th>Durée max (mois)</th>
            <td><?= isset($this->duration['max'][0]) ? $this->duration['max'][0] : 'pas de limitation' ?></td>
        </tr>
        <tr>
            <th>Besoin d'emprunteur éligible</th>
            <td>
                <?php if (empty($this->borrowerNeeds)) : ?>
                    pas de limitation
                <?php else: ?>
                    <ul>
                        <?php foreach ($this->borrowerNeeds as $need): ?>
                            <li><?= $need ?></li>
                        <?php endforeach; ?>
                    </ul>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <th>Pays d'emprunteur éligible</th>
            <td>
                <?php if (empty($this->borrowerCountries)) : ?>
                    pas de limitation
                <?php else: ?>
                    <ul>
                        <?php foreach ($this->borrowerCountries as $country): ?>
                            <li><?= $country ?></li>
                        <?php endforeach; ?>
                    </ul>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <th>Nationalité de prêteur éligible</th>
            <td>
                <?php if (empty($this->lenderNationalities)) : ?>
                    pas de limitation
                <?php else: ?>
                <ul>
                    <?php foreach ($this->lenderNationalities as $nationality): ?>
                        <li><?= $nationality ?></li>
                    <?php endforeach; ?>
                </ul>
                <? endif ?>
            </td>
        </tr>
    </table>
</div>
