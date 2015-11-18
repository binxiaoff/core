<style>
    table {
        table-layout: fixed;
        margin-bottom: 30px;
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        border-radius: 3px;
    }

    td {
        width: 300px;
        text-align: left;
        vertical-align: middle;
        padding: 5px;
    }

    th {
        text-align: left;
        vertical-align: middle;
        padding: 5px;
    }

    .factures {
        font-size: 13px;
    }

    tr:nth-child(even) {
        background-color: #f4f4f4;
    }

    .table-head{
        height: 40px;
        background: #b10366 none repeat scroll 0 0;
        color: #f4f4f4;
        padding: 4px;
    }

</style>

<div class="wrapper">
    <div class="shell">
        <div class="documents">
            <h2><?=$this->lng['espace-emprunteur']['documents-contractuels']?></h2>
            <table>
                <tr class="table-head">
                    <th><?=$this->lng['espace-emprunteur']['identifiant-projet']?></th>
                    <th><?=$this->lng['espace-emprunteur']['pouvoir']?></th>
                    <th><?=$this->lng['espace-emprunteur']['mandat']?></th>
                </tr>
                <?php
                foreach ($this->aClientsProjects as $iKey => $aProject) { ?>
                    <tr class="documents">
                        <td><?= $aProject['id_project'] ?></td>
                        <td><a href="<?= $this->lurl . $aProject['pouvoir'][0]['url_pdf'] ?>"><img
                                    src="https://dev.www.unilend.fr/styles/default/images/pdf50.png"></a></td>
                        <td>
                            <a href="<?= $this->lurl . $aProject['mandat'][0]['url_pdf'] ?>">
                                <img src="https://dev.www.unilend.fr/styles/default/images/pdf50.png"></a>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>


        </div>
        <div class="invoices">
            <h2><?=$this->lng['espace-emprunteur']['factures']?></h2>
            <table>
                <tr class="table-head">
                    <th><?=$this->lng['espace-emprunteur']['no-facture']?></th>
                    <th><?=$this->lng['espace-emprunteur']['identifiant-projet']?></th>
                    <th><?=$this->lng['espace-emprunteur']['date-facture']?></th>
                    <th></th>
                </tr>
                <?php foreach ($this->aClientsInvoices as $aInvoice) { ?>
                    <tr class="factures">
                        <td><?= $aInvoice['num_facture'] ?></td>
                        <td><?= $aInvoice['id_project'] ?></td>
                        <td><?= $aInvoice['date'] ?></td>
                        <td><a class="tooltip-anchor icon-pdf" href="<?= $aInvoice['url'] ?>">
                                </a>
                    </tr>
                    <?php
                } ?>
            </table>
        </div>
    </div>
</div>