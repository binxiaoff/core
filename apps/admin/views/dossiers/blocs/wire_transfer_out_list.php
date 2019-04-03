<?php

use Unilend\Entity\Virements;

if (count($this->wireTransferOuts) > 0) :
    ?>
    <table class="tablesorter">
        <thead>
        <tr>
            <th>Date</th>
            <th>Bénéficiaire</th>
            <th>Motif</th>
            <th>Montant</th>
            <th>Statut</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        /** @var \Unilend\Entity\Virements $wireTransferOut */
        $i = 0;
        ?>
        <?php foreach ($this->wireTransferOuts as $wireTransferOut) : ?>
            <?php
            $bankAccount = $wireTransferOut->getBankAccount();
            if (null === $bankAccount) {
                $bankAccount = $this->bankAccountRepository->getClientValidatedBankAccount($wireTransferOut->getClient());
            }
            $beneficiary        = $bankAccount->getIdClient();
            $beneficiaryCompany = $this->companyRepository->findOneBy(['idClientOwner' => $beneficiary->getIdClient()]);
            ?>
            <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                <td><?= $wireTransferOut->getTransferAt() === null ? 'Dès validation' : $wireTransferOut->getTransferAt()->format('d/m/Y') ?></td>
                <td>
                    <?= $beneficiaryCompany->getName() ?>
                    <?= ' (' . $bankAccount->getIdClient()->getPrenom() . ' ' . $bankAccount->getIdClient()->getNom() . ')' ?>
                </td>
                <td><?= $wireTransferOut->getMotif() ?></td>
                <td><?= $this->currencyFormatter->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'); ?></td>
                <td><?= $this->translator->trans('wire-transfer-out_status-' . $wireTransferOut->getStatus()) ?></td>
                <td>
                    <?php if (false === in_array($wireTransferOut->getStatus(),
                            [Virements::STATUS_CLIENT_DENIED, Virements::STATUS_DENIED, Virements::STATUS_VALIDATED, Virements::STATUS_SENT])
                    ) : ?>
                        <a href="<?= $this->lurl ?>/dossiers/refuse_wire_transfer_out_lightbox/<?= $wireTransferOut->getIdVirement() ?>/project/" class="thickbox cboxElement">
                            <img src="<?= $this->surl ?>/images/admin/delete.png">
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php ++$i; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
