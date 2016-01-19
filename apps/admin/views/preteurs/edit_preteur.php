<script type="text/javascript">
    $(document).ready(function () {

        $(".histo_status_client").tablesorter({headers: {8: {sorter: false}}});

        $(".cgv_accept").tablesorter({headers: {}});

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 90)?>:<?=(date('Y') - 17)?>'
        });

        $("#debut").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 1)?>:<?=(date('Y') + 16)?>'
        });

        $("#fin").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 1)?>:<?=(date('Y') + 16)?>'
        });

        $(".radio_exonere").change(function () {
            if ($(this).val() == 1)$('.exo').fadeIn(); else $('.exo').fadeOut();
        });

        initAutocompleteCity($('#ville'), $('#cp'));
        initAutocompleteCity($('#ville2'), $('#cp2'));
        initAutocompleteCity($('#com-naissance'), $('#insee_birth'));

    });

    <?php
    if (isset($_SESSION['freeow'])) {
    ?>
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
    }
    ?>
</script>

<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" title="Gestion prêteurs">Détail prêteurs</a> -</li>
        <li>Informations prêteur</li>
    </ul>


    <?php
    // a controler
    if ($this->clients_status->status == 10) {
        ?>
        <div class="attention">
            Attention : compte non validé - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?php
    } // completude
    elseif (in_array($this->clients_status->status, array(20, 30, 40))) {
        ?>
        <div class="attention" style="background-color:#F9B137">
            Attention : compte en complétude - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?php
    } // modification
    elseif (in_array($this->clients_status->status, array(50))) {
        ?>
        <div class="attention" style="background-color:#F2F258">
            Attention : compte en modification - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
        <?php
    }
    ?>

    <h1>Informations prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>

    <div class="btnDroite">
        <a
            href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>"
            class="btn_link">Consulter Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Historique des emails</a>
        <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>"
           class="btn_link">Portefeuille & Performances</a>
    </div>

    <?php
    if (isset($_SESSION['error_email_exist']) && $_SESSION['error_email_exist'] != '') {
        ?>
        <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
        <?php
        unset($_SESSION['error_email_exist']);
    }
    ?>
    <form action="" method="post" enctype="multipart/form-data" id="form_etape1">
        <h2>Etape 1</h2>
        <table class="form" style="margin: auto;">
            <?php /*?><tr>
            <th>Vous êtes : </th>
            <td colspan="3">
            	<input type="radio" name="type" id="type1" value="1" <?=($this->clients->type == 1?'checked':'')?>><label for="type1">Particulier</label>
                <input type="radio" name="type" id="type2" value="2" <?=($this->clients->type == 2?'checked':'')?>><label for="type2">Société</label>
            </td>

		</tr><?php */ ?>
            <!-- particulier -->
            <?php
            if (in_array($this->clients->type, array(1, 3))) {
                ?>
                <tr class="particulier">
                    <th>Civilite :</th>
                    <td colspan="3">
                        <input type="radio" name="civilite" id="civilite1" <?= ($this->clients->civilite == 'Mme' ? 'checked' : '') ?> value="Mme"><label for="civilite1">Madame</label>
                        <input type="radio" name="civilite" id="civilite2" <?= ($this->clients->civilite == 'M.' ? 'checked' : '') ?> value="M."><label for="civilite2">Monsieur</label>
                    </td>

                </tr>
                <tr class="particulier">
                    <th><label for="nom-famille">Nom de famille :</label></th>
                    <td><input type="text" class="input_large" name="nom-famille" id="nom-famille" value="<?= $this->clients->nom ?>"></td>

                    <th><label for="nom-usage">Nom d'usage :</label></th>
                    <td><input type="text" class="input_large" name="nom-usage" id="nom-usage" value="<?= $this->clients->nom_usage ?>"></td>
                </tr>
                <tr class="particulier">
                    <th><label for="prenom">Prénom :</label></th>
                    <td><input type="text" class="input_large" name="prenom" id="prenom" value="<?= $this->clients->prenom ?>"></td>

                    <th><label for="email">Email :</label></th>
                    <td><input type="text" class="input_large" name="email" id="email" value="<?= $this->clients->email ?>"></td>
                </tr>
                <tr class="particulier">
                    <th></th>
                    <td><input style="font-size: 11px; height: 25px; width: 105px;" type="button" id="generer_mdp2" name="generer_mdp2" value="Générer mdp" class="btn"
                               onclick="generer_le_mdp('<?= $this->clients->id_client ?>')"/><span style="margin-left:5px;color:green; display:none;" class="reponse">mdp généré</span></td>

                    <th><label for="exonere">Exonéré :</label></th>
                    <td><input id="exonere" class="radio_exonere" type="radio" <?= ($this->lenders_accounts->exonere == 1 ? 'checked' : '') ?> name="exonere" value="1">Oui
                        <?php
                        //if($this->lenders_accounts->exonere==0)
                        //{
                        ?><input id="exonere2" class="radio_exonere" type="radio" <?= ($this->lenders_accounts->exonere == 0 ? 'checked' : '') ?> name="exonere" value="0">Non
                    </td><?php
                    //}
                    ?>
                </tr>
                <tr class="exo"<?= ($this->lenders_accounts->exonere == 1 ? '' : 'style="display:none;"') ?> >
                    <th></th>
                    <td></td>
                    <th>Debut</th>
                    <td><input type="text" name="debut" id="debut" class="input_dp" value="<?= $this->debut_exo ?>"/></td>
                </tr>
                <tr class="exo" <?= ($this->lenders_accounts->exonere == 1 ? '' : 'style="display:none;"') ?>>
                    <th></th>
                    <td></td>
                    <th>Fin</th>
                    <td><input type="text" name="fin" id="fin" class="input_dp" value="<?= $this->fin_exo ?>"/></td>
                </tr>
                <?php
            } else {
                ?>
                <!-- fin particulier -->

                <!-- societe -->
                <tr class="societe">
                    <th><label for="raison-sociale">Raison sociale :</label></th>
                    <td><input type="text" class="input_large" name="raison-sociale" id="raison-sociale" value="<?= $this->companies->name ?>"></td>

                    <th><label for="nom-usage">Forme juridique :</label></th>
                    <td>
                        <input type="text" class="input_large" name="form-juridique" id="form-juridique" value="<?= $this->companies->forme ?>">
                    </td>
                </tr>
                <tr class="societe">
                    <th><label for="capital-social">Capital social :</label></th>
                    <td><input type="text" class="input_large" name="capital-sociale" id="capital-sociale" value="<?= $this->companies->capital ?>"></td>

                    <th><label for="siren">SIREN :</label></th>
                    <td><input type="text" class="input_large" name="siren" id="siren" value="<?= $this->companies->siren ?>"></td>
                </tr>
                <tr class="societe">
                    <th><label for="phone-societe">Téléphone :</label></th>
                    <td><input type="text" class="input_large" name="phone-societe" id="phone-societe" value="<?= $this->companies->phone ?>"></td>

                    <th><label for="siret">SIRET :</label></th>
                    <td><input type="text" class="input_large" name="siret" id="siret" value="<?= $this->companies->siret ?>"></td>

                </tr>

                <tr class="societe">
                    <th><label for="phone-societe">Tribunal de commerce :</label></th>
                    <td><input type="text" class="input_large" name="tribunal_com" id="tribunal_com" value="<?= $this->companies->tribunal_com ?>"></td>

                    <th><label for="rcs">RCS :</label></th>
                    <td><input type="text" class="input_large" name="rcs" id="rcs" value="<?= $this->companies->rcs ?>"></td>

                </tr>

                <tr class="societe">
                    <th></th>
                    <td></td>
                    <th></th>
                    <td><input style="font-size: 11px; height: 25px; width: 105px;" type="button" id="generer_mdp" name="generer_mdp" value="Générer mdp" class="btn"
                               onclick="generer_le_mdp('<?= $this->clients->id_client ?>')"/><span style="margin-left:5px;color:green; display:none;" class="reponse">mdp généré</span></td>
                </tr>
                <?php
            }
            ?>

            <!-- fin societe -->
            <tr>
                <th><h3>Adresse fiscale</h3></th>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <th><label for="adresse">Adresse :</label></th>
                <td colspan="3"><input type="text" class="input_big" name="adresse" id="adresse" style="width:836px;" value="<?= $this->adresse_fiscal ?>"></td>
            </tr>
            <tr>
                <th><label for="ville">Ville :</label></th>
                <td><input type="text" class="input_large" name="ville" id="ville" value="<?= $this->city_fiscal ?>" data-autocomplete="city"></td>

                <th><label for="cp">Code postal :</label></th>
                <td><input type="text" class="input_large" name="cp" id="cp" value="<?= $this->zip_fiscal ?>" data-autocomplete="post_code"></td>
            </tr>

            <?php
            // particulier
            if (in_array($this->clients->type, array(1, 3))) {
                ?>
                <tr>
                    <th><label for="id_pays_fiscal">Pays fiscal :</label></th>
                    <td>
                        <select name="id_pays_fiscal" id="id_pays_fiscal" class="select">
                            <?php
                            foreach ($this->lPays as $p) {
                                ?>
                                <option <?= ($this->clients_adresses->id_pays_fiscal == $p['id_pays'] ? 'selected' : '') ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option><?
                            }
                            ?>
                        </select>
                    </td>
                    <th></th>
                    <td></td>
                </tr>
                <tr>
                    <th></th>
                    <td><input type="submit" value="Valider ce pays et appliquer aux échéanciers" class="btn" id="valider_pays" name="valider_pays"></td>
                    <th></th>
                    <td></td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <td colspan="4"><input type="checkbox" name="meme-adresse" id="meme-adresse" <?= ($this->meme_adresse_fiscal == 1 ? 'checked' : '') ?>><label for="meme-adresse">Mon adresse de
                        correspondance est identique à mon adresse de fiscale </label></td>
            </tr>
            <tr class="meme-adresse" style="display:none;">
                <th><label for="adresse2">Adresse :</label></th>
                <td colspan="3"><input type="text" class="input_big" name="adresse2" id="adresse2" style="width:836px;" value="<?= $this->clients_adresses->adresse1 ?>"></td>
            </tr>
            <tr class="meme-adresse" style="display:none;">
                <th><label for="ville2">Ville :</label></th>
                <td><input type="text" class="input_large" name="ville2" id="ville2" value="<?= $this->clients_adresses->ville ?>" data-autocomplete="city"></td>

                <th><label for="cp2">Code postal :</label></th>
                <td><input type="text" class="input_large" name="cp2" id="cp2" value="<?= $this->clients_adresses->cp ?>" data-autocomplete="post_code"></td>
            </tr>
            <?php
            // particulier
            if (in_array($this->clients->type, array(1, 3))) {
                ?>
                <tr class="meme-adresse" style="display:none;">
                    <th><label for="id_pays">Pays :</label></th>
                    <td>
                        <select name="id_pays" id="id_pays" class="select">
                            <?php
                            foreach ($this->lPays as $p) {
                                ?>
                                <option <?= ($this->clients_adresses->id_pays == $p['id_pays'] ? 'selected' : '') ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option><?
                            }
                            ?>
                        </select>
                    </td>

                    <th></th>
                    <td></td>
                </tr>
                <?php
            }
            ?>

            <!-- particulier -->
            <?php
            if (in_array($this->clients->type, array(1, 3))) {
                ?>
                <tr class="particulier">
                    <th><label for="phone">Téléphone :</label></th>
                    <td><input type="text" class="input_large" name="phone" id="phone" value="<?= $this->clients->telephone ?>"></td>

                    <th><label for="com-naissance">Commune de naissance :</label></th>
                    <td>
                        <input type="text" class="input_large" name="com-naissance" id="com-naissance" value="<?= $this->clients->ville_naissance ?>" data-autocomplete="birth_city">
                        <input type="hidden" id="insee_birth" name="insee_birth" value="<?= $this->clients->insee_birth ?>">
                    </td>
                </tr>
                <tr class="particulier">
                    <th><label for="naissance">Naissance :</label></th>
                    <td><input type="text" name="naissance" id="datepik" class="input_dp" value="<?= $this->naissance ?>"/></td>

                    <th><label for="nationalite">Nationalité :</label></th>
                    <td>
                        <select name="nationalite" id="nationalite" class="select">
                            <?php
                            foreach ($this->lNatio as $p) {
                                ?>
                                <option <?= ($this->clients->id_nationalite == $p['id_nationalite'] ? 'selected' : '') ?> value="<?= $p['id_nationalite'] ?>"><?= $p['fr_f'] ?></option><?
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr class="particulier">
                    <th><label for="id_pays_naissance">Pays de naissance :</label></th>
                    <td>
                        <select name="id_pays_naissance" id="id_pays_naissance" class="select">
                            <?php
                            foreach ($this->lPays as $p) {
                                ?>
                                <option <?= ($this->clients->id_pays_naissance == $p['id_pays'] ? 'selected' : '') ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option><?
                            }
                            ?>
                        </select>
                    </td>
                    <th></th>
                    <td></td>
                </tr>
                <?php
            } else {
                ?>
                <!-- fin particulier -->

                <!-- societe -->
                <tr class="societe">
                    <th colspan="4" style="text-align:left;"><br/>Vous êtes :</th>
                </tr>
                <tr class="societe">
                    <td colspan="4"><input <?= ($this->companies->status_client == 1 ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise1" value="1"/><label for="enterprise1"> Je suis
                            le dirigeant de l'entreprise </label></td>
                </tr>
                <tr class="societe">
                    <td colspan="4"><input <?= ($this->companies->status_client == 2 ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise2" value="2"/><label for="enterprise2"> Je ne
                            suis pas le dirigeant de l'entreprise mais je bénéficie d'une délégation de pouvoir </label></td>
                </tr>
                <tr class="societe">
                    <td colspan="4"><input <?= ($this->companies->status_client == 3 ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise3" value="3"/><label for="enterprise3"> Je suis
                            un conseil externe de l'entreprise </label></td>
                </tr>
                <!---->
                <tr <?= ($this->companies->status_client == 3 ? '' : 'style="display:none;"') ?> class="statut_dirigeant_e3 societe">
                    <th><label for="status_conseil_externe_entreprise">Expert comptable :</label></th>
                    <td>
                        <select name="status_conseil_externe_entreprise" id="status_conseil_externe_entreprise" class="select">
                            <option value="0">Choisir</option>
                            <?php
                            foreach ($this->conseil_externe as $k => $conseil_externe) {
                                ?>
                                <option <?= ($this->companies->status_conseil_externe_entreprise == $k ? 'selected' : '') ?> value="<?= $k ?>" ><?= $conseil_externe ?></option><?
                            }
                            ?>
                        </select>
                    </td>
                    <th><label for="preciser_conseil_externe_entreprise">Autre (préciser) :</label></th>
                    <td><input type="text" name="preciser_conseil_externe_entreprise" id="preciser_conseil_externe_entreprise" class="input_large"
                               value="<?= $this->companies->preciser_conseil_externe_entreprise ?>"/></td>
                </tr>
                <!---->
                <tr class="societe">
                    <th colspan="4" style="text-align:left;"><br/>Vos coordonnées :</th>
                </tr>
                <tr class="societe">
                    <th>Civilité :</th>
                    <td>
                        <input <?= ($this->clients->civilite == 'Mme' ? 'checked' : '') ?> type="radio" name="civilite_e" id="civilite_e1" value="Mme"/>
                        <label for="civilite_e1">Madame</label>

                        <input <?= ($this->clients->civilite == 'M.' ? 'checked' : '') ?> type="radio" name="civilite_e" id="civilite_e2" value="M."/>
                        <label for="civilite_e2">Monsieur</label>
                    </td>
                    <th></th>
                    <td></td>
                </tr>
                <tr class="societe">
                    <th><label for="nom_e">Nom :</label></th>
                    <td><input type="text" name="nom_e" id="nom_e" class="input_large" value="<?= $this->clients->nom ?>"/></td>
                    <th><label for="prenom_e">Prénom :</label></th>
                    <td><input type="text" name="prenom_e" id="prenom_e" class="input_large" value="<?= $this->clients->prenom ?>"/></td>
                </tr>
                <tr class="societe">
                    <th><label for="fonction_e">Fonction :</label></th>
                    <td><input type="text" name="fonction_e" id="fonction_e" class="input_large" value="<?= $this->clients->fonction ?>"/></td>
                    <th><label for="email_e">Email :</label></th>
                    <td><input type="text" name="email_e" id="email_e" class="input_large" value="<?= $this->clients->email ?>"/></td>
                </tr>
                <tr class="societe">
                    <th><label for="phone_e">Téléphone :</label></th>
                    <td><input type="text" name="phone_e" id="phone_e" class="input_moy" value="<?= $this->clients->telephone ?>"/></td>
                    <th></th>
                    <td></td>
                </tr>

                <!---->
                <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?> class="statut_dirigeant_e societe">
                    <th colspan="4" style="text-align:left;"><br/>Identification du dirigeant :</th>
                </tr>
                <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?> class="statut_dirigeant_e societe">
                    <th>Civilité :</th>
                    <td>
                        <input <?= ($this->companies->civilite_dirigeant == 'Mme' ? 'checked' : '') ?> type="radio" name="civilite2_e" id="civilite21_e" value="Mme"/>
                        <label for="civilite21_e">Madame</label>

                        <input <?= ($this->companies->civilite_dirigeant == 'M.' ? 'checked' : '') ?> type="radio" name="civilite2_e" id="civilite22_e" value="M."/>
                        <label for="civilite22_e">Monsieur</label>
                    </td>
                    <th></th>
                    <td></td>
                </tr>
                <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?> class="statut_dirigeant_e societe">
                    <th><label for="nom2_e">Nom :</label></th>
                    <td><input type="text" name="nom2_e" id="nom2_e" class="input_large" value="<?= $this->companies->nom_dirigeant ?>"/></td>
                    <th><label for="prenom2_e">Prénom :</label></th>
                    <td><input type="text" name="prenom2_e" id="prenom2_e" class="input_large" value="<?= $this->companies->prenom_dirigeant ?>"/></td>
                </tr>
                <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?> class="statut_dirigeant_e societe">
                    <th><label for="fonction2_e">Fonction :</label></th>
                    <td><input type="text" name="fonction2_e" id="fonction2_e" class="input_large" value="<?= $this->companies->fonction_dirigeant ?>"/></td>
                    <th><label for="email2_e">Email :</label></th>
                    <td><input type="text" name="email2_e" id="email2_e" class="input_large" value="<?= $this->companies->email_dirigeant ?>"/></td>
                </tr>
                <tr <?= ($this->companies->status_client == 1 ? 'style="display:none;"' : '') ?> class="statut_dirigeant_e societe">
                    <th><label for="phone2_e">Téléphone :</label></th>
                    <td><input type="text" name="phone2_e" id="phone2_e" class="input_moy" value="<?= $this->companies->phone_dirigeant ?>"/></td>
                    <th></th>
                    <td></td>
                </tr>
                <!---->
                <!-- fin societe -->
                <?php
            }
            ?>
        </table>

        <h2>Etape 2</h2>

        <table class="form" style="margin: auto;">
            <tr>
                <th><label for="bic">BIC :</label></th>
                <td><input type="text" name="bic" id="bic" class="input_large" value="<?= $this->lenders_accounts->bic ?>"/></td>

            </tr>
            <tr>
                <th><label for="iban1">IBAN :</label></th>
                <td>
                    <table>
                        <tr>
                            <td><input type="text" name="iban1" id="iban1" class="input_court" value="<?= $this->iban1 ?>"/></td>
                            <td><input type="text" name="iban2" id="iban2" class="input_court" value="<?= $this->iban2 ?>"/></td>
                            <td><input type="text" name="iban3" id="iban3" class="input_court" value="<?= $this->iban3 ?>"/></td>
                            <td><input type="text" name="iban4" id="iban4" class="input_court" value="<?= $this->iban4 ?>"/></td>
                            <td><input type="text" name="iban5" id="iban5" class="input_court" value="<?= $this->iban5 ?>"/></td>
                            <td><input type="text" name="iban6" id="iban6" class="input_court" value="<?= $this->iban6 ?>"/></td>
                            <td><input type="text" name="iban7" id="iban7" class="input_court" value="<?= $this->iban7 ?>"/></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <?php
            if ($this->origine_fonds[0] != false) {
                if (in_array($this->clients->type, array(1, 2, 3, 4))) {
                    ?>
                    <tr class="particulier">
                        <th colspan="2" style="text-align:left;"><label for="origines">Quelle est l'origine des fonds que vous déposer sur Unilend ?</label></th>
                    </tr>
                    <tr class="particulier">
                        <td colspan="2">

                            <select name="origine_des_fonds" id="origine_des_fonds" class="select">
                                <option value="0">Choisir</option>
                                <?php
                                foreach ($this->origine_fonds as $k => $origine_fonds) {
                                    ?>
                                    <option <?= ($this->lenders_accounts->origine_des_fonds == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>" ><?= $origine_fonds ?></option><?
                                }
                                ?>
                                <option <?= ($this->lenders_accounts->origine_des_fonds == 1000000 ? 'selected' : '') ?> value="1000000">Autre</option>
                            </select>

                        </td>
                    </tr>
                    <tr class="particulier">
                        <td colspan="2">

                            <div id="row_precision" style="display:none;"><input type="text" id="preciser" name="preciser"
                                                                                 value="<?= ($this->lenders_accounts->precision != '' ? $this->lenders_accounts->precision : '') ?>"
                                                                                 class="input_large"></div>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>
        </table>

        <br/><br/>
        <div class="gauche">
            <h2>Pièces jointes :</h2>
            <table class="form" style="width: auto;">
                <tr>
                    <th>Type de fichier</th>
                    <th>Nom <br> (cliquer pour télécharger)</th>
                    <th>Uploader un autre fichier</th>
                </tr>
                <?php foreach ($this->aAttachmentTypes as $sAttachmentType): ?>
                    <tr>
                        <th><?= $sAttachmentType['label'] ?></th>
                        <td>
                            <?php if (isset($this->attachments[$sAttachmentType['id']]['path'])): ?>
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $this->attachments[$sAttachmentType['id']]['id'] ?>/file/<?= urlencode($this->attachments[$sAttachmentType['id']]['path']) ?>">
                                    <?= $this->attachments[$sAttachmentType['id']]['path'] ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><input type="file" name="<?= $sAttachmentType['id'] ?>" id="fichier_project_<?= $sAttachmentType['id'] ?>"/></td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <th>Mandat</th>
                    <td>
                        <?php
                        if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) {
                            ?><a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $this->clients_mandats->name ?>"><?= $this->clients_mandats->name ?></a><?
                        }
                        ?>
                    </td>
                    <td><input type="file" name="mandat"></td>
                </tr>
            </table>

            <br/><br/>
            <h2>Historique :</h2>
            <?php
            // historique des actions
            $clients_status = $this->loadData('clients_status');
            if ($this->lActions != false) {
                ?>
                <style>
                    .histo_status_client li {
                        margin-left: 15px;
                        list-style: disc;
                    }
                </style>
                <table class="tablesorter histo_status_client">
                <?php
                foreach ($this->lActions as $a) {
                    $clients_status->get($a['id_client_status'], 'id_client_status');
                    $this->users->get($a['id_user'], 'id_user');

                    // creation compte a controler
                    if ($clients_status->status == 10) {
                        ?>
                        <tr>
                        <td>Création de compte le <?= date('d/m/Y H:i:s', strtotime($a['added'])) ?></td></tr><?
                    } elseif ($clients_status->status == 60) {
                        ?>
                        <tr>
                        <td>Compte validé le <?= date('d/m/Y H:i:s', strtotime($a['added'])) ?> par <?= $this->users->name ?></td></tr><?
                    } elseif (in_array($clients_status->status, array(20))) {
                        ?>
                        <tr>
                        <td>
                            Email de complétude envoyé le <?= date('d/m/Y H:i:s', strtotime($a['added'])) ?> par <?= $this->users->name ?><br>
                            Contenu : <?= $a['content'] ?>
                        </td>
                        </tr><?php
                    } elseif (in_array($clients_status->status, array(30))) {
                        ?>
                        <tr>
                        <td>
                            Complétude Relance le <?= date('d/m/Y H:i:s', strtotime($a['added'])) ?><br>
                        </td>
                        </tr><?php
                    } elseif (in_array($clients_status->status, array(40))) {
                        ?>
                        <tr>
                        <td>
                            Complétude Reponse le <?= date('d/m/Y H:i:s', strtotime($a['added'])) ?><br>
                            Champs : <?= $a['content'] ?>
                        </td>
                        </tr><?php
                    } else {
                        ?>
                        <tr>
                        <td>
                            Compte modifié le <?= date('d/m/Y H:i:s', strtotime($a['added'])) ?><br/>
                            Champs : <?= $a['content'] ?>
                        </td></tr><?
                    }
                }
                ?></table><?php
            }
            ?>

        </div>
        <div class="droite">

            <table class="tabLesStatuts">
                <tr>
                    <?php
                    // Si le compte n'est pas validé
                    if (!in_array($this->clients_status->status, array(60))) {
                        ?>
                        <td><input type="button" id="valider_preteur" class="btn" value="Valider le prêteur"></td>
                        <td><input type="button"
                                   onclick="if(confirm('Voulez vous supprimer définitivement ce prêteur ?')){window.location = '<?= $this->lurl ?>/preteurs/activation/delete/<?= $this->clients->id_client ?>';}"
                                   class="btnRouge" value="Supprimer"></td>
                        <?php
                    }
                    ?>
                </tr>
                <tr>
                    <td>
                        <input type="button" id="completude_edit" class="btn btnCompletude" value="Complétude">
                    </td>
                    <td>

                        <?php
                        if (isset($_SESSION['email_completude_confirm']) && $_SESSION['email_completude_confirm'] == true) {
                            ?>
                            <img src="<?= $this->surl ?>/images/admin/mail.png" alt="email" style="position: relative; top: 7px;"/>
                            <span style="color:green;">Votre email a été envoyé</span>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <?php
                //if(in_array($this->clients_status->status,array(60,20)))
                if (isset($_SESSION['email_completude_confirm']) && $_SESSION['email_completude_confirm'] == true || $_SESSION['compte_valide'] == true) {
                    ?>
                    <tr>
                        <td><a href="<?= $this->lurl ?>/preteurs/activation" class="btn_link btnBackListe">Revenir à la liste<br/> de contôle</a></td>
                        <td></td>
                    </tr>
                    <?php
                    unset($_SESSION['email_completude_confirm']);
                    unset($_SESSION['compte_valide']);
                }
                ?>
            </table>

            <br/>
            <div class="message_completude">
                <h2>Complétude - Personnalisation du message</h2>

                <div class="liwording">
                    <table>
                        <?php
                        for ($i = 1; $i <= $this->nbWordingCompletude; $i++) {

                            ?>
                            <tr>
                            <td>
                                <img class="add" id="add-<?= $i ?>" src="<?= $this->surl ?>/images/admin/add.png">
                            </td>
                            <td>
                                <span class="content-add-<?= $i ?>"><?= $this->completude_wording['cas-' . $i] ?></span>
                            </td>
                            </tr>
                            <?php
                            if (in_array($i, array(3, 6, 11))) {
                                echo '<tr><td colspan="2">&nbsp;</td></tr>';
                            }
                        }
                        ?>
                    </table>
                </div>
                <br/>

                <h3 class="test">Listes : </h3>
                <div class="content_li_wording">

                </div>

                <fieldset style="width:100%;">
                    <table class="formColor" style="width:100%;">
                        <tr>

                            <td>
                                <label for="id">Saisir votre message :</label>
                                <textarea name="content_email_completude" id="content_email_completude"><?= $text = str_replace(array("<br>", "<br />"), "",
                                        $_SESSION['content_email_completude'][$this->params[0]]) ?></textarea>
                                <?php /*?> <script type="text/javascript">var cked = CKEDITOR.replace('content_email_completude');</script><?php */ ?>
                            </td>
                        </tr>
                        <tr>

                            <th>
                                <a id="completude_preview" href="<?= $this->lurl ?>/preteurs/completude_preview/<?= $this->clients->id_client ?>" class="thickbox"></a>
                                <input type="button" value="Prévisualiser" title="Prévisualiser" name="previsualisation" id="previsualisation" class="btn"/>
                            </th>
                        </tr>
                    </table>
                </fieldset>
                <br/><br/>
            </div>

        </div>
        <div class="clear"></div>

        <br/><br/>

        <div class="content_cgv_accept">
            <h2>Acceptation CGV</h2>
            <?php
            if (count($this->lAcceptCGV) > 0) {
                ?>
                <table class="tablesorter cgv_accept">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Version</th>
                        <th>URL</th>
                        <th>Date validation</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    foreach ($this->lAcceptCGV as $a) {

                        $this->tree->get(array('id_tree' => $a['id_legal_doc'], 'id_langue' => $this->language));
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($this->tree->added)) ?></td>
                            <td><?= $this->tree->title ?></td>
                            <td><a target="_blank" href="<?= $this->furl . '/' . $this->tree->slug ?>"><?= $this->furl . '/' . $this->tree->slug ?></a></td>
                            <td><?= date('d/m/Y H:i:s', strtotime($a['updated'])) ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p style="text-align:center;" >Aucun CGV signé</p>';
            }
            ?>
        </div>

        <br/><br/><br/><br/>

        <input type="hidden" name="statut_valider_preteur" id="statut_valider_preteur" value="0"/>

        <input type="hidden" name="send_edit_preteur" id="send_edit_preteur"/>
        <div class="btnDroite"><input type="submit" id="save_etape_1" name="save_etape_1" value="Sauvegarder" class="btn"/></div>
    </form>
</div>
<script type="text/javascript">
    function addWordingli(id) {
        var content = $(".content-" + id).html();
        var textarea = $('#content_email_completude').val();

        var champ = "<input class=\"input_li\" type=\"text\" value=\"" + content + "\" name=\"input-" + id + "\" id=\"input-" + id + "\">";
        var clickdelete = "<div onclick='deleteWordingli(this.id);' class='delete_li' id='delete-" + id + "'><img src='" + add_surl + "/images/admin/delete.png' ></div>";
        $('.content_li_wording').append(champ + clickdelete);
    }

    function deleteWordingli(id) {
        var id_delete = id;
        var id_input = id.replace("delete", "input");
        $("#" + id_delete).remove();
        $("#" + id_input).remove();
    }

    $(".add").click(function () {
        var id = $(this).attr("id");
        addWordingli(id);
    });

    $("#completude_edit").click(function () {
        $('.message_completude').slideToggle();
    });


    $("#valider_preteur").click(function () {
        $("#statut_valider_preteur").val('1');
        $("#form_etape1").submit();
    });

    // previsualisation
    $("#previsualisation").click(function () {
        var content = $("#content_email_completude").val();
        var input = '';
        $(".input_li").each(function (index) {
            input = input + "<li>" + $(this).val() + "</li>";
        });

        $.post(add_url + "/ajax/session_content_email_completude", {id_client: "<?=$this->clients->id_client?>", content: content, liste: input}).done(function (data) {
            if (data != 'nok') {
                $("#completude_preview").get(0).click();
            }
        });
    });

    <?php
    if($this->meme_adresse_fiscal==0)
    {
        ?>$('.meme-adresse').show('slow');
    <?php
    }

    if($this->companies->status_client == 1)
    {
        ?>$('.statut_dirigeant_e').hide('slow');
    $('.statut_dirigeant_e3').hide('slow');
    <?php
    }
    elseif($this->companies->status_client == 2)
    {
        ?>$('.statut_dirigeant_e').show('slow');
    $('.statut_dirigeant_e3').hide('slow');
    <?php
    }
    elseif($this->companies->status_client == 3)
    {
        ?>$('.statut_dirigeant_e').show('slow');
    $('.statut_dirigeant_e3').show('slow');<?
    }
    ?>

    $('#meme-adresse').click(function () {
        if ($(this).attr('checked') == true) {
            $('.meme-adresse').hide('slow');
        }
        else {
            $('.meme-adresse').show('slow');
        }
    });

    $('#type1,#type2').click(function () {
        var type = $('input[name=type]:checked', '#form_etape1').val();

        if (type == 1) {
            $('.particulier').show();
            $('.societe').hide();
        }
        else if (type == 2) {
            $('.particulier').hide();
            $('.societe').show();
        }
    });

    $('#enterprise1').click(function () {
        if ($(this).attr('checked') == true) {
            $('.statut_dirigeant_e').hide('slow');
            $('.statut_dirigeant_e3').hide('slow');
        }
    });
    $('#enterprise2').click(function () {
        if ($(this).attr('checked') == true) {
            $('.statut_dirigeant_e').show('slow');
            $('.statut_dirigeant_e3').hide('slow');
        }
    });
    $('#enterprise3').click(function () {
        if ($(this).attr('checked') == true) {
            $('.statut_dirigeant_e').show('slow');
            $('.statut_dirigeant_e3').show('slow');
        }
    });


    $("#origine_des_fonds").change(function () {
        if ($(this).val() == '1000000') {
            $("#row_precision").show();
        }
        else {
            $("#row_precision").hide();
        }
    });
</script>

<?php
if ($this->lenders_accounts->origine_des_fonds == 1000000) {
    ?>
    <script>
        $("#row_precision").show();
    </script><?php
}
?>

