<script type="text/javascript">
	$(document).ready(function(){
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
		$("#datepik_from").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		$("#datepik_to").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
	});
</script>
<div id="popup" style="height:1150px;">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="edit_promo" id="edit_promo" enctype="multipart/form-data" action="<?=$this->lurl?>/promotions/<?=$this->promotions->id_code?>" target="_parent">
        <h1>Modifier <?=$this->promotions->code?></h1>            
        <fieldset>
        	<table class="formColor" style="width:550px;">
            	<tr>
                	<th><label for="code">Code promo :</label></td>
                    <td colspan="3"><input type="text" name="code" id="code" value="<?=$this->promotions->code?>" class="input_large" /></td>
                </tr>
                <tr>
                	<th><label for="nb_utilisations">Nb utilisations :</label></td>
                    <td colspan="3"><input type="text" name="nb_utilisations" id="nb_utilisations" value="<?=$this->promotions->nb_utilisations?>" class="input_court" /></td>
                </tr>
                <tr>
                    <th><label>Statut du code :</label></th>
                    <td colspan="3">
                        <input type="radio" value="1" id="status1" name="status" <?=($this->promotions->status == 1?'checked="checked"':'')?> class="radio" />
                        <label for="status1" class="label_radio">En ligne</label>
                        <input type="radio" value="0" id="status0" name="status" <?=($this->promotions->status == 0?'checked="checked"':'')?> class="radio" />
                        <label for="status0" class="label_radio">Hors ligne</label>
                        <input type="radio" value="2" id="status2" name="status" <?=($this->promotions->status == 2?'checked="checked"':'')?> class="radio" />
                        <label for="status2" class="label_radio">Template</label>
                        <input type="radio" value="3" id="status3" name="status" <?=($this->promotions->status == 3?'checked="checked"':'')?> class="radio" />
                        <label for="status3" class="label_radio">Auto</label>	
                    </td>
                </tr>
                <tr>
                	<td colspan="4"><h3>Conditions d'application</h3></td>
                </tr>
                <tr>
                    <th><label for="from">From : </label></th>
                    <td><input type="text" name="from" id="datepik_from" value="<?=$this->dates->formatDate($this->promotions->from,'d/m/Y')?>" class="input_dp" /></td>
                    <th><label for="to">To : </label></th>
                    <td><input type="text" name="to" id="datepik_to" value="<?=$this->dates->formatDate($this->promotions->to,'d/m/Y')?>" class="input_dp" /></td>
                </tr>
                <tr>
                	<th><label for="seuil">Seuil :</label></td>
                    <td colspan="3"><input type="text" name="seuil" id="seuil" value="<?=$this->promotions->seuil?>" class="input_court" /></td>
                </tr>
                <tr>
                	<th><label for="id_groupe">Groupe de client :</label></th>
                    <td colspan="3">
						<select name="id_groupe" id="id_groupe" class="select">
                            <option value="0">Choisir un groupe</option>
                            <?php
                            foreach($this->lGroupes as $g)
                            {
                                echo '<option value="'.$g['id_groupe'].'"'.($this->promotions->id_groupe == $g['id_groupe']?' selected="selected"':'').'>'.$g['nom'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                	<td colspan="4"><h3>Restrictions</h3></td>
                </tr>
                <tr>
                	<th><label for="nb_minimum">Nb minimum de produits :</label></td>
                    <td colspan="3"><input type="text" name="nb_minimum" id="nb_minimum" value="<?=$this->promotions->nb_minimum?>" class="input_court" /></td>
                </tr>
                <tr>
                	<th><label for="id_tree">Catégories :</label></th>
                    <td colspan="3">
						<select name="id_tree[]" id="id_tree" class="selectm2" multiple="multiple">
                            <option value="">Choisir une / des catégories</option>
                            <?php
                            foreach($this->lTree as $tree)
                            {
                                echo '<option value="'.$tree['id_tree'].'"'.(in_array($tree['id_tree'],$this->lTreeOn)?' selected="selected"':'').'>'.$tree['title'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                	<th><label for="id_produit">Produits :</label></th>
                    <td colspan="3">
						<select name="id_produit[]" id="id_produit" class="selectm2" multiple="multiple">
                            <option value="">Choisir un / des produits</option>
                            <?php
                            foreach($this->lProduits as $p)
                            {
                                echo '<option value="'.$p['id_produit'].'"'.(in_array($p['id_produit'],$this->lProduitsOn)?' selected="selected"':'').'>'.$p['nom'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                	<td colspan="4"><h3>Restrictions (cumul)</h3></td>
                </tr>
                <tr>
                	<th><label for="nb_minimum2">Nb minimum de produits :</label></td>
                    <td colspan="3"><input type="text" name="nb_minimum2" id="nb_minimum2" value="<?=$this->promotions->nb_minimum2?>" class="input_court" /></td>
                </tr>
                <tr>
                	<th><label for="id_tree2">Catégories :</label></th>
                    <td colspan="3">
						<select name="id_tree2[]" id="id_tree2" class="selectm2" multiple="multiple">
                            <option value="">Choisir une / des catégories</option>
                            <?php
                            foreach($this->lTree as $tree)
                            {
                                echo '<option value="'.$tree['id_tree'].'"'.(in_array($tree['id_tree'],$this->lTree2On)?' selected="selected"':'').'>'.$tree['title'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                	<th><label for="id_produit2">Produits :</label></th>
                    <td colspan="3">
						<select name="id_produit2[]" id="id_produit2" class="selectm2" multiple="multiple">
                            <option value="">Choisir un / des produits</option>
                            <?php
                            foreach($this->lProduits as $p)
                            {
                                echo '<option value="'.$p['id_produit'].'"'.(in_array($p['id_produit'],$this->lProduits2On)?' selected="selected"':'').'>'.$p['nom'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                	<td colspan="4"><h3>Réductions et avantages</h3></td>
                </tr>
                <tr>
                	<th><label for="type">Type :</label></th>
                    <td colspan="3"><?=$this->bdd->listEnum('promotions','type','type',$this->promotions->type)?></td>
                </tr>
                <tr>
                	<th><label for="value">Valeur :</label></td>
                    <td colspan="3"><input type="text" name="value" id="value" value="<?=$this->promotions->value?>" class="input_court" /></td>
                </tr>
                <tr>
                    <th><label>Frais de port offert :</label></th>
                    <td colspan="3">
                        <input type="radio" value="1" id="fdp1" name="fdp" <?=($this->promotions->fdp == 1?'checked="checked"':'')?> class="radio" />
                        <label for="fdp1" class="label_radio">Oui</label>
                        <input type="radio" value="0" id="fdp0" name="fdp" <?=($this->promotions->fdp == 0?'checked="checked"':'')?> class="radio" />
                        <label for="fdp0" class="label_radio">Non</label>	
                    </td>
                </tr>
                <tr>
                	<th><label for="id_produit_kdo">Produit Cadeau :</label></th>
                    <td colspan="3">
						<select name="id_produit_kdo" id="id_produit_kdo" class="select">
                            <option value="0">Choisir un produit cadeau</option>
                            <?php
                            foreach($this->lProduits as $p)
                            {
                                echo '<option value="'.$p['id_produit'].'"'.($this->promotions->id_produit_kdo == $g['id_produit']?' selected="selected"':'').'>'.$p['nom'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                	<th><label for="id_promo">Génération d'un code promo :</label></th>
                    <td colspan="3">
						<select name="id_promo" id="id_promo" class="select">
                            <option value="0">Choisir un code promo témoin</option>
                            <?php
                            foreach($this->lCodes as $c)
                            {
                                echo '<option value="'.$c['id_code'].'"'.($this->promotions->id_promo == $c['id_code']?' selected="selected"':'').'>'.$c['code'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr> 
                <tr>
                    <th><label>Offre le plus cher :</label></th>
                    <td colspan="3">
                        <input type="radio" value="1" id="plus_cher1" name="plus_cher" <?=($this->promotions->plus_cher == 1?'checked="checked"':'')?> class="radio" />
                        <label for="plus_cher1" class="label_radio">Oui</label>
                        <input type="radio" value="0" id="plus_cher0" name="plus_cher" <?=($this->promotions->plus_cher == 0?'checked="checked"':'')?> class="radio" />
                        <label for="plus_cher0" class="label_radio">Non</label>	
                    </td>
                </tr>
                <tr>
                    <th><label>Offre le moins cher :</label></th>
                    <td colspan="3">
                        <input type="radio" value="1" id="moins_cher1" name="moins_cher" <?=($this->promotions->moins_cher == 1?'checked="checked"':'')?> class="radio" />
                        <label for="moins_cher1" class="label_radio">Oui</label>
                        <input type="radio" value="0" id="moins_cher0" name="moins_cher" <?=($this->promotions->moins_cher == 0?'checked="checked"':'')?> class="radio" />
                        <label for="moins_cher0" class="label_radio">Non</label>	
                    </td>
                </tr>
                <tr>
                    <th><label>Première commande :</label></th>
                    <td colspan="3">
                        <input type="radio" value="1" id="premiere_cmde1" name="premiere_cmde" <?=($this->promotions->premiere_cmde == 1?'checked="checked"':'')?> class="radio" />
                        <label for="premiere_cmde1" class="label_radio">Oui</label>
                        <input type="radio" value="0" id="premiere_cmde0" name="premiere_cmde" <?=($this->promotions->premiere_cmde == 0?'checked="checked"':'')?> class="radio" />
                        <label for="premiere_cmde0" class="label_radio">Non</label>	
                    </td>
                </tr>
                <tr>
                	<td colspan="4"><h3>Pour les templates</h3></td>
                </tr>
                <tr>
                	<th><label for="duree">Durée pour template (jours) :</label></td>
                    <td colspan="3"><input type="text" name="duree" id="duree" value="<?=$this->promotions->duree?>" class="input_court" /></td>
                </tr>
                <tr>
                    <td colspan="3">&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_edit_promo" id="form_edit_promo" />
                        <input type="submit" value="Valider" title="Valider" name="send_promo" id="send_promo" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>