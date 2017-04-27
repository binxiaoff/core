<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <div id="popup-content">
        <form name="add_prescripteur" id="add_prescripteur" action="#">
            <h1>Ajouter un prescripteur</h1>
            <fieldset>
                <table class="formColor" style="width: 755px;">
                    <tr>
                        <th>Civilité</th>
                        <td>
                            <input type="radio" name="civilite" id="civilite_mme" value="Mme">
                            <label for="civilite_mme">Madame</label>
                            <input type="radio" name="civilite" id="civilite_m" value="M.">
                            <label for="civilite_m">Monsieur</label>
                        </td>
                        <th></th>
                        <td></td>
                    </tr>
                    <tr>
                        <th><label for="nom">Nom</label></th>
                        <td><input type="text" name="nom" id="nom" class="input_large" value=""></td>
                        <th><label for="prenom">Prénom</label></th>
                        <td><input type="text" name="prenom" id="prenom" class="input_large" value=""></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email</label></th>
                        <td><input type="text" name="email" id="email" class="input_large" value=""></td>
                        <th><label for="telephone">Téléphone</label></th>
                        <td><input type="text" name="telephone" id="telephone" class="input_large" value=""></td>
                    </tr>
                    <tr>
                        <th><label for="adresse">Adresse</label></th>
                        <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value=""></td>
                    </tr>
                    <tr>
                        <th><label for="cp">Code postal</label></th>
                        <td><input type="text" name="cp" id="cp" class="input_large" value=""></td>
                        <th><label for="ville">Ville</label></th>
                        <td><input type="text" name="ville" id="ville" class="input_large" value=""></td>
                    </tr>
                    <tr>
                        <th><label for="company_name">Raison sociale</label></th>
                        <td><input type="text" name="company_name" id="company_name" class="input_large" value=""></td>
                        <th><label for="siren">SIREN</label></th>
                        <td><input type="text" name="siren" id="siren" class="input_large" value=""></td>
                    </tr>
                    <tr>
                        <th><label for="iban">IBAN</label></th>
                        <td><input type="text" name="iban" id="iban" class="input_large" value=""></td>
                        <th><label for="bic">BIC</label></th>
                        <td><input type="text" name="bic" id="bic" class="input_large" value=""></td>
                    </tr>
                    <tr><td><input type="hidden" name="id_project" value="<?= isset($this->params[0]) ? $this->params[0] : '' ?>"></td></tr>
                    <tr>
                        <th colspan="4">
                            <input type="submit" value="Créer prescripteur" name="send_add_prescripteur" class="btn">
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
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
            url: "<?= $this->lurl ?>/prescripteurs/add_client",
            type: 'POST',
            data: values,
            dataType: 'json',
            error: function() {
                alert('An error has occurred');
            },
            success: function(data) {

                if (data.result && data.result == 'OK') {
                    $("#popup-content").html('le prescripteur a &eacute;t&eacute; cr&eacute;&eacute; !');
                    $("#id_prescripteur").val(data.id_prescripteur);
                    $("#civilite_prescripteur").html(values.civilite);
                    $("#prenom_prescripteur").html(values.prenom);
                    $("#nom_prescripteur").html(values.nom);
                    $("#email_prescripteur").html(values.email);
                    $("#telephone_prescripteur").html(values.telephone);
                    $("#company_prescripteur").html(values.company_name);
                    $("#siren_prescripteur").html(values.siren);
                    $('.identification_prescripteur').show('slow');
                    parent.$.fn.colorbox.close();
                } else {
                    alert('An error has occurred');
                }
            }
        });
    });
</script>
