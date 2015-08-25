<?php

class inscription_preteurController extends bootstrap
{
	var $Command;
	
	function inscription_preteurController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		//Recuperation des element de traductions
		$this->lng['inscription-preteur-etape-header'] = $this->ln->selectFront('inscription-preteur-etape-header',$this->language,$this->App);
		
		$this->navigateurActive = 2;
		
	}
	
	function _default()
	{
		header('location:'.$this->lurl.'/inscription_preteur/etape1');
        die;
	}
	
	function _etape1()
	{
		// source
		$this->ficelle->source($_GET['utm_source'],$this->lurl.'/inscription_preteur/etape1',$_GET['utm_source2']);
		
		// CSS
		$this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
		$this->loadCss('default/preteurs/new-style');
		
		// JS
		$this->unLoadJs('default/functions');
		$this->unLoadJs('default/main');
		$this->unLoadJs('default/ajax');
		
		$this->loadJs('default/preteurs/functions');
		$this->loadJs('default/main',0,date('Ymd'));
		$this->loadJs('default/ajax',0,date('Ymd'));
		
		// Chargement des datas
		//$this->pays = $this->loadData('pays');
		$this->pays = $this->loadData('pays_v2');
		//$this->nationalites = $this->loadData('nationalites');
		$this->nationalites = $this->loadData('nationalites_v2');
		$this->companies = $this->loadData('companies');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		$this->clients_history_actions = $this->loadData('clients_history_actions');
		
		// etape 1
		$this->page_preteur = 1;
		
		// Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3',$this->language,$this->App);
		
		// Liste des pays
		$this->lPays = $this->pays->select('','ordre ASC');
		
		// liste des nationalites
		$this->lNatio = $this->nationalites->select('','ordre ASC');
		
		// cgu societe
		$this->settings->get('Lien conditions generales inscription preteur societe','type');
		$this->lienConditionsGeneralesSociete = $this->settings->value;
	
		// cgu particulier
		$this->settings->get('Lien conditions generales inscription preteur particulier','type');
		$this->lienConditionsGeneralesParticulier = $this->settings->value;
		
		// Liste deroulante conseil externe de l'entreprise
		$this->settings->get("Liste deroulante conseil externe de l'entreprise",'type');
		$this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);
		
		/////////////////////////////
		// Initialisation variable //
		$this->emprunteurCreatePreteur = false;
		$this->preteurOnline = false;
		$this->hash_client = '';
		
		//Ajout CM 06/08/14
		// Si on a une session landing client active
		if(count($_SESSION['landing_client'])>0)
		{
			$this->clients->prenom = $_SESSION['landing_client']['prenom'];
			$this->clients->nom = $_SESSION['landing_client']['nom'];
			$this->clients->email = $_SESSION['landing_client']['email'];
		}
		
		// Si on a une session active
		if(isset($_SESSION['client'])){
			// On recup le mec
			$this->clients->get($_SESSION['client']['id_client'],'id_client');
			
			// preteur ayant deja crée son compte
			if($this->clients->status_pre_emp == 1 && $this->clients->etape_inscription_preteur == 3){
				header('Location:'.$this->lurl.'/projects');
				die;
			}
			// preteur n'ayant pas terminé la création de son compte
			elseif($this->clients->status_pre_emp == 1 && $this->clients->etape_inscription_preteur < 3){
				$this->preteurOnline = true;	
			}	
			// Si c'est un emprunteur
			elseif($this->clients->status_pre_emp == 2){
				$this->emprunteurCreatePreteur = true;
				$this->clients->type = 2;
				// tant qu'on a pas le systeme preteur/emprunteur
				header('Location:'.$this->lurl.'/projects');
				die;
			}	
			
			
		}

		/////////////////////////////
		
		// message si email existe deja
		if(isset($_SESSION['reponse_email']) && $_SESSION['reponse_email'] != ''){
			$this->reponse_email = $_SESSION['reponse_email'];
			mail('d.courtier@relance.fr','reponse_email','reponse_email : '.$this->clients->email.' date : '.date('Y-m-d H:i:s'));
			unset($_SESSION['reponse_email']);
		}
		if(isset($_SESSION['reponse_age']) && $_SESSION['reponse_age'] != ''){
			$this->reponse_age = $_SESSION['reponse_age'];
			unset($_SESSION['reponse_age']);
		}
		// message si email existe deja
		if(isset($_SESSION['messageDeuxiemeCompte']) && $_SESSION['messageDeuxiemeCompte'] != ''){
			$this->messageDeuxiemeCompte = $_SESSION['messageDeuxiemeCompte'];
			unset($_SESSION['messageDeuxiemeCompte']);
		}
		
		if($this->emprunteurCreatePreteur == true)$this->modif = true;
		elseif($this->preteurOnline == true)$this->modif = true;
		else $this->modif = false;
		
		// Si on a un compte empreunteur qui veut créer un compte preteur (systheme pas en place - l'emprunteur ne peut pas créer de compte prêteur pour l'instant)
		if($this->emprunteurCreatePreteur == true)$conditionOk = true;
		
		// Si on a un hash client dans l'url
		elseif(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))$conditionOk = true;
		
		// Si on a une session (et pas de hash ;) )
		elseif(isset($_SESSION['client']) && $this->clients->get($_SESSION['client']['id_client'],'id_client'))$conditionOk = true;
		
		// sinon pas good pour recup les infos preteur
		else $conditionOk = false;

		// Check si y a un compte accessible 
		if($conditionOk == true){
			// adresses
			$this->clients_adresses->get($this->clients->id_client,'id_client');
			// lender
			if(!$this->lenders_accounts->get($this->clients->id_client,'id_client_owner')){
				$noLender = true;		
			}
			// societe
			if(in_array($this->clients->type,array(2,4))){
				//On recup la societe
				$this->companies->get($this->clients->id_client,'id_client_owner');
			}
			// particulier
			elseif(in_array($this->clients->type,array(1,3))){
				$nais = explode('-',$this->clients->naissance);	
				$this->jour = $nais[2];
				$this->mois = $nais[1];
				$this->annee = $nais[0];
			}
			
			/// DEBUT ETREANGER OU PAS ///
			$this->etranger = 0;
			// fr/resident etranger
			if($this->clients->id_nationalite <= 1 && $this->clients_adresses->id_pays_fiscal > 1){
				$this->etranger = 1;
			}
			// no fr/resident etranger
			elseif($this->clients->id_nationalite > 1 && $this->clients_adresses->id_pays_fiscal > 1){
				$this->etranger = 2;
			}
			/// FIN ETREANGER OU PAS ///
			
			$this->modif = true;						// On signale que c'est une mise a jour et non une creation
			
			$this->email = $this->clients->email;		// On enregistre l'adresse mail au cas où
			$this->hash_client = $this->clients->hash; 	// on enregistre le hash du client au cas où
		}
		
		if($this->clients->telephone != '')$this->clients->telephone = trim(chunk_split(str_replace(' ','',$this->clients->telephone), 2,' '));
		if($this->companies->phone != '')$this->companies->phone = trim(chunk_split(str_replace(' ','',$this->companies->phone), 2,' '));
		if($this->companies->phone_dirigeant != '') $this->companies->phone_dirigeant = trim(chunk_split(str_replace(' ','',$this->companies->phone_dirigeant), 2,' '));
		
		// Formulaire particulier etape 1
		if(isset($_POST['form_inscription_preteur_particulier_etape_1']))
		{
			$this->form_ok = true;
			
			////////////////////////////////////
			// On verifie meme adresse ou pas //
			////////////////////////////////////
			if($_POST['mon-addresse'] != false) $this->clients_adresses->meme_adresse_fiscal = 1; // la meme
			else $this->clients_adresses->meme_adresse_fiscal = 0; // pas la meme
			
			// Mon adresse fiscale
			$this->clients_adresses->adresse_fiscal = $_POST['adresse_inscription'];
			$this->clients_adresses->ville_fiscal = $_POST['ville_inscription'];
			$this->clients_adresses->cp_fiscal = $_POST['postal'];
			$this->clients_adresses->id_pays_fiscal = $_POST['pays1'];	
			
			// Adresse de correspondance
			// pas la meme
			if($this->clients_adresses->meme_adresse_fiscal == 0){
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adress2'];
				$this->clients_adresses->ville = $_POST['ville2'];
				$this->clients_adresses->cp = $_POST['postal2'];
				$this->clients_adresses->id_pays = $_POST['pays2'];	
			}
			// la meme
			else{
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adresse_inscription'];
				$this->clients_adresses->ville = $_POST['ville_inscription'];
				$this->clients_adresses->cp = $_POST['postal'];
				$this->clients_adresses->id_pays = $_POST['pays1'];	
			}
			////////////////////////////////////
			
			$this->clients->civilite = $_POST['sex'];
			$this->clients->nom = $this->ficelle->majNom($_POST['nom-famille']);
			
			if(isset($_POST['nom-dusage']) && $_POST['nom-dusage'] == $this->lng['etape1']['nom-dusage'])
			$this->clients->nom_usage = '';
			else $this->clients->nom_usage = $this->ficelle->majNom($_POST['nom-dusage']);
			
			$this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
			$this->clients->email = $_POST['email']; 							// Enregistrement du mail inscrit

			// Emprunteur crée compte preteur ( pas en place)
			if($this->emprunteurCreatePreteur == false){
				$this->clients->secrete_question = $_POST['secret-question'];
				$this->clients->secrete_reponse = md5($_POST['secret-response']);
			}
			
			$this->clients->telephone = str_replace(' ','',$_POST['phone']);
			$this->clients->ville_naissance = $_POST['naissance'];
			$this->clients->id_pays_naissance = $_POST['pays3'];
			$this->clients->id_nationalite = $_POST['nationalite'];
			$this->clients->naissance = $_POST['annee_naissance'].'-'.$_POST['mois_naissance'].'-'.$_POST['jour_naissance'];
			
			
			// Si le preteur a deja executé les 3 etapes d'inscription on retourne false
			if(isset($_SESSION['client']) && $_SESSION['client']['etape_inscription_preteur'] > 3){ 
				$this->form_ok = false;
			}
			
			// check_etranger
			/*if($this->etranger > 0){*/
				if(isset($_POST['check_etranger']) && $_POST['check_etranger'] == false){
					$this->form_ok = false;	
				}
			/*}*/
			
			// age (+18ans)
			if($this->dates->ageplus18($this->clients->naissance) == false){
				$this->form_ok = false;
				$_SESSION['reponse_age'] = $this->lng['etape1']['erreur-age'];
			}

			//nom-famille
			if(!isset($_POST['nom-famille']) || $_POST['nom-famille'] == $this->lng['etape1']['nom-de-famille']){
				$this->form_ok = false;
			}
			//nom-dusage
			if(!isset($_POST['nom-dusage']) || $_POST['nom-dusage'] == $this->lng['etape1']['nom-dusage']){
				//$this->form_ok = false;
			}
			//prenom
			if(!isset($_POST['prenom']) || $_POST['prenom'] == $this->lng['etape1']['prenom']){
				$this->form_ok = false;
			}
			//email
			if(!isset($_POST['email']) || $_POST['email'] == $this->lng['etape1']['email']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['email']) && $this->ficelle->isEmail($_POST['email']) == false){
				$this->form_ok = false;
			}
			elseif($_POST['email'] != $_POST['conf_email']){
				$this->form_ok = false;
			}
			// On regarde si l'addresse en POST existe deja en BDD
			elseif($this->clients->existEmail($_POST['email']) == false){
				
				// Dans le cas d'une modification
				if($this->modif == true){
					
					// On compare l'email du POST a celle enregsitré avant traitement
					if($_POST['email'] != $this->email){
						// Si les deux adresses mails sont diff on envoie un message d'erreur
						$this->reponse_email = $this->lng['etape1']['erreur-email'];
						mail('d.courtier@relance.fr','erreur-email','erreur-email : POST '.$_POST['email'].' email :'.$this->email.' date : '.date('Y-m-d H:i:s'));
						$this->error_email_exist = true;
					}
					// Si les deux adresses sont identique 
					else{
						// On fait rien car c'est son adresse mail
					}
				}
				// Dans le cas d'une creation
				else{
					// check si l'adresse mail est deja utilisé
					$this->reponse_email = $this->lng['etape1']['erreur-email'];
					$this->error_email_exist = true;
				}
			}
			
			// Emprunteur crée un compte preteur (pas en place donc on passe tout le temps de dans)
			if($this->emprunteurCreatePreteur == false){
				//pass
				if(!isset($_POST['pass']) || $_POST['pass'] == ''){
					$this->form_ok = false;
				}
				if(!isset($_POST['pass2']) || $_POST['pass2'] == ''){
					$this->form_ok = false;
				}
				if(isset($_POST['pass']) && isset($_POST['pass2']) && $_POST['pass'] != $_POST['pass2']){
					$this->form_ok = false;
				}
				//secret-question
				if(!isset($_POST['secret-question']) || $_POST['secret-question'] == $this->lng['etape1']['question-secrete']){
					$this->form_ok = false;
				}
				//secret-response
				if(!isset($_POST['secret-response']) || $_POST['secret-response'] == $this->lng['etape1']['response']){
					$this->form_ok = false;
				}
			}
			
			//adresse_inscription
			if(!isset($_POST['adresse_inscription']) || $_POST['adresse_inscription'] == $this->lng['etape1']['adresse']){
				$this->form_ok = false;
			}
			//ville_inscription
			if(!isset($_POST['ville_inscription']) || $_POST['ville_inscription'] == $this->lng['etape1']['ville']){
				$this->form_ok = false;
			}
			//postal
			if(!isset($_POST['postal']) || $_POST['postal'] == $this->lng['etape1']['code-postal']){
				$this->form_ok = false;
			}
			
			// pas la meme
			if($this->clients_adresses->meme_adresse_fiscal == 0){
				// adresse client
				if(!isset($_POST['adress2']) || $_POST['adress2'] == $this->lng['etape1']['adresse']){
					$this->form_ok = false;
				}
				if(!isset($_POST['ville2']) || $_POST['ville2'] == $this->lng['etape1']['ville']){
					$this->form_ok = false;
				}
				if(!isset($_POST['postal2']) || $_POST['postal2'] == $this->lng['etape1']['postal']){
					$this->form_ok = false;
				}
			}

			// Formulaire ok
			if($this->form_ok == true){
				// mdp si nouveau compte
				if($this->emprunteurCreatePreteur == false){
					$this->clients->password = md5($_POST['pass']);
				}
				
				// On créer le client
				$this->clients->id_langue = 'fr';
				
				// type de preteur
				if($this->clients->id_nationalite != 1)$this->clients->type = 3; // physique etrangé
				else $this->clients->type = 1; // physique
				
				$this->clients->fonction = ''; // pas de fonction pour les personnes physique
				$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);				
				$this->lenders_accounts->id_company_owner = 0; // pas de companie pour les personnes physique
				
				// Si mail existe deja
				/*if($this->reponse_email != '' && $this->modif = true){
					$this->clients->email = $this->email;
				}
				elseif($this->reponse_email != ''){
					$this->clients->email = '';
				}*/
				
				// DEBUT UPDATE //
				if($this->modif == true){
					
					if($this->error_email_exist == true){
						$this->clients->email = $this->email;
					}
					
					$le_id_client = $this->clients->id_client;

					$this->clients->update();
					$this->clients_adresses->update();
					$this->lenders_accounts->update();
					
					// Histo client //
					
					// (on fait une manip pour cacher le mdp et la reponse secrete) //
					$pass = $_POST['pass'];
					$pass2 = $_POST['pass2'];
					$secret_response = $_POST['secret-response'];
					$_POST['pass'] = md5($pass);
					$_POST['pass2'] = md5($pass2);
					$_POST['secret-response'] = md5($secret_response);
					
					$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
					$this->clients_history_actions->histo(13,'edition inscription etape 1 particulier',$le_id_client,$serialize);
					
					// on remet comme c'etait
					$_POST['pass'] = $pass;
					$_POST['pass2'] = $pass2;
					$_POST['secret-response'] = $secret_response;
					
					////////////////
					
					// Si on a une entreprise reliée, on la supprime car elle n'a plus rien a faire ici. on est un particulier.
					if($this->companies->get($this->clients->id_client,'id_client_owner')){
						$this->companies->delete($this->companies->id_company,'id_company');
					}
				}
				// FIN UPDATE //
				
				// DEBUT CREATE //
				else
				{
					if($this->error_email_exist == true){
						$this->clients->email = '';
					}
					
					
					$this->clients->source = $_SESSION['utm_source'];
					$this->clients->source2 = $_SESSION['utm_source2'];
					
					// type de preteur
					if($this->clients->id_nationalite != 1)$this->clients->type = 3; // physique etrangé
					else $this->clients->type = 1; // physique

					// On créer le client
					$this->clients->id_client = $this->clients->create();

					// Histo client //
					$pass = $_POST['pass'];
					$pass2 = $_POST['pass2'];
					$secret_response = $_POST['secret-response'];
					$_POST['pass'] = md5($pass);
					$_POST['pass2'] = md5($pass2);
					$_POST['secret-response'] = md5($secret_response);
					
					// Histo client //
					$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
					$this->clients_history_actions->histo(14,'inscription etape 1 particulier',$this->clients->id_client,$serialize);
					////////////////
					
					$_POST['pass'] = $pass;
					$_POST['pass2'] = $pass2;
					$_POST['secret-response'] = $secret_response;
					
					// Ainsi que adresses clients
					$this->clients_adresses->id_client = $this->clients->id_client;
					$this->clients_adresses->create();
					
					// creation du lender
					$this->lenders_accounts->id_client_owner = $this->clients->id_client;
					$this->lenders_accounts->create();
				}
				// FIN CREATE //
				
				if(isset($_POST['accept-cgu']) && $_POST['accept-cgu'] != false)
				{
					// acceptation des cgu 
					if($this->acceptations_legal_docs->get($this->lienConditionsGeneralesParticulier,'id_client = "'.$this->clients->id_client.'" AND id_legal_doc')) $accepet_ok = true;
					else $accepet_ok = false;
						
					$this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGeneralesParticulier;
					$this->acceptations_legal_docs->id_client = $this->clients->id_client;
					
					if($accepet_ok == true)$this->acceptations_legal_docs->update();
					else $this->acceptations_legal_docs->create();
				}
				
				if($this->reponse_email != '')
				{
					$_SESSION['reponse_email'] = $this->reponse_email;
					header('location:'.$this->lurl.'/inscription_preteur/etape1/'.$this->clients->hash);
					die;
				}
				else
				{
					// Si c'es un nouvel utilisateur
					if($this->emprunteurCreatePreteur == false && $this->modif == false || $this->modif == true && $this->clients->etape_inscription_preteur == 0)
					{
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
						'prenom' => $this->clients->prenom,
						'email_p' => $this->clients->email,
						'mdp' => $_POST['pass'],
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
						
						// parametres pour valider le compte //
						
						$this->clients->status_pre_emp =  1; // preteur
						$this->clients->status = 1; // online
						$this->clients->status_inscription_preteur = 1; // inscription terminé
						$this->clients->etape_inscription_preteur = 1; // etape 1 ok
						
						// type de preteur
						if($this->clients->id_nationalite != 1)$this->clients->type = 3; // physique etrangé
						else $this->clients->type = 1; // physique
						
						$this->lenders_accounts->status = 1; // statut lender online
						
						// Enregistrement
						$this->clients->update();
						$this->lenders_accounts->update();
						
						///////////////////////////////
						
					}
					
					header('location:'.$this->lurl.'/inscription_preteur/etape2/'.$this->clients->hash);
					
				}
				die;
			}
			else
			{
				header('location:'.$this->lurl.'/inscription_preteur/etape1/'.$this->params[0]);
				die;
			}
		}
		// Formulaire societe etape 1
		elseif(isset($_POST['send_form_inscription_preteur_societe_etape_1']))
		{

			$this->form_ok = true;
			
			$this->companies->name = $_POST['raison_sociale_inscription'];
			$this->companies->forme = $_POST['forme_juridique_inscription'];
			$this->companies->capital = str_replace(' ','',$_POST['capital_social_inscription']);
			$this->companies->phone = str_replace(' ','',$_POST['phone_inscription']);
			
			$this->companies->siret 	= $_POST['siret_inscription'];
			$this->companies->siren 	= substr($this->companies->siret,0,9);
			//$this->companies->siren 	= $_POST['siren_inscription'];
			
			////////////////////////////////////
			// On verifie meme adresse ou pas //
			////////////////////////////////////
			if($_POST['mon-addresse'] != false)
			$this->companies->status_adresse_correspondance = '1'; // la meme
			else
			$this->companies->status_adresse_correspondance = '0'; // pas la meme
			
			// adresse fiscal (siege de l'entreprise)
			$this->companies->adresse1 = $_POST['adresse_inscriptionE'];
			$this->companies->city = $_POST['ville_inscriptionE'];
			$this->companies->zip = $_POST['postalE'];
			$this->companies->id_pays = $_POST['pays1E'];
			
			// pas la meme
			if($this->companies->status_adresse_correspondance == 0){
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adress2E'];
				$this->clients_adresses->ville = $_POST['ville2E'];
				$this->clients_adresses->cp = $_POST['postal2E'];
				$this->clients_adresses->id_pays = $_POST['pays2E'];
			}
			// la meme
			else{
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adresse_inscriptionE'];
				$this->clients_adresses->ville = $_POST['ville_inscriptionE'];
				$this->clients_adresses->cp = $_POST['postalE'];
				$this->companies->id_pays = $_POST['pays1E'];
			}
			////////////////////////////////////////
			
			$this->companies->status_client = $_POST['enterprise']; // radio 1 dirigeant 2 pas dirigeant 3 externe
			
			$this->clients->civilite = $_POST['genre1'];
			$this->clients->nom = $this->ficelle->majNom($_POST['nom_inscription']);
			$this->clients->prenom = $this->ficelle->majNom($_POST['prenom_inscription']);
			$this->clients->fonction = $_POST['fonction_inscription'];
			$this->clients->email = $_POST['email_inscription'];
			$this->clients->telephone = str_replace(' ','',$_POST['phone_new_inscription']);
			
			//extern ou non dirigeant
			if($this->companies->status_client == 2 || $this->companies->status_client == 3){
				$this->companies->civilite_dirigeant = $_POST['genre2'];
				$this->companies->nom_dirigeant = $this->ficelle->majNom($_POST['nom2_inscription']);
				$this->companies->prenom_dirigeant = $this->ficelle->majNom($_POST['prenom2_inscription']);
				$this->companies->fonction_dirigeant = $_POST['fonction2_inscription'];
				$this->companies->email_dirigeant = $_POST['email2_inscription'];
				$this->companies->phone_dirigeant = str_replace(' ','',$_POST['phone_new2_inscription']);
				
				// externe
				if($this->companies->status_client == 3){
					$this->companies->status_conseil_externe_entreprise = $_POST['external-consultant'];
					$this->companies->preciser_conseil_externe_entreprise = $_POST['autre_inscription'];
				}
			}
			
			
			
			if($this->emprunteurCreatePreteur == false){
				//pass
				if(!isset($_POST['passE']) || $_POST['passE'] == ''){
					$this->form_ok = false;
				}
				if(!isset($_POST['passE2']) || $_POST['passE2'] == ''){
					$this->form_ok = false;
				}
				if(isset($_POST['passE']) && isset($_POST['passE2']) && $_POST['passE'] != $_POST['passE2']){
					$this->form_ok = false;
				}
				//secret-question
				if(!isset($_POST['secret-questionE']) || $_POST['secret-questionE'] == $this->lng['etape1']['question-secrete']){
					$this->form_ok = false;
				}
				//secret-response
				if(!isset($_POST['secret-responseE']) || $_POST['secret-responseE'] == $this->lng['etape1']['responseE']){
					$this->form_ok = false;
				}
			}
			
			//raison_sociale_inscription
			if(!isset($_POST['raison_sociale_inscription']) || $_POST['raison_sociale_inscription'] == $this->lng['etape1']['raison-sociale']){
				$this->form_ok = false;
			}
			//forme_juridique_inscription
			if(!isset($_POST['forme_juridique_inscription']) || $_POST['forme_juridique_inscription'] == $this->lng['etape1']['forme-juridique']){
				$this->form_ok = false;
			}
			//capital_social_inscription
			if(!isset($_POST['capital_social_inscription']) || $_POST['capital_social_inscription'] == $this->lng['etape1']['capital-sociale']){
				$this->form_ok = false;
			}
			//siren_inscription
			if(!isset($_POST['siret_inscription']) || $_POST['siret_inscription'] == $this->lng['etape1']['siret']){
				$this->form_ok = false;
			}
			/*if(!isset($_POST['siren_inscription']) || $_POST['siren_inscription'] == $this->lng['etape1']['siren']){
				$this->form_ok = false;
			}*/
			
			//phone_inscription
			if(!isset($_POST['phone_inscription']) || $_POST['phone_inscription'] == $this->lng['etape1']['telephone']){
				$this->form_ok = false;
			}
			elseif(strlen($_POST['phone_inscription']) < 9 || strlen($_POST['phone_inscription']) > 14){
				$this->form_ok = false;
			}
			
			//adresse_inscription
			if(!isset($_POST['adresse_inscriptionE']) || $_POST['adresse_inscriptionE'] == $this->lng['etape1']['adresse']){
				$this->form_ok = false;
			}
			
			//ville_inscription
			if(!isset($_POST['ville_inscriptionE']) || $_POST['ville_inscriptionE'] == $this->lng['etape1']['ville']){
				$this->form_ok = false;
			}
			//postal
			if(!isset($_POST['postalE']) || $_POST['postalE'] == $this->lng['etape1']['code-postal']){
				$this->form_ok = false;
			}
			
			// pas la meme
			if($this->companies->status_adresse_correspondance == 0){
				// adresse client
				if(!isset($_POST['adress2E']) || $_POST['adress2E'] == $this->lng['etape1']['adresse']){
					$this->form_ok = false;
				}
				if(!isset($_POST['ville2E']) || $_POST['ville2E'] == $this->lng['etape1']['ville']){
					$this->form_ok = false;
				}
				if(!isset($_POST['postal2E']) || $_POST['postal2E'] == $this->lng['etape1']['postal']){
					$this->form_ok = false;
				}
				if(!isset($_POST['pays2E']) || $_POST['pays2E'] == $this->lng['etape1']['pays']){
					$this->form_ok = false;
				}
			}
			
			//nom_inscription
			if(!isset($_POST['nom_inscription']) || $_POST['nom_inscription'] == $this->lng['etape1']['nom']){
				$this->form_ok = false;
			}
			//prenom_inscription
			if(!isset($_POST['prenom_inscription']) || $_POST['prenom_inscription'] == $this->lng['etape1']['prenom']){
				$this->form_ok = false;
			}
			//fonction_inscription
			if(!isset($_POST['fonction_inscription']) || $_POST['fonction_inscription'] == $this->lng['etape1']['fonction']){
				$this->form_ok = false;
			}
			//email_inscription
			if(!isset($_POST['email_inscription']) || $_POST['email_inscription'] == $this->lng['etape1']['email']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['email_inscription']) && $this->ficelle->isEmail($_POST['email_inscription']) == false){
				$this->form_ok = false;
			}
			elseif($_POST['email_inscription'] != $_POST['conf_email_inscription']){
				$this->form_ok = false;
			}
			elseif($this->clients->existEmail($_POST['email_inscription']) == false){
				// si modif
				if($this->modif == true){
					// et si l'email n'est pas celle du client
					if($_POST['email_inscription'] != $this->email)
					{
						// check si l'adresse mail est deja utilisé
						$this->reponse_email = $this->lng['etape1']['erreur-email'];
					}
				}
				else{
					// check si l'adresse mail est deja utilisé
					$this->reponse_email = $this->lng['etape1']['erreur-email'];
				}
			}
			
			//phone_new_inscription
			if(!isset($_POST['phone_new_inscription']) || $_POST['phone_new_inscription'] == $this->lng['etape1']['telephone']){
				$this->form_ok = false;
				
			}
			elseif(strlen($_POST['phone_new_inscription']) < 9 || strlen($_POST['phone_new_inscription']) > 14){
				$this->form_ok = false;
			}
			
			//extern ou non dirigeant
			if($this->companies->status_client == 2 || $this->companies->status_client == 3){
				
				if(!isset($_POST['nom2_inscription']) || $_POST['nom2_inscription'] == $this->lng['etape1']['nom']){
					$this->form_ok = false;
				}
				if(!isset($_POST['prenom2_inscription']) || $_POST['prenom2_inscription'] == $this->lng['etape1']['prenom']){

					$this->form_ok = false;
				}
				if(!isset($_POST['fonction2_inscription']) || $_POST['fonction2_inscription'] == $this->lng['etape1']['fonction']){
					$this->form_ok = false;
				}
				if(!isset($_POST['email2_inscription']) || $_POST['email2_inscription'] == $this->lng['etape1']['email']){
					$this->form_ok = false;
				}
				elseif(isset($_POST['email2_inscription']) && $this->ficelle->isEmail($_POST['email2_inscription']) == false){
					$this->form_ok = false;
				}
				if(!isset($_POST['phone_new2_inscription']) || $_POST['phone_new2_inscription'] == $this->lng['etape1']['telephone']){
					$this->form_ok = false;
				}
				elseif(strlen($_POST['phone_new2_inscription']) < 9 || strlen($_POST['phone_new2_inscription']) > 14){
				$this->form_ok = false;
				}
				// externe
				if($this->companies->status_client == 3){
					
					if(!isset($_POST['external-consultant']) || $_POST['external-consultant'] == ''){
						$this->form_ok = false;
						
					}
					/*if(isset($_POST['autre_inscription']) && $_POST['external-consultant'] == 3 && $_POST['autre_inscription'] == $this->lng['etape1']['autre']){
						$this->form_ok = false;
						
					}	*/
				}
			}

			// Formulaire societe ok
			if($this->form_ok == true)
			{
				$this->clients->password = md5($_POST['passE']);
				
				$this->clients->id_langue = 'fr';
				$this->clients->type = 2;
				$this->clients->nom_usage = '';
				$this->clients->naissance = '0000-00-00';
				$this->clients->ville_naissance = '';
				$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);
				
				if($this->emprunteurCreatePreteur == false)
				{
					$this->clients->secrete_question = $_POST['secret-questionE'];
					$this->clients->secrete_reponse = md5($_POST['secret-responseE']);
				}
				
				// Si mail existe deja
				if($this->reponse_email != '')
				{
					$this->clients->email = '';
				}
				
				// modif
				if($this->modif == true)
				{
					// On crée la l'entreprise si existe pas
					if($this->companies->exist($this->clients->id_client,'id_client_owner'))
					{
						$this->companies->update();
					}
					else
					{
						$this->companies->id_client_owner = $this->clients->id_client;
						$this->companies->id_company = $this->companies->create();
					}
					
					// On met a jour le lender
					$this->lenders_accounts->id_company_owner = $this->companies->id_company;
					
					if($this->emprunteurCreatePreteur == true && $noLender == true) 
					{
						$this->lenders_accounts->id_client_owner = $this->clients->id_client;	
						$this->lenders_accounts->create();
					}
					
					$this->lenders_accounts->update();
					
					// On met a jour le client
					$this->clients->update();
					// On met a jour l'adresse client
					$this->clients_adresses->update();
					
					// Histo client //
					$pass = $_POST['pass'];
					$pass2 = $_POST['pass2'];
					$secret_response = $_POST['secret-response'];
					$_POST['pass'] = md5($pass);
					$_POST['pass2'] = md5($pass2);
					$_POST['secret-response'] = md5($secret_response);
					
					// Histo client //
					$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
					$this->clients_history_actions->histo(14,'edition inscription etape 1 entreprise',$this->clients->id_client,$serialize);
					////////////////
					
					$_POST['pass'] = $pass;
					$_POST['pass2'] = $pass2;
					$_POST['secret-response'] = $secret_response;
				}
				// create
				else
				{
					$this->clients->source = $_SESSION['utm_source'];
					$this->clients->source2 = $_SESSION['utm_source2'];
					
					// On créer le client
					$this->clients->id_client = $this->clients->create();
					
					// Histo client //
					$pass = $_POST['pass'];
					$pass2 = $_POST['pass2'];
					$secret_response = $_POST['secret-response'];
					$_POST['pass'] = md5($pass);
					$_POST['pass2'] = md5($pass2);
					$_POST['secret-response'] = md5($secret_response);
					
					// Histo client //
					$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
					$this->clients_history_actions->histo(16,'edition inscription etape 1 entreprise',$this->clients->id_client,$serialize);
					////////////////
					
					$_POST['pass'] = $pass;
					$_POST['pass2'] = $pass2;
					$_POST['secret-response'] = $secret_response;
					
					// Ainsi que adresses clients
					$this->clients_adresses->id_client = $this->clients->id_client;
					$this->clients_adresses->create();
					
					// Et la companie
					$this->companies->id_client_owner = $this->clients->id_client;
					$this->companies->id_company = $this->companies->create();
					
					// creation du lender
					$this->lenders_accounts->id_client_owner = $this->clients->id_client;
					$this->lenders_accounts->id_company_owner = $this->companies->id_company;
					$this->lenders_accounts->create();
				}
				
				// On recup le client
				$this->clients->get($this->clients->id_client,'id_client');
				
				//cgu
				if(isset($_POST['accept-cgu-societe']) && $_POST['accept-cgu-societe'] != false)
				{
					// acceptation des cgu 
					if($this->acceptations_legal_docs->get($this->lienConditionsGeneralesSociete,'id_client = "'.$this->clients->id_client.'" AND id_legal_doc')) $accepet_ok = true;
					else $accepet_ok = false;
						
					$this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGeneralesSociete;
					$this->acceptations_legal_docs->id_client = $this->clients->id_client;
					
					if($accepet_ok == true)$this->acceptations_legal_docs->update();
					else $this->acceptations_legal_docs->create();
				}
				
				if($this->reponse_email != ''){
					
					$_SESSION['reponse_email'] = $this->reponse_email;
					header('location:'.$this->lurl.'/inscription_preteur/etape1/'.$this->clients->hash);
					die;
				}
				else
				{
					// Si c'es un nouvel utilisateur
					if($this->emprunteurCreatePreteur == false && $this->modif == false || $this->modif == true && $this->clients->etape_inscription_preteur == 0){
						// on recup les infos client 
						$this->clients->get($this->clients->id_client,'id_client');
						$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
						$this->companies->get($this->companies->id_company,'id_company');
						// email inscription preteur //
						
						// Motif virement
						$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
						$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
						$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
						$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
						
						
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
						'prenom' => $this->clients->prenom,
						'motif_virement' => $motif,
						'email_p' => $this->clients->email,
						'mdp' => $_POST['pass'],
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
						
						// parametres pour valider le compte //
	
						$this->clients->status_pre_emp =  1; // preteur
						$this->clients->status = 1; // online
						$this->clients->status_inscription_preteur = 1; // inscription terminé
						$this->clients->etape_inscription_preteur = 1; // etape 1 ok
						
						// type de preteur
						if($this->companies->id_pays != 1)
							$this->clients->type = 4; // morale etrangée
						else 
							$this->clients->type = 2; // morale
											
						$this->lenders_accounts->status = 1; // statut lender online
						
						// Enregistrement
						$this->clients->update();
						$this->lenders_accounts->update();
						
						///////////////////////////////
						
					}
					
					header('location:'.$this->lurl.'/inscription_preteur/etape2/'.$this->clients->hash);
					die;
					
				}
				
			}
			else{
				
				header('location:'.$this->lurl.'/inscription_preteur/etape1/'.$this->params[0]);
				die;
			}
		}
	}
	
	function _etape2()
	{

		// CSS
		$this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
		$this->loadCss('default/preteurs/new-style');
		
		// JS
		$this->unLoadJs('default/functions');
		$this->unLoadJs('default/main');
		$this->unLoadJs('default/ajax');
		
		$this->loadJs('default/preteurs/functions');
		$this->loadJs('default/main');
		$this->loadJs('default/ajax');
		
		// Chargement des datas
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_status_history = $this->loadData('clients_status_history');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_history_actions = $this->loadData('clients_history_actions');
		$this->attachment = $this->loadData('attachment');
		$this->attachment_type = $this->loadData('attachment_type');
		
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		
		$this->page_preteur = 2;
		
		// Liste deroulante origine des fonds
		$this->settings->get("Liste deroulante origine des fonds",'status = 1 AND type');
		$this->origine_fonds = $this->settings->value;
		$this->origine_fonds = explode(';',$this->origine_fonds);
		
		// Liste deroulante origine des fonds
		$this->settings->get("Liste deroulante origine des fonds societe",'status = 1 AND type');
		$this->origine_fonds_E = explode(';',$this->settings->value);
		
		
		/////////////////////////////
		// Initialisation variable //
		$this->emprunteurCreatePreteur = false;
		$this->preteurOnline = false;
		$this->hash_client = '';
		
		// Si on a une session active
		if(isset($_SESSION['client'])){ 
			// On recup le mec
			$this->clients->get($_SESSION['client']['id_client'],'id_client');
			
			// preteur ayant deja crée son compte
			if($this->clients->status_pre_emp == 1 && $this->clients->etape_inscription_preteur == 3){
				
				header('location:'.$this->lurl.'/inscription_preteur/etape1');
				die;
			}
			// preteur n'ayant pas terminé la création de son compte
			elseif($this->clients->status_pre_emp == 1 && $this->clients->etape_inscription_preteur < 3){
				$this->preteurOnline = true;	
			}
			// Emprunteur/preteur n'ayant pas terminé la création de son compte
			elseif($this->clients->status_pre_emp == 3 && $this->clients->etape_inscription_preteur < 3){
				$this->emprunteurCreatePreteur = true;
			}
		}
		//////////////////////////////////
		
		if($this->emprunteurCreatePreteur == true) $conditionOk = true;
		elseif($this->preteurOnline == true)$conditionOk = true;
		elseif(isset($this->params[0]) && $this->clients->get($this->params[0],'status = 1 AND etape_inscription_preteur < 3 AND hash')) $conditionOk = true;
		else $conditionOk = false;
		
		// On recupere le client
		if($conditionOk == true){
			
			// On recupere le lender account
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
			
			$this->clients_adresses->get($this->clients->id_client,'id_client');
			
			if($this->lenders_accounts->iban != '')
			{
				$this->iban1 = substr($this->lenders_accounts->iban,0,4);
				$this->iban2 = substr($this->lenders_accounts->iban,4,4);
				$this->iban3 = substr($this->lenders_accounts->iban,8,4);
				$this->iban4 = substr($this->lenders_accounts->iban,12,4);
				$this->iban5 = substr($this->lenders_accounts->iban,16,4);
				$this->iban6 = substr($this->lenders_accounts->iban,20,4);
				$this->iban7 = substr($this->lenders_accounts->iban,24,3);
			}
			else $this->iban1 = 'FR...';
			
			$this->hash_client = $this->clients->hash;
			
			$this->etranger = 0;
			// fr/resident etranger
			if($this->clients->id_nationalite <= 1 && $this->clients_adresses->id_pays_fiscal > 1){
				$this->etranger = 1;
			}
			// no fr/resident etranger
			elseif($this->clients->id_nationalite > 1 && $this->clients_adresses->id_pays_fiscal > 1){
				$this->etranger = 2;
			}

			// Particulier
			if(isset($_POST['send_form_inscription_preteur_particulier_etape_2'])) {

                $this->form_ok = true;
                $this->error_fichier = false;

                // fichier
                $fichier_document_fiscal = $fichier_cni_passeport = $fichier_justificatif_domicile = $fichier_rib = $fichier_autre = '';

				$iban = $this->lenders_accounts->iban;
				$bic = $this->lenders_accounts->bic;
				$origine_des_fonds = $this->lenders_accounts->origine_des_fonds;
				
				// si etrangé
				if($this->etranger > 0){
					// document_fiscal
                    $fichier_document_fiscal = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::JUSTIFICATIF_FISCAL);
                    $this->error_document_fiscal = false === $fichier_document_fiscal;
				}
				
				// carte-nationale-didentite
                $fichier_cni_passeport = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE);
                $this->error_cni = false === $fichier_cni_passeport;

				// justificatif-de-domicile
                $fichier_justificatif_domicile = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::JUSTIFICATIF_DOMICILE);
                $this->error_justificatif_domicile = false === $fichier_justificatif_domicile;

				// rib
                $fichier_rib = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB);
                $this->error_rib = false === $fichier_rib;
				
				// autre
                $fichier_autre = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::AUTRE1);
                $this->error_autre = false === $fichier_autre;
				
				$this->lenders_accounts->bic = trim(strtoupper($_POST['bic']));// Bic
				$this->lenders_accounts->iban = ''; // Iban
				for($i=1;$i<=7;$i++){ $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-'.$i]));}
				
				if($this->lenders_accounts->iban != '')
				{
					$this->iban1 = substr($this->lenders_accounts->iban,0,4);
					$this->iban2 = substr($this->lenders_accounts->iban,4,4);
					$this->iban3 = substr($this->lenders_accounts->iban,8,4);
					$this->iban4 = substr($this->lenders_accounts->iban,12,4);
					$this->iban5 = substr($this->lenders_accounts->iban,16,4);
					$this->iban6 = substr($this->lenders_accounts->iban,20,4);
					$this->iban7 = substr($this->lenders_accounts->iban,24,3);
				}
				
				// origine
				$this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
				if($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000)$this->lenders_accounts->precision = $_POST['preciser'];
				else $this->lenders_accounts->precision = '';
				
				$this->lenders_accounts->cni_passeport = 1; // ci
				
				// BIC
				if(!isset($_POST['bic']) || $_POST['bic'] == $this->lng['etape2']['bic'] || $_POST['bic'] == ''){
					$this->form_ok = false;
				}
				elseif(isset($_POST['bic']) && $this->ficelle->swift_validate(trim($_POST['bic'])) == false){
					$this->form_ok = false;
				}
				// IBAN
				if(strlen($this->lenders_accounts->iban) < 27){
					$this->form_ok = false;
				}
				elseif($this->lenders_accounts->iban != '' && $this->ficelle->isIBAN($this->lenders_accounts->iban) != 1){
					$this->form_ok = false;
				}
				// Origine des fonds
				if(!isset($_POST['origine_des_fonds']) || $_POST['origine_des_fonds'] == 0){
					$this->form_ok = false;
				}
				elseif($_POST['origine_des_fonds'] == 1000000 && in_array($_POST['preciser'],array($this->lng['etape2']['autre-preciser'],''))){
					$this->form_ok = false;
				}
				
				if($this->form_ok == true){
					
					$this->clients->etape_inscription_preteur = 2; // etape 2 ok
					
					// On met à jour en BDD
					$this->lenders_accounts->update();
					$this->clients->update();
					
					if($this->clients_status_history->counter('id_client = '.$this->clients->id_client.' AND id_client_status = 1') <= 0){
						// creation du statut "a contrôler"
						$this->clients_status_history->addStatus('-2','10',$this->clients->id_client);
						
						$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
						$this->clients_history_actions->histo(17,'inscription etape 2 particulier',$this->clients->id_client,$serialize);
						////////////////
						
						//********************************************//
						//*** ENVOI DU MAIL NOTIFICATION notification-nouveaux-preteurs ***//
						//********************************************//
						
						$this->settings->get('Adresse notification nouveau preteur','type');
						$destinataire = $this->settings->value;
						
						$lemois = $this->dates->tableauMois[$this->language][date('n')];
						
						// Recuperation du modele de mail
						$this->mails_text->get('notification-nouveaux-preteurs','lang = "'.$this->language.'" AND type');
						
						// Variables du mailing
						$surl = $this->surl;
						$url = $this->lurl;
						$id_preteur = $this->clients->id_client;
						$nom = utf8_decode($this->clients->nom);
						$prenom = utf8_decode($this->clients->prenom);
						//$montant = 'virement';
						$montant = '';
						$date = date('d').' '.$lemois.' '.date('Y');
						$heure_minute = date('h:m');
						$email = $this->clients->email;
						$lien = $this->aurl.'/preteurs/edit_preteur/'.$this->lenders_accounts->id_lender_account;
						
						// Attribution des données aux variables
						$sujetMail = htmlentities($this->mails_text->subject);
						eval("\$sujetMail = \"$sujetMail\";");
						
						$texteMail = $this->mails_text->content;
						eval("\$texteMail = \"$texteMail\";");
						
						$exp_name = $this->mails_text->exp_name;
						eval("\$exp_name = \"$exp_name\";");
						
						// Nettoyage de printemps
						$sujetMail = strtr($sujetMail,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
						$exp_name = strtr($exp_name,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
						
						// Envoi du mail
						$this->email = $this->loadLib('email',array());
						$this->email->setFrom($this->mails_text->exp_email,$exp_name);
						$this->email->addRecipient(trim($destinataire));
						//$this->email->addBCCRecipient('');
					
						$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
						$this->email->setHTMLBody($texteMail);
						Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
						// fin mail
						
						// email inscription preteur //
					
					}
					// si on a deja le compte et que c'est une modif
					else{
						// Histo client //
						$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
						$this->clients_history_actions->histo(18,'edition inscription etape 2 particulier',$this->clients->id_client,$serialize);
						////////////////
						
						if($fichier_cni_passeport != '' ||
						$fichier_justificatif_domicile != '' ||
						$fichier_rib != '' ||
						$this->etranger > 0 && $fichier_document_fiscal != '' ||
						$iban != $this->lenders_accounts->iban ||
						$bic != $this->lenders_accounts->bic ||
						$origine_des_fonds != $this->lenders_accounts->origine_des_fonds)
						{
							$contenu = '<ul>';
							if($origine_des_fonds != $this->lenders_accounts->origine_des_fonds) {
								$contenu .= '<li>Origine des fonds</li>';
                            }
							if($bic != $this->lenders_accounts->bic) {
								$contenu .= '<li>BIC</li>';
                            }
							if($iban != $this->lenders_accounts->iban)
								$contenu .= '<li>IBAN</li>';
							if($fichier_cni_passeport != '') {
								$contenu .= '<li>Fichier cni passeport</li>';
                            }
							if($fichier_justificatif_domicile != '') {
								$contenu .= '<li>Fichier justificatif domicile</li>';
                            }
							if($fichier_rib != '') {
								$contenu .= '<li>Fichier RIB</li>';
                            }
							if($this->etranger > 0 && $fichier_document_fiscal != '') {
								$contenu .= '<li>Fichier document fiscal</li>';
                            }
							$contenu .= '</ul>';
							
							$this->clients_status->getLastStatut($this->clients->id_client);
							if($this->clients_status->status == 10) $statut_client = 10;
							if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
							else $statut_client = 50;
						
							// creation du statut "Modification"
							$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
							
							// destinataire
							$this->settings->get('Adresse notification modification preteur','type');
							$destinataire = $this->settings->value;
							//$destinataire = 'courtier.damien@gmail.com';
							
							$lemois = utf8_decode($this->dates->tableauMois[$this->language][date('n')]);
							
							// Recuperation du modele de mail
							$this->mails_text->get('notification-modification-preteurs','lang = "'.$this->language.'" AND type');
							
							// Variables du mailing
							$surl = $this->surl;
							$url = $this->lurl;
							$id_preteur = $this->clients->id_client;
							$nom = utf8_decode($this->clients->nom);
							$prenom = utf8_decode($this->clients->prenom);
							$montant = $this->solde.' euros';
							$date = date('d').' '.$lemois.' '.date('Y');
							$heure_minute = date('H:m');
							$email = $this->clients->email;
							$lien = $this->aurl.'/preteurs/edit_preteur/'.$this->lenders_accounts->id_lender_account;
							
							// Attribution des données aux variables
							$sujetMail = htmlentities($this->mails_text->subject);
							eval("\$sujetMail = \"$sujetMail\";");
							
							$texteMail = $this->mails_text->content;
							eval("\$texteMail = \"$texteMail\";");
							
							$exp_name = $this->mails_text->exp_name;
							eval("\$exp_name = \"$exp_name\";");
							
							// Nettoyage de printemps
							$sujetMail = strtr($sujetMail,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
							$exp_name = strtr($exp_name,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
							
							// Envoi du mail
							$this->email = $this->loadLib('email',array());
							$this->email->setFrom($this->mails_text->exp_email,$exp_name);
							$this->email->addRecipient(trim($destinataire));
							$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
							$this->email->setHTMLBody($texteMail);
							Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
							// fin mail
						
						}
						
					}
					
					header('location:'.$this->lurl.'/inscription_preteur/etape3/'.$this->clients->hash);
					die;
				}
			}
			// SOCIETE
			elseif(isset($_POST['send_form_inscription_preteur_societe_etape_2']))
			{
                $this->form_ok = true;
                $this->error_fichier = false;

				// carte-nationale-didentite dirigeant
                $this->error_cni_dirigent = false === $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_DIRIGEANT);

				// Extrait Kbis
                $this->error_extrait_kbis = false === $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::KBIS);

				// rib
                $this->error_rib = false === $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB);
				
				// autre
                $this->error_autre = false === $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::AUTRE1);

                $this->error_delegation_pouvoir = false === $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::DELEGATION_POUVOIR);
				
				$this->lenders_accounts->bic = trim(strtoupper($_POST['bic']));// Bic
				$this->lenders_accounts->iban = ''; // Iban
				for($i=1;$i<=7;$i++){ $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-'.$i]));}
				
				if($this->lenders_accounts->iban != '')
				{
					$this->iban1 = substr($this->lenders_accounts->iban,0,4);
					$this->iban2 = substr($this->lenders_accounts->iban,4,4);
					$this->iban3 = substr($this->lenders_accounts->iban,8,4);
					$this->iban4 = substr($this->lenders_accounts->iban,12,4);
					$this->iban5 = substr($this->lenders_accounts->iban,16,4);
					$this->iban6 = substr($this->lenders_accounts->iban,20,4);
					$this->iban7 = substr($this->lenders_accounts->iban,24,3);
				}
				
				// origine
				$this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
				if($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000)$this->lenders_accounts->precision = $_POST['preciser'];
				else $this->lenders_accounts->precision = '';
				
				$this->lenders_accounts->cni_passeport = 1; // ci

				// BIC
				if(!isset($_POST['bic']) || $_POST['bic'] == $this->lng['etape2']['bic'] || $_POST['bic'] == ''){
					$this->form_ok = false;
				}
				elseif(isset($_POST['bic']) && $this->ficelle->swift_validate(trim($_POST['bic'])) == false){
					$this->form_ok = false;
				}
				// IBAN
				if(strlen($this->lenders_accounts->iban) < 27){
					$this->form_ok = false;
				}
				elseif($this->lenders_accounts->iban != '' && $this->ficelle->isIBAN($this->lenders_accounts->iban) != 1){
					$this->form_ok = false;
				}
				
				// Origine des fonds
				if(!isset($_POST['origine_des_fonds']) || $_POST['origine_des_fonds'] == 0){
					$this->form_ok = false;
				}
				elseif($_POST['origine_des_fonds'] == 1000000 && in_array($_POST['preciser'],array($this->lng['etape2']['autre-preciser'],''))){
					$this->form_ok = false;
				}
				
				if($this->form_ok == true){
					
					// Histo client //
					$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
					$this->clients_history_actions->histo(19,'inscription etape 2 entreprise',$this->clients->id_client,$serialize);
					////////////////
					
					
					$this->clients->etape_inscription_preteur = 2; // etape 2 ok
					
					// On met à jour en BDD
					$this->lenders_accounts->update();
					$this->clients->update();
					
					if($this->clients_status_history->counter('id_client = '.$this->clients->id_client.' AND id_client_status = 1') <= 0){
						// creation du statut "a contrôler"
						$this->clients_status_history->addStatus('-2','10',$this->clients->id_client);
					
						//********************************************//
						//*** ENVOI DU MAIL NOTIFICATION notification-nouveaux-preteurs ***//
						//********************************************//
						
						$this->settings->get('Adresse notification nouveau preteur','type');
						$destinataire = $this->settings->value;
						
						$lemois = utf8_decode($this->dates->tableauMois[$this->language][date('n')]);
						
						// Recuperation du modele de mail
						$this->mails_text->get('notification-nouveaux-preteurs','lang = "'.$this->language.'" AND type');
						
						// Variables du mailing
						$surl = $this->surl;
						$url = $this->lurl;
						$id_preteur = $this->clients->id_client;
						$nom = utf8_decode($this->clients->nom);
						$prenom = utf8_decode($this->clients->prenom);
						//$montant = 'virement';
						$montant = '';
						$date = date('d').' '.$lemois.' '.date('Y');
						$heure_minute = date('h:m');
						$email = $this->clients->email;
						$lien = $this->aurl.'/preteurs/edit_preteur/'.$this->lenders_accounts->id_lender_account;
						
						// Attribution des données aux variables
						$sujetMail = htmlentities($this->mails_text->subject);
						eval("\$sujetMail = \"$sujetMail\";");
						
						$texteMail = $this->mails_text->content;
						eval("\$texteMail = \"$texteMail\";");
						
						$exp_name = $this->mails_text->exp_name;
						eval("\$exp_name = \"$exp_name\";");
						
						// Nettoyage de printemps
						$sujetMail = strtr($sujetMail,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
						$exp_name = strtr($exp_name,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
						
						// Envoi du mail
						$this->email = $this->loadLib('email',array());
						$this->email->setFrom($this->mails_text->exp_email,$exp_name);
						$this->email->addRecipient(trim($destinataire));
						//$this->email->addBCCRecipient('');
					
						$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
						$this->email->setHTMLBody($texteMail);
						Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
						// fin mail
						
						// email inscription preteur //
					
					}
					
					header('location:'.$this->lurl.'/inscription_preteur/etape3/'.$this->clients->hash);
					die;
				}
			}
		}
		else
		{
			header('location:'.$this->lurl.'/inscription_preteur/etape1/');
			die;
		}
	}
	
	function _etape3()
	{
		// CSS
		$this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
		$this->loadCss('default/preteurs/new-style');
		$this->loadCss('default/preteurs/print');
		
		// JS
		$this->unLoadJs('default/functions');
		$this->unLoadJs('default/main');
		$this->unLoadJs('default/ajax');
		
		$this->loadJs('default/preteurs/functions');
		$this->loadJs('default/main');
		$this->loadJs('default/ajax');
		
		$this->page_preteur = 3;
		
		//Recuperation des element de traductions
		$this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3',$this->language,$this->App);
		
		// On recup la lib et le reste payline
		require_once($this->path.'protected/payline/include.php');
		
		// Chargement des datas
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->transactions = $this->loadData('transactions');
		$this->backpayline = $this->loadData('backpayline');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		$this->settings->get('Virement - aide par banque','type');
		$this->aide_par_banque = $this->settings->value;
		
		$this->settings->get('Virement - IBAN','type');
		$iban = strtoupper($this->settings->value);
		
		$this->settings->get('Virement - BIC','type');
		$this->bic = strtoupper($this->settings->value);
		
		$this->settings->get('Virement - domiciliation','type');
		$this->domiciliation = $this->settings->value;
		
		$this->settings->get('Virement - titulaire du compte','type');
		$this->titulaire = $this->settings->value;
		
		/////////////////////////////
		// Initialisation variable //
		$this->emprunteurCreatePreteur = false;
		$this->preteurOnline = false;
		$this->hash_client = '';
		
		// Si on a une session active
		if(isset($_SESSION['client'])){ 
			// On recup le mec
			$this->clients->get($_SESSION['client']['id_client'],'id_client');
			
			// preteur ayant deja crée son compte
			if($this->clients->status_pre_emp == 1 && $this->clients->etape_inscription_preteur == 3){
				header('location:'.$this->lurl.'/inscription_preteur/etape1');
				die;
			}
			// preteur n'ayant pas terminé la création de son compte
			elseif($this->clients->status_pre_emp == 1 && $this->clients->etape_inscription_preteur < 3){
				$this->preteurOnline = true;	
			}
			// Emprunteur/preteur n'ayant pas terminé la création de son compte
			elseif($this->clients->status_pre_emp == 3 && $this->clients->etape_inscription_preteur < 3){
				$this->emprunteurCreatePreteur = true;
			}
			
			// Utilisateur est un emprunteur/preteur ou preteur ayant deja finalisé son inscription
			
		}
		//////////////////////////////////
		
		if($this->emprunteurCreatePreteur == true) $conditionOk = true;
		elseif($this->preteurOnline == true)$conditionOk = true;
		elseif(isset($this->params[0]) && $this->clients->get($this->params[0],'status = 1 AND etape_inscription_preteur < 3 AND hash')) $conditionOk = true;
		else $conditionOk = false;
		
		// On recupere le client
		if($conditionOk == true){
			$this->clients_adresses->get($this->clients->id_client,'id_client');
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
			
			$this->hash_client = $this->clients->hash;
			
			// Motif virement
			$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
			$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
			$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
			$this->motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
			
			$_SESSION['motif'] = $this->motif;
			
			if($iban != '')
			{	
				$this->iban1 = substr($iban,0,4);
				$this->iban2 = substr($iban,4,4);
				$this->iban3 = substr($iban,8,4);
				$this->iban4 = substr($iban,12,4);
				$this->iban5 = substr($iban,16,4);
				$this->iban6 = substr($iban,20,4);
				$this->iban7 = substr($iban,24,3);
				
				$this->etablissement = substr($iban,4,5);
				$this->guichet = substr($iban,9,5);
				$this->compte = substr($iban,14,11);
				$this->cle = substr($iban,25,2);
			}
			
			// paiement CB
			if(isset($_POST['send_form_preteur_cb'])){

				$amount = str_replace(array(',',' '),array('.',''),$_POST['amount']);
				
				if(is_numeric($amount) && $amount >= 20 && $amount <=10000){
					$amount = (number_format($amount,2,'.','')*100);
					$this->lenders_accounts->fonds = $amount;
					$this->lenders_accounts->motif = $this->motif;
					$this->lenders_accounts->type_transfert = 2; // cb
					$this->lenders_accounts->update();
					
					$this->transactions->id_client = $this->clients->id_client;
					$this->transactions->montant = $amount;
					$this->transactions->id_langue = 'fr';
					$this->transactions->date_transaction = date('Y-m-d h:i:s');
					$this->transactions->status = '0';
					$this->transactions->etat = '0';
					$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
					$this->transactions->civilite_fac = $this->clients->civilite;
					$this->transactions->nom_fac = $this->clients->nom;
					$this->transactions->prenom_fac = $this->clients->prenom;
					if($this->clients->type == 2)$this->transactions->societe_fac = $this->companies->name;
					$this->transactions->adresse1_fac = $this->clients_adresses->adresse1;
					$this->transactions->cp_fac = $this->clients_adresses->cp;
					$this->transactions->ville_fac = $this->clients_adresses->ville;
					$this->transactions->id_pays_fac = $this->clients_adresses->id_pays;
					$this->transactions->type_transaction = 1; // on signal que c'est un solde pour l'inscription
					$this->transactions->transaction = 1; // transaction physique
					$this->transactions->id_transaction = $this->transactions->create();
					
					//***************//
					//*** PAYLINE ***//
					//***************//
					
					$array = array();
					$payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
					$payline->returnURL = $this->lurl.'/inscription_preteur/payment/'.$this->clients->hash.'/';
					$payline->cancelURL = $this->lurl.'/inscription_preteur/payment/'.$this->clients->hash.'/';
					$payline->notificationURL = NOTIFICATION_URL;
					
					//$payline->customPaymentTemplateURL = 'http://unilend.dev2.equinoa.net/template.html';
					//$array['customPaymentTemplateURL'] = CUSTOM_PAYMENT_TEMPLATE_URL;
					//mail('d.courtier@equinoa.com','test payline',$array['customPaymentTemplateURL']);
					
					// PAYMENT
					$array['payment']['amount'] = $amount;
					$array['payment']['currency'] = ORDER_CURRENCY;
					$array['payment']['action'] = PAYMENT_ACTION;
					$array['payment']['mode'] = PAYMENT_MODE;
					
					// ORDER
					$array['order']['ref'] = $this->transactions->id_transaction;
					$array['order']['amount'] = $amount;
					$array['order']['currency'] = ORDER_CURRENCY;
					
					// CONTRACT NUMBERS
					$array['payment']['contractNumber'] = CONTRACT_NUMBER;
					$contracts = explode(";",CONTRACT_NUMBER_LIST);
					$array['contracts'] = $contracts;
					$secondContracts = explode(";",SECOND_CONTRACT_NUMBER_LIST);
					$array['secondContracts'] = $secondContracts;
					
					// EXECUTE
					$result = $payline->doWebPayment($array);
					
					// On enregistre le tableau retourné
					$this->transactions->get($this->transactions->id_transaction,'id_transaction');
					$this->transactions->serialize_payline = serialize($result);
					$this->transactions->update();
					
					// si on retourne quelque chose
					if(isset($result)){
						if($result['result']['code'] == '00000'){
							header("location:".$result['redirectURL']);
							exit();
						}
						// Si erreur on envoie sur mon mail
						elseif(isset($result)) {
							//mail('d.courtier@equinoa.com','unilend erreur payline','Inscription preteur etape 3 | ERROR : '.$result['result']['code']. ' '.$result['result']['longMessage']);
							header('location:'.$this->lurl.'/inscription_preteur/erreur/'.$this->clients->hash);
							die;
						}
					}
				}
				
			}
			// Virement
			elseif(isset($_POST['send_form_preteur_virement'])){
				
				$this->clients->etape_inscription_preteur = 3; // etape 3 ok
				
				// type de versement virement
				$this->lenders_accounts->fonds = 0;
				$this->lenders_accounts->motif = $this->motif;
				$this->lenders_accounts->type_transfert = 1;
				// on enregistre les infos
				$this->lenders_accounts->update();	
				
				// Enregistrement
				$this->clients->update();
					
				//******************************************************//
				//*** ENVOI DU MAIL CONFIRMATION INSCRIPTION PRETEUR ***//
				//******************************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('confirmation-inscription-preteur-etape-3','lang = "'.$this->language.'" AND type');
				
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
				'prenom' => $this->clients->prenom,
				'email_p' => $this->clients->email,
				'mdp' => $_POST['pass'],
				'motif_virement' => $this->motif,
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
				
				header('location:'.$this->lurl.'/inscription_preteur/confirmation/'.$this->clients->hash.'/v/');
				die;
			}
			
		}
		else{
			header('location:'.$this->lurl.'/inscription_preteur/etape1');
        	die;	
		}
		
	}
	
	function _payment()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireView = false;
		$this->autoFireFooter = false;
		
		// On recup la lib et le reste payline
		require_once($this->path.'protected/payline/include.php');
				
		// Chargement des datas
		$this->transactions = $this->loadData('transactions');
		$this->backpayline = $this->loadData('backpayline');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->bank_lines = $this->loadData('bank_lines');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		// Prêteur n'ayant pas terminé son inscription
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'hash') && $this->clients->etape_inscription_preteur < 3) $conditionOk = true;
		else $conditionOk = false;
		// On recupere le client
		if($conditionOk == true){
			
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
			
			$array = array();
			$payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
			
			// GET TOKEN
			if(isset($_POST['token'])){
				$array['token'] = $_POST['token'];
			}
			elseif(isset($_GET['token'])){
				$array['token'] = $_GET['token'];
			}
			else{
				header('location:'.$this->lurl.'/inscription_preteur/etape3/'.$this->clients->hash);
				die;
			}
			 
			// VERSION
			if(isset($_POST['version'])){
				$array['version'] = $_POST['version'];
			}else{
				$array['version'] = '3';
			}
			 
			// RESPONSE FORMAT
			$response = $payline->getWebPaymentDetails($array);
			if(isset($response)){
				//print_r($response);
				
				// On enregistre le resultat payline
				$this->backpayline->code = $response['result']['code'];
				$this->backpayline->token = $array['token'];
				$this->backpayline->id = $response['transaction']['id'];
				$this->backpayline->date = $response['transaction']['date'];
				$this->backpayline->amount = $response['payment']['amount'];
				$this->backpayline->serialize = serialize($response);
				$this->backpayline->id_backpayline = $this->backpayline->create();
				
				// Paiement approuvé
				if($response['result']['code'] == '00000'){
					if($this->transactions->get($response['order']['ref'],'status = 0 AND etat = 0 AND id_transaction')){
						$this->transactions->id_backpayline = $this->backpayline->id_backpayline;
						$this->transactions->montant = $response['payment']['amount'];
						$this->transactions->id_langue = 'fr';
						$this->transactions->date_transaction = date('Y-m-d h:i:s');
						$this->transactions->status = '1';
						$this->transactions->etat = '1';
						$this->transactions->type_paiement = ($response['extendedCard']['type'] == 'VISA'?'0':($response['extendedCard']['type'] == 'MASTERCARD'?'3':''));
						$this->transactions->update();
						
						// On enrgistre la transaction dans le wallet
						$this->wallets_lines->id_lender = $this->lenders_accounts->id_lender_account;
						$this->wallets_lines->type_financial_operation = 10; // Inscription preteur
						$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
						$this->wallets_lines->status = 1;
						$this->wallets_lines->type = 1;
						$this->wallets_lines->amount = $response['payment']['amount'];
						$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
						
						// Transaction physique donc on enregistre aussi dans la bank lines
						$this->bank_lines->id_wallet_line = $this->wallets_lines->id_wallet_line;
						$this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account;
						$this->bank_lines->status = 1;
						$this->bank_lines->amount = $response['payment']['amount'];
						$this->bank_lines->create();
						
						// Historique client
						$this->clients_history->id_client = $this->clients->id_client;
						$this->clients_history->type = $this->clients->status_pre_emp;
						$this->clients_history->status = 2; // statut creation compte preteur
						$this->clients_history->create();
						
						//********************************************//
						//*** ENVOI DU MAIL NOTIFICATION notification-nouveaux-preteurs ***//
						//********************************************//
						
						//$this->settings->get('Adresse notification nouveau preteur','type');
//						$destinataire = $this->settings->value;
//						
//						$lemois = $this->dates->tableauMois[$this->language][date('n')];
//						
//						// Recuperation du modele de mail
//						$this->mails_text->get('notification-nouveaux-preteurs','lang = "'.$this->language.'" AND type');
//						
//						// Variables du mailing
//						$surl = $this->surl;
//						$url = $this->lurl;
//						$id_preteur = $this->clients->id_client;
//						$nom = $this->clients->nom;
//						$prenom = $this->clients->prenom;
//						$montant = ($response['payment']['amount']/100).'&euro;';
//						$date = date('d').' '.$lemois.' '.date('Y');
//						$heure_minute = date('H:m');
//						$email = $this->clients->email;
//						$lien = $this->aurl.'/preteurs/edit_preteur/'.$this->lenders_accounts->id_lender_account;
//						
//						// Attribution des données aux variables
//						$sujetMail = htmlentities($this->mails_text->subject);
//						eval("\$sujetMail = \"$sujetMail\";");
//						
//						$texteMail = $this->mails_text->content;
//						eval("\$texteMail = \"$texteMail\";");
//						
//						$exp_name = $this->mails_text->exp_name;
//						eval("\$exp_name = \"$exp_name\";");
//						
//						// Nettoyage de printemps
//						$sujetMail = strtr($sujetMail,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
//						$exp_name = strtr($exp_name,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
//						
//						// Envoi du mail
//						$this->email = $this->loadLib('email',array());
//						$this->email->setFrom($this->mails_text->exp_email,$exp_name);
//						$this->email->addRecipient(trim($destinataire));
//						//$this->email->addBCCRecipient('');
//					
//						$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
//						$this->email->setHTMLBody($texteMail);
//						Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
						// fin mail
						
						
						
						//********************************************//
						//*** ENVOI DU MAIL NOTIFICATION VERSEMENT ***//
						//********************************************//
						
						$this->settings->get('Adresse notification nouveau versement preteur','type');
						$destinataire = $this->settings->value;
						
						// Recuperation du modele de mail
						$this->mails_text->get('notification-nouveau-versement-dun-preteur','lang = "'.$this->language.'" AND type');
						
						// Variables du mailing
						$surl = $this->surl;
						$url = $this->lurl;
						$id_preteur = $this->clients->id_client;
						$nom = utf8_decode($this->clients->nom);
						$prenom = utf8_decode($this->clients->prenom);
						$montant = ($response['payment']['amount']/100);
						
						// Attribution des données aux variables
						$sujetMail = htmlentities($this->mails_text->subject);
						eval("\$sujetMail = \"$sujetMail\";");
						
						$texteMail = $this->mails_text->content;
						eval("\$texteMail = \"$texteMail\";");
						
						$exp_name = $this->mails_text->exp_name;
						eval("\$exp_name = \"$exp_name\";");
						
						// Nettoyage de printemps
						$sujetMail = strtr($sujetMail,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
						$exp_name = strtr($exp_name,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
						
						// Envoi du mail
						$this->email = $this->loadLib('email',array());
						$this->email->setFrom($this->mails_text->exp_email,$exp_name);
						$this->email->addRecipient(trim($destinataire));
					
						$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
						$this->email->setHTMLBody($texteMail);
						Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
						// fin mail
							
						// email inscription preteur //
					
						//******************************************************//
						//*** ENVOI DU MAIL CONFIRMATION INSCRIPTION PRETEUR ***//
						//******************************************************//
			
						// Recuperation du modele de mail
						$this->mails_text->get('confirmation-inscription-preteur-etape-3','lang = "'.$this->language.'" AND type');
						
						
						// Motif virement
						$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
						$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
						$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
						$this->motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
						
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
						'prenom' => $this->clients->prenom,
						'email_p' => $this->clients->email,
						'mdp' => $_POST['pass'],
						'motif_virement' => $this->motif,
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
						
						/// email inscription preteur ///
						
						$this->clients->etape_inscription_preteur = 3; // etape 3 ok
						
						// creation du statut "a contrôler"
						//$this->clients_status_history->addStatus('-2','10',$this->clients->id_client);
						
						// Enregistrement
						$this->clients->update();
						
						// connection au compte
						// mise en session
						$client = $this->clients->select('id_client = '.$this->clients->id_client);
						$_SESSION['auth'] = true;
						$_SESSION['token'] = md5(md5(mktime().$this->clients->securityKey));
						$_SESSION['client'] = $client[0];
						// fin mise en session
						
						
						header('location:'.$this->lurl.'/inscription_preteur/confirmation/'.$this->clients->hash.'/cb/'.$this->transactions->id_transaction);
						die;
					}
					else // si infos pas good
					{
						header('location:'.$this->lurl.'/inscription_preteur/etape3/'.$this->clients->hash);
						die;
					}
				}
				
				// Paiement annulé
				elseif($response['result']['code'] == '02319')
				{
					$this->transactions->get($response['order']['ref'],'id_transaction');
					$this->transactions->id_backpayline = $this->backpayline->id_backpayline;
					$this->transactions->statut = '0';
					$this->transactions->etat = '3';
					$this->transactions->update();
					
					header('location:'.$this->lurl.'/inscription_preteur/etape3/'.$this->clients->hash);
					die;
				}
				
				// Si erreur
				else
				{
					mail('d.courtier@equinoa.com','unilend payline erreur','erreur sur page payment inscription preteur id_preteur :'.$this->clients->id_client.' Reponse : '.serialize($response));
					
					header('location:'.$this->lurl.'/inscription_preteur/erreur/'.$this->clients->hash);
        			die;
				}
			}
		}
	}
	
	function _confirmation()
	{	
	
		//////////////////////////////////
		
		// Initialisation variable
		$this->emprunteurCreatePreteur = false;
		
		// Si on a une session active
		/*if(isset($_SESSION['client']))
		{
			 
			// On recup le mec
			$this->clients->get($_SESSION['client']['id_client'],'id_client');
			
			// Si c'est un preteur on interdit de se créer un deuxieme compte
			if($this->clients->status_pre_emp == 1)
			{
				header('location:'.$this->lurl.'/inscription_preteur/etape1');
				die;
				
			}
			// Si c'est un emprunteur/preteur
			elseif($this->clients->status_pre_emp == 3) // on a deja passé le satut du compte en 3
			{
				$this->emprunteurCreatePreteur = true;
				
				$this->clients->type = 2;
			}
			else
			{
				// Si emprunteur pas le droit de créer un autre compte en etant connecté
				header('location:'.$this->lurl.'/inscription_preteur/etape1');
				die;
			}
		}
		//////////////////////////////////
		
		if($this->emprunteurCreatePreteur == true)
		{
			$conditionOk = true;
		}
		elseif(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))
		{
			$conditionOk = true;
		}
		else
		{
			$conditionOk = false;
		}*/
	
		// On recupere le client
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))
		{
			$this->page_preteur = 3;
			
			if(isset($this->params[1]) && $this->params[1] == 'v')
			{
				header('location:'.$this->lurl.'/'.$this->tree->getSlug(16,$this->language).'/'.$this->params[0]);
			}
			elseif(isset($this->params[1]) && $this->params[1] == 'cb')
			{
				// on rajoute l'id transaction en params 2
				header('location:'.$this->lurl.'/'.$this->tree->getSlug(130,$this->language).'/'.$this->params[0].'/'.$this->params[2].'/');
			}
			else
			{
				header('location:'.$this->lurl.'/inscription_preteur/etape1/');
				die;
			}
		}
		else
		{
			header('location:'.$this->lurl.'/inscription_preteur/etape1/');
			die;	
		}
	}
	
	function _template()
	{
		
	}
	
	function _erreur()
	{
		
		//////////////////////////////////
		// Initialisation variable
		$this->emprunteurCreatePreteur = false;
		
		// Si on a une session active
		if(isset($_SESSION['client']))
		{
			 
			// On recup le mec
			$this->clients->get($_SESSION['client']['id_client'],'id_client');
			
			// Si c'est un preteur on interdit de se créer un deuxieme compte
			if($this->clients->status_pre_emp == 1)
			{
				header('location:'.$this->lurl.'/inscription_preteur/etape1');
				die;
				
			}
			// Si c'est un emprunteur
			elseif($this->clients->status_pre_emp == 3) // deja statut changé en 3 car validation de linscription a la fin de letape 2
			{
				$this->emprunteurCreatePreteur = true;
				
				$this->clients->type = 2;
			}
			else
			{
				// Si emprunteur pas le droit de créer un autre compte en etant connecté
				header('location:'.$this->lurl.'/inscription_preteur/etape1');
				die;
			}
		}
		//////////////////////////////////
		
		if($this->emprunteurCreatePreteur == true)
		{
			$conditionOk = true;
		}
		elseif(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))
		{
			$conditionOk = true;
		}
		else
		{
			$conditionOk = false;
		}
		
		// On recupere le client
		if($conditionOk == true)
		{
			// pour menu etapes
			$this->page_preteur = 3;
			
			//Recuperation des element de traductions
			$this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3',$this->language,$this->App);
			
		}
		else
		{
			header('location:'.$this->lurl.'/inscription_preteur/etape1/');
			die;	
		}
	}
	
	function _contact_form()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		//Recuperation des element de traductions
		$this->lng['contact'] = $this->ln->selectFront('contact',$this->language,$this->App);
		
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'hash')){
			
		}
	}
	
	function _particulier_etape_1()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _societe_etape_1()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _particulier_etape_2()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _societe_etape_2()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	
	function _print()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// CSS
		$this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
		//$this->unLoadCss('default/izicom');
		$this->unLoadCss('default/colorbox');
		$this->unLoadCss('default/jquery.c2selectbox');
		//$this->unLoadCss('default/style');
		//$this->unLoadCss('default/style-edit');
		
		
		
		$this->loadCss('default/preteurs/new-style');
		$this->loadCss('default/preteurs/print');
		
		// JS
		$this->unLoadJs('default/functions');
		$this->unLoadJs('default/bootstrap-tooltip');
		$this->unLoadJs('default/jquery.carouFredSel-6.2.1-packed');
		$this->unLoadJs('default/jquery.c2selectbox');
		$this->unLoadJs('default/livevalidation_standalone.compressed');
		$this->unLoadJs('default/jquery.colorbox-min');
		$this->unLoadJs('default/jquery-ui-1.10.3.custom.min');
		$this->unLoadJs('default/jquery-ui-1.10.3.custom2');
		$this->unLoadJs('default/ui.datepicker-fr');
		$this->unLoadJs('default/highcharts.src');
		
		$this->unLoadJs('default/main');
		$this->unLoadJs('default/ajax');
		
		
		
		$this->page_preteur = 3;
		
		//Recuperation des element de traductions
		$this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3',$this->language,$this->App);
		
		$this->settings->get('Virement - aide par banque','type');
		$this->aide_par_banque = $this->settings->value;
		
		$this->settings->get('Virement - IBAN','type');
		$this->iban = strtoupper($this->settings->value);
		
		$this->settings->get('Virement - BIC','type');
		$this->bic = strtoupper($this->settings->value);
		
		$this->settings->get('Virement - domiciliation','type');
		$this->domiciliation = $this->settings->value;
		
		$this->settings->get('Virement - titulaire du compte','type');
		$this->titulaire = $this->settings->value;
		
		$this->motif = $_SESSION['motif'];
	}

	private function uploadAttachment($lenderAccountId, $attachmentType)
	{
		if(false === isset($this->attachmentHelper) || false === $this->attachmentHelper instanceof attachment_helper) {
			$this->attachmentHelper = $this->loadLib('attachment_helper');
		}

		if(false === isset($this->upload) || false === $this->upload instanceof upload) {
			$this->upload = $this->loadLib('upload');
		}

		if(false === isset($this->attachment) || false === $this->attachment instanceof attachment) {
			$this->attachment = $this->loadData('attachment');
		}

		$basePath = 'protected/lenders/';

		switch($attachmentType) {
			case attachment_type::CNI_PASSPORTE :
				$field = 'ci';
				$uploadPath = $basePath.'cni_passeport/';
				break;
			case attachment_type::JUSTIFICATIF_FISCAL :
				$field = 'document_fiscal';
				$uploadPath = $basePath.'document_fiscal/';
				break;
			case attachment_type::JUSTIFICATIF_DOMICILE :
				$field = 'justificatif_de_domicile';
				$uploadPath = $basePath.'justificatif_domicile/';
				break;
			case attachment_type::RIB :
				$field = 'rib';
				$uploadPath = $basePath.'rib/';
				break;
			case attachment_type::AUTRE1 :
				$field = 'autre';
				$uploadPath = $basePath.'autre/';
				break;
			case attachment_type::CNI_PASSPORTE_DIRIGEANT :
				$field = 'ci_dirigeant';
				$uploadPath = $basePath.'cni_passeport_dirigent/';
				break;
			case attachment_type::KBIS :
				$field = 'kbis';
				$uploadPath = $basePath.'extrait_kbis/';
				break;
			case attachment_type::DELEGATION_POUVOIR :
				$field = 'delegation_pouvoir';
				$uploadPath = $basePath.'delegation_pouvoir/';
				break;
			default :
				return false;
		}

		$resultUpload = $this->attachmentHelper->upload($lenderAccountId, attachment::LENDER, $attachmentType, $field, $this->path, $uploadPath, $this->upload, $this->attachment);

		if(false === $resultUpload) {
			$this->form_ok = false;
			$this->error_fichier = true;
		}

		return $resultUpload;
	}
}