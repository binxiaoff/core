<script type="text/javascript">
    $(function() {
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#date").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });

        $(document).click('.tab_title', function(event) {
            $(event.target).next().slideToggle();
        });

        <?php if ($this->nb_lignes != '') { ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?php } ?>
    });

    <?php if (isset($_SESSION['freeow'])) { ?>
        $(function () {
            var title, message, opts, container;
            title = "<?=$_SESSION['freeow']['title']?>";
            message = "<?=$_SESSION['freeow']['message']?>";
            opts = {};
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);

        });
        <?php unset($_SESSION['freeow']); ?>
    <?php } ?>
</script>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/emprunteurs" title="Emprunteurs">Emprunteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/dossiers" title="Gestion des dossiers">Gestion des dossiers</a> -</li>
        <li>Création Dossier</li>
    </ul>
    <h1>Création Dossier : </h1>
    <?php if (isset($this->params['0']) && $this->params['0'] == 'create') { ?>
        <form action="<?= $this->lurl ?>/dossiers/add/create_etape1" method="post">
            <table style="margin:auto;">
                <tr>
                    <th><label>Client existant ?</label></th>
                    <td style="text-align:left;">
                        <input checked="checked" type="radio" name="leclient" id="leclient1" value="1"/><label for="leclient1"> Oui </label>
                    </td>
                    <td style="text-align:left;">
                        <input type="radio" name="leclient" id="leclient2" value="2"/><label for="leclient2"> Non </label>
                    </td>
                </tr>
            </table>
            <br/>
            <br/>
            <div id="recherche_client">
                <table style="width:500px; margin:auto;text-align:center;margin-bottom:10px; border:2px solid;padding:10px;">
                    <tr>
                        <th style="padding:15px;"><label for="search">Prenom / Nom : </label></th>
                        <td style="padding:15px;">
                            <input id="search" class="input_moy" type="text" value="" name="search"></td>
                        <td style="padding:15px;">
                            <a id="link_search" class="btn_link thickbox" onclick="$(this).attr('href','<?= $this->lurl ?>/dossiers/changeClient/'+$('#search').val());" href="<?= $this->lurl ?>/dossiers/changeClient/">Rechercher</a>
                        </td>
                    </tr>
                </table>
                <table class="tablesorter" style="width:600px;margin:auto;">
                    <thead>
                        <tr>
                            <th>Id client</th>
                            <th>Prénom</th>
                            <th>Nom</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="id_clientHtml"></td>
                            <td id="prenomHtml"></td>
                            <td id="nomHtml"></td>
                        </tr>
                    </tbody>
                </table>
                <br/>
                <br/>
            </div>
            <br/>
            <br/>
            <input id="id_client" type="hidden" value="" name="id_client">
            <input id="send_create_etape1" type="hidden" name="send_create_etape1">
            <div class="btnDroite" style="text-align:center;"><input type="submit" class="btn" value="Valider"></div>
        </form>
    <?php } elseif ($this->create_etape_ok == true) { ?>
        <style type="text/css">
            .tab_title {cursor: pointer; text-align: center; background-color: #b10366; color: white; padding: 5px; font-size: 16px; font-weight: bold; margin-top: 15px;}
            .tab_content {border: 2px solid #b10366; padding: 10px;}
            #valid_etape1, #valid_etape2, #valid_etape3, #valid_etape4, #valid_etape5 {display: none; text-align: center; font-size: 16px; font-weight: bold; color: #009933;}
            #etape2, #etape3, #etape4, #etape5 {display: none;}
        </style>
        <br/>
        <br/>
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/dossiers/add/<?= $this->projects->id_project ?>/altares" class="btn_link">Générer les données Altares</a>
        </div>
        <div id="lesEtapes">
            <div class="tab_title" id="title_etape1">Etape 1</div>
            <div class="tab_content" id="etape1">
                <form method="post" name="dossier_etape1" id="dossier_etape1" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
                    <table class="form" style="width: 100%;">
                        <tr>
                            <th><label for="montant_etape1">Montant :</label></th>
                            <td>
                                <input type="text" name="montant_etape1" id="montant_etape1" class="input_moy" value="<?= $this->projects->amount ?>"/> €
                            </td>
                            <th><label for="duree_etape1">Durée du prêt :</label></th>
                            <td>
                                <select name="duree_etape1" id="duree_etape1" class="select">
                                    <option value="24">24 mois</option>
                                    <option value="36">36 mois</option>
                                    <option value="48">48 mois</option>
                                    <option value="60">60 mois</option>
                                    <option value="1000000">je ne sais pas</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="siren_etape1">SIREN :</label></th>
                            <td>
                                <input type="text" name="siren_etape1" id="siren_etape1" class="input_large" value="<?= $this->companies->siren ?>"/>
                            </td>
                            <th></th>
                            <td></td>
                        </tr>
                    </table>
                    <div id="valid_etape1">Données sauvegardées</div>
                    <div class="btnDroite">
                        <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape1(<?= $this->projects->id_project ?>);">
                    </div>
                </form>
            </div>

            <div class="tab_title" id="title_etape2">Etape 2</div>
            <div class="tab_content" id="etape2">
                <form method="post" name="dossier_etape2" id="dossier_etape2" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
                    <table class="form" style="width: 100%;">
                        <tr>
                            <th><label for="raison_sociale_etape2">Raison sociale :</label></th>
                            <td>
                                <input type="text" name="raison_sociale_etape2" id="raison_sociale_etape2" class="input_large" value="<?= $this->companies->name ?>"/>
                            </td>
                            <th><label for="forme_juridique_etape2">Forme juridique :</label></th>
                            <td>
                                <input type="text" name="forme_juridique_etape2" id="forme_juridique_etape2" class="input_large" value="<?= $this->companies->forme ?>"/>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="capital_social_etape2">Capital social :</label></th>
                            <td>
                                <input type="text" name="capital_social_etape2" id="capital_social_etape2" class="input_large" value="<?= $this->companies->capital ?>"/>
                            </td>
                            <th><label for="creation_date_etape2">Date de création (jj/mm/aaaa):</label></th>
                            <td>
                                <input type="text" name="creation_date_etape2" id="creation_date_etape2" class="input_moy" value="<?= $this->dates->formatDate($this->companies->date_creation, 'd/m/Y') ?>"/>
                            </td>
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
                                <input <?= ($this->companies->status_adresse_correspondance == 1 ? 'checked' : '') ?> type="checkbox" name="same_address_etape2" id="same_address_etape2"/><label for="same_address_etape2">L'adresse de correspondance est la même que l'adresse du siège social </label>
                            </td>
                        </tr>
                        <tr <?= ($this->companies->status_adresse_correspondance == 0 ? '' : 'style="display:none;"') ?> class="same_adresse">
                            <th colspan="4" style="text-align:left;"><br/>Coordonnées de l'adresse de correspondance :
                            </th>
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
                        <tr <?= ($this->companies->status_adresse_correspondance == 0 ? '' : 'style="display:none;"') ?> class="same_adresse">
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
                                <input <?= ($this->companies->status_client == 1 ? 'checked' : ($this->companies->status_client == 0 ? 'checked' : '')) ?> type="radio" name="enterprise_etape2" id="enterprise1_etape2" value="1"/><label for="enterprise1_etape2"> Je suis le dirigeant de l'entreprise </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="text-align:left;">
                                <input <?= ($this->companies->status_client == 2 ? 'checked' : '') ?> type="radio" name="enterprise_etape2" id="enterprise2_etape2" value="2"/><label for="enterprise2_etape2"> Je ne suis pas le dirigeant de l'entreprise mais je bénéficie d'une délégation de pouvoir </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="text-align:left;">
                                <input <?= ($this->companies->status_client == 3 ? 'checked' : '') ?> type="radio" name="enterprise_etape2" id="enterprise3_etape2" value="3"/><label for="enterprise3_etape2"> Je suis un conseil externe de l'entreprise </label>
                            </td>
                        </tr>
                        <tr <?= ($this->companies->status_client == 3 ? '' : 'style="display:none;"') ?> class="statut_dirigeant3_etape2">
                            <th><label for="status_conseil_externe_entreprise_etape2">Type de conseiller :</label></th>
                            <td>
                                <select name="status_conseil_externe_entreprise_etape2" id="status_conseil_externe_entreprise_etape2" class="select">
                                    <option value="0">Choisir</option>
                                    <?php foreach ($this->conseil_externe as $k => $conseil_externe) { ?>
                                        <option <?= ($this->companies->status_conseil_externe_entreprise == $k ? 'selected' : '') ?> value="<?= $k ?>" ><?= $conseil_externe ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                            <th><label for="preciser_conseil_externe_entreprise_etape2">Autre (préciser) :</label></th>
                            <td>
                                <input type="text" name="preciser_conseil_externe_entreprise_etape2" id="preciser_conseil_externe_entreprise_etape2" class="input_large" value="<?= $this->companies->preciser_conseil_externe_entreprise ?>"/>
                            </td>
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
                                <input type="text" name="email_etape2" id="email_etape2" class="input_large" value="<?= $this->clients->email ?>" onBlur="create_client(<?= $this->projects->id_project ?>);" onMouseOut="create_client(<?= $this->projects->id_project ?>);"/>
                                <input type="hidden" id="id_client" value="<?= $this->companies->id_client_owner ?>">
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
                        <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : ($this->companies->status_client == 0 ? 'style="display:none;"' : '')) ?> class="statut_dirigeant_etape2">
                            <th colspan="4" style="text-align:left;"><br/>Identification du dirigeant :</th>
                        </tr>
                        <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : ($this->companies->status_client == 0 ? 'style="display:none;"' : '')) ?> class="statut_dirigeant_etape2">
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
                        <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : ($this->companies->status_client == 0 ? 'style="display:none;"' : '')) ?> class="statut_dirigeant_etape2">
                            <th><label for="nom2_etape2">Nom :</label></th>
                            <td>
                                <input type="text" name="nom2_etape2" id="nom2_etape2" class="input_large" value="<?= $this->companies->nom_dirigeant ?>"/>
                            </td>
                            <th><label for="prenom2_etape2">Prénom :</label></th>
                            <td>
                                <input type="text" name="prenom2_etape2" id="prenom2_etape2" class="input_large" value="<?= $this->companies->prenom_dirigeant ?>"/>
                            </td>
                        </tr>
                        <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : ($this->companies->status_client == 0 ? 'style="display:none;"' : '')) ?> class="statut_dirigeant_etape2">
                            <th><label for="fonction2_etape2">Fonction :</label></th>
                            <td>
                                <input type="text" name="fonction2_etape2" id="fonction2_etape2" class="input_large" value="<?= $this->companies->fonction_dirigeant ?>"/>
                            </td>
                            <th><label for="email2_etape2">Email :</label></th>
                            <td>
                                <input type="text" name="email2_etape2" id="email2_etape2" class="input_large" value="<?= $this->companies->email_dirigeant ?>"/>
                            </td>
                        </tr>
                        <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : ($this->companies->status_client == 0 ? 'style="display:none;"' : '')) ?> class="statut_dirigeant_etape2">
                            <th><label for="phone_new2_etape2">Téléphone :</label></th>
                            <td>
                                <input type="text" name="phone_new2_etape2" id="phone_new2_etape2" class="input_moy" value="<?= $this->companies->phone_dirigeant ?>"/>
                            </td>
                            <th></th>
                            <td></td>
                        </tr>
                    </table>
                    <div id="valid_etape2">Données sauvegardées</div>
                    <div <?= ($this->companies->id_client_owner != 0 ? 'style="display:none;"' : '') ?> class="btnDroite" id="sav_email2">
                        <input type="button" class="btn_link" value="Sauvegarder" onclick="create_client(<?= $this->projects->id_project ?>)">
                    </div>
                    <div <?= ($this->companies->id_client_owner != 0 ? '' : 'style="display:none;"') ?> class="btnDroite" id="sav_etape2">
                        <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape2(<?= $this->projects->id_project ?>);">
                    </div>
                </form>
            </div>

            <div class="tab_title" id="title_etape3">Etape 3</div>
            <div class="tab_content" id="etape3">
                <form method="post" name="dossier_etape3" id="dossier_etape3" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
                    <table class="form" style="width: 100%;">
                        <tr>
                            <th><label for="montant_etape3">Montant :</label></th>
                            <td>
                                <input type="text" name="montant_etape3" id="montant_etape3" class="input_large" value="<?= $this->projects->amount ?>"/> €
                            </td>
                            <th><label for="duree_etape3">Durée du prêt :</label></th>
                            <td>
                                <select name="duree_etape3" id="duree_etape3" class="select">
                                    <option <?= ($this->projects->period == '24' ? 'selected' : '') ?> value="24">24 mois</option>
                                    <option <?= ($this->projects->period == '36' ? 'selected' : '') ?> value="36">36 mois</option>
                                    <option <?= ($this->projects->period == '48' ? 'selected' : '') ?> value="48">48 mois</option>
                                    <option <?= ($this->projects->period == '60' ? 'selected' : '') ?> value="60">60 mois</option>
                                    <option <?= ($this->projects->period == '1000000' ? 'selected' : '') ?> value="1000000">je ne sais pas</option>
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
                    </table>
                    <div id="valid_etape3">Données sauvegardées</div>
                    <div class="btnDroite">
                        <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape3(<?= $this->projects->id_project ?>);">
                    </div>
                </form>
            </div>

            <div class="tab_title" id="title_etape4">Etape 4</div>
            <div class="tab_content" id="etape4">
                <script language="javascript" type="text/javascript">
                    function formUploadCallbackcsv(result) {
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
                        <div id="valid_upload_etape4" style="text-align:center;color:#009933;font-weight:bold;display:none;">Upload csv terminé</div>
                        <div style="display:none;">
                            <iframe id="upload_csv_target" name="upload_csv_target" src="#">
                            </iframe>
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
                                    <select name="mois_etape4" id="mois_etape4" class="select">
                                    <?php
                                        foreach ($this->dates->tableauMois['fr'] as $k => $mois) {
                                            if ($k > 0) {
                                                echo '<option ' . ($this->date_dernier_bilan_mois == $k ? 'selected' : '') . ' value="' . $k . '">' . $mois . '</option>';
                                            }
                                        }
                                    ?>
                                    </select>
                                    <select name="annee_etape4" id="annee_etape4" class="select">
                                    <?php
                                        for ($i = 2008; $i <= date('Y') + 1; $i++) {
                                            ?>
                                            <option <?= ($this->date_dernier_bilan_annee == $i ? 'selected' : '') ?> value="<?= $i ?>"><?= $i ?></option><?
                                        }
                                    ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <br/>
                        <br/>
                        <?php if (count($this->lbilans) > 0) { ?>
                            <table class="tablesorter" style="text-align:center;">
                                <thead>
                                    <tr>
                                        <th width="200"></th>
                                        <?php foreach ($this->lbilans as $b) { ?>
                                            <th><?= $b['date'] ?></th>
                                        <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Chiffe d'affaires</td>
                                        <?php for ($i = 0; $i < 5; $i++) { ?>
                                        <td class="<?= ($i < 3 ? 'grisfonceBG' : '') ?>">
                                            <input name="ca_<?= $i ?>" id="ca_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['ca'] != false ? number_format($this->lbilans[$i]['ca'], 2, '.', '') : ''); ?>"/>
                                            <input type="hidden" id="ca_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                                        </td>
                                        <?php } ?>
                                    </tr>
                                    <tr>
                                        <td>Résultat brut d'exploitation</td>
                                        <?php for ($i = 0; $i < 5; $i++) { ?>
                                        <td class="<?= ($i < 3 ? 'grisfonceBG' : '') ?>">
                                            <input name="resultat_brute_exploitation_<?= $i ?>" id="resultat_brute_exploitation_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['resultat_brute_exploitation'] != false ? number_format($this->lbilans[$i]['resultat_brute_exploitation'], 2, '.', '') : ''); ?>"/>
                                            <input type="hidden" id="resultat_brute_exploitation_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                                        </td>
                                        <?php } ?>
                                    </tr>
                                    <tr>
                                        <td>Résultat d'exploitation</td>
                                        <?php for ($i = 0; $i < 5; $i++) { ?>
                                        <td class="<?= ($i < 3 ? 'grisfonceBG' : '') ?>">
                                            <input name="resultat_exploitation_<?= $i ?>" id="resultat_exploitation_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['resultat_exploitation'] != false ? number_format($this->lbilans[$i]['resultat_exploitation'], 2, '.', '') : ''); ?>"/>
                                            <input type="hidden" id="resultat_exploitation_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                                        </td>
                                        <?php } ?>
                                    </tr>
                                    <tr>
                                        <td>Investissements</td>
                                        <?php for ($i = 0; $i < 5; $i++) { ?>
                                        <td <?= ($i < 3 ? 'class="grisfonceBG"' : '') ?>>
                                            <input name="investissements_<?= $i ?>" id="investissements_<?= $i ?>" type="text" class="input_moy <?= ($i < 3 ? 'grisfonceBG' : '') ?>" value="<?= ($this->lbilans[$i]['investissements'] != false ? number_format($this->lbilans[$i]['investissements'], 2, '.', '') : ''); ?>"/>
                                            <input type="hidden" id="investissements_id_<?= $i ?>" value="<?= $this->lbilans[$i]['id_bilan'] ?>"/>
                                        </td>
                                        <?php } ?>
                                    </tr>
                                </tbody>
                            </table>
                            <?php if ($this->nb_lignes != '') { ?>
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
                            <?php } ?>
                        <?php } ?>
                        <br/>
                        <br/>
                        <table class="form" style="width: 100%;">
                            <tr>
                                <th>
                                    <label for="encours_actuel_dette_fianciere">Encours actuel de la dette financière :</label>
                                </th>
                                <td>
                                    <input type="text" name="encours_actuel_dette_fianciere" id="encours_actuel_dette_fianciere" class="input_moy" value="<?= ($this->companies_details->encours_actuel_dette_fianciere != false ? number_format($this->companies_details->encours_actuel_dette_fianciere, 2, '.', '') : '') ?>"/> €
                                </td>
                                <th><label for="remb_a_venir_cette_annee">Remboursements à venir cette année :</label>
                                </th>
                                <td>
                                    <input type="text" name="remb_a_venir_cette_annee" id="remb_a_venir_cette_annee" class="input_moy" value="<?= ($this->companies_details->remb_a_venir_cette_annee != false ? number_format($this->companies_details->remb_a_venir_cette_annee, 2, '.', '') : '') ?>"/> €
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="remb_a_venir_annee_prochaine">Remboursements à venir l'année prochaine :</label>
                                </th>
                                <td>
                                    <input type="text" name="remb_a_venir_annee_prochaine" id="remb_a_venir_annee_prochaine" class="input_moy" value="<?= ($this->companies_details->remb_a_venir_annee_prochaine != false ? number_format($this->companies_details->remb_a_venir_annee_prochaine, 2, '.', '') : '') ?>"/> €
                                </td>
                                <th>
                                    <label for="tresorie_dispo_actuellement">Trésorerie disponible actuellement :</label>
                                </th>
                                <td>
                                    <input type="text" name="tresorie_dispo_actuellement" id="tresorie_dispo_actuellement" class="input_moy" value="<?= ($this->companies_details->tresorie_dispo_actuellement != false ? number_format($this->companies_details->tresorie_dispo_actuellement, 2, '.', '') : '') ?>"/> €
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="autre_demandes_financements_prevues">Autres demandes de financements prévues<br/> (autres que celles que vous réalisez auprès d'Unilend) :</label>
                                </th>
                                <td>
                                    <input type="text" name="autre_demandes_financements_prevues" id="autre_demandes_financements_prevues" class="input_moy" value="<?= ($this->companies_details->autre_demandes_financements_prevues != false ? number_format($this->companies_details->autre_demandes_financements_prevues, 2, '.', '') : '') ?>"/> €
                                </td>
                                <th></th>
                                <td></td>
                            </tr>
                            <tr>
                                <th><label for="precisions">Vous souhaitez apporter des précisions
                                        <br/> pour nous aider à mieux vous comprendre ? :</label>
                                </th>
                                <td colspan="3">
                                    <textarea style="width:350px;" name="precisions" id="precisions" class="textarea"/><?= $this->companies_details->precisions ?></textarea>
                                </td>
                            </tr>
                        </table>
                        <style>
                            .actif_passif .input_moy {
                                width: 128px;
                            }
                        </style>
                        <h2>Actif :</h2>
                        <?php if (count($this->lCompanies_actif_passif) > 0) { ?>
                            <table class="tablesorter actif_passif" style="text-align:center;">
                                <thead>
                                    <tr>
                                        <th width="20">Ordre</th>
                                        <th>Immobilisations corporelles</th>
                                        <th>Immobilisations incorporelles</th>
                                        <th>Immobilisations financières</th>
                                        <th>Stocks</th>
                                        <th>Créances clients</th>
                                        <th>Disponibilités</th>
                                        <th>Valeurs mobilières de placement</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $total1 = 0;
                                $total2 = 0;
                                $total3 = 0;
                                $total4 = 0;
                                $total5 = 0;
                                $total6 = 0;
                                $total7 = 0;

                                $i = 1;
                                foreach ($this->lCompanies_actif_passif as $ap) {
                                    if ($i <= 3) {
                                        $totalAnnee = ($ap['immobilisations_corporelles'] + $ap['immobilisations_incorporelles'] + $ap['immobilisations_financieres'] + $ap['stocks'] + $ap['creances_clients'] + $ap['disponibilites'] + $ap['valeurs_mobilieres_de_placement'])
                                        ?>
                                        <tr>
                                            <td><?= $ap['annee'] ?></td>
                                            <td>
                                                <input name="immobilisations_corporelles_<?= $ap['ordre'] ?>" id="immobilisations_corporelles_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['immobilisations_corporelles'] != false ? number_format($ap['immobilisations_corporelles'], 2, '.', '') : ''); ?>" onkeyup="cal_actif();"/>
                                            </td>
                                            <td>
                                                <input name="immobilisations_incorporelles_<?= $ap['ordre'] ?>" id="immobilisations_incorporelles_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['immobilisations_incorporelles'] != false ? number_format($ap['immobilisations_incorporelles'], 2, '.', '') : ''); ?>" onkeyup="cal_actif();"/>
                                            </td>
                                            <td>
                                                <input name="immobilisations_financieres_<?= $ap['ordre'] ?>" id="immobilisations_financieres_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['immobilisations_financieres'] != false ? number_format($ap['immobilisations_financieres'], 2, '.', '') : ''); ?>" onkeyup="cal_actif();"/>
                                            </td>
                                            <td>
                                                <input name="stocks_<?= $ap['ordre'] ?>" id="stocks_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['stocks'] != false ? number_format($ap['stocks'], 0, '.', '') : ''); ?>" onkeyup="cal_actif();"/>
                                            </td>
                                            <td>
                                                <input name="creances_clients_<?= $ap['ordre'] ?>" id="creances_clients_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['creances_clients'] != false ? number_format($ap['creances_clients'], 2, '.', '') : ''); ?>" onkeyup="cal_actif();"/>
                                            </td>
                                            <td>
                                                <input name="disponibilites_<?= $ap['ordre'] ?>" id="disponibilites_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['disponibilites'] != false ? number_format($ap['disponibilites'], 2, '.', '') : ''); ?>" onkeyup="cal_actif();"/>
                                            </td>
                                            <td>
                                                <input name="valeurs_mobilieres_de_placement_<?= $ap['ordre'] ?>" id="valeurs_mobilieres_de_placement_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['valeurs_mobilieres_de_placement'] != false ? number_format($ap['valeurs_mobilieres_de_placement'], 2, '.', '') : ''); ?>" onkeyup="cal_actif();"/>
                                            </td>
                                            <td id="totalAnneeAct_<?= $ap['ordre'] ?>"><?= $totalAnnee ?></td>
                                        </tr>
                                        <?
                                        $total1 += $ap['immobilisations_corporelles'];
                                        $total2 += $ap['immobilisations_incorporelles'];
                                        $total3 += $ap['immobilisations_financieres'];
                                        $total4 += $ap['stocks'];
                                        $total5 += $ap['creances_clients'];
                                        $total6 += $ap['disponibilites'];
                                        $total7 += $ap['valeurs_mobilieres_de_placement'];
                                    }
                                    $i++;
                                }
                                ?>
                                </tbody>
                            </table>
                            <?php if ($this->nb_lignes != '') { ?>
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
                            <?php } ?>
                        <?php } ?>
                        <br/>
                        <br/>
                        <h2>Passif :</h2>
                        <?php if (count($this->lCompanies_actif_passif) > 0) { ?>
                            <table class="tablesorter" style="text-align:center;">
                                <thead>
                                    <tr>
                                        <th width="20">Ordre</th>
                                        <th>Capitaux propres</th>
                                        <th>Provisions pour risques & charges</th>
                                        <th>Amortissements sur immobilisations</th>
                                        <th>Dettes financières</th>
                                        <th>Dettes fournisseurs</th>
                                        <th>Autres dettes</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $total1 = 0;
                                $total2 = 0;
                                $total3 = 0;
                                $total4 = 0;
                                $total5 = 0;
                                $total6 = 0;

                                $i = 1;
                                foreach ($this->lCompanies_actif_passif as $ap) {
                                    if ($i <= 3) {
                                        $totalAnnee = ($ap['capitaux_propres'] + $ap['provisions_pour_risques_et_charges'] + $ap['amortissement_sur_immo'] + $ap['dettes_financieres'] + $ap['dettes_fournisseurs'] + $ap['autres_dettes']);
                                        ?>
                                        <tr>
                                            <td><?= $ap['annee'] ?></td>
                                            <td>
                                                <input name="capitaux_propres_<?= $ap['ordre'] ?>" id="capitaux_propres_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['capitaux_propres'] != false ? number_format($ap['capitaux_propres'], 2, '.', '') : ''); ?>" onkeyup="cal_passif();"/>
                                            </td>

                                            <td>
                                                <input name="provisions_pour_risques_et_charges_<?= $ap['ordre'] ?>" id="provisions_pour_risques_et_charges_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['provisions_pour_risques_et_charges'] != false ? number_format($ap['provisions_pour_risques_et_charges'], 2, '.', '') : ''); ?>" onkeyup="cal_passif();"/>
                                            </td>
                                            <td>
                                                <input name="amortissement_sur_immo_<?= $ap['ordre'] ?>" id="amortissement_sur_immo_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['amortissement_sur_immo'] != false ? number_format($ap['amortissement_sur_immo'], 2, '.', '') : ''); ?>" onkeyup="cal_passif();"/>
                                            </td>

                                            <td>
                                                <input name="dettes_financieres_<?= $ap['ordre'] ?>" id="dettes_financieres_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['dettes_financieres'] != false ? number_format($ap['dettes_financieres'], 2, '.', '') : ''); ?>" onkeyup="cal_passif();"/>
                                            </td>

                                            <td>
                                                <input name="dettes_fournisseurs_<?= $ap['ordre'] ?>" id="dettes_fournisseurs_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['dettes_fournisseurs'] != false ? number_format($ap['dettes_fournisseurs'], 2, '.', '') : ''); ?>" onkeyup="cal_passif();"/>
                                            </td>

                                            <td>
                                                <input name="autres_dettes_<?= $ap['ordre'] ?>" id="autres_dettes_<?= $ap['ordre'] ?>" type="text" class="input_moy" value="<?= ($ap['autres_dettes'] != false ? number_format($ap['autres_dettes'], 2, '.', '') : ''); ?>" onkeyup="cal_passif();"/>
                                            </td>
                                            <td id="totalAnneePass_<?= $ap['ordre'] ?>"><?= $totalAnnee ?></td>
                                        </tr>
                                        <?php

                                        $total1 += $ap['capitaux_propres'];
                                        $total2 += $ap['provisions_pour_risques_et_charges'];
                                        $total3 += $ap['amortissement_sur_immo'];
                                        $total4 += $ap['dettes_financieres'];
                                        $total5 += $ap['dettes_fournisseurs'];
                                        $total6 += $ap['autres_dettes'];
                                    }
                                    $i++;
                                }
                                ?>
                                </tbody>
                            </table>
                            <?php if ($this->nb_lignes != '') { ?>
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
                            <?php } ?>
                        <?php } ?>
                        <br/>
                        <br/>
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
                                <th>
                                    <label for="credit_bancaire_investissements_materiels">Crédit bancaire<br/>investissements matériels :</label>
                                </th>
                                <td>
                                    <input type="text" name="credit_bancaire_investissements_materiels" id="credit_bancaire_investissements_materiels" class="input_moy" value="<?= ($this->companies_details->credit_bancaire_investissements_materiels != false ? number_format($this->companies_details->credit_bancaire_investissements_materiels, 2, '.', '') : '') ?>"/> €
                                </td>
                                <th>
                                    <label for="credit_bancaire_investissements_immateriels">Crédit bancaire<br/>investissements immatériels :</label>
                                </th>
                                <td>
                                    <input type="text" name="credit_bancaire_investissements_immateriels" id="credit_bancaire_investissements_immateriels" class="input_moy" value="<?= ($this->companies_details->credit_bancaire_investissements_immateriels != false ? number_format($this->companies_details->credit_bancaire_investissements_immateriels, 2, '.', '') : '') ?>"/> €
                                </td>
                            </tr>
                            <tr>
                                <th><label for="rachat_entreprise_ou_titres">Rachat d'entreprise ou de titres :</label>
                                </th>
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
                                    <input type="text" name="pret_oseo" id="pret_oseo" class="input_moy" value="<?= ($this->companies_details->pret_oseo != false ? number_format($this->companies_details->pret_oseo, 2, '.', '') : '') ?>"/> €
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
                    <br/>
                    <br/>
                    <div id="valid_etape4">Données sauvegardées</div>
                    <div class="btnDroite">
                        <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape4(<?= $this->projects->id_project ?>)">
                    </div>
                </form>
            </div>

            <div class="tab_title" id="title_etape5">Etape 5</div>
            <div class="tab_content" id="etape5">
                <script type="text/javascript">
                    function formUploadCallback(result) {
                        var aStatus = jQuery.parseJSON(result);
                        if (aStatus.length != 0) {
                            $.each(aStatus, function (fileType, value) {
                                if ('ok' == value) {
                                    $(".statut_" + fileType).html('Enregistré');
                                }

                            });

                            $("#valid_etape5").slideDown();

                            setTimeout(function () {
                                $("#valid_etape5").slideUp();
                            }, 4000);
                        }
                    }
                </script>
                <form method="post" name="dossier_etape5" id="dossier_etape5" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/file/<?= $this->params[0] ?>" target="upload_target">
                    <?php if (count($this->lbilans) > 0) { ?>
                        <table class="tablesorter">
                            <thead>
                                <tr>
                                    <th width="200">Nom</th>
                                    <th>Fichier</th>
                                    <th>Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($this->aAttachmentTypes as $sAttachmentType): ?>
                                <tr>
                                    <td><?= $sAttachmentType['label'] ?></td>
                                    <td>
                                        <?php if (isset($this->aAttachments[$sAttachmentType['id']]['path'])): ?>
                                            <a href="<?= $this->url ?>/attachment/download/id/<?= $this->aAttachments[$sAttachmentType['id']]['id'] ?>/file/<?= urlencode($this->aAttachments[$sAttachmentType['id']]['path']) ?>"><?= $this->aAttachments[$sAttachmentType['id']]['path'] ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="statut_fichier_<?= $sAttachmentType['id'] ?>"><?= isset($this->aAttachments[$sAttachmentType['id']]) === true ? 'Enregistré' : '' ?></td>
                                    <td>
                                        <input type="file" name="<?= $sAttachmentType['id'] ?>" id="fichier_project_<?= $sAttachmentType['id'] ?>"/>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($this->nb_lignes != '') { ?>
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
                        <?php } ?>
                    <?php } ?>
                    <br/>
                    <div id="valid_etape5">Données sauvegardées</div>
                    <br/>
                    <br/>
                    <input type="hidden" name="send_etape5"/>
                    <div class="btnDroite"><input type="submit" class="btn_link" value="Sauvegarder"></div>
                </form>
                <div style="display:none;">
                    <iframe id="upload_target" name="upload_target" src="#"></iframe>
                </div>
            </div>
            <br/>
        </div>
        <br>
        <br>
        <br>
        <div class="btnDroite">
            <a href="#" id="valid_end" <?= ($this->companies->id_client_owner == 0 ? '' : 'style="display:none;"') ?> class="btn_link" onClick="alert('email vos coordonées obligatoire dans l\'etape 2')">Terminer</a>
            <a href="#" id="end_create" <?= ($this->companies->id_client_owner == 0 ? 'style="display:none;"' : '') ?> class="btn_link" onClick="valid_create(<?= $this->projects->id_project ?>);">Terminer</a>
        </div>
    <?php } ?>
</div>

<script>
    $('#same_address_etape2').click(function () {
        if ($(this).attr('checked') == true) {
            $('.same_adresse').hide('slow');
        } else {
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

    $('#leclient1').click(function () {
        $('#recherche_client').show();
        $('#new_client').hide();
    });

    $('#leclient2').click(function () {
        $('#recherche_client').hide();
        $('#new_client').show();
    });
</script>
