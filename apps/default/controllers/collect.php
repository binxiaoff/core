<?php

class collectController extends bootstrap
{
	var $Command;

	function collectController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);


		$this->catchAll = true;
	}

	function _default()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = true;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;

		die;

	}

	function _prospect()
	{
		$key = 'unilend';
		$time = '60';

		// si token ok
		if(isset($_POST['token']) && $this->ficelle->verifier_token(trim($_POST['token']),$key,$time)){

			$form_ok = true;

			$erreur = '';

			$nom = trim($_POST['nom']);
			$prenom = trim($_POST['prenom']);
			$email = trim($_POST['email']);
			$date = trim($_POST['date']);
			$utm_source = trim($_POST['utm_source']);
			$utm_source2 = trim($_POST['utm_source2']);
			$utm_source3 = trim($_POST['utm_source3']);
			$slug_origine = trim($_POST['slug_origine']);

			/*$nom = 'toto';
			$prenom = 'tata';
			$email = 'courtier.damien@gmail.fr';
			$date = '2009-05-01 12:06:01';
			$utm_source = '';*/

			// Verif nom
			if(!isset($nom) || strlen($nom) > 255 || strlen($nom) <= 0){
				$form_ok = false;
				$erreur .= 'Nom;';
			}
			// Verif prenom
			if(!isset($prenom) || strlen($prenom) > 255 || strlen($prenom) <= 0){
				$form_ok = false;
				$erreur .= 'Prenom;';
			}
			// Verif email
			if(!isset($email) || $email == '' || strlen($email) > 255 || strlen($email) <= 0){
				$form_ok = false;
				$erreur .= 'Email;';
			}
			// Verif format mail
			elseif(!$this->ficelle->isEmail($email)){
				$form_ok = false;
				$erreur .= 'Format email;';
			}
			// Si exite déjà
			elseif($this->clients->existEmail($email) == false){

				$clients_status_history = $this->loadData('clients_status_history');
				if($this->clients->get($email,'slug_origine != "" AND email') && $clients_status_history->counter('id_client = '.$this->clients->id_client) <= 0){
					$form_update = true;
				}
				else{
					$form_ok = false;
					$erreur .= 'Email existant;';
				}
			}
			// Verif date presente ou pas
			if(!isset($date) || $date == '0000-00-00 00:00:00' || $date == '' || strlen($date) > 19 || strlen($date) < 19){
				$date = date('Y-m-d H:i:s');
				$date_diff = false;
			}
			else $date_diff = true;

			if(!isset($slug_origine) || $slug_origine == ''){
				$slug_origine = '';
			}
			if(!isset($utm_source) || $utm_source == ''){

				if($slug_origine != '') $utm_source = $slug_origine;
				else $utm_source = $this->lurl.'/prospect';
			}

			if(!isset($utm_source2) || $utm_source2 == ''){
				$utm_source2 = '';
			}
			if(!isset($utm_source3) || $utm_source3 == ''){
				$utm_source3 = '';
			}


			// Si ok
			if($form_ok == true){

				// chargement des datas
				$this->prospects = $this->loadData('prospects');

				// Si on a l'email dans la table client et dans la table prospect
				if($form_update == true && $this->prospects->get($email,'email')){
					$form_prospect_update = true;
				}

				$this->prospects->nom = $nom;
				$this->prospects->prenom = $prenom;
				$this->prospects->email = $email;
				$this->prospects->id_langue = $this->language;
				$this->prospects->source = $utm_source;
				$this->prospects->source2 = $utm_source2;
				$this->prospects->source3 = $utm_source3;
				$this->prospects->slug_origine = $slug_origine;
				if($form_prospect_update == true)$this->prospects->update();
				else $this->prospects->id_prospect = $this->prospects->create();

				// on modifie la date du added en bdd
				if($date_diff == true) $this->prospects->update_added($date,$this->prospects->id_prospect);

				$reponse = 'OK';
				$id_prospect = $this->prospects->id_prospect;
			}
			else{
				$erreur = explode(';',$erreur);
				$lesErreurs = array_filter($erreur);

				$newErreurs = array();
				foreach($lesErreurs as $k => $e){
					$newErreurs[$k]['erreur'] = $e;
				}
				$erreur = $newErreurs;

				$reponse = $erreur;
			}
		}
		else $reponse = array('Erreur' => 'Token');

		echo json_encode(array('reponse' => $reponse,'id_prospect' => $id_prospect));

		die;
	}

	function _inscription()
	{

		$key = 'unilend';
		$time = '60';

		// si token ok
		if(isset($_POST['token']) && $this->ficelle->verifier_token(trim($_POST['token']),$key,$time)){

			// chargement des datas
			$this->pays = $this->loadData('pays_v2');
			$this->nationalites = $this->loadData('nationalites_v2');
			$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
			$this->clients = $this->loadData('clients');
			$this->clients_adresses = $this->loadData('clients_adresses');
			$this->lenders_accounts = $this->loadData('lenders_accounts');


			$form_ok = true;

			$utm_source = trim($_POST['utm_source']);
			$utm_source2 = trim($_POST['utm_source2']);
			$utm_source3 = trim($_POST['utm_source3']);
			$slug_origine = trim($_POST['slug_origine']);
			$forme_preteur = trim($_POST['forme_preteur']);
			$civilite = trim($_POST['civilite']);
			$nom = trim($_POST['nom']);
			$nom_usage = trim($_POST['nom_usage']);
			$prenom = trim($_POST['prenom']);
			$email = trim($_POST['email']);
			$password = trim($_POST['password']);
			$question = trim($_POST['question']);
			$reponse = trim($_POST['reponse']);

			$adresse_fiscale = trim($_POST['adresse_fiscale']);
			$ville_fiscale = trim($_POST['ville_fiscale']);
			$cp_fiscale = trim($_POST['cp_fiscale']);
			$id_pays_fiscale = trim($_POST['id_pays_fiscale']);

			$adresse = trim($_POST['adresse']);
			$ville = trim($_POST['ville']);
			$cp = trim($_POST['cp']);
			$id_pays = trim($_POST['id_pays']);

			$telephone = trim($_POST['telephone']);
			$id_nationalite = trim($_POST['id_nationalite']);
			$date_naissance = trim($_POST['date_naissance']);
			$commune_naissance = trim($_POST['commune_naissance']);
			$id_pays_naissance = trim($_POST['id_pays_naissance']);
			$signature_cgv = trim($_POST['signature_cgv']);
			$date = trim($_POST['date']);
			$insee_birth = isset($_POST['insee_birth']) ? trim($_POST['insee_birth']) : '';

			// test //

			/*$utm_source = 'source';
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
			$date = '2014-05-20 10:15:06';*/

			//////////

			$form_ok = true;

			$erreur = '';

			// Verif forme preteur
			if(!isset($forme_preteur) || !in_array($forme_preteur,array(1,3))){
				$form_ok = false;
				$erreur .= 'Forme preteur;';
			}
			// Verif civilite
			if(!isset($civilite) || !in_array($civilite,array('M.','Mme','Mlle'))){
				$form_ok = false;
				$erreur .= 'Civilite;';
			}
			// Verif nom
			if(!isset($nom) || strlen($nom) > 255 || strlen($nom) <= 0){
				$form_ok = false;
				$erreur .= 'Nom;';
			}
			// Verif nom usage
			if(strlen($nom_usage) > 255){
				$form_ok = false;
				$erreur .= 'Nom usage;';
			}
			// Verif prenom
			if(!isset($prenom) || strlen($prenom) > 255 || strlen($prenom) <= 0){
				$form_ok = false;
				$erreur .= 'Prenom;';
			}
			// Verif email
			if(!isset($email) || $email == '' || strlen($email) > 255 || strlen($email) <= 0){
				$form_ok = false;
				$erreur .= 'Email;';
			}
			// Verif format mail
			elseif(!$this->ficelle->isEmail($email)){
				$form_ok = false;
				$erreur .= 'Format email;';
			}
			// Si exite déjà
			elseif($this->clients->existEmail($email) == false){
				$clients_status_history = $this->loadData('clients_status_history');
				if($this->clients->get($email,'origine = 1 AND email') && $clients_status_history->counter('id_client = '.$this->clients->id_client) <= 0){
					$form_update = true;
				}
				else{
					$form_ok = false;
					$erreur .= 'Email déjà présent;';
				}
			}
			// Verif mot de passe
			if(!isset($password) || strlen($password) > 255 || strlen($password) <= 0){
				$form_ok = false;
				$erreur .= 'Mot de passe;';
			}
			// Verif question
			if(strlen($question) > 255){
				$form_ok = false;
				$erreur .= 'Question secrète;';
			}
			// Verif reponse
			if(strlen($reponse) > 255){
				$form_ok = false;
				$erreur .= 'Reponse secrète;';
			}

			// Verif adresse fiscale
			if(!isset($adresse_fiscale) || strlen($adresse_fiscale) > 255 || strlen($adresse_fiscale) <= 0){
				$form_ok = false;
				$erreur .= 'Adresse fiscale;';
			}
			// Verif ville fiscale
			if(!isset($ville_fiscale) || strlen($ville_fiscale) > 255 || strlen($ville_fiscale) <= 0){
				$form_ok = false;
				$erreur .= 'Ville fiscale;';
			}
			// Verif cp fiscale
			$oVilles = $this->loadData('villes');
			if (!isset($cp_fiscale) || false === $oVilles->exist($_POST['postal'], 'cp')) {
				$form_ok = false;
				$erreur .= 'Code postal fiscale;';
			}
			// Verif id pays fiscale
			if(!isset($id_pays_fiscale) || $this->pays->get($id_pays_fiscale,'id_pays') == false){
				$form_ok = false;
				$erreur .= 'Pays fiscale;';
			}


			// meme adresse ou non
			if($adresse == '' && $ville == '' && $cp == '' && in_array($id_pays,array('',0))) $meme_adresse_fiscal = true;
			else{
				$meme_adresse_fiscal = false;

				// Verif adresse
				if(isset($adresse) && strlen($adresse) > 255){
					$form_ok = false;
					$erreur .= 'Adresse;';
				}

				// Verif ville
				if(isset($ville) && strlen($ville) > 255){
					$form_ok = false;
					$erreur .= 'Ville;';
				}

				// Verif cp
				if(isset($cp) && strlen($cp) != 0 && strlen($cp) != 5){
					$form_ok = false;
					$erreur .= 'Code postal;';
				}

				// Verif id pays
				if(isset($id_pays) && strlen($id_pays) > 0 && $this->pays->get($id_pays,'id_pays') == false){
					$form_ok = false;
					$erreur .= 'Pays;';
				}

			}


			// Verif telephone
			if(!isset($telephone) || strlen($telephone) < 9 || strlen($telephone) > 14){
				$form_ok = false;
				$erreur .= 'Téléphone;';
			}

			// Verif id nationalite
			if(!isset($id_nationalite) || $this->nationalites->get($id_nationalite,'id_nationalite') == false){
				$form_ok = false;
				$erreur .= 'Nationalité;';
			}

			// Verif date de naissance
			if(!isset($date_naissance) || $date_naissance == '0000-00-00 00:00:00' || strlen($date_naissance) != 10 || $this->dates->ageplus18($date_naissance) == false){
				$form_ok = false;
				$erreur .= 'Date de naissance;';
			}


			// Verif Commune de naissance
			if(!isset($commune_naissance) || strlen($commune_naissance) > 255 || strlen($commune_naissance) <= 0){
				$form_ok = false;
				$erreur .= 'Commune de naissance;';
			}
			// Verif id pays naissance
			if(!isset($id_pays_naissance) || $this->pays->get($id_pays_naissance,'id_pays') == false){
				$form_ok = false;
				$erreur .= 'Pays de naissance;';
			}
			// Verif code insee de naissance
			if (1 == $id_pays_naissance) {
				//Check birth city
				if ('' == $insee_birth) {
					$oVilles = $this->loadData('villes');
					//for France, the code insee is empty means that the city is not verified with table "villes", check again here.
					if (false === $oVilles->get($_POST['naissance'], 'ville')) {
						$form_ok = false;
						$erreur .= 'Code INSEE de naissance;';
					} else {
						$insee_birth = $oVilles->insee;
					}
					unset($oVilles);
				}
			} else {
				/** @var pays_v2 $oPays */
				$oPays = $this->loadData('pays_v2');
				/** @var insee_pays $oInseePays */
				$oInseePays = $this->loadData('insee_pays');

				if ($oPays->get($id_pays_naissance) && $oInseePays->get($oPays->iso)) {
					$insee_birth = $oInseePays->COG;
				} else {
					$form_ok = false;
					$erreur .= 'Code INSEE de naissance;';
				}
				unset($oPays, $oInseePays);
			}

			// Verif signature cgv
			if(!isset($signature_cgv) || $signature_cgv != 1){
				$form_ok = false;
				$erreur .= 'Signature cgv;';
			}

			// Verif date presente ou pas
			if(!isset($date) || $date == '0000-00-00 00:00:00' || $date == '' || strlen($date) > 19 || strlen($date) < 19){
				$date = date('Y-m-d H:i:s');
				$date_diff = false;
			}
			else $date_diff = true;

			// slug_origine
			if(!isset($slug_origine) || $slug_origine == ''){
				$slug_origine = '';
			}

			// utm source
			if(!isset($utm_source) || $utm_source == ''){

				if($slug_origine != '') $utm_source = $slug_origine;
				else $utm_source = $this->lurl.'/inscription';
			}

			// utm source
			if(!isset($utm_source2) || $utm_source2 == ''){
				$utm_source2 = '';
			}
			// utm source
			if(!isset($utm_source3) || $utm_source3 == ''){
				$utm_source3 = '';
			}

			// Si ok
			if($form_ok == true){


				// client
				$this->clients->id_langue = 'fr';

				$this->clients->civilite = $civilite;
				$this->clients->nom = $nom;
				$this->clients->nom_usage = $nom_usage;
				$this->clients->prenom = $prenom;
				$this->clients->slug = $this->bdd->generateSlug($prenom.'-'.$nom);


				$this->clients->naissance = $date_naissance;
				$this->clients->id_pays_naissance = $id_pays_naissance;
				$this->clients->ville_naissance = $commune_naissance;
				$this->clients->insee_birth = $insee_birth;
				$this->clients->id_nationalite = $id_nationalite;

				$this->clients->telephone = $telephone;
				$this->clients->email = $email;
				$this->clients->password = $password;
				$this->clients->secrete_question = $question;
				$this->clients->secrete_reponse = md5($reponse);
				$this->clients->type = $forme_preteur;

				$this->clients->status_pre_emp =  1; // preteur
				$this->clients->status = 1; // online
				$this->clients->status_inscription_preteur = 1; // inscription terminé
				$this->clients->etape_inscription_preteur = 1; // etape 1 ok
				$this->clients->source = $utm_source;
				$this->clients->source2 = $utm_source2;
				$this->clients->source3 = $utm_source3;
				$this->clients->slug_origine = $slug_origine;

				// slugs autorisés à une offre de bienvenue
				$this->settings->get("Offre de bienvenue slug",'type');
				$ArraySlugOffre = explode(';',$this->settings->value);

				// temporaire
				/*if(date('Y') == 2015){
					if(in_array(trim($slug_origine),array('Lp-2015-web','2015')))	$this->clients->origine = 1; // offre ok
					else 															$this->clients->origine = 0; // pas d'offre
				}
				else{*/
					if(in_array(trim($slug_origine),$ArraySlugOffre))	$this->clients->origine = 1; // offre ok
					else 												$this->clients->origine = 0; // pas d'offre
				//}
				//$this->clients->origine = 0;

				// enregistrement
				if($form_update == true)$this->clients->update();
				else $this->clients->id_client = $this->clients->create();

				// on modifie la date du added en bdd
				if($date_diff == true) $this->clients->update_added($date,$this->clients->id_client);

				// client adresse
				if($form_update == true) $this->clients_adresses->get($this->clients->id_client,'id_client');
				else $this->clients_adresses->id_client = $this->clients->id_client;

				$this->clients_adresses->adresse_fiscal = $adresse_fiscale;
				$this->clients_adresses->cp_fiscal = $cp_fiscale;
				$this->clients_adresses->ville_fiscal = $ville_fiscale;
				$this->clients_adresses->id_pays_fiscal = $id_pays_fiscale;

				if($meme_adresse_fiscal == true){
					$this->clients_adresses->adresse1 = $adresse_fiscale;
					$this->clients_adresses->cp = $cp_fiscale;
					$this->clients_adresses->ville = $ville_fiscale;
					$this->clients_adresses->id_pays = $id_pays_fiscale;
				}
				else{
					$this->clients_adresses->adresse1 = $adresse;
					$this->clients_adresses->cp = $cp;
					$this->clients_adresses->ville = $ville;
					$this->clients_adresses->id_pays = $id_pays;
				}

				// enregistrement
				if($form_update == true)$this->clients_adresses->update();
				else $this->clients_adresses->create();

				// lender
				if($form_update == true)$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
				else $this->lenders_accounts->id_client_owner = $this->clients->id_client;
				$this->lenders_accounts->status = 1; // statut lender online

				// enregistrement
				if($form_update == true)$this->lenders_accounts->update();
				else $this->lenders_accounts->create();

				// acceptations_legal_docs
				$this->settings->get('Lien conditions generales inscription preteur particulier','type');
				$this->lienConditionsGeneralesParticulier = $this->settings->value;

				if($this->acceptations_legal_docs->get($this->lienConditionsGeneralesParticulier,'id_client = "'.$this->clients->id_client.'" AND id_legal_doc')) $accepet_ok = true;
				else $accepet_ok = false;

				$this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGeneralesParticulier;
				$this->acceptations_legal_docs->id_client = $this->clients->id_client;

				// enregistrement
				if($accepet_ok == true)$this->acceptations_legal_docs->update();
				else $this->acceptations_legal_docs->create();

				// on recup les infos client
				$this->clients->get($this->clients->id_client,'id_client');
				$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');


				// Motif virement
				$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
				$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
				$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');

				// email inscription preteur //

				//******************************************************//
				//*** ENVOI DU MAIL CONFIRMATION INSCRIPTION PRETEUR ***//
				//******************************************************//

				// Recuperation du modele de mail
				$this->mails_text->get('confirmation-inscription-preteur','lang = "'.$this->language.'" AND type');

				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;

				// FB
				$this->settings->get('Facebook','type');
				$lien_fb = $this->settings->value;

				// Twitter
				$this->settings->get('Twitter','type');
				$lien_tw = $this->settings->value;

				// Variables du mailing
				$varMail = array(
				'surl' => $surl,
				'url' => $url,
				'prenom' => utf8_decode($this->clients->prenom),
				'email_p' => $this->clients->email,
				'mdp' => '',
				'motif_virement' => $motif,
				'lien_fb' => $lien_fb,
				'lien_tw' => $lien_tw);

				// Construction du tableau avec les balises EMV
				$tabVars = $this->tnmp->constructionVariablesServeur($varMail);

				// Attribution des données aux variables
				$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);
				$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
				$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);

				// Envoi du mail
				$this->email = $this->loadLib('email',array());
				$this->email->setFrom($this->mails_text->exp_email,$exp_name);
				$this->email->setSubject(stripslashes($sujetMail));
				$this->email->setHTMLBody(stripslashes($texteMail));

				if($this->Config['env'] == 'prod') // nmp
				{
					Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->clients->email,$tabFiler);

					// Injection du mail NMP dans la queue
					$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
				}
				else // non nmp
				{
					$this->email->addRecipient(trim($this->clients->email));
					Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
				}
				// fin mail

				///////////////////////////////

				$_SESSION['LP_id_unique'] = $this->clients->id_client;

				$reponse = 'OK';

				echo json_encode(array('reponse' => $reponse,'URL' => $this->lurl.'/inscription_preteur/etape2/'.$this->clients->hash,'uniqueid' => $this->clients->id_client));
				die;

			}
			else{
				$lesErreurs = explode(';',$erreur);
				$lesErreurs = array_filter($lesErreurs);

				$newErreurs = array();
				foreach($lesErreurs as $k => $e){
					$newErreurs[$k]['erreur'] = $e;
				}
				$erreur = $newErreurs;

				$reponse = $erreur;
			}
		}
		else $reponse = array('Erreur' => 'Token');

		echo json_encode(array('reponse' => $reponse,'URL' => ''));

		die;
	}




}