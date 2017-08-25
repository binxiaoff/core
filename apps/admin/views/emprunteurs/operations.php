<?php

use \Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use \Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;

?>

<table class="tablesorter operation-table">
    <thead>
    <tr>
        <th width="30%">Type d'operation</th>
        <th width="10%">ID Projet</th>
        <th width="10%">Date</th>
        <th width="45%">Montant de l'opération</th>
        <th width="5%">Solde</th>
    </tr>
    </thead>
    <tbody>
    <?php $i = 1; ?>
    <?php foreach ($this->operations as $operation) : ?>
        <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
            <?php if (OperationType::BORROWER_PROVISION_CANCEL === $operation['label']) : ?>
                <td <?= (false === empty($operation['rejectionIsoCode'])) ? 'class="operation-tooltip" title="' . $operation['rejectionIsoCode'] . ' - ' . $operation['rejectionReasonLabel'] . '"' : '' ?>>
                    <?= $this->translator->trans('borrower-operation_' . $operation['label']) ?>
                    <?= (false === empty($operation['rejectionIsoCode'])) ? '<img src="' . $this->surl . '/images/admin/info.png">' : '' ?>
                </td>
            <?php else : ?>
                <td>
                    <?= $this->translator->trans('borrower-operation_' . $operation['label']) ?>
                    <?php if (OperationType::BORROWER_WITHDRAW === $operation['label']) : ?>
                        - <?= $operation['third_party_company']['name'] ?>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td><?= $operation['idProject'] ?></td>
            <td><?= \DateTime::createFromFormat('Y-m-d', $operation['date'])->format('d/m/Y') ?></td>
            <td>
                <strong><?= $this->currencyFormatter->formatCurrency($operation['amount'], 'EUR') ?></strong>
                <?php if (OperationSubType::BORROWER_COMMISSION_REPAYMENT === $operation['label']) : ?>
                    <em>
                        <br><?= $this->translator->trans('borrower-operation_commission-excl-tax') ?> : <?= $this->currencyFormatter->formatCurrency($operation['netCommission'], 'EUR') ?>
                        <br><?= $this->translator->trans('borrower-operation_vat') ?> : <?= $this->currencyFormatter->formatCurrency($operation['vat'], 'EUR') ?>
                    </em>
                <?php endif; ?>
            </td>
            <td><?= $this->currencyFormatter->formatCurrency($operation['availableBalance'], 'EUR') ?></td>
        </tr>
        <?php $i++; ?>
    <?php endforeach; ?>
    </tbody>
</table>
<script>
    operationTable = $(".operation-table");
    operationTable.tablesorter({headers: {3: {sorter: false}}});

    $('.operation-tooltip').tooltip({
        show: false,
        position: {
            at: 'right center',
            my: 'right center',
        },
        content: function () {
            var content = $(this).attr('title')
            return content
        }
    })
</script>
