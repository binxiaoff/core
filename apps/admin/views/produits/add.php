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
        <li>Ajout d'un produit</li>
    </ul>
    <form method="post" name="add_produit" id="add_produit" enctype="multipart/form-data"> 
    	<input type="hidden" name="lng_encours" id="lng_encours" value="<?=$this->language?>" />  
        <input type="hidden" name="id_produit_temp" id="id_produit_temp" value="<?=(isset($this->params[2]) && $this->params[2]!=''?$this->params[2]:$this->id_produit_temp)?>" /> 
        <fieldset>
        	<div class="gauche">
            	<h1>Informations produit</h1>
                <table class="form">
					<tr>
                        <th><label for="type">Type :</label></th>
                        <td colspan="2">
                        	<input type="text" name="type" id="type" class="input_large" value="<?=$this->params[0]?>" readonly />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="id_template">Template produit :</label></th>
                        <td colspan="2">
                            <select name="id_template" id="id_template" onchange="if(confirm('Voulez-vous vraiment changer ce template ?\nLe contenu existant de ce produit sera d\351finitivement \351ffac\351.')){ window.location.href = '<?=$this->lurl?>/produits/add/<?=$this->params[0]?>/' + this.value + '/<?=$this->id_produit_temp?>'; }" class="select">
                                <option value="">Choisir un template</option>
                                <?
                                foreach($this->lTemplates as $t)
                                {
                                    echo '<option value="'.$t['id_template'].'"'.(isset($this->params[1]) && $this->params[1] == $t['id_template']?' selected="selected"':'').'>'.$t['name'].'</option>';
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
                                    echo '<option value="'.$c['id_tree'].'">'.$c['title'].'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ordre_produit">Ordre dans la catégorie :</label></th>
                        <td colspan="2"><input type="text" name="ordre_produit" id="ordre_produit" class="input_court" /></td>
                    </tr>
                    <tr>
                        <th><label for="id_tree">Catégories secondaires :</label></th>
                        <td colspan="2">
                            <select name="id_tree[]" id="id_tree" class="selectm" multiple="multiple">
                                <option value="0">Choisir une/des catégorie(s)</option>
                                <?
                                foreach($this->lTree as $p)
                                {
                                    echo '<option value="'.$p['id_tree'].'">'.$p['title'].'</option>';
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
                                    echo '<option value="'.$b['id_brand'].'">'.$b['name'].'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tva">TVA (%) :</label></th>
                        <td colspan="2"><input type="text" name="tva" id="tva" value="<?=$this->tva?>" class="input_court" /></td>
                    </tr>
                    <tr>
                        <th><label>Statut du produit :</label></th>
                        <td width="110px">
                            <input type="radio" value="1" id="status1" class="radio" name="status" />
                            <label for="status1" class="label_radio">En ligne</label>
                        </td>
                        <td width="150px">
                            <input type="radio" value="0" id="status0" class="radio" name="status" checked="checked" />
                            <label for="status0" class="label_radio">Hors ligne</label>	
                        </td>
                    </tr>
                </table>
                <br /><br />
                <?
				for($i=1; $i<=10; $i++)
				{
				?>
                	<div id="contenuDetails<?=$i?>"<?=($i != 1?' style="display: none;"':'')?>>
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
                            <input type="hidden" name="nbdetails" id="nbdetails" value="1" />
                            <a onclick="ajouterDetails('nbdetails');" id="lienAjoutDetails" class="btn_link" title="Ajouter un détail">Ajouter un détail</a>
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
                                <a class="btn_link" onclick="if(document.getElementById('id_crosseling').value > 0){ ajoutProduitComp('<?=(isset($this->params[2]) && $this->params[2]!=''?$this->params[2]:$this->id_produit_temp)?>',document.getElementById('id_crosseling').value); }">Ajouter</a>
                            </td>
                        </tr>
                  	</table>
                    <div id="bloc_comp_produit"><?=$this->fireView('../ajax/produitComplementaire')?></div>
         		</div>
                <div class="bloc_onglet" id="images_produit">
                	<iframe name="bloc_images_produit" src="<?=$this->lurl?>/produits/upload/<?=(isset($this->params[2]) && $this->params[2]!=''?$this->params[2]:$this->id_produit_temp)?>" border="0" frameborder="0" width="100%" height="510" scrolling="no"></iframe> 
                </div>
			
           	</div>
            <table class="large">
                <tr>
                    <td>
                        <input type="hidden" name="form_add_produit" id="form_add_produit" />
                        <input type="submit" value="Valider l'ajout du produit" name="send_produit" id="send_produit" class="btn" />
                    </td>
                </tr>
            </table>
            <?
			// Affichage des infos qu'au choix du template
			if(isset($this->params[1]) & $this->params[1] != '')
			{
			?>
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
					$this->lElements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = '.$this->params[1],'ordre ASC');
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
                                    $this->produits->displayFormElement((isset($this->params[2]) && $this->params[2]!=''?$this->params[2]:$this->id_produit_temp),$element,$key);
                                }
                                ?>
                            </table>
                            <table class="large">
                                <tr>
                                    <td colspan="2">
                                        <input type="hidden" name="form_add_produit" id="form_add_produit" />
                        				<input type="submit" value="Valider l'ajout du produit" name="send_produit" id="send_produit" class="btn" />
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
            <?
			}
			?>
       	</fieldset>
    </form>
</div>