<script type="text/javascript">
    $(function() {
        $(".histo_status_client").tablesorter({headers: {8: {sorter: false}}});

        $(".cgv_accept").tablesorter({headers: {}});

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-90)?>:<?=(date('Y')-17)?>'
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

        initAutocompleteCity($('#ville'), $('#cp'));
        initAutocompleteCity($('#ville2'), $('#cp2'));
        initAutocompleteCity($('#com-naissance'), $('#insee_birth'));

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?=$_SESSION['freeow']['title']?>",
                message = "<?=$_SESSION['freeow']['message']?>",
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
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" title="Gestion prêteurs">Détail prêteurs</a> -</li>
        <li>Informations prêteur</li>
    </ul>
    <div><?= $this->sClientStatusMessage ?></div>
    <h1>Informations prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Enchères</a>
        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Consulter Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Historique des emails</a>
        <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Portefeuille & Performances</a>
    </div>
    <?php if (isset($_SESSION['error_email_exist']) && $_SESSION['error_email_exist'] != '') : ?>
        <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
        <?php unset($_SESSION['error_email_exist']); ?>
    <?php endif; ?>
    <form action="" method="post" enctype="multipart/form-data" id="form_etape1">
        <h2>Etape 1</h2>
        <table class="form" style="margin: auto;">
            <?php if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) : ?>
                <tr class="particulier">
                    <th>ID Client :</th>
                    <td>
                        <span><?= $this->clients->id_client ?></span>
                    </td>
                    <td><h3>Exonération fiscale</h3></td>
                    <td><h3>Informations MRZ</h3></td>
                </tr>
                <tr class="particulier">
                    <th>Civilite :</th>
                    <td>
                        <input type="radio" name="civilite" id="civilite1" <?= ($this->clients->civilite == 'Mme' ? 'checked' : '') ?> value="Mme"><label for="civilite1">Madame</label>
                        <input type="radio" name="civilite" id="civilite2" <?= ($this->clients->civilite == 'M.' ? 'checked' : '') ?> value="M."><label for="civilite2">Monsieur</label>
                    </td>
                    <td rowspan="6" style="vertical-align: top">
                        <?php if (false === in_array($this->iNextYear, $this->aExemptionYears)) : ?>
                            <a id="confirm_exemption" href="<?= $this->lurl ?>/thickbox/confirm_tax_exemption/<?= $this->iNextYear ?>/check" class="thickbox cboxElement">
                                <input type="checkbox" id="tax_exemption_<?= $this->iNextYear ?>" name="tax_exemption[<?= $this->iNextYear ?>]" value="1">
                            </a>
                            <label for="tax_exemption_<?= $this->iNextYear ?>"><?= $this->iNextYear ?></label>
                            <br>
                        <?php endif; ?>
                        <?php foreach ($this->aExemptionYears as $iExemptionYear) : ?>
                            <?php if ($this->iNextYear == $iExemptionYear) : ?>
                            <a id="confirm_exemption" href="<?= $this->lurl ?>/thickbox/confirm_tax_exemption/<?= $iExemptionYear ?>/uncheck" class="thickbox cboxElement">
                                <input type="checkbox" id="tax_exemption_<?= $iExemptionYear ?>" name="tax_exemption[<?= $iExemptionYear ?>]" value="1" checked>
                            </a>
                            <?php else: ?>
                                <input type="checkbox" id="tax_exemption_<?= $iExemptionYear ?>" name="tax_exemption[<?= $iExemptionYear ?>]" value="1" checked disabled>
                            <?php endif; ?>
                            <label for="tax_exemption_<?= $iExemptionYear ?>"><?= $iExemptionYear ?></label>
                            <br>
                        <?php endforeach; ?>
                    </td>
                    <td rowspan="6" style="vertical-align: top">
                        <table style="border-left: 1px solid;">
                            <tr>
                                <th colspan="2" style="text-align: center;">Prêteur</th>
                            </tr>
                            <tr class="particulier">
                                <th>Nationalité :</th>
                                <td><?= isset($this->lenderIdentityMRZData['identity_nationality']) ? $this->lenderIdentityMRZData['identity_nationality'] : '' ?></td>
                            </tr>
                            <tr class="particulier">
                                <th>Pays émetteur :</th>
                                <td><?= isset($this->lenderIdentityMRZData['identity_issuing_country']) ? $this->lenderIdentityMRZData['identity_issuing_country'] : '' ?></td>
                            </tr>
                            <tr class="particulier">
                                <th>Autorité émettrice :</th>
                                <td><?= isset($this->lenderIdentityMRZData['identity_issuing_authority']) ? $this->lenderIdentityMRZData['identity_issuing_authority'] : '' ?></td>
                            </tr>
                            <tr class="particulier">
                                <th>N°. de la pièce :</th>
                                <td><?= isset($this->lenderIdentityMRZData['identity_document_number']) ? $this->lenderIdentityMRZData['identity_document_number'] : '' ?></td>
                            </tr>
                            <?php if (false === empty($this->hostIdentityMRZData)) : ?>
                                <tr>
                                    <th colspan="2" style="text-align: center;">Hébergeur</th>
                                </tr>
                                <tr class="particulier">
                                    <th>Nationalité :</th>
                                    <td><?= isset($this->hostIdentityMRZData['identity_nationality']) ? $this->hostIdentityMRZData['identity_nationality'] : '' ?></td>
                                </tr>
                                <tr class="particulier">
                                    <th>Pays émetteur :</th>
                                    <td><?= isset($this->hostIdentityMRZData['identity_issuing_country']) ? $this->hostIdentityMRZData['identity_issuing_country'] : '' ?></td>
                                </tr>
                                <tr class="particulier">
                                    <th>Autorité émettrice :</th>
                                    <td><?= isset($this->hostIdentityMRZData['identity_issuing_authority']) ? $this->hostIdentityMRZData['identity_issuing_authority'] : '' ?></td>
                                </tr>
                                <tr class="particulier">
                                    <th>N°. de la pièce :</th>
                                    <td><?= isset($this->hostIdentityMRZData['identity_document_number']) ? $this->hostIdentityMRZData['identity_document_number'] : '' ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
                <tr class="particulier">
                    <th><label for="nom-famille">Nom de famille :</label></th>
                    <td><input type="text" class="input_large" name="nom-famille" id="nom-famille" value="<?= $this->clients->nom ?>"></td>
                </tr>
                <tr class="particulier">
                    <th><label for="nom-usage">Nom d'usage :</label></th>
                    <td><input type="text" class="input_large" name="nom-usage" id="nom-usage" value="<?= $this->clients->nom_usage ?>"></td>
                </tr>
                <tr class="particulier">
                    <th><label for="prenom">Prénom :</label></th>
                    <td><input type="text" class="input_large" name="prenom" id="prenom" value="<?= $this->clients->prenom ?>"></td>
                </tr>
                <tr class="particulier">
                    <th><label for="email">Email :</label></th>
                    <td><input type="text" class="input_large" name="email" id="email" value="<?= $this->clients->email ?>"></td>
                </tr>
                <tr class="particulier">
                    <th></th>
                    <td>
                        <input style="font-size: 11px; height: 25px; width: 105px;" type="button" id="generer_mdp2" name="generer_mdp2" value="Générer mdp" class="btn" onclick="generer_le_mdp('<?= $this->clients->id_client ?>')"/>
                        <span style="margin-left:5px;color:green; display:none;" class="reponse">mdp généré</span>
                    </td>
                </tr>
            <?php else : ?>
                <tr class="societe">
                    <th>ID Client :</th>
                    <td colspan="3">
                        <span><?= $this->clients->id_client ?></span>
                    </td>
                </tr>
                <tr class="societe">
                    <th><label for="raison-sociale">Raison sociale :</label></th>
                    <td><input type="text" class="input_large" name="raison-sociale" id="raison-sociale" value="<?= $this->companies->name ?>"></td>
                    <th><label for="nom-usage">Forme juridique :</label></th>
                    <td><input type="text" class="input_large" name="form-juridique" id="form-juridique" value="<?= $this->companies->forme ?>"></td>
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
                    <th></th>
                    <td></td>
                </tr>
                <tr class="societe">
                    <th></th>
                    <td></td>
                    <th></th>
                    <td>
                        <input style="font-size: 11px; height: 25px; width: 105px;" type="button" id="generer_mdp" name="generer_mdp" value="Générer mdp" class="btn" onclick="generer_le_mdp('<?= $this->clients->id_client ?>')"/>
                        <span style="margin-left:5px;color:green; display:none;" class="reponse">mdp généré</span>
                    </td>
                </tr>
            <?php endif; ?>
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
            <?php if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) : ?>
                <tr>
                    <th><label for="id_pays_fiscal">Pays fiscal :</label></th>
                    <td>
                        <select name="id_pays_fiscal" id="id_pays_fiscal" class="select">
                            <?php foreach ($this->lPays as $p) : ?>
                                <option <?= ($this->clients_adresses->id_pays_fiscal == $p['id_pays'] ? 'selected' : '') ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option>
                            <?php endforeach; ?>
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
            <?php endif; ?>
            <tr>
                <td colspan="4">
                    <input type="checkbox" name="meme-adresse" id="meme-adresse" <?= ($this->meme_adresse_fiscal == 1 ? 'checked' : '') ?>>
                    <label for="meme-adresse">Mon adresse de correspondance est identique à mon adresse fiscale </label>
                </td>
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
            <?php if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) : ?>
                <tr class="meme-adresse" style="display:none;">
                    <th><label for="id_pays">Pays :</label></th>
                    <td>
                        <select name="id_pays" id="id_pays" class="select">
                            <?php foreach ($this->lPays as $p) : ?>
                                <option <?= ($this->clients_adresses->id_pays == $p['id_pays'] ? 'selected' : '') ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <th></th>
                    <td></td>
                </tr>
            <?php endif; ?>
            <?php if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) : ?>
                <tr class="particulier">
                    <th><label for="phone">Téléphone :</label></th>
                    <td><input type="text" class="input_large" name="phone" id="phone" value="<?= $this->clients->telephone ?>"></td>
                    <th><label for="mobile">Mobile :</label></th>
                    <td><input type="text" class="input_large" name="mobile" id="mobile" value="<?= $this->clients->mobile ?>"></td>
                </tr>
                <tr class="particulier">
                    <th><label for="naissance">Naissance :</label></th>
                    <td><input type="text" name="naissance" id="datepik" class="input_dp" value="<?= $this->naissance ?>"/></td>
                    <th><label for="com-naissance">Commune de naissance :</label></th>
                    <td>
                        <input type="text" class="input_large" name="com-naissance" id="com-naissance" value="<?= $this->clients->ville_naissance ?>" data-autocomplete="birth_city">
                        <input type="hidden" id="insee_birth" name="insee_birth" value="<?= $this->clients->insee_birth ?>">
                    </td>
                </tr>
                <tr class="particulier">
                    <th><label for="id_pays_naissance">Pays de naissance :</label></th>
                    <td>
                        <select name="id_pays_naissance" id="id_pays_naissance" class="select">
                            <?php foreach ($this->lPays as $p) : ?>
                                <option <?= ($this->clients->id_pays_naissance == $p['id_pays'] ? 'selected' : '') ?> value="<?= $p['id_pays'] ?>"><?= $p['fr'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <th><label for="nationalite">Nationalité :</label></th>
                    <td>
                        <select name="nationalite" id="nationalite" class="select">
                            <?php foreach ($this->lNatio as $p) : ?>
                                <option <?= ($this->clients->id_nationalite == $p['id_nationalite'] ? 'selected' : '') ?> value="<?= $p['id_nationalite'] ?>"><?= $p['fr_f'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <!-- societe -->
                <tr class="societe">
                    <th colspan="4" style="text-align:left;"><br/>Vous êtes :</th>
                </tr>
                <tr class="societe">
                    <td colspan="4"><input <?= ($this->companies->status_client == 1 ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise1" value="1"/>
                        <label for="enterprise1"> Je suis le dirigeant de l'entreprise </label>
                    </td>
                </tr>
                <tr class="societe">
                    <td colspan="4"><input <?= ($this->companies->status_client == 2 ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise2" value="2"/>
                        <label for="enterprise2"> Je ne suis pas le dirigeant de l'entreprise mais je bénéficie d'une délégation de pouvoir </label>
                    </td>
                </tr>
                <tr class="societe">
                    <td colspan="4"><input <?= ($this->companies->status_client == 3 ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise3" value="3"/>
                        <label for="enterprise3"> Je suis un conseil externe de l'entreprise </label>
                    </td>
                </tr>
                <tr <?= ($this->companies->status_client == 3 ? '' : 'style="display:none;"') ?> class="statut_dirigeant_e3 societe">
                    <th><label for="status_conseil_externe_entreprise">Expert comptable :</label></th>
                    <td>
                        <select name="status_conseil_externe_entreprise" id="status_conseil_externe_entreprise" class="select">
                            <option value="0">Choisir</option>
                            <?php foreach ($this->conseil_externe as $k => $conseil_externe) : ?>
                                <option <?= ($this->companies->status_conseil_externe_entreprise == $k ? 'selected' : '') ?> value="<?= $k ?>" ><?= $conseil_externe ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <th><label for="preciser_conseil_externe_entreprise">Autre (préciser) :</label></th>
                    <td><input type="text" name="preciser_conseil_externe_entreprise" id="preciser_conseil_externe_entreprise" class="input_large" value="<?= $this->companies->preciser_conseil_externe_entreprise ?>"/></td>
                </tr>
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
            <?php endif; ?>
        </table>
        <h2>Etape 2</h2>
        <table class="form" style="margin: auto;">
            <tr>
                <th><label for="bic">BIC :</label></th>
                <td><input type="text" name="bic" id="bic" class="input_large" value="<?= $this->lenders_accounts->bic ?>"/></td>
            </tr>
            <tr>
                <th style="text-align: right; vertical-align: top; padding: 5px 0px 10px 5px;><label for="iban1">IBAN :</label></th>
                <td style="padding: 0 0 10px 0;">
                    <table>
                        <tr>
                            <td><input type="text" name="iban1" id="iban1" class="input_court" value="<?= (false === empty($this->iban1)) ? $this->iban1 : '' ?>"/></td>
                            <td><input type="text" name="iban2" id="iban2" class="input_court" value="<?= (false === empty($this->iban2)) ? $this->iban2 : '' ?>"/></td>
                            <td><input type="text" name="iban3" id="iban3" class="input_court" value="<?= (false === empty($this->iban3)) ? $this->iban3 : '' ?>"/></td>
                            <td><input type="text" name="iban4" id="iban4" class="input_court" value="<?= (false === empty($this->iban4)) ? $this->iban4 : '' ?>"/></td>
                            <td><input type="text" name="iban5" id="iban5" class="input_court" value="<?= (false === empty($this->iban5)) ? $this->iban5 : '' ?>"/></td>
                            <td><input type="text" name="iban6" id="iban6" class="input_court" value="<?= (false === empty($this->iban6)) ? $this->iban6 : '' ?>"/></td>
                            <td><input type="text" name="iban7" id="iban7" class="input_court" value="<?= (false === empty($this->iban7)) ? $this->iban7 : '' ?>"/></td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <span class="btn" id="change_bank_account_btn">Valider les modifications sur le RIB</span>
                            </td>
                            <td colspan="2" valign="middle">
                                <p id="iban_ok" style="margin:0px;"></p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <?php if ($this->origine_fonds[0] != false) : ?>
                <?php if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER, \clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) : ?>
                    <tr class="particulier">
                        <th colspan="2" style="text-align:left;"><label for="origines">Quelle est l'origine des fonds que vous déposer sur Unilend ?</label></th>
                    </tr>
                    <tr class="particulier">
                        <td colspan="2">
                            <select name="origine_des_fonds" id="origine_des_fonds" class="select">
                                <option value="0">Choisir</option>
                                <?php foreach ($this->origine_fonds as $k => $origine_fonds) : ?>
                                    <option <?= ($this->lenders_accounts->origine_des_fonds == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>" ><?= $origine_fonds ?></option>
                                <?php endforeach; ?>
                                <option <?= ($this->lenders_accounts->origine_des_fonds == 1000000 ? 'selected' : '') ?> value="1000000">Autre</option>
                            </select>
                        </td>
                    </tr>
                    <tr class="particulier">
                        <td colspan="2">
                            <div id="row_precision" style="display:none;">
                                <input type="text" id="preciser" name="preciser" value="<?= ($this->lenders_accounts->precision != '' ? $this->lenders_accounts->precision : '') ?>" class="input_large">
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </table>
        <br/><br/>

        <style type="text/css">
            .form-style-10{
                padding:20px;
                background: #FFF;
                border-radius: 10px;
                -webkit-border-radius:10px;
                -moz-border-radius: 10px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.13);
                -moz-box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.13);
                -webkit-box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.13);
            }
            .form-style-10 .inner-wrap{
                padding: 10px;
                background: #F8F8F8;
                border-radius: 6px;
                margin-bottom: 15px;
            }
            .form-style-10 .section{
                font: normal 20px 'Bitter', serif;
                color: #B20066;
                margin-bottom: 5px;
            }
            .form-style-10 .section span {
                background: #B20066;
                padding: 5px 10px 5px 10px;
                position: absolute;
                border-radius: 50%;
                -webkit-border-radius: 50%;
                -moz-border-radius: 50%;
                border: 4px solid #fff;
                font-size: 14px;
                margin-left: -45px;
                color: #fff;
                margin-top: -3px;
            }
            span.st {
                width: 25%;
            }
            .form-style-10 .add-attachment{
                border-collapse: separate;
                border-spacing: 2px;
            }
            .form-style-10 .add-attachment td{
                padding: 5px;
            }
            .td-greenPoint-status-valid {
                border-radius: 5px; background-color: #00A000; color: white; width: 250px;
            }
            .td-greenPoint-status-warning {
                border-radius: 5px; background-color: #f79232; color: white; width: 250px;
            }
            .td-greenPoint-status-error {
                border-radius: 5px; background-color: #ff0100; color: white; width: 250px;
            }
        </style>

        <h2>Pièces jointes<span></span></h2>
        <div class="form-style-10">
            <div class="section"><span>1</span>Identité</div>
            <div class="inner-wrap">
                <table id="identity-attachments" class="add-attachment">
                    <?php foreach ($this->aIdentity as $iIdType => $aAttachmentType): ?>
                        <tr>
                            <th><?= $aAttachmentType['label'] ?></th>
                            <td>
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $aAttachmentType['id'] ?>/file/<?= urlencode($aAttachmentType['path']) ?>">
                                    <?= $aAttachmentType['path'] ?>
                                </a>
                            </td>
                            <td class="td-greenPoint-status-<?= $aAttachmentType['color']?>">
                                <?= $aAttachmentType['greenpoint_label'] ?>
                            </td>
                            <td>
                                <input type="file" name="<?= $iIdType ?>" id="fichier_project_<?= $iIdType ?>"/>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="row row-upload">
                        <td>
                            <select class="select">
                                <option value="">Selectionnez un document</option>
                                <?php foreach ($this->aIdentityToAdd as $iIdType => $aAttachmentType): ?>
                                    <option value="<?= $iIdType ?>"><?= $aAttachmentType['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="file" class="file-field">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="btn btn-small btn-add-row">+</span>
                            <span style="margin-left: 5px;">Cliquez pour ajouter</span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="section"><span>2</span>Justificatif de domicile</div>
            <div class="inner-wrap">
                <table id="domicile-attachments" class="add-attachment">
                    <?php foreach ($this->aDomicile as $iIdType => $aAttachmentType): ?>
                        <tr>
                            <th><?= $aAttachmentType['label'] ?></th>
                            <td>
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $aAttachmentType['id'] ?>/file/<?= urlencode($aAttachmentType['path']) ?>">
                                    <?= $aAttachmentType['path'] ?>
                                </a>
                            </td>
                            <td class="td-greenPoint-status-<?= $aAttachmentType['color']?>">
                                <?= $aAttachmentType['greenpoint_label'] ?>
                            </td>
                            <td>
                                <input type="file" name="<?= $iIdType ?>" id="fichier_project_<?= $iIdType ?>"/>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="row row-upload">
                        <td>
                            <select class="select">
                                <option value="">Selectionnez un document</option>
                                <?php foreach ($this->aDomicileToAdd as $iIdType => $aAttachmentType): ?>
                                    <option value="<?= $iIdType ?>"><?= $aAttachmentType['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="file" class="file-field">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="btn btn-small btn-add-row">+</span>
                            <span style="margin-left: 5px;">Cliquez pour ajouter</span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="section"><span>3</span>RIB et Jsutificatif fiscal</div>
            <div class="inner-wrap">
                <table id="rib-attachments" class="add-attachment">
                    <?php foreach ($this->aRibAndFiscale as $iIdType => $aAttachmentType): ?>
                        <tr>
                            <th><?= $aAttachmentType['label'] ?></th>
                            <td>
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $aAttachmentType['id'] ?>/file/<?= urlencode($aAttachmentType['path']) ?>">
                                    <?= $aAttachmentType['path'] ?>
                                </a>
                            </td>
                            <td class="td-greenPoint-status-<?= $aAttachmentType['color']?>">
                                <?= $aAttachmentType['greenpoint_label'] ?>
                            </td>
                            <td>
                                <input type="file" name="<?= $iIdType ?>" id="fichier_project_<?= $iIdType ?>"/>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="row row-upload">
                        <td>
                            <select class="select">
                                <option value="">Selectionnez un document</option>
                                <?php foreach ($this->aRibAndFiscaleToAdd as $iIdType => $aAttachmentType): ?>
                                    <option value="<?= $iIdType ?>"><?= $aAttachmentType['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="file" class="file-field">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="btn btn-small btn-add-row">+</span>
                            <span style="margin-left: 5px;">Cliquez pour ajouter</span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="section"><span>4</span>Autre</div>
            <div class="inner-wrap">
                <table id="other-attachments" class="add-attachment">
                    <?php foreach ($this->aOther as $iIdType => $aAttachmentType): ?>
                        <tr>
                            <th><?= $aAttachmentType['label'] ?></th>
                            <td>
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $aAttachmentType['id'] ?>/file/<?= urlencode($aAttachmentType['path']) ?>">
                                    <?= $aAttachmentType['path'] ?>
                                </a>
                            </td>
                            <td class="td-greenPoint-status-<?= $aAttachmentType['color']?>">
                                 <?= $aAttachmentType['greenpoint_label'] ?>
                            </td>
                            <td>
                                <input type="file" name="<?= $iIdType ?>" id="fichier_project_<?= $iIdType ?>"/>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th>Mandat</th>
                        <td>
                            <?php if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) : ?>
                                <a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $this->clients_mandats->name ?>"><?= $this->clients_mandats->name ?></a>
                            <?php endif; ?>
                        </td>
                        <td><input type="file" name="mandat"></td>
                    </tr>
                    <tr class="row row-upload">
                        <td>
                            <select class="select">
                                <option value="">Selectionnez un document</option>
                                <?php foreach ($this->aOtherToAdd as $iIdType => $aAttachmentType): ?>
                                    <option value="<?= $iIdType ?>"><?= $aAttachmentType['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="file" class="file-field">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="btn btn-small btn-add-row">+</span>
                            <span style="margin-left: 5px;">Cliquez pour ajouter</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        </br></br>
        <div class="gauche">
            <br/><br/>
            <h2>Historique :</h2>
            <style>
                .histo_status_client li {
                    margin-left: 15px;
                    list-style: disc;
                }
            </style>
            <!-- Lender tax country history -->
            <?php
            if (false === empty($this->aTaxationCountryHistory)): ?>
                <h3>Historique Fiscal</h3>
                <table class="tablesorter histo_status_client">
                    <?php if (array_key_exists('error', $this->aTaxationCountryHistory)): ?>
                        <tr>
                            <td><?= $this->aTaxationCountryHistory['error'] ?></td>
                        </tr>
                    <?php else:
                        foreach ($this->aTaxationCountryHistory as $aRow) { ?>
                            <tr>
                                <td>Nouveau pays fiscal: <b><?= $aRow['country_name'] ?></b>. Modifié par <?= $aRow['user_firstname'] ?> <?= $aRow['user_name'] ?> le <?= date('d/m/Y H:i:s', strtotime($aRow['added'])) ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php endif; ?>
                </table>
            <?php endif; ?>
            <!-- Lender status history -->
            <?php if (false === empty($this->lActions)) : ?>
                <style>
                    .histo_status_client li {
                        margin-left: 15px;
                        list-style: disc;
                    }
                </style>
                <div style="margin-top: 15px;">
                    <h3>Historique des status client</h3>
                    <table class="tablesorter histo_status_client">
                    <?php foreach ($this->lActions as $historyEntry) {
                        $this->oClientsStatusForHistory->get($historyEntry['id_client_status'], 'id_client_status');
                        $this->users->get($historyEntry['id_user'], 'id_user');

                        switch ($this->oClientsStatusForHistory->status) {
                            case \clients_status::TO_BE_CHECKED: ?>
                                <tr>
                                    <td>
                                        <?php if (empty($historyEntry['content'])) : ?>
                                            Création de compte le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?><br>
                                        <?php else: ?>
                                            Compte modifié le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?><br>
                                            <?= $historyEntry['content'] ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php break;
                            case \clients_status::COMPLETENESS: ?>
                                <tr>
                                    <td>
                                        Complétude le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?><br/>
                                        par <?= $this->users->name ?><br/>
                                        <?= $historyEntry['content'] ?>
                                    </td>
                                </tr>
                                <?php break;
                            case \clients_status::COMPLETENESS_REMINDER: ?>
                                <tr>
                                    <td>
                                        Complétude Relance le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?><br/>
                                        <?= $historyEntry['content'] ?>
                                    </td>
                                </tr>
                                <?php break;
                            case \clients_status::COMPLETENESS_REPLY: ?>
                                <tr>
                                    <td>
                                        Complétude Reponse le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?><br/>
                                        <?= $historyEntry['content'] ?>
                                    </td>
                                </tr>
                                <?php break;
                            case \clients_status::MODIFICATION: ?>
                                <tr>
                                    <td>
                                        Compte modifié le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?><br/>
                                        <?= $historyEntry['content'] ?>
                                    </td>
                                </tr>
                                <?php break;
                            case \clients_status::VALIDATED: ?>
                                <tr>
                                    <td>
                                        <?php if (empty($historyEntry['content'])) : ?>
                                            Compte validé le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?><br />par <?= $this->users->name ?></td>
                                        <?php else : ?>
                                            <?= $historyEntry['content'] . ' le ' . date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?>
                                            <br>par <?= $this->users->name ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php break;
                            case \clients_status::CLOSED_LENDER_REQUEST : ?>
                                <tr>
                                    <td>Compte clôturé à la demande du prêteur (mis hors ligne) <br />
                                        le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?><br />
                                        par <?= $this->users->name ?></td>
                                </tr>
                                <?php break;
                            case \clients_status::CLOSED_BY_UNILEND : ?>
                                <tr>
                                    <td>Compte clôturé par Unilend (mis hors ligne) <br />
                                        le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?> <br />
                                        par <?= $this->users->name ?><br />
                                        <?= $historyEntry['content'] ?>
                                    </td>
                                </tr>
                                <?php break;
                            case \clients_status::CLOSED_DEFINITELY: ?>
                                <tr>
                                    <td>
                                        Compte definitvement fermé le <?= date('d/m/Y H:i:s', strtotime($historyEntry['added'])) ?>
                                        <br>
                                        <?= $historyEntry['content'] ?>
                                        <br>par <?= $this->users->name ?>
                                    </td>
                                </tr>
                                <?php break;
                        }
                    }
                    ?>
                    </table>
                </div>
            <?php endif; ?>
            <!-- Lender tax exemption history -->
            <?php if (false === empty($this->taxExemptionUserHistoryAction)): ?>
                <table class="tablesorter histo_status_client">
                    <?php foreach ($this->taxExemptionUserHistoryAction as $actions): ?>
                        <?php foreach ($actions['modifications'] as $action): ?>
                            <tr>
                                <td>Dispense de prélèvement fiscal <b>année <?= $action['year'] ?></b>.
                                    <?php if ('adding' === $action['action']): ?>
                                        Ajoutée
                                    <?php elseif ('deletion' === $action['action']): ?>
                                        Supprimée
                                    <?php endif; ?>
                                    le <?= \DateTime::createFromFormat('Y-m-d H:i:s', $actions['date'])->format('d/m/Y H:i:s') ?> par <?= $actions['user'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        <div class="droite">
            <?php if($this->clients_status->status != \clients_status::CLOSED_DEFINITELY) : ?>
            <table class="tabLesStatuts">
                <tr>
                    <td>
                        <?php if (isset($_SESSION['email_completude_confirm']) && $_SESSION['email_completude_confirm']
                            || isset($_SESSION['compte_valide']) && $_SESSION['compte_valide']) : ?>
                        <a href="<?= $this->lurl ?>/preteurs/activation" class="btn_link btnBackListe">Revenir à la liste<br/> de contôle</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($_SESSION['email_completude_confirm']) && $_SESSION['email_completude_confirm']) : ?>
                            <img src="<?= $this->surl ?>/images/admin/mail.png" alt="email" style="position: relative; top: 7px;"/>
                            <span style="color:green;">Votre email a été envoyé</span>
                            <?php unset($_SESSION['email_completude_confirm']); ?>
                            <?php unset($_SESSION['compte_valide']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php if ($this->clients_status->status != clients_status::VALIDATED && $this->clients->status == \clients::STATUS_ONLINE) : ?>
                        <input type="button" id="valider_preteur" class="btn" value="Valider le prêteur">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php if (false === in_array($this->clients_status->status, array(\clients_status::CLOSED_BY_UNILEND, \clients_status::CLOSED_LENDER_REQUEST))) : ?>
                        <input type="button" id="completude_edit" class="btn btnCompletude" value="Complétude">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><div style="padding-bottom: 25px;"></div></td></tr>
                <tr>
                    <td colspan="2">
                        <?php if ($this->clients->status == \clients::STATUS_ONLINE) :?>
                            <input type="button"
                                   onclick="if(confirm('Voulez vous mettre le client hors ligne et changer son status en Clôturé par Unilend')){window.location = '<?= $this->lurl ?>/preteurs/lenderOnlineOffline/status/<?= $this->lenders_accounts->id_lender_account ?>/<?= \clients::STATUS_OFFLINE ?>';}"
                                   class="btnRouge"
                                   value="Hors ligne / Clôturé par Unilend">
                        <?php else: ?>
                            <input type="button"
                                   onclick="if(confirm('Voulez vous remettre le client en ligne et revenir au status avant la mis hors ligne ?')){window.location = '<?= $this->lurl ?>/preteurs/lenderOnlineOffline/status/<?= $this->lenders_accounts->id_lender_account ?>/<?= \clients::STATUS_ONLINE ?>';}"
                                   class="btn"
                                   value="En ligne / Status avant mis hors ligne">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php if (false === in_array($this->clients_status->status, array(\clients_status::CLOSED_LENDER_REQUEST)) ) : ?>
                        <input type="button"
                               onclick="if(confirm('Voulez vous vraiment desactiver ce prêteur (mettre son compte hors ligne et changer son stauts en Clôturé à la demande du preteur ?')){window.location = '<?= $this->lurl ?>/preteurs/lenderOnlineOffline/deactivate/<?= $this->lenders_accounts->id_lender_account ?>/<?= \clients::STATUS_OFFLINE ?>';}"
                               class="btnRouge" value="Hors ligne / Clôturé à la demande du client">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <br/>
            <div class="message_completude">
                <h2>Complétude - Personnalisation du message</h2>

                <div class="liwording">
                    <table>
                        <?php foreach($this->completude_wording as $key => $message): ?>
                            <tr>
                                <td><img class="add" id="add-<?= $key ?>" src="<?= $this->surl ?>/images/admin/add.png"></td>
                                <td><span class="content-add-<?= $key ?>"><?= $message ?></span></td>
                            </tr>
                            <?php if (substr($key, -1, 1) == 3) : ?>
                                <tr><td colspan="2">&nbsp;</td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
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
                                <textarea name="content_email_completude" id="content_email_completude"><?= isset($_SESSION['content_email_completude'][$this->params[0]]) ? $text = str_replace(array('<br>', '<br />'), '', $_SESSION['content_email_completude'][$this->params[0]]) : '' ?></textarea>
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
            <?php endif; ?>
        </div>
        <div class="clear"></div>
        <br/><br/>
        <div class="content_cgv_accept">
            <h2>Acceptation CGV</h2>
            <?php if (count($this->lAcceptCGV) > 0) : ?>
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
                    <?php foreach ($this->lAcceptCGV as $a) :
                        $this->tree->get(array('id_tree' => $a['id_legal_doc'], 'id_langue' => $this->language));
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($this->tree->added)) ?></td>
                            <td><?= $this->tree->title ?></td>
                            <td><a target="_blank" href="<?= $this->furl . '/' . $this->tree->slug ?>"><?= $this->furl . '/' . $this->tree->slug ?></a></td>
                            <td><?= date('d/m/Y H:i:s', strtotime($a['updated'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p style="text-align:center;" >Aucun CGV signé</p>
            <?php endif; ?>
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

    $(".add").click(function() {
        var id = $(this).attr("id");
        addWordingli(id);
    });

    $("#completude_edit").click(function() {
        $('.message_completude').slideToggle();
    });


    $("#valider_preteur").click(function() {
        $("#statut_valider_preteur").val('1');
        $("#form_etape1").submit();
    });

    $("#previsualisation").click(function() {
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

    <?php if ($this->meme_adresse_fiscal == 0) : ?>
        $('.meme-adresse').show();
    <?php endif; ?>

    <?php if ($this->companies->status_client == 1) : ?>
        $('.statut_dirigeant_e').hide('slow');
        $('.statut_dirigeant_e3').hide('slow');
    <?php elseif($this->companies->status_client == 2) : ?>
        $('.statut_dirigeant_e').show('slow');
        $('.statut_dirigeant_e3').hide('slow');
    <?php elseif($this->companies->status_client == 3) : ?>
        $('.statut_dirigeant_e').show('slow');
        $('.statut_dirigeant_e3').show('slow');
    <?php endif; ?>

    $('#meme-adresse').click(function () {
        if ($(this).prop('checked')) {
            $('.meme-adresse').hide();
        } else {
            $('.meme-adresse').show();
        }
    });

    $('#type1,#type2').click(function() {
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

    $('#enterprise1').click(function() {
        if ($(this).prop('checked')) {
            $('.statut_dirigeant_e').hide('slow');
            $('.statut_dirigeant_e3').hide('slow');
        }
    });
    $('#enterprise2').click(function() {
        if ($(this).prop('checked')) {
            $('.statut_dirigeant_e').show('slow');
            $('.statut_dirigeant_e3').hide('slow');
        }
    });
    $('#enterprise3').click(function() {
        if ($(this).prop('checked')) {
            $('.statut_dirigeant_e').show('slow');
            $('.statut_dirigeant_e3').show('slow');
        }
    });

    $("#change_bank_account_btn").click(function() {
        var rib = {
            bic: $('#bic').val(),
            iban1: $('#iban1').val(),
            iban2: $('#iban2').val(),
            iban3: $('#iban3').val(),
            iban4: $('#iban4').val(),
            iban5: $('#iban5').val(),
            iban6: $('#iban6').val(),
            iban7: $('#iban7').val(),
            id_client: "<?= $this->clients->id_client ?>"
        };

        $.post(add_url + "/preteurs/change_bank_account", rib).done(function (data) {
            oJson = JSON.parse(data);
            var color = 'red';
            if (typeof oJson.text !== 'undefined' && typeof oJson.severity !== 'undefined') {
                switch (oJson.severity) {
                    case 'valid':
                        color = 'green';
                        break;
                    case 'warning':
                        color = 'orange';
                        break;
                    case 'error':
                        color = 'red';
                        break;
                }
                $('#iban_ok').text(oJson.text);
                $('#iban_ok').css("color", color);
            } else {
                $('#iban_ok').text('Une erreur est survenue');
                $('#iban_ok').css("color", color);
            }
        });
    });

    $("#origine_des_fonds").change(function() {
        if ($(this).val() == '1000000') {
            $("#row_precision").show();
        } else {
            $("#row_precision").hide();
        }
    });

    <?php if ($this->lenders_accounts->origine_des_fonds == 1000000): ?>
        $("#row_precision").show();
    <?php endif; ?>
</script>
