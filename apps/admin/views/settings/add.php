<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="add_settings" id="add_settings" enctype="multipart/form-data" action="<?=$this->lurl?>/settings" target="_parent">
        <h1>Ajouter un paramètre</h1>            
        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="type">Type :</label></th>
                    <td><input type="text" name="type" id="type" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="value">Valeur :</label></th>
                    <td><input type="text" name="value" id="value" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="id_template">Template :</label></th>
                    <td>
                        <select name="id_template" id="id_template" class="select">
                            <option value="">Sélectionner</option>
							<?
                            foreach($this->lTemplates as $t)
                            {
                                echo '<option value="'.$t['id_template'].'">'.$t['name'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Statut du paramètre :</label></th>
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
                        <input type="hidden" name="form_add_settings" id="form_add_settings" />
                        <input type="submit" value="Valider" title="Valider" name="send_settings" id="send_settings" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>