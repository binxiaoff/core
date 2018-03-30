<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus, Companies
};

?>
<script type="text/javascript">
    $(function() {
        $(".histo_status_client").tablesorter({headers: {8: {sorter: false}}});

        $(".cgv_accept").tablesorter({headers: {}});

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-90)?>:<?=(date('Y')-17)?>'
        });

        $("#debut").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 1)?>:<?=(date('Y') + 16)?>'
        });

        $("#fin").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 1)?>:<?=(date('Y') + 16)?>'
        });

        initAutocompleteCity($('#ville'), $('#cp'));
        initAutocompleteCity($('#ville2'), $('#cp2'));
        initAutocompleteCity($('#com-naissance'), $('#insee_birth'));
    });
</script>
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="contenu">
    <?php if (empty($this->clients->id_client)) : ?>
        <div class="attention">Attention : Client <?= $this->params[0] ?> innconu</div>
    <?php elseif (empty($this->wallet)) : ?>
        <div class="attention">Attention : ce compte n’est pas un compte prêteur</div>
    <?php else : ?>
    <div><?= $this->clientStatusMessage ?></div>
    <div class="row">&nbsp;</div>
    <div class="row">&nbsp;</div>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->clients->id_client ?>" class="btn_link">Enchères</a>
        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->clients->id_client ?>" class="btn_link">Consulter Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->clients->id_client ?>" class="btn_link">Historique des emails</a>
        <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->clients->id_client ?>" class="btn_link">Portefeuille & Performances</a>
    </div>
    <?php if (isset($_SESSION['error_email_exist']) && $_SESSION['error_email_exist'] != '') : ?>
        <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
        <?php unset($_SESSION['error_email_exist']); ?>
    <?php endif; ?>
    <?php if (in_array($this->clients->type, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) : ?>
        <form action="" method="post" enctype="multipart/form-data" id="form_etape1">
        <table class="form" style="margin: auto;">
            <tr class="particulier">
                <th>ID client :</th>
                <td>
                    <span><?= $this->clients->id_client ?></span>
                </td>
                <td><h3>Exonération fiscale</h3></td>
                <td><h3>Informations MRZ</h3></td>
            </tr>
                <tr class="particulier">
                    <th>Civilité :</th>
                    <td>
                        <input type="radio" name="civilite" id="civilite1" <?= ($this->clients->civilite == 'Mme' ? 'checked' : '') ?> value="Mme"> <label for="civilite1">Madame</label>
                        <input type="radio" name="civilite" id="civilite2" <?= ($this->clients->civilite == 'M.' ? 'checked' : '') ?> value="M."> <label for="civilite2">Monsieur</label>
                    </td>
                    <td rowspan="6" style="vertical-align: top">
                        <?php if (false === in_array($this->iNextYear, $this->exemptionYears)) : ?>
                            <a id="confirm_exemption" href="<?= $this->lurl ?>/thickbox/confirm_tax_exemption/<?= $this->iNextYear ?>/check" class="thickbox cboxElement">
                                <input type="checkbox" id="tax_exemption_<?= $this->iNextYear ?>" name="tax_exemption[<?= $this->iNextYear ?>]" value="1">
                            </a>
                            <label for="tax_exemption_<?= $this->iNextYear ?>"><?= $this->iNextYear ?></label>
                            <br>
                        <?php endif; ?>
                        <?php foreach ($this->exemptionYears as $iExemptionYear) : ?>
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
                                <td><?= isset($this->lenderIdentityMRZData) ? $this->lenderIdentityMRZData->getIdentityNationality() : '' ?></td>
                            </tr>
                            <tr class="particulier">
                                <th>Pays émetteur :</th>
                                <td><?= isset($this->lenderIdentityMRZData) ? $this->lenderIdentityMRZData->getIdentityIssuingCountry() : '' ?></td>
                            </tr>
                            <tr class="particulier">
                                <th>Autorité émettrice :</th>
                                <td><?= isset($this->lenderIdentityMRZData) ? $this->lenderIdentityMRZData->getIdentityIssuingAuthority() : '' ?></td>
                            </tr>
                            <tr class="particulier">
                                <th>N°. de la pièce :</th>
                                <td><?= isset($this->lenderIdentityMRZData) ? $this->lenderIdentityMRZData->getIdentityDocumentNumber() : '' ?></td>
                            </tr>
                            <?php if (false === empty($this->hostIdentityMRZData)) : ?>
                                <tr>
                                    <th colspan="2" style="text-align: center;">Hébergeur</th>
                                </tr>
                                <tr class="particulier">
                                    <th>Nationalité :</th>
                                    <td><?= isset($this->hostIdentityMRZData) ? $this->hostIdentityMRZData->getIdentityNationality() : '' ?></td>
                                </tr>
                                <tr class="particulier">
                                    <th>Pays émetteur :</th>
                                    <td><?= isset($this->hostIdentityMRZData) ? $this->hostIdentityMRZData->getIdentityIssuingCountry() : '' ?></td>
                                </tr>
                                <tr class="particulier">
                                    <th>Autorité émettrice :</th>
                                    <td><?= isset($this->hostIdentityMRZData) ? $this->hostIdentityMRZData->getIdentityIssuingAuthority() : '' ?></td>
                                </tr>
                                <tr class="particulier">
                                    <th>N°. de la pièce :</th>
                                    <td><?= isset($this->hostIdentityMRZData) ? $this->hostIdentityMRZData->getIdentityDocumentNumber() : '' ?></td>
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
                        <input style="font-size: 11px;" type="button" id="generer_mdp2" name="generer_mdp2" value="Générer un nouveau mot de passe" class="btn-primary" onclick="generer_le_mdp('<?= $this->clients->id_client ?>')">
                        <span style="margin-left:5px;color:green; display:none;" class="success">Email envoyé</span>
                        <span style="margin-left:5px;color:orange; display:none;" class="warning">Email non envoyé</span>
                        <span style="margin-left:5px;color:red; display:none;" class="error">Erreur</span>
                    </td>
                </tr>
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
        </table>
    <?php else : ?>
        <form method="post" action="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->clients->id_client ?>">
        <div class="row">
            <div class="col-md-12">
                <h2>Société</h2>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="societe">Raison sociale</label>
                        <input type="text" name="raison-sociale" id="raison-sociale" value="<?= $this->companies->name ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="form-juridique">Forme juridque</label>
                        <input type="text" name="form-juridique" id="form-juridique" value="<?= $this->companies->forme ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="capital-sociale">Capital social</label>
                        <input type="text" name="capital-sociale" id="capital-sociale" value="<?= $this->companies->capital ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="tribunal_com">Tribunal de Commerce</label>
                        <input type="text" name="tribunal_com" id="tribunal_com" value="<?= $this->companies->tribunal_com ?>"  class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="siren">SIREN</label>
                        <input type="text" name="siren" id="siren" value="<?= $this->companies->siren ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="siret">SIRET</label>
                        <input type="text" name="siret" id="siret" value="<?= $this->companies->siret ?>" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="phone-societe">Téléphone</label>
                        <input type="text" name="phone-societe" id="phone-societe" value="<?= $this->companies->phone ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                </div>
                </div>
            </div>
            <?php if (null !== $this->companyEntity->getIdAddress()) : ?>
                <div class="col-md-6">
                    <h3>Adresse fiscale validée</h3>
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="adresse">Adresse</label>
                            <input type="text" name="adresse" id="adresse" value="<?= $this->companyEntity->getIdAddress()->getAddress() ?>" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="cp">Code postal</label>
                            <input type="text" name="cp" id="cp" value="<?= $this->companyEntity->getIdAddress()->getZip() ?>" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ville">Ville</label>
                            <input type="text" name="ville" id="ville" value="<?= $this->companyEntity->getIdAddress()->getCity() ?>" class="form-control">
                        </div>
                    </div>
                </div>
            <? endif; ?>
            <?php if (null !== $this->lastModifiedCompanyAddress && $this->lastModifiedCompanyAddress !== $this->companyEntity->getIdAddress()): ?>
                <div class="col-md-6">
                    <h3>Adresse fiscale en attente de validation</h3>
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="adresse">Adresse</label>
                            <input type="text" name="adresse" id="adresse" value="<?= $this->lastModifiedCompanyAddress->getAddress() ?>" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="cp">Code postal</label>
                            <input type="text" name="cp" id="cp" value="<?= $this->lastModifiedCompanyAddress->getZip() ?>" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ville">Ville</label>
                            <input type="text" name="ville" id="ville" value="<?= $this->lastModifiedCompanyAddress->getCity() ?>" class="form-control">
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <div class="col-md-12">
                <h3 class="meme-adresse" <?= (null !== $this->companyEntity->getIdPostalAddress() ? '' : 'style="display:none;"') ?>>Adresse postale</h3>
                <div class="row meme-adresse" <?= (null !== $this->companyEntity->getIdPostalAddress() ? '' : 'style="display:none;"') ?>>
                    <div class="form-group col-md-6">
                        <label for="adresse">Adresse postale</label>
                        <input type="text" name="adresse2" id="adresse2" value="<?= null !== $this->companyEntity->getIdPostalAddress() ? $this->companyEntity->getIdPostalAddress()->getAddress() : '' ?>" class="form-control">
                    </div>
                </div>
                <div class="row meme-adresse" <?= (null !== $this->companyEntity->getIdPostalAddress() ? '' : 'style="display:none;"' )?>>
                    <div class="form-group col-md-3">
                        <label for="cp">Code postal</label>
                        <input type="text" name="cp2" id="cp2" value="<?= null !== $this->companyEntity->getIdPostalAddress() ? $this->companyEntity->getIdPostalAddress()->getZip(): '' ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ville">Ville</label>
                        <input type="text" name="ville2" id="ville2" value="<?= null !== $this->companyEntity->getIdPostalAddress() ? $this->companyEntity->getIdPostalAddress()->getCity() : '' ?>" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <input type="checkbox" name="meme-adresse" id="meme-adresse" <?= (null === $this->companyEntity->getIdPostalAddress() ? 'checked' : '') ?>>
                        <label for="meme-adresse">Mon adresse de correspondance est identique à mon adresse fiscale </label>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <h2>Representant legal</h2>
                <div class="row">
                    <div class="form-group col-md-12">
                        <input <?= ($this->clients->civilite == 'Mme' ? 'checked' : '') ?> type="radio" name="civilite_e" id="civilite_e1" value="Mme"/>
                        <label for="civilite_e1">Madame</label>
                        <input <?= ($this->clients->civilite == 'M.' ? 'checked' : '') ?> type="radio" name="civilite_e" id="civilite_e2" value="M."/>
                        <label for="civilite_e2">Monsieur</label>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="nom">Nom</label>
                        <input type="text" name="nom_e" id="nom_e" value="<?= $this->clients->nom ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="prenom">Prénom</label>
                        <input type="text" name="prenom_e" id="prenom_e" value="<?= $this->clients->prenom ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="email">Email</label>
                        <input type="text" name="email_e" id="email_e" value="<?= $this->clients->email ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="telephone">Téléphone</label>
                        <input type="text" name="phone_e" id="phone_e" value="<?= $this->clients->telephone ?>" class="form-control">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="fonction_e">Fonction :</label>
                        <input type="text" name="fonction_e" id="fonction_e" value="<?= $this->clients->fonction ?>" class="form-control">
                    </div>
                </div>
                <div class="row statut_dirigeant_e societe" <?= ($this->companies->status_client == Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT ? '' : 'style="display:none;"') ?>>
                    <div class="form-group col-md-6">
                        <label for="status_conseil_externe_entreprise">Autre : </label>
                        <select name="status_conseil_externe_entreprise" id="status_conseil_externe_entreprise" class="form-control">
                            <option value="0">Choisir</option>
                            <?php foreach ($this->conseil_externe as $k => $conseil_externe) : ?>
                                <option <?= ($this->companies->status_conseil_externe_entreprise == $k ? 'selected' : '') ?> value="<?= $k ?>" ><?= $conseil_externe ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6 ">
                        <label for="preciser_conseil_externe_entreprise">Autre (préciser) :</label>
                        <input type="text" name="preciser_conseil_externe_entreprise" id="preciser_conseil_externe_entreprise" value="<?= $this->companies->preciser_conseil_externe_entreprise ?>" class="form-control"/>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="row">
                    <div class="form-group col-md-12">
                        <input <?= ($this->companies->status_client == Companies::CLIENT_STATUS_MANAGER ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise1" value="1"/>
                        <label for="enterprise1">Je suis le dirigeant de l'entreprise </label>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <input <?= ($this->companies->status_client == Companies::CLIENT_STATUS_DELEGATION_OF_POWER ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise2" value="2"/>            <label for="enterprise2">Je ne suis pas le dirigeant de l'entreprise mais je bénéficie d'une délégation de pouvoir </label>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <input <?= ($this->companies->status_client == Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT ? 'checked' : '') ?> type="radio" name="enterprise" id="enterprise3" value="3"/>           <label for="enterprise3"> Je suis un conseil externe de l'entreprise </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 statut_dirigeant_e societe"<?= (Companies::CLIENT_STATUS_MANAGER == $this->companies->status_client ? 'style="display:none;"' : '') ?>>
                <h2 >Identification du dirigeant :</h2>
                <div class="row">
                    <div class="form-group col-md-6">
                        <input <?= ($this->companies->civilite_dirigeant == 'Mme' ? 'checked' : '') ?> type="radio" name="civilite2_e" id="civilite21_e" value="Mme"/>
                        <label for="civilite21_e">Madame</label>
                        <input <?= ($this->companies->civilite_dirigeant == 'M.' ? 'checked' : '') ?> type="radio" name="civilite2_e" id="civilite22_e" value="M."/>
                        <label for="civilite22_e">Monsieur</label>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="nom2_e">Nom :</label>
                        <input type="text" name="nom2_e" id="nom2_e" class="form-control" value="<?= $this->companies->nom_dirigeant ?>"/>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="prenom2_e">Prénom :</label>
                        <input type="text" name="prenom2_e" id="prenom2_e" class="form-control" value="<?= $this->companies->prenom_dirigeant ?>"/>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="email2_e">Email :</label>
                        <input type="text" name="email2_e" id="email2_e" class="form-control" value="<?= $this->companies->email_dirigeant ?>"/>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="phone2_e">Téléphone :</label>
                        <input type="text" name="phone2_e" id="phone2_e" class="form-control" value="<?= $this->companies->phone_dirigeant ?>"/>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label for="fonction2_e">Fonction :</label>
                        <input type="text" name="fonction2_e" id="fonction2_e" class="form-control" value="<?= $this->companies->fonction_dirigeant ?>"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right">
            <input type="hidden" name="send_edit_preteur" id="send_edit_preteur"/>
            <button type="submit" class="btn-primary">Modifier</button>
        </div>
    </form>
    <?php endif; ?>
    <hr>
    <div>
        <input style="font-size: 11px;" type="button" id="generer_mdp2" name="generer_mdp2" value="Générer un nouveau mot de passe" class="btn-primary" onclick="generer_le_mdp('<?= $this->clients->id_client ?>')">
        <span style="margin-left:5px;color:green; display:none;" class="success">Email envoyé</span>
        <span style="margin-left:5px;color:orange; display:none;" class="warning">Email non envoyé</span>
        <span style="margin-left:5px;color:red; display:none;" class="error">Erreur</span>
    </div>
    <hr>
    <h2>Informations bancaires</h2>
    <table class="form" style="margin: auto;">
        <input type="hidden" value="<?= (null !== $this->currentBankAccount) ? $this->currentBankAccount->getId() : ''?>" name="id_bank_account" id="id_bank_account">
        <tr>
            <th>BIC :</th>
            <td><?= (null !== $this->currentBankAccount) ? $this->currentBankAccount->getBic() : '' ?></td>
        </tr>
        <tr>
            <th>IBAN :</th>
            <td><?= (null !== $this->currentBankAccount) ? chunk_split($this->currentBankAccount->getIban(), 4, ' ') : '' ?></td>
        </tr>
        <?php if ($this->origine_fonds[0] != false) : ?>
            <tr class="particulier">
                <th colspan="2" style="text-align:left;"><label for="origines">Quelle est l'origine des fonds que vous déposez sur Unilend ?</label></th>
            </tr>
            <tr class="particulier">
                <td colspan="2">
                    <select name="origine_des_fonds" id="origine_des_fonds" class="select">
                        <option value="0">Choisir</option>
                        <?php foreach ($this->origine_fonds as $k => $origine_fonds) : ?>
                            <option <?= ($this->clients->funds_origin == $k + 1 ? 'selected' : '') ?> value="<?= $k + 1 ?>" ><?= $origine_fonds ?></option>
                        <?php endforeach; ?>
                        <option <?= ($this->clients->funds_origin == 1000000 ? 'selected' : '') ?> value="1000000">Autre</option>
                    </select>
                </td>
            </tr>
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
                <?php foreach ($this->attachmentGroups as $key => $attachmentGroup) : ?>
                <div class="section"><span><?= $key + 1 ?></span><?= $attachmentGroup['title'] ?></div>
                <div class="inner-wrap">
                    <table id="identity-attachments" class="add-attachment">
                        <?php
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $attachment */
                        foreach ($attachmentGroup['attachments'] as $attachment) :
                            $greenpointLabel       = 'Non Contrôlé par GreenPoint';
                            $greenpointColor       = 'error';
                            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment $greenPointAttachment */
                            $greenPointAttachment  = $attachment->getGreenpointAttachment();
                            if ($greenPointAttachment) {
                                $greenpointLabel = $greenPointAttachment->getValidationStatusLabel();
                                if (0 == $greenPointAttachment->getValidationStatus()) {
                                    $greenpointColor = 'error';
                                } elseif (8 > $greenPointAttachment->getValidationStatus()) {
                                    $greenpointColor = 'warning';
                                } else {
                                    $greenpointColor = 'valid';
                                }
                            }
                            ?>
                            <tr>
                                <th><?= $attachment->getType()->getLabel() ?></th>
                                <td>
                                    <a href="<?= $this->url ?>/attachment/download/id/<?= $attachment->getId() ?>/file/<?= urlencode($attachment->getPath()) ?>">
                                        <?= $attachment->getPath() ?>
                                    </a>
                                </td>
                                <td class="td-greenPoint-status-<?= $greenpointColor?>">
                                    <?= $greenpointLabel ?>
                                </td>
                                <td>
                                    <input type="file" name="<?= $attachment->getType()->getId() ?>" id="fichier_project_<?= $attachment->getType()->getId() ?>"/>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ('Autre' === $attachmentGroup['title']) : ?>
                            <tr>
                                <th>Mandat</th>
                                <td>
                                    <?php if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) : ?>
                                        <a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $this->clients_mandats->name ?>"><?= $this->clients_mandats->name ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><input type="file" name="mandat"></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="row row-upload">
                            <td>
                                <select class="select">
                                    <option value="">Selectionnez un document</option>
                                    <?php
                                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $attachmentType */
                                    foreach ($attachmentGroup['typeToAdd'] as $attachmentType) :
                                    ?>
                                        <option value="<?= $attachmentType->getId() ?>"><?= $attachmentType->getLabel() ?></option>
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
                <?php endforeach; ?>
            </div>
            </br></br>
            <div class="gauche">
                <br/><br/>
                <h2>Historiques</h2>
                <style>
                    .histo_status_client li {
                        margin-left: 15px;
                        list-style: disc;
                    }
                </style>
                <!-- Lender tax country history -->
                <?php
                if (false === empty($this->aTaxationCountryHistory)) : ?>
                    <h3>Historique Fiscal</h3>
                    <table class="tablesorter histo_status_client">
                        <?php if (array_key_exists('error', $this->aTaxationCountryHistory)) : ?>
                            <tr>
                                <td><?= $this->aTaxationCountryHistory['error'] ?></td>
                            </tr>
                        <?php else:
                            foreach ($this->aTaxationCountryHistory as $aRow) { ?>
                                <tr>
                                    <td>
                                        Nouveau pays fiscal: <b><?= $aRow['country_name'] ?></b>.
                                        Modifié par <?= $aRow['user_firstname'] ?> <?= $aRow['user_name'] ?> le <?= date('d/m/Y H:i:s', strtotime($aRow['added'])) ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php endif; ?>
                    </table>
                <?php endif; ?>
                <!-- Lender status history -->
                <?php if (false === empty($this->statusHistory)) : ?>
                    <style>
                        .histo_status_client li {
                            margin-left: 15px;
                            list-style: disc;
                        }
                    </style>
                    <div style="margin-top: 15px;">
                    <h3>Historique des statuts client</h3>
                        <table class="tablesorter histo_status_client">
                        <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatusHistory $historyEntry */ ?>
                        <?php foreach ($this->statusHistory as $historyEntry) : ?>
                        <?php
                            switch ($historyEntry->getIdStatus()->getId()) {
                                case ClientsStatus::STATUS_CREATION: ?>
                                    <tr>
                                        <td>Création de compte le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?></td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_TO_BE_CHECKED: ?>
                                    <tr>
                                        <td>
                                            <?php if (empty($historyEntry->getContent())) : ?>
                                                Fin d'inscription le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                            <?php else: ?>
                                                Compte modifié le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                                <?= $historyEntry->getContent() ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_COMPLETENESS: ?>
                                    <tr>
                                        <td>
                                            Complétude le <?= $historyEntry->getAdded()->format('d/m/Y H:i:s') ?><br>
                                            par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?><br>
                                            <?= $historyEntry->getContent() ?>
                                        </td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_COMPLETENESS_REMINDER: ?>
                                    <tr>
                                        <td>
                                            Complétude Relance le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                            <?= $historyEntry->getContent() ?>
                                        </td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_COMPLETENESS_REPLY: ?>
                                    <tr>
                                        <td>
                                            Complétude Reponse le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                            <?= $historyEntry->getContent() ?>
                                        </td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_MODIFICATION: ?>
                                    <tr>
                                        <td>
                                            Compte modifié le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                            <?= $historyEntry->getContent() ?>
                                        </td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_VALIDATED: ?>
                                    <tr>
                                        <td>
                                            <?php if ($historyEntry->getIdUser()->getIdUser() > 0) : ?>
                                                Compte validé le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                                par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?>
                                            <?php else : ?>
                                                <?= $historyEntry->getContent() ?> le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                                par <?= (-1 === $historyEntry->getIdUser()->getIdUser()) ? ' le CRON de validation automatique Greenpoint' : $historyEntry->getIdUser()->getName() ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_CLOSED_LENDER_REQUEST: ?>
                                    <tr>
                                        <td>
                                            Compte clôturé à la demande du prêteur (mis hors ligne)<br>
                                            le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                            par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?>
                                        </td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_CLOSED_BY_UNILEND: ?>
                                    <tr>
                                        <td>
                                            Compte clôturé par Unilend (mis hors ligne)<br>
                                            le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                            par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?><br>
                                            <?= $historyEntry->getContent() ?>
                                        </td>
                                    </tr>
                                    <?php break;
                                case ClientsStatus::STATUS_CLOSED_DEFINITELY: ?>
                                    <tr>
                                        <td>
                                            Compte définitvement fermé le <?= $historyEntry->getAdded()->format('d/m/Y H:i') ?><br>
                                            <?= $historyEntry->getContent() ?><br>
                                            par <?= $historyEntry->getIdUser()->getFirstname() ?> <?= $historyEntry->getIdUser()->getName() ?>
                                        </td>
                                    </tr>
                                    <?php break;
                            }
                        ?>
                        <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
                <!-- Lender tax exemption history -->
                <?php if (false === empty($this->taxExemptionUserHistoryAction)) : ?>
                    <table class="tablesorter histo_status_client">
                        <?php foreach ($this->taxExemptionUserHistoryAction as $actions) : ?>
                            <?php foreach ($actions['modifications'] as $action) : ?>
                                <tr>
                                    <td>Dispense de prélèvement fiscal <b>année <?= $action['year'] ?></b>.
                                        <?php if ('adding' === $action['action']) : ?>
                                            Ajoutée
                                        <?php elseif ('deletion' === $action['action']) : ?>
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
            <div class="droite lender-status-action">
                <?php $clientStatus = $this->wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId(); ?>
                <?php if ($clientStatus !== ClientsStatus::STATUS_CLOSED_DEFINITELY) : ?>
                    <?php if (isset($_SESSION['email_completude_confirm']) && $_SESSION['email_completude_confirm']) : ?>
                        <div>
                            <img src="<?= $this->surl ?>/images/admin/mail.png" alt="email">
                            Votre email a été envoyé
                        </div>
                        <div>
                            <a href="<?= $this->lurl ?>/preteurs/activation" class="btn_link btnBackListe">Revenir à la liste de contôle</a>
                        </div>
                        <?php unset($_SESSION['email_completude_confirm']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['compte_valide']) && $_SESSION['compte_valide']) : ?>
                        <div>
                            <a href="<?= $this->lurl ?>/preteurs/activation" class="btn_link btnBackListe">Revenir à la liste de contôle</a>
                        </div>
                        <?php unset($_SESSION['compte_valide']); ?>
                    <?php endif; ?>

                    <?php if (ClientsStatus::STATUS_VALIDATED !== $clientStatus && in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) : ?>
                        <div>
                            <input type="button" id="valider_preteur" class="btn-primary" value="Valider le prêteur">
                        </div>
                    <?php endif; ?>

                    <?php if (false === in_array($clientStatus, [ClientsStatus::STATUS_CLOSED_BY_UNILEND, ClientsStatus::STATUS_CLOSED_LENDER_REQUEST])) : ?>
                        <div>
                            <input type="button" id="completude_edit" class="btn-primary btnCompletude" value="Complétude">
                        </div>

                        <div class="message_completude">
                            <h2>Complétude - Personnalisation du message</h2>
                            <div class="liwording">
                                <table>
                                    <?php foreach ($this->completude_wording as $key => $message) : ?>
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
                            <div class="content_li_wording"></div>
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
                        </div>

                        <div style="padding-bottom: 25px;"></div>
                    <?php endif; ?>

                    <?php if (in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) : ?>
                        <div>
                            <input type="button"
                                onclick="if (confirm('Voulez-vous clôturer le compte à l’initiative d’Unilend ?')){window.location = '<?= $this->lurl ?>/preteurs/status/<?= $this->clients->id_client ?>/close_unilend';}"
                                class="btn-primary" style="background: #FF0000; border: 1px solid #FF0000;"
                                value="Clôturer le compte à l’initiative d’Unilend">
                        </div>
                        <div>
                            <input type="button"
                                onclick="if (confirm('Voulez-vous clôturer le compte à la demande du prêteur ?')){window.location = '<?= $this->lurl ?>/preteurs/status/<?= $this->clients->id_client ?>/close_lender';}"
                                class="btn-primary" style="background: #FF0000; border: 1px solid #FF0000;"
                                value="Clôturer le compte à la demande du prêteur">
                        </div>
                    <?php endif; ?>

                    <?php if (false === in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) : ?>
                        <div>
                            <input type="button"
                                onclick="if (confirm('Voulez-vous réactiver le compte à son précédent statut ?')){window.location = '<?= $this->lurl ?>/preteurs/status/<?= $this->clients->id_client ?>/online';}"
                                class="btn-primary"
                                value="Réactiver le compte à son précédent statut">
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="clear"></div>
            <br/><br/>
            <?php $this->fireView('../blocs/acceptedLegalDocumentList'); ?>
            <br/><br/><br/><br/>
            <input type="hidden" name="statut_valider_preteur" id="statut_valider_preteur" value="0"/>
            <input type="hidden" name="send_edit_preteur" id="send_edit_preteur"/>
            <button type="submit" class="btn-primary">Sauvegarder</button>
        </form>
    <?php endif; ?>
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
            if (data !== 'nok') {
                $("#completude_preview").get(0).click();
            }
        });
    });

    <?php if (null !== $this->companyEntity->getIdPostalAddress()) : ?>
        $('.meme-adresse').show();
    <?php endif; ?>

    <?php if ($this->companies->status_client == Companies::CLIENT_STATUS_MANAGER) : ?>
        $('.statut_dirigeant_e').hide('slow');
        $('.statut_dirigeant_e3').hide('slow');
    <?php elseif($this->companies->status_client == Companies::CLIENT_STATUS_DELEGATION_OF_POWER) : ?>
        $('.statut_dirigeant_e').show('slow');
        $('.statut_dirigeant_e3').hide('slow');
    <?php elseif($this->companies->status_client == Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) : ?>
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

    $("#origine_des_fonds").change(function() {
        if ($(this).val() == '1000000') {
            $("#row_precision").show();
        } else {
            $("#row_precision").hide();
        }
    });

    <?php if ($this->clients->funds_origin == 1000000) : ?>
        $("#row_precision").show();
    <?php endif; ?>
</script>
