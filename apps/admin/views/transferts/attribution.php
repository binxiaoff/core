

<div id="popup" style="width:500px;height:400px;">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <h1>Rechercher un prêteur </h1>
    <p>Montant : <?=$this->ficelle->formatNumber($this->receptions->montant/100)?> €</p>
     <p style="text-align:center;color:green;display:none;" class="reponse_valid_vir">Virement effectué</p>
    <div id="leformpreteur">
	<form method="post" name="search_preteur" id="search_preteur" enctype="multipart/form-data" action="<?=$this->lurl?>/preteurs/gestion" target="_parent">

        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="id">ID :</label></th>
                    <td><input type="text" name="id" id="id" class="input_large" /></td>
                </tr>
                <tr>
                    <th colspan="2" style="text-align:center;"><br />Personne physique</th>
                </tr>
                <tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="nom" id="nom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="prenom">Prenom :</label></th>
                    <td><input type="text" name="prenom" id="prenom" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="email">Email :</label></th>
                    <td><input type="text" name="email" id="email" class="input_large" /></td>
                </tr>
                <tr>
                    <th colspan="2" style="text-align:center;"><br />Personne morale</th>
                </tr>
                <tr>
                    <th><label for="raison_sociale">Raison sociale :</label></th>
                    <td><input type="text" name="raison_sociale" id="raison_sociale" class="input_large" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>

                        <input type="button" value="Valider" title="Valider" name="send_preteur" id="send_preteur" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
    </div>
    <div id="reponse">

    </div>
</div>

<script type="text/javascript">



$("#send_preteur").click(function() {

	var id = $("#id").val();
	var nom = $("#nom").val();
	var prenom = $("#prenom").val();
	var email = $("#email").val();
	var raison_sociale = $("#raison_sociale").val();

	var val = {
		id: id,
		nom: nom,
		prenom: prenom,
		email: email,
		raison_sociale: raison_sociale,
		id_reception: <?=$this->receptions->id_reception?>,
	}

	$.post(add_url + '/ajax/attribution', val).done(function(data) {
		//alert(data);

		if(data != 'nok')
		{
			$("#leformpreteur").hide();
			$("#reponse").show();
			$("#reponse").html(data);

			/*setTimeout(function() {
				$(".reponse").slideUp();
			}, 3000);*/
		}
	});
});

</script>
