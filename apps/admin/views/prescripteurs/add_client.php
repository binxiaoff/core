<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form name="add_prescripteur" id="add_prescripteur" action="#">
        <h1>Ajouter un prescripteur</h1>            
        <fieldset>
            <table class="formColor" style="width: 755px;">
                <tr>
                    <th>Civilité :</th>
                    <td>
                        <input type="radio" name="civilite" id="civilite_mme" value="Mme"/>
                        <label for="civilite_mme">Madame</label>

                        <input type="radio" name="civilite" id="civilite_m" value="M."/>
                        <label for="civilite_m">Monsieur</label>
                    </td>
                    <th></th>
                    <td></td>
                </tr>
            	<tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" value=""/></td>
                    
                    <th><label for="prenom">Prénom :</label></th>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" value=""/></td>
                </tr>
                <tr>
                    <th><label for="email">Email :</label></th>
                    <td><input type="text" name="email" id="email" class="input_large" value=""/></td>
                    <th><label for="telephone">Téléphone :</label></th>
                    <td><input type="text" name="telephone" id="telephone" class="input_large" value=""/></td>
                </tr>
                <tr>
                    <th><label for="adresse">Adresse :</label></th>
                    <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value=""/></td>
                </tr>
                <tr>
                    <th><label for="cp">Code postal :</label></th>
                    <td><input type="text" name="cp" id="cp" class="input_large" value=""/></td>
                    
                    <th><label for="ville">Ville :</label></th>
                    <td><input type="text" name="ville" id="ville" class="input_large" value=""/></td>
                </tr>
            	<tr>
                	<th colspan="4">
                        <input type="submit" value="Valider" title="Valider" name="send_add_prescripteur" id="send_add_prescripteur" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>
<script>
    $('#add_prescripteur').submit(function(e) {
        e.preventDefault();
        // get all the inputs into an array.
        var $inputs = $('#add_prescripteur :input');

        // not sure if you wanted this, but I thought I'd add it.
        // get an associative array of just the values.
        var values = {};
        $inputs.each(function() {
            values[this.name] = $(this).val();
        });

        $.ajax({
            url: "<?=$this->lurl?>/prescripteurs/add_client",
            type: 'POST',
            data: values,
            error: function() {
                alert('An error has occurred');
            },
            success: function(data) {
                if('OK' == data) {
                    $("#popup").html('le prescripteur a &eacute;t&eacute; cr&eacute;t&eacute; !');
                } else {
                    alert('An error has occurred');
                }
            }
        });
    });
</script>