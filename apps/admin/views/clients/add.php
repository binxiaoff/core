<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="add_client" id="add_client" enctype="multipart/form-data" action="<?=$this->lurl?>/clients" target="_parent">
        <h1>Ajouter un client</h1>            
        <fieldset>
            <table class="formColor">            	
            	<tr>
                	<th><label for="civilite">Civilité :</label></th>
                    <td><?=$this->bdd->listEnum('clients','civilite','civilite','select')?></td>
                </tr>
            	<tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="prenom">Prénom :</label></th>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="telephone">Téléphone :</label></th>
                    <td><input type="text" name="telephone" id="telephone" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="email">Email :</label></th>
                    <td><input type="text" name="email" id="email" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="adresse1">Adresse :</label></th>
                    <td><input type="text" name="adresse1" id="adresse1" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="adresse2">Adresse (suite) :</label></th>
                    <td><input type="text" name="adresse2" id="adresse2" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="adresse3">Adresse (suite) :</label></th>
                    <td><input type="text" name="adresse3" id="adresse3" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="cp">Code Postal :</label></th>
                    <td><input type="text" name="cp" id="cp" class="input_court" /></td>
                </tr>
                <tr>
                    <th><label for="ville">Ville :</label></th>
                    <td><input type="text" name="ville" id="ville" class="input_large" /></td>
                </tr>
                <tr>
                	<th><label for="id_pays">Pays :</label></th>
                    <td>
						<select name="id_pays" id="id_pays" class="select">
                            <?
                            foreach($this->lPays as $p)
                            {
                                echo '<option value="'.$p['id_pays'].'">'.$p[$this->language].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_add_client" id="form_add_client" />
                        <input type="submit" value="Valider" title="Valider" name="send_client" id="send_client" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>