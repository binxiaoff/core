<style type="text/css">
    table.tablesorter tbody td.grisfonceBG, .grisfonceBG {
        background: #D2D2D2;
        text-align: right;
    }

    #etape4_1 .input_moy,
    #etape4_2 .input_moy,
    #etape4_3 .input_moy,
    #etape4_4 .input_moy {
        text-align: right;
    }

    .lanote {
        color: #5591EC;
        font-size: 17px;
        font-weight: bold;
    }
</style>
<script type="text/javascript">
    $(function () {
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
        $('#duree').change(function(){
            if(0 == $(this).val() && 35 == <?= $this->current_projects_status->status ?>) {
                $("#status").css('display', 'none');
                $("#msgProject").css('display', 'none');
                $("#displayPeriodHS").css('display', 'block');
                $("#msgProjectPeriodHS").css('display', 'block');
            } else if('' != $(".statut_fichier2").html()) {
                $("#status").css('display', 'block');
                $("#msgProject").css('display', 'block');
                $("#displayPeriodHS").css('display', 'none');
                $("#msgProjectPeriodHS").css('display', 'none');
            }
        });

        $(document).click(function(event) {
            var $clicked = $(event.target);
            if ($clicked.hasClass('tab_title')) {
                $clicked.next().slideToggle();
            }
        });

        <?php if ($this->nb_lignes != '') { ?>
        $(".tablesorter").tablesorterPager({
            container: $("#pager"),
            positionFixed: false,
            size: <?= $this->nb_lignes ?>
        });
        <?php } ?>
    });

    <?php if (isset($_SESSION['freeow'])) { ?>
        $(function () {
            var title, message, opts, container;
            title = "<?= $_SESSION['freeow']['title'] ?>";
            message = "<?= $_SESSION['freeow']['message'] ?>";
            opts = {};
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
        <?php unset($_SESSION['freeow']); ?>
    <?php } ?>
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
    <form method="post" name="dossier_resume" id="dossier_resume" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
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
                        <?php if ($this->projects->create_bo == 1) { ?>
                            <input type="text" name="siren" id="siren" class="input_large" value="<?= $this->companies->siren ?>">
                        <?php } else { ?>
                            <input type="hidden" name="siren" id="siren"value="<?= $this->companies->siren ?>">
                            <?= $this->companies->siren ?>
                        <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="siret">SIRET :</label></th>
                        <td>
                            <input type="text" name="siret" id="siret" class="input_large" value="<?= $this->companies->siret ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rcs">RCS :</label></th>
                        <td>
                            <input type="text" name="rcs" id="rcs" class="input_large" value="<?= $this->companies->rcs ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="code_naf">Code NAF :</label></th>
                        <td>
                            <input type="text" name="code_naf" id="code_naf" class="input_large" value="<?= $this->companies->code_naf ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="libelle_naf">Libellé NAF :</label></th>
                        <td>
                            <input type="text" name="libelle_naf" id="libelle_naf" class="input_large" value="<?= $this->companies->libelle_naf ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="title_bo">Titre du projet :</label></th>
                        <td>
                            <input type="text" name="title_bo" id="title_bo" class="input_large" value="<?= $this->projects->title_bo ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="societe">Nom société :</label></th>
                        <td>
                            <input type="text" name="societe" id="societe" class="input_large" value="<?= $this->companies->name ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="title">Titre du projet FO :</label></th>
                        <td>
                            <input type="text" name="title" id="title" class="input_large" value="<?= $this->projects->title ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sector">Secteur de la société :</label></th>
                        <td>
                            <select name="sector" id="sector" class="select">
                                <option value=""></option>
                                <?php foreach ($this->lSecteurs as $k => $s) { ?>
                                    <option <?= ($this->companies->sector == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>"><?= $s ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tribunal_com">Tribunal de commerce :</label></th>
                        <td>
                            <input type="text" name="tribunal_com" id="tribunal_com" class="input_large" value="<?= $this->companies->tribunal_com ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="activite">Activité :</label></th>
                        <td>
                            <input type="text" name="activite" id="activite" class="input_large" value="<?= $this->companies->activite ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lieu_exploi">Lieu exploitation :</label></th>
                        <td>
                            <input type="text" name="lieu_exploi" id="lieu_exploi" class="input_large" value="<?= $this->companies->lieu_exploi ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nature_project">Nature du projet :</label></th>

                        <td>
                            <textarea class="textarea_lng" name="nature_project" id="nature_project" style="height: 100px;width: 427px;"><?= $this->projects->nature_project ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="photo_projet">Photo projet :</label></th>
                        <td><input type="file" name="photo_projet" id="photo_projet" /><br /><a target="_blank" href="<?= $this->surl ?>/images/dyn/projets/source/<?= $this->projects->photo_projet ?>"><?= $this->projects->photo_projet ?></a></td>
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
                        <td>
                            <input type="text" name="adresse" id="adresse" class="input_large" value="<?= $this->adresse ?>"/>
                        </td>
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
                        <td>
                            <input style="background-color:#AAACAC;" type="text" name="montant" id="montant" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->amount) ?>"/> €
                        </td>
                    </tr>
                    <tr>
                        <th><label for="duree">Durée du prêt* :</label></th>
                        <td>
                            <select name="duree" id="duree" class="select" style="width:160px;background-color:#AAACAC;" >
                                <?php foreach($this->dureePossible as $duree): ?>
                                    <option <?= ($this->projects->period == $duree ? 'selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                                <?php endforeach ?>
                                <option<?= (in_array($this->projects->period, array(0, 1000000)) ? ' selected' : '') ?> value="0">Je ne sais pas</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="content_risk" <?= ($this->current_projects_status->status >= \projects_status::PREP_FUNDING ? '' : 'style="display:none"') ?>>
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
                        <td><strong><?= $this->companies->altares_scoreVingt ?></strong>/20</td>
                    </tr>
                    <tr>
                        <th><label for="rate">Score Sectoriel :</label></th>
                        <td><strong><?= $this->companies->altares_scoreSectorielCent / 100 * 20 ?></strong>/20</td>
                    </tr>
                    <tr>
                        <th><label for="rate">Date dernier bilan :</label></th>
                        <td>
                            <?php if (empty($this->aAnnualAccountsDates)) { ?>
                                -
                            <?php
                                } else {
                                    $aAnnualAccountsDate = current($this->aAnnualAccountsDates);
                                    echo $aAnnualAccountsDate['end']->format('d/m/Y');
                                }
                            ?>
                        </td>
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
                    <?php if ($this->virement_recu) { ?>
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
                    <?php if (false == $this->virement_recu) { ?>
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
                <?php if (! $this->virement_recu && ! $this->remb_anticipe_effectue && isset($this->date_next_echeance)) { ?>
                    * : Le montant correspond aux CRD des échéances restantes après celle du <?= $this->date_next_echeance ?> qui sera prélevé normalement
                <?php } ?>
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
                        <th><label for="commercial">Commercial :</label></th>
                        <td>
                            <select name="commercial" id="commercial" class="select">
                                <option value="0">Choisir</option>
                                <?php foreach ($this->aSalesPersons as $aSalesPerson) { ?>
                                    <option <?= ($this->projects->id_commercial == $aSalesPerson['id_user'] ? 'selected' : '') ?> value="<?= $aSalesPerson['id_user'] ?>"><?= $aSalesPerson['firstname'] ?> <?= $aSalesPerson['name'] ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr id="analysts-row"<?php if ($this->current_projects_status->status < \projects_status::ATTENTE_ANALYSTE && empty($this->projects->id_analyste)) { ?> style="display: none;"<?php } ?>>
                        <th><label for="analyste">Analyste :</label></th>
                        <td>
                            <select name="analyste" id="analyste" class="select">
                                <option value="0">Choisir</option>
                                <?php foreach ($this->aAnalysts as $aAnalyst) { ?>
                                    <option <?= ($this->projects->id_analyste == $aAnalyst['id_user'] ? 'selected' : '') ?> value="<?= $aAnalyst['id_user'] ?>"><?= $aAnalyst['firstname'] ?> <?= $aAnalyst['name'] ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status">Statut :</label></th>
                        <td id="current_statut">
                            <?php
                            $sDisplayPeriodHS    = 'none';
                            $sDisplayMsgPeriodHs = 'none';
                            $sDisplayStatus      = 'block';
                            $sDisplayMsgProject  = 'block';

                            if ($this->current_projects_status->status == 130) {
                                echo 'Remboursement anticipé';
                            } else {
                                if (count($this->lProjects_status) > 0) {
                                    if (
                                        (in_array($this->projects->period, array(0, 1000000)) || empty($this->aAttachments[3]['path'])) // No RIB or no duration selected
                                        && $this->current_projects_status->status == \projects_status::PREP_FUNDING
                                    ) {
                                        $sDisplayPeriodHS    = 'block';
                                        $sDisplayStatus      = 'none';
                                        $sDisplayMsgPeriodHs = 'block';
                                        $sDisplayMsgProject  = 'none';
                                    }
                                ?>
                                    <span id="displayPeriodHS" style="display:<?= $sDisplayPeriodHS ?>;">
                                        <?= $this->current_projects_status->label ?>
                                    </span>
                                    <select name="status" id="status" class="select" style="display:<?= $sDisplayStatus ?>;" <?= ($this->current_projects_status->status == 130 ? '"disabled"' : "") ?>>
                                    <?php foreach ($this->lProjects_status as $s) { ?>
                                        <option <?= ($this->current_projects_status->status == $s['status'] ? 'selected' : '') ?> value="<?= $s['status'] ?>"><?= $s['label'] ?></option>
                                    <?php } ?>
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
                    <?php if ($this->current_projects_status->status == \projects_status::NOTE_EXTERNE_FAIBLE && false === empty($this->current_projects_status_history->content)) { ?>
                    <tr>
                        <th><label for="status">Motif :</label></th>
                        <td><?= $this->current_projects_status_history->content ?></td>
                    </tr>
                    <?php } ?>
                </table>
                <table class="form" style="width: 538px;">
                    <?php if (in_array($this->current_projects_status->status, array(\projects_status::ATTENTE_ANALYSTE, \projects_status::REVUE_ANALYSTE, \projects_status::COMITE, \projects_status::PREP_FUNDING))) { ?>
                        <tr class="change_statut" <?= ($this->current_projects_status->status == \projects_status::PREP_FUNDING ? '' : 'style="display:none"') ?>>
                            <td colspan="2">
                                <span id="msgProject" style="display:<?= $sDisplayMsgProject ?>;">Vous devez changer le statut du projet pour ajouter une date de publication et de retrait</span>
                                <span id="msgProjectPeriodHS" style="display:<?= $sDisplayMsgPeriodHs ?>;">V&eacute;rifiez la dur&eacute;e du pr&ecirc;t et le rib avant de pouvoir changer de statut</span>
                                <div class="block_cache change_statut"></div>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr class="content_date_publicaion" <?= ($this->current_projects_status->status >= \projects_status::PREP_FUNDING ? '' : 'style="display:none"') ?>>
                        <th><label for="date_publication">Date de publication* :</label></th>
                        <td id="date_publication">
                            <?php
                            if (in_array($this->current_projects_status->status, array(\projects_status::EN_ATTENTE_PIECES, \projects_status::REVUE_ANALYSTE, \projects_status::COMITE, \projects_status::PREP_FUNDING, \projects_status::A_FUNDER))) {
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
                                        <option value="<?= (strlen($h) < 2 ? "0" . $h : $h) ?>" <?= ($heure_date_publication == $h ? "selected=selected" : "") ?>><?= (strlen($h) < 2 ? "0" . $h : $h) ?></option><?php
                                    }
                                    ?>
                                </select>h

                                <select name="date_publication_minute" class="selectMini">
                                    <?php
                                    for ($m = 0; $m < 60; $m+=5) {
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
                                    for ($m = 0; $m < 60; $m+=5) {
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
                    <?php if (isset($this->retour_dates_valides) && $this->retour_dates_valides != "") { ?>
                        <tr class="content_date_retrait">
                            <th></th>
                            <td style="color:red; font-weight:bold;"><?= $this->retour_dates_valides ?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td></td>
                        <td id="status_dossier">
                        <?php if ($this->current_projects_status->status == \projects_status::EN_ATTENTE_PIECES) { ?>
                            <input type="button" id="status_dosier_valider" class="btn" onclick="check_status_dossierV2(25, <?= $this->projects->id_project ?>);" style="background:#009933;border-color:#009933;font-size:10px;" value="Revue du dossier">
                            <input type="button" id="status_dosier_rejeter" class="btn" onclick="check_status_dossierV2(30, <?= $this->projects->id_project ?>);" style="background:#CC0000;border-color:#CC0000;font-size:10px;" value="Rejeter dossier">
                        <?php } ?>
                        </td>
                    </tr>
                    <?php if ($this->projects_pouvoir->get($this->projects->id_project, 'id_project') && $this->projects_pouvoir->status == 1) { ?>
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
                                <?php if ($this->projects_pouvoir->status_remb == '0' && $this->current_projects_status->status == 60) { ?>
                                    <select name="satut_pouvoir" id="satut_pouvoir" class="select">
                                        <option <?= ($this->projects_pouvoir->status_remb == '0' ? 'selected' : '') ?> value="0">En attente</option>
                                        <option <?= ($this->projects_pouvoir->status_remb == '1' ? 'selected' : '') ?> value="1">Validé</option>
                                    </select>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } elseif ($this->current_projects_status->status == \projects_status::FUNDE) { ?>
                        <tr>
                            <th><label for="upload_pouvoir">Pouvoir :</label></th>
                            <td><input type="file" name="upload_pouvoir" id="upload_pouvoir"/></td>
                        </tr>
                    <?php } ?>

                    <?php if ($this->current_projects_status->status == \projects_status::FUNDE) { ?>
                        <tr>
                            <th>Prêt refusé :</th>
                            <td>
                                <select name="pret_refuse" id="pret_refuse" class="select">
                                    <option value="0">Non</option>
                                    <option value="1">Oui</option>
                                </select>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <div style="display:none" class="recharge">
                <script type="text/javascript">
                    $("#status").change(function () {
                        var status = $("#status").val();
                        if (status == <?= \projects_status::A_FUNDER ?>) {
                            $(".change_statut").hide();
                        } else if (status != <?= \projects_status::REMBOURSEMENT ?>) {
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
    <br>
    <style type="text/css">
        .tab_content {
            border: 2px solid #B10366;
            display: none;
            padding: 10px;
        }

        .tab_title {
            cursor: pointer;
            text-align: center;
            background-color: #B10366;
            color: white;
            padding: 5px;
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
        }

        #tab_email_msg, .valid_etape {
            display: none;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #009933;
        }

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

    <div id="lesEtapes">
        <?php $this->fireView('blocs/email'); ?>
        <?php $this->fireView('blocs/etape1'); ?>
        <?php $this->fireView('blocs/etape2'); ?>
        <?php $this->fireView('blocs/etape3'); ?>
        <?php $this->fireView('blocs/etape4_1'); ?>
        <?php $this->fireView('blocs/etape4_2'); ?>
        <?php $this->fireView('blocs/etape4_3'); ?>
        <?php $this->fireView('blocs/etape4_4'); ?>
        <?php $this->fireView('blocs/etape5'); ?>
        <?php $this->fireView('blocs/etape6'); ?>
        <?php $this->fireView('blocs/etape7'); ?>
    </div>
</div>
<script>
    $("#dossier_resume").submit(function (event) {
        if ($("#statut_encours").val() == '0') {
            $("#statut_encours").val('1');
            $(".submitdossier").remove();
        }
        else {
            event.preventDefault();
        }
    });

    $('.icon_remove_attachment').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var type = $(this).data('label');
        var response = confirm("Voulez-vous supprimer " + type + "?");
        if (response == true) {
            $.ajax({
                url: "<?= $this->lurl ?>/dossiers/remove_file",
                dataType: 'json',
                type: 'POST',
                data: {
                    attachment_id: id
                },
                error: function() {
                    alert('An error has occurred');
                },
                success: function(data) {
                    if(false === $.isEmptyObject(data)) {
                        $.each(data, function(fileId, value){
                            if ('ok' == value) {
                                $("#statut_fichier_id_"+fileId).html('Supprimé');
                                $(this).remove;
                                $("#statut_fichier_id_"+fileId).parent().find('.label_col').html('');
                                $("#statut_fichier_id_"+fileId).parent().find('.remove_col').html('');
                            }

                        });
                        $("#valid_etape5").slideDown();
                        setTimeout(function () {
                            $("#valid_etape5").slideUp();
                        }, 4000);
                    } else {
                        alert('An error has occurred');
                    }
                }
            });
        }
    });

    $('#save_projects_tab_email').click(function(e){
        e.preventDefault();
        var iProjectId =$(this).data('project-id');
        var iFlag = 0;
        if ($('#stop_relances').is(':checked')) {
            iFlag = 1;
        }

        $.ajax({
            url: "<?= $this->lurl ?>/dossiers/tab_email",
            type: 'POST',
            data: {
                project_id: iProjectId,
                flag: iFlag
            },
            error: function() {
                alert('An error has occurred');
            },
            success: function(data) {
                if('ok' == data) {
                    $("#tab_email_msg").slideDown();
                    setTimeout(function () {
                        $("#tab_email_msg").slideUp();
                    }, 4000);
                } else {
                    alert('An error has occurred');
                }
            }
        });
    });

    function deleteWordingli(id){
        var id_delete = id;
        var id_input = id.replace("delete", "input");
        $("#"+id_delete).remove();
        $("#"+id_input).remove();
    }

    $(".add_wording").click(function(e) {
        e.preventDefault();
        var id = $(this).attr("id");
        var content = $(".content-"+id).html();
        if ($("#input-"+id).length == 0) {
            var champ = "<input class=\"input_li\" type=\"text\" value=\""+content+"\" name=\"input-"+id+"\" id=\"input-"+id+"\">";
            var clickdelete = '<a onclick="deleteWordingli(this.id)" class="delete_wording" id="delete-'+id+'"><img src="'+add_surl+'/images/admin/delete.png" ></a>';
            $('.content_li_wording').append(champ+clickdelete);
        }
    });

    $( "#completude_preview" ).click(function() {
        var content = $("#content_email_completude").val();
        var list = '';
        $(".input_li").each(function() {
            list = list + "<li>"+$(this).val()+"</li>";
        });

        $.post(
            add_url+"/ajax/session_project_completude",
            {
                id_project: "<?= $this->projects->id_project ?>",
                content: content,
                list: list
            }
        ).done(function( data ) {
            if(data != 'nok'){
                $( "#send_completeness" ).get(0).click();
            }
        });
    });
</script>
