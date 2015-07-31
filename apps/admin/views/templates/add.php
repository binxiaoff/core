<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="add_template" id="add_template" enctype="multipart/form-data" action="<?=$this->lurl?>/templates" target="_parent">
        <h1>Ajouter un template</h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="name">Nom :</label></th>
                    <td><input type="text" name="name" id="name" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="slug">Lien permanent :</label></th>
                    <td><input type="text" name="slug" id="slug" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label>Statut du template :</label></th>
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
                        <input type="hidden" name="form_add_template" id="form_add_template" />
                        <input type="submit" value="Valider" name="send_template" id="send_template" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>