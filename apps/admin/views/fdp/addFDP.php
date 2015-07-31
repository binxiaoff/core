<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="add_montant" id="add_montant" enctype="multipart/form-data" action="<?=$this->lurl?>/fdp" target="_parent">
        <h1>Ajouter un montant à la zone <?=$this->params[0]?></h1>            
        <fieldset>
        	<table class="formColor">
            	<tr>
                	<th><label for="id_zone_aff">ID Zone :</label></td>
                    <td>
                    	<input type="text" name="id_zone_aff" id="id_zone_aff" value="<?=$this->params[0]?>" disabled="disabled" class="input_court" />
                        <input type="hidden" name="id_zone" id="id_zone" value="<?=$this->params[0]?>" />
                   	</td>
                </tr>
                <tr>
                	<th><label for="id_type">Type :</label></th>
                    <td>
						<select name="id_type" id="id_type" class="select">
                            <?
                            foreach($this->lTypes as $t)
                            {
                                echo '<option value="'.$t['id_type'].'">'.$t['nom'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr> 
                <tr>
                	<th><label for="poids">Poids (g) :</label></td>
                    <td><input type="text" name="poids" id="poids" class="input_court" /></td>
                </tr> 
                <tr>
                	<th><label for="fdp">Montant :</label></td>
                    <td><input type="text" name="fdp" id="fdp" class="input_court" /></td>
                </tr>
                <tr>
                	<th><label for="fdp_reduit">Montant réduit :</label></td>
                    <td><input type="text" name="fdp_reduit" id="fdp_reduit" class="input_court" /></td>
                </tr> 
                <tr>
                	<th><label for="montant_free">Gratuit à partir de :</label></td>
                    <td><input type="text" name="montant_free" id="montant_free" class="input_court" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_add_fdp" id="form_add_fdp" />
                        <input type="submit" value="Valider" title="Valider" name="send_zone" id="send_zone" class="btn" />
                    </th>
                </tr>
       		</table>
        </fieldset>
    </form>
</div>