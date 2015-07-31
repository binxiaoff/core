<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="edit_menu" id="edit_menu" enctype="multipart/form-data" action="<?=$this->lurl?>/menus/<?=$this->menus->id_menu?>" target="_parent">
        <h1>Modifier <?=$this->menus->nom?></h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" value="<?=$this->menus->nom?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label>Statut du menu :</label></th>
                    <td>
                        <input type="radio" value="1" id="status1" name="status" <?=($this->menus->status == 1?'checked="checked"':'')?> class="radio" />
                        <label for="status1" class="label_radio">En ligne</label>
                        <input type="radio" value="0" id="status0" name="status" <?=($this->menus->status == 0?'checked="checked"':'')?> class="radio" />
                        <label for="status0" class="label_radio">Hors ligne</label>	
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_edit_menu" id="form_edit_menu" />
                        <input type="submit" value="Valider" name="send_menu" id="send_menu" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>