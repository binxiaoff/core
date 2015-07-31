<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="search_emprunteurs" id="search_emprunteur" enctype="multipart/form-data" action="<?=$this->lurl?>/emprunteurs/gestion" target="_parent">
        <h1>Rechercher un emprunteur</h1>            
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="nom">Nom emprunteur :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="prenom">Prenom emprunteur :</label></th>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="email">Email emprunteur :</label></th>
                    <td><input type="text" name="email" id="email" class="input_large" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_search_emprunteur" id="form_search_emprunteur" />
                        <input type="submit" value="Valider" title="Valider" name="send_emprunteur" id="send_emprunteur" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>