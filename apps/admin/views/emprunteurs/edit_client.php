<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="edit_emprunteur" id="edit_emprunteur" enctype="multipart/form-data" action="<?=$this->lurl?>/emprunteurs/edit/<?=$this->clients->id_client?>" target="_parent">
        <h1>Modifier <?=$this->clients->nom.' '.$this->clients->prenom?></h1>            
        <fieldset>
            <table class="formColor" style="width: 755px;">            	
            	<tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" value="<?=$this->clients->nom?>"/></td>
                    
                    <th><label for="prenom">Prénom :</label></th>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" value="<?=$this->clients->prenom?>"/></td>
                </tr>
                <tr>
                    <th><label for="email">Email :</label></th>
                    <td><input type="text" name="email" id="email" class="input_large" value="<?=$this->clients->email?>"/></td>
                    <th><label for="telephone">Téléphone :</label></th>
                    <td><input type="text" name="telephone" id="telephone" class="input_large" value="<?=$this->clients->telephone?>"/></td>
                </tr>
                <tr>
                    <th><label for="societe">Société :</label></th>
                    <td><input type="text" name="societe" id="societe" class="input_large" value="<?=$this->companies->name?>"/></td>
                    
                    <th><label for="secteur">Secteur :</label></th>
                    <td>
                    <select name="secteur" id="secteur" class="select">
                    	<option <?=($this->companies->sector == 'secteur 1'?'selected':'')?> value="secteur 1">secteur 1</option>
                        <option <?=($this->companies->sector == 'secteur 2'?'selected':'')?> value="secteur 2">secteur 2</option>
                        <option <?=($this->companies->sector == 'secteur 3'?'selected':'')?> value="secteur 3">secteur 3</option>
                        <option <?=($this->companies->sector == 'secteur 4'?'selected':'')?> value="secteur 4">secteur 4</option>
                    </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="adresse">Adresse :</label></th>
                    <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value="<?=$this->adresse?>"/></td>
                </tr>
                <tr>
                    <th><label for="cp">Code postal :</label></th>
                    <td><input type="text" name="cp" id="cp" class="input_large" value="<?=$this->cp?>"/></td>
                    
                    <th><label for="ville">Ville :</label></th>
                    <td><input type="text" name="ville" id="ville" class="input_large" value="<?=$this->ville?>"/></td>
                </tr>
                <tr>
                    <th><label for="cni_passeport">CNI/Passeport :</label></th>
                    <td>
                    <?=$this->clients->cni_passeport?><br>
                    <input type="file" name="cni_passeport" id="cni_passeport" value="<?=$this->clients->cni_passeport?>"/></td>
                    
                    <th><label for="signature">Signature :</label></th>
                    <td>
                    <?=$this->clients->signature?><br>
                    <input type="file" name="signature" id="signature" value="<?=$this->ville?>"/></td>
                </tr>
            	<tr>
                    
                	<th colspan="4">
                        <input type="hidden" name="form_edit_emprunteur" id="form_edit_emprunteur" />
                        <input type="submit" value="Valider" title="Valider" name="send_edit_emprunteur" id="send_edit_emprunteur" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>