<script type="text/javascript">
    <?php
    if(isset($_SESSION['freeow'])) : ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php
    unset($_SESSION['freeow']);
    endif; ?>
</script>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Detail prêteur</a> -</li>
        <li>Portefeuille & Performances</li>
    </ul>

    <?php
    // a controler
    if ($this->clients_status->status == 10) : ?>
        <div class="attention">
            Attention : compte non validé - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?php
    elseif (in_array($this->clients_status->status, array(20, 30, 40))) :
        ?>
        <div class="attention" style="background-color:#F9B137">
            Attention : compte en complétude - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?php
    elseif (in_array($this->clients_status->status, array(50))) :
        ?>
        <div class="attention" style="background-color:#F2F258">
            Attention : compte en modification - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?php
    endif;
    ?>


    <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>

    <h2>Préférences Notifications</h2>
    <div class="btnDroite">
        <a
            href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>"
            class="btn_link">Consulter Prêteur</a>
        <a
            href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>"
            class="btn_link">Modifier Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Portefeuille & Performances</a>
    </div>
    <div class="form-body">
        <div class="form-row">
            <table>
                <tr>
                    <th width="auto"><span><br>Offres et Projets</span></th>
                    <th width="100px"><br>Immédiatement</th>
                    <th width="100px">
                        <p>Synthèse<br>quotidienne</p>
                    </th>
                    <th width="100px">
                        <p>Synthèse<br>hebdomadaire</p>
                    </th>
                    <th width="100px">
                        <p>Synthèse<br>Mensuelle</p>
                    </th>
                    <th width="100px">
                        <p>Uniquement<br>notification</p>
                    </th>
                </tr>
                <?php
                foreach ($this->aTypesOfNotifications as $aNotificationType) {
                    if (in_array($aNotificationType['id_client_gestion_type_notif'], array(\clients_gestion_type_notif::TYPE_NEW_PROJECT, \clients_gestion_type_notif::TYPE_BID_PLACED, \clients_gestion_type_notif::TYPE_BID_REJECTED, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED))) : ?>
                        <tr>
                            <td><p><?= $aNotificationType['nom'] ?></p></td>
                            <td>
                                <input
                                    type="checkbox" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['immediatement'] == 1 ? 'checked' : '') ?>
                                    disabled/>
                            </td>
                            <td>
                                <input
                                    type="checkbox" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['quotidienne'] == 1 ? 'checked' : '') ?>
                                    disabled/>
                            </td>
                            <td>
                                <?php
                                    if (false === in_array($aNotificationType['id_client_gestion_type_notif'], array(\clients_gestion_type_notif::TYPE_BID_PLACED, \clients_gestion_type_notif::TYPE_BID_REJECTED))) : ?>
                                    <input type="checkbox"
                                        <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['hebdomadaire'] == 1 ? 'checked' : '') ?>
                                           disabled/>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    if ((int)$aNotificationType['id_client_gestion_type_notif'] === \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED) : ?>
                                    <input type="checkbox"
                                        <?= $this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['mensuelle'] == 1 ? 'checked' : '' ?>
                                           disabled/>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="radio"
                                    <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['uniquement_notif'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                            </td>
                        </tr>
                        <?php
                    endif;
                }
                ?>
                <tr>
                    <th><span>Remboursements</span></th>
                </tr>
                <?php
                foreach ($this->aTypesOfNotifications as $aNotificationType) {
                    if ((int)$aNotificationType['id_client_gestion_type_notif'] === \clients_gestion_type_notif::TYPE_REPAYMENT) : ?>
                        <tr>
                            <td><p><?= $aNotificationType['nom'] ?></p></td>
                            <td>
                                <input
                                    type="checkbox" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['immediatement'] == 1 ? 'checked' : '') ?>
                                    disabled/>
                            </td>

                            <td>
                                <input
                                    type="checkbox" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['quotidienne'] == 1 ? 'checked' : '') ?>
                                    disabled/>
                            </td>
                            <td>
                                <input
                                    type="checkbox" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['hebdomadaire'] == 1 ? 'checked' : '') ?>
                                    disabled/>
                            </td>
                            <td>
                                <input
                                    type="checkbox" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['mensuelle'] == 1 ? 'checked' : '') ?>
                                    disabled/>
                            </td>
                            <td>
                                <div class="form-controls">
                                    <input
                                        type="radio" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif']['id_client_gestion_type_notif'] ]['uniquement_notif'] == 1 ? 'checked' : '') ?>
                                        disabled/>
                            </td>
                        </tr>
                        <?php
                    endif;
                }
                ?>
                <tr>
                    <th><span>Mouvements sur le compte</span></th>
                </tr>
                <?php
                foreach ($this->aTypesOfNotifications as $aNotificationType) {
                    if (in_array($aNotificationType['id_client_gestion_type_notif'], array(\clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT, \clients_gestion_type_notif::TYPE_DEBIT))) : ?>
                        <tr>
                            <td>
                                <p><?= $aNotificationType['nom'] ?></p></td>
                            <td>
                                <input
                                    type="checkbox" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['immediatement'] == 1 ? 'checked' : '') ?>
                                    disabled/>
                            </td>
                            <td colspan="3"></td>
                            <td>
                                <input
                                    type="radio" <?= ($this->aClientsNotifications[ $aNotificationType['id_client_gestion_type_notif'] ]['uniquement_notif'] == 1 ? 'checked' : '') ?>
                                    disabled/>
                            </td>
                        </tr>
                        <?php
                    endif;
                }
                ?>
            </table>
        </div><!-- /.form-row -->
    </div><!-- /.form-body -->


    <H2>Historique des Emails</H2>
    Et ici viedra la vue des l'historique des mails
    avec une lightbox preview à droite
