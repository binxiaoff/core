<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="edit_groupes" id="edit_groupes" enctype="multipart/form-data" action="<?=$this->lurl?>/clients/groupes/<?=$this->groupes->id_groupe?>" target="_parent">
        <h1>Modifier <?=$this->groupes->nom?></h1>            
        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" value="<?=$this->groupes->nom?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label>Statut du groupe :</label></th>
                    <td>
                        <input type="radio" value="1" id="status1" name="status" <?=($this->groupes->status == 1?'checked="checked"':'')?> class="radio" />
                        <label for="status1" class="label_radio">En ligne</label>
                        <input type="radio" value="0" id="status0" name="status" <?=($this->groupes->status == 0?'checked="checked"':'')?> class="radio" />
                        <label for="status0" class="label_radio">Hors ligne</label>	
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_edit_groupes" id="form_edit_groupes" />
                        <input type="submit" value="Valider" title="Valider" name="send_groupes" id="send_groupes" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>