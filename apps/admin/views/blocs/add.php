<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="add_bloc" id="add_bloc" enctype="multipart/form-data" action="<?=$this->lurl?>/blocs" target="_parent">
        <h1>Ajouter un bloc</h1>
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
                    <th><label>Statut du bloc :</label></th>
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
                        <input type="hidden" name="form_add_bloc" id="form_add_bloc" />
                        <input type="submit" value="Valider" name="send_bloc" id="send_bloc" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>