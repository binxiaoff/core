<?php use Unilend\Entity\UniversignEntityInterface; ?>
<div class="row">
    <div class="col-md-12">
        <?php if (count($this->aMoneyOrders) > 0) : ?>
            <table class="tablesorter table table-hover table-striped lender-operations">
                <thead>
                <tr>
                    <th>ID Projet</th>
                    <th>IBAN</th>
                    <th>BIC</th>
                    <th>PDF</th>
                    <th>Statut</th>
                    <th>Date d'ajout</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->aMoneyOrders as $aMoneyOrder) : ?>
                    <tr>
                        <td><?= $aMoneyOrder['id_project'] ?></td>
                        <td><?= $aMoneyOrder['iban'] ?></td>
                        <td><?= $aMoneyOrder['bic'] ?></td>
                        <td><a href="<?= $this->lurl ?>/protected/mandats/<?= $aMoneyOrder['name'] ?>">MANDAT</a></td>
                        <td>
                            <?php
                            switch ($aMoneyOrder['status']) {
                                case UniversignEntityInterface::STATUS_PENDING:
                                    echo 'En attente de signature';
                                    break;
                                case UniversignEntityInterface::STATUS_SIGNED:
                                    echo 'Signé';
                                    break;
                                case UniversignEntityInterface::STATUS_CANCELED:
                                    echo 'Annulé';
                                    break;
                                case UniversignEntityInterface::STATUS_FAILED:
                                    echo 'Echec';
                                    break;
                                case UniversignEntityInterface::STATUS_ARCHIVED:
                                    echo 'Archivé';
                                    break;
                                default:
                                    echo 'Inconnu';
                                    break;
                            }
                            ?>
                        </td>
                        <td><?= $this->formatDate($aMoneyOrder['added'], 'd/m/Y à H:i:s') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Aucun mandat.</p>
        <? endif; ?>
    </div>
</div>
