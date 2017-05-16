<style>
    #factures td, #factures th {
        white-space: nowrap;
    }
</style>
<div id="popup" width="80%">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <div id="popup-content">
        <?php if (empty($this->aProjectInvoices)) : ?>
            Il n'y pas de facture pour ce projet.
        <?php else : ?>
            <table class="tablesorter listeProjets" id="factures">
                <thead>
                    <tr>
                        <th>N° de la facture</th>
                        <th>Date de la facture</th>
                        <th>Montant HT</th>
                        <th>Montant TTC</th>
                        <th>Télécharger</th>
                        <th>Type de facture</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->aProjectInvoices as $aProjectInvoice) : ?>
                        <tr>
                            <td><?= $aProjectInvoice['num_facture'] ?></td>
                            <td><?= $this->dates->formatDateMysqltoFr($aProjectInvoice['date']) ?></td>
                            <td><?= $this->ficelle->formatNumber($aProjectInvoice['montant_ht']/100) ?> € </td>
                            <td><?= $this->ficelle->formatNumber($aProjectInvoice['montant_ttc']/100) ?> € </td>
                            <td align="center">
                                <a href="<?= $aProjectInvoice['url'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/pdf.png" alt="télécharger la facture"/>
                                </a>
                            </td>
                            <td><?= $aProjectInvoice['type_commission'] == \Unilend\Bundle\CoreBusinessBundle\Entity\Factures::TYPE_COMMISSION_FUNDS ? 'Financement' : 'Remboursement' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
