<style type="text/css">
    table.tablesorter tbody td.grisfonceBG, .grisfonceBG {
        background: #D2D2D2;
        text-align: right;
    }

    input[type=text].numbers {
        text-align: right;
    }

    .lanote {
        color: #5591EC;
        font-size: 17px;
        font-weight: bold;
    }

    .hidden-fields {
        display: none;
    }

    #problematic_status_error {
        display: none;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        color: #f00;
    }

    .tab_content {
        border: 2px solid #B10366;
        display: none;
        padding: 10px;
    }

    .tab_content .btnDroite {
        margin: 10px 0 0 0;
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

    table.form .section-title th {
        font-size: 14px;
        text-align: center;
    }

    table.annual-accounts td:first-child {
        text-align: left;
    }

    table.annual-accounts td:not(:first-child) {
        text-align: right;
    }

    table.annual-accounts .sub-total td {
        background-color: #d2d2d2;
        font-weight: bold;
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

    .annual_accounts_dates {
        cursor: pointer;
    }

    div.div-2-columns {
        -webkit-column-count: 2;
        -moz-column-count: 2;
        column-count: 2;
    }

    div.div-left-pos, div.div-right-pos {
        margin: 0;
        -webkit-column-break-inside: avoid;
        page-break-inside: avoid;
        break-inside: avoid-column;
        display:table;
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
            minDate: new Date(<?= date('Y') ?>, <?= (date('m') - 1) ?>, <?= date('d') ?>)
        });
        $("#date_de_retrait").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            minDate: new Date(<?= date('Y') ?>, <?= (date('m') - 1) ?>, <?= date('d') ?>)
        });
        $("#date_ps").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true
        });
        $("#date_rj").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true
        });
        $("#date_lj").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true
        });
        $('#duree').change(function() {
            if (0 == $(this).val() && <?= \projects_status::PREP_FUNDING ?> == <?= $this->current_projects_status->status ?>) {
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

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({
                container: $("#pager"),
                positionFixed: false,
                size: <?= $this->nb_lignes ?>
            });
        <?php endif; ?>

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
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/emprunteurs" title="Emprunteurs">Emprunteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/dossiers" title="Gestion des dossiers">Gestion des dossiers</a> -</li>
        <li>Detail Dossier</li>
    </ul>
    <h1>Detail dossier<?php if (false === empty($this->projects->title)) : ?> : <?= $this->projects->title ?><?php endif; ?></h1>
    <form method="post" name="dossier_resume" id="dossier_resume" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <div id="resume">
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
                <h2>Projet</h2>
                <table class="form" style="width: 575px;">
                    <tr>
                        <th><label for="montant">Montant du prêt* :</label></th>
                        <td><input style="background-color:#AAACAC;" type="text" name="montant" id="montant" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->amount) ?>"/> €</td>
                    </tr>
                    <tr>
                        <th><label for="duree">Durée du prêt* :</label></th>
                        <td>
                            <select name="duree" id="duree" class="select" style="width:160px;background-color:#AAACAC;">
                                <option<?= (in_array($this->projects->period, array(0, 1000000)) ? ' selected' : '') ?> value="0">Je ne sais pas</option>
                                <?php foreach ($this->dureePossible as $duree) : ?>
                                    <option<?= ($this->projects->period == $duree ? ' selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                                <?php endforeach ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="need">Type de besoin :</label></th>
                        <td>
                            <select name="need" id="need" class="select" style="width:160px;background-color:#AAACAC;">
                                <option value="0"></option>
                                <?php foreach ($this->aNeeds as $aNeed) : ?>
                                    <optgroup label="<?= $aNeed['label'] ?>">
                                    <?php foreach ($aNeed['children'] as $aNeedChild) : ?>
                                        <option<?= ($this->projects->id_project_need == $aNeedChild['id_project_need'] ? ' selected' : '') ?>><?= $aNeedChild['label'] ?></option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
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
                </table>
                <br><br>
                <h2>Remboursement anticipé / Information</h2>
                <table class="form" style="width: 538px; border: 1px solid #B10366;">
                    <tr>
                        <th>Statut</th>
                        <td><strong><?= $this->phrase_resultat ?></strong></td>
                    </tr>
                    <?php if ($this->virement_recu): ?>
                        <tr>
                            <th>Virement reçu le</th>
                            <td><strong><?= $this->dates->formatDateMysqltoFr_HourOut($this->receptions->added) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Identification virement</th>
                            <td><strong><?= $this->receptions->id_reception ?></strong></td>
                        </tr>
                        <tr>
                            <th>Montant virement</th>
                            <td><strong><?= $this->ficelle->formatNumber($this->receptions->montant / 100) ?>&nbsp;€</strong></td>
                        </tr>
                        <tr>
                            <th>Motif du virement</th>
                            <td><strong><?= $this->receptions->motif ?></strong></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th>Virement à émettre avant le</th>
                            <td><strong><?= (isset($this->date_next_echeance_4jouvres_avant)) ? $this->date_next_echeance_4jouvres_avant : '' ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Montant CRD (*)</th>
                        <td><strong><?= $this->ficelle->formatNumber($this->montant_restant_du_preteur) ?>&nbsp;€</strong></td>
                    </tr>
                    <?php if (false == $this->virement_recu): ?>
                        <tr>
                            <th>Motif à indiquer sur le virement</th>
                            <td><strong>RA-<?= $this->projects->id_project ?></strong></td>
                        </tr>
                    <?php endif; ?>
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
                            <input <?= ($this->projects->display == \projects::DISPLAY_PROJECT_ON ? 'checked' : '') ?> type="radio" name="display_project" id="oui_display_project" value="<?= \projects::DISPLAY_PROJECT_ON ?>"/>
                            <label for="oui_display_project">Oui</label>
                            <input <?= ($this->projects->display == \projects::DISPLAY_PROJECT_OFF ? 'checked' : '') ?> type="radio" name="display_project" id="non_display_project" value="<?= \projects::DISPLAY_PROJECT_OFF ?>"/>
                            <label for="non_display_project">Non</label>
                        </td>
                    </tr>
                    <tr>
                        <th>ID dossier :</th>
                        <td><?= $this->projects->id_project ?></td>
                    </tr>
                    <tr>
                        <th>ID emprunteur :</th>
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
                            ?>
                            <?php if (count($this->lProjects_status) > 0) : ?>
                                <?php
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
                                <select name="status" id="status" class="select" style="display:<?= $sDisplayStatus ?>;" <?= ($this->current_projects_status->status == \projects_status::REMBOURSEMENT_ANTICIPE ? '"disabled"' : "") ?>>
                                <?php foreach ($this->lProjects_status as $s) { ?>
                                    <option <?= ($this->current_projects_status->status == $s['status'] ? 'selected' : '') ?> value="<?= $s['status'] ?>"><?= $s['label'] ?></option>
                                <?php } ?>
                                </select>
                            <?php  else : ?>
                                <input type="hidden" name="status" id="status" value="<?= $this->current_projects_status->status ?>" />
                                <?= $this->current_projects_status->label ?>
                                <?php if (false === empty($this->sRejectionReason)) : ?>
                                    (<?= $this->sRejectionReason ?>)
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $this->lurl ?>/thickbox/project_history/<?= $this->projects->id_project ?>" class="thickbox"><img src="<?= $this->surl ?>/images/admin/info.png" alt="Information" /></a>
                        </td>
                    </tr>
                    <?php if ($this->current_projects_status->status == \projects_status::NOTE_EXTERNE_FAIBLE && false === empty($this->current_projects_status_history->content)) { ?>
                    <tr>
                        <th><label for="status">Motif :</label></th>
                        <td><?= $this->current_projects_status_history->content ?></td>
                    </tr>
                    <?php } ?>
                </table>

                <a href="<?= $this->lurl ?>/thickbox/popup_confirmation_send_email/<?= $this->projects->id_project ?>" class="thickbox confirmation_send_email"></a>
                <input type="hidden" name="check_confirmation_send_email" id="check_confirmation_send_email" value="0">

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
                                        ?><option value="<?= (strlen($h) < 2 ? "0" . $h : $h) ?>" <?= ($heure_date_publication == $h ? "selected=selected" : "") ?>><?= (strlen($h) < 2 ? "0" . $h : $h) ?></option><?php
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
                    <tr class="content_date_retrait" <?= ($this->current_projects_status->status >= \projects_status::PREP_FUNDING ? '' : 'style="display:none"') ?>>
                        <th><label for="date_retrait">Date de retrait* :</label></th>
                        <td id="date_retrait">
                            <?php
                            if (in_array($this->current_projects_status->status, array(\projects_status::EN_ATTENTE_PIECES, \projects_status::REVUE_ANALYSTE, \projects_status::COMITE, \projects_status::PREP_FUNDING, \projects_status::A_FUNDER))) {
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

                                if ($this->current_projects_status->status < \projects_status::FUNDE) {
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
                            <input type="button" id="status_dosier_valider" class="btn" onclick="check_status_dossier(<?= \projects_status::ATTENTE_ANALYSTE ?>, <?= $this->projects->id_project ?>);" style="background:#009933;border-color:#009933;font-size:10px;" value="Revue du dossier">
                            <a href="<?= $this->lurl ?>/dossiers/ajax_rejection/1/<?= $this->projects->id_project ?>" class="btn btn_link thickbox" style="background:#CC0000;border-color:#CC0000;font-size:10px;">Rejeter dossier</a>
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
                                <?php if ($this->projects_pouvoir->status_remb == '0' && $this->current_projects_status->status == \projects_status::FUNDE) { ?>
                                    <select name="statut_pouvoir" id="statut_pouvoir" class="select">
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
                        $('.hidden_table').hide();

                        if (status == <?= \projects_status::A_FUNDER ?>) {
                            $(".change_statut").hide();
                        } else if (status == <?= \projects_status::REMBOURSEMENT ?>) {

                        } else if (
                            status == <?= \projects_status::PROBLEME ?>
                            || status == <?= \projects_status::PROBLEME_J_X ?>
                            || status == <?= \projects_status::RECOUVREMENT ?>
                            || status == <?= \projects_status::PROCEDURE_SAUVEGARDE ?>
                            || status == <?= \projects_status::REDRESSEMENT_JUDICIAIRE ?>
                            || status == <?= \projects_status::LIQUIDATION_JUDICIAIRE ?>
                            || status == <?= \projects_status::DEFAUT ?>
                        ) {
                            $.colorbox({href: "<?= $this->lurl ?>/thickbox/project_status_update/<?= $this->projects->id_project ?>/" + status});
                        } else {
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

    <br/><br/>

    <h2>Mémos</h2>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/dossiers/export/<?= $this->projects->id_project ?>" class="btn_link">CSV données financières</a>
        <a href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>/altares" class="btn_link">Générer les données Altares</a>
    </div>
    <?php if (count($this->lProjects_comments) > 0): ?>
        <div id="table_memo">
            <table class="tablesorter">
                <thead>
                <tr>
                    <th width="120" align="center">Date ajout</th>
                    <th align="center">Contenu</th>
                    <th width="50" align="center">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lProjects_comments as $p): ?>
                    <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td align="center"><?= $this->dates->formatDate($p['added'], 'd/m/Y H:i:s') ?></td>
                        <td><?= nl2br($p['content']) ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/addMemo/<?= $p['id_project'] ?>/<?= $p['id_project_comment'] ?>" class="thickbox"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier"/></a>
                            <img style="cursor:pointer;" onclick="deleteMemo(<?= $p['id_project_comment'] ?>,<?= $p['id_project'] ?>);" src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer"/>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <br/>
    <?php endif; ?>
    <br/><br/>
    <div class="btnDroite"><a href="<?= $this->lurl ?>/dossiers/addMemo/<?= $this->projects->id_project ?>" class="btn_link thickbox">Ajouter un mémo</a></div>

    <style type="text/css">
        #tab_email, #etape1, #etape2, #etape3, #etape4, #etape5, #etape6, #etape7 {
            border: 2px solid #B10366;
            display: none;
            padding: 10px;
        }

        #title_tab_email, #title_etape1, #title_etape2, #title_etape3, #title_etape4, #title_etape5, #title_etape6, #title_etape7 {
            cursor: pointer;
            text-align: center;
            background-color: #B10366;
            color: white;
            padding: 5px;
            font-size: 16px;
            font-weight: bold;
        }

        #tab_email_msg, #valid_etape1, #valid_etape2, #valid_etape3, #valid_etape4, #valid_etape5, #valid_etape6, #valid_etape7 {
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

    <div id="lesEtapes">
        <div id="title_tab_email">Email</div>
        <div id="tab_email">
            <div style="float: right; min-width: 550px;">
                <h2>Historique</h2>
                <?php if (false === empty($this->aEmails) || false === empty($this->project_cgv->id)) : ?>
                    <table class="tablesorter">
                        <tbody>
                        <?php if (false === empty($this->project_cgv->id)) : ?>
                            <tr>
                                <td>
                                    CGV envoyées le <?= date('d/m/Y à H:i:s', strtotime($this->project_cgv->added)) ?>
                                    (<a href="<?= $this->furl . $this->project_cgv->getUrlPath() ?>" target="_blank">PDF</a>)
                                    <?php if (in_array($this->project_cgv->status, array(project_cgv::STATUS_SIGN_UNIVERSIGN, project_cgv::STATUS_SIGN_FO))) : ?>
                                        <br/><strong>signées</strong> le <?= date('d/m/Y à H:i:s', strtotime($this->project_cgv->updated))  ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($this->aEmails as $aEmail) : ?>
                            <tr>
                                <td>
                                    <?php $this->users->get($aEmail['id_user'], 'id_user'); ?>
                                    Envoyé le <?= date('d/m/Y à H:i:s', strtotime($aEmail['added'])) ?> par <?= $this->users->name ?><br>
                                    <?= $aEmail['content'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div id="edit_projects_tab_email">
                <h2>Configuration d'envoi d'Email</h2>
                <input type="checkbox" name="stop_relances" id="stop_relances" value="1" <?= $this->projects->stop_relances == 1 ? 'checked':'' ?>/> <label for="stop_relances">Arrêt des relances</label>
                <br/>
                <br/>
                <a href="#" class="btn_link" id="save_projects_tab_email" data-project-id="<?= $this->projects->id_project ?>">Sauvegarder</a>
            </div>
            <br />
            <div id="tab_email_msg">Données sauvegardées</div>
            <br />
            <div id="send_cgv">
                <h2>Envoi des CGV</h2>
                <a href="<?= $this->lurl ?>/dossiers/send_cgv_ajax/<?= $this->projects->id_project ?>" class="btn_link thickbox cboxElement">Envoyer</a>
            </div>

            <?php if (in_array($this->current_projects_status->status, array(\projects_status::EN_ATTENTE_PIECES, \projects_status::ATTENTE_ANALYSTE, \projects_status::REVUE_ANALYSTE, \projects_status::COMITE, \projects_status::PREP_FUNDING))) { ?>
            <br />
            <br />
            <div id="send_completeness">
                <h2>Complétude - Personnalisation du message</h2>
                <div class="liwording">
                    <table>
                        <?php foreach($this->completude_wording as $sSlug => $sWording):?><tr>
                            <td>
                                <a class="add_wording" id="add-<?= $sSlug ?>"><img src="<?= $this->surl ?>/images/admin/add.png"></a>
                            </td>
                            <td>
                                <span class="content-add-<?= $sSlug ?>"><?= $sWording ?></span>
                            </td>
                            </tr>
                        <?php endforeach?>
                    </table>
                </div>
                <br />
                <h3 class="test">Listes : </h3>
                <div class="content_li_wording"></div>
                <fieldset style="width:100%;">
                    <table class="formColor" style="width:100%;">
                        <tr>
                            <td>
                                <label for="id">Saisir votre message :</label>
                                <textarea name="content_email_completude" id="content_email_completude"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <a id="completude_preview" href="<?= $this->lurl ?>/dossiers/completude_preview/<?= $this->projects->id_project ?>" class="btn_link thickbox cboxElement">Prévisualiser</a>
                            </th>
                        </tr>
                    </table>
                </fieldset>
            </div>
            <?php } ?>
        </div>
        <br/>

        <div id="title_etape1">Etape 1</div>
        <div id="etape1">
            <form method="post" name="dossier_etape1" id="dossier_etape1" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
                <table class="form" style="width: 100%;">
                    <tr>
                        <th><label for="montant_etape1">Montant :</label></th>
                        <td>
                            <input type="text" name="montant_etape1" id="montant_etape1" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->amount) ?>"/> €
                        </td>

                        <th><label for="duree_etape1">Durée du prêt :</label></th>
                        <td>
                            <select name="duree_etape1" id="duree_etape1" class="select">
                                <?php foreach ($this->dureePossible as $duree): ?>
                                    <option <?= ($this->projects->period == $duree ? 'selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                                <?php endforeach ?>
                                <option <?= ((int)$this->projects->period === 1000000 || (int)$this->projects->period === 0) ? 'selected' : '' ?> value="0">Je ne sais pas</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="siren_etape1">SIREN :</label></th>
                        <td>
                            <?php
                            if ($this->projects->create_bo == 1) {
                                ?><input type="text" name="siren_etape1" id="siren_etape1" class="input_large" value="<?= $this->companies->siren ?>"/><?
                            } else {
                                ?><input type="hidden" name="siren_etape1" id="siren_etape1" value="<?= $this->companies->siren ?>"/><?
                                echo $this->companies->siren;
                            }
                            ?>
                        </td>

                        <th></th>
                        <td></td>
                    </tr>
                </table>
                <div id="valid_etape1">Données sauvegardées</div>
                <div class="btnDroite">
                    <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape1(<?= $this->projects->id_project ?>)">
                </div>
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
                        <td>
                            <input type="text" name="capital_social_etape2" id="capital_social_etape2" class="input_large" value="<?= $this->ficelle->formatNumber($this->companies->capital) ?>"/>
                        </td>
                        <th><label for="creation_date_etape2">Date de création (jj/mm/aaaa):</label></th>
                        <td><input readonly="readonly" type="text" name="creation_date_etape2" id="creation_date_etape2" class="input_dp" value="<?= $this->dates->formatDate($this->companies->date_creation, 'd/m/Y') ?>"/></td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:left;"><br/>Coordonnées du siège social :</th>
                    </tr>
                    <tr>
                        <th><label for="address_etape2">Adresse :</label></th>
                        <td>
                            <input type="text" name="address_etape2" id="address_etape2" class="input_large" value="<?= $this->companies->adresse1 ?>"/>
                        </td>
                        <th><label for="ville_etape2">Ville :</label></th>
                        <td>
                            <input type="text" name="ville_etape2" id="ville_etape2" class="input_large" value="<?= $this->companies->city ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="postal_etape2">Code postal :</label></th>
                        <td>
                            <input type="text" name="postal_etape2" id="postal_etape2" class="input_court" value="<?= $this->companies->zip ?>"/>
                        </td>
                        <th><label for="phone_etape2">Téléphone :</label></th>
                        <td>
                            <input type="text" name="phone_etape2" id="phone_etape2" class="input_moy" value="<?= $this->companies->phone ?>"/>
                        </td>
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
                        <td>
                            <input type="text" name="adresse_correspondance_etape2" id="adresse_correspondance_etape2" class="input_large" value="<?= $this->clients_adresses->adresse1 ?>"/>
                        </td>
                        <th><label for="city_correspondance_etape2">Ville :</label></th>
                        <td>
                            <input type="text" name="city_correspondance_etape2" id="city_correspondance_etape2" class="input_large" value="<?= $this->clients_adresses->ville ?>"/>
                        </td>
                    </tr>
                    <tr <?= ($this->companies->status_adresse_correspondance == 0 ? '' : 'style="display:none;"') ?>
                        class="same_adresse">
                        <th><label for="zip_correspondance_etape2">Code postal :</label></th>
                        <td>
                            <input type="text" name="zip_correspondance_etape2" id="zip_correspondance_etape2" class="input_court" value="<?= $this->clients_adresses->cp ?>"/>
                        </td>
                        <th><label for="phone_correspondance_etape2">Téléphone :</label></th>
                        <td>
                            <input type="text" name="phone_correspondance_etape2" id="phone_correspondance_etape2" class="input_moy" value="<?= $this->clients_adresses->telephone ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:left;"><br/>Vous êtes :</th>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:left;">
                            <input <?= false === $this->bHasPrescripteur ? 'checked' : '' ?> type="radio" name="enterprise_etape2" id="enterprise1_etape2" value="1"/><label for="enterprise1_etape2"> Je suis le dirigeant de l'entreprise </label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:left;">
                            <input <?= true === $this->bHasPrescripteur ? 'checked' : ''?> type="radio" name="enterprise_etape2" id="enterprise3_etape2" value="3"/><label for="enterprise3_etape2"> Je suis un conseil externe de l'entreprise </label>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:left;"><br/>Identification du dirigeant :</th>
                    </tr>
                    <tr>
                        <th>Civilité :</th>
                        <td>
                            <input <?= $this->clients->civilite == 'Mme' ? 'checked' : '' ?> type="radio" name="civilite_etape2" id="civilite1_etape2" value="Mme"/>
                            <label for="civilite1_etape2">Madame</label>
                            <input <?= $this->clients->civilite == 'M.' ? 'checked' : '' ?> type="radio" name="civilite_etape2" id="civilite2_etape2" value="M."/>
                            <label for="civilite2_etape2">Monsieur</label>
                        </td>
                        <th></th>
                        <td></td>
                    </tr>
                    <tr>
                        <th><label for="nom_etape2">Nom :</label></th>
                        <td>
                            <input type="text" name="nom_etape2" id="nom_etape2" class="input_large" value="<?= $this->clients->nom ?>"/>
                        </td>
                        <th><label for="prenom_etape2">Prénom :</label></th>
                        <td>
                            <input type="text" name="prenom_etape2" id="prenom_etape2" class="input_large" value="<?= $this->clients->prenom ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fonction_etape2">Fonction :</label></th>
                        <td>
                            <input type="text" name="fonction_etape2" id="fonction_etape2" class="input_large" value="<?= $this->clients->fonction ?>"/>
                        </td>
                        <th><label for="email_etape2">Email :</label></th>
                        <td>
                            <input type="text" name="email_etape2" id="email_etape2" class="input_large" value="<?= $this->clients->email ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="phone_new_etape2">Téléphone :</label></th>
                        <td>
                            <input type="text" name="phone_new_etape2" id="phone_new_etape2" class="input_moy" value="<?= $this->clients->telephone ?>"/>
                        </td>
                        <th></th>
                        <td></td>
                    </tr>
                    <tr<?= $this->bHasPrescripteur ? '' : ' style="display:none;"' ?> class="statut_dirigeant_etape2">
                        <th colspan="4" style="text-align:left;"><br/>Prescripteur :</th>
                    </tr>
                    <tr<?= $this->bHasPrescripteur ? '' : ' style="display:none;"' ?> class="identification_prescripteur">
                        <th>Civilité :</th>
                        <td colspan="3" id="civilite_prescripteur"><?= $this->clients_prescripteurs->civilite ?></td>
                    </tr>
                    <tr<?= $this->bHasPrescripteur ? '' : ' style="display:none;"' ?> class="identification_prescripteur">
                        <th>Nom :</th>
                        <td id="nom_prescripteur"><?= $this->clients_prescripteurs->nom ?></td>
                        <th>Prénom :</th>
                        <td id="prenom_prescripteur"><?= $this->clients_prescripteurs->prenom ?></td>
                    </tr>
                    <tr<?= $this->bHasPrescripteur ? '' : ' style="display:none;"' ?> class="identification_prescripteur">
                        <th>Téléphone :</th>
                        <td id="telephone_prescripteur"><?= $this->clients_prescripteurs->telephone ?></td>
                        <th>Email :</th>
                        <td id="email_prescripteur"><?= $this->clients_prescripteurs->email ?></td>
                    </tr>
                    <tr<?= $this->bHasPrescripteur ? '' : ' style="display:none;"' ?> class="identification_prescripteur">
                        <th>Raison sociale :</th>
                        <td id="company_prescripteur"><?= $this->companies_prescripteurs->name ?></td>
                        <th>Siren :</th>
                        <td id="siren_prescripteur"><?= $this->companies_prescripteurs->siren ?></td>
                    </tr>
                    <tr<?= $this->bHasPrescripteur ? '' : ' style="display:none;"' ?> class="statut_dirigeant_etape2">
                        <td colspan="4">
                            <input class="input_large" name="search_prescripteur" id="search_prescripteur" placeholder="nom, prenom ou email du prescripteur" />
                            <a id="btn_search_prescripteur" class="btn_link thickbox cboxElement" href="<?= $this->lurl ?>/prescripteurs/search_ajax/" onclick="$(this).attr('href', '<?= $this->lurl ?>/prescripteurs/search_ajax/<?= $this->projects->id_project ?>/' + $('#search_prescripteur').val());">Rechercher un prescripteur existant</a>
                        </td>
                    </tr>
                    <tr<?= $this->bHasPrescripteur ? '' : ' style="display:none;"' ?> class="statut_dirigeant_etape2">
                        <td colspan="4">
                            <a id="btn_add_prescripteur" class="btn_link thickbox cboxElement"
                               href="<?= $this->lurl ?>/prescripteurs/add_client/<?= $this->projects->id_project ?>" target="_blank">Créer un prescripteur</a>
                        </td>
                    </tr>
                    <input type="hidden" id="id_prescripteur" name="id_prescripteur" value="<?= $this->prescripteurs->id_prescripteur ?>" />
                </table>
                <br />
                <br />
                <div id="valid_etape2">Données sauvegardées</div>
                <div class="btnDroite">
                    <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape2(<?= $this->projects->id_project ?>)">
                </div>
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
                        <td><input type="text" name="montant_etape3" id="montant_etape3" class="input_large" value="<?= $this->ficelle->formatNumber($this->projects->amount) ?>"/> €</td>

                        <th><label for="duree_etape3">Durée du prêt :</label></th>
                        <td>
                            <select name="duree_etape3" id="duree_etape3" class="select">
                                <?php foreach ($this->dureePossible as $duree): ?>
                                    <option <?= ($this->projects->period == $duree ? 'selected' : '') ?> value="<?= $duree ?>"><?= $duree ?> mois</option>
                                <?php endforeach ?>
                                <option <?= ((int)$this->projects->period === 1000000 || (int)$this->projects->period === 0 ? 'selected' : '') ?> value="0">Je ne sais pas</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="titre_etape3">Titre projet :</label></th>
                        <td colspan="3">
                            <input style="width:780px;" type="text" name="titre_etape3" id="titre_etape3" class="input_large" value="<?= $this->projects->title ?>"/>
                        </td>
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
                <div class="btnDroite">
                    <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape3(<?= $this->projects->id_project ?>)">
                </div>
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

    <div id="lesEtapes">
        <?php $this->fireView('blocs/email'); ?>
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
            var check_ok = true;

            if ($('input[name=mail_a_envoyer_preteur_probleme]:checked', '#dossier_resume').val() == '0') {
                check_ok = false;
            } else if ($('input[name=mail_a_envoyer_preteur_problemeX]:checked', '#dossier_resume').val() == '0') {
                check_ok = false;
            } else if ($('input[name=mail_a_envoyer_preteur_probleme_recouvrement]:checked', '#dossier_resume').val() == '0') {
                check_ok = false;
            } else if ($('input[name=mail_a_envoyer_preteur_ps]:checked', '#dossier_resume').val() == '0') {
                check_ok = false;
            } else if ($('input[name=mail_a_envoyer_preteur_rj]:checked', '#dossier_resume').val() == '0') {
                check_ok = false;
            } else if ($('input[name=mail_a_envoyer_preteur_lj]:checked', '#dossier_resume').val() == '0') {
                check_ok = false;
            } else if ($('input[name=mail_a_envoyer_preteur_default]:checked', '#dossier_resume').val() == '0') {
                check_ok = false;
            }
            // On a un envoi de mail selected qu'on doit confirmer
            if (check_ok == false && $('#check_confirmation_send_email').val() == '0') {
                $(".confirmation_send_email").click();
                event.preventDefault();
            } else {
                $("#statut_encours").val('1');
                $(".submitdossier").remove();
            }
        } else {
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
