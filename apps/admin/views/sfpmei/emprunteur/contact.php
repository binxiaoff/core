<?php use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount; ?>
<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Coordonnées</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th>Nom</th>
                <td><?= $this->clients->nom ?></td>
            </tr>
            <tr>
                <th>Prénom</th>
                <td><?= $this->clients->prenom ?></td>
            </tr>
            <tr>
                <th>Socité</th>
                <td><?= $this->companies->name ?></td>
            </tr>
            <tr>
                <th>Secteur d'activité</th>
                <?php if ($this->companies->code_naf === \Unilend\Bundle\CoreBusinessBundle\Entity\Companies::NAF_CODE_NO_ACTIVITY || empty($this->companies->sector)) : ?>
                    <td> - </td>
                <?php else : ?>
                    <td><?= $this->translator->trans('company-sector_sector-' . $this->companies->sector) ?></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th>Email</th>
                <td><?= $this->clients->email ?></td>
            </tr>
            <tr>
                <th>Téléphone</th>
                <td><?= empty($this->clients->telephone) ? '-' : $this->clients->telephone ?></td>
            </tr>
            <tr>
                <th>Adresse</th>
                <td>
                    <?= $this->clientAddress ?>
                </td>
            </tr>
            <tr>
                <th>Email de facturation</th>
                <td><?= $this->companies->email_facture ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Informations bancaires en vigueur</th>
            </tr>
            </thead>
            <tbody>
            <?php if (false === empty($this->currentBankAccount)) : ?>
                <tr>
                    <th>IBAN</th>
                    <td><?= $this->currentBankAccount->getIban() ?></td>
                </tr>
                <tr>
                    <th>BIC</th>
                    <td><?= $this->currentBankAccount->getBic() ?></td>
                </tr>
            <?php else : ?>
                <tr>
                    <td colspan="2">Pas de RIB en vigueur.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th colspan="2">Autres RIB</th>
            </tr>
            </thead>
            <tbody>
            <?php if (false === empty($this->bankAccountDocuments)) : ?>
                <?php foreach ($this->bankAccountDocuments as $attachment) : ?>
                    <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount $bankAccount */ ?>
                    <?php $bankAccount = $attachment->getBankAccount(); ?>
                    <?php if (null === $bankAccount || BankAccount::STATUS_PENDING === $bankAccount->getStatus() || BankAccount::STATUS_ARCHIVED === $bankAccount->getStatus()) : ?>
                        <tr>
                            <th>Document</th>
                            <td>
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $attachment->getId() ?>/file/<?= urlencode($attachment->getPath()) ?>">
                                    <?= empty($attachment->getOriginalName()) ? $attachment->getPath() : $attachment->getOriginalName() ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>IBAN</th>
                            <td><?= empty($bankAccount) ? '' : $bankAccount->getIban() ?></td>
                        </tr>
                        <tr>
                            <th>BIC</th>
                            <td><?= empty($bankAccount) ? '' : $bankAccount->getBic() ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3">Pas de RIB.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
