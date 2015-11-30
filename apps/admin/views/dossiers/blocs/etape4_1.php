<div class="tab_title" id="title_etape4_1">Etape 4.1 - Notation externe</div>
<div class="tab_content" id="etape4_1">
    <form method="post" name="dossier_etape4_1" id="dossier_etape4_1" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <div id="contenu_etape4_1">
            <table class="form" style="width: 100%;">
                <tr>
                    <th><label for="ca_declara_client">Chiffe d'affaires declaré par client</label></th>
                    <td><input type="text" name="ca_declara_client" id="ca_declara_client" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->ca_declara_client, 0) ?>"/></td>
                    <th><label for="resultat_exploitation_declara_client">Résultat d'exploitation declaré par client</label></th>
                    <td><input type="text" name="resultat_exploitation_declara_client" id="resultat_exploitation_declara_client" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->resultat_exploitation_declara_client, 0) ?>"/></td>
                </tr>
                <tr>
                    <th><label for="fonds_propres_declara_client">Fonds propres declarés par client</label></th>
                    <td colspan="3"><input type="text" name="fonds_propres_declara_client" id="fonds_propres_declara_client" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->fonds_propres_declara_client, 0) ?>"/></td>
                </tr>
            </table>

            <br/><br/>

            <table class="form" style="width: 100%;">
                <tr>
                    <th><label for="encours_actuel_dette_fianciere">Encours actuel de la dette financière :</label></th>
                    <td><input type="text" name="encours_actuel_dette_fianciere" id="encours_actuel_dette_fianciere" class="input_moy" value="<?= ($this->companies_details->encours_actuel_dette_fianciere != false ? number_format($this->companies_details->encours_actuel_dette_fianciere, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="remb_a_venir_cette_annee">Remboursements à venir cette année  :</label></th>
                    <td><input type="text" name="remb_a_venir_cette_annee" id="remb_a_venir_cette_annee" class="input_moy" value="<?= ($this->companies_details->remb_a_venir_cette_annee != false ? number_format($this->companies_details->remb_a_venir_cette_annee, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="remb_a_venir_annee_prochaine">Remboursements à venir l'année prochaine :</label></th>
                    <td><input type="text" name="remb_a_venir_annee_prochaine" id="remb_a_venir_annee_prochaine" class="input_moy" value="<?= ($this->companies_details->remb_a_venir_annee_prochaine != false ? number_format($this->companies_details->remb_a_venir_annee_prochaine, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="tresorie_dispo_actuellement">Trésorerie disponible actuellement :</label></th>
                    <td><input type="text" name="tresorie_dispo_actuellement" id="tresorie_dispo_actuellement" class="input_moy" value="<?= ($this->companies_details->tresorie_dispo_actuellement != false ? number_format($this->companies_details->tresorie_dispo_actuellement, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="autre_demandes_financements_prevues">Autres demandes de financements prévues<br /> (autres que celles que vous réalisez auprès d'Unilend) :</label></th>
                    <td colspan="3"><input type="text" name="autre_demandes_financements_prevues" id="autre_demandes_financements_prevues" class="input_moy" value="<?= ($this->companies_details->autre_demandes_financements_prevues != false ? number_format($this->companies_details->autre_demandes_financements_prevues, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="precisions">Vous souhaitez apporter des précisions <br /> pour nous aider à mieux vous comprendre ? :</label></th>
                    <td colspan="3"><textarea style="width:350px;" name="precisions" id="precisions" class="textarea" /><?= $this->companies_details->precisions ?></textarea></td>
                </tr>
            </table>

            <table class="form" style="width: 100%;">
                <tr>
                    <th><label for="decouverts_bancaires">Découverts bancaires :</label></th>
                    <td><input type="text" name="decouverts_bancaires" id="decouverts_bancaires" class="input_moy" value="<?= ($this->companies_details->decouverts_bancaires != false ? number_format($this->companies_details->decouverts_bancaires, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="lignes_de_tresorerie">Lignes de trésorerie :</label></th>
                    <td><input type="text" name="lignes_de_tresorerie" id="lignes_de_tresorerie" class="input_moy" value="<?= ($this->companies_details->lignes_de_tresorerie != false ? number_format($this->companies_details->lignes_de_tresorerie, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="affacturage">Affacturage :</label></th>
                    <td><input type="text" name="affacturage" id="affacturage" class="input_moy" value="<?= ($this->companies_details->affacturage != false ? number_format($this->companies_details->affacturage, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="escompte">Escompte :</label></th>
                    <td><input type="text" name="escompte" id="escompte" class="input_moy" value="<?= ($this->companies_details->escompte != false ? number_format($this->companies_details->escompte, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="financement_dailly">Financement Dailly :</label></th>
                    <td><input type="text" name="financement_dailly" id="financement_dailly" class="input_moy" value="<?= ($this->companies_details->financement_dailly != false ? number_format($this->companies_details->financement_dailly, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="credit_de_tresorerie">Crédit de trésorerie :</label></th>
                    <td><input type="text" name="credit_de_tresorerie" id="credit_de_tresorerie" class="input_moy" value="<?= ($this->companies_details->credit_de_tresorerie != false ? number_format($this->companies_details->credit_de_tresorerie, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="credit_bancaire_investissements_materiels">Crédit bancaire<br/>investissements matériels :</label></th>
                    <td><input type="text" name="credit_bancaire_investissements_materiels" id="credit_bancaire_investissements_materiels" class="input_moy" value="<?= ($this->companies_details->credit_bancaire_investissements_materiels != false ? number_format($this->companies_details->credit_bancaire_investissements_materiels, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="credit_bancaire_investissements_immateriels">Crédit bancaire<br/>investissements immatériels :</label></th>
                    <td><input type="text" name="credit_bancaire_investissements_immateriels" id="credit_bancaire_investissements_immateriels" class="input_moy" value="<?= ($this->companies_details->credit_bancaire_investissements_immateriels != false ? number_format($this->companies_details->credit_bancaire_investissements_immateriels, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="rachat_entreprise_ou_titres">Rachat d'entreprise ou de titres :</label></th>
                    <td><input type="text" name="rachat_entreprise_ou_titres" id="rachat_entreprise_ou_titres" class="input_moy" value="<?= ($this->companies_details->rachat_entreprise_ou_titres != false ? number_format($this->companies_details->rachat_entreprise_ou_titres, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="credit_immobilier">Crédit immobilier :</label></th>
                    <td><input type="text" name="credit_immobilier" id="credit_immobilier" class="input_moy" value="<?= ($this->companies_details->credit_immobilier != false ? number_format($this->companies_details->credit_immobilier, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="credit_bail_immobilier">Crédit bail immobilier :</label></th>
                    <td><input type="text" name="credit_bail_immobilier" id="credit_bail_immobilier" class="input_moy" value="<?= ($this->companies_details->credit_bail_immobilier != false ? number_format($this->companies_details->credit_bail_immobilier, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="credit_bail">Crédit bail :</label></th>
                    <td><input type="text" name="credit_bail" id="credit_bail" class="input_moy" value="<?= ($this->companies_details->credit_bail != false ? number_format($this->companies_details->credit_bail, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="location_avec_option_achat">Location avec option d'achat :</label></th>
                    <td><input type="text" name="location_avec_option_achat" id="location_avec_option_achat" class="input_moy" value="<?= ($this->companies_details->location_avec_option_achat != false ? number_format($this->companies_details->location_avec_option_achat, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="location_financiere">Location financière :</label></th>
                    <td><input type="text" name="location_financiere" id="location_financiere" class="input_moy" value="<?= ($this->companies_details->location_financiere != false ? number_format($this->companies_details->location_financiere, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="location_longue_duree">Location longue durée :</label></th>
                    <td><input type="text" name="location_longue_duree" id="location_longue_duree" class="input_moy" value="<?= ($this->companies_details->location_longue_duree != false ? number_format($this->companies_details->location_longue_duree, 2, '.', '') : '') ?>"/> €</td>
                    <th><label for="pret_oseo">Prêt OSEO :</label></th>
                    <td><input type="text" name="pret_oseo" id="pret_oseo" class="input_moy" value="<?= ($this->companies_details->pret_oseo != false ? number_format($this->companies_details->pret_oseo, 2, '.', '') : '') ?>"/> €</td>
                </tr>
                <tr>
                    <th><label for="pret_participatif">Prêt participatif :</label></th>
                    <td colspan="3"><input type="text" name="pret_participatif" id="pret_participatif" class="input_moy" value="<?= ($this->companies_details->pret_participatif != false ? number_format($this->companies_details->pret_participatif, 2, '.', '') : '') ?>"/> €</td>
                </tr>
            </table>
        </div>

        <br/><br/>

        <div id="valid_etape4_1" class="valid_etape">Données sauvegardées</div>
        <div class="btnDroite">
            <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape4_1(<?= $this->projects->id_project ?>)">
        </div>
    </form>
</div>
