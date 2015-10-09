<style type="text/css">
    table.tablesorter tbody td.grisfonceBG, .grisfonceBG {
        background: #D2D2D2;
        text-align: right;
    }

    #contenu_etape4 .input_moy {
        text-align: right;
    }

    .lanote {
        color: #5591EC;
        font-size: 17px;
        font-weight: bold;
    }
</style>
<script type="text/javascript">
    $(document).ready(function () {

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#date").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });
        $("#date_pub").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            minDate: new Date(<?= date('Y') ?>, <?= date('m') - 1 ?>, <?= (date('d')) ?>)
        });
        $("#date_de_retrait").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            minDate: new Date(<?= date('Y') ?>, <?= date('m') - 1 ?>, <?= (date('d')) ?>)
        });
        $("#creation_date_etape2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 40) ?>:<?= (date('Y')) ?>'
        });


        <?php
            if ($this->nb_lignes != ''){
        ?>
                $(".tablesorter").tablesorterPager({
                    container: $("#pager"),
                    positionFixed: false,
                    size: <?= $this->nb_lignes ?>
                });
        <?php } ?>
    });
    <?php
        if (isset($_SESSION['freeow'])) {
    ?>
            $(document).ready(function () {
                var title, message, opts, container;
                title = "<?= $_SESSION['freeow']['title'] ?>";
                message = "<?= $_SESSION['freeow']['message'] ?>";
                opts = {};
                opts.classes = ['smokey'];
                $('#freeow-tr').freeow(title, message, opts);

            });
    <?php
            unset($_SESSION['freeow']);
        }
    ?>
</script>
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>

<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/emprunteurs" title="Emprunteurs">Emprunteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/dossiers" title="Gestion des dossiers">Gestion des dossiers</a> -</li>
        <li>Detail Dossier</li>
    </ul>

    <h1>Detail dossier : <?= $this->projects->title ?></h1>

    <form method="post" name="dossier_resume" id="dossier_resume" enctype="multipart/form-data"
          action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <div id="resume">
            <h2>Resume & actions</h2>

            <div class="gauche">
                <h2>Identité</h2>
                <table class="form" style="width: 580px;">
                    <tr>
                        <th>Lien projet :</th>
                        <td><?= $this->furl . '/projects/detail/' . $this->projects->slug ?></td>
                    </tr>
                    <tr>
                        <th>Date de la demande :</th>
                        <td><?= $this->dates->formatDate($this->projects->added, 'd/m/Y') ?></td>
                    </tr>
                    <tr>
                        <th>Source :</th>
                        <td><?= $this->clients->source ?></td>
                    </tr>
                    <tr>
                        <th><label for="siren">SIREN :</label></th>
                        <td>

                            <?php
                                if ($this->projects->create_bo == 1) {
                                    ?><input type="text" name="siren" id="siren" class="input_large"
                                             value="<?= $this->companies->siren ?>"/><?
                                } else {
                                    ?><input type="hidden" name="siren" id="siren"value="<?= $this->companies->siren ?>"/><?
                                    echo $this->companies->siren;
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rcs">SIRET :</label></th>
                        <td><input type="text" name="siret" id="siret" class="input_large" value="<?= $this->companies->siret ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="rcs">RCS :</label></th>
                        <td><input type="text" name="rcs" id="rcs" class="input_large" value="<?= $this->companies->rcs ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="title_bo">Titre du projet :</label></th>
                        <td><input type="text" name="title_bo" id="title_bo" class="input_large" value="<?= $this->projects->title_bo ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="societe">Nom société :</label></th>
                        <td><input type="text" name="societe" id="societe" class="input_large" value="<?= $this->companies->name ?>"/></td>

                    <tr>
                        <th><label for="title">Titre du projet FO :</label></th>
                        <td><input type="text" name="title" id="title" class="input_large" value="<?= $this->projects->title ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="sector">Secteur de la société :</label></th>
                        <td>
                            <select name="sector" id="sector" class="select">
                                <?php
                                    foreach ($this->lSecteurs as $k => $s) {
                                ?>
                                    <option <?= ($this->companies->sector == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>"><?= $s ?></option>
                                <?php
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tribunal_com">Tribunal de commerce :</label></th>
                        <td><input type="text" name="tribunal_com" id="tribunal_com" class="input_large" value="<?= $this->companies->tribunal_com ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="activite">Activité :</label></th>
                        <td><input type="text" name="activite" id="activite" class="input_large" value="<?= $this->companies->activite ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="lieu_exploi">Lieu exploitation :</label></th>
                        <td><input type="text" name="lieu_exploi" id="lieu_exploi" class="input_large" value="<?= $this->companies->lieu_exploi ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="nature_project">Nature du projet :</label></th>

                        <td><textarea class="textarea_lng" name="nature_project" id="nature_project" style="height: 100px;width: 427px;"><?= $this->projects->nature_project ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="photo_projet">Photo projet :</label></th>
                        <td><input type="file" name="photo_projet" id="photo_projet"/><br/>
                            <a target="_blank" href="<?= $this->surl ?>/images/dyn/projets/source/<?= $this->projects->photo_projet ?>"><?= $this->projects->photo_projet ?></a>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lien_video">Lien vidéo :</label></th>
                        <td>
                            <textarea class="textarea_lng" name="lien_video" id="lien_video" style="height: 100px;width: 427px;"><?= $this->projects->lien_video ?></textarea>
                        </td>
                    </tr>
                </table>
                <br><br>

                <h2>Contact</h2>
                <table class="form" style="width: 495px;">
                    <tr>
                        <th><label for="adresse">Adress correspondant :</label></th>
                        <td><input type="text" name="adresse" id="adresse" class="input_large" value="<?= $this->adresse ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="city">Ville correspondant :</label></th>
                        <td><input type="text" name="city" id="city" class="input_large" value="<?= $this->city ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="zip">Code postal correspondant :</label></th>
                        <td><input type="text" name="zip" id="zip" class="input_court" value="<?= $this->zip ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Téléphone correspondant :</label></th>
                        <td><input type="text" name="phone" id="phone" class="input_moy" value="<?= $this->phone ?>"/></td>
                    </tr>
                </table>
            </div>

            <div class="droite">
                <h2>Montant</h2>
                <table class="form" style="width: 575px;">
                    <tr>
                        <th><label for="montant">Montant du prêt* :</label></th>
                        <td><input style="background-color:#AAACAC;" type="text" name="montant" id="montant" class="input_moy" value="<?= number_format($this->projects->amount, 2, ',', ' ') ?>"/> €</td>
                    </tr>
                    <tr>
                        <th><label for="duree">Durée du prêt* :</label></th>
                        <td>
                            <select name="duree" id="duree" class="select"
                                    style="width:160px;background-color:#AAACAC;">
                                <?php foreach ($this->dureePossible as $duree): ?>
                                    <option <?= ($this->projects->period == $duree ? 'selected' : '') ?>
                                        value="<?= $duree ?>"><?= $duree ?> mois
                                    </option>
                                <?php endforeach ?>
                                <option <?= ($this->projects->period == '1000000' ? 'selected' : '') ?> value="1000000">
                                    je ne sais pas
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr class="content_risk" <?= ($this->current_projects_status->status >= 35 ? '' : 'style="display:none"') ?>>
                        <th><label for="risk">Niveau de risque* :</label></th>
                        <td>
                            <select name="risk" id="risk" class="select" style="width:160px;background-color:#AAACAC;">
                                <option value="">Choisir</option>
                                <option <?= ($this->projects->risk == 'A' ? 'selected' : '') ?> value="A">5 étoiles</option>
                                <option <?= ($this->projects->risk == 'B' ? 'selected' : '') ?> value="B">4,5 étoiles</option>
                                <option <?= ($this->projects->risk == 'C' ? 'selected' : '') ?> value="C">4 étoiles</option>
                                <option <?= ($this->projects->risk == 'D' ? 'selected' : '') ?> value="D">3,5 étoiles</option>
                                <option <?= ($this->projects->risk == 'E' ? 'selected' : '') ?> value="E">3 étoiles</option>
                                <option <?= ($this->projects->risk == 'F' ? 'selected' : '') ?> value="F">2,5 étoiles</option>
                                <option <?= ($this->projects->risk == 'G' ? 'selected' : '') ?> value="G">2 étoiles</option>
                                <option <?= ($this->projects->risk == 'H' ? 'selected' : '') ?> value="H">1,5 étoiles</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rate">Dernière MAJ Altarès :</label></th>
                        <td><?= $this->altares_dateValeur ?></td>
                    </tr>
                    <tr>
                        <th><label for="rate">Risque Altares :</label></th>
                        <td><?= $this->companies->altares_niveauRisque ?></td>
                    </tr>
                    <tr>
                        <th><label for="rate">Score :</label></th>
                        <td><?= $this->companies->altares_scoreVingt ?>/20</td>
                    </tr>
                </table>
                <br><br>

                <h2>Remboursement anticipé / Information</h2>
                <table class="form" style="width: 538px; border: 1px solid #B10366;">
                    <tr>
                        <th>Statut :</th>
                        <td>
                            <label for="statut"><?= $this->phrase_resultat ?></label>
                        </td>
                    </tr>
                    <?php
                        if ($this->virement_recu) {
                    ?>
                        <tr>
                            <th>Virement reçu le :</th>
                            <td>
                                <label for="statut"><?= $this->dates->formatDateMysqltoFr_HourOut($this->receptions->added) ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th>Identification virement :</th>
                            <td>
                                <label for="statut"><?= $this->receptions->id_reception ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th>Montant virement :</th>
                            <td>
                                <label for="statut"><?= ($this->receptions->montant / 100) ?> €</label>
                            </td>
                        </tr>
                        <tr>
                            <th>Motif du virement :</th>
                            <td>
                                <label for="statut"><?= $this->receptions->motif ?></label>
                            </td>
                        </tr>

                    <?php
                        } else {
                        ?>
                        <tr>
                            <th>Virement à émettre avant le :</th>
                            <td>
                                <label for="statut"><?= (isset($this->date_next_echeance_4jouvres_avant)) ? $this->date_next_echeance_4jouvres_avant : '' ?></label>
                            </td>
                        </tr>
                    <?php
                        }
                    ?>
                    <tr>
                        <th>Montant CRD (*) :</th>
                        <td>
                            <label for="statut"><?= $this->montant_restant_du_preteur ?>€</label>
                        </td>
                    </tr>
                    <?php
                        if (false === $this->virement_recu) {
                    ?>
                        <tr>
                            <th>Motif à indiquer sur le virement :</th>
                            <td>
                                <label for="statut">RA-<?= $this->projects->id_project ?></label>
                            </td>
                        </tr>
                    <?php
                        }
                    ?>
                </table>
                <?php
                    if (!$this->virement_recu && !$this->remb_anticipe_effectue && isset($this->date_next_echeance)) {
                ?>
                    * : Le montant correspond aux CRD des échéances restantes après celle du <?= $this->date_next_echeance ?> qui sera prélevé normalement
                <?php
                    }
                ?>

                <br><br><br><br>

                <h2>Actions</h2>
                <table class="form" style="width: 538px;">
                    <tr>
                        <th>Afficher projet :</th>
                        <td>
                            <input <?= ($this->projects->display == '0' ? 'checked' : '') ?> type="radio" name="display_project" id="oui_display_project" value="0"/>
                            <label for="oui_display_project">Oui</label>
                            <input <?= ($this->projects->display == '1' ? 'checked' : '') ?> type="radio" name="display_project" id="non_display_project" value="1"/>
                            <label for="non_display_project">Non</label>
                        </td>
                    </tr>
                    <tr>
                        <th>Id dossier :</th>
                        <td><?= $this->projects->id_project ?></td>
                    </tr>
                    <tr>
                        <th>id emprunteur:</th>
                        <td>
                            <?= $this->clients->id_client ?>
                            <input id="id_client" type="hidden" value="<?= $this->clients->id_client ?>" name="id_client">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="prenom">Prénom :</label></th>
                        <td>
                            <input id="prenom" name="prenom" class="input_large" type="text" value="<?= $this->clients->prenom ?>">
                        </td>
                        <td class="align-right">
                            <input id="search" class="input_moy" type="text" value="" name="search">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nom">Nom :</label></th>
                        <td><input id="nom" name="nom" class="input_large" type="text" value="<?= $this->clients->nom ?>"></td>
                        <td class="align-right">
                            <a id="link_search" class="btn_link thickbox" onclick="$(this).attr('href', '<?= $this->lurl ?>/dossiers/changeClient/' + $('#search').val());" href="<?= $this->lurl ?>/dossiers/changeClient/">Rechercher</a>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="analyste">Analyste :</label></th>
                        <td>
                            <select name="analyste" id="analyste" class="select">
                                <option value="0">Choisir</option>
                                <?php
                                    foreach ($this->lUsers as $u) {
                                ?>
                                        <option <?= ($this->projects->id_analyste == $u['id_user'] ? 'selected' : '') ?> value="<?= $u['id_user'] ?>"><?= $u['firstname'] ?> <?= $u['name'] ?></option>
                                <?php
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status">Statut :</label></th>
                        <td id="current_statut">
                            <?php
                                if ($this->current_projects_status->status == 130) {
                                    echo "Remboursement anticipé";
                                } else {
                                    if (count($this->lProjects_status) > 0) {
                            ?>
                                    <select name="status" id="status" class="select" <?= ($this->current_projects_status->status == 130 ? '"disabled"' : "") ?>>
                                        <?php
                                            foreach ($this->lProjects_status as $s) {
                                        ?>
                                                <option <?= ($this->current_projects_status->status == $s['status'] ? 'selected' : '') ?> value="<?= $s['status'] ?>"><?= $s['label'] ?></option>
                                        <?php
                                            }
                                        ?>
                                    </select>
                            <?php
                                    } else {
                                        ?><input type="hidden" name="status" id="status"
                                                 value="<?= $this->current_projects_status->status ?>" /><?
                                        echo $this->current_projects_status->label;
                                    }
                                }
                            ?>
                        </td>
                    </tr>
                </table>
                <table class="form" style="width: 538px;">
                    <?php
                        if (in_array($this->current_projects_status->status, array(20, 31, 33, 35))) {
                    ?>
                        <tr class="change_statut" <?= ($this->current_projects_status->status == 35 ? '' : 'style="display:none"') ?>>
                            <td colspan="2">
                                Vous devez changer le statut du projet pour ajouter une date de publication et de retrait
                                <div class="block_cache change_statut"></div>
                            </td>
                        </tr>
                    <?php
                        }
                    ?>
                    <tr class="content_date_publicaion" <?= ($this->current_projects_status->status >= 35 ? '' : 'style="display:none"') ?>>
                        <th><label for="date_publication">Date de publication* :</label></th>
                        <td id="date_publication">
                            <?php
                                if (in_array($this->current_projects_status->status, array(20, 31, 33, 35, 40))) {
                            ?>
                                    <input style="background-color:#AAACAC;" type="text" name="date_publication" id="date_pub" class="input_dp" value="<?= ($this->projects->date_publication != '0000-00-00' ? $this->dates->formatDate($this->projects->date_publication, 'd/m/Y') : '') ?>" />
                            <?php
                                // Récupération de la date enregistrée
                                $tab_date_publication_full  = explode(" ", $this->projects->date_publication_full);
                                $tab_date_publication_full2 = explode(":", $tab_date_publication_full[1]);
                                $heure_date_publication     = $tab_date_publication_full2[0];
                                $minute_date_publication    = $tab_date_publication_full2[1];
                                $seconde_date_publication   = $tab_date_publication_full2[2];

                                //Si vide valeur par defaut
                                if ($heure_date_publication == '00') {
                                    $heure_date_publication = $this->HdebutFunding;
                                }
                            ?>
                                &agrave;
                                <select name="date_publication_heure" class="selectMini">
                                    <?php
                                       for ($h = 0; $h < 24; $h++) {
                                    ?>
                                        <option value="<?= (strlen($h) < 2 ? "0" . $h : $h) ?>" <?= ($heure_date_publication == $h ? "selected=selected" : "") ?>><?= (strlen($h) < 2 ? "0" . $h : $h) ?></option>
                                    <?php
                                        }
                                    ?>
                                </select>h

                                <select name="date_publication_minute" class="selectMini">
                                    <?php
                                        for ($m = 0; $m < 60; $m++) {
                                    ?>
                                        <option value="<?= (strlen($m) < 2 ? "0" . $m : $m) ?>" <?= ($minute_date_publication == $m ? "selected=selected" : "") ?>><?= (strlen($m) < 2 ? "0" . $m : $m) ?></option>
                                    <?php
                                        }
                                    ?>
                                </select>

                            <?php
                                } else {
                                    if ($this->projects->date_publication_full == '0000-00-00 00:00:00') {
                                        echo $this->dates->formatDate($this->projects->date_publication, 'd/m/Y') . ' 07:00';
                                    } else {
                                        echo $this->dates->formatDate($this->projects->date_publication_full, 'd/m/Y H:i');
                                    }
                                }
                            ?>
                        </td>
                    </tr>

                    <tr class="content_date_retrait" <?= ($this->current_projects_status->status >= 35 ? '' : 'style="display:none"') ?>>
                        <th><label for="date_retrait">Date de retrait* :</label></th>
                        <td id="date_retrait">
                            <?php
                                if (in_array($this->current_projects_status->status, array(20, 31, 33, 35, 40))) {
                            ?>
                                    <input  style="background-color:#AAACAC;" type="text" name="date_retrait" id="date_de_retrait" class="input_dp" value="<?= ($this->projects->date_retrait != '0000-00-00' ? $this->dates->formatDate($this->projects->date_retrait, 'd/m/Y') : '') ?>" />
                            <?php
                                    // Récupération de la date enregistrée
                                    $tab_date_retrait_full  = explode(" ", $this->projects->date_retrait_full);
                                    $tab_date_retrait_full2 = explode(":", $tab_date_retrait_full[1]);
                                    $heure_date_retrait     = $tab_date_retrait_full2[0];
                                    $minute_date_retrait    = $tab_date_retrait_full2[1];
                                    $seconde_date_retrait   = $tab_date_retrait_full2[2];

                                    // si vide valeur par defaut
                                    if ($heure_date_retrait == '00') {
                                        $heure_date_retrait = $this->HfinFunding;
                                    }
                            ?>
                                &agrave;
                                <select name="date_retrait_heure" class="selectMini">
                                    <?php
                                        for ($h = 0; $h < 24; $h++) {
                                    ?>
                                        <option value="<?= (strlen($h) < 2 ? "0" . $h : $h) ?>" <?= ($heure_date_retrait == $h ? "selected=selected" : "") ?>><?= (strlen($h) < 2 ? "0" . $h : $h) ?></option>
                                    <?php
                                        }
                                    ?>
                                </select>h
                                <select name="date_retrait_minute" class="selectMini">
                                    <?php
                                        for ($m = 0; $m < 60; $m++) {
                                    ?>
                                        <option value="<?= (strlen($m) < 2 ? "0" . $m : $m) ?>" <?= ($minute_date_retrait == $m ? "selected=selected" : "") ?>><?= (strlen($m) < 2 ? "0" . $m : $m) ?></option>
                                    <?php
                                        }
                                    ?>
                                </select>
                            <?php
                                } else {
                                    if ($this->projects->date_publication_full == '0000-00-00 00:00:00') {
                                        echo $this->dates->formatDate($this->projects->date_retrait, 'd/m/Y') . ' 16:00';
                                    } else {
                                        echo $this->dates->formatDate($this->projects->date_retrait_full, 'd/m/Y H:i');
                                    }

                                    if ($this->current_projects_status->status < 60) {
                            ?>
                                    &nbsp;&nbsp;&nbsp;<a href="<?= $this->lurl ?>/thickbox/pop_up_edit_date_retrait/<?= $this->projects->id_project ?>" class="thickbox btn_link ">Modifier</a>
                            <?php
                                    }
                                }
                            ?>
                        </td>
                    </tr>
                    <?php
                        if (isset($this->retour_dates_valides) && $this->retour_dates_valides != "") {
                    ?>
                            <tr class="content_date_retrait">
                                <th></th>
                                <td style="color:red; font-weight:bold;"><?= $this->retour_dates_valides ?></td>
                            </tr>
                    <?php
                        }
                    ?>
                    <tr>
                        <td></td>
                        <td id="status_dossier">
                            <?php
                                if (in_array($this->current_projects_status->status, array(20))) {
                            ?>
                                    <input  type="button" id="status_dosier_valider" class="btn" onClick="check_status_dossierV2(31,<?= $this->projects->id_project ?>);" style="background:#009933;border-color:#009933;font-size:10px;" value="Revue du dossier">
                            <?php
                                }
                                if (in_array($this->current_projects_status->status, array(20))) {
                            ?>
                                    <input type="button" id="status_dosier_rejeter" class="btn" onClick="check_status_dossierV2(30,<?= $this->projects->id_project ?>);" style="background:#CC0000;border-color:#CC0000;font-size:10px;" value="Rejeter dossier">
                            <?php
                                }
                            ?>
                        </td>
                    </tr>
                    <?php
                        if ($this->projects_pouvoir->get($this->projects->id_project, 'id_project') && $this->projects_pouvoir->status == 1) {
                    ?>
                        <tr>
                            <th><label for="pouvoir">Pouvoir :</label></th>

                            <td>
                                <div>
                                    <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $this->projects_pouvoir->name ?>"><?= $this->projects_pouvoir->name ?></a>
                    <?php
                            if ($this->projects_pouvoir->status_remb == '1') {
                                ?><span style="color:green;">&nbsp;Validé</span><?
                            }
                    ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                    <?php
                            if ($this->projects_pouvoir->status_remb == '0' && $this->current_projects_status->status == 60) {
                    ?>
                                <select name="satut_pouvoir" id="satut_pouvoir" class="select">
                                    <option <?= ($this->projects_pouvoir->status_remb == '0' ? 'selected' : '') ?> value="0">En attente</option>
                                    <option <?= ($this->projects_pouvoir->status_remb == '1' ? 'selected' : '') ?> value="1">Validé</option>
                                </select>
                    <?
                            }
                    ?>
                            </td>
                        </tr>
                    <?php
                        } elseif ($this->current_projects_status->status == 60) {// si projet fundé
                    ?>
                        <tr>
                            <th><label for="upload_pouvoir">Pouvoir :</label></th>
                            <td><input type="file" name="upload_pouvoir" id="upload_pouvoir"/></td>
                        </tr>
                    <?php
                        }
                        if ($this->current_projects_status->status == 60) {
                    ?>
                        <tr>
                            <th>Prêt refusé :</th>
                            <td>
                                <select name="pret_refuse" id="pret_refuse" class="select">
                                    <option value="0">Non</option>
                                    <option value="1">Oui</option>
                                </select>
                            </td>
                        </tr>
                    <?php
                        }
                    ?>
                </table>
            </div>
            <style>
                .block_cache {
                    background-color: black;
                    height: 80px;
                    left: 0;
                    margin-top: 4px;
                    opacity: 0.50;
                    position: absolute;
                    width: 550px;
                    z-index: 999;
                }
            </style>
            <div style="display:none" class="recharge">
                <script type="text/javascript">
                    $("#status").change(function () {
                        if ($("#status").val() == 40) {
                            $(".change_statut").hide();
                        } else if ($("#status").val() != 80) {
                            $(".change_statut").show();
                        }
                    });
                </script>
            </div>
            <div class="clear"></div>
            <br/><br/>
            <input type="hidden" name="statut_encours" id="statut_encours" value="0">
            <input type="hidden" name="send_form_dossier_resume">
            <div class="btnDroite submitdossier">
                <button type="submit" class="btn">Sauvegarder</button>
            </div>
        </div>
    </form>
    <hr style="border: 2px solid #B10366;">
    <br><br>
    <h2>Mémos</h2>
    <div class="btnDroite"><a href="<?= $this->lurl ?>/dossiers/addMemo/<?= $this->projects->id_project ?>" class="btn_link thickbox">Ajouter un mémo</a></div>
    <br/><br/><br/>
    <div class="btnDroite"><a href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>/altares" class="btn_link">Générer les données Altares</a></div>
    <div id="table_memo">
        <?php
            if (count($this->lProjects_comments) > 0) {
        ?>
            <table class="tablesorter">
                <thead>
                <tr>
                    <th width="120" align="center">Date ajout</th>
                    <th align="center">Contenu</th>
                    <th width="50" align="center">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
        <?php
                $i = 1;
                foreach ($this->lProjects_comments as $p) {
        ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td align="center"><?= $this->dates->formatDate($p['added'], 'd/m/Y H:i:s') ?></td>
                        <td><?= nl2br($p['content']) ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/addMemo/<?= $p['id_project'] ?>/<?= $p['id_project_comment'] ?>" class="thickbox"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier"/></a>
                            <img style="cursor:pointer;" onclick="deleteMemo(<?= $p['id_project_comment'] ?>,<?= $p['id_project'] ?>);" src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer"/>
                        </td>
                    </tr>
        <?php
                    $i++;
                }
        ?>
                </tbody>
            </table>
        <?php
            if ($this->nb_lignes != '') {
        ?>
                <table>
                    <tr>
                        <td id="pager">
                            <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                            <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                            <input type="text" class="pagedisplay"/>
                            <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                            <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                            <select class="pagesize">
                                <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
        <?php
            }
        }
        ?>
    </div>
    <style type="text/css">
        #etape1, #etape2, #etape3, #etape4, #etape5, #etape6, #etape7 {
            border: 2px solid #B10366;
            display: none;
            padding: 10px;
        }

        #title_etape1, #title_etape2, #title_etape3, #title_etape4, #title_etape4bis, #title_etape5, #title_etape6, #title_etape7 {
            cursor: pointer;
            text-align: center;
            background-color: #B10366;
            color: white;
            padding: 5px;
            font-size: 16px;
            font-weight: bold;
        }

        #valid_etape1, #valid_etape2, #valid_etape3, #valid_etape4, #valid_etape5, #valid_etape6, #valid_etape7 {
            display: none;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #009933;
        }

        .table_bilan {
            display: inline;
        }
    </style>
    <br/><br/>
    <div id="lesEtapes">
        <div id="title_etape1">Etape 1</div>
        <div id="etape1">
            <form method="post" name="dossier_etape1" id="dossier_etape1" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
                <table class="form" style="width: 100%;">
                    <tr>
                        <th><label for="montant_etape1">Montant :</label></th>
                        <td><input type="text" name="montant_etape1" id="montant_etape1" class="input_moy" value="<?= number_format($this->projects->amount, 2, ',', ' ') ?>"/> €
                        </td>

                        <th><label for="duree_etape1">Durée du prêt :</label></th>
                        <td>
                            <select name="duree_etape1" id="duree_etape1" class="select">
                                <?php foreach ($this->dureePossible as $duree): ?>
                                    <option <?= ($this->projects->period == $duree ? 'selected' : '') ?>
                                        value="<?= $duree ?>"><?= $duree ?> mois
                                    </option>
                                <? endforeach ?>
                                <option <?= ($this->projects->period == '1000000' ? 'selected' : '') ?> value="1000000">
                                    je ne sais pas
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="siren_etape1">SIREN :</label></th>
                        <td>
                            <?php
                            if ($this->projects->create_bo == 1) {
                                ?><input type="text" name="siren_etape1" id="siren_etape1" class="input_large"
                                         value="<?= $this->companies->siren ?>"/><?
                            } else {
                                ?><input type="hidden" name="siren_etape1" id="siren_etape1"
                                         value="<?= $this->companies->siren ?>"/><?
                                echo $this->companies->siren;
                            }
                            ?>
                        </td>

                        <th></th>
                        <td></td>
                    </tr>
                </table>
                <div id="valid_etape1">Données sauvegardées</div>
                <div class="btnDroite"><input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape1(<?= $this->projects->id_project ?>)"></div>
            </form>
        </div>
        <br/>

        <div id="title_etape2">Etape 2</div>
        <div id="etape2">
            <form method="post" name="dossier_etape2" id="dossier_etape2" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
                <table class="form" style="width: 100%;">
                    <tr>
                        <th><label for="raison_sociale_etape2">Raison sociale :</label></th>
                        <td><input type="text" name="raison_sociale_etape2" id="raison_sociale_etape2" class="input_large" value="<?= $this->companies->name ?>"/></td>
                        <th><label for="forme_juridique_etape2">Forme juridique :</label></th>
                        <td><input type="text" name="forme_juridique_etape2" id="forme_juridique_etape2" class="input_large" value="<?= $this->companies->forme ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="capital_social_etape2">Capital social :</label></th>
                        <td><input type="text" name="capital_social_etape2" id="capital_social_etape2" class="input_large" value="<?= number_format($this->companies->capital, 2, ',', ' ') ?>"/></td>
                        <th><label for="creation_date_etape2">Date de création (jj/mm/aaaa):</label></th>
                        <td><input readonly="readonly" type="text" name="creation_date_etape2" id="creation_date_etape2" class="input_dp" value="<?= $this->dates->formatDate($this->companies->date_creation, 'd/m/Y') ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:left;"><br/>Coordonnées du siège social :</th>
                    </tr>
                    <tr>
                        <th><label for="address_etape2">Adresse :</label></th>
                        <td><input type="text" name="address_etape2" id="address_etape2" class="input_large" value="<?= $this->companies->adresse1 ?>"/></td>
                        <th><label for="ville_etape2">Ville :</label></th>
                        <td><input type="text" name="ville_etape2" id="ville_etape2" class="input_large" value="<?= $this->companies->city ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="postal_etape2">Code postal :</label></th>
                        <td><input type="text" name="postal_etape2" id="postal_etape2" class="input_court" value="<?= $this->companies->zip ?>"/></td>
                        <th><label for="phone_etape2">Téléphone :</label></th>
                        <td><input type="text" name="phone_etape2" id="phone_etape2" class="input_moy" value="<?= $this->companies->phone ?>"/></td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:left;">
                            <input <?= ($this->companies->status_adresse_correspondance == 1 ? 'checked' : '') ?> type="checkbox" name="same_address_etape2" id="same_address_etape2"/>
                            <label for="same_address_etape2">L'adresse de correspondance est la même que l'adresse du siège social </label>
                        </td>
                    </tr>
                    <tr <?= ($this->companies->status_adresse_correspondance == 0 ? '' : 'style="display:none;"') ?> class="same_adresse">
                        <th colspan="4" style="text-align:left;"><br/>Coordonnées de l'adresse de correspondance :</th>
                    </tr>
                    <tr <?= ($this->companies->status_adresse_correspondance == 0 ? '' : 'style="display:none;"') ?> class="same_adresse">
                        <th><label for="adresse_correspondance_etape2">Adresse :</label></th>
                        <td><input type="text" name="adresse_correspondance_etape2" id="adresse_correspondance_etape2" class="input_large" value="<?= $this->clients_adresses->adresse1 ?>"/></td>
                        <th><label for="city_correspondance_etape2">Ville :</label></th>
                        <td><input type="text" name="city_correspondance_etape2" id="city_correspondance_etape2" class="input_large" value="<?= $this->clients_adresses->ville ?>"/></td>
                    </tr>
                    <tr <?= ($this->companies->status_adresse_correspondance == 0 ? '' : 'style="display:none;"') ?>
                        class="same_adresse">
                        <th><label for="zip_correspondance_etape2">Code postal :</label></th>
                        <td><input type="text" name="zip_correspondance_etape2" id="zip_correspondance_etape2" class="input_court" value="<?= $this->clients_adresses->cp ?>"/></td>
                        <th><label for="phone_correspondance_etape2">Téléphone :</label></th>
                        <td><input type="text" name="phone_correspondance_etape2" id="phone_correspondance_etape2" class="input_moy" value="<?= $this->clients_adresses->telephone ?>"/></td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:left;"><br/>Vous êtes :</th>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:left;">
                            <input <?= ($this->companies->status_client == 1 ? 'checked' : '') ?> type="radio" name="enterprise_etape2" id="enterprise1_etape2" value="1"/>
                            <label for="enterprise1_etape2"> Je suis le dirigeant de l'entreprise </label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:left;">
                            <input <?= ($this->companies->status_client == 2 ? 'checked' : '') ?> type="radio" name="enterprise_etape2" id="enterprise2_etape2" value="2"/>
                            <label for="enterprise2_etape2"> Je ne suis pas le dirigeant de l'entreprise mais je bénéficie d'une délégation de pouvoir </label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:left;">
                            <input <?= ($this->companies->status_client == 3 ? 'checked' : '') ?> type="radio" name="enterprise_etape2" id="enterprise3_etape2" value="3"/>
                            <label for="enterprise3_etape2"> Je suis un conseil externe de l'entreprise </label>
                        </td>
                    </tr>
                    <tr <?= ($this->companies->status_client == 3 ? '' : 'style="display:none;"') ?> class="statut_dirigeant3_etape2">
                        <th><label for="status_conseil_externe_entreprise_etape2">Type de conseiller :</label></th>
                        <td>
                            <select name="status_conseil_externe_entreprise_etape2" id="status_conseil_externe_entreprise_etape2" class="select">
                                <option value="0">Choisir</option>
                                <?php
                                    foreach ($this->conseil_externe as $k => $conseil_externe) {
                                ?>
                                        <option <?= ($this->companies->status_conseil_externe_entreprise == $k ? 'selected' : '') ?> value="<?= $k ?>" ><?= $conseil_externe ?></option>
                                <?php
                                    }
                                ?>
                            </select>
                        </td>
                        <th><label for="preciser_conseil_externe_entreprise_etape2">Autre (préciser) :</label></th>
                        <td><input type="text" name="preciser_conseil_externe_entreprise_etape2" id="preciser_conseil_externe_entreprise_etape2" class="input_large" value="<?= $this->companies->preciser_conseil_externe_entreprise ?>"/></td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:left;"><br/>Vos coordonnées :</th>
                    </tr>
                    <tr>
                        <th>Civilité :</th>
                        <td>
                            <input <?= ($this->clients->civilite == 'Mme' ? 'checked' : '') ?> type="radio" name="civilite_etape2" id="civilite1_etape2" value="Mme"/>
                            <label for="civilite1_etape2">Madame</label>
                            <input <?= ($this->clients->civilite == 'M.' ? 'checked' : '') ?> type="radio" name="civilite_etape2" id="civilite2_etape2" value="M."/>
                            <label for="civilite2_etape2">Monsieur</label>
                        </td>
                        <th></th>
                        <td></td>
                    </tr>
                    <tr>
                        <th><label for="nom_etape2">Nom :</label></th>
                        <td><input type="text" name="nom_etape2" id="nom_etape2" class="input_large" value="<?= $this->clients->nom ?>"/></td>
                        <th><label for="prenom_etape2">Prénom :</label></th>
                        <td><input type="text" name="prenom_etape2" id="prenom_etape2" class="input_large" value="<?= $this->clients->prenom ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="fonction_etape2">Fonction :</label></th>
                        <td><input type="text" name="fonction_etape2" id="fonction_etape2" class="input_large" value="<?= $this->clients->fonction ?>"/></td>
                        <th><label for="email_etape2">Email :</label></th>
                        <td><input type="text" name="email_etape2" id="email_etape2" class="input_large" value="<?= $this->clients->email ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="phone_new_etape2">Téléphone :</label></th>
                        <td><input type="text" name="phone_new_etape2" id="phone_new_etape2" class="input_moy" value="<?= $this->clients->telephone ?>"/></td>
                        <th></th>
                        <td></td>
                    </tr>

                    <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?>
                        class="statut_dirigeant_etape2">
                        <th colspan="4" style="text-align:left;"><br/>Identification du dirigeant :</th>
                    </tr>
                    <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?>
                        class="statut_dirigeant_etape2">
                        <th>Civilité :</th>
                        <td>
                            <input <?= ($this->companies->civilite_dirigeant == 'Mme' ? 'checked' : '') ?> type="radio" name="civilite2_etape2" id="civilite21_etape2" value="Mme"/>
                            <label for="civilite21_etape2">Madame</label>
                            <input <?= ($this->companies->civilite_dirigeant == 'M.' ? 'checked' : '') ?> type="radio" name="civilite2_etape2" id="civilite22_etape2" value="M."/>
                            <label for="civilite22_etape2">Monsieur</label>
                        </td>
                        <th></th>
                        <td></td>
                    </tr>
                    <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?>
                        class="statut_dirigeant_etape2">
                        <th><label for="nom2_etape2">Nom :</label></th>
                        <td><input type="text" name="nom2_etape2" id="nom2_etape2" class="input_large" value="<?= $this->companies->nom_dirigeant ?>"/></td>
                        <th><label for="prenom2_etape2">Prénom :</label></th>
                        <td><input type="text" name="prenom2_etape2" id="prenom2_etape2" class="input_large" value="<?= $this->companies->prenom_dirigeant ?>"/></td>
                    </tr>
                    <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?>
                        class="statut_dirigeant_etape2">
                        <th><label for="fonction2_etape2">Fonction :</label></th>
                        <td><input type="text" name="fonction2_etape2" id="fonction2_etape2" class="input_large" value="<?= $this->companies->fonction_dirigeant ?>"/></td>
                        <th><label for="email2_etape2">Email :</label></th>
                        <td><input type="text" name="email2_etape2" id="email2_etape2" class="input_large" value="<?= $this->companies->email_dirigeant ?>"/></td>
                    </tr>
                    <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?>
                        class="statut_dirigeant_etape2">
                        <th><label for="phone_new2_etape2">Téléphone :</label></th>
                        <td><input type="text" name="phone_new2_etape2" id="phone_new2_etape2" class="input_moy" value="<?= $this->companies->phone_dirigeant ?>"/></td>
                        <th></th>
                        <td></td>
                    </tr>
                </table>

                <div id="valid_etape2">Données sauvegardées</div>
                <div class="btnDroite"><input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape2(<?= $this->projects->id_project ?>)"></div>
            </form>
        </div>
        <br/>

        <div id="title_etape3">Etape 3</div>
        <div id="etape3">
            <form method="post" name="dossier_etape3" id="dossier_etape3" enctype="multipart/form-data"
                  action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
                <table class="form" style="width: 100%;">
                    <tr>
                        <th><label for="montant_etape3">Montant :</label></th>
                        <td><input type="text" name="montant_etape3" id="montant_etape3" class="input_large" value="<?= number_format($this->projects->amount, 2, ',', ' ') ?>"/> €
                        </td>

                        <th><label for="duree_etape3">Durée du prêt :</label></th>
                        <td>
                            <select name="duree_etape3" id="duree_etape3" class="select">
                                <?php foreach ($this->dureePossible as $duree): ?>
                                    <option <?= ($this->projects->period == $duree ? 'selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                                <?php endforeach ?>
                                <option <?= ($this->projects->period == '1000000' ? 'selected' : '') ?> value="1000000">
                                    je ne sais pas
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="titre_etape3">Titre projet :</label></th>
                        <td colspan="3"><input style="width:780px;" type="text" name="titre_etape3" id="titre_etape3" class="input_large" value="<?= $this->projects->title ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="objectif_etape3">Objectif du crédit :</label></th>
                        <td colspan="3">
                            <textarea style="width:780px;" name="objectif_etape3" id="objectif_etape3" class="textarea_lng"/><?= $this->projects->objectif_loan ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="presentation_etape3">Présentation de la société :</label></th>
                        <td colspan="3">
                            <textarea style="width:780px;" name="presentation_etape3" id="presentation_etape3" class="textarea_lng"/><?= $this->projects->presentation_company ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="moyen_etape3">Moyen de remboursement prévu :</label></th>
                        <td colspan="3">
                            <textarea style="width:780px;" name="moyen_etape3" id="moyen_etape3" class="textarea_lng"/><?= $this->projects->means_repayment ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="moyen_etape3">Informations utiles :</label></th>
                        <td colspan="3">
                            <textarea style="width:780px;" name="comments_etape3" id="comments_etape3" class="textarea_lng"/><?= $this->projects->comments ?></textarea>
                        </td>
                    </tr>
                </table>


                <div id="valid_etape3">Données sauvegardées</div>
                <div class="btnDroite"><input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape3(<?= $this->projects->id_project ?>)"></div>
            </form>
        </div>
        <br/>

        <div id="title_etape4">Etape 4</div>
        <div id="etape4">
            <script language="javascript" type="text/javascript">
                function formUploadCallbackcsv(result) {
                    console.log("Upload OK:", result);

                    if (result == 'ok') {
                        refeshEtape4(<?= $this->projects->id_project ?>);
                    }

                }
            </script>
            <div style="border: 2px solid #B10366;margin-bottom: 10px;padding: 5px;width: auto; float:right;">
                <form method="post" name="upload_csv" id="upload_csv" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/upload_csv/<?= $this->params[0] ?>" target="upload_csv_target">
                    <input type="hidden" name="send_csv" id="send_csv"/>
                    <input type="file" name="csv" id="csv">
                    <div style="display:inline;"><input type="submit" class="btn_link" value="Upload"></div>
                    <div id="valid_upload_etape4"style="text-align:center;color:#009933;font-weight:bold;display:none;">Upload csv terminé</div>
                    <div style="display:none;">
                        <iframe id="upload_csv_target" name="upload_csv_target" src="#"></iframe>
                    </div>
                </form>
            </div>

            <div class="clear"></div>
            <form method="post" name="dossier_etape4" id="dossier_etape4" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
                <div id="contenu_etape4">
                    <table class="form" style="width: 100%;">
                        <tr>
                            <th>Date du dernier bilan certifié :</th>
                            <td>
                                <select name="jour_etape4" id="jour_etape4" class="select">
                                    <?php
                                    for ($i = 1; $i <= 31; $i++) {
                                        $numjour = (strlen($i) < 2) ? '0' . $i : $i;
                                    ?>
                                        <option <?= ($this->date_dernier_bilan_jour == $i ? 'selected' : '') ?> value="<?= $numjour ?>"><?= $i ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>

                                <select name="mois_etape4" id="mois_etape4" class="select">
                                    <?php
                                    foreach ($this->dates->tableauMois['fr'] as $k => $mois) {
                                        $numMois = (strlen($k) < 2) ? '0' . $k : $k;
                                        if ($k > 0) {
                                            echo '<option ' . ($this->date_dernier_bilan_mois == $numMois ? 'selected' : '') . ' value="' . $numMois . '">' . $mois . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <select name="annee_etape4" id="annee_etape4" class="select">
                                    <?php
                                        for ($i = 2008; $i <= date('Y') + 1; $i++) {
                                    ?>
                                        <option <?= ($this->date_dernier_bilan_annee == $i ? 'selected' : '') ?> value="<?= $i ?>"><?= $i ?></option>
                                    <?php
                                        }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <br/><br/>

                    <!-- bilans -->
                    <?php
                    if (count($this->lbilans) > 0) {
                    ?>
                        <table class="tablesorter" style="text-align:center;">
                            <thead>
                            <th width="200"></th>
                            <?php
                                foreach ($this->lbilans as $b) {
                            ?>
                                    <th><?= $b['date'] ?></th>
                            <?php
                                }
                            ?>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Chiffe d'affaires</td>
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                ?>
                                <td class="<?= ($i < 3 ? 'grisfonceBG' : '') ?>">
                                    <input name="ca_<?= $i ?>" id="ca_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['ca'] != false ? number_format($this->lbilans[$i]['ca'], 2, ',', ' ') : ''); ?>"/>
                                    <input type="hidden" id="ca_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                                    </td>
                                <?php
                                }
                                ?>
                            </tr>
                            <tr>
                                <td>Résultat brut d'exploitation</td>
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                ?>
                                <td class="<?= ($i < 3 ? 'grisfonceBG' : '') ?>">
                                    <input name="resultat_brute_exploitation_<?= $i ?>" id="resultat_brute_exploitation_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['resultat_brute_exploitation'] != false ? number_format($this->lbilans[$i]['resultat_brute_exploitation'], 2, ',', ' ') : ''); ?>"/>
                                    <input type="hidden" id="resultat_brute_exploitation_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                                    </td>
                                <?php
                                }
                                ?>
                            </tr>
                            <tr>
                                <td>Résultat d'exploitation</td>
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                ?>
                                <td class="<?= ($i < 3 ? 'grisfonceBG' : '') ?>">
                                    <input name="resultat_exploitation_<?= $i ?>" id="resultat_exploitation_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['resultat_exploitation'] != false ? number_format($this->lbilans[$i]['resultat_exploitation'], 2, ',', ' ') : ''); ?>"/>
                                    <input type="hidden" id="resultat_exploitation_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                                    </td>
                                <?php
                                }
                                ?>
                            </tr>
                            <tr>
                                <td>Investissements</td>
                                <?php
                                for ($i = 0; $i < 5; $i++) {
                                ?>
                                <td <?= ($i < 3 ? 'class="grisfonceBG"' : '') ?>>
                                    <input name="investissements_<?= $i ?>" id="investissements_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['investissements'] != false ? number_format($this->lbilans[$i]['investissements'], 2, ',', ' ') : ''); ?>"/>
                                    <input type="hidden" id="investissements_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                                    </td>
                                <?php
                                }
                                ?>
                            </tr>
                            </tbody>
                        </table>
                        <?php
                        if ($this->nb_lignes != '') {
                        ?>
                            <table>
                                <tr>
                                    <td id="pager">
                                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                                        <input type="text" class="pagedisplay"/>
                                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                                        <select class="pagesize">
                                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                    <?php
                        }
                    }
                    ?>
                    <br/><br/>
                    <table class="form" style="width: 100%;">
                        <tr>
                            <th><label for="encours_actuel_dette_fianciere">Encours actuel de la dette financière :</label></th>
                            <td>
                                <input type="text" name="encours_actuel_dette_fianciere" id="encours_actuel_dette_fianciere" class="input_moy" value="<?= ($this->companies_details->encours_actuel_dette_fianciere != false ? number_format($this->companies_details->encours_actuel_dette_fianciere, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="remb_a_venir_cette_annee">Remboursements à venir cette année :</label></th>
                            <td>
                                <input type="text" name="remb_a_venir_cette_annee" id="remb_a_venir_cette_annee" class="input_moy" value="<?= ($this->companies_details->remb_a_venir_cette_annee != false ? number_format($this->companies_details->remb_a_venir_cette_annee, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>
                        <tr>
                            <th><label for="remb_a_venir_annee_prochaine">Remboursements à venir l'année prochaine :</label></th>
                            <td>
                                <input type="text" name="remb_a_venir_annee_prochaine" id="remb_a_venir_annee_prochaine" class="input_moy" value="<?= ($this->companies_details->remb_a_venir_annee_prochaine != false ? number_format($this->companies_details->remb_a_venir_annee_prochaine, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="tresorie_dispo_actuellement">Trésorerie disponible actuellement :</label></th>
                            <td>
                                <input type="text" name="tresorie_dispo_actuellement" id="tresorie_dispo_actuellement" class="input_moy" value="<?= ($this->companies_details->tresorie_dispo_actuellement != false ? number_format($this->companies_details->tresorie_dispo_actuellement, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="autre_demandes_financements_prevues">Autres demandes de financements prévues<br/>
                                    (autres que celles que vous réalisez auprès d'Unilend) :</label></th>
                            <td>
                                <input type="text" name="autre_demandes_financements_prevues" id="autre_demandes_financements_prevues" class="input_moy" value="<?= ($this->companies_details->autre_demandes_financements_prevues != false ? number_format($this->companies_details->autre_demandes_financements_prevues, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th></th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>
                                <label for="precisions">Vous souhaitez apporter des précisions <br/> pour nous aider à mieux vous comprendre ? :</label>
                            </th>
                            <td colspan="3">
                                <textarea style="width:350px;" name="precisions" id="precisions" class="textarea"/><?= $this->companies_details->precisions ?></textarea>
                            </td>
                        </tr>
                    </table>
                    <!-- actif / passif-->
                    <style>
                        .actif_passif .input_moy {
                            width: 128px;
                        }
                    </style>
                    <h2>Actif :</h2>
                    <?php
                    if (count($this->lCompanies_actif_passif) > 0) {

                        $arrayBilans[0]['title'] = 'Ordre';
                        $arrayBilans[0]['value'] = '';

                        $arrayBilans[1]['title'] = 'Immobilisations corporelles';
                        $arrayBilans[1]['value'] = 'immobilisations_corporelles';

                        $arrayBilans[2]['title'] = 'Immobilisations incorporelles';
                        $arrayBilans[2]['value'] = 'immobilisations_incorporelles';

                        $arrayBilans[3]['title'] = 'Immobilisations financières';
                        $arrayBilans[3]['value'] = 'immobilisations_financieres';

                        $arrayBilans[4]['title'] = 'Stocks';
                        $arrayBilans[4]['value'] = 'stocks';

                        $arrayBilans[5]['title'] = 'Créances clients';
                        $arrayBilans[5]['value'] = 'creances_clients';

                        $arrayBilans[6]['title'] = 'Disponibilités';
                        $arrayBilans[6]['value'] = 'disponibilites';

                        $arrayBilans[7]['title'] = 'Valeurs mobilières de placement';
                        $arrayBilans[7]['value'] = 'valeurs_mobilieres_de_placement';

                        $arrayBilans[8]['title'] = 'Total';
                        $arrayBilans[8]['value'] = 'totalAnneeAct';

                        $end = end($arrayBilans);

                        $i = 1;
                    ?>
                        <table class="tablesorter actif_passif" style="text-align:center;">
                        <?php
                            foreach ($arrayBilans as $k => $t) {
                                // entete
                                if ($k == 0) {
                        ?>
                                    <thead>
                                    <th width="300"><?= $t['title'] ?></th>
                                    <?php
                                    foreach ($this->lCompanies_actif_passif as $ap) {
                                        ?>
                                        <th><?= $ap['annee'] ?></th><?
                                        if ($i == 3)
                                            break;
                                        else
                                            $i++;
                                    }
                                    ?>
                                    </thead>
                                    <tbody>
                                    <tr>
                                <?php
                                } elseif ($end['title'] != $t['title']) { //corps
                                ?>
                                    <td><?= $t['title'] ?></td>
                                    <?php
                                    $a = 1;
                                    foreach ($this->lCompanies_actif_passif as $ap) {
                                    ?>
                                        <td>
                                        <input name="<?= $t['value'] ?>_<?= $ap['ordre'] ?>"
                                               id="<?= $t['value'] ?>_<?= $ap['ordre'] ?>" type="text" class="input_moy"
                                               value="<?= ($ap[$t['value']] != false ? number_format($ap[$t['value']], 2, '.', '') : ''); ?>"
                                               onkeyup="cal_actif();"/>

                                        </td>
                                    <?php
                                        if ($a == 3) {
                                            break;
                                        } else {
                                            $a++;
                                        }
                                    }
                                    ?>
                                </tr>
                                <tr>
                                <?php
                                } else { //pied
                                ?>
                                    <td><?= $t['title'] ?></td>
                                    <?php
                                    $b = 1;
                                    foreach ($this->lCompanies_actif_passif as $ap) {
                                        $totalAnnee = ($ap[$arrayBilans[1]['value']] +
                                            $ap[$arrayBilans[2]['value']] +
                                            $ap[$arrayBilans[3]['value']] +
                                            $ap[$arrayBilans[4]['value']] +
                                            $ap[$arrayBilans[5]['value']] +
                                            $ap[$arrayBilans[6]['value']] +
                                            $ap[$arrayBilans[7]['value']])
                                        ?>
                                        <td id="<?= $t['value'] ?>_<?= $ap['ordre'] ?>" ><?= $totalAnnee ?></td><?
                                        if ($b == 3)
                                            break;
                                        else
                                            $b++;
                                    }
                                    ?>
                                    </tr>
                                    </tbody>
                                <?php
                                }
                            }
                            ?>
                        </table>
                        <?php
                        }
                        ?>
                    <br/><br/>

                    <h2>Passif :</h2>

                    <?php
                    if (count($this->lCompanies_actif_passif) > 0) {
                    $arrayBilansPassif[0]['title'] = 'Ordre';
                    $arrayBilansPassif[0]['value'] = '';

                    $arrayBilansPassif[1]['title'] = 'Capitaux propres';
                    $arrayBilansPassif[1]['value'] = 'capitaux_propres';

                    $arrayBilansPassif[2]['title'] = 'Provisions pour risques & charges';
                    $arrayBilansPassif[2]['value'] = 'provisions_pour_risques_et_charges';

                    $arrayBilansPassif[3]['title'] = 'Amortissements sur immobilisations';
                    $arrayBilansPassif[3]['value'] = 'amortissement_sur_immo';

                    $arrayBilansPassif[4]['title'] = 'Dettes financières';
                    $arrayBilansPassif[4]['value'] = 'dettes_financieres';

                    $arrayBilansPassif[5]['title'] = 'Dettes fournisseurs';
                    $arrayBilansPassif[5]['value'] = 'dettes_fournisseurs';

                    $arrayBilansPassif[6]['title'] = 'Autres dettes';
                    $arrayBilansPassif[6]['value'] = 'autres_dettes';

                    $arrayBilansPassif[7]['title'] = 'Total';
                    $arrayBilansPassif[7]['value'] = 'totalAnneePass';

                    $end = end($arrayBilansPassif);

                    $i = 1;
                    ?>
                    <table class="tablesorter actif_passif" style="text-align:center;">

                        <?php
                        foreach ($arrayBilansPassif as $k => $t) {

                            // entete
                            if ($k == 0) {
                                ?>
                                <thead>
                                <th width="300"><?= $t['title'] ?></th>
                                <?php
                                foreach ($this->lCompanies_actif_passif as $ap) {
                                    ?>
                                    <th><?= $ap['annee'] ?></th><?
                                    if ($i == 3) {
                                        break;
                                    } else {
                                        $i++;
                                    }
                                }
                                ?>
                                </thead>
                                <tbody>
                                <tr>
                                <?php
                            } elseif ($end['title'] != $t['title']) { //corps
                                ?>
                                <td><?= $t['title'] ?></td>
                                <?php
                                $a = 1;
                                foreach ($this->lCompanies_actif_passif as $ap) {
                                    ?>
                                    <td>
                                        <input name="<?= $t['value'] ?>_<?= $ap['ordre'] ?>"
                                               id="<?= $t['value'] ?>_<?= $ap['ordre'] ?>" type="text" class="input_moy"
                                               value="<?= ($ap[$t['value']] != false ? number_format($ap[$t['value']], 2, '.', '') : ''); ?>"
                                               onkeyup="cal_passif();"/>
                                    </td>
                                    <?php
                                    if ($a == 3) {
                                        break;
                                    } else {
                                        $a++;
                                    }
                                }
                                ?>
                            </tr>
                            <tr>
                                <?php
                            } else { //pied
                                ?>
                                <td><?= $t['title'] ?></td>
                                <?php
                                $b = 1;
                                foreach ($this->lCompanies_actif_passif as $ap) {
                                    $totalAnnee = ($ap[$arrayBilansPassif[1]['value']] +
                                        $ap[$arrayBilansPassif[2]['value']] +
                                        $ap[$arrayBilansPassif[3]['value']] +
                                        $ap[$arrayBilansPassif[4]['value']] +
                                        $ap[$arrayBilansPassif[5]['value']] +
                                        $ap[$arrayBilansPassif[6]['value']])
                                    ?>
                                    <td id="<?= $t['value'] ?>_<?= $ap['ordre'] ?>"><?= $totalAnnee ?></td>
                                    <?php
                                    if ($b == 3) {
                                        break;
                                    } else {
                                        $b++;
                                    }
                                }
                                ?>
                                </tr>
                                </tbody>
                                <?php
                            }
                        }
                        ?>
                    </table>
                    <?php } ?>
                    <br/><br/>
                    <table class="form" style="width: 100%;">
                        <tr>
                            <th><label for="decouverts_bancaires">Découverts bancaires :</label></th>
                            <td>
                                <input type="text" name="decouverts_bancaires" id="decouverts_bancaires" class="input_moy" value="<?= ($this->companies_details->decouverts_bancaires != false ? number_format($this->companies_details->decouverts_bancaires, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="lignes_de_tresorerie">Lignes de trésorerie :</label></th>
                            <td>
                                <input type="text" name="lignes_de_tresorerie" id="lignes_de_tresorerie" class="input_moy" value="<?= ($this->companies_details->lignes_de_tresorerie != false ? number_format($this->companies_details->lignes_de_tresorerie, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>
                        <tr>
                            <th><label for="affacturage">Affacturage :</label></th>
                            <td>
                                <input type="text" name="affacturage" id="affacturage" class="input_moy" value="<?= ($this->companies_details->affacturage != false ? number_format($this->companies_details->affacturage, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="escompte">Escompte :</label></th>
                            <td>
                                <input type="text" name="escompte" id="escompte" class="input_moy" value="<?= ($this->companies_details->escompte != false ? number_format($this->companies_details->escompte, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>
                        <tr>
                            <th><label for="financement_dailly">Financement Dailly :</label></th>
                            <td>
                                <input type="text" name="financement_dailly" id="financement_dailly" class="input_moy" value="<?= ($this->companies_details->financement_dailly != false ? number_format($this->companies_details->financement_dailly, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="credit_de_tresorerie">Crédit de trésorerie :</label></th>
                            <td>
                                <input type="text" name="credit_de_tresorerie" id="credit_de_tresorerie" class="input_moy" value="<?= ($this->companies_details->credit_de_tresorerie != false ? number_format($this->companies_details->credit_de_tresorerie, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>

                        <tr>
                            <th><label for="credit_bancaire_investissements_materiels">Crédit bancaire<br/>investissements matériels :</label></th>
                            <td>
                                <input type="text" name="credit_bancaire_investissements_materiels" id="credit_bancaire_investissements_materiels" class="input_moy" value="<?= ($this->companies_details->credit_bancaire_investissements_materiels != false ? number_format($this->companies_details->credit_bancaire_investissements_materiels, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="credit_bancaire_investissements_immateriels">Crédit bancaire<br/>investissements immatériels :</label></th>
                            <td>
                                <input type="text" name="credit_bancaire_investissements_immateriels" id="credit_bancaire_investissements_immateriels" class="input_moy" value="<?= ($this->companies_details->credit_bancaire_investissements_immateriels != false ? number_format($this->companies_details->credit_bancaire_investissements_immateriels, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>

                        <tr>
                            <th><label for="rachat_entreprise_ou_titres">Rachat d'entreprise ou de titres :</label></th>
                            <td>
                                <input type="text" name="rachat_entreprise_ou_titres" id="rachat_entreprise_ou_titres" class="input_moy" value="<?= ($this->companies_details->rachat_entreprise_ou_titres != false ? number_format($this->companies_details->rachat_entreprise_ou_titres, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="credit_immobilier">Crédit immobilier :</label></th>
                            <td>
                                <input type="text" name="credit_immobilier" id="credit_immobilier" class="input_moy" value="<?= ($this->companies_details->credit_immobilier != false ? number_format($this->companies_details->credit_immobilier, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>

                        <tr>
                            <th><label for="credit_bail_immobilier">Crédit bail immobilier :</label></th>
                            <td>
                                <input type="text" name="credit_bail_immobilier" id="credit_bail_immobilier" class="input_moy" value="<?= ($this->companies_details->credit_bail_immobilier != false ? number_format($this->companies_details->credit_bail_immobilier, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="credit_bail">Crédit bail :</label></th>
                            <td>
                                <input type="text" name="credit_bail" id="credit_bail" class="input_moy" value="<?= ($this->companies_details->credit_bail != false ? number_format($this->companies_details->credit_bail, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>

                        <tr>
                            <th><label for="location_avec_option_achat">Location avec option d'achat :</label></th>
                            <td>
                                <input type="text" name="location_avec_option_achat" id="location_avec_option_achat" class="input_moy" value="<?= ($this->companies_details->location_avec_option_achat != false ? number_format($this->companies_details->location_avec_option_achat, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="location_financiere">Location financière :</label></th>
                            <td>
                                <input type="text" name="location_financiere" id="location_financiere" class="input_moy" value="<?= ($this->companies_details->location_financiere != false ? number_format($this->companies_details->location_financiere, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>

                        <tr>
                            <th><label for="location_longue_duree">Location longue durée :</label></th>
                            <td>
                                <input type="text" name="location_longue_duree" id="location_longue_duree" class="input_moy" value="<?= ($this->companies_details->location_longue_duree != false ? number_format($this->companies_details->location_longue_duree, 2, '.', '') : '') ?>"/> €
                            </td>
                            <th><label for="pret_oseo">Prêt OSEO :</label></th>
                            <td>
                                <input type="text" name="pret_oseo" id="pret_oseo" class="input_moy" value="<?= ($this->companies_details->pret_oseo != false ? number_format($this->companies_details->pret_oseo, 2, '.', '') : '') ?>"/>                                 €
                            </td>
                        </tr>

                        <tr>
                            <th><label for="pret_participatif">Prêt participatif :</label></th>
                            <td>
                                <input type="text" name="pret_participatif" id="pret_participatif" class="input_moy" value="<?= ($this->companies_details->pret_participatif != false ? number_format($this->companies_details->pret_participatif, 2, '.', '') : '') ?>"/> €
                            </td>
                        </tr>

                    </table>
                </div>
                <br/><br/>

                <div id="valid_etape4">Données sauvegardées</div>
                <div class="btnDroite"><input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape4(<?= $this->projects->id_project ?>)"></div>
            </form>
        </div>
        <br/>

        <div id="title_etape5">Etape 5</div>
        <div id="etape5">
            <script language="javascript" type="text/javascript">
                function formUploadCallback(result) {

                    var obj = jQuery.parseJSON(result);
                    var enregistre = '<span style="color:green;">Enregistré</span>';
                    if (obj.fichier1 == 'ok') {
                        $(".statut_fichier1").html(enregistre);
                    }
                    if (obj.fichier2 == 'ok') {
                        $(".statut_fichier2").html(enregistre);
                    }
                    if (obj.fichier3 == 'ok') {
                        $(".statut_fichier3").html(enregistre);
                    }
                    if (obj.fichier4 == 'ok') {
                        $(".statut_fichier4").html(enregistre);
                    }
                    if (obj.fichier5 == 'ok') {
                        $(".statut_fichier5").html(enregistre);
                    }
                    if (obj.fichier6 == 'ok') {
                        $(".statut_fichier6").html(enregistre);
                    }
                    if (obj.fichier7 == 'ok') {
                        $(".statut_fichier7").html(enregistre);
                    }
                    if (obj.fichier8 == 'ok') {
                        $(".statut_fichier8").html(enregistre);
                    }
                    if (obj.fichier9 == 'ok') {
                        $(".statut_fichier9").html(enregistre);
                    }
                    if (obj.fichier10 == 'ok') {
                        $(".statut_fichier10").html(enregistre);
                    }
                    if (obj.fichier11 == 'ok') {
                        $(".statut_fichier11").html(enregistre);
                    }
                    if (obj.fichier12 == 'ok') {
                        $(".statut_fichier12").html(enregistre);
                    }
                    if (obj.fichier13 == 'ok') {
                        $(".statut_fichier13").html(enregistre);
                    }
                    if (obj.fichier15 == 'ok') {
                        $(".statut_fichier15").html(enregistre);
                    }
                    if (obj.fichier16 == 'ok') {
                        $(".statut_fichier16").html(enregistre);
                    }
                    if (obj.fichier17 == 'ok') {
                        $(".statut_fichier17").html(enregistre);
                    }

                    //console.log("Upload OK:", result);
                    $("#valid_etape5").slideDown();
                    setTimeout(function () {
                        $("#valid_etape5").slideUp();
                    }, 4000);
                }
            </script>
            <form method="post" name="dossier_etape5" id="dossier_etape5" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/file/<?= $this->params[0] ?>" target="upload_target">
                <!-- bilans -->
                <?php
                if (count($this->lbilans) > 0) {
                ?>
                    <table class="tablesorter">
                        <thead>
                        <th width="200">Nom</th>
                        <th>Fichier</th>
                        <th>Statut</th>
                        <th></th>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Extrait Kbis</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/extrait_kbis/<?= $this->companies_details->fichier_extrait_kbis ?>"><?= $this->companies_details->fichier_extrait_kbis ?></a>
                            </td>
                            <td class="statut_fichier1"><?= ($this->companies_details->fichier_extrait_kbis != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier1" id="fichier_extrait_kbis"/></td>
                        </tr>
                        <tr>
                            <td>RIB</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/rib/<?= $this->companies_details->fichier_rib ?>"><?= $this->companies_details->fichier_rib ?></a>
                            </td>
                            <td class="statut_fichier2"><?= ($this->companies_details->fichier_rib != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier2" id="fichier_rib"/></td>
                        </tr>
                        <tr>
                            <td>Délégation de pouvoir</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/delegation_pouvoir/<?= $this->companies_details->fichier_delegation_pouvoir ?>"><?= $this->companies_details->fichier_delegation_pouvoir ?></a>
                            </td>
                            <td class="statut_fichier3"><?= ($this->companies_details->fichier_delegation_pouvoir != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier3" id="fichier_delegation_pouvoir"/></td>
                        </tr>
                        <tr>
                            <td>Logo de la société</td>
                            <td><a target="_blank"
                                   href="<?= $this->surl ?>/var/images/logos_companies/<?= $this->companies_details->fichier_logo_societe ?>"><?= $this->companies_details->fichier_logo_societe ?></a>
                            </td>
                            <td class="statut_fichier4"><?= ($this->companies_details->fichier_logo_societe != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier4" id="fichier_logo_societe"/></td>
                        </tr>
                        <tr>
                            <td>Photo du dirigeant</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/photo_dirigeant/<?= $this->companies_details->fichier_photo_dirigeant ?>"><?= $this->companies_details->fichier_photo_dirigeant ?></a>
                            </td>
                            <td class="statut_fichier5"><?= ($this->companies_details->fichier_photo_dirigeant != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier5" id="fichier_photo_dirigeant"/></td>
                        </tr>


                        <tr>
                            <td>cni/passeport</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/cni_passeport_emprunteur/<?= $this->companies_details->fichier_cni_passeport ?>"><?= $this->companies_details->fichier_cni_passeport ?></a>
                            </td>
                            <td class="statut_fichier6"><?= ($this->companies_details->fichier_cni_passeport != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier6" id="fichier_cni_passeport"/></td>
                        </tr>
                        <tr>
                            <td>Dernière liasse fiscale</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/derniere_liasse_fiscale/<?= $this->companies_details->fichier_derniere_liasse_fiscale ?>"><?= $this->companies_details->fichier_derniere_liasse_fiscale ?></a>
                            </td>
                            <td class="statut_fichier7"><?= ($this->companies_details->fichier_derniere_liasse_fiscale != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier7" id="fichier_derniere_liasse_fiscale"/></td>
                        </tr>
                        <tr>
                            <td>Derniers comptes approuvés</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/derniers_comptes_approuves/<?= $this->companies_details->fichier_derniers_comptes_approuves ?>"><?= $this->companies_details->fichier_derniers_comptes_approuves ?></a>
                            </td>
                            <td class="statut_fichier8"><?= ($this->companies_details->fichier_derniers_comptes_approuves != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier8" id="fichier_derniers_comptes_approuves"/></td>
                        </tr>
                        <tr>
                            <td>Derniers comptes consolidés du groupe</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/derniers_comptes_consolides_groupe/<?= $this->companies_details->fichier_derniers_comptes_consolides_groupe ?>"><?= $this->companies_details->fichier_derniers_comptes_consolides_groupe ?></a>
                            </td>
                            <td><?= ($this->companies_details->fichier_derniers_comptes_consolides_groupe != '' ? 'Enregistré' : '') ?></td>
                            <td class="statut_fichier9"><input type="file" name="fichier9" id="fichier_derniers_comptes_consolides_groupe"/></td>
                        </tr>
                        <tr>
                            <td>Annexes et rapport spécial du commissaire aux comptes</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/annexes_rapport_special_commissaire_compte/<?= $this->companies_details->fichier_annexes_rapport_special_commissaire_compte ?>"><?= $this->companies_details->fichier_annexes_rapport_special_commissaire_compte ?></a>
                            </td>
                            <td class="statut_fichier10"><?= ($this->companies_details->fichier_annexes_rapport_special_commissaire_compte != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier10" id="fichier_annexes_rapport_special_commissaire_compte"/></td>
                        </tr>
                        <tr>
                            <td>Arrêté comptable récent</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/arret_comptable_recent/<?= $this->companies_details->fichier_arret_comptable_recent ?>"><?= $this->companies_details->fichier_arret_comptable_recent ?></a>
                            </td>
                            <td class="statut_fichier11"><?= ($this->companies_details->fichier_arret_comptable_recent != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier11" id="fichier_arret_comptable_recent"/></td>
                        </tr>
                        <tr>
                            <td>Budget de l'exercice en cours et de l'exercice à venir</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/budget_exercice_en_cours_a_venir/<?= $this->companies_details->fichier_budget_exercice_en_cours_a_venir ?>"><?= $this->companies_details->fichier_budget_exercice_en_cours_a_venir ?></a>
                            </td>
                            <td class="statut_fichier12"><?= ($this->companies_details->fichier_budget_exercice_en_cours_a_venir != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier12" id="fichier_budget_exercice_en_cours_a_venir"/></td>
                        </tr>
                        <tr>
                            <td>Notation de la Banque de France</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/notation_banque_france/<?= $this->companies_details->fichier_notation_banque_france ?>"><?= $this->companies_details->fichier_notation_banque_france ?></a>
                            </td>
                            <td class="statut_fichier13"><?= ($this->companies_details->fichier_notation_banque_france != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier13" id="fichier_notation_banque_france"/></td>
                        </tr>
                        <tr>
                            <td>Autre 1</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/autres/<?= $this->companies_details->fichier_autre_1 ?>"><?= $this->companies_details->fichier_autre_1 ?></a>
                            </td>
                            <td class="statut_fichier15"><?= ($this->companies_details->fichier_autre_1 != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier15" id="fichier_autre_1"/></td>
                        </tr>
                        <tr>
                            <td>Autre 2</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/autres/<?= $this->companies_details->fichier_autre_2 ?>"><?= $this->companies_details->fichier_autre_2 ?></a>
                            </td>
                            <td class="statut_fichier16"><?= ($this->companies_details->fichier_autre_2 != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier16" id="fichier_autre_2"/></td>
                        </tr>
                        <tr>
                            <td>Autre 3</td>
                            <td>
                                <a href="<?= $this->url ?>/protected/autres/<?= $this->companies_details->fichier_autre_3 ?>"><?= $this->companies_details->fichier_autre_3 ?></a>
                            </td>
                            <td class="statut_fichier17"><?= ($this->companies_details->fichier_autre_3 != '' ? 'Enregistré' : '') ?></td>
                            <td><input type="file" name="fichier17" id="fichier_autre_3"/></td>
                        </tr>
                        </tbody>
                    </table>
                    <?php
                    if ($this->nb_lignes != '') {
                    ?>
                        <table>
                            <tr>
                                <td id="pager">
                                    <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                                    <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                                    <input type="text" class="pagedisplay"/>
                                    <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                                    <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                                    <select class="pagesize">
                                        <option value="<?= $this->nb_lignes ?>"
                                                selected="selected"><?= $this->nb_lignes ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                <?php
                    }
                }
                ?>
                <br/>

                <div id="valid_etape5">Données sauvegardées</div>
                <br/><br/>
                <input type="hidden" name="send_etape5"/>
                <div class="btnDroite"><input type="submit" class="btn_link" value="Sauvegarder"></div>
            </form>
            <div style="display:none;">
                <iframe id="upload_target" name="upload_target" src="#"></iframe>
            </div>
        </div>
        <br/>
        <div id="content_etape6">
            <?php
            // si statut revueA
            if ($this->current_projects_status->status >= 31) {
                $moyenne1 = (($this->projects_notes->performance_fianciere * 0.4) + ($this->projects_notes->marche_opere * 0.3) + ($this->projects_notes->qualite_moyen_infos_financieres * 0.2) + ($this->projects_notes->notation_externe * 0.1));
                $moyenne = round($moyenne1, 1);
            ?>
                <div id="title_etape6">Etape 6</div>
                <div id="etape6">
                    <table class="form tableNotes" style="width: 100%;">
                        <tr>

                            <th><label for="performance_fianciere">Performance financière</label></th>
                            <td>
                                <span id="performance_fianciere"><?= $this->projects_notes->performance_fianciere ?></span> /10
                            </td>

                            <th><label for="marche_opere">Marché opéré</label></th>
                            <td><span id="marche_opere"><?= $this->projects_notes->marche_opere ?></span> /10</td>

                            <th><label for="qualite_moyen_infos_financieres">Qualité des moyens & infos financières</label></th>
                            <td>
                                <input tabindex="6" id="qualite_moyen_infos_financieres" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->qualite_moyen_infos_financieres ?>" name="qualite_moyen_infos_financieres" maxlength="4" onkeyup="nodizaines(this.value, this.id);"> /10
                            </td>

                            <th><label for="notation_externe">Notation externe</label></th>
                            <td>
                                <input tabindex="7" id="notation_externe" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->notation_externe ?>" name="notation_externe" maxlength="4" onkeyup="nodizaines(this.value, this.id);"> /10
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="vertical-align:top;">
                                <table>
                                    <tr>
                                        <th><label for="structure">Structure</label></th>
                                        <td>
                                            <input tabindex="1" class="input_court cal_moyen" type="text" value="<?= ($this->projects_notes->structure) ?>" name="structure" id="structure" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="rentabilite">Rentabilité</label></th>
                                        <td>
                                            <input tabindex="2" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->rentabilite ?>" name="rentabilite" id="rentabilite" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="tresorerie">Trésorerie</label></th>
                                        <td>
                                            <input tabindex="3" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->tresorerie ?>" name="tresorerie" id="tresorerie" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td colspan="2" style="vertical-align:top;">
                                <table>
                                    <tr>
                                        <th><label for="global">Global</label></th>
                                        <td>
                                            <input tabindex="4" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->global ?>" name="global" id="global" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="individuel">Individuel</label></th>
                                        <td>
                                            <input tabindex="5" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->individuel ?>" name="individuel" id="individuel" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                        <tr class="lanote">
                            <th colspan="8" style="text-align:center;">Note : <span class="moyenneNote"><?= $moyenne ?>/10</span></th>
                        </tr>
                        <tr>
                            <td colspan="8" style="text-align:center;">
                                <label for="avis" style="text-align:left;display: block;">Avis :</label><br/>
                                <textarea tabindex="8" name="avis" style="height:700px;" id="avis" class="textarea_large avis"/><?= $this->projects_notes->avis ?></textarea>
                                <script type="text/javascript">var ckedAvis = CKEDITOR.replace('avis', {height: 700});</script>
                            </td>
                        </tr>
                    </table>
                    <br/><br/>
                    <div id="valid_etape6">Données sauvegardées</div>
                    <div class="btnDroite listBtn_etape6">
                        <input type="button" onclick="valid_rejete_etape6(3,<?= $this->projects->id_project ?>)" class="btn" value="Sauvegarder">
                        <?php
                        if ($this->current_projects_status->status == 31) {
                        ?>
                            <input type="button" onclick="valid_rejete_etape6(1,<?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape6" style="background:#009933;border-color:#009933;" value="Valider">
                            <input type="button" onclick="valid_rejete_etape6(2,<?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape6" style="background:#CC0000;border-color:#CC0000;" value="Rejeter">
                        <?php
                        }
                        ?>
                    </div>
                </div>
                <script type="text/javascript">
                    $(".cal_moyen").keyup(function () {

                        // --- Chiffre et marché ---
                        // Variables
                        var structure = parseFloat($("#structure").val().replace(",", "."));
                        var rentabilite = parseFloat($("#rentabilite").val().replace(",", "."));
                        var tresorerie = parseFloat($("#tresorerie").val().replace(",", "."));

                        var global = parseFloat($("#global").val().replace(",", "."));
                        var individuel = parseFloat($("#individuel").val().replace(",", "."));

                        // Arrondis
                        structure = (Math.round(structure * 10) / 10);
                        rentabilite = (Math.round(rentabilite * 10) / 10);
                        tresorerie = (Math.round(tresorerie * 10) / 10);

                        global = (Math.round(global * 10) / 10);
                        individuel = (Math.round(individuel * 10) / 10);

                        // Calcules
                        var performance_fianciere = ((structure + rentabilite + tresorerie) / 3)
                        performance_fianciere = (Math.round(performance_fianciere * 10) / 10);

                        // Arrondis
                        var marche_opere = ((global + individuel) / 2)
                        marche_opere = (Math.round(marche_opere * 10) / 10);

                        // --- Fin chiffre et marché ---

                        // Variables
                        var qualite_moyen_infos_financieres = parseFloat($("#qualite_moyen_infos_financieres").val().replace(",", "."));
                        var notation_externe = parseFloat($("#notation_externe").val().replace(",", "."));

                        // Arrondis
                        qualite_moyen_infos_financieres = (Math.round(qualite_moyen_infos_financieres * 10) / 10);
                        notation_externe = (Math.round(notation_externe * 10) / 10);

                        // Calcules
                        var moyenne1 = (((performance_fianciere * 0.4) + (marche_opere * 0.3) + (qualite_moyen_infos_financieres * 0.2) + (notation_externe * 0.1)));

                        // Arrondis
                        moyenne = (Math.round(moyenne1 * 10) / 10);

                        // Affichage
                        $("#marche_opere").html(marche_opere);
                        $("#performance_fianciere").html(performance_fianciere);
                        $(".moyenneNote").html(moyenne + "/10");

                    });
                </script>
            <?php
            }
            ?>
        </div>
        <br/>
        <div id="content_etape7">
            <?php
            // si statut revueA
            if ($this->current_projects_status->status >= 33) {
            ?>
                <div id="title_etape7">Etape 7</div>
                <div id="etape7">
                    <table class="form tableNotes" style="width: 100%;">
                        <tr>
                            <th><label for="performance_fianciere2">Performance financière</label></th>
                            <td>
                                <span id="performance_fianciere2"><?= $this->projects_notes->performance_fianciere ?></span> /10
                            </td>
                            <th style="vertical-align:top;"><label for="marche_opere2">Marché opéré</label></th>
                            <td style="vertical-align:top;">
                                <span id="marche_opere2"><?= $this->projects_notes->marche_opere ?></span> /10
                            </td>

                            <th><label for="qualite_moyen_infos_financieres2">Qualité des moyens & infos financières</label></th>
                            <td>
                                <input tabindex="14" id="qualite_moyen_infos_financieres2" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->qualite_moyen_infos_financieres ?>" name="qualite_moyen_infos_financieres" maxlength="4" onkeyup="nodizaines(this.value, this.id);"> /10
                            </td>
                            <th><label for="notation_externe2">Notation externe</label></th>
                            <td>
                                <input tabindex="15" id="notation_externe2" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->notation_externe ?>" name="notation_externe" maxlength="4" onkeyup="nodizaines(this.value, this.id);"> /10
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="vertical-align:top;">
                                <table>
                                    <tr>
                                        <th><label for="structure2">Structure</label></th>
                                        <td>
                                            <input tabindex="9" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->structure ?>" name="structure2" id="structure2" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="rentabilite2">Rentabilité</label></th>
                                        <td>
                                            <input tabindex="10" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->rentabilite ?>" name="rentabilite2" id="rentabilite2" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="tresorerie2">Trésorerie</label></th>
                                        <td>
                                            <input tabindex="11" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->tresorerie ?>" name="tresorerie2" id="tresorerie2" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td colspan="2" style="vertical-align:top;">
                                <table>
                                    <tr>
                                        <th><label for="global2">Global</label></th>
                                        <td>
                                            <input tabindex="12" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->global ?>" name="global2" id="global2" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="individuel2">Individuel</label></th>
                                        <td>
                                            <input tabindex="13" class="input_court cal_moyen" type="text" value="<?= $this->projects_notes->individuel ?>" name="individuel2" id="individuel2" maxlength="4" onkeyup="nodizaines(this.value, this.id);"/> /10
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                        <tr class="lanote">
                            <th colspan="8" style="text-align:center;">Note : <span class="moyenneNote2"><?= $moyenne ?> /10</span></th>
                        </tr>
                        <tr>
                            <td colspan="8" style="text-align:center;">
                                <label for="avis_comite" style="text-align:left;display: block;">Avis comité :</label><br/>
                                <textarea tabindex="16" name="avis_comite" style="height:700px;" id="avis_comite" class="textarea_large avis_comite"><?= $this->projects_notes->avis_comite ?></textarea>
                                <script type="text/javascript">var ckedAvis_comite = CKEDITOR.replace('avis_comite', {height: 700});</script>
                            </td>
                        </tr>
                    </table>
                    <script type="text/javascript">
                        $(".cal_moyen").keyup(function () {
                            // --- Chiffre et marché ---

                            // Variables
                            var structure = parseFloat($("#structure2").val().replace(",", "."));
                            var rentabilite = parseFloat($("#rentabilite2").val().replace(",", "."));
                            var tresorerie = parseFloat($("#tresorerie2").val().replace(",", "."));

                            var global = parseFloat($("#global2").val().replace(",", "."));
                            var individuel = parseFloat($("#individuel2").val().replace(",", "."));

                            // Arrondis
                            structure = (Math.round(structure * 10) / 10);
                            rentabilite = (Math.round(rentabilite * 10) / 10);
                            tresorerie = (Math.round(tresorerie * 10) / 10);

                            global = (Math.round(global * 10) / 10);
                            individuel = (Math.round(individuel * 10) / 10);

                            // Calcules
                            var performance_fianciere = ((structure + rentabilite + tresorerie) / 3)
                            performance_fianciere = (Math.round(performance_fianciere * 10) / 10);

                            // Arrondis
                            var marche_opere = ((global + individuel) / 2)
                            marche_opere = (Math.round(marche_opere * 10) / 10);

                            // --- Fin chiffre et marché ---

                            // Variables
                            var qualite_moyen_infos_financieres = parseFloat($("#qualite_moyen_infos_financieres2").val().replace(",", "."));
                            var notation_externe = parseFloat($("#notation_externe2").val().replace(",", "."));

                            // Arrondis
                            qualite_moyen_infos_financieres = (Math.round(qualite_moyen_infos_financieres * 10) / 10);
                            notation_externe = (Math.round(notation_externe * 10) / 10);

                            // Calcules
                            var moyenne1 = (((performance_fianciere * 0.4) + (marche_opere * 0.3) + (qualite_moyen_infos_financieres * 0.2) + (notation_externe * 0.1)));

                            // Arrondis
                            moyenne = (Math.round(moyenne1 * 10) / 10);

                            // Affichage
                            $("#marche_opere2").html(marche_opere);
                            $("#performance_fianciere2").html(performance_fianciere);
                            $(".moyenneNote2").html(moyenne + "/10");
                        });
                    </script>
                    <br/><br/>

                    <div id="valid_etape7">Données sauvegardées</div>
                    <div class="btnDroite">
                        <input type="button" onclick="valid_rejete_etape7(3,<?= $this->projects->id_project ?>)" class="btn" value="Sauvegarder">
                        <?php
                        if ($this->current_projects_status->status == 33) {
                        ?>
                            <input type="button" onclick="valid_rejete_etape7(1,<?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape7" style="background:#009933;border-color:#009933;" value="Valider">
                            <input type="button" onclick="valid_rejete_etape7(2,<?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape7" style="background:#CC0000;border-color:#CC0000;" value="Rejeter">
                            <input type="button" onclick="valid_rejete_etape7(4,<?= $this->projects->id_project ?>)" class="btn btnValid_rejet_etape7" value="Plus d'informations">
                        <?php
                        }
                        ?>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>
<script>
    <?php
        for ($i = 1; $i <= 7; $i++) {
    ?>
            $('#title_etape<?= $i ?>').click(function () {
                $('#etape<?= $i ?>').slideToggle();
            });
    <?php
        }
    ?>

    $("#dossier_resume").submit(function (event) {

        if ($("#statut_encours").val() == '0') {
            $("#statut_encours").val('1');
            $(".submitdossier").remove();
        }
        else {
            event.preventDefault();
        }
    });

    $('#same_address_etape2').click(function () {
        if ($(this).attr('checked') == true) {
            $('.same_adresse').hide('slow');
        }
        else {
            $('.same_adresse').show('slow');
        }
    });

    $('#enterprise1_etape2').click(function () {
        if ($(this).attr('checked') == true) {
            $('.statut_dirigeant_etape2').hide('slow');
            $('.statut_dirigeant3_etape2').hide('slow');
        }
    });
    $('#enterprise2_etape2').click(function () {
        if ($(this).attr('checked') == true) {
            $('.statut_dirigeant_etape2').show('slow');
            $('.statut_dirigeant3_etape2').hide('slow');
        }
    });
    $('#enterprise3_etape2').click(function () {
        if ($(this).attr('checked') == true) {
            $('.statut_dirigeant_etape2').show('slow');
            $('.statut_dirigeant3_etape2').show('slow');
        }
    });
</script>
