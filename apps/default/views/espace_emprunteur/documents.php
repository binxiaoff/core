<style>
    .documents table {
        table-layout: fixed;
        margin-bottom: 30px;
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        border-radius: 3px;
    }

    .documents td {
        width: 300px;
        text-align: left;
        vertical-align: middle;
        padding: 5px;
    }

    .documents th {
        text-align: left;
        vertical-align: middle;
        padding: 5px;
    }

    .factures {
        font-size: 13px;
    }

    .documents tr:nth-child(even) {
        background-color: #f4f4f4;
    }

    .documents thead, .invoices thead {
        height: 40px;
        background: #b10366 none repeat scroll 0 0;
        color: #f4f4f4;
        padding: 4px;
    }

    .invoices td, .invoices th {
        width: 200px;
        text-align: center;
        vertical-align: middle;
        padding: 5px;
    }

</style>

<div class="documents">
    <h2><?= $this->lng['espace-emprunteur']['documents-contractuels'] ?></h2>
    <table class="documents">
        <thead>
        <tr>
            <th><?= $this->lng['espace-emprunteur']['identifiant-projet'] ?></th>
            <th><?= $this->lng['espace-emprunteur']['pouvoir'] ?></th>
            <th><?= $this->lng['espace-emprunteur']['mandat'] ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->aClientsProjects as $iKey => $aProject) : ?>
            <tr class="documents">
                <td><?= $aProject['id_project'] ?></td>
                <td>
                    <?php
                    if (empty($aProject['pouvoir'])) :
                        echo $this->lng['espace-emprunteur']['pouvoir-non-disponible'];
                    else : ?>
                        <a href="<?= $this->lurl . '/pdf/pouvoir/' . $this->clients->hash . '/' . $aProject['id_project'] ?>">
                            <img src="<?= $this->lurl . '/styles/default/images/pdf50.png' ?>"></a>
                        <?= ($aProject['pouvoir'][0]['status'] > 0) ? $this->lng['espace-emprunteur']['pouvoir-signe'] : $this->lng['espace-emprunteur']['pouvoir-a-signer'] ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    if (empty($aProject['mandat'])) :
                        echo $this->lng['espace-emprunteur']['mandat-non-disponible'];
                    else: ?>
                        <a href="<?= $this->lurl . '/pdf/mandat/' . $this->clients->hash . '/' . $aProject['id_project'] ?>">
                            <img src="<?= $this->lurl . '/styles/default/images/pdf50.png' ?>"></a>
                        <?= $this->lng['espace-emprunteur'][ $aProject['mandat'][0]['status-trad'] ] ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>


</div>
<div class="invoices">
    <h2><?= $this->lng['espace-emprunteur']['factures'] ?></h2>
    <table class="invoices" width="100%">
        <thead>
        <tr>
            <th>
                <div class="th-wrap">
                    <i class="tooltip-anchor icon-double"></i>
                    <div><?= $this->lng['espace-emprunteur']['no-facture'] ?></div>
                </div>
            </th>
            <th>
                <div class="th-wrap">
                <i class="icon-person tooltip-anchor" style="margin-left:-15px;"></i>
                    <div><?= $this->lng['espace-emprunteur']['identifiant-projet'] ?></div>
                </div>
                </th>
            <th>
                <div class="th-wrap">
                    <i class="icon-calendar tooltip-anchor" style="margin-left:-15px;" ></i>
                    <div><?= $this->lng['espace-emprunteur']['date-facture'] ?></div>
                </div>

            </th>
            <th>
                <div class="th-wrap">
                <i title="<?= $this->lng['preteur-operations-pdf']['info-titre-bon-caisse'] ?>" class="tooltip-anchor icon-bdc"></i>
                    <div><?= $this->lng['espace-emprunteur']['facture'] ?></div>
                    </div>
                </th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->aClientsInvoices as $aInvoice) : ?>
            <tr class="factures">
                <td><?= $aInvoice['num_facture'] ?></td>
                <td><?= $aInvoice['id_project'] ?></td>
                <td><?= $this->dates->formatDateMysqltoShortFR($aInvoice['date']) ?></td>
                <td><a class="tooltip-anchor icon-pdf" href="<?= $aInvoice['url'] ?>"></a>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
