<script type="text/javascript">
    $(function() {
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });

        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });
    });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Detail prêteur</a> -</li>
        <li>Portefeuille & Performances</li>
    </ul>
    <h1>Historique des bids prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>

    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Enchères</a>
        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Consulter Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Modifier Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Portefeuille & Performances</a>
    </div>
    <p>ID Client : <?= $this->clients->id_client ?></p>
    <div class="date_picker_bids_history" style="width:500px;margin: auto;margin-bottom:20px;background-color: white;border: 1px solid #A1A5A7;border-radius: 10px 10px 10px 10px;margin: 0 auto 20px;padding:5px;">
        <form method="post" name="history_dates" id="history_dates" enctype="multipart/form-data" action="" target="_parent">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="datepik_1">Debut :</label></th>
                        <td><input type="text" name="debut" id="datepik_1" class="input_dp" value="<?= $this->sDisplayDateTimeStart ?>"/></td>
                        <th><label for="datepik_2">Fin :</label></th>
                        <td><input type="text" name="fin" id="datepik_2" class="input_dp" value="<?= $this->sDisplayDateTimeEnd ?>"/></td>
                        <td><input type="submit" value="Filtrer par date" name="send_dates" class="btn"/></td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <div style="margin-bottom:20px; float:right;"><a href="<?= $this->lurl ?>/preteurs/extract_bids_csv/<?= $this->lenders_accounts->id_lender_account ?>"  class="btn_link">Récupération du CSV</a></div>
    <table class="tablesorter">
        <thead>
        <tr>
            <th>Id projet</th>
            <th>Id bid</th>
            <th>Date bid</th>
            <th>Statut bid</th>
            <th>Montant</th>
            <th>Taux</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->bidList as $bid) : ?>
            <tr>
                <td><?= $bid['id_project'] ?></td>
                <td><?= $bid['id_bid'] ?></td>
                <td><?= $bid['added'] ?></td>
                <td><?= $bid['status'] ?></td>
                <td><?= $bid['amount'] ?> €</td>
                <td><?= $bid['rate'] ?> %</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
