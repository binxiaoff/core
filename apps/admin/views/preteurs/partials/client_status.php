<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;

?>
<div class="row">
    <div class="col-md-6">
        <h2>Historique des statuts client</h2>
        <div class="row">
            <div class="col-md-12">
                <table class="tablesorter histo_status_client">
                    <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatusHistory $historyEntry */ ?>
                    <?php foreach ($this->statusHistory as $historyEntry) : ?>
                    <?php
                        switch ($historyEntry->getIdStatus()->getId()) {
                            case ClientsStatus::STATUS_CREATION: ?>
                                <tr>
                                    <td>Création de compte le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?></td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_TO_BE_CHECKED: ?>
                                <tr>
                                    <td>
                                        <?php if (empty($historyEntry->getContent())) : ?>
                                            Fin d'inscription le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                        <?php else: ?>
                                            Compte modifié le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                            <?= $historyEntry->getContent() ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_COMPLETENESS: ?>
                                <tr>
                                    <td>
                                        Complétude le <?= $historyEntry->getAdded()->format('d/m/Y H:i:s') ?>
                                        par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?><br>
                                        <?= $historyEntry->getContent() ?>
                                    </td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_COMPLETENESS_REMINDER: ?>
                                <tr>
                                    <td>
                                        Complétude Relance le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                        <?= $historyEntry->getContent() ?>
                                    </td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_COMPLETENESS_REPLY: ?>
                                <tr>
                                    <td>
                                        Complétude Reponse le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                        <?= $historyEntry->getContent() ?>
                                    </td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_MODIFICATION: ?>
                                <tr>
                                    <td>
                                        Compte modifié le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                        <?= $historyEntry->getContent() ?>
                                    </td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_VALIDATED: ?>
                                <tr>
                                    <td>
                                        <?php if ($historyEntry->getIdUser()->getIdUser() > 0) : ?>
                                            Compte validé le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?>
                                            par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?>
                                        <?php else : ?>
                                            <?= $historyEntry->getContent() ?> le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?>
                                            par <?= (-1 === $historyEntry->getIdUser()->getIdUser()) ? ' le CRON de validation automatique Greenpoint' : $historyEntry->getIdUser()->getName() ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_CLOSED_LENDER_REQUEST: ?>
                                <tr>
                                    <td>
                                        Compte clôturé à la demande du prêteur (mis hors ligne)<br>
                                        le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                        par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?>
                                    </td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_CLOSED_BY_UNILEND: ?>
                                <tr>
                                    <td>
                                        Compte clôturé par Unilend (mis hors ligne)<br>
                                        le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                        par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?><br>
                                        <?= $historyEntry->getContent() ?>
                                    </td>
                                </tr>
                                <?php break;
                            case ClientsStatus::STATUS_CLOSED_DEFINITELY: ?>
                                <tr>
                                    <td>
                                        Compte définitvement fermé le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                        <?= $historyEntry->getContent() ?><br>
                                        par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?>
                                    </td>
                                </tr>
                                <?php break;
                        }
                    ?>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <h2>Actions</h2>
            <?php $clientStatus = $this->wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId(); ?>
            <?php if (isset($_SESSION['compte_valide']) && $_SESSION['compte_valide']) : ?>
                <div class="row">
                    <div class="form-group col-md-6">
                        <a href="<?= $this->lurl ?>/preteurs/activation" class="btn_link btnBackListe">Revenir à la liste de contôle</a>
                    </div>
                </div>
                <?php unset($_SESSION['compte_valide']); ?>
            <?php endif; ?>

            <?php if (ClientsStatus::STATUS_VALIDATED !== $clientStatus && in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) : ?>
                <div class="row">
                    <div class="form-group col-md-6">
                        <input type="button" id="valider_preteur" class="btn-primary" value="Valider le prêteur">
                    </div>
                </div>
            <?php endif; ?>

            <?php if (in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) : ?>
                <div class="row">
                    <div class="form-group col-md-6">
                        <input type="button"
                        onclick="if (confirm('Voulez-vous clôturer le compte à l’initiative d’Unilend ?')){window.location = '<?= $this->lurl ?>/preteurs/status/<?= $this->client->getIdClient() ?>/close_unilend';}"
                        class="btn-primary" style="background: #FF0000; border: 1px solid #FF0000;"
                        value="Clôturer le compte à l’initiative d’Unilend">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <input type="button"
                            onclick="if (confirm('Voulez-vous clôturer le compte à la demande du prêteur ?')){window.location = '<?= $this->lurl ?>/preteurs/status/<?= $this->client->getIdClient() ?>/close_lender';}"
                            class="btn-primary" style="background: #FF0000; border: 1px solid #FF0000;"
                            value="Clôturer le compte à la demande du prêteur">
                    </div>
                </div>
            <?php endif; ?>

            <?php if (false === in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) : ?>
                <div class="row">
                    <div class="form-group col-md-6">
                        <input type="button"
                            onclick="if (confirm('Voulez-vous réactiver le compte à son précédent statut ?')){window.location = '<?= $this->lurl ?>/preteurs/status/<?= $this->client->getIdClient() ?>/online';}"
                            class="btn-primary"
                            value="Réactiver le compte à son précédent statut">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>