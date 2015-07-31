<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="edit_client" id="edit_client" enctype="multipart/form-data" action="<?=$this->lurl?>/clients/<?=$this->clients->id_client?>" target="_parent">
        <h1>Modifier <?=$this->clients->nom.' '.$this->clients->prenom?></h1>            
        <fieldset>
            <table class="formColor">            	
            	<tr>
                	<th><label for="civilite">Civilité :</label></th>
                    <td><?=$this->bdd->listEnum('clients','civilite','civilite',$this->clients->civilite)?></td>
                </tr>
            	<tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" value="<?=$this->clients->nom?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="prenom">Prénom :</label></th>
                    <td><input type="text" name="prenom" id="prenom" value="<?=$this->clients->prenom?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="telephone">Téléphone :</label></th>
                    <td><input type="text" name="telephone" id="telephone" value="<?=$this->clients->telephone?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="telephone">Date de naissance :</label></th>
                    <td><input type="text" name="naissance" id="naissance" value="<?=$this->clients->naissance?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="email">Email :</label></th>
                    <td><input type="text" name="email" id="email" value="<?=$this->clients->email?>" class="input_large" /></td>
                </tr>
        	</table>
            <br />
            <h1>Modifier adresse de facturation par défaut</h1>
            <table class="formColor">            	
            	<tr>
                	<th><label for="civilite">Civilité :</label></th>
                    <td><?=$this->bdd->listEnum('clients_adresses','civilite','civilite',$this->clients_adresses->civilite)?></td>
                </tr>
            	<tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" value="<?=$this->clients_adresses->nom?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="prenom">Prénom :</label></th>
                    <td><input type="text" name="prenom" id="prenom" value="<?=$this->clients_adresses->prenom?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="adresse1">Adresse :</label></th>
                    <td><input type="text" name="adresse1" id="adresse1" value="<?=$this->clients_adresses->adresse1?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="adresse2">Adresse (suite) :</label></th>
                    <td><input type="text" name="adresse2" id="adresse2" value="<?=$this->clients_adresses->adresse2?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="adresse3">Adresse (suite) :</label></th>
                    <td><input type="text" name="adresse3" id="adresse3" value="<?=$this->clients_adresses->adresse3?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="cp">Code Postal :</label></th>
                    <td><input type="text" name="cp" id="cp" value="<?=$this->clients_adresses->cp?>" class="input_court" /></td>
                </tr>
                <tr>
                    <th><label for="ville">Ville :</label></th>
                    <td><input type="text" name="ville" id="ville" value="<?=$this->clients_adresses->ville?>" class="input_large" /></td>
                </tr>
                <tr>
                	<th><label for="id_pays">Pays :</label></th>
                    <td>
						<select name="id_pays" id="id_pays" class="select">
                            <?
                            foreach($this->lPays as $p)
                            {
                                echo '<option value="'.$p['id_pays'].'"'.($p['id_pays']==$this->clients_adresses->id_pays?' selected="selected"':'').'>'.$p[$this->language].'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="telephone">Téléphone :</label></th>
                    <td><input type="text" name="telephone" id="telephone" value="<?=$this->clients_adresses->telephone?>" class="input_large" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_edit_client" id="form_edit_client" />
                        <input type="submit" value="Valider" title="Valider" name="send_client" id="send_client" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>