<?php
$bankAccount = $this->wireTransferOut->getBankAccount();
if (null === $bankAccount) {
    $bankAccount = $this->bankAccountRepository->getClientValidatedBankAccount($this->wireTransferOut->getClient());
}
$beneficiary        = $bankAccount->getIdClient();
$beneficiaryCompany = $this->companyRepository->findOneBy(['idClientOwner' => $beneficiary->getIdClient()]);
?>
<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <h1>Validation du transfert</h1>
    <p>Voulez-vous valider le transfert ci-dessus :
    <?php if ($this->displayWarning) : ?>
        <p class="red">Attention: <?= $beneficiaryCompany->getName() ?> n'a jamais été utilisé comme bénéficiaire d'un transfert.</p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="/transferts/validate_lightbox/<?= $this->params[0] ?>">
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Date</th>
                <th>Bénéficiaire</th>
                <th>Motif</th>
                <th>Montant</th>
                <th>Statut</th>
            </tr>
            </thead>
            <tbody>

            <tr>
                <td><?= $this->wireTransferOut->getTransferAt() === null ? 'Dès validation' : $this->wireTransferOut->getTransferAt()->format('d/m/Y') ?></td>
                <td>
                    <?= $beneficiaryCompany->getName() ?>
                    <?= ' (' . $bankAccount->getIdClient()->getPrenom() . ' ' . $bankAccount->getIdClient()->getNom() . ')' ?>
                </td>
                <td><?= $this->wireTransferOut->getMotif() ?></td>
                <td><?= $this->currencyFormatter->formatCurrency(bcdiv($this->wireTransferOut->getMontant(), 100, 4), 'EUR'); ?></td>
                <td><?= $this->translator->trans('wire-transfer-out_status-' . $this->wireTransferOut->getStatus()) ?></td>
            </tr>
            </tbody>
        </table>
        <br>
        <br>
        <div style="text-align: center">
            <a href="javascript:parent.$.fn.colorbox.close()" class="btn btn_link btnDisabled">Annuler</a>
            <input type="submit" class="btn" value="Valider">
        </div>
    </form>
</div>
