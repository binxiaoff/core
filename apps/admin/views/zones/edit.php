<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="mod_zones" id="mod_zones" enctype="multipart/form-data" action="<?=$this->lurl?>/zones/<?=$this->zones->id_zone?>" target="_parent">
        <h1>Modifier <?=$this->zones->name?></h1>            
        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="name">Nom :</label></th>
                    <td><input type="text" name="name" id="name" class="input_large" value="<?=$this->zones->name?>" /></td>
                </tr>
                <tr>
                    <th><label for="slug">Permalink :</label></th>
                    <td><input type="text" name="slug" id="slug" class="input_large" value="<?=$this->zones->slug?>" /></td>
                </tr>
                <tr>
                    <th><label>Statut de la zone :</label></th>
                    <td>
                        <input type="radio" value="1" id="status1" name="status" class="radio" <?=($this->zones->status == 1?'checked="checked"':'')?> />
                        <label for="status1" class="label_radio">En ligne</label>
                        <input type="radio" value="0" id="status0" name="status" class="radio" <?=($this->zones->status == 0?'checked="checked"':'')?> />
                        <label for="status0" class="label_radio">Hors ligne</label>	
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_mod_zones" id="form_mod_zones" />
                        <input type="submit" value="Valider" title="Valider" name="send_zones" id="send_zones" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>