
<?
// on génère le token
		$token = $this->ficelle->genere_token('unilend');
		
		echo '<br>';
		
		//if($this->ficelle->verifier_token($token,'unilend','60')){
			//echo 'good';
		//}
		//else echo 'pas good';
		
		$nom = 'toto';
		$prenom = 'tata';
		$email = 'courtier.damien@gmail.fr';
		$date = '2009-05-01 12:06:01';
		
		if(!empty($_GET["utm_source"])) $utm_source = $_GET["utm_source"];
    	else $utm_source = "";
		if(!empty($_GET["utm_source2"])) $utm_source2 = $_GET["utm_source2"];
    	else $utm_source2 = "";
		
		?>
        <style>
		th{text-align:right;width:200px;padding-right: 15px;}
		input{width:250px;}
		</style>
        
        <h2>Prospect</h2>
        <form action="<?=$this->lurl?>/collect/prospect" method="post" target="_blank">
        <table>
        	<tr>
            	<th>token</th>
            	<td>
                <input type="text" name="token" id="token_p" value="<?=$token?>">
                </td>
            </tr>
            <tr>
            	<th>source</th>
            	<td>
                <input type="text" name="utm_source" id="utm_source_p" value="<?=$utm_source?>">
                </td>
            </tr>
             <tr>
            	<th>source 2</th>
            	<td>
                <input type="text" name="utm_source2" id="utm_source2_p" value="<?=$utm_source2?>">
                </td>
            </tr>
            <tr>
            	<th>nom</th>
            	<td>
                <input type="text" name="nom" id="nom_p" value="<?=$nom?>">
                </td>
            </tr>
            <tr>
            	<th>prenom</th>
            	<td>
                <input type="text" name="prenom" id="prenom_p" value="<?=$prenom?>">
                </td>
            </tr>
            <tr>
            	<th>email</th>
            	<td>
                <input type="text" name="email" id="email_p" value="<?=$email?>">
                </td>
            </tr>
            <tr>
            	<th>date</th>
            	<td>
                <input type="text" name="date" id="date_p" value="<?=$date?>">
                </td>
            </tr>
            <tr>
            	<th></th>
            	<td>
                <input type="button" name="valider" id="valider_p" value="valider">
                </td>
            </tr>
        </table>
        </form>
        
        <script type="text/javascript">
		$("#valider_p" ).click(function( event ) {
			var val = {
				token: $("#token_p").val(),
				utm_source: $("#utm_source_p").val(),
				utm_source2: $("#utm_source2_p").val(),
				nom: $("#nom_p").val(),
				prenom: $("#prenom_p").val(),
				email: $("#email_p").val(),
				date: $("#date_p").val()	
			}

			$.post( "<?=$this->lurl?>/collect/prospect", val).done(function( data ) {
				alert(data);
			});
		});
		</script>
        
        
        <?

		if(!empty($_GET["utm_source"])) $utm_source = $_GET["utm_source"];
    	else $utm_source = "";
		if(!empty($_GET["utm_source2"])) $utm_source2 = $_GET["utm_source2"];
    	else $utm_source2 = "";
		
		$forme_preteur = '1';
		$civilite = 'M.';
		$nom = 'toto premier';
		$nom_usage = 'toto junior';
		$prenom = 'damien';
		$email = 'courtier.damien@equinoa.fr';
		$password = '202cb962ac59075b964b07152d234b70';
		$question = 'toto ?';
		$reponse = 'toto';
		
		$adresse_fiscale = 'chez moi';
		$ville_fiscale = 'bobo';
		$cp_fiscale = '77350';
		$id_pays_fiscale = '1';
		
		$adresse = '';
		$ville = '';
		$cp = '';
		$id_pays = '';
		
		$telephone = '0164559200';
		$id_nationalite = '1';
		$date_naissance = '1989-05-05';
		$commune_naissance = 'melun';
		$id_pays_naissance = '1';
		$signature_cgv = '1';
		$date = '2014-05-20 10:15:06';
		
		
		
		?>
        
        <h2>Inscription prêteur physique</h2>
        <form action="<?=$this->lurl?>/collect/inscription" method="post" target="_blank">
        <table>
        	<tr>
            	<th>token</th>
            	<td>
                <input type="text" name="token" id="token" value="<?=$token?>">
                </td>
            </tr>
            <tr>
            	<th>source</th>
            	<td>
                <input type="text" name="utm_source" id="utm_source" value="<?=$utm_source?>">
                </td>
            </tr>
            <tr>
            	<th>source 2</th>
            	<td>
                <input type="text" name="utm_source2" id="utm_source2" value="<?=$utm_source?>">
                </td>
            </tr>
            <tr>
            	<th>Forme preteur</th>
            	<td>
                <input type="text" name="forme_preteur" id="forme_preteur" value="<?=$forme_preteur?>">
                </td>
            </tr>
            <tr>
            	<th>Civilite</th>
            	<td>
                <input type="text" name="civilite" id="civilite" value="<?=$civilite?>">
                </td>
            </tr>
            <tr>
            	<th>nom</th>
            	<td>
                <input type="text" name="nom" id="nom" value="<?=$nom?>">
                </td>
            </tr>
            <tr>
            	<th>Nom usage</th>
            	<td>
                <input type="text" name="nom_usage" id="nom_usage" value="<?=$nom_usage?>">
                </td>
            </tr>
            <tr>
            	<th>prenom</th>
            	<td>
                <input type="text" name="prenom" id="prenom" value="<?=$prenom?>">
                </td>
            </tr>
            <tr>
            	<th>email</th>
            	<td>
                <input type="text" name="email" id="email" value="<?=$email?>">
                </td>
            </tr>
            <tr>
            	<th>Mot de passe</th>
            	<td>
                <input type="text" name="password" id="password" value="<?=$password?>">
                </td>
            </tr>
            <tr>
            	<th>Question secrete</th>
            	<td>
                <input type="text" name="question" id="question" value="<?=$question?>">
                </td>
            </tr>
            <tr>
            	<th>Reponse secrete</th>
            	<td>
                <input type="text" name="reponse" id="reponse" value="<?=$reponse?>">
                </td>
            </tr>
            <tr>
            	<th>Adresse fiscale</th>
            	<td>
                <input type="text" name="adresse_fiscale" id="adresse_fiscale" value="<?=$adresse_fiscale?>">
                </td>
            </tr>
            <tr>
            	<th>Ville adresse fiscale</th>
            	<td>
                <input type="text" name="ville_fiscale" id="ville_fiscale" value="<?=$ville_fiscale?>">
                </td>
            </tr>
            <tr>
            	<th>Code postale fiscale</th>
            	<td>
                <input type="text" name="cp_fiscale" id="cp_fiscale" value="<?=$cp_fiscale?>">
                </td>
            </tr>
            <tr>
            	<th>Pays adresse fiscale</th>
            	<td>
                <input type="text" name="id_pays_fiscale" id="id_pays_fiscale" value="<?=$id_pays_fiscale?>">
                </td>
            </tr>
            
            
            
            <tr>
            	<th>Adresse correspondance</th>
            	<td>
                <input type="text" name="adresse" id="adresse" value="<?=$adresse?>">
                </td>
            </tr>
            <tr>
            	<th>Ville adresse correspondance</th>
            	<td>
                <input type="text" name="ville" id="ville" value="<?=$ville?>">
                </td>
            </tr>
            <tr>
            	<th>Code postale correspondance</th>
            	<td>
                <input type="text" name="cp" id="cp" value="<?=$cp?>">
                </td>
            </tr>
            <tr>
            	<th>Pays adresse correspondance</th>
            	<td>
                <input type="text" name="id_pays" id="id_pays" value="<?=$id_pays?>">
                </td>
            </tr>
            
            <tr>
            	<th>Téléphone</th>
            	<td>
                <input type="text" name="telephone" id="telephone" value="<?=$telephone?>">
                </td>
            </tr>
            <tr>
            	<th>Nationalité</th>
            	<td>
                <input type="text" name="id_nationalite" id="id_nationalite" value="<?=$id_nationalite?>">
                </td>
            </tr>
            <tr>
            	<th>Date naissance</th>
            	<td>
                <input type="text" name="date_naissance" id="date_naissance" value="<?=$date_naissance?>">
                </td>
            </tr>
            <tr>
            	<th>Commune naissance</th>
            	<td>
                <input type="text" name="commune_naissance" id="commune_naissance" value="<?=$commune_naissance?>">
                </td>
            </tr>
            <tr>
            	<th>Pays naissance</th>
            	<td>
                <input type="text" name="id_pays_naissance" id="id_pays_naissance" value="<?=$id_pays_naissance?>">
                </td>
            </tr>
            <tr>
            	<th>Signature des CGV</th>
            	<td>
                <input type="text" name="signature_cgv" id="signature_cgv" value="<?=$signature_cgv?>">
                </td>
            </tr>
            
            <tr>
            	<th>date</th>
            	<td>
                <input type="text" name="date" id="date" value="<?=$date?>">
                </td>
            </tr>
            <tr>
            	<th></th>
            	<td>
                <input type="button" name="valider" value="valider" id="valid_inscription">
                </td>
            </tr>
        </table>
        </form>
        
        
        <script type="text/javascript">
		$("#valid_inscription" ).click(function( event ) {
			var val = {
				token: $("#token").val(),
				utm_source: $("#utm_source").val(),
				utm_source2: $("#utm_source2").val(),
				forme_preteur: $("#forme_preteur").val(),
				civilite: $("#civilite").val(),
				nom: $("#nom").val(),
				nom_usage: $("#nom_usage").val(),
				prenom: $("#prenom").val(),
				email: $("#email").val(),
				password: $("#password").val(),
				question: $("#question").val(),
				reponse: $("#reponse").val(),
				ville_fiscale: $("#ville_fiscale").val(),
				adresse_fiscale: $("#adresse_fiscale").val(),
				id_pays_fiscale: $("#id_pays_fiscale").val(),
				cp_fiscale: $("#cp_fiscale").val(),
				adresse: $("#adresse").val(),
				ville: $("#ville").val(),
				cp: $("#cp").val(),
				id_pays: $("#id_pays").val(),
				telephone: $("#telephone").val(),
				id_nationalite: $("#id_nationalite").val(),
				date_naissance: $("#date_naissance").val(),
				commune_naissance: $("#commune_naissance").val(),
				id_pays_naissance: $("#id_pays_naissance").val(),
				signature_cgv: $("#signature_cgv").val(),
				date: $("#date").val()
			}

			$.post( "<?=$this->lurl?>/collect/inscription", val).done(function( data ) {
				alert(data);
			});
		});
		</script>