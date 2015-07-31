<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="add_menu" id="add_menu" enctype="multipart/form-data" action="<?=$this->lurl?>/menus" target="_parent">
        <h1>Ajouter un menu</h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="slug">Permalink :</label></th>
                    <td><input type="text" name="slug" id="slug" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label>Statut du menu :</label></th>
                    <td>
                        <input type="radio" value="1" id="status1" name="status" checked="checked" class="radio" />
                        <label for="status1" class="label_radio">En ligne</label>
                        <input type="radio" value="0" id="status0" name="status" class="radio" />
                        <label for="status0" class="label_radio">Hors ligne</label>	
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_add_menu" id="form_add_menu" />
                        <input type="submit" value="Valider" name="send_menu" id="send_menu" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>