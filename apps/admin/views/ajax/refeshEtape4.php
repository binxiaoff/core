
<table class="form" style="width: 100%;">
    <tr>
        <th>Date du dernier bilan certifié :</th>
        <td>
        <select name="mois_etape4" id="mois_etape4" class="select">
            <?
            foreach($this->dates->tableauMois['fr'] as $k => $mois)
            {
                if($k > 0) echo '<option '.($this->companies_details->date_dernier_bilan_mois == $k?'selected':'').' value="'.$k.'">'.$mois.'</option>';
            }
            ?>
        </select>
        <select name="annee_etape4" id="annee_etape4" class="select">
            <?
            for($i=2008;$i<=2013;$i++)
            {
                ?><option <?=($this->companies_details->date_dernier_bilan_annee == $i?'selected':'')?> value="<?=$i?>"><?=$i?></option><?
            }
            ?>
        </select>
        </td>
    </tr>
</table>
<br /><br />

<!-- bilans -->
<?
if(count($this->lbilans) > 0)
{
?>
    <table class="tablesorter" style="text-align:center;">
        <thead>
            <th width="200"></th>
            <?
            foreach($this->lbilans as $b)
            {
                ?><th><?=$b['date']?></th><?
            }
            ?>
        </thead>
        <tbody>
            <tr>
                <td>Chiffe d'affaires</td>
                <?
                for($i=0;$i<5;$i++)
                {
                    ?><td>
                    <input name="ca_<?=$i?>" id="ca_<?=$i?>" type="text" class="input_moy" value="<?=($this->lbilans[$i]['ca']!=false?number_format($this->lbilans[$i]['ca'], 2, '.', ''):'');?>" />
                    <input type="hidden" id="ca_id_<?=$i?>" value="<?=$this->lbilans[$i]['id_bilan']?>" />
                    </td><?
                }
                ?>
            </tr>
            <tr>
                <td>Résultat brut d'exploitation</td>
                <?
                for($i=0;$i<5;$i++)
                {
                    ?><td>
                    <input name="resultat_brute_exploitation_<?=$i?>" id="resultat_brute_exploitation_<?=$i?>" type="text" class="input_moy" value="<?=($this->lbilans[$i]['resultat_brute_exploitation']!= false?number_format($this->lbilans[$i]['resultat_brute_exploitation'], 2, '.', ''):'');?>" />
                    <input type="hidden" id="resultat_brute_exploitation_id_<?=$i?>" value="<?=$this->lbilans[$i]['id_bilan']?>" />
                    </td><?
                }
                ?>
            </tr>
            <tr>
                <td>Résultat d'exploitation</td>
                <?
                for($i=0;$i<5;$i++)
                {
                    ?><td>
                    <input name="resultat_exploitation_<?=$i?>" id="resultat_exploitation_<?=$i?>" type="text" class="input_moy" value="<?=($this->lbilans[$i]['resultat_exploitation']!=false?number_format($this->lbilans[$i]['resultat_exploitation'], 2, '.', ''):'');?>" />
                    <input type="hidden" id="resultat_exploitation_id_<?=$i?>" value="<?=$this->lbilans[$i]['id_bilan']?>" />
                    </td><?
                }
                ?>
            </tr>
            <tr>
                <td>Investissements</td>
                <?
                for($i=0;$i<5;$i++)
                {
                    ?><td>
                    <input name="investissements_<?=$i?>" id="investissements_<?=$i?>" type="text" class="input_moy" value="<?=($this->lbilans[$i]['investissements']!=false?number_format($this->lbilans[$i]['investissements'], 2, '.', ''):'');?>" />
                    <input type="hidden" id="investissements_id_<?=$i?>" value="<?=$this->lbilans[$i]['id_bilan']?>" />
                    </td><?
                }
                ?>
            </tr>
        </tbody>
    </table>
    <?
    if($this->nb_lignes != '')
    {
    ?>
        <table>
            <tr>
                <td id="pager">
                    <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                    <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                    <input type="text" class="pagedisplay" />
                    <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                    <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                    <select class="pagesize">
                        <option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                    </select>
                </td>
            </tr>
        </table>
    <?
    }
    ?>
<?
}
?>
<br /><br />

<table class="form" style="width: 100%;">
    <tr>
        <th><label for="encours_actuel_dette_fianciere">Encours actuel de la dette financière :</label></th>
        <td><input type="text" name="encours_actuel_dette_fianciere" id="encours_actuel_dette_fianciere" class="input_moy" value="<?=($this->companies_details->encours_actuel_dette_fianciere!=false?number_format($this->companies_details->encours_actuel_dette_fianciere, 2, '.', ''):'')?>"/> €</td>
        <th><label for="remb_a_venir_cette_annee">Remboursements à venir cette année  :</label></th>
        <td><input type="text" name="remb_a_venir_cette_annee" id="remb_a_venir_cette_annee" class="input_moy" value="<?=($this->companies_details->remb_a_venir_cette_annee!=false?number_format($this->companies_details->remb_a_venir_cette_annee, 2, '.', ''):'')?>"/> €</td>
    </tr>
    <tr>
        <th><label for="remb_a_venir_annee_prochaine">Remboursements à venir l'année prochaine :</label></th>
        <td><input type="text" name="remb_a_venir_annee_prochaine" id="remb_a_venir_annee_prochaine" class="input_moy" value="<?=($this->companies_details->remb_a_venir_annee_prochaine!=false?number_format($this->companies_details->remb_a_venir_annee_prochaine, 2, '.', ''):'')?>"/> €</td>
        <th><label for="tresorie_dispo_actuellement">Trésorerie disponible actuellement :</label></th>
        <td><input type="text" name="tresorie_dispo_actuellement" id="tresorie_dispo_actuellement" class="input_moy" value="<?=($this->companies_details->tresorie_dispo_actuellement!=false?number_format($this->companies_details->tresorie_dispo_actuellement, 2, '.', ''):'')?>"/> €</td>
    </tr>
    <tr>
        <th><label for="autre_demandes_financements_prevues">Autres demandes de financements prévues<br /> (autres que celles que vous réalisez auprès d'Unilend) :</label></th>
        <td><input type="text" name="autre_demandes_financements_prevues" id="autre_demandes_financements_prevues" class="input_moy" value="<?=($this->companies_details->autre_demandes_financements_prevues!=false?number_format($this->companies_details->autre_demandes_financements_prevues, 2, '.', ''):'')?>"/> €</td>
        <th></th>
        <td></td>
    </tr>
    <tr>
        <th><label for="precisions">Vous souhaitez apporter des précisions <br /> pour nous aider à mieux vous comprendre ? :</label></th>
        <td colspan="3">
        <textarea style="width:350px;" name="precisions" id="precisions" class="textarea"><?=$this->companies_details->precisions?></textarea>
        </td>
	</tr>
</table>
<!-- actif / passif-->
<style>
.actif_passif .input_moy{width: 128px;}
</style>
<h2>Actif :</h2>
<?
if(count($this->lCompanies_actif_passif) > 0)
{
?>
    <table class="tablesorter actif_passif" style="text-align:center;">
        <thead>
            <th width="20">Ordre</th>
            <th>Immobilisations corporelles</th>
            <th>Immobilisations incorporelles</th>
            <th>Immobilisations financières</th>
            <th>Stocks</th>
            <th>Créances clients</th>
            <th>Disponibilités</th>
            <th>Valeurs mobilières de placement</th>
            <th>Total</th>
        </thead>
        <tbody>
            <?
            $total1 = 0;
            $total2 = 0;
            $total3 = 0;
            $total4 = 0;
            $total5 = 0;
            $total6 = 0;
            $total7 = 0;
            
            foreach($this->lCompanies_actif_passif as $ap)
            {
                $totalAnnee = ($ap['immobilisations_corporelles']+$ap['immobilisations_incorporelles']+$ap['immobilisations_financieres']+$ap['stocks']+$ap['creances_clients']+$ap['disponibilites']+$ap['valeurs_mobilieres_de_placement'])
            ?>
            <tr>
                <td><?=$ap['ordre']?></td>
                <td><input name="immobilisations_corporelles_<?=$ap['ordre']?>" id="immobilisations_corporelles_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['immobilisations_corporelles']!=false?number_format($ap['immobilisations_corporelles'], 2, '.', ''):'');?>" onkeyup="cal_actif();"/></td>
                
                <td><input name="immobilisations_incorporelles_<?=$ap['ordre']?>" id="immobilisations_incorporelles_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['immobilisations_incorporelles']!=false?number_format($ap['immobilisations_incorporelles'], 2, '.', ''):'');?>" onkeyup="cal_actif();"/></td>
                
                <td><input name="immobilisations_financieres_<?=$ap['ordre']?>" id="immobilisations_financieres_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['immobilisations_financieres']!=false?number_format($ap['immobilisations_financieres'], 2, '.', ''):'');?>" onkeyup="cal_actif();"/></td>
                
                <td><input name="stocks_<?=$ap['ordre']?>" id="stocks_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['stocks']!=false?number_format($ap['stocks'], 0, '.', ''):'');?>" onkeyup="cal_actif();"/></td>
                
                <td><input name="creances_clients_<?=$ap['ordre']?>" id="creances_clients_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['creances_clients']!=false?number_format($ap['creances_clients'], 2, '.', ''):'');?>" onkeyup="cal_actif();"/></td>
                
                <td><input name="disponibilites_<?=$ap['ordre']?>" id="disponibilites_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['disponibilites']!=false?number_format($ap['disponibilites'], 2, '.', ''):'');?>" onkeyup="cal_actif();"/></td>
                
                <td><input name="valeurs_mobilieres_de_placement_<?=$ap['ordre']?>" id="valeurs_mobilieres_de_placement_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['valeurs_mobilieres_de_placement']!=false?number_format($ap['valeurs_mobilieres_de_placement'], 2, '.', ''):'');?>" onkeyup="cal_actif();"/></td>
                <td id="totalAnneeAct_<?=$ap['ordre']?>"><?=$totalAnnee?></td>
                
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
            ?>
            <tr id="total_actif">
                <td>Total</td>
                <td><?=$total1?></td>
                <td><?=$total2?></td>
                <td><?=$total3?></td>
                <td><?=$total4?></td>
                <td><?=$total5?></td>
                <td><?=$total6?></td>
                <td><?=$total7?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <?
    if($this->nb_lignes != '')
    {
    ?>
        <table>
            <tr>
                <td id="pager">
                    <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                    <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                    <input type="text" class="pagedisplay" />
                    <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                    <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                    <select class="pagesize">
                        <option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                    </select>
                </td>
            </tr>
        </table>
    <?
    }
    ?>
<?
}
?>
<br /><br />
<h2>Passif :</h2>

<?
if(count($this->lCompanies_actif_passif) > 0)
{
?>
    <table class="tablesorter" style="text-align:center;">
        <thead>
            <th width="20">Ordre</th>
            <th>Capitaux propres</th>
            <th>Provisions pour risques & charges</th>
            <th>Dettes financières</th>
            <th>Dettes fournisseurs</th>
            <th>Autres dettes</th>
            <th>Total</th>
        </thead>
        <tbody>
            <?
            $total1 = 0;
            $total2 = 0;
            $total3 = 0;
            $total4 = 0;
            $total5 = 0;
            foreach($this->lCompanies_actif_passif as $ap)
            {
                
                $totalAnnee = ($ap['capitaux_propres']+$ap['provisions_pour_risques_et_charges']+$ap['dettes_financieres']+$ap['dettes_fournisseurs']+$ap['autres_dettes']);
            ?>
            <tr>
                <td><?=$ap['ordre']?></td>
                <td><input name="capitaux_propres_<?=$ap['ordre']?>" id="capitaux_propres_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['capitaux_propres']!=false?number_format($ap['capitaux_propres'], 2, '.', ''):'');?>"  onkeyup="cal_passif();"/></td>
                
                <td><input name="provisions_pour_risques_et_charges_<?=$ap['ordre']?>" id="provisions_pour_risques_et_charges_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['provisions_pour_risques_et_charges']!=false?number_format($ap['provisions_pour_risques_et_charges'], 2, '.', ''):'');?>" onkeyup="cal_passif();"/></td>
                
                <td><input name="dettes_financieres_<?=$ap['ordre']?>" id="dettes_financieres_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['dettes_financieres']!=false?number_format($ap['dettes_financieres'], 2, '.', ''):'');?>" onkeyup="cal_passif();"/></td>
                
                <td><input name="dettes_fournisseurs_<?=$ap['ordre']?>" id="dettes_fournisseurs_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['dettes_fournisseurs']!=false?number_format($ap['dettes_fournisseurs'], 2, '.', ''):'');?>" onkeyup="cal_passif();"/></td>
                
                <td><input name="autres_dettes_<?=$ap['ordre']?>" id="autres_dettes_<?=$ap['ordre']?>" type="text" class="input_moy" value="<?=($ap['autres_dettes']!=false?number_format($ap['autres_dettes'], 2, '.', ''):'');?>" onkeyup="cal_passif();"/></td>
                <td id="totalAnneePass_<?=$ap['ordre']?>"><?=$totalAnnee?></td>
            </tr>
            <?
            
            
            $total1 += $ap['capitaux_propres'];
            $total2 += $ap['provisions_pour_risques_et_charges'];
            $total3 += $ap['dettes_financieres'];
            $total4 += $ap['dettes_fournisseurs'];
            $total5 += $ap['autres_dettes'];
            }
            ?>
            <tr id="total_passif">
                <td>Total</td>
                <td><?=$total1?></td>
                <td><?=$total2?></td>
                <td><?=$total3?></td>
                <td><?=$total4?></td>
                <td><?=$total5?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <?
    if($this->nb_lignes != '')
    {
    ?>
        <table>
            <tr>
                <td id="pager">
                    <img src="<?=$this->surl?>/images/admin/first.png" alt="Première" class="first"/>
                    <img src="<?=$this->surl?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                    <input type="text" class="pagedisplay" />
                    <img src="<?=$this->surl?>/images/admin/next.png" alt="Suivante" class="next"/>
                    <img src="<?=$this->surl?>/images/admin/last.png" alt="Dernière" class="last"/>
                    <select class="pagesize">
                        <option value="<?=$this->nb_lignes?>" selected="selected"><?=$this->nb_lignes?></option>
                    </select>
                </td>
            </tr>
        </table>
    <?
    }
    ?>
<?
}
?>
<br /><br />
<table class="form" style="width: 100%;">
    <tr>
        <th><label for="decouverts_bancaires">Découverts bancaires :</label></th>
        <td><input type="text" name="decouverts_bancaires" id="decouverts_bancaires" class="input_moy" value="<?=($this->companies_details->decouverts_bancaires!=false?number_format($this->companies_details->decouverts_bancaires, 2, '.', ''):'')?>"/> €</td>
        <th><label for="lignes_de_tresorerie">Lignes de trésorerie :</label></th>
        <td><input type="text" name="lignes_de_tresorerie" id="lignes_de_tresorerie" class="input_moy" value="<?=($this->companies_details->lignes_de_tresorerie!=false?number_format($this->companies_details->lignes_de_tresorerie, 2, '.', ''):'')?>"/> €</td>
    </tr>
    <tr>
        <th><label for="affacturage">Affacturage :</label></th>
        <td><input type="text" name="affacturage" id="affacturage" class="input_moy" value="<?=($this->companies_details->affacturage!=false?number_format($this->companies_details->affacturage, 2, '.', ''):'')?>"/> €</td>
        <th><label for="escompte">Escompte :</label></th>
        <td><input type="text" name="escompte" id="escompte" class="input_moy" value="<?=($this->companies_details->escompte!=false?number_format($this->companies_details->escompte, 2, '.', ''):'')?>"/> €</td>
    </tr>
    <tr>
        <th><label for="financement_dailly">Financement Dailly :</label></th>
        <td><input type="text" name="financement_dailly" id="financement_dailly" class="input_moy" value="<?=($this->companies_details->financement_dailly!=false?number_format($this->companies_details->financement_dailly, 2, '.', ''):'')?>"/> €</td>
        <th><label for="credit_de_tresorerie">Crédit de trésorerie :</label></th>
        <td><input type="text" name="credit_de_tresorerie" id="credit_de_tresorerie" class="input_moy" value="<?=($this->companies_details->credit_de_tresorerie!=false?number_format($this->companies_details->credit_de_tresorerie, 2, '.', ''):'')?>"/> €</td>
    </tr>
    
    <tr>
        <th><label for="credit_bancaire_investissements_materiels">Crédit bancaire<br />investissements matériels :</label></th>
        <td><input type="text" name="credit_bancaire_investissements_materiels" id="credit_bancaire_investissements_materiels" class="input_moy" value="<?=($this->companies_details->credit_bancaire_investissements_materiels!=false?number_format($this->companies_details->credit_bancaire_investissements_materiels, 2, '.', ''):'')?>"/> €</td>
        <th><label for="credit_bancaire_investissements_immateriels">Crédit bancaire<br />investissements immatériels :</label></th>
        <td><input type="text" name="credit_bancaire_investissements_immateriels" id="credit_bancaire_investissements_immateriels" class="input_moy" value="<?=($this->companies_details->credit_bancaire_investissements_immateriels!=false?number_format($this->companies_details->credit_bancaire_investissements_immateriels, 2, '.', ''):'')?>"/> €</td>
    </tr>
    
    <tr>
        <th><label for="rachat_entreprise_ou_titres">Rachat d'entreprise ou de titres :</label></th>
        <td><input type="text" name="rachat_entreprise_ou_titres" id="rachat_entreprise_ou_titres" class="input_moy" value="<?=($this->companies_details->rachat_entreprise_ou_titres!=false?number_format($this->companies_details->rachat_entreprise_ou_titres, 2, '.', ''):'')?>"/> €</td>
        <th><label for="credit_immobilier">Crédit immobilier :</label></th>
        <td><input type="text" name="credit_immobilier" id="credit_immobilier" class="input_moy" value="<?=($this->companies_details->credit_immobilier!=false?number_format($this->companies_details->credit_immobilier, 2, '.', ''):'')?>"/> €</td>
    </tr>
    
    <tr>
        <th><label for="credit_bail_immobilier">Crédit bail immobilier :</label></th>
        <td><input type="text" name="credit_bail_immobilier" id="credit_bail_immobilier" class="input_moy" value="<?=($this->companies_details->credit_bail_immobilier!=false?number_format($this->companies_details->credit_bail_immobilier, 2, '.', ''):'')?>"/> €</td>
        <th><label for="credit_bail">Crédit bail :</label></th>
        <td><input type="text" name="credit_bail" id="credit_bail" class="input_moy" value="<?=($this->companies_details->credit_bail!=false?number_format($this->companies_details->credit_bail, 2, '.', ''):'')?>"/> €</td>
    </tr>
    
    <tr>
        <th><label for="location_avec_option_achat">Location avec option d'achat :</label></th>
        <td><input type="text" name="location_avec_option_achat" id="location_avec_option_achat" class="input_moy" value="<?=($this->companies_details->location_avec_option_achat!=false?number_format($this->companies_details->location_avec_option_achat, 2, '.', ''):'')?>"/> €</td>
        <th><label for="location_financiere">Location financière :</label></th>
        <td><input type="text" name="location_financiere" id="location_financiere" class="input_moy" value="<?=($this->companies_details->location_financiere!=false?number_format($this->companies_details->location_financiere, 2, '.', ''):'')?>"/> €</td>
    </tr>
    
    <tr>
        <th><label for="location_longue_duree">Location longue durée :</label></th>
        <td><input type="text" name="location_longue_duree" id="location_longue_duree" class="input_moy" value="<?=($this->companies_details->location_longue_duree!=false?number_format($this->companies_details->location_longue_duree, 2, '.', ''):'')?>"/> €</td>
        <th><label for="pret_oseo">Prêt OSEO :</label></th>
        <td><input type="text" name="pret_oseo" id="pret_oseo" class="input_moy" value="<?=($this->companies_details->pret_oseo!=false?number_format($this->companies_details->pret_oseo, 2, '.', ''):'')?>"/> €</td>
    </tr>
    
    <tr>
        <th><label for="pret_participatif">Prêt participatif :</label></th>
        <td><input type="text" name="pret_participatif" id="pret_participatif" class="input_moy" value="<?=($this->companies_details->pret_participatif!=false?number_format($this->companies_details->pret_participatif, 2, '.', ''):'')?>"/> €</td>
    </tr>
    
</table>
