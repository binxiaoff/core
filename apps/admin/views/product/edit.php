<div id="contenu">
    <h1>Consulter des produits</h1>
    <table class="form">
        <tr>
            <th>Nom</th>
            <td><?= $this->translator->trans('product_label_' . $this->product->label) ?></td>
        </tr>
        <tr>
            <th>Statut</th>
            <?php
            switch ( $this->product->status) {
                case \Unilend\Bundle\CoreBusinessBundle\Entity\Product::STATUS_OFFLINE:
                    $status = 'Desactivé FO (indisponible FO mais disponible BO)';
                    break;
                case \Unilend\Bundle\CoreBusinessBundle\Entity\Product::STATUS_ONLINE:
                    $status = 'Activé';
                    break;
                case \Unilend\Bundle\CoreBusinessBundle\Entity\Product::STATUS_ARCHIVED:
                    $status = 'Archivé (indisponible FO et BO)';
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
                    <?php foreach ($this->contracts as $contract) : ?>
                    <li>
                        <a href="/product/contract_details/<?= $contract['id_contract'] ?>" title="<?= $this->translator->trans('contract-type-label_' . $contract['label']) ?>">
                            <?= $this->translator->trans('contract-type-label_' . $contract['label']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </td>
        </tr>
        <tr>
            <th>Durée min (mois)</th>
            <td><?= isset($this->duration['min'][0]) ? $this->duration['min'][0] : '<em>Pas de contrôle</em>' ?></td>
        </tr>
        <tr>
            <th>Durée max (mois)</th>
            <td><?= isset($this->duration['max'][0]) ? $this->duration['max'][0] : '<em>Pas de contrôle</em>' ?></td>
        </tr>
        <tr>
            <th>Motif de l'emprunt</th>
            <td>
                <?php if (empty($this->borrowerMotives)) : ?>
                    <em>Pas de contrôle</em>
                <?php else : ?>
                    <ul>
                        <?php foreach ($this->borrowerMotives as $motive) : ?>
                            <li><?= $motive ?></li>
                        <?php endforeach; ?>
                    </ul>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <th>Jours de création min</th>
            <td><?= isset($this->creationDaysMin[0]) ? $this->creationDaysMin[0] : '<em>Pas de contrôle</em>' ?></td>
        </tr>

        <tr>
            <th>RCS</th>
            <td><?= isset($this->rcs[0]) ? ($this->rcs[0] == 1 ? 'La société doit être RCS.' : 'La société doit être non RCS.') : '<em>Pas de contrôle</em>' ?></td>
        </tr>
        <tr>
            <th>Codes NAF éligible</th>
            <td>
                <?php if (empty($this->nafcodes)) : ?>
                    <em>Pas de contrôle</em>
                <?php else : ?>
                    <?php foreach ($this->nafcodes as $code) : ?>
                        <?= $code ?>
                    <?php endforeach; ?>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <th>ID prêteur</th>
            <td>
                <?php if (empty($this->lenderId)) : ?>
                    <em>Pas de contrôle</em>
                <?php else : ?>
                    <?php foreach ($this->lenderId as $lenderId) : ?>
                        <?= $lenderId ?>
                    <?php endforeach; ?>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <th>Type prêteur</th>
            <td>
                <?php if (empty($this->lenderType)) : ?>
                    <em>Pas de contrôle</em>
                <?php else : ?>
                    <?php foreach ($this->lenderType as $lenderType) : ?>
                        <?= $lenderType ?>
                    <?php endforeach; ?>
                <? endif ?>
            </td>
        </tr>

        <tr>
            <th>Le siège social est hors les codes postale suivants</th>
            <td>
                <?php if (empty($this->checkExcludedLocation)) : ?>
                    <em>Pas de contrôle</em>
                <?php else : ?>
                    <?php foreach ($this->checkExcludedLocation as $location) : ?>
                        <?= $location ?>
                    <?php endforeach; ?>
                <? endif ?>
            </td>
        </tr>

        <tr>
            <th>Score Xerfi max</th>
            <td><?= isset($this->maxXerfiScore[0]) ? $this->maxXerfiScore[0] : '<em>Pas de contrôle</em>' ?></td>
        </tr>

        <tr>
            <th>N'a aucun projet avec produit B-LEND en cours depuis</th>
            <td><?= isset($this->minNoIncidentBlendDays[0]) ? $this->noBlendDays[0] . ' jours' : '<em>Pas de contrôle</em>' ?></td>
        </tr>

        <tr>
            <th>Aucun incident sur un projet Unilend depuis</th>
            <td><?= isset($this->minNoIncidentUnilendDays[0]) ? $this->noIncidentUnilendDays[0] . ' jours' : '<em>Pas de contrôle</em>' ?></td>
        </tr>

        <tr>
            <th>Aucun incident sur un projet B-LEND depuis</th>
            <td><?= isset($this->minNoIncidentBlendDays[0]) ? $this->noIncidentBlendDays[0] . ' jours' : '<em>Pas de contrôle</em>' ?></td>
        </tr>

        <tr>
            <th>Pre-score min</th>
            <td><?= isset($this->minPreScore[0]) ? $this->minPreScore[0] : '<em>Pas de contrôle</em>' ?></td>
        </tr>

        <tr>
            <th>Pre-score max</th>
            <td><?= isset($this->maxPreScore[0]) ? $this->maxPreScore[0] : '<em>Pas de contrôle</em>' ?></td>
        </tr>
    </table>
</div>
