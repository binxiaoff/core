<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="edit_requete" id="edit_requete" enctype="multipart/form-data" action="<?=$this->lurl?>/queries/<?=$this->queries->id_query?>" target="_parent">
        <h1>Modifier <?=$this->queries->name?></h1>            
        <fieldset>
            <table class="formColor">
            <tr>
                <th><label for="name">Nom :</label></th>
                <td><input type="text" name="name" id="name" value="<?=$this->queries->name?>" class="input_large" /></td>
            </tr>
            <tr>
                <th><label for="paging">Nb par page :</label></td>
                <td><input type="text" name="paging" id="paging" value="<?=$this->queries->paging?>" class="input_court" /></th>
            </tr>
            <tr>
                <th><label for="sql">SQL :</label></th>
                <td><textarea name="sql" id="sql" class="textarea"><?=$this->queries->sql?></textarea></td>
            </tr>
            <tr>
            	<td>&nbsp;</td>
                <th>
                    <input type="hidden" name="form_edit_requete" id="form_edit_requete" />
                    <input type="submit" value="Valider" title="Valider" name="send_requete" id="send_requete" class="btn" />
                </th>
            </tr>
        </table>
        </fieldset>
    </form>
</div>