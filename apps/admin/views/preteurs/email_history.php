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
                opts = {},
                container;
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
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

    <div><?= $this->sClientStatusMessage ?></div>

    <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
    <h2>Préférences Notifications</h2>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Consulter Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Modifier Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Portefeuille & Performances</a>
    </div>
    <div class="form-body">
        <div class="form-row">
            <table>
                <tr>
                    <th width="auto"><span><br>Offres et Projets</span></th>
                    <th width="100px"><br>Immédiatement</th>
                    <th width="100px"><p>Synthèse<br>quotidienne</p></th>
                    <th width="100px"><p>Synthèse<br>hebdomadaire</p></th>
                    <th width="100px"><p>Synthèse<br>Mensuelle</p></th>
                    <th width="100px"><p>Uniquement<br>notification</p></th>
                </tr>
                <?php foreach ($this->aTypesOfNotifications as $aNotificationType) : ?>
                    <?php if (
                        in_array($aNotificationType['id_client_gestion_type_notif'], array(
                            \clients_gestion_type_notif::TYPE_NEW_PROJECT,
                            \clients_gestion_type_notif::TYPE_BID_PLACED,
                            \clients_gestion_type_notif::TYPE_BID_REJECTED,
                            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED
                        ))
                    ) : ?>
                        <tr>
                            <td><p><?= $aNotificationType['nom'] ?></p></td>
                            <td>
                                <input type="checkbox"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['immediatement'] ? ' checked' : '') ?> disabled="disabled" />
                            </td>
                            <td>
                                <input type="checkbox" <?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['quotidienne'] ? ' checked' : '') ?> disabled="disabled" />
                            </td>
                            <td>
                                <?php if (false === in_array($aNotificationType['id_client_gestion_type_notif'], array(
                                        \clients_gestion_type_notif::TYPE_BID_PLACED,
                                        \clients_gestion_type_notif::TYPE_BID_REJECTED
                                    ))
                                ) : ?>
                                    <input type="checkbox"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['hebdomadaire'] ? ' checked' : '') ?> disabled="disabled" />
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (
                                    false === in_array($aNotificationType['id_client_gestion_type_notif'], array(
                                        \clients_gestion_type_notif::TYPE_NEW_PROJECT,
                                        \clients_gestion_type_notif::TYPE_BID_PLACED,
                                        \clients_gestion_type_notif::TYPE_BID_REJECTED
                                    ))
                                ) : ?>
                                    <input type="checkbox"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['mensuelle'] ? ' checked' : '') ?> disabled="disabled" />
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="radio"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['uniquement_notif'] ? ' checked' : '') ?> disabled="disabled" />
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <tr>
                    <th><span>Remboursements</span></th>
                </tr>
                <?php foreach ($this->aTypesOfNotifications as $aNotificationType) : ?>
                    <?php if (in_array($aNotificationType['id_client_gestion_type_notif'],
                        array(\clients_gestion_type_notif::TYPE_REPAYMENT,
                              \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM))) : ?>
                        <tr>
                            <td><p><?= $aNotificationType['nom'] ?></p></td>
                            <td>
                                <input type="checkbox"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['immediatement'] ? ' checked' : '') ?> disabled="disabled"/>
                            </td>
                            <td>
                                <?php if ($aNotificationType['id_client_gestion_type_notif'] != \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM): ?>
                                    <input type="checkbox"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['quotidienne'] ? ' checked' : '') ?> disabled="disabled"/>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($aNotificationType['id_client_gestion_type_notif'] != \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM): ?>
                                    <input type="checkbox"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['hebdomadaire'] ? ' checked' : '') ?> disabled="disabled"/>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($aNotificationType['id_client_gestion_type_notif'] != \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM): ?>
                                    <input type="checkbox"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['mensuelle'] ? ' checked' : '') ?> disabled="disabled"/>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="radio"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']['id_client_gestion_type_notif']]['uniquement_notif'] ? ' checked' : '') ?> disabled="disabled"/>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <tr>
                    <th><span>Mouvements sur le compte</span></th>
                </tr>
                <?php foreach ($this->aTypesOfNotifications as $aNotificationType) :
                    if (
                        in_array($aNotificationType['id_client_gestion_type_notif'], array(
                            \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT,
                            \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT,
                            \clients_gestion_type_notif::TYPE_DEBIT
                        ))
                    ) : ?>
                        <tr>
                            <td>
                                <p><?= $aNotificationType['nom'] ?></p></td>
                            <td>
                                <input type="checkbox"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['immediatement'] ? ' checked' : '') ?> disabled="disabled" />
                            </td>
                            <td colspan="3"></td>
                            <td>
                                <input type="radio"<?= (1 == $this->aClientsNotifications[$aNotificationType['id_client_gestion_type_notif']]['uniquement_notif'] ? ' checked' : '') ?> disabled="disabled" />
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <H2>Historique des Emails</H2>
    <p>(envoyés à l'adresse email : <?= $this->clients->email ?>)</p>
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
            <th>Type de Mail</th>
            <th>Sujet</th>
            <th>Date d'envoi</th>
            <th>Visualiser</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->aEmailsSentToClient as $aEmail) : ?>
            <tr>
                <td><?= $aEmail['name'] ?></td>
                <td><?= str_replace('_', ' ', utf8_encode(mb_decode_mimeheader($aEmail['subject']))) ?></td>
                <td><?= $this->dates->formatDateMysqltoFr_HourIn($aEmail['added']) ?></td>
                <td style="text-align: center">
                    <a href="<?= $this->lurl ?>/preteurs/email_history_preview/<?= $aEmail['id_filermails'] ?>" class="thickbox">
                        <img src="<?= $this->surl ?>/images/admin/mail.png" alt="previsualiser" height="13px" width="20px" />
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
