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
                foreach ($this->lTypeNotifs as $k => $n) {
                    $id_notif = $n['id_client_gestion_type_notif'];
                    if (in_array($id_notif, array(1, 2, 3, 4))) : ?>
                        <tr>
                            <td><p><?= $this->lTypeNotifs[ $id_notif - 1 ]['nom'] ?></p></td>
                            <td>
                                <input type="checkbox" id="immediatement_<?= $id_notif ?>"
                                       name="immediatement_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['immediatement'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="immediatement_<?= $id_notif ?>"></label>
                            </td>
                            <td>
                                <input type="checkbox" id="quotidienne_<?= $id_notif ?>"
                                       name="quotidienne_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['quotidienne'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="quotidienne_<?= $id_notif ?>"></label>
                            </td>
                            <td>
                                <?php if (!in_array($id_notif, array(2, 3))) : ?>
                                    <input type="checkbox" id="hebdomadaire_<?= $id_notif ?>"
                                           name="hebdomadaire_<?= $id_notif ?>" <?= (in_array($id_notif, array(2)) ? 'class="check-delete" disabled checked' : ($this->NotifC[ $id_notif ]['hebdomadaire'] == 1 ? 'checked' : '')) ?>
                                           disabled/>
                                    <label for="hebdomadaire_<?= $id_notif ?>"></label>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!in_array($id_notif, array(1, 2, 3))) : ?>
                                    <input type="checkbox" id="mensuelle_<?= $id_notif ?>"
                                           name="mensuelle_<?= $id_notif ?>" <?= (in_array($id_notif, array(1, 2)) ? 'class="check-delete" disabled checked' : ($this->NotifC[ $id_notif ]['mensuelle'] == 1 ? 'checked' : '')) ?>
                                           disabled/>
                                    <label for="mensuelle_<?= $id_notif ?>"></label>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="radio" id="uniquement_notif_<?= $id_notif ?>"
                                       name="uniquement_notif_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['uniquement_notif'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="uniquement_notif_<?= $id_notif ?>"></label>
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
                foreach ($this->lTypeNotifs as $k => $n) {
                    $id_notif = $n['id_client_gestion_type_notif'];
                    if (in_array($id_notif, array(5))) : ?>
                        <tr>
                            <td><p><?= $this->lTypeNotifs[ $id_notif - 1 ]['nom'] ?></p></td>
                            <td>
                                <input type="radio" id="immediatement_<?= $id_notif ?>"
                                       name="immediatement_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['immediatement'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="immediatement_<?= $id_notif ?>"></label>
                            </td>

                            <td>
                                <input type="checkbox" id="quotidienne_<?= $id_notif ?>"
                                       name="quotidienne_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['quotidienne'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="quotidienne_<?= $id_notif ?>"></label>
                            </td>
                            <td>
                                <input type="checkbox" id="hebdomadaire_<?= $id_notif ?>"
                                       name="hebdomadaire_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['hebdomadaire'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="hebdomadaire_<?= $id_notif ?>"></label>
                            </td>
                            <td>
                                <input type="checkbox" id="mensuelle_<?= $id_notif ?>"
                                       name="mensuelle_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['mensuelle'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="mensuelle_<?= $id_notif ?>"></label>
                            </td>
                            <td>
                                <div class="form-controls">
                                    <input type="radio" id="uniquement_notif_<?= $id_notif ?>"
                                           name="uniquement_notif_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['uniquement_notif'] == 1 ? 'checked' : '') ?>
                                           disabled/>

                                    <label for="uniquement_notif_<?= $id_notif ?>"></label>
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
                foreach ($this->lTypeNotifs as $k => $n) {
                    $id_notif = $n['id_client_gestion_type_notif'];
                    if (in_array($id_notif, array(6, 7, 8))) : ?>
                        <tr>
                            <td>
                                <p><?= $this->lTypeNotifs[ $id_notif - 1 ]['nom'] ?></p></td>
                            <td>
                                <input type="checkbox" id="immediatement_<?= $id_notif ?>"
                                       name="immediatement_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['immediatement'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="immediatement_<?= $id_notif ?>"></label>
                            </td>
                            <td colspan="3"></td>
                            <td>
                                <input type="radio" id="uniquement_notif_<?= $id_notif ?>"
                                       name="uniquement_notif_<?= $id_notif ?>" <?= ($this->NotifC[ $id_notif ]['uniquement_notif'] == 1 ? 'checked' : '') ?>
                                       disabled/>
                                <label for="uniquement_notif_<?= $id_notif ?>"></label>
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
