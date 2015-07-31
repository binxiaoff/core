<script type="text/javascript">	
	$(document).ready(function() {
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
		<?
		for($i=1; $i<=10; $i++)
		{
		?>
			$("#debut_promo<?=$i?>").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=date('Y')?>:<?=(date('Y')+10)?>'});
			$("#fin_promo<?=$i?>").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=date('Y')?>:<?=(date('Y')+10)?>'});
		<?
		}
		?>
		<?
		foreach($this->lLangues as $key => $lng)
		{
		?>
			$("#datepik_<?=$key?>").datepicker({showOn: 'both', buttonImage: '<?=$this->surl?>/images/admin/calendar.gif', buttonImageOnly: true,changeMonth: true,changeYear: true,yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'});
		<?
		}
		?>
  	});
	<?
	if(isset($_SESSION['freeow']))
	{
	?>
		$(document).ready(function(){
			var title, message, opts, container;
			title = "<?=$_SESSION['freeow']['title']?>";
			message = "<?=$_SESSION['freeow']['message']?>";
			opts = {};
			opts.classes = ['smokey'];
			$('#freeow-tr').freeow(title, message, opts);
		});
	<?
	}
	?>
</script>
<script type="text/javascript" src="<?=$this->url?>/ckeditor/ckeditor.js"></script>
<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/produits" title="Boutique">Boutique</a> -</li>
        <li><a href="<?=$this->lurl?>/produits" title="Produits">Produits</a> -</li>
        <li>Modifier le produit <?=$this->prod['nom']?></li>
    </ul>
    <form method="post" name="mod_produit" id="mod_produit" enctype="multipart/form-data"> 
    	<input type="hidden" name="lng_encours" id="lng_encours" value="<?=$this->language?>" />  
        <input type="hidden" name="id_produit_temp" id="id_produit_temp" value="<?=$this->produits->id_produit?>" /> 
        <fieldset>    
        	<div class="gauche">
            	<h1>Informations produit</h1>
                <table class="form">
                    <tr>
                        <th><label for="type">Type :</label></th>
                        <td colspan="2">
                        	<input type="text" name="type" id="type" class="input_large" value="<?=$this->produits->type?>" readonly />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="id_template">Template produit :</label></th>
                        <td colspan="2">
                            <select name="id_template" id="id_template" onchange="if(confirm('Voulez-vous vraiment changer ce template ?\nLe contenu existant de ce produit sera d\351finitivement \351ffac\351.')){ window.location.href = '<?=$this->lurl?>/produits/edit/<?=$this->produits->id_produit?>/<?=$this->params[1]?>/' + this.value; }" class="select">
                                <option value="">Choisir un template</option>
                                <?
                                foreach($this->lTemplates as $t)
                                {
                                    echo '<option value="'.$t['id_template'].'"'.($this->produits->id_template == $t['id_template']?' selected="selected"':'').'>'.$t['name'].'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="id_cat">Catégorie principale :</label></th>
                        <td colspan="2">
                            <select name="id_cat" id="id_cat" class="select">
                                <option value="0">Choisir une catégorie</option>
								<?
                                foreach($this->lCategories as $c)
                                {
                                    echo '<option value="'.$c['id_tree'].'"'.($this->produits_tree->id_tree == $c['id_tree']?' selected="selected"':'').'>'.$c['title'].'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ordre_produit">Ordre dans la catégorie :</label></th>
                        <td colspan="2"><input type="text" name="ordre_produit" id="ordre_produit" class="input_court" value="<?=$this->produits_tree->ordre_produit?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="id_tree">Catégories secondaires :</label></th>
                        <td colspan="2">
                            <select name="id_tree[]" id="id_tree" class="selectm" multiple="multiple">
                                <option value="0">Choisir une/des catégorie(s)</option>
                                <?
                                foreach($this->lTree as $p)
                                {
                                    echo '<option value="'.$p['id_tree'].'"'.(in_array($p['id_tree'],$this->lIdTree)?' selected="selected"':'').'>'.$p['title'].'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="id_brand">Marque :</label></th>
                        <td colspan="2">
                            <select name="id_brand" id="id_brand" class="select">
                                <option value="0">Choisir une marque</option>
                                <?
                                foreach($this->lBrands as $b)
                                {
                                    echo '<option value="'.$b['id_brand'].'"'.($this->produits->id_brand == $b['id_brand']?' selected="selected"':'').'>'.$b['name'].'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tva">TVA (%) :</label></th>
                        <td colspan="2"><input type="text" name="tva" id="tva" value="<?=$this->produits->tva?>" class="input_court" /></td>
                    </tr>
                    <tr>
                        <th><label>Statut du produit :</label></th>
                        <td width="110px">
                            <input type="radio" value="1" id="status1" class="radio" name="status"<?=($this->produits->status == 1?' checked="checked"':'')?> />
                            <label for="status1" class="label_radio">En ligne</label>
                        </td>
                        <td width="150px">
                            <input type="radio" value="0" id="status0" class="radio" name="status"<?=($this->produits->status == 0?' checked="checked"':'')?> />
                            <label for="status0" class="label_radio">Hors ligne</label>	
                        </td>
                    </tr>
                </table>
                <br /><br />
                <?
				foreach($this->lDetails as $d)
				{
				?>
                	<div id="contenuDetails<?=$d['ordre']?>">
                        <h1>Détail n° <?=$d['ordre']?> du produit<? if($d['ordre'] > 1) { ?>&nbsp;<a onClick="document.getElementById('contenuDetails<?=$d['ordre']?>').style.display = 'none'; document.getElementById('todelete<?=$d['ordre']?>').value = '1';" title="Supprimer Détail n° <?=$d['ordre']?> du produit"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer Détail n° <?=$d['ordre']?> du produit" /></a><? } ?></h1>
                        <input type="hidden" name="todelete<?=$d['ordre']?>" id="todelete<?=$d['ordre']?>" value="0">
                        <input type="hidden" name="id_detail<?=$d['ordre']?>" id="id_detail<?=$d['ordre']?>" value="<?=$d['id_detail']?>">
                        <table class="form">
                            <tr>
                                <th><label for="reference<?=$d['ordre']?>">Référence :</label></th>
                                <td colspan="2"><input type="text" name="reference<?=$d['ordre']?>" value="<?=$d['reference']?>" id="reference<?=$d['ordre']?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="poids<?=$d['ordre']?>">Poids (g) :</label></th>
                                <td colspan="2"><input type="text" name="poids<?=$d['ordre']?>" value="<?=$d['poids']?>" id="poids<?=$d['ordre']?>" class="input_court" /></td>
                            </tr>
                            <tr>
                                <th><label for="prix<?=$d['ordre']?>">Prix (€ TTC) :</label></th>
                                <td colspan="2"><input type="text" name="prix<?=$d['ordre']?>" value="<?=$d['prix']?>" id="prix<?=$d['ordre']?>" class="input_court" /></td>
                            </tr>
                            <tr>
                                <th><label>Type de promo :</label></th>
                                <td width="110px">
                                    <input type="radio" value="1" id="1promo<?=$d['ordre']?>" class="radio" name="promo<?=$d['ordre']?>"<?=($d['promo'] == 1?' checked="checked"':'')?> />
                                    <label for="1promo<?=$d['ordre']?>" class="label_radio">Pourcentage</label>
                                </td>
                                <td width="150px">
                                    <input type="radio" value="0" id="0promo<?=$d['ordre']?>" class="radio" name="promo<?=$d['ordre']?>"<?=($d['promo'] == 0?' checked="checked"':'')?> />
                                    <label for="0promo<?=$d['ordre']?>" class="label_radio">Remise</label>	
                                </td>
                            </tr>
                            <tr>
                                <th><label for="montant_promo<?=$d['ordre']?>">Montant promo (€/%) :</label></th>
                                <td colspan="2"><input type="text" name="montant_promo<?=$d['ordre']?>" value="<?=$d['montant_promo']?>" id="montant_promo<?=$d['ordre']?>" class="input_court" /></td>
                            </tr>                        
                            <tr>
                                <th><label for="debut_promo<?=$d['ordre']?>">Début promo :</label></th>
                                <td colspan="2"><input type="text" name="debut_promo<?=$d['ordre']?>" value="<?=$this->dates->formatDate($d['debut_promo'],'d/m/Y')?>" id="debut_promo<?=$d['ordre']?>" class="input_dp" /></td>
                            </tr>
                            <tr>
                                <th><label for="fin_promo<?=$d['ordre']?>">Fin promo :</label></th>
                                <td colspan="2"><input type="text" name="fin_promo<?=$d['ordre']?>" value="<?=$this->dates->formatDate($d['fin_promo'],'d/m/Y')?>" id="fin_promo<?=$d['ordre']?>" class="input_dp" /></td>
                            </tr>
                            <tr>
                                <th><label for="type_detail<?=$d['ordre']?>">Type détail :</label></th>
                                <td colspan="2"><?=$this->bdd->listEnum('produits_details','type_detail','type_detail'.$d['ordre'],$d['type_detail'])?></td>
                            </tr>
                            <tr>
                                <th><label for="detail<?=$d['ordre']?>">Détail :</label></th>
                                <td colspan="2"><input type="text" name="detail<?=$d['ordre']?>" value="<?=$d['detail']?>" id="detail<?=$d['ordre']?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="stock<?=$d['ordre']?>">Stock :</label></th>
                                <td colspan="2"><input type="text" name="stock<?=$d['ordre']?>" value="<?=$d['stock']?>" id="stock<?=$d['ordre']?>" class="input_court" /></td>
                            </tr>
                            <tr>
                                <th><label>Statut du détail :</label></th>
                                <td width="110px">
                                    <input type="radio" value="1" id="1status_details<?=$d['ordre']?>" class="radio" name="status_details<?=$d['ordre']?>"<?=($d['status'] == 1?' checked="checked"':'')?> />
                                    <label for="1status_details<?=$d['ordre']?>" class="label_radio">En ligne</label>
                                </td>
                                <td width="150px">
                                    <input type="radio" value="0" id="0status_details<?=$d['ordre']?>" class="radio" name="status_details<?=$d['ordre']?>"<?=($d['status'] == 0?' checked="checked"':'')?> />
                                    <label for="0status_details<?=$d['ordre']?>" class="label_radio">Hors ligne</label>	
                                </td>
                            </tr>
                        </table>
                        <br /><br />
                  	</div>
            	<?
				}
				?>
                <?
				for($i=(count($this->lDetails) + 1); $i<=10; $i++)
				{
				?>
                	<div id="contenuDetails<?=$i?>" style="display: none;">
                        <h1>Détail n° <?=$i?> du produit<? if($i > 1) { ?>&nbsp;<a onClick="document.getElementById('contenuDetails<?=$i?>').style.display = 'none'; document.getElementById('todelete<?=$i?>').value = '1';" title="Supprimer Détail n° <?=$i?> du produit"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Supprimer Détail n° <?=$i?> du produit" /></a><? } ?></h1>
                        <input type="hidden" name="todelete<?=$i?>" id="todelete<?=$i?>" value="0">
                        <table class="form">
                            <tr>
                                <th><label for="reference<?=$i?>">Référence :</label></th>
                                <td colspan="2"><input type="text" name="reference<?=$i?>" id="reference<?=$i?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="poids<?=$i?>">Poids (g) :</label></th>
                                <td colspan="2"><input type="text" name="poids<?=$i?>" id="poids<?=$i?>" class="input_court" /></td>
                            </tr>
                            <tr>
                                <th><label for="prix<?=$i?>">Prix (€ TTC) :</label></th>
                                <td colspan="2"><input type="text" name="prix<?=$i?>" id="prix<?=$i?>" class="input_court" /></td>
                            </tr>
                            <tr>
                                <th><label>Type de promo :</label></th>
                                <td width="110px">
                                    <input type="radio" value="1" id="1promo<?=$i?>" class="radio" name="promo<?=$i?>" />
                                    <label for="1promo<?=$i?>" class="label_radio">Pourcentage</label>
                                </td>
                                <td width="150px">
                                    <input type="radio" value="0" id="0promo<?=$i?>" class="radio" name="promo<?=$i?>" checked="checked" />
                                    <label for="0promo<?=$i?>" class="label_radio">Remise</label>	
                                </td>
                            </tr>
                            <tr>
                                <th><label for="montant_promo<?=$i?>">Montant promo (€/%) :</label></th>
                                <td colspan="2"><input type="text" name="montant_promo<?=$i?>" id="montant_promo<?=$i?>" class="input_court" /></td>
                            </tr>                        
                            <tr>
                                <th><label for="debut_promo<?=$i?>">Début promo :</label></th>
                                <td colspan="2"><input type="text" name="debut_promo<?=$i?>" id="debut_promo<?=$i?>" class="input_dp" /></td>
                            </tr>
                            <tr>
                                <th><label for="fin_promo<?=$i?>">Fin promo :</label></th>
                                <td colspan="2"><input type="text" name="fin_promo<?=$i?>" id="fin_promo<?=$i?>" class="input_dp" /></td>
                            </tr>
                            <tr>
                                <th><label for="type_detail<?=$i?>">Type détail :</label></th>
                                <td colspan="2"><?=$this->bdd->listEnum('produits_details','type_detail','type_detail'.$i)?></td>
                            </tr>
                            <tr>
                                <th><label for="detail<?=$i?>">Détail :</label></th>
                                <td colspan="2"><input type="text" name="detail<?=$i?>" id="detail<?=$i?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="stock<?=$i?>">Stock :</label></th>
                                <td colspan="2"><input type="text" name="stock<?=$i?>" id="stock<?=$i?>" class="input_court" /></td>
                            </tr>
                            <tr>
                                <th><label>Statut du détail :</label></th>
                                <td width="110px">
                                    <input type="radio" value="1" id="1status_details<?=$i?>" class="radio" name="status_details<?=$i?>" checked="checked" />
                                    <label for="1status_details<?=$i?>" class="label_radio">En ligne</label>
                                </td>
                                <td width="150px">
                                    <input type="radio" value="0" id="0status_details<?=$i?>" class="radio" name="status_details<?=$i?>" />
                                    <label for="0status_details<?=$i?>" class="label_radio">Hors ligne</label>	
                                </td>
                            </tr>
                        </table>
                        <br /><br />
                  	</div>
            	<?
				}
				?>
                <table class="form">
                	 <tr>
                        <th>
                            <input type="hidden" name="nbdetails" id="nbdetails" value="<?=count($this->lDetails)?>" />
                            <?
							if(count($this->lDetails) < 10)
							{
							?>
                            	<a onclick="ajouterDetails('nbdetails');" id="lienAjoutDetails" class="btn_link" title="Ajouter un détail">Ajouter un détail</a>
                           	<?
							}
							?>
                        </th>
                    </tr>
                </table>
          	</div>
            <div class="droite">
            	<div id="onglets_produits">
					<a onclick="changeOngletProduit('images_produit','comp_produit');" id="lien_images_produit" title="Images du produit" class="active">Images du produit</a>
					<a onclick="changeOngletProduit('comp_produit','images_produit');" id="lien_comp_produit" title="Produits complémentaires">Produits complémentaires</a>    
                </div>
                <div class="bloc_onglet" id="comp_produit" style="display:none;">
                	<h1>Choisir vos produits complémentaires</h1>
                    <table class="form">
                    	<tr>
                        	<th><label for="id_crosseling">Votre choix :</label></th>
                            <td>
                                <select name="id_crosseling" id="id_crosseling" class="select">
                                    <option value="0">Selectionner votre produit</option>
                                    <?
                                    foreach($this->lProduits as $p)
                                    {
                                    ?>
                                        <option value="<?=$p['id_produit']?>"><?=$p['nom_produit']?></option>
                                    <?
                                    }
                                    ?>
                                </select> 
                                &nbsp;&nbsp;
                                <a class="btn_link" onclick="if(document.getElementById('id_crosseling').value > 0){ ajoutProduitComp('<?=$this->produits->id_produit?>',document.getElementById('id_crosseling').value); }">Ajouter</a>
                            </td>
                        </tr>
                  	</table>
                    <div id="bloc_comp_produit"><?=$this->fireView('../ajax/produitComplementaire')?></div>
         		</div>
                <div class="bloc_onglet" id="images_produit">
                	<iframe name="bloc_images_produit" src="<?=$this->lurl?>/produits/upload/<?=$this->produits->id_produit?>" border="0" frameborder="0" width="100%" height="510" scrolling="no"></iframe> 
                </div>
           	</div>
            <table class="large">
                <tr>
                    <td>
                        <input type="hidden" name="form_mod_produit" id="form_mod_produit" />
                        <input type="submit" value="Valider la modification du produit" name="send_produit" id="send_produit" class="btn" />
                    </td>
                </tr>
            </table>
            <?php 
			if(count($this->lLangues) > 1)
			{
			?>   
				<div id="onglets">
					<?
					foreach($this->lLangues as $key => $lng)
					{			
					?>
						<a onclick="changeOngletLangue('<?=$key?>');" id="lien_<?=$key?>" title="<?=$lng?>" class="<?=($key==$this->language?'active':'')?>"><?=$lng?></a>
					<?
					}
					?>    	
				</div>
			<?php
			}
			?>
            <?
            foreach($this->lLangues as $key => $lng)
            {
                // Recuperation des elements du template de produit
                $this->lElements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = '.$this->produits->id_template,'ordre ASC');
            ?>
                <div id="langue_<?=$key?>"<?=($this->language!=$key?' style="display:none;"':'')?>>
                    <!-- DEBUT DES ELEMENTS DU TEMPLATE -->
                    <?
                    if(count($this->lElements) > 0)
                    {
                    ?>
                        <br />
                        <h1>El&eacute;ments du template</h1>
                        <table class="large">
                            <?
                            foreach($this->lElements as $element)
                            {
                                $this->produits->displayFormElement($this->produits->id_produit,$element,$key);
                            }
                            ?>
                        </table>
                        <table class="large">
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="form_mod_produit" id="form_mod_produit" />
                                    <input type="submit" value="Valider la modification du produit" name="send_produit" id="send_produit" class="btn" />
                                </td>
                            </tr>
                        </table>
                    <?
                    }
                    ?>
                    <!-- FIN DES ELEMENTS DU TEMPLATE -->   
                </div>
            <?
            }
            ?>
       	</fieldset>
    </form>
</div>