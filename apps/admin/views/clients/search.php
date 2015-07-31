<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="search_client" id="search_client" enctype="multipart/form-data" action="<?=$this->lurl?>/clients" target="_parent">
        <h1>Rechercher un client</h1>            
        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="reference">Réf. commande :</label></th>
                    <td><input type="text" name="reference" id="reference" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="nom">Nom client :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="prenom">Prenom client :</label></th>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="email">Email client :</label></th>
                    <td><input type="text" name="email" id="email" class="input_large" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_search_client" id="form_search_client" />
                        <input type="submit" value="Valider" title="Valider" name="send_client" id="send_client" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>