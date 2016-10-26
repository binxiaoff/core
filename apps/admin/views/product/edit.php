<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/emprunteurs" title="Emprunteur">Emprunteur</a> -</li>
        <li><a href="<?=$this->lurl?>/product" title="Gestion des produits">Gestion des produits</a> -</li>
        <li>Consultation d'un produit</li>
    </ul>
    <h1>Consulter des produits</h1>
    <table class="form">
        <tr>
            <th>Nom :</th>
            <td><?= $this->translator->trans('product_label_' . $this->product->label) ?></td>
        </tr>
        <tr>
            <th>Status :</th>
            <?php
            switch ( $this->product->status) {
                case \product::STATUS_OFFLINE:
                    $status = 'Desactivé FO (indisponible FO mais disponible BO)';
                    break;
                case \product::STATUS_ONLINE:
                    $status = 'Activé';
                    break;
                case \product::STATUS_ARCHIVED:
                    $status = 'Archivé (indisponible FO et BO)';
                    break;
            }
            ?>
            <td><?= $status ?></td>
        </tr>
        <tr>
            <th>Type d'échéancier : </th>
            <td><?= $this->translator->trans('repayment-type-label_' . $this->repaymentType->label) ?></td>
        </tr>
        <tr>
            <th>Contrat(s) sous-jacent : </th>
            <td>
                <ul>
                    <?php foreach ($this->contracts as $contract): ?>
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
            <th>Durée min (mois) :</th>
            <td><?= isset($this->duration['min'][0]) ? $this->duration['min'][0] : 'pas de contrôle' ?></td>
        </tr>
        <tr>
            <th>Durée max (mois) :</th>
            <td><?= isset($this->duration['max'][0]) ? $this->duration['max'][0] : 'pas de contrôle' ?></td>
        </tr>
        <tr>
            <th>Motive d'emprunteur éligible</th>
            <td>
                <?php if (empty($this->borrowerMotives)) : ?>
                    pas de contrôle
                <?php else: ?>
                    <ul>
                        <?php foreach ($this->borrowerMotives as $motive): ?>
                            <li><?= $motive ?></li>
                        <?php endforeach; ?>
                    </ul>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <th>Jours de creation min :</th>
            <td><?= isset($this->creationDaysMin[0]) ? $this->creationDaysMin[0] : 'pas de contrôle' ?></td>
        </tr>

        <tr>
            <th>RCS :</th>
            <td><?= isset($this->rcs[0]) ? ($this->rcs[0] == 1 ? 'La société doit être RCS.' : 'La société doit être non RCS.') : 'pas de contrôle' ?></td>
        </tr>
        <tr>
            <th>Codes NAF éligible :</th>
            <td>
                <?php if (empty($this->nafcodes)) : ?>
                    pas de contrôle
                <?php else: ?>
                    <?php foreach ($this->nafcodes as $code): ?>
                        <?= $code ?>
                    <?php endforeach; ?>
                <? endif ?>
            </td>
        </tr>
    </table>
</div>
