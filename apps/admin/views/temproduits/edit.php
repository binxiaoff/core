<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="edit_templates" id="edit_templates" enctype="multipart/form-data" action="<?=$this->lurl?>/temproduits/<?=$this->templates->id_template?>" target="_parent">
        <h1>Modifier <?=$this->templates->name?></h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="name">Nom :</label></th>
                    <td><input type="text" name="name" id="name" class="input_large" value="<?=$this->templates->name?>" /></td>
                </tr>
                <tr>
                    <th><label>Statut du template :</label></th>
                    <td>
                        <input type="radio" value="1" id="status1" name="status" class="radio" <?=($this->templates->status == 1?'checked="checked"':'')?> />
                        <label for="status1" class="label_radio">En ligne</label>
                        <input type="radio" value="0" id="status0" name="status" class="radio" <?=($this->templates->status == 0?'checked="checked"':'')?> />
                        <label for="status0" class="label_radio">Hors ligne</label>	
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_edit_template" id="form_edit_template" />
                        <input type="submit" value="Valider" name="send_template" id="send_template" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>