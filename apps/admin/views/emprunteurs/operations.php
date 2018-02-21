<?php

use \Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use \Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;

?>
<?php if (empty($this->operations)) : ?>
    <div>Aucune opération pour l'année sélectionnée</div>
<?php else : ?>
    <table class="tablesorter operation-table">
        <thead>
        <tr>
            <th width="30%">Type d'operation</th>
            <th width="10%">ID projet</th>
            <th width="10%">Date</th>
            <th width="45%">Montant de l'opération</th>
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
                        <?php if (OperationType::BORROWER_WITHDRAW === $operation['label'] && false === empty($operation['third_party_company'])) : ?>
                            - <?= $operation['third_party_company'] ?>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $operation['idProject'] ?>"><?= $operation['idProject'] ?></a></td>
                <td><?= \DateTime::createFromFormat('Y-m-d', $operation['date'])->format('d/m/Y') ?></td>
                <td class="text-right">
                    <strong><?= $this->currencyFormatter->formatCurrency($operation['amount'], 'EUR') ?></strong>
                    <?php if (OperationSubType::BORROWER_COMMISSION_REPAYMENT === $operation['label']) : ?>
                        <em>
                            <br><?= $this->translator->trans('borrower-operation_commission-excl-tax') ?> : <?= $this->currencyFormatter->formatCurrency($operation['netCommission'], 'EUR') ?>
                            <br><?= $this->translator->trans('borrower-operation_vat') ?> : <?= $this->currencyFormatter->formatCurrency($operation['vat'], 'EUR') ?>
                        </em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php $i++; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <script>
        operationTable = $('.operation-table')
        operationTable.tablesorter()

        $('.operation-tooltip').tooltip({
            show: false,
            position: {
                at: 'right center',
                my: 'right center',
            },
            content: function () {
                return $(this).attr('title')
            }
        })
    </script>
<?php endif; ?>
