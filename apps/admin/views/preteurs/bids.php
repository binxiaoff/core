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
<div id="contenu">
    <?php if (empty($this->clients->id_client)) : ?>
        <div class="attention">Attention : Compte <?= $this->params[0] ?> innconu</div>
    <?php elseif (empty($this->wallet)) : ?>
        <div class="attention">Attention : ce compte n’est pas un compte prêteur</div>
    <?php else : ?>
        <div><?= $this->clientStatusMessage ?></div>
        <h1>Historique des bids prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->clients->id_client ?>" class="btn-primary">Consulter Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->clients->id_client ?>" class="btn-primary">Modifier Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->clients->id_client ?>" class="btn-primary">Historique des emails</a>
            <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->clients->id_client ?>" class="btn-primary">Portefeuille & Performances</a>
        </div>
        <p>ID Client : <?= $this->clients->id_client ?></p>
        <div class="date_picker_bids_history" style="width: 500px; background-color: white; border: 1px solid #A1A5A7; border-radius: 10px 10px 10px 10px; margin: 0 auto 20px; padding: 5px;">
            <form method="post" name="history_dates" id="history_dates" enctype="multipart/form-data" action="" target="_parent">
                <fieldset>
                    <table class="formColor">
                        <tr>
                            <th><label for="datepik_1">Début :</label></th>
                            <td><input type="text" name="debut" id="datepik_1" class="input_dp" value="<?= $this->sDisplayDateTimeStart ?>"/></td>
                            <th><label for="datepik_2">Fin :</label></th>
                            <td><input type="text" name="fin" id="datepik_2" class="input_dp" value="<?= $this->sDisplayDateTimeEnd ?>"/></td>
                            <td><input type="submit" value="Filtrer par date" name="send_dates" class="btn-primary"/></td>
                        </tr>
                    </table>
                </fieldset>
            </form>
        </div>
        <?php if (empty($this->bidList)) : ?>
            <p>Aucune enchère n'a été faite sur la période sélectionnée.</p>
        <?php else : ?>
            <div>
                <a href="<?= $this->lurl ?>/preteurs/extract_bids_csv/<?= $this->clients->id_client ?>" class="btn-primary pull-right">Récupération du CSV</a>
            </div>
            <div>
                <table class="tablesorter">
                    <thead>
                    <tr>
                        <th>ID projet</th>
                        <th>ID bid</th>
                        <th>Date bid</th>
                        <th>Statut bid</th>
                        <th>Montant</th>
                        <th>Taux</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->bidList as $bid) : ?>
                        <tr>
                            <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $bid['id_project'] ?>"><?= $bid['id_project'] ?></a></td>
                            <td><?= $bid['id_bid'] ?></td>
                            <td><?= \DateTime::createFromFormat('Y-m-d H:i:s', $bid['added'])->format('d/m/Y H:i:s') ?></td>
                            <td><?= $bid['status'] ?></td>
                            <td class="text-right"><?= $this->ficelle->formatNumber($bid['amount'], 0) ?> €</td>
                            <td><?= $this->ficelle->formatNumber($bid['rate'], 1) ?> %</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
