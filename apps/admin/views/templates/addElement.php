<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="add_element" id="add_element" enctype="multipart/form-data" action="<?=$this->lurl?>/templates/elements" target="_parent">
    	<input type="hidden" name="id_template" id="id_template" value="<?=$this->templates->id_template?>" />
    	<h1>Ajouter un &eacute;l&eacute;ment au template <?=$this->templates->name?></h1>
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
                    <th><label for="type_element">Type d'&eacute;l&eacute;ment :</label></th>
                    <td>
                        <select name="type_element" id="type_element" class="select">
                            <?
                            foreach($this->tree->typesElements as $elt)
                            {
                                echo '<option value="'.$elt.'">'.$elt.'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Statut de l'&eacute;l&eacute;ment :</label></th>
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
                        <input type="hidden" name="form_add_element" id="form_add_element" />
                        <input type="submit" value="Valider" name="send_element" id="send_element" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>