<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="add_bloc" id="add_bloc" enctype="multipart/form-data" action="<?=$this->lurl?>/temproduits/elements" target="_parent">
    	<input type="hidden" name="id_template" id="id_template" value="<?=$this->templates->id_template?>" />
    	<h1>Ajouter un bloc au template</h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="id_bloc">Choisir le bloc :</label></th>
                    <td>
                        <select name="id_bloc" id="id_bloc" class="select">
                            <option value="0">Choisir un bloc</option>
                            <?
                            foreach($this->lBlocsOnline as $blocs)
                            {
                                echo '<option value="'.$blocs['id_bloc'].'">'.$blocs['name'].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="position">Position :</label></th>
                    <td><?=$this->bdd->listEnum('blocs_templates','position','position','select')?></td>
                </tr>
                <tr>
                    <th><label>Statut du bloc :</label></th>
                    <td>
                        <input type="radio" value="1" id="status1" name="status" class="radio" checked="checked" />
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