<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="modif_zone" id="modif_zone" enctype="multipart/form-data" action="<?=$this->lurl?>/fdp/<?=$this->params[0]?>" target="_parent">
        <h1>Modifier la zone <?=$this->params[0]?></h1>            
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
                	<th><label for="id_pays">Pays :</label></th>
                    <td>
						<select name="id_pays[]" id="id_pays" class="selectm" multiple="multiple">
                        	<?
                            foreach($this->liste_pays_oqp as $pays_oqp)
							{
							?>
                                <option value="<?=$pays_oqp['id_pays']?>" selected="selected"><?=$pays_oqp[$this->language]?></option>
                            <?
							}
							foreach($this->liste_pays_dispo as $pays)
							{
							?>
                                <option value="<?=$pays['id_pays']?>"><?=$pays[$this->language]?></option>
                            <?
                           	}
							?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_edit_zone" id="form_edit_zone" />
                        <input type="submit" value="Valider" title="Valider" name="send_zone" id="send_zone" class="btn" />
                    </th>
                </tr>
       		</table>
        </fieldset>
    </form>
</div>