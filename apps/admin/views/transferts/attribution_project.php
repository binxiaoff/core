

<div id="popup" style="width:500px;height:400px;">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <h1>Rechercher un projet en remboursement</h1>
    <p>Montant : <?=$this->ficelle->formatNumber($this->receptions->montant/100)?> €</p>
     <p style="text-align:center;color:green;display:none;" class="reponse_valid_pre">Prélèvement effectué</p>
    <div id="leformProject">
	<form method="post" name="search_project" id="search_project" enctype="multipart/form-data" action="" target="_parent">

        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="id">ID :</label></th>
                    <td><input type="text" name="id" id="id" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="siren">Siren :</label></th>
                    <td><input type="text" name="siren" id="siren" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="raison_sociale">Raison sociale :</label></th>
                    <td><input type="text" name="raison_sociale" id="raison_sociale" class="input_large" /></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>

                        <input type="button" value="Valider" title="Valider" name="send_project" id="send_project" class="btn" />
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



$("#send_project").click(function() {

	var id = $("#id").val();
	var siren = $("#siren").val();
	var raison_sociale = $("#raison_sociale").val();

	var val = {
		id: id,
		siren : siren,
		raison_sociale: raison_sociale,
		id_reception: <?=$this->receptions->id_reception?>,
	}

	$.post(add_url + '/ajax/attribution_project', val).done(function(data) {


		if(data != 'nok')
		{
			$("#leformProject").hide();
			$("#reponse").show();
			$("#reponse").html(data);

			/*setTimeout(function() {
				$(".reponse").slideUp();
			}, 3000);*/
		}
	});
});

</script>
