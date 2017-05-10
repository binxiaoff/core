<script>
  $(function() {
    $('body').on('click', '.status-line', function() {
      $(this).toggleClass('expand').nextUntil('.status-line').slideToggle(0)
    })
  })
</script>
<style>
    table.tablesorter thead tr th {
        font-size: 12px;
    }
    table.tablesorter.projects tbody tr td.partner-logo {
        padding: 1px;
        text-align: center;
        vertical-align: middle;
    }
    table.tablesorter.projects tbody tr td.partner-logo img {
        margin: 0;
        max-height: 20px;
        max-width: 20px;
    }
    .status-line,
    .projects td[data-project] {
        cursor: pointer;
    }
    .projects .status-line td {
        background-color: #6d1f4f;
        color: #fff;
        font-size: 13px;
    }
    .status-line .sign:after{
        content: '+';
        display: inline-block;
    }
    .status-line.expand .sign:after{
        content: '-';
    }
    h1:not(:first-child) {
        margin-top: 20px;
    }
</style>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
<?php
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
if (empty($this->wireTransferOuts)) : ?>
    <h2>Aucun projet en cours</h2>
<?php else : ?>
    <table class="tablesorter projects">
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
        <?php foreach ($this->wireTransferOuts as $status => $wireTransferOuts) : ?>
            <tr class="status-line expand">
                <td colspan="10"><span class="sign"></span> <?= $this->translator->trans('wire-transfer-out_status-' . $status) ?> (<?= count($wireTransferOuts) ?>)</td>
            </tr>
            <?php
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Virements $wireTransferOut */
            $i = 0;
            ?>
            <?php foreach ($wireTransferOuts as $wireTransferOut) : ?>
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
                        <?php if (false === in_array($wireTransferOut->getStatus(), [Virements::STATUS_CLIENT_DENIED, Virements::STATUS_DENIED, Virements::STATUS_VALIDATED, Virements::STATUS_SENT])) : ?>
                            <a href="<?= $this->lurl ?>/transferts/refuse_lightbox/<?= $wireTransferOut->getIdVirement() ?>" class="thickbox cboxElement">
                                <img src="<?= $this->surl ?>/images/admin/delete.png">
                            </a>
                        <?php endif; ?>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <?php if (Virements::STATUS_CLIENT_VALIDATED === $wireTransferOut->getStatus()) : ?>
                            <a href="<?= $this->lurl ?>/transferts/validate_lightbox/<?= $wireTransferOut->getIdVirement() ?>" class="thickbox cboxElement">
                                <img src="<?= $this->surl ?>/images/admin/check.png">
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php ++$i; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
