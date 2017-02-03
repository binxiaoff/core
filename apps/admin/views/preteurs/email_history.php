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

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {};

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <?php if (empty($this->clients->id_client)) : ?>
        <div class="attention">Attention : Compte <?= $this->params[0] ?> innconu</div>
    <?php else : ?>
        <div><?= $this->sClientStatusMessage ?></div>
        <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Enchères</a>
            <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Consulter Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Modifier Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Portefeuille & Performances</a>
        </div>
        <p>ID Client : <?= $this->clients->id_client ?></p>
        <h2>Préférences Notifications</h2>
        <div class="form-body">
            <div class="form-row">
                <table>
                    <thead>
                        <tr>
                            <th width="auto"></th>
                            <th width="100px"><br>Immédiatement</th>
                            <th width="100px">Synthèse<br>quotidienne</th>
                            <th width="100px">Synthèse<br>hebdomadaire</th>
                            <th width="100px">Synthèse<br>Mensuelle</th>
                            <th width="100px">Uniquement<br>notification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->aInfosNotifications as $sGroup => $aNotificationGroup) : ?>
                            <tr>
                                <th colspan="6"><span><br><?= $aNotificationGroup['title'] ?></span></th>
                            </tr>
                            <?php foreach ($aNotificationGroup['notifications'] as $iTypes => $aNotification) : ?>
                                <tr>
                                    <td><?= $aNotification['title'] ?></td>
                                    <?php foreach ($this->aNotificationPeriode as $sPeriod) : ?>
                                        <td>
                                            <?php if (in_array($sPeriod, $aNotification['available_types'])) : ?>
                                                <?php if (1 == $this->aClientsNotifications[$iTypes][$sPeriod]) : ?>
                                                    <img alt="" src="<?= $this->surl ?>/images/admin/check_on.png">
                                                <?php else : ?>
                                                    <img alt="" src="<?= $this->surl ?>/images/admin/check_off.png">
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <br><br>
        <H2>Historique des Emails</H2>
        <div class="date_picker_email_history">
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
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>Sujet</th>
                    <th>Visualiser</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->aEmailsSentToClient as $aEmail) : ?>
                    <tr>
                        <td><?= $this->dates->formatDate($aEmail['sent_at'], 'd/m/Y H:i') ?></td>
                        <td><?= $aEmail['sender_name'] ?></td>
                        <td><?= $aEmail['subject'] ?></td>
                        <td style="text-align: center">
                            <a href="<?= $this->lurl ?>/preteurs/email_history_preview/<?= $aEmail['id_queue'] ?>" class="thickbox">
                                <img src="<?= $this->surl ?>/images/admin/mail.png" alt="previsualiser" height="13px" width="20px" />
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
