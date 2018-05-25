<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;

?>
<h2>Actions</h2>
<?php $clientStatus = $this->wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId(); ?>
<?php if (isset($_SESSION['compte_valide']) && $_SESSION['compte_valide']) : ?>
    <div class="row">
        <div class="form-group col-md-6">
            <a href="<?= $this->lurl ?>/preteurs/activation" class="btn-primary btnBackListe">Revenir à la liste de contôle</a>
        </div>
    </div>
    <?php unset($_SESSION['compte_valide']); ?>
<?php endif; ?>

<?php if (ClientsStatus::STATUS_VALIDATED !== $clientStatus && in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) : ?>
    <form method="post" action="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->client->getIdClient() ?>">
        <div class="row">
            <div class="form-group col-md-6">
                <?php if (null !== $this->currentBankAccount && null === $this->currentBankAccount->getDateValidated()) : ?>
                    <input type="hidden" value="<?= $this->currentBankAccount->getId() ?>" name="id_bank_account">
                <?php endif; ?>
                <?php if (null !== $this->lastModifiedAddress && $this->lastModifiedAddress !== $this->client->getIdAddress()) : ?>
                    <input type="hidden" value="<?= $this->lastModifiedAddress->getId() ?>" name="id_last_modified_main_address" id="id_last_modified_main_address">
                <?php endif; ?>
                <input type="submit" id="valider_preteur" class="btn-primary" value="Valider le prêteur" name="valider_preteur">
            </div>
        </div>
    </form>
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