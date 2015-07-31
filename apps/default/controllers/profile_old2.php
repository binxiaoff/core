<?php

class profileController extends bootstrap
{
	var $Command;
	
	function profileController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// On prend le header account
		$this->setHeader('header_account');
		
		// On check si y a un compte
		if(!$this->clients->checkAccess()){
			header('Location:'.$this->lurl);
			die;
		}
		else{
			// check preteur ou emprunteur (ou les deux)
			$this->clients->checkStatusPreEmp($this->clients->status_pre_emp,'preteur',$this->clients->id_client);
		}
		
		//Recuperation des element de traductions
		$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
		
		// Heure fin periode funding
		$this->settings->get('Heure fin periode funding','type');
		$this->heureFinFunding = $this->settings->value;
	
		$this->page = 'profile';
	}
	
	
	function _default()
	{
		// Particulier
		if(in_array($this->clients->type,array(1,3))){
			header('Location:'.$this->lurl.'/profile/particulier');
			die;
		}
		// Societe
		elseif(in_array($this->clients->type,array(2,4))){
			header('Location:'.$this->lurl.'/profile/societe');
			die;
		}		
	}
	
	
	
	function _particulier_old()
	{
		
		// Societe (si on est pas sur la bonne page)
		if(in_array($this->clients->type,array(2,4))){
			header('Location:'.$this->lurl.'/profile/societe');
			die;
		}
		
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
	
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
		//$this->pays = $this->loadData('pays');
		$this->pays = $this->loadData('pays_v2');
		//$this->nationalites = $this->loadData('nationalites');
		$this->nationalites = $this->loadData('nationalites_v2');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		// recuperation info lender
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// Liste des pays
		$this->lPays = $this->pays->select('','ordre ASC');
		//echo count($this->lPays);
		// liste des nationalites
		$this->lNatio = $this->nationalites->select('','ordre ASC');
		
		// Naissance 
		$nais = explode('-',$this->clients->naissance);	
		$this->jour = $nais[2];
		$this->mois = $nais[1];
		$this->annee = $nais[0];
		
		// On garde de coté l'adresse mail du preteur
		$this->email = $this->clients->email;
		
		// Liste deroulante origine des fonds
		$this->settings->get("Liste deroulante origine des fonds",'type');
		$this->origine_fonds = explode(';',$this->settings->value);
			
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
		
		$this->etranger = 0;
		
		// fr/resident etranger
		if($this->clients->id_nationalite <= 1 && $this->clients_adresses->id_pays_fiscal > 1){
			$this->etranger = 1;
		}
		// no fr/resident etranger
		elseif($this->clients->id_nationalite > 1 && $this->clients_adresses->id_pays_fiscal > 1){
			$this->etranger = 2;
		}
		
		// formulaire particulier perso
		if(isset($_POST['send_form_particulier_perso'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(4,'info perso profile',$this->clients->id_client,$serialize);
			////////////////
			
			
			// fr/resident etranger
			if($_POST['nationalite'] == 1 && $_POST['pays1'] > 1){
				$this->etranger = 1;
			}
			// no fr/resident etranger
			elseif($_POST['nationalite'] != 1 && $_POST['pays1'] > 1){
				$this->etranger = 2;
			}
			else{
				$this->etranger = 0;
			}
			
			// on recup la valeur deja existante //
			
			
			// adresse fiscal
			$adresse_fiscal = $this->clients_adresses->adresse_fiscal;
			$ville_fiscal = $this->clients_adresses->ville_fiscal;
			$cp_fiscal = $this->clients_adresses->cp_fiscal;
			$id_pays_fiscal = $this->clients_adresses->id_pays_fiscal;	
			
			// adresse client
			$adresse1 = $this->clients_adresses->adresse1;
			$ville = $this->clients_adresses->ville;
			$cp = $this->clients_adresses->cp;
			$id_pays = $this->clients_adresses->id_pays;
			
			$civilite = $this->clients->civilite;
			$nom = $this->clients->nom;
			$nom_usage = $this->clients->nom_usage;
			$prenom = $this->clients->prenom;
			$email = $this->clients->email;
			$telephone = $this->clients->telephone;
			$id_pays_naissance = $this->clients->id_pays_naissance;
			$ville_naissance = $this->clients->ville_naissance;
			$id_nationalite = $this->clients->id_nationalite;
			$naissance = $this->clients->naissance;
			
			// fichier
			$fichier_cni_passeport = $this->lenders_accounts->fichier_cni_passeport;
			$fichier_justificatif_domicile = $this->lenders_accounts->fichier_justificatif_domicile;
			if($this->etranger > 0) $fichier_document_fiscal = $this->lenders_accounts->fichier_document_fiscal;
			
			$this->form_ok = true;
			
			////////////////////////////////////
			// On verifie meme adresse ou pas //
			////////////////////////////////////
			if($_POST['mon-addresse'] != false)
			$this->clients_adresses->meme_adresse_fiscal = 1; // la meme
			else
			$this->clients_adresses->meme_adresse_fiscal = 0; // pas la meme
			
			// adresse fiscal
			
			$this->clients_adresses->adresse_fiscal = $_POST['adresse_inscription'];
			$this->clients_adresses->ville_fiscal = $_POST['ville_inscription'];
			$this->clients_adresses->cp_fiscal = $_POST['postal'];
			$this->clients_adresses->id_pays_fiscal = $_POST['pays1'];	
			
			// pas la meme
			if($this->clients_adresses->meme_adresse_fiscal == 0)
			{
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adress2'];
				$this->clients_adresses->ville = $_POST['ville2'];
				$this->clients_adresses->cp = $_POST['postal2'];
				$this->clients_adresses->id_pays = $_POST['pays2'];	
			}
			// la meme
			else
			{
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adresse_inscription'];
				$this->clients_adresses->ville = $_POST['ville_inscription'];
				$this->clients_adresses->cp = $_POST['postal'];
				$this->clients_adresses->id_pays = $_POST['pays1'];	
			}
			////////////////////////////////////////
			
			$this->clients->civilite = $_POST['sex'];
			$this->clients->nom = $this->ficelle->majNom($_POST['nom-famille']);
			
			//Ajout CM 06/08/14
			//$this->clients->nom_usage = $this->ficelle->majNom($_POST['nom-dusage']);
			if(isset($_POST['nom-dusage']) && $_POST['nom-dusage'] == $this->lng['etape1']['nom-dusage'])
				$this->clients->nom_usage = '';
			else 
				$this->clients->nom_usage = $this->ficelle->majNom($_POST['nom-dusage']);
			
			$this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
			$this->clients->email = $_POST['email'];
			$this->clients->telephone = str_replace(' ','',$_POST['phone']);
			$this->clients->id_pays_naissance = $_POST['pays3'];
			$this->clients->ville_naissance = $_POST['naissance'];
			$this->clients->id_nationalite = $_POST['nationalite'];
			$this->clients->naissance = $_POST['annee_naissance'].'-'.$_POST['mois_naissance'].'-'.$_POST['jour_naissance'];
			// Verif //
			
			// check_etranger
			if($this->etranger > 0){
				if(isset($_POST['check_etranger']) && $_POST['check_etranger'] == false){
					$this->form_ok = false;	
				}
			}
			
			// age 
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
			elseif($this->clients->existEmail($_POST['email']) == false){
				// et si l'email n'est pas celle du client
				if($_POST['email'] != $this->email){
					// check si l'adresse mail est deja utilisé
					$this->reponse_email = $this->lng['etape1']['erreur-email'];
					$this->form_ok = false;
					$_SESSION['reponse_email'] = $this->reponse_email;
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
			// telephone
			if(!isset($_POST['phone']) || $_POST['phone'] == $this->lng['etape1']['telephone']){
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
			
			// si form particulier ok
			if($this->form_ok == true){
				//////////////
				// FICHIERS //
				
				// si etrangé
				if($this->etranger == 1){
					if(isset($_FILES['document_fiscal_1']) && $_FILES['document_fiscal_1']['name'] != ''){
						$this->upload->setUploadDir($this->path,'protected/lenders/document_fiscal/');
						if($this->upload->doUpload('document_fiscal_1')){
							if($this->lenders_accounts->fichier_document_fiscal != '')@unlink($this->path.'protected/lenders/document_fiscal/'.$this->lenders_accounts->fichier_document_fiscal);
							$this->lenders_accounts->fichier_document_fiscal = $this->upload->getName();
						}
					}
				}
				elseif($this->etranger == 2){
					if(isset($_FILES['document_fiscal_2']) && $_FILES['document_fiscal_2']['name'] != ''){
						$this->upload->setUploadDir($this->path,'protected/lenders/document_fiscal/');
						if($this->upload->doUpload('document_fiscal_2')){
							if($this->lenders_accounts->fichier_document_fiscal != '')@unlink($this->path.'protected/lenders/document_fiscal/'.$this->lenders_accounts->fichier_document_fiscal);
							$this->lenders_accounts->fichier_document_fiscal = $this->upload->getName();
						}
					}
				}
				
				// carte-nationale-didentite
				if(isset($_FILES['ci']) && $_FILES['ci']['name'] != ''){
					$this->upload->setUploadDir($this->path,'protected/lenders/cni_passeport/');
					if($this->upload->doUpload('ci')){
						if($this->lenders_accounts->fichier_cni_passeport != '')@unlink($this->path.'protected/lenders/cni_passeport/'.$this->lenders_accounts->fichier_cni_passeport);
						$this->lenders_accounts->fichier_cni_passeport = $this->upload->getName();
					}
				}
				// justificatif-de-domicile
				if(isset($_FILES['justificatif_de_domicile']) && $_FILES['justificatif_de_domicile']['name'] != ''){
					$this->upload->setUploadDir($this->path,'protected/lenders/justificatif_domicile/');
					if($this->upload->doUpload('justificatif_de_domicile')){
						if($this->lenders_accounts->fichier_justificatif_domicile != '')@unlink($this->path.'protected/companies/justificatif_domicile/'.$this->lenders_accounts->fichier_justificatif_domicile);
						$this->lenders_accounts->fichier_justificatif_domicile = $this->upload->getName();
					}
				}
				// FIN FICHIERS //
				//////////////////
				
				$this->clients->id_langue = 'fr';
				$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);

				// Si mail existe deja
				if($this->reponse_email != ''){
					$this->clients->email = $this->email;
					$_SESSION['reponse_email'] = $this->reponse_email;
				}
				
				// Update
				$this->clients->update();
				$this->clients_adresses->update();
				$this->lenders_accounts->update();
				
				//********************************************//
				//*** ENVOI DU MAIL NOTIFICATION notification-nouveaux-preteurs ***//
				//********************************************//
				
				$dateDepartControlPays = strtotime('2014-07-31 18:00:00');
				
				// on modifie que si on a des infos sensiblent
				if(
				$adresse_fiscal != $this->clients_adresses->adresse_fiscal || 
				$ville_fiscal != $this->clients_adresses->ville_fiscal || 
				$cp_fiscal != $this->clients_adresses->cp_fiscal || 
				!in_array($this->clients_adresses->id_pays_fiscal,array(0,$id_pays_fiscal)) && strtotime($this->clients->added) >= $dateDepartControlPays || 
				//$id_pays != $this->clients_adresses->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays || 
				$nom != $this->clients->nom || 
				$nom_usage != $this->clients->nom_usage || 
				$prenom != $this->clients->prenom || 
				$id_pays_naissance != $this->clients->id_pays_naissance && strtotime($this->clients->added) >= $dateDepartControlPays || 
				$id_nationalite != $this->clients->id_nationalite && strtotime($this->clients->added) >= $dateDepartControlPays || 
				$naissance != $this->clients->naissance || 
				$fichier_cni_passeport != $this->lenders_accounts->fichier_cni_passeport || 
				$fichier_justificatif_domicile != $this->lenders_accounts->fichier_justificatif_domicile || 
				$this->etranger > 0 && $fichier_document_fiscal != $this->lenders_accounts->fichier_document_fiscal
				){
				
					$contenu = '<ul>';
					// adresse fiscal
					if($adresse_fiscal != $this->clients_adresses->adresse_fiscal)
						$contenu .= '<li>adresse fiscale</li>';
					if($ville_fiscal != $this->clients_adresses->ville_fiscal)
						$contenu .= '<li>ville fiscale</li>';
					if($cp_fiscal != $this->clients_adresses->cp_fiscal)
						$contenu .= '<li>cp fiscal</li>';
					if(!in_array($this->clients_adresses->id_pays_fiscal,array(0,$id_pays_fiscal)) && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>pays fiscal</li>';
					// adresse client	
					if($adresse1 != $this->clients_adresses->adresse1)
						$contenu .= '<li>adresse</li>';
					if($ville != $this->clients_adresses->ville)
						$contenu .= '<li>ville</li>';
					if($cp != $this->clients_adresses->cp)
						$contenu .= '<li>cp</li>';
					if($id_pays != $this->clients_adresses->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>pays</li>';
					// client	
					if($civilite != $this->clients->civilite)
						$contenu .= '<li>civilite</li>';
					if($nom != $this->clients->nom)
						$contenu .= '<li>nom</li>';
					if($nom_usage != $this->clients->nom_usage)
						$contenu .= '<li>nom_usage</li>';
					if($prenom != $this->clients->prenom)
						$contenu .= '<li>prenom</li>';
					if($email != $this->clients->email)
						$contenu .= '<li>email</li>';
					if($telephone != $this->clients->telephone)
						$contenu .= '<li>telephone</li>';
					if($id_pays_naissance != $this->clients->id_pays_naissance && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>pays naissance</li>';
					if($ville_naissance != $this->clients->ville_naissance)
						$contenu .= '<li>ville naissance</li>';
					if($id_nationalite != $this->clients->id_nationalite && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>nationalite</li>';
					if($naissance != $this->clients->naissance)
						$contenu .= '<li>date naissance</li>';
					// fichier
					if($fichier_cni_passeport != $this->lenders_accounts->fichier_cni_passeport)
						$contenu .= '<li>fichier cni passeport</li>';
					if($fichier_justificatif_domicile != $this->lenders_accounts->fichier_justificatif_domicile)
						$contenu .= '<li>fichier justificatif domicile</li>';
					if($this->etranger > 0 && $fichier_document_fiscal != $this->lenders_accounts->fichier_document_fiscal)
						$contenu .= '<li>fichier document fiscal</li>';
						
						
					$contenu .= '</ul>';
					
					// 40 : Complétude (Réponse)
					if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
					else $statut_client = 50; // 50 : Modification
					
					// creation du statut "Modification"
					$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
					
					// destinataire
					$this->settings->get('Adresse notification modification preteur','type');
					$destinataire = $this->settings->value;
					
					$lemois = $this->dates->tableauMois[$this->language][date('n')];
					
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
					
					
					/// mail nmp pour le preteur particulier ///
				
					//************************************//
					//*** ENVOI DU MAIL GENERATION MDP ***//
					//************************************//
		
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-modification-compte','lang = "'.$this->language.'" AND type');
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					// Variables du mailing
					$varMail = array(
					'surl' =>  $this->surl,
					'url' => $this->lurl,
					'prenom' => $this->clients->prenom,
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
					
				}
				$_SESSION['reponse_profile_perso'] = $this->lng['profile']['titre-1'].' '.$this->lng['profile']['sauvegardees'];
				
				header('Location:'.$this->lurl.'/profile/particulier');
				die;
				
			} // fin form valide	
		} // fin form
		
		// Formulaire informations bancaires particulier
		elseif(isset($_POST['send_form_bank_particulier'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(5,'info bank profile',$this->clients->id_client,$serialize);
			////////////////
			
			// rib
			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/rib/');
				if($this->upload->doUpload('rib')){
					if($this->lenders_accounts->fichier_rib != '')@unlink($this->path.'protected/lenders/rib/'.$this->lenders_accounts->fichier_rib);
					$this->lenders_accounts->fichier_rib = $this->upload->getName();
					$fichier_rib = true;
				}
				else{
					$this->error_rib = true;	
				}
			}	
					
			$bic_old = $this->lenders_accounts->bic;
			$iban_old = $this->lenders_accounts->iban;
			
			$this->lenders_accounts->bic = trim(strtoupper($_POST['bic']));
			$this->lenders_accounts->iban = '';
			for($i=1;$i<=7;$i++){ $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-'.$i])); }
			
			$origine_des_fonds_old = $this->lenders_accounts->origine_des_fonds;
			
			$this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
			if($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000)$this->lenders_accounts->precision = $_POST['preciser'];
			else $this->lenders_accounts->precision = '';
			
			
			$this->form_ok = true;
			$this->error_fichier = false;
			
			if($this->error_rib == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			
			// on enregistre une partie pour avoir les images good
			if($this->error_rib == false){
				$this->lenders_accounts->update();	
			}
			
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
			// RIB
			if($this->lenders_accounts->fichier_rib == ''){
				$this->form_ok = false;
			}
			
			if($this->form_ok == true){
				// On met a jour le lender
				$this->lenders_accounts->update();
				
				// origine_des_fonds | BIC | IBAN
				if($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds || $bic_old != $this->lenders_accounts->bic || $iban_old != $this->lenders_accounts->iban || $fichier_rib == true)
				{
					
					$contenu = '<ul>'; 
					if($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds)
						$contenu .= '<li>Origine des fonds</li>';
					if($bic_old != $this->lenders_accounts->bic)
						$contenu .= '<li>BIC</li>';
					if($iban_old != $this->lenders_accounts->iban)
						$contenu .= '<li>IBAN</li>';
					if($fichier_rib == true)
						$contenu .= '<li>Fichier RIB</li>';
					$contenu .= '</ul>';
					
					if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
					else $statut_client = 50;
					
					// creation du statut "Modification"
					$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
					
					// destinataire
					$this->settings->get('Adresse notification modification preteur','type');
					$destinataire = $this->settings->value;
					
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
					
					
					/// mail nmp pour le preteur particulier ///
					
					//************************************//
					//*** ENVOI DU MAIL  ***//
					//************************************//
		
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-modification-compte','lang = "'.$this->language.'" AND type');
					
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					// Variables du mailing
					$varMail = array(
					'surl' =>  $this->surl,
					'url' => $this->lurl,
					'prenom' => $this->clients->prenom,
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
					////////////////////////////////
					
					$_SESSION['reponse_profile_bank'] = $this->lng['profile']['titre-2'].' '.$this->lng['profile']['sauvegardees'];
					
					header('Location:'.$this->lurl.'/profile/particulier/2');
					die;
				}
				
			}
			
		}
		// formulaire particulier secu
		elseif(isset($_POST['send_form_mdp'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'newmdp' => md5($_POST['passNew']),'question' => $_POST['question'],'reponse' => md5($_POST['reponse'])));
			$this->clients_history_actions->histo(7,'change mdp',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			// old mdp
			if(!isset($_POST['passOld']) || $_POST['passOld'] == '' || $_POST['passOld'] == $this->lng['etape1']['ancien-mot-de-passe']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['passOld']) && md5($_POST['passOld']) != $this->clients->password){
				$this->form_ok = false;
				
				$_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
				header('Location:'.$this->lurl.'/profile/particulier/3');
				die;
			}
			
			// new pass
			if(!isset($_POST['passNew']) || $_POST['passNew'] == '' || $_POST['passNew'] == $this->lng['etape1']['nouveau-mot-de-passe']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['passNew']) && $this->ficelle->password_fo($_POST['passNew'],6) == false){
				$this->form_ok = false;
			}
			
			// confirmation new pass
			if(!isset($_POST['passNew2']) || $_POST['passNew2'] == '' || $_POST['passNew2'] == $this->lng['etape1']['confirmation-nouveau-mot-de-passe']){
				$this->form_ok = false;
			}
			// check new pass != de confirmation
			if(isset($_POST['passNew']) && isset($_POST['passNew2']) && $_POST['passNew'] != $_POST['passNew2']){
				$this->form_ok = false;
			}
			
			// si good
			if($this->form_ok == true){
				
				$this->clients->password = md5($_POST['passNew']);
				$_SESSION['client']['password'] = $this->clients->password;
				
				// question / reponse
				if(isset($_POST['question']) && isset($_POST['reponse']) && $_POST['question'] != '' && $_POST['reponse'] != '' && $_POST['question'] != $this->lng['etape1']['question-secrete'] && $_POST['reponse'] != $this->lng['etape1']['question-reponse'])
				{
					$this->clients->secrete_question = $_POST['question'];
					$this->clients->secrete_reponse = md5($_POST['reponse']);
				}
				
				$this->clients->update();
				
				//************************************//
				//*** ENVOI DU MAIL GENERATION MDP ***//
				//************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('generation-mot-de-passe','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				$login = $this->clients->email;
				
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
				'login' => $login,
				'prenom_p' => $this->clients->prenom,
				'mdp' => '',
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
				
				$_SESSION['reponse_profile_secu'] = $this->lng['profile']['votre-mot-de-passe-a-bien-ete-change'];
					
				header('Location:'.$this->lurl.'/profile/particulier/3');
				die;
			
			}
			
		}
		
	}
	
	function _particulier()
	{
		
		// Societe (si on est pas sur la bonne page)
		if(in_array($this->clients->type,array(2,4))){
			header('Location:'.$this->lurl.'/profile/societe');
			die;
		}
		
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
	
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
		//$this->pays = $this->loadData('pays');
		$this->pays = $this->loadData('pays_v2');
		//$this->nationalites = $this->loadData('nationalites');
		$this->nationalites = $this->loadData('nationalites_v2');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		// recuperation info lender
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// Liste des pays
		$this->lPays = $this->pays->select('','ordre ASC');
		//echo count($this->lPays);
		// liste des nationalites
		$this->lNatio = $this->nationalites->select('','ordre ASC');
		
		// Naissance 
		$nais = explode('-',$this->clients->naissance);	
		$this->jour = $nais[2];
		$this->mois = $nais[1];
		$this->annee = $nais[0];
		
		// On garde de coté l'adresse mail du preteur
		$this->email = $this->clients->email;
		
		// Liste deroulante origine des fonds
		$this->settings->get("Liste deroulante origine des fonds",'type');
		$this->origine_fonds = explode(';',$this->settings->value);
			
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
		
		$this->etranger = 0;
		
		// fr/resident etranger
		if($this->clients->id_nationalite == 1 && $this->clients_adresses->id_pays_fiscal > 1){
			$this->etranger = 1;
		}
		// no fr/resident etranger
		elseif($this->clients->id_nationalite != 1 && $this->clients_adresses->id_pays_fiscal > 1){
			$this->etranger = 2;
		}
		
		// formulaire particulier perso
		if(isset($_POST['send_form_particulier_perso'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(4,'info perso profile',$this->clients->id_client,$serialize);
			////////////////
			
			
			// fr/resident etranger
			if($_POST['nationalite'] == 1 && $_POST['pays1'] > 1){
				$this->etranger = 1;
			}
			// no fr/resident etranger
			elseif($_POST['nationalite'] != 1 && $_POST['pays1'] > 1){
				$this->etranger = 2;
			}
			else{
				$this->etranger = 0;
			}
			
			// on recup la valeur deja existante //
			
			
			// adresse fiscal
			$adresse_fiscal = $this->clients_adresses->adresse_fiscal;
			$ville_fiscal = $this->clients_adresses->ville_fiscal;
			$cp_fiscal = $this->clients_adresses->cp_fiscal;
			$id_pays_fiscal = $this->clients_adresses->id_pays_fiscal;	
			
			// adresse client
			$adresse1 = $this->clients_adresses->adresse1;
			$ville = $this->clients_adresses->ville;
			$cp = $this->clients_adresses->cp;
			$id_pays = $this->clients_adresses->id_pays;
			
			$civilite = $this->clients->civilite;
			$nom = $this->clients->nom;
			$nom_usage = $this->clients->nom_usage;
			$prenom = $this->clients->prenom;
			$email = $this->clients->email;
			$telephone = $this->clients->telephone;
			$id_pays_naissance = $this->clients->id_pays_naissance;
			$ville_naissance = $this->clients->ville_naissance;
			$id_nationalite = $this->clients->id_nationalite;
			$naissance = $this->clients->naissance;
			
			// fichier
			$fichier_cni_passeport = $this->lenders_accounts->fichier_cni_passeport;
			$fichier_justificatif_domicile = $this->lenders_accounts->fichier_justificatif_domicile;
			if($this->etranger > 0) $fichier_document_fiscal = $this->lenders_accounts->fichier_document_fiscal;
			
			$this->form_ok = true;
			
			////////////////////////////////////
			// On verifie meme adresse ou pas //
			////////////////////////////////////
			if($_POST['mon-addresse'] != false)
			$this->clients_adresses->meme_adresse_fiscal = 1; // la meme
			else
			$this->clients_adresses->meme_adresse_fiscal = 0; // pas la meme
			
			// adresse fiscal
			
			$this->clients_adresses->adresse_fiscal = $_POST['adresse_inscription'];
			$this->clients_adresses->ville_fiscal = $_POST['ville_inscription'];
			$this->clients_adresses->cp_fiscal = $_POST['postal'];
			$this->clients_adresses->id_pays_fiscal = $_POST['pays1'];	
			
			// pas la meme
			if($this->clients_adresses->meme_adresse_fiscal == 0)
			{
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adress2'];
				$this->clients_adresses->ville = $_POST['ville2'];
				$this->clients_adresses->cp = $_POST['postal2'];
				$this->clients_adresses->id_pays = $_POST['pays2'];	
			}
			// la meme
			else
			{
				// adresse client
				$this->clients_adresses->adresse1 = $_POST['adresse_inscription'];
				$this->clients_adresses->ville = $_POST['ville_inscription'];
				$this->clients_adresses->cp = $_POST['postal'];
				$this->clients_adresses->id_pays = $_POST['pays1'];	
			}
			////////////////////////////////////////
			
			$this->clients->civilite = $_POST['sex'];
			$this->clients->nom = $this->ficelle->majNom($_POST['nom-famille']);
			
			//Ajout CM 06/08/14
			//$this->clients->nom_usage = $this->ficelle->majNom($_POST['nom-dusage']);
			if(isset($_POST['nom-dusage']) && $_POST['nom-dusage'] == $this->lng['etape1']['nom-dusage'])
				$this->clients->nom_usage = '';
			else 
				$this->clients->nom_usage = $this->ficelle->majNom($_POST['nom-dusage']);
			
			$this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
			$this->clients->email = $_POST['email'];
			$this->clients->telephone = str_replace(' ','',$_POST['phone']);
			$this->clients->id_pays_naissance = $_POST['pays3'];
			$this->clients->ville_naissance = $_POST['naissance'];
			$this->clients->id_nationalite = $_POST['nationalite'];
			$this->clients->naissance = $_POST['annee_naissance'].'-'.$_POST['mois_naissance'].'-'.$_POST['jour_naissance'];
			// Verif //
			
			// check_etranger
			if($this->etranger > 0){
				if(isset($_POST['check_etranger']) && $_POST['check_etranger'] == false){
					$this->form_ok = false;	
				}
			}
			
			// age 
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
			elseif($this->clients->existEmail($_POST['email']) == false){
				// et si l'email n'est pas celle du client
				if($_POST['email'] != $this->email){
					// check si l'adresse mail est deja utilisé
					$this->reponse_email = $this->lng['etape1']['erreur-email'];
					$this->form_ok = false;
					$_SESSION['reponse_email'] = $this->reponse_email;
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
			// telephone
			if(!isset($_POST['phone']) || $_POST['phone'] == $this->lng['etape1']['telephone']){
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
			
			
			/////////////////////// PARTIE BANQUE /////////////////////////////
			
			
			// rib
			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/rib/');
				if($this->upload->doUpload('rib')){
					if($this->lenders_accounts->fichier_rib != '')@unlink($this->path.'protected/lenders/rib/'.$this->lenders_accounts->fichier_rib);
					$this->lenders_accounts->fichier_rib = $this->upload->getName();
					$fichier_rib = true;
				}
				else{
					$this->error_rib = true;	
				}
			}	
					
			$bic_old = $this->lenders_accounts->bic;
			$iban_old = $this->lenders_accounts->iban;
			
			$this->lenders_accounts->bic = trim(strtoupper($_POST['bic']));
			$this->lenders_accounts->iban = '';
			for($i=1;$i<=7;$i++){ $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-'.$i])); }
			
			$origine_des_fonds_old = $this->lenders_accounts->origine_des_fonds;
			
			$this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
			if($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000)$this->lenders_accounts->precision = $_POST['preciser'];
			else $this->lenders_accounts->precision = '';
			
			
			$this->form_ok = true;
			$this->error_fichier = false;
			
			if($this->error_rib == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			
			// on enregistre une partie pour avoir les images good
			if($this->error_rib == false){
				$this->lenders_accounts->update();	
			}
			
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
			// RIB
			if($this->lenders_accounts->fichier_rib == ''){
				$this->form_ok = false;
			}
			
			
			///////////////////////////////////////////////////////////////////
			
			// si form particulier ok
			if($this->form_ok == true){
				//////////////
				// FICHIERS //
				
				// si etrangé
				if($this->etranger == 1){
					if(isset($_FILES['document_fiscal_1']) && $_FILES['document_fiscal_1']['name'] != ''){
						$this->upload->setUploadDir($this->path,'protected/lenders/document_fiscal/');
						if($this->upload->doUpload('document_fiscal_1')){
							if($this->lenders_accounts->fichier_document_fiscal != '')@unlink($this->path.'protected/lenders/document_fiscal/'.$this->lenders_accounts->fichier_document_fiscal);
							$this->lenders_accounts->fichier_document_fiscal = $this->upload->getName();
						}
					}
				}
				elseif($this->etranger == 2){
					if(isset($_FILES['document_fiscal_2']) && $_FILES['document_fiscal_2']['name'] != ''){
						$this->upload->setUploadDir($this->path,'protected/lenders/document_fiscal/');
						if($this->upload->doUpload('document_fiscal_2')){
							if($this->lenders_accounts->fichier_document_fiscal != '')@unlink($this->path.'protected/lenders/document_fiscal/'.$this->lenders_accounts->fichier_document_fiscal);
							$this->lenders_accounts->fichier_document_fiscal = $this->upload->getName();
						}
					}
				}
				
				// carte-nationale-didentite
				if(isset($_FILES['ci']) && $_FILES['ci']['name'] != ''){
					$this->upload->setUploadDir($this->path,'protected/lenders/cni_passeport/');
					if($this->upload->doUpload('ci')){
						if($this->lenders_accounts->fichier_cni_passeport != '')@unlink($this->path.'protected/lenders/cni_passeport/'.$this->lenders_accounts->fichier_cni_passeport);
						$this->lenders_accounts->fichier_cni_passeport = $this->upload->getName();
					}
				}
				// justificatif-de-domicile
				if(isset($_FILES['justificatif_de_domicile']) && $_FILES['justificatif_de_domicile']['name'] != ''){
					$this->upload->setUploadDir($this->path,'protected/lenders/justificatif_domicile/');
					if($this->upload->doUpload('justificatif_de_domicile')){
						if($this->lenders_accounts->fichier_justificatif_domicile != '')@unlink($this->path.'protected/companies/justificatif_domicile/'.$this->lenders_accounts->fichier_justificatif_domicile);
						$this->lenders_accounts->fichier_justificatif_domicile = $this->upload->getName();
					}
				}
				// FIN FICHIERS //
				//////////////////
				
				$this->clients->id_langue = 'fr';
				$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);

				// Si mail existe deja
				if($this->reponse_email != ''){
					$this->clients->email = $this->email;
					$_SESSION['reponse_email'] = $this->reponse_email;
				}
				
				// Update
				$this->clients->update();
				$this->clients_adresses->update();
				$this->lenders_accounts->update();
				
				//********************************************//
				//*** ENVOI DU MAIL NOTIFICATION notification-nouveaux-preteurs ***//
				//********************************************//
				
				$dateDepartControlPays = strtotime('2014-07-31 18:00:00');
				
				// on modifie que si on a des infos sensiblent
				if(
				$adresse_fiscal != $this->clients_adresses->adresse_fiscal || 
				$ville_fiscal != $this->clients_adresses->ville_fiscal || 
				$cp_fiscal != $this->clients_adresses->cp_fiscal || 
				!in_array($this->clients_adresses->id_pays_fiscal,array(0,$id_pays_fiscal)) && strtotime($this->clients->added) >= $dateDepartControlPays || 
				//$id_pays != $this->clients_adresses->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays || 
				$nom != $this->clients->nom || 
				$nom_usage != $this->clients->nom_usage || 
				$prenom != $this->clients->prenom || 
				$id_pays_naissance != $this->clients->id_pays_naissance && strtotime($this->clients->added) >= $dateDepartControlPays || 
				$id_nationalite != $this->clients->id_nationalite && strtotime($this->clients->added) >= $dateDepartControlPays || 
				$naissance != $this->clients->naissance || 
				$fichier_cni_passeport != $this->lenders_accounts->fichier_cni_passeport || 
				$fichier_justificatif_domicile != $this->lenders_accounts->fichier_justificatif_domicile || 
				$this->etranger > 0 && $fichier_document_fiscal != $this->lenders_accounts->fichier_document_fiscal ||
				$origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds || 
				$bic_old != $this->lenders_accounts->bic || 
				$iban_old != $this->lenders_accounts->iban || 
				$fichier_rib == true
				){
				
					$contenu = '<ul>';
					// adresse fiscal
					if($adresse_fiscal != $this->clients_adresses->adresse_fiscal)
						$contenu .= '<li>adresse fiscale</li>';
					if($ville_fiscal != $this->clients_adresses->ville_fiscal)
						$contenu .= '<li>ville fiscale</li>';
					if($cp_fiscal != $this->clients_adresses->cp_fiscal)
						$contenu .= '<li>cp fiscal</li>';
					if(!in_array($this->clients_adresses->id_pays_fiscal,array(0,$id_pays_fiscal)) && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>pays fiscal</li>';
					// adresse client	
					if($adresse1 != $this->clients_adresses->adresse1)
						$contenu .= '<li>adresse</li>';
					if($ville != $this->clients_adresses->ville)
						$contenu .= '<li>ville</li>';
					if($cp != $this->clients_adresses->cp)
						$contenu .= '<li>cp</li>';
					if($id_pays != $this->clients_adresses->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>pays</li>';
					// client	
					if($civilite != $this->clients->civilite)
						$contenu .= '<li>civilite</li>';
					if($nom != $this->clients->nom)
						$contenu .= '<li>nom</li>';
					if($nom_usage != $this->clients->nom_usage)
						$contenu .= '<li>nom_usage</li>';
					if($prenom != $this->clients->prenom)
						$contenu .= '<li>prenom</li>';
					if($email != $this->clients->email)
						$contenu .= '<li>email</li>';
					if($telephone != $this->clients->telephone)
						$contenu .= '<li>telephone</li>';
					if($id_pays_naissance != $this->clients->id_pays_naissance && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>pays naissance</li>';
					if($ville_naissance != $this->clients->ville_naissance)
						$contenu .= '<li>ville naissance</li>';
					if($id_nationalite != $this->clients->id_nationalite && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>nationalite</li>';
					if($naissance != $this->clients->naissance)
						$contenu .= '<li>date naissance</li>';
					// fichier
					if($fichier_cni_passeport != $this->lenders_accounts->fichier_cni_passeport)
						$contenu .= '<li>fichier cni passeport</li>';
					if($fichier_justificatif_domicile != $this->lenders_accounts->fichier_justificatif_domicile)
						$contenu .= '<li>fichier justificatif domicile</li>';
					if($this->etranger > 0 && $fichier_document_fiscal != $this->lenders_accounts->fichier_document_fiscal)
						$contenu .= '<li>fichier document fiscal</li>';
						
					////////////// PARTIE BANQUE ////////////////////////
					
					if($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds)
						$contenu .= '<li>Origine des fonds</li>';
					if($bic_old != $this->lenders_accounts->bic)
						$contenu .= '<li>BIC</li>';
					if($iban_old != $this->lenders_accounts->iban)
						$contenu .= '<li>IBAN</li>';
					if($fichier_rib == true)
						$contenu .= '<li>Fichier RIB</li>';
					
					/////////////////////////////////////////////////////
						
						
					$contenu .= '</ul>';
					
					// 40 : Complétude (Réponse)
					if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
					else $statut_client = 50; // 50 : Modification
					
					// creation du statut "Modification"
					$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
					
					// destinataire
					$this->settings->get('Adresse notification modification preteur','type');
					$destinataire = $this->settings->value;
					
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
					
					
					/// mail nmp pour le preteur particulier ///
				
					//************************************//
					//*** ENVOI DU MAIL GENERATION MDP ***//
					//************************************//
		
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-modification-compte','lang = "'.$this->language.'" AND type');
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					// Variables du mailing
					$varMail = array(
					'surl' =>  $this->surl,
					'url' => $this->lurl,
					'prenom' => $this->clients->prenom,
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
					
				}
				$_SESSION['reponse_profile_perso'] = $this->lng['profile']['titre-1'].' '.$this->lng['profile']['sauvegardees'];
				
				header('Location:'.$this->lurl.'/profile/particulier/3');
				die;
				
			} // fin form valide	
		} // fin form
		// formulaire particulier secu
		elseif(isset($_POST['send_form_mdp'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'newmdp' => md5($_POST['passNew']),'question' => $_POST['question'],'reponse' => md5($_POST['reponse'])));
			$this->clients_history_actions->histo(7,'change mdp',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			// old mdp
			if(!isset($_POST['passOld']) || $_POST['passOld'] == '' || $_POST['passOld'] == $this->lng['etape1']['ancien-mot-de-passe']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['passOld']) && md5($_POST['passOld']) != $this->clients->password){
				$this->form_ok = false;
				
				$_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
				header('Location:'.$this->lurl.'/profile/particulier/3');
				die;
			}
			
			// new pass
			if(!isset($_POST['passNew']) || $_POST['passNew'] == '' || $_POST['passNew'] == $this->lng['etape1']['nouveau-mot-de-passe']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['passNew']) && $this->ficelle->password_fo($_POST['passNew'],6) == false){
				$this->form_ok = false;
			}
			
			// confirmation new pass
			if(!isset($_POST['passNew2']) || $_POST['passNew2'] == '' || $_POST['passNew2'] == $this->lng['etape1']['confirmation-nouveau-mot-de-passe']){
				$this->form_ok = false;
			}
			// check new pass != de confirmation
			if(isset($_POST['passNew']) && isset($_POST['passNew2']) && $_POST['passNew'] != $_POST['passNew2']){
				$this->form_ok = false;
			}
			
			// si good
			if($this->form_ok == true){
				
				$this->clients->password = md5($_POST['passNew']);
				$_SESSION['client']['password'] = $this->clients->password;
				
				// question / reponse
				if(isset($_POST['question']) && isset($_POST['reponse']) && $_POST['question'] != '' && $_POST['reponse'] != '' && $_POST['question'] != $this->lng['etape1']['question-secrete'] && $_POST['reponse'] != $this->lng['etape1']['question-reponse'])
				{
					$this->clients->secrete_question = $_POST['question'];
					$this->clients->secrete_reponse = md5($_POST['reponse']);
				}
				
				$this->clients->update();
				
				//************************************//
				//*** ENVOI DU MAIL GENERATION MDP ***//
				//************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('generation-mot-de-passe','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				$login = $this->clients->email;
				
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
				'login' => $login,
				'prenom_p' => $this->clients->prenom,
				'mdp' => '',
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
				
				$_SESSION['reponse_profile_secu'] = $this->lng['profile']['votre-mot-de-passe-a-bien-ete-change'];
					
				header('Location:'.$this->lurl.'/profile/particulier/2');
				die;
			
			}
			
		}
		
	}
	
	function _societe_old()
	{
		// Particulier (si on est pas sur la bonne page)
		if(in_array($this->clients->type,array(1,3))){
			header('Location:'.$this->lurl.'/profile/particulier');
			die;
		}
		
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
	
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
		//$this->pays = $this->loadData('pays');
		$this->pays = $this->loadData('pays_v2');
		//$this->nationalites = $this->loadData('nationalites');
		$this->nationalites = $this->loadData('nationalites_v2');
		$this->companies = $this->loadData('companies');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		// Liste des pays
		$this->lPays = $this->pays->select('','ordre ASC');
		
		// Liste deroulante conseil externe de l'entreprise
		$this->settings->get("Liste deroulante conseil externe de l'entreprise",'type');
		$this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);
		
		// On recup le preteur
		$this->companies->get($this->clients->id_client,'id_client_owner');
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		// Liste deroulante origine des fonds entreprise
		$this->settings->get("Liste deroulante origine des fonds societe",'status = 1 AND type');
		$this->origine_fonds_E = explode(';',$this->settings->value);
		
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
		
		// form info perso
		if(isset($_POST['send_form_societe_perso'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(4,'info perso profile',$this->clients->id_client,$serialize);
			////////////////
			
			// on met ca de coté
			$this->email_temp = $this->clients->email;
			
			$this->form_ok = true;
			
			
			$name 		= $this->companies->name;
			$forme 		= $this->companies->forme;
			$capital 	= $this->companies->capital;
			$siren 		= $this->companies->siren;
			$siret 		= $this->companies->siret;
			$phone 		= $this->companies->phone;
			
			$this->companies->name 		= $_POST['raison_sociale_inscription'];
			$this->companies->forme 	= $_POST['forme_juridique_inscription'];
			$this->companies->capital 	= str_replace(' ','',$_POST['capital_social_inscription']);
			$this->companies->siret 	= $_POST['siret_inscription'];
			$this->companies->siren 	= $_POST['siren_inscription'];
			//$this->companies->siren 	= substr($this->companies->siret,0,9);
			$this->companies->phone 	= str_replace(' ','',$_POST['phone_inscription']);
			

			////////////////////////////////////
			// On verifie meme adresse ou pas //
			////////////////////////////////////
			if($_POST['mon-addresse'] != false)
			$this->companies->status_adresse_correspondance = '1'; // la meme
			else
			$this->companies->status_adresse_correspondance = '0'; // pas la meme
			
			// adresse fiscale
			$adresse_fiscal = $this->companies->adresse1;
			$ville_fiscal = $this->companies->city;
			$cp_fiscal = $this->companies->zip;
			$pays_fiscal = $this->companies->id_pays;
			// adresse client
			$adresse1 = $this->clients_adresses->adresse1;
			$ville = $this->clients_adresses->ville;
			$cp = $this->clients_adresses->cp;
			$id_pays = $this->clients_adresses->id_pays;
			
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
			
			$civilite = $this->clients->civilite;
			$nom = $this->clients->nom;
			$prenom = $this->clients->prenom;
			$fonction = $this->clients->fonction;
			$telephone = $this->clients->telephone;
			
			$this->clients->civilite = $_POST['genre1'];
			$this->clients->nom = $this->ficelle->majNom($_POST['nom_inscription']);
			$this->clients->prenom = $this->ficelle->majNom($_POST['prenom_inscription']);
			$this->clients->fonction = $_POST['fonction_inscription'];
			$this->clients->telephone = str_replace(' ','',$_POST['phone_new_inscription']);
			
			$civilite_dirigeant = $this->companies->civilite_dirigeant;
			$nom_dirigeant = $this->companies->nom_dirigeant;
			$prenom_dirigeant = $this->companies->prenom_dirigeant;
			$fonction_dirigeant = $this->companies->fonction_dirigeant;
			$email_dirigeant = $this->companies->email_dirigeant;
			$phone_dirigeant = $this->companies->phone_dirigeant;
			
			$status_conseil_externe_entreprise = $this->companies->status_conseil_externe_entreprise;
			$preciser_conseil_externe_entreprise = $this->companies->preciser_conseil_externe_entreprise;
			
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
			//siret_inscription
			if(!isset($_POST['siret_inscription']) || $_POST['siret_inscription'] == $this->lng['etape1']['siret']){
				$this->form_ok = false;
			}
			//siret_inscription
			if(!isset($_POST['siren_inscription']) || $_POST['siren_inscription'] == $this->lng['etape1']['siren']){
				$this->form_ok = false;
			}
			
			//phone_inscription
			if(!isset($_POST['phone_inscription']) || $_POST['phone_inscription'] == $this->lng['etape1']['telephone']){
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
				// et si l'email n'est pas celle du client
				if($_POST['email_inscription'] != $this->email_temp){
					// check si l'adresse mail est deja utilisé
					$this->reponse_email = $this->lng['etape1']['erreur-email'];
				}
				else $this->clients->email = $_POST['email_inscription'];
			}
			else $this->clients->email = $_POST['email_inscription'];
			
			//phone_new_inscription
			if(!isset($_POST['phone_new_inscription']) || $_POST['phone_new_inscription'] == $this->lng['etape1']['telephone']){
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
				
				// externe
				if($this->companies->status_client == 3){
					
					if(!isset($_POST['external-consultant']) || $_POST['external-consultant'] == ''){
						$this->form_ok = false;
					}
					/*if(!isset($_POST['autre_inscription']) || $_POST['autre_inscription'] == $this->lng['etape1']['autre']){
						$this->form_ok = false;
					}*/	
				}
			}
			
			// Formulaire societe ok
			if($this->form_ok == true)
			{
				$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);
				
				
				$this->clients->update();
				$this->clients_adresses->update();
				$this->companies->update();
				//$this->lenders_accounts->update();
				
				$dateDepartControlPays = strtotime('2014-07-31 18:00:00');
				
				// on envoie un mail notifiaction si infos fiscale modifiés
				if(
				$adresse_fiscal != $this->companies->adresse1 || 
				$ville_fiscal != $this->companies->city || 
				$cp_fiscal != $this->companies->zip || 
				$pays_fiscal != $this->companies->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays || 
				$name != $this->companies->name || 
				$forme != $this->companies->forme || 
				$capital != $this->companies->capital ||  
				$siren != $this->companies->siren || 
//				$phone != $this->companies->phone || 
				//$adresse1 != $this->clients_adresses->adresse1 || 
//				$ville != $this->clients_adresses->ville || 
//				$cp != $this->clients_adresses->cp || 
//				$id_pays != $this->clients_adresses->id_pays || 
//				$civilite != $this->clients->civilite || 
				$nom != $this->clients->nom || 
				$prenom != $this->clients->prenom || 
//				$fonction != $this->clients->fonction || 
//				$telephone != $this->clients->telephone || 
//				$civilite_dirigeant != $this->companies->civilite_dirigeant || 
				$nom_dirigeant != $this->companies->nom_dirigeant || 
				$prenom_dirigeant != $this->companies->prenom_dirigeant || 
//				$fonction_dirigeant != $this->companies->fonction_dirigeant || 
//				$email_dirigeant != $this->companies->email_dirigeant || 
//				$phone_dirigeant != $this->companies->phone_dirigeant || 
				$status_conseil_externe_entreprise != $this->companies->status_conseil_externe_entreprise || 
				$preciser_conseil_externe_entreprise != $this->companies->preciser_conseil_externe_entreprise
				)
				{
					
					
					
					$contenu = '<ul>';
					
					
					// entreprise
					if($name != $this->companies->name)
						$contenu .= '<li>Raison sociale</li>';
					if($forme != $this->companies->forme)
						$contenu .= '<li>Forme juridique</li>';
					if($capital != $this->companies->capital)
						$contenu .= '<li>Capital social</li>';
					if($siren != $this->companies->siren)
						$contenu .= '<li>SIREN</li>';
					if($phone != $this->companies->phone)
						$contenu .= '<li>Téléphone entreprise</li>';
					// adresse fiscale
					if($adresse_fiscal != $this->companies->adresse1)
						$contenu .= '<li>Adresse fiscale</li>';
					if($ville_fiscal != $this->companies->city)
						$contenu .= '<li>Ville fiscale</li>';
					if($cp_fiscal != $this->companies->zip)
						$contenu .= '<li>CP fiscal</li>';
					if($pays_fiscal != $this->companies->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>Pays fiscal</li>';
					// adresse client
					if($adresse1 != $this->clients_adresses->adresse1)
						$contenu .= '<li>Adresse</li>';
					if($ville != $this->clients_adresses->ville)
						$contenu .= '<li>Ville</li>';
					if($cp != $this->clients_adresses->cp)
						$contenu .= '<li>CP</li>';
					if($id_pays != $this->clients_adresses->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>Pays</li>';
					// coordonnées client
					if($civilite != $this->clients->civilite)
						$contenu .= '<li>Civilite</li>';
					if($nom != $this->clients->nom)
						$contenu .= '<li>Nom</li>';
					if($prenom != $this->clients->prenom)
						$contenu .= '<li>Prenom</li>';
					if($fonction != $this->clients->fonction)
						$contenu .= '<li>Fonction</li>';
					if($telephone != $this->clients->telephone)
						$contenu .= '<li>Telephone</li>';
					// coordonnées dirigeant si externe
					if($civilite_dirigeant != $this->companies->civilite_dirigeant)
						$contenu .= '<li>Civilité dirigeant</li>';
					if($nom_dirigeant != $this->companies->nom_dirigeant)
						$contenu .= '<li>Nom dirigeant</li>';
					if($prenom_dirigeant != $this->companies->prenom_dirigeant)
						$contenu .= '<li>Prenom dirigeant</li>';
					if($fonction_dirigeant != $this->companies->fonction_dirigeant)
						$contenu .= '<li>Fonction dirigeant</li>';
					if($email_dirigeant != $this->companies->email_dirigeant)
						$contenu .= '<li>Email dirigeant</li>';
					if($phone_dirigeant != $this->companies->phone_dirigeant)
						$contenu .= '<li>Telephone dirigeant</li>';
					
					if($status_conseil_externe_entreprise != $this->companies->status_conseil_externe_entreprise)
						$contenu .= '<li>Conseil externe</li>';
					if($preciser_conseil_externe_entreprise != $this->companies->preciser_conseil_externe_entreprise)
						$contenu .= '<li>Precision conseil externe</li>';
						
					$contenu .= '</ul>';
					
					if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
					else $statut_client = 50;
					
					// creation du statut "Modification"
					$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
					
					// destinataire
					$this->settings->get('Adresse notification modification preteur','type');
					$destinataire = $this->settings->value;
					
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
					
					/// mail nmp pour le preteur morale ///
					
					//************************************//
					//*** ENVOI DU MAIL GENERATION MDP ***//
					//************************************//
		
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-modification-compte','lang = "'.$this->language.'" AND type');
					
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					// Variables du mailing
					$varMail = array(
					'surl' =>  $this->surl,
					'url' => $this->lurl,
					'prenom' => $this->clients->prenom,
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
					////////////////////////////////
				}
				
				// si mail existe deja
				if($this->reponse_email != '') $_SESSION['reponse_email'] = $this->reponse_email;
								
				$_SESSION['reponse_profile_perso'] = $this->lng['profile']['titre-2'].' '.$this->lng['profile']['sauvegardees'];
				header('Location:'.$this->lurl.'/profile/societe');
				die;
			}
			
		}
		// Formulaire informations bancaires particulier
		elseif(isset($_POST['send_form_bank_societe'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(5,'info bank profile',$this->clients->id_client,$serialize);
			////////////////
			
			// carte-nationale-didentite dirigeant
			if(isset($_FILES['ci_dirigeant']) && $_FILES['ci_dirigeant']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/cni_passeport_dirigent/');
				if($this->upload->doUpload('ci_dirigeant')){
					if($this->lenders_accounts->fichier_cni_passeport_dirigent != '')@unlink($this->path.'protected/lenders/cni_passeport_dirigent/'.$this->lenders_accounts->fichier_cni_passeport_dirigent);
					$this->lenders_accounts->fichier_cni_passeport_dirigent = $this->upload->getName();
					$fichier_cni_passeport_dirigent = true;
				}
				else{
					$this->error_cni_dirigent = true;	
				}
			}
			// Extrait Kbis
			if(isset($_FILES['kbis']) && $_FILES['kbis']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/extrait_kbis/');
				if($this->upload->doUpload('kbis')){
					if($this->lenders_accounts->fichier_extrait_kbis != '')@unlink($this->path.'protected/companies/extrait_kbis/'.$this->lenders_accounts->fichier_extrait_kbis);
					$this->lenders_accounts->fichier_extrait_kbis = $this->upload->getName();
					$fichier_extrait_kbis = true;
				}
				else{
					$this->error_extrait_kbis = true;	
				}
			}
			// rib
			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/rib/');
				if($this->upload->doUpload('rib')){
					if($this->lenders_accounts->fichier_rib != '')@unlink($this->path.'protected/lenders/rib/'.$this->lenders_accounts->fichier_rib);
					$this->lenders_accounts->fichier_rib = $this->upload->getName();
					$fichier_rib = true;
				}
				else{
					$this->error_rib = true;	
				}
			}
			
			// autre
			if(isset($_FILES['autre']) && $_FILES['autre']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/autre/');
				if($this->upload->doUpload('autre')){
					if($this->lenders_accounts->fichier_autre != '')@unlink($this->path.'protected/lenders/autre/'.$this->lenders_accounts->fichier_autre);
					$this->lenders_accounts->fichier_autre = $this->upload->getName();
					$fichier_autre = true;
				}
				else{
					$this->error_autre = true;	
				}
			}
			// Délégation de pouvoir
			if(isset($_FILES['delegation_pouvoir']) && $_FILES['delegation_pouvoir']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/delegation_pouvoir/');
				if($this->upload->doUpload('delegation_pouvoir')){
					if($this->lenders_accounts->fichier_delegation_pouvoir != '')@unlink($this->path.'protected/companies/delegation_pouvoir/'.$this->lenders_accounts->fichier_delegation_pouvoir);
					$this->lenders_accounts->fichier_delegation_pouvoir = $this->upload->getName();
					$fichier_delegation_pouvoir = true;
				}
				else{
					$this->error_delegation_pouvoir = true;	
				}
			}	
				
			$bic_old = $this->lenders_accounts->bic;
			$iban_old = $this->lenders_accounts->iban;
			
			$this->lenders_accounts->bic = trim(strtoupper($_POST['bic']));
			$this->lenders_accounts->iban = '';
			for($i=1;$i<=7;$i++){ $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-'.$i])); }
			
			$origine_des_fonds_old = $this->lenders_accounts->origine_des_fonds;
			
			$this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
			if($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000)$this->lenders_accounts->precision = $_POST['preciser'];
			else $this->lenders_accounts->precision = '';
			
			
			$this->form_ok = true;
			$this->error_fichier = false;
			// CI
			if($this->lenders_accounts->fichier_cni_passeport_dirigent == '' || $this->error_cni_dirigent == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			// justificatif domicile
			if($this->lenders_accounts->fichier_extrait_kbis == '' || $this->error_extrait_kbis == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			// RIB
			if($this->lenders_accounts->fichier_rib == '' || $this->error_rib == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			if($this->error_autre == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			if($this->error_delegation_pouvoir == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			
			// on enregistre une partie pour avoir les images good ($this->error_cni_dirigent = dirigeant*)
				if($this->error_cni_dirigent == false || $this->error_extrait_kbis == false || $this->error_rib == false || $this->error_autre == false || $this->error_delegation_pouvoir == false){
					$this->lenders_accounts->update();	
				}
			
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
				// On met a jour le lender
				$this->lenders_accounts->update();
				
				// origine_des_fonds | BIC | IBAN
				if(
				$origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds || 
				$bic_old != $this->lenders_accounts->bic || 
				$iban_old != $this->lenders_accounts->iban || 
				$fichier_cni_passeport_dirigent == true || 
				$fichier_extrait_kbis == true || 
				$fichier_rib == true || 
				$fichier_autre == true ||
				$fichier_delegation_pouvoir == true
				)
				{

					$contenu = '<ul>'; 
					if($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds)
						$contenu .= '<li>Origine des fonds</li>';
					if($bic_old != $this->lenders_accounts->bic)
						$contenu .= '<li>BIC</li>';
					if($iban_old != $this->lenders_accounts->iban)
						$contenu .= '<li>IBAN</li>';
					if($fichier_cni_passeport_dirigent == true)
						$contenu .= '<li>Fichier cni passeport dirigent</li>';
					if($fichier_extrait_kbis == true)
						$contenu .= '<li>Fichier extrait kbis</li>';
					if($fichier_rib == true)
						$contenu .= '<li>Fichier RIB</li>';
					if($fichier_autre == true)
						$contenu .= '<li>Fichier autre</li>';
					if($fichier_delegation_pouvoir == true)
						$contenu .= '<li>Fichier delegation de pouvoir</li>';
					
					$contenu .= '</ul>';
					
					// 40 : Complétude (Réponse)
					if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
					else $statut_client = 50; // 50 : Modification
					
					// creation du statut "Modification"
					$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
					
					// destinataire
					$this->settings->get('Adresse notification modification preteur','type');
					$destinataire = $this->settings->value;
					
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
					
					/// mail nmp pour le preteur morale ///
					
					//************************************//
					//*** ENVOI DU MAIL GENERATION MDP ***//
					//************************************//
		
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-modification-compte','lang = "'.$this->language.'" AND type');
					
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					// Variables du mailing
					$varMail = array(
					'surl' =>  $this->surl,
					'url' => $this->lurl,
					'prenom' => $this->clients->prenom,
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
					////////////////////////////////
					
					$_SESSION['reponse_profile_bank'] = $this->lng['profile']['titre-2'].' '.$this->lng['profile']['sauvegardees'];
					
					header('Location:'.$this->lurl.'/profile/societe/2');
					die;
				}
				
			}
			
		}
		// formulaire particulier secu
		elseif(isset($_POST['send_form_mdp'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'newmdp' => md5($_POST['passNew']),'question' => $_POST['question'],'reponse' => md5($_POST['reponse'])));
			$this->clients_history_actions->histo(7,'change mdp',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			// old mdp
			if(!isset($_POST['passOld']) || $_POST['passOld'] == '' || $_POST['passOld'] == $this->lng['etape1']['ancien-mot-de-passe']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['passOld']) && md5($_POST['passOld']) != $this->clients->password){
				$this->form_ok = false;
				;
				echo $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
				header('Location:'.$this->lurl.'/profile/societe/3');
				die;
			}
			
			// new pass
			if(!isset($_POST['passNew']) || $_POST['passNew'] == '' || $_POST['passNew'] == $this->lng['etape1']['nouveau-mot-de-passe']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['passNew']) && $this->ficelle->password_fo($_POST['passNew'],6) == false){
				$this->form_ok = false;
			}
			
			// confirmation new pass
			if(!isset($_POST['passNew2']) || $_POST['passNew2'] == '' || $_POST['passNew2'] == $this->lng['etape1']['confirmation-nouveau-mot-de-passe']){
				$this->form_ok = false;
			}
			// check new pass != de confirmation
			if(isset($_POST['passNew']) && isset($_POST['passNew2']) && $_POST['passNew'] != $_POST['passNew2']){
				$this->form_ok = false;
			}
			
			// si good
			if($this->form_ok == true){
				
				$this->clients->password = md5($_POST['passNew']);
				$_SESSION['client']['password'] = $this->clients->password;
				
				// question / reponse
				if(isset($_POST['question']) && isset($_POST['reponse']) && $_POST['question'] != '' && $_POST['reponse'] != '' && $_POST['question'] != $this->lng['etape1']['question-secrete'] && $_POST['reponse'] != $this->lng['etape1']['question-reponse'])
				{
					$this->clients->secrete_question = $_POST['question'];
					$this->clients->secrete_reponse = md5($_POST['reponse']);
				}
				
				$this->clients->update();
				
				//************************************//
				//*** ENVOI DU MAIL GENERATION MDP ***//
				//************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('generation-mot-de-passe','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				$login = $this->clients->email;
				
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
				'login' => $login,
				'prenom_p' => $this->clients->prenom,
				'mdp' => '',
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
				
				$_SESSION['reponse_profile_secu'] = $this->lng['profile']['votre-mot-de-passe-a-bien-ete-change'];
					
				header('Location:'.$this->lurl.'/profile/societe/3');
				die;
			
			}
			else{
				header('Location:'.$this->lurl.'/profile/societe/3');
				die;	
			}
		}
		
		
	}
	
	function _societe()
	{
		// Particulier (si on est pas sur la bonne page)
		if(in_array($this->clients->type,array(1,3))){
			header('Location:'.$this->lurl.'/profile/particulier');
			die;
		}
		
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
	
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
		//$this->pays = $this->loadData('pays');
		$this->pays = $this->loadData('pays_v2');
		//$this->nationalites = $this->loadData('nationalites');
		$this->nationalites = $this->loadData('nationalites_v2');
		$this->companies = $this->loadData('companies');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		// Liste des pays
		$this->lPays = $this->pays->select('','ordre ASC');
		
		// Liste deroulante conseil externe de l'entreprise
		$this->settings->get("Liste deroulante conseil externe de l'entreprise",'type');
		$this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);
		
		// On recup le preteur
		$this->companies->get($this->clients->id_client,'id_client_owner');
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		// Liste deroulante origine des fonds entreprise
		$this->settings->get("Liste deroulante origine des fonds societe",'status = 1 AND type');
		$this->origine_fonds_E = explode(';',$this->settings->value);
		
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
		
		// form info perso
		if(isset($_POST['send_form_societe_perso'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(4,'info perso profile',$this->clients->id_client,$serialize);
			////////////////
			
			// on met ca de coté
			$this->email_temp = $this->clients->email;
			
			$this->form_ok = true;
			
			
			$name 		= $this->companies->name;
			$forme 		= $this->companies->forme;
			$capital 	= $this->companies->capital;
			$siren 		= $this->companies->siren;
			$siret 		= $this->companies->siret;
			$phone 		= $this->companies->phone;
			
			$this->companies->name 		= $_POST['raison_sociale_inscription'];
			$this->companies->forme 	= $_POST['forme_juridique_inscription'];
			$this->companies->capital 	= str_replace(' ','',$_POST['capital_social_inscription']);
			$this->companies->siret 	= $_POST['siret_inscription'];
			$this->companies->siren 	= $_POST['siren_inscription'];
			//$this->companies->siren 	= substr($this->companies->siret,0,9);
			$this->companies->phone 	= str_replace(' ','',$_POST['phone_inscription']);
			

			////////////////////////////////////
			// On verifie meme adresse ou pas //
			////////////////////////////////////
			if($_POST['mon-addresse'] != false)
			$this->companies->status_adresse_correspondance = '1'; // la meme
			else
			$this->companies->status_adresse_correspondance = '0'; // pas la meme
			
			// adresse fiscale
			$adresse_fiscal = $this->companies->adresse1;
			$ville_fiscal = $this->companies->city;
			$cp_fiscal = $this->companies->zip;
			$pays_fiscal = $this->companies->id_pays;
			// adresse client
			$adresse1 = $this->clients_adresses->adresse1;
			$ville = $this->clients_adresses->ville;
			$cp = $this->clients_adresses->cp;
			$id_pays = $this->clients_adresses->id_pays;
			
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
			
			$civilite = $this->clients->civilite;
			$nom = $this->clients->nom;
			$prenom = $this->clients->prenom;
			$fonction = $this->clients->fonction;
			$telephone = $this->clients->telephone;
			
			$this->clients->civilite = $_POST['genre1'];
			$this->clients->nom = $this->ficelle->majNom($_POST['nom_inscription']);
			$this->clients->prenom = $this->ficelle->majNom($_POST['prenom_inscription']);
			$this->clients->fonction = $_POST['fonction_inscription'];
			$this->clients->telephone = str_replace(' ','',$_POST['phone_new_inscription']);
			
			$civilite_dirigeant = $this->companies->civilite_dirigeant;
			$nom_dirigeant = $this->companies->nom_dirigeant;
			$prenom_dirigeant = $this->companies->prenom_dirigeant;
			$fonction_dirigeant = $this->companies->fonction_dirigeant;
			$email_dirigeant = $this->companies->email_dirigeant;
			$phone_dirigeant = $this->companies->phone_dirigeant;
			
			$status_conseil_externe_entreprise = $this->companies->status_conseil_externe_entreprise;
			$preciser_conseil_externe_entreprise = $this->companies->preciser_conseil_externe_entreprise;
			
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
			//siret_inscription
			if(!isset($_POST['siret_inscription']) || $_POST['siret_inscription'] == $this->lng['etape1']['siret']){
				$this->form_ok = false;
			}
			//siret_inscription
			if(!isset($_POST['siren_inscription']) || $_POST['siren_inscription'] == $this->lng['etape1']['siren']){
				$this->form_ok = false;
			}
			
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
				// et si l'email n'est pas celle du client
				if($_POST['email_inscription'] != $this->email_temp){
					// check si l'adresse mail est deja utilisé
					$this->reponse_email = $this->lng['etape1']['erreur-email'];
				}
				else $this->clients->email = $_POST['email_inscription'];
			}
			else $this->clients->email = $_POST['email_inscription'];
			
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
					/*if(!isset($_POST['autre_inscription']) || $_POST['autre_inscription'] == $this->lng['etape1']['autre']){
						$this->form_ok = false;
					}*/	
				}
			}
			
			
			/////////////////// PARTIE BANQUE /////////////////////////
			
			
			// carte-nationale-didentite dirigeant
			if(isset($_FILES['ci_dirigeant']) && $_FILES['ci_dirigeant']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/cni_passeport_dirigent/');
				if($this->upload->doUpload('ci_dirigeant')){
					if($this->lenders_accounts->fichier_cni_passeport_dirigent != '')@unlink($this->path.'protected/lenders/cni_passeport_dirigent/'.$this->lenders_accounts->fichier_cni_passeport_dirigent);
					$this->lenders_accounts->fichier_cni_passeport_dirigent = $this->upload->getName();
					$fichier_cni_passeport_dirigent = true;
				}
				else{
					$this->error_cni_dirigent = true;	
				}
			}
			// Extrait Kbis
			if(isset($_FILES['kbis']) && $_FILES['kbis']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/extrait_kbis/');
				if($this->upload->doUpload('kbis')){
					if($this->lenders_accounts->fichier_extrait_kbis != '')@unlink($this->path.'protected/companies/extrait_kbis/'.$this->lenders_accounts->fichier_extrait_kbis);
					$this->lenders_accounts->fichier_extrait_kbis = $this->upload->getName();
					$fichier_extrait_kbis = true;
				}
				else{
					$this->error_extrait_kbis = true;	
				}
			}
			// rib
			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/rib/');
				if($this->upload->doUpload('rib')){
					if($this->lenders_accounts->fichier_rib != '')@unlink($this->path.'protected/lenders/rib/'.$this->lenders_accounts->fichier_rib);
					$this->lenders_accounts->fichier_rib = $this->upload->getName();
					$fichier_rib = true;
				}
				else{
					$this->error_rib = true;	
				}
			}
			
			// autre
			if(isset($_FILES['autre']) && $_FILES['autre']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/autre/');
				if($this->upload->doUpload('autre')){
					if($this->lenders_accounts->fichier_autre != '')@unlink($this->path.'protected/lenders/autre/'.$this->lenders_accounts->fichier_autre);
					$this->lenders_accounts->fichier_autre = $this->upload->getName();
					$fichier_autre = true;
				}
				else{
					$this->error_autre = true;	
				}
			}
			// Délégation de pouvoir
			if(isset($_FILES['delegation_pouvoir']) && $_FILES['delegation_pouvoir']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/delegation_pouvoir/');
				if($this->upload->doUpload('delegation_pouvoir')){
					if($this->lenders_accounts->fichier_delegation_pouvoir != '')@unlink($this->path.'protected/companies/delegation_pouvoir/'.$this->lenders_accounts->fichier_delegation_pouvoir);
					$this->lenders_accounts->fichier_delegation_pouvoir = $this->upload->getName();
					$fichier_delegation_pouvoir = true;
				}
				else{
					$this->error_delegation_pouvoir = true;	
				}
			}	
				
			$bic_old = $this->lenders_accounts->bic;
			$iban_old = $this->lenders_accounts->iban;
			
			$this->lenders_accounts->bic = trim(strtoupper($_POST['bic']));
			$this->lenders_accounts->iban = '';
			for($i=1;$i<=7;$i++){ $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-'.$i])); }
			
			$origine_des_fonds_old = $this->lenders_accounts->origine_des_fonds;
			
			$this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
			if($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000)$this->lenders_accounts->precision = $_POST['preciser'];
			else $this->lenders_accounts->precision = '';
			
			
			$this->form_ok = true;
			$this->error_fichier = false;
			// CI
			if($this->lenders_accounts->fichier_cni_passeport_dirigent == '' || $this->error_cni_dirigent == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			// justificatif domicile
			if($this->lenders_accounts->fichier_extrait_kbis == '' || $this->error_extrait_kbis == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			// RIB
			if($this->lenders_accounts->fichier_rib == '' || $this->error_rib == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			if($this->error_autre == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			if($this->error_delegation_pouvoir == true){
				$this->form_ok = false;
				$this->error_fichier = true;
			}
			
			// on enregistre une partie pour avoir les images good ($this->error_cni_dirigent = dirigeant*)
				if($this->error_cni_dirigent == false || $this->error_extrait_kbis == false || $this->error_rib == false || $this->error_autre == false || $this->error_delegation_pouvoir == false){
					$this->lenders_accounts->update();	
				}
			
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
			
			
			///////////////////////////////////////////////////////////
			
			
			
			// Formulaire societe ok
			if($this->form_ok == true)
			{
				$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);
				
				
				$this->clients->update();
				$this->clients_adresses->update();
				$this->companies->update();
				$this->lenders_accounts->update();
				
				$dateDepartControlPays = strtotime('2014-07-31 18:00:00');
				
				// on envoie un mail notifiaction si infos fiscale modifiés
				if(
				$adresse_fiscal != $this->companies->adresse1 || 
				$ville_fiscal != $this->companies->city || 
				$cp_fiscal != $this->companies->zip || 
				$pays_fiscal != $this->companies->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays || 
				$name != $this->companies->name || 
				$forme != $this->companies->forme || 
				$capital != $this->companies->capital ||  
				$siret != $this->companies->siret || 
				$siren != $this->companies->siren || 
//				$phone != $this->companies->phone || 
				//$adresse1 != $this->clients_adresses->adresse1 || 
//				$ville != $this->clients_adresses->ville || 
//				$cp != $this->clients_adresses->cp || 
//				$id_pays != $this->clients_adresses->id_pays || 
//				$civilite != $this->clients->civilite || 
				$nom != $this->clients->nom || 
				$prenom != $this->clients->prenom || 
//				$fonction != $this->clients->fonction || 
//				$telephone != $this->clients->telephone || 
//				$civilite_dirigeant != $this->companies->civilite_dirigeant || 
				$nom_dirigeant != $this->companies->nom_dirigeant || 
				$prenom_dirigeant != $this->companies->prenom_dirigeant || 
//				$fonction_dirigeant != $this->companies->fonction_dirigeant || 
//				$email_dirigeant != $this->companies->email_dirigeant || 
//				$phone_dirigeant != $this->companies->phone_dirigeant || 
				$status_conseil_externe_entreprise != $this->companies->status_conseil_externe_entreprise || 
				$preciser_conseil_externe_entreprise != $this->companies->preciser_conseil_externe_entreprise ||
				$origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds || 
				$bic_old != $this->lenders_accounts->bic || 
				$iban_old != $this->lenders_accounts->iban || 
				$fichier_cni_passeport_dirigent == true || 
				$fichier_extrait_kbis == true || 
				$fichier_rib == true || 
				$fichier_autre == true ||
				$fichier_delegation_pouvoir == true
				)
				{
					
					
					
					$contenu = '<ul>';
					
					
					// entreprise
					if($name != $this->companies->name)
						$contenu .= '<li>Raison sociale</li>';
					if($forme != $this->companies->forme)
						$contenu .= '<li>Forme juridique</li>';
					if($capital != $this->companies->capital)
						$contenu .= '<li>Capital social</li>';
					if($siret != $this->companies->siret)
						$contenu .= '<li>SIRET</li>';
					if($siren != $this->companies->siren)
						$contenu .= '<li>SIREN</li>';
					if($phone != $this->companies->phone)
						$contenu .= '<li>Téléphone entreprise</li>';
					// adresse fiscale
					if($adresse_fiscal != $this->companies->adresse1)
						$contenu .= '<li>Adresse fiscale</li>';
					if($ville_fiscal != $this->companies->city)
						$contenu .= '<li>Ville fiscale</li>';
					if($cp_fiscal != $this->companies->zip)
						$contenu .= '<li>CP fiscal</li>';
					if($pays_fiscal != $this->companies->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>Pays fiscal</li>';
					// adresse client
					if($adresse1 != $this->clients_adresses->adresse1)
						$contenu .= '<li>Adresse</li>';
					if($ville != $this->clients_adresses->ville)
						$contenu .= '<li>Ville</li>';
					if($cp != $this->clients_adresses->cp)
						$contenu .= '<li>CP</li>';
					if($id_pays != $this->clients_adresses->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays)
						$contenu .= '<li>Pays</li>';
					// coordonnées client
					if($civilite != $this->clients->civilite)
						$contenu .= '<li>Civilite</li>';
					if($nom != $this->clients->nom)
						$contenu .= '<li>Nom</li>';
					if($prenom != $this->clients->prenom)
						$contenu .= '<li>Prenom</li>';
					if($fonction != $this->clients->fonction)
						$contenu .= '<li>Fonction</li>';
					if($telephone != $this->clients->telephone)
						$contenu .= '<li>Telephone</li>';
					// coordonnées dirigeant si externe
					if($civilite_dirigeant != $this->companies->civilite_dirigeant)
						$contenu .= '<li>Civilité dirigeant</li>';
					if($nom_dirigeant != $this->companies->nom_dirigeant)
						$contenu .= '<li>Nom dirigeant</li>';
					if($prenom_dirigeant != $this->companies->prenom_dirigeant)
						$contenu .= '<li>Prenom dirigeant</li>';
					if($fonction_dirigeant != $this->companies->fonction_dirigeant)
						$contenu .= '<li>Fonction dirigeant</li>';
					if($email_dirigeant != $this->companies->email_dirigeant)
						$contenu .= '<li>Email dirigeant</li>';
					if($phone_dirigeant != $this->companies->phone_dirigeant)
						$contenu .= '<li>Telephone dirigeant</li>';
					
					if($status_conseil_externe_entreprise != $this->companies->status_conseil_externe_entreprise)
						$contenu .= '<li>Conseil externe</li>';
					if($preciser_conseil_externe_entreprise != $this->companies->preciser_conseil_externe_entreprise)
						$contenu .= '<li>Precision conseil externe</li>';
					
					/////////// PARTIE BANQUE ////////
					
					if($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds)
						$contenu .= '<li>Origine des fonds</li>';
					if($bic_old != $this->lenders_accounts->bic)
						$contenu .= '<li>BIC</li>';
					if($iban_old != $this->lenders_accounts->iban)
						$contenu .= '<li>IBAN</li>';
					if($fichier_cni_passeport_dirigent == true)
						$contenu .= '<li>Fichier cni passeport dirigent</li>';
					if($fichier_extrait_kbis == true)
						$contenu .= '<li>Fichier extrait kbis</li>';
					if($fichier_rib == true)
						$contenu .= '<li>Fichier RIB</li>';
					if($fichier_autre == true)
						$contenu .= '<li>Fichier autre</li>';
					if($fichier_delegation_pouvoir == true)
						$contenu .= '<li>Fichier delegation de pouvoir</li>';
					
					//////////////////////////////////
					
					
					
					$contenu .= '</ul>';
					
					if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
					else $statut_client = 50;
					
					// creation du statut "Modification"
					$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
					
					// destinataire
					$this->settings->get('Adresse notification modification preteur','type');
					$destinataire = $this->settings->value;
					
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
					
					/// mail nmp pour le preteur morale ///
					
					//************************************//
					//*** ENVOI DU MAIL GENERATION MDP ***//
					//************************************//
		
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-modification-compte','lang = "'.$this->language.'" AND type');
					
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					// Variables du mailing
					$varMail = array(
					'surl' =>  $this->surl,
					'url' => $this->lurl,
					'prenom' => $this->clients->prenom,
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
					////////////////////////////////
				}
				
				// si mail existe deja
				if($this->reponse_email != '') $_SESSION['reponse_email'] = $this->reponse_email;
								
				$_SESSION['reponse_profile_perso'] = $this->lng['profile']['sauvegardees'];
				header('Location:'.$this->lurl.'/profile/societe/3');
				die;
			}
			
		}
		// formulaire particulier secu
		elseif(isset($_POST['send_form_mdp'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'newmdp' => md5($_POST['passNew']),'question' => $_POST['question'],'reponse' => md5($_POST['reponse'])));
			$this->clients_history_actions->histo(7,'change mdp',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			// old mdp
			if(!isset($_POST['passOld']) || $_POST['passOld'] == '' || $_POST['passOld'] == $this->lng['etape1']['ancien-mot-de-passe']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['passOld']) && md5($_POST['passOld']) != $this->clients->password){
				$this->form_ok = false;
				;
				echo $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
				header('Location:'.$this->lurl.'/profile/societe/3');
				die;
			}
			
			// new pass
			if(!isset($_POST['passNew']) || $_POST['passNew'] == '' || $_POST['passNew'] == $this->lng['etape1']['nouveau-mot-de-passe']){
				$this->form_ok = false;
			}
			elseif(isset($_POST['passNew']) && $this->ficelle->password_fo($_POST['passNew'],6) == false){
				$this->form_ok = false;
			}
			
			// confirmation new pass
			if(!isset($_POST['passNew2']) || $_POST['passNew2'] == '' || $_POST['passNew2'] == $this->lng['etape1']['confirmation-nouveau-mot-de-passe']){
				$this->form_ok = false;
			}
			// check new pass != de confirmation
			if(isset($_POST['passNew']) && isset($_POST['passNew2']) && $_POST['passNew'] != $_POST['passNew2']){
				$this->form_ok = false;
			}
			
			// si good
			if($this->form_ok == true){
				
				$this->clients->password = md5($_POST['passNew']);
				$_SESSION['client']['password'] = $this->clients->password;
				
				// question / reponse
				if(isset($_POST['question']) && isset($_POST['reponse']) && $_POST['question'] != '' && $_POST['reponse'] != '' && $_POST['question'] != $this->lng['etape1']['question-secrete'] && $_POST['reponse'] != $this->lng['etape1']['question-reponse'])
				{
					$this->clients->secrete_question = $_POST['question'];
					$this->clients->secrete_reponse = md5($_POST['reponse']);
				}
				
				$this->clients->update();
				
				//************************************//
				//*** ENVOI DU MAIL GENERATION MDP ***//
				//************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('generation-mot-de-passe','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				$login = $this->clients->email;
				
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
				'login' => $login,
				'prenom_p' => $this->clients->prenom,
				'mdp' => '',
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
				
				$_SESSION['reponse_profile_secu'] = $this->lng['profile']['votre-mot-de-passe-a-bien-ete-change'];
					
				header('Location:'.$this->lurl.'/profile/societe/2');
				die;
			
			}
			else{
				header('Location:'.$this->lurl.'/profile/societe/2');
				die;	
			}
		}
		
		
	}
	
	
	function _default_old()
	{
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
		
		// Chargement des datas
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->loans = $this->loadData('loans');
		$this->projects = $this->loadData('projects');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->transactions = $this->loadData('transactions');
		$this->clients_mandats = $this->loadData('clients_mandats');
		
		
		// Liste deroulante origine des fonds
		$this->settings->get("Liste deroulante origine des fonds",'type');
		$this->origine_fonds = $this->settings->value;
		$this->origine_fonds = explode(';',$this->origine_fonds);
		
		// message si email existe deja
		if(isset($_SESSION['reponse_email']) && $_SESSION['reponse_email'] != '')
		{
			$this->reponse_email = $_SESSION['reponse_email'];
			unset($_SESSION['reponse_email']);
		}
		if(isset($_SESSION['reponse_age']) && $_SESSION['reponse_age'] != '')
		{
			$this->reponse_age = $_SESSION['reponse_age'];
			unset($_SESSION['reponse_age']);
		}
		////////////////
		// info perso //
		////////////////
		
		
		// Liste deroulante conseil externe de l'entreprise
		$this->settings->get("Liste deroulante conseil externe de l'entreprise",'type');
		$this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);
		
		// On recup les adresses
		$this->clients_adresses->get($this->clients->id_client,'id_client');
		
		// on recup le lender
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// dans le cas d'une societe
		if($this->clients->type == 2)
		{
			//On recup la societe
			$this->companies->get($this->clients->id_client,'id_client_owner');
		}
		elseif($this->clients->type == 1)
		{
			$nais = explode('-',$this->clients->naissance);	
			$this->jour = $nais[2];
			$this->mois = $nais[1];
			$this->annee = $nais[0];
		}
		
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

		$this->email = $this->clients->email;
		
		
		
		
		if(isset($_POST['form_send_mandat']))
		{
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(4,'info perso profile',$this->clients->id_client,$serialize);
			////////////////
			
			$this->upload_mandat = false;
			// mandat
			if(isset($_FILES['mandat']) && $_FILES['mandat']['name'] != '')
			{
				if($this->clients_mandats->get($this->clients->id_client,'id_client'))$create = false;
				else $create = true;
				
				$this->upload->setUploadDir($this->path,'protected/mandats/');
				if($this->upload->doUpload('mandat'))
				{
					if($this->clients_mandats->name != '')@unlink($this->path.'protected/mandats/'.$this->clients_mandats->name);
					$this->clients_mandats->name = $this->upload->getName();
				}
				
				$this->clients_mandats->id_client = $this->clients->id_client;
				
				if($create == true)$this->clients_mandats->create();
				else $this->clients_mandats->update();
						
				$this->upload_mandat = true;
				
			}
		}
		
		if($this->clients->telephone != '')$this->clients->telephone = trim(chunk_split($this->clients->telephone, 2,' '));
		if($this->companies->phone != '')$this->companies->phone = trim(chunk_split($this->companies->phone, 2,' '));
		if($this->companies->phone_dirigeant != '') $this->companies->phone_dirigeant = trim(chunk_split($this->companies->phone_dirigeant, 2,' '));
		
			
		if(isset($_POST['send_form_etape1']))
		{
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(4,'info perso profile',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			// particulier
			if($_POST['send_form_etape1'] == 1)
			{
				////////////////////////////////////
				// On verifie meme adresse ou pas //
				////////////////////////////////////
				if($_POST['mon-addresse'] != false)
				$this->clients_adresses->meme_adresse_fiscal = 1; // la meme
				else
				$this->clients_adresses->meme_adresse_fiscal = 0; // pas la meme
				
				// adresse fiscal
				
				$adresse_fiscal = $this->clients_adresses->adresse_fiscal;
				$ville_fiscal = $this->clients_adresses->ville_fiscal;
				$cp_fiscal = $this->clients_adresses->cp_fiscal;
				
				$this->clients_adresses->adresse_fiscal = $_POST['adresse_inscription'];
				$this->clients_adresses->ville_fiscal = $_POST['ville_inscription'];
				$this->clients_adresses->cp_fiscal = $_POST['postal'];
				
				// pas la meme
				if($this->clients_adresses->meme_adresse_fiscal == 0)
				{
					// adresse client
					$this->clients_adresses->adresse1 = $_POST['adress2'];
					$this->clients_adresses->ville = $_POST['ville2'];
					$this->clients_adresses->cp = $_POST['postal2'];
				}
				// la meme
				else
				{
					// adresse client
					$this->clients_adresses->adresse1 = $_POST['adresse_inscription'];
					$this->clients_adresses->ville = $_POST['ville_inscription'];
					$this->clients_adresses->cp = $_POST['postal'];
				}
				////////////////////////////////////////
				
				$this->clients->civilite = $_POST['sex'];
				$this->clients->nom = $this->ficelle->majNom($_POST['nom-famille']);
				$this->clients->nom_usage = $this->ficelle->majNom($_POST['nom-dusage']);
				$this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
				$this->clients->email = $_POST['email'];

				/*$this->clients->secrete_question = $_POST['secret-question'];
				$this->clients->secrete_reponse = $_POST['secret-response'];*/
				
				$this->clients->telephone = str_replace(' ','',$_POST['phone']);
				$this->clients->ville_naissance = $_POST['naissance'];
				$this->clients->naissance = $_POST['annee_naissance'].'-'.$_POST['mois_naissance'].'-'.$_POST['jour_naissance'];
				
				// age 
				if($this->dates->ageplus18($this->clients->naissance) == false)
				{
					$this->form_ok = false;
					$_SESSION['reponse_age'] = $this->lng['etape1']['erreur-age'];
				}
				
				//nom-famille
				if(!isset($_POST['nom-famille']) || $_POST['nom-famille'] == $this->lng['etape1']['nom-de-famille'])
				{
					$this->form_ok = false;
				}
				//nom-dusage
				if(!isset($_POST['nom-dusage']) || $_POST['nom-dusage'] == $this->lng['etape1']['nom-dusage'])
				{
					//$this->form_ok = false;
				}
				//prenom
				if(!isset($_POST['prenom']) || $_POST['prenom'] == $this->lng['etape1']['prenom'])
				{
					$this->form_ok = false;
				}
				//email
				if(!isset($_POST['email']) || $_POST['email'] == $this->lng['etape1']['email'])
				{
					$this->form_ok = false;
				}
				elseif(isset($_POST['email']) && $this->ficelle->isEmail($_POST['email']) == false)
				{
					$this->form_ok = false;
				}
				elseif($_POST['email'] != $_POST['conf_email'])
				{
					$this->form_ok = false;
				}
				elseif($this->clients->existEmail($_POST['email']) == false)
				{
					// et si l'email n'est pas celle du client
					if($_POST['email'] != $this->email)
					{
						// check si l'adresse mail est deja utilisé
						$this->reponse_email = $this->lng['etape1']['erreur-email'];
					}
				}
				//adresse_inscription
				if(!isset($_POST['adresse_inscription']) || $_POST['adresse_inscription'] == $this->lng['etape1']['adresse'])
				{
					$this->form_ok = false;
				}
				//ville_inscription
				if(!isset($_POST['ville_inscription']) || $_POST['ville_inscription'] == $this->lng['etape1']['ville'])
				{
					$this->form_ok = false;
				}
				//postal
				if(!isset($_POST['postal']) || $_POST['postal'] == $this->lng['etape1']['code-postal'])
				{
					$this->form_ok = false;
				}
				
				// pas la meme
				if($this->clients_adresses->meme_adresse_fiscal == 0)
				{
					// adresse client
					if(!isset($_POST['adress2']) || $_POST['adress2'] == $this->lng['etape1']['adresse'])
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['ville2']) || $_POST['ville2'] == $this->lng['etape1']['ville'])
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['postal2']) || $_POST['postal2'] == $this->lng['etape1']['postal'])
					{
						$this->form_ok = false;
					}
				}
				
				// si form particulier ok
				if($this->form_ok == true)
				{
					$this->clients->id_langue = 'fr';
					$this->clients->type = 1;
					$this->clients->fonction = '';
					$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);
					
					// On passe a zero le id company dans lender
					$this->lenders_accounts->id_company_owner = 0;
					
					// Si mail existe deja
					if($this->reponse_email != '')
					{
						$this->clients->email = $this->email;
					}
					
					// mise a jour
					$this->clients->update();
					$this->clients_adresses->update();
					$this->lenders_accounts->update();
					
					// Si on a une entreprise reliée, on la supprime car elle n'a plus rien a faire ici. on est un particulier.
					if($this->companies->get($this->clients->id_client,'id_client_owner'))
					{
						$this->companies->delete($this->companies->id_company,'id_company');
					}
					
					// On recup le client
					$this->clients->get($this->clients->id_client,'id_client');
					
					// si email pas ok
					if($this->reponse_email != '') $_SESSION['reponse_email'] = $this->reponse_email;
					
					
					// on envoie un mail notifiaction si infos fiscale modifiés
					if($adresse_fiscal != $this->clients_adresses->adresse_fiscal || $ville_fiscal != $this->clients_adresses->ville_fiscal || $cp_fiscal != $this->clients_adresses->cp_fiscal)
					{
						$contenu = '<ul>';
						
						if($adresse_fiscal != $this->clients_adresses->adresse_fiscal)
							$contenu .= '<li>adresse fiscal</li>';
						if($ville_fiscal != $this->clients_adresses->ville_fiscal)
							$contenu .= '<li>ville fiscal</li>';
						if($cp_fiscal != $this->clients_adresses->cp_fiscal)
							$contenu .= '<li>cp fiscal</li>';
							
						$contenu .= '</ul>';
						
						// destinataire
						$this->settings->get('Adresse notification changement informations bancaires','type');
						$destinataire = $this->settings->value;
						//$destinataire = 'd.courtier@relance.fr';
						
						// mail notification
						
						// Recuperation du modele de mail
						$this->mails_text->get('notification-modification-informations-bancaires','lang = "'.$this->language.'" AND type');
						
						// Variables du mailing
						$surl = $this->surl;
						$url = $this->lurl;
						$id_client = $this->clients->id_client;
						$email_client = $this->clients->email;
						
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
						//$this->email->addBCCRecipient('d.courtier@relance.fr');
					
						$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
						$this->email->setHTMLBody($texteMail);
						Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
						// fin mail
					}
					
					
					header('location:'.$this->lurl.'/profile/');
        			die;
				}
				// si form pas ok
				else
				{
					header('location:'.$this->lurl.'/profile/');
        			die;
				}
				
			}
			
			
			// Societe
			elseif($_POST['send_form_etape1'] == 2)
			{
				$this->companies->name = $_POST['raison_sociale_inscription'];
				$this->companies->forme = $_POST['forme_juridique_inscription'];
				$this->companies->capital = str_replace(' ','',$_POST['capital_social_inscription']);
				$this->companies->siren = $_POST['siren_inscription'];
				$this->companies->phone = str_replace(' ','',$_POST['phone_inscription']);
				
				////////////////////////////////////
				// On verifie meme adresse ou pas //
				////////////////////////////////////
				if($_POST['mon-addresse'] != false)
				$this->companies->status_adresse_correspondance = '1'; // la meme
				else
				$this->companies->status_adresse_correspondance = '0'; // pas la meme
				
				// adresse fiscal (siege de l'entreprise)
				$this->companies->adresse1 = $_POST['adresse_inscription'];
				$this->companies->city = $_POST['ville_inscription'];
				$this->companies->zip = $_POST['postal'];
				
				// pas la meme
				if($this->companies->status_adresse_correspondance == 0)
				{
					// adresse client
					$this->clients_adresses->adresse1 = $_POST['adress2'];
					$this->clients_adresses->ville = $_POST['ville2'];
					$this->clients_adresses->cp = $_POST['postal2'];
				}
				// la meme
				else
				{
					// adresse client
					$this->clients_adresses->adresse1 = $_POST['adresse_inscription'];
					$this->clients_adresses->ville = $_POST['ville_inscription'];
					$this->clients_adresses->cp = $_POST['postal'];
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
				if($this->companies->status_client == 2 || $this->companies->status_client == 3)
				{
					$this->companies->civilite_dirigeant = $_POST['genre2'];
					$this->companies->nom_dirigeant = $this->ficelle->majNom($_POST['nom2_inscription']);
					$this->companies->prenom_dirigeant = $this->ficelle->majNom($_POST['prenom2_inscription']);
					$this->companies->fonction_dirigeant = $_POST['fonction2_inscription'];
					$this->companies->email_dirigeant = $_POST['email2_inscription'];
					$this->companies->phone_dirigeant = str_replace(' ','',$_POST['phone_new2_inscription']);
					
					// externe
					if($this->companies->status_client == 3)
					{
						$this->companies->status_conseil_externe_entreprise = $_POST['external-consultant'];
						$this->companies->preciser_conseil_externe_entreprise = $_POST['autre_inscription'];
					}
				}
				//raison_sociale_inscription
				if(!isset($_POST['raison_sociale_inscription']) || $_POST['raison_sociale_inscription'] == $this->lng['etape1']['raison-sociale'])
				{
					$this->form_ok = false;
				}
				//forme_juridique_inscription
				if(!isset($_POST['forme_juridique_inscription']) || $_POST['forme_juridique_inscription'] == $this->lng['etape1']['forme-juridique'])
				{
					$this->form_ok = false;
				}
				//capital_social_inscription
				if(!isset($_POST['capital_social_inscription']) || $_POST['capital_social_inscription'] == $this->lng['etape1']['capital-sociale'])
				{
					$this->form_ok = false;
				}
				//siren_inscription
				if(!isset($_POST['siren_inscription']) || $_POST['siren_inscription'] == $this->lng['etape1']['siren'])
				{
					$this->form_ok = false;
				}
				//phone_inscription
				if(!isset($_POST['phone_inscription']) || $_POST['phone_inscription'] == $this->lng['etape1']['telephone'])
				{
					$this->form_ok = false;
				}
				//adresse_inscription
				if(!isset($_POST['adresse_inscription']) || $_POST['adresse_inscription'] == $this->lng['etape1']['adresse'])
				{
					$this->form_ok = false;
				}
				//ville_inscription
				if(!isset($_POST['ville_inscription']) || $_POST['ville_inscription'] == $this->lng['etape1']['ville'])
				{
					$this->form_ok = false;
				}
				//postal
				if(!isset($_POST['postal']) || $_POST['postal'] == $this->lng['etape1']['code-postal'])
				{
					$this->form_ok = false;
				}
				
				// pas la meme
				if($this->companies->status_adresse_correspondance == 0)
				{
					// adresse client
					if(!isset($_POST['adress2']) || $_POST['adress2'] == $this->lng['etape1']['adresse'])
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['ville2']) || $_POST['ville2'] == $this->lng['etape1']['ville'])
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['postal2']) || $_POST['postal2'] == $this->lng['etape1']['postal'])
					{
						$this->form_ok = false;
					}
				}
				
				//nom_inscription
				if(!isset($_POST['nom_inscription']) || $_POST['nom_inscription'] == $this->lng['etape1']['nom'])
				{
					$this->form_ok = false;
				}
				//prenom_inscription
				if(!isset($_POST['prenom_inscription']) || $_POST['prenom_inscription'] == $this->lng['etape1']['prenom'])
				{
					$this->form_ok = false;
				}
				//fonction_inscription
				if(!isset($_POST['fonction_inscription']) || $_POST['fonction_inscription'] == $this->lng['etape1']['fonction'])
				{
					$this->form_ok = false;
				}
				//email_inscription
				if(!isset($_POST['email_inscription']) || $_POST['email_inscription'] == $this->lng['etape1']['email'])
				{
					$this->form_ok = false;
				}
				elseif(isset($_POST['email_inscription']) && $this->ficelle->isEmail($_POST['email_inscription']) == false)
				{
					$this->form_ok = false;
				}
				elseif($_POST['email_inscription'] != $_POST['conf_email_inscription'])
				{
					$this->form_ok = false;
				}
				elseif($this->clients->existEmail($_POST['email_inscription']) == false)
				{
					// et si l'email n'est pas celle du client
					if($_POST['email_inscription'] != $this->email)
					{
						// check si l'adresse mail est deja utilisé
						$this->reponse_email = $this->lng['etape1']['erreur-email'];
					}
				}
				
				
				//phone_new_inscription
				if(!isset($_POST['phone_new_inscription']) || $_POST['phone_new_inscription'] == $this->lng['etape1']['telephone'])
				{
					$this->form_ok = false;
				}
				
				//extern ou non dirigeant
				if($this->companies->status_client == 2 || $this->companies->status_client == 3)
				{
					
					if(!isset($_POST['nom2_inscription']) || $_POST['nom2_inscription'] == $this->lng['etape1']['nom'])
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['prenom2_inscription']) || $_POST['prenom2_inscription'] == $this->lng['etape1']['prenom'])
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['fonction2_inscription']) || $_POST['fonction2_inscription'] == $this->lng['etape1']['fonction'])
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['email2_inscription']) || $_POST['email2_inscription'] == $this->lng['etape1']['email'])
					{
						$this->form_ok = false;
					}
					elseif(isset($_POST['email2_inscription']) && $this->ficelle->isEmail($_POST['email2_inscription']) == false)
					{
						$this->form_ok = false;
					}
					if(!isset($_POST['phone_new2_inscription']) || $_POST['phone_new2_inscription'] == $this->lng['etape1']['telephone'])
					{
						$this->form_ok = false;
					}
					
					// externe
					if($this->companies->status_client == 3)
					{
						if(!isset($_POST['external-consultant']) || $_POST['external-consultant'] == '')
						{
							$this->form_ok = false;
						}
						if(!isset($_POST['autre_inscription']) || $_POST['autre_inscription'] == $this->lng['etape1']['autre'])
						{
							//$this->form_ok = false;
						}
						
					}
				}
				
				// Si form societe ok
				if($this->form_ok == true)
				{
					$this->clients->id_langue = 'fr';
					$this->clients->type = 2;
					$this->clients->nom_usage = '';
					$this->clients->naissance = '0000-00-00';
					$this->clients->ville_naissance = '';
					$this->clients->slug = $this->bdd->generateSlug($this->clients->prenom.'-'.$this->clients->nom);
					
					// Si mail existe deja
					if($this->reponse_email != '')
					{
						$this->clients->email = $this->email;
					}
					
					
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
					$this->lenders_accounts->update();
					
					// On met a jour le client
					$this->clients->update();
					// On met a jour l'adresse client
					$this->clients_adresses->update();
					
					
					// On recup le client
					$this->clients->get($this->clients->id_client,'id_client');
					
					if($this->reponse_email != '') $_SESSION['reponse_email'] = $this->reponse_email;
					
					
					// on envoie un mail notifiaction si infos fiscale modifiés
					if($adresse_fiscal != $this->companies->adresse1 || $ville_fiscal != $$this->companies->city || $cp_fiscal != $this->companies->zip)
					{
						$contenu = '<ul>';
						
						if($adresse_fiscal != $this->companies->adresse1)
							$contenu .= '<li>adresse fiscal</li>';
						if($ville_fiscal != $this->companies->city)
							$contenu .= '<li>ville fiscal</li>';
						if($cp_fiscal != $this->companies->zip)
							$contenu .= '<li>cp fiscal</li>';
							
						$contenu .= '</ul>';
						
						// destinataire
						$this->settings->get('Adresse notification changement informations bancaires','type');
						$destinataire = $this->settings->value;
						//$destinataire = 'd.courtier@relance.fr';
						
						// mail notification
						
						// Recuperation du modele de mail
						$this->mails_text->get('notification-modification-informations-bancaires','lang = "'.$this->language.'" AND type');
						
						// Variables du mailing
						$surl = $this->surl;
						$url = $this->lurl;
						$id_client = $this->clients->id_client;
						$email_client = $this->clients->email;
						
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
						//$this->email->addBCCRecipient('d.courtier@relance.fr');
					
						$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
						$this->email->setHTMLBody($texteMail);
						Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
						// fin mail
					}
					
					header('location:'.$this->lurl.'/profile/');
        			die;
				}
				else
				{
					header('location:'.$this->lurl.'/profile/');
        			die;
				}
			}
			
		}
		
		////////////////////
		// fin info perso //
		////////////////////
		
		
		///////////////
		// info bank //
		///////////////
		
		if($this->clients->type == 1)
		{
			$fichier1 = $this->lenders_accounts->fichier_cni_passeport;
			$fichier2 = $this->lenders_accounts->fichier_justificatif_domicile;
			$fichier3 = $this->lenders_accounts->fichier_rib;
		}
		elseif($this->clients->type == 2)
		{
			$fichier1 = $this->lenders_accounts->fichier_extrait_kbis;
			$fichier2 = $this->lenders_accounts->fichier_delegation_pouvoir;
			$fichier3 = $this->lenders_accounts->fichier_rib;
			$fichier4 = $this->lenders_accounts->fichier_statuts;
			$fichier5 = $this->lenders_accounts->fichier_cni_passeport_dirigent;
		}
		
		
		// upload fichiers
		if(isset($_POST['form_pop_up_etape2']))
		{
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'file' => $_FILES));
			$this->clients_history_actions->histo(6,'info bank file profile',$this->clients->id_client,$serialize);
			////////////////
			
			
			
			// Particulier
			if($this->clients->type == 1)
			{
				// carte-nationale-didentite-passeport
				if(isset($_FILES['fichier1']) && $_FILES['fichier1']['name'] != '')
				{
					$this->upload->setUploadDir($this->path,'protected/lenders/cni_passeport/');
					if($this->upload->doUpload('fichier1'))
					{
						if($this->lenders_accounts->fichier_cni_passeport != '')@unlink($this->path.'protected/lenders/cni_passeport/'.$this->lenders_accounts->fichier_cni_passeport);
						$this->lenders_accounts->fichier_cni_passeport = $this->upload->getName();
					}
				}
				// justificatif-de-domicile
				if(isset($_FILES['fichier2']) && $_FILES['fichier2']['name'] != '')
				{
					$this->upload->setUploadDir($this->path,'protected/lenders/justificatif_domicile/');
					if($this->upload->doUpload('fichier2'))
					{
						if($this->lenders_accounts->fichier_justificatif_domicile != '')@unlink($this->path.'protected/companies/justificatif_domicile/'.$this->lenders_accounts->fichier_justificatif_domicile);
						$this->lenders_accounts->fichier_justificatif_domicile = $this->upload->getName();
					}
				}
				// rib
				if(isset($_FILES['fichier3']) && $_FILES['fichier3']['name'] != '')
				{
					$this->upload->setUploadDir($this->path,'protected/lenders/rib/');
					if($this->upload->doUpload('fichier3'))
					{
						if($this->lenders_accounts->fichier_rib != '')@unlink($this->path.'protected/lenders/rib/'.$this->lenders_accounts->fichier_rib);
						$this->lenders_accounts->fichier_rib = $this->upload->getName();
					}
				}
				
				$this->lenders_accounts->update();
				
			}
			// Societe
			elseif($this->clients->type == 2)
			{	
				// Extrait Kbis
				if(isset($_FILES['fichier1']) && $_FILES['fichier1']['name'] != '')
				{
					$this->upload->setUploadDir($this->path,'protected/lenders/extrait_kbis/');
					if($this->upload->doUpload('fichier1'))
					{
						if($this->lenders_accounts->fichier_extrait_kbis != '')@unlink($this->path.'protected/lenders/extrait_kbis/'.$this->lenders_accounts->fichier_extrait_kbis);
						$this->lenders_accounts->fichier_extrait_kbis = $this->upload->getName();
					}
				}
				// Délégation de pouvoir
				if(isset($_FILES['fichier2']) && $_FILES['fichier2']['name'] != '')
				{
					$this->upload->setUploadDir($this->path,'protected/lenders/delegation_pouvoir/');
					if($this->upload->doUpload('fichier2'))
					{
						if($this->lenders_accounts->fichier_delegation_pouvoir != '')@unlink($this->path.'protected/companies/delegation_pouvoir/'.$this->lenders_accounts->fichier_delegation_pouvoir);
						$this->lenders_accounts->fichier_delegation_pouvoir = $this->upload->getName();
					}
				}
				// RIB
				if(isset($_FILES['fichier3']) && $_FILES['fichier3']['name'] != '')
				{
					$this->upload->setUploadDir($this->path,'protected/lenders/rib/');
					if($this->upload->doUpload('fichier3'))
					{
						if($this->lenders_accounts->fichier_rib != '')@unlink($this->path.'protected/lenders/rib/'.$this->lenders_accounts->fichier_rib);
						$this->lenders_accounts->fichier_rib = $this->upload->getName();
					}
				}
				// Statuts
				if(isset($_FILES['fichier4']) && $_FILES['fichier4']['name'] != '')
				{
					$this->upload->setUploadDir($this->path,'protected/lenders/statuts/');
					if($this->upload->doUpload('fichier4'))
					{
						if($this->lenders_accounts->fichier_statuts != '')@unlink($this->path.'protected/lenders/statuts/'.$this->lenders_accounts->fichier_statuts);
						$this->lenders_accounts->fichier_statuts = $this->upload->getName();
					}
				}
				// CNI/Passeport dirigeants
				if(isset($_FILES['fichier5']) && $_FILES['fichier5']['name'] != '')
				{
					$this->upload->setUploadDir($this->path,'protected/lenders/cni_passeport_dirigent/');
					if($this->upload->doUpload('fichier5'))
					{
						if($this->lenders_accounts->fichier_cni_passeport_dirigent != '')@unlink($this->path.'protected/lenders/cni_passeport_dirigent/'.$this->lenders_accounts->fichier_cni_passeport_dirigent);
						$this->lenders_accounts->fichier_cni_passeport_dirigent = $this->upload->getName();
					}
				}
				
				$this->lenders_accounts->update();
				
				
			}
			
			if($this->clients->type == 1)
			{
					
				$fichier_ok = false;
				$contenu_fichier = '';
				if($fichier1 != $this->lenders_accounts->fichier_cni_passeport)
				{
					$fichier_ok = true;
					$contenu_fichier .= '<li>fichier cni / passeport</li>';
				}
				if($fichier2 != $this->lenders_accounts->fichier_justificatif_domicile)
				{
					$fichier_ok = true;
					$contenu_fichier .= '<li>fichier justificatif domicile</li>';
				}
				if($fichier3 != $this->lenders_accounts->fichier_rib)
				{
					$fichier_ok = true;
					$contenu_fichier .= '<li>fichier rib</li>';
				}
			}
			elseif($this->clients->type == 2)
			{
				$fichier_ok = false;
				
				if($fichier1 != $this->lenders_accounts->fichier_extrait_kbis)
				{
					$fichier_ok = true;
					$contenu_fichier .= '<li>fichier extrait kbis</li>';
				}
				if($fichier2 != $this->lenders_accounts->fichier_delegation_pouvoir)
				{
					$fichier_ok = true;
					$contenu_fichier .= '<li>fichier delegation pouvoir</li>';
				}
				if($fichier3 != $this->lenders_accounts->fichier_rib)
				{
					$fichier_ok = true;
					$contenu_fichier .= '<li>fichier rib</li>';
				}
				if($fichier4 != $this->lenders_accounts->fichier_statuts)
				{
					$fichier_ok = true;
					$contenu_fichier .= '<li>fichier statuts</li>';
				}
				if($fichier5 != $this->lenders_accounts->fichier_cni_passeport_dirigent)
				{
					$fichier_ok = true;
					$contenu_fichier .= '<li>fichier cni passeport dirigent</li>';
				}
				
			}
			
			if($fichier_ok == true)
			{
				$contenu = '<ul>';
				$contenu .= $contenu_fichier;
				$contenu .= '</ul>';
				
				// le mail
				
				// destinataire
				$this->settings->get('Adresse notification changement informations bancaires','type');
				$destinataire = $this->settings->value;
				//$destinataire = 'd.courtier@relance.fr';
				
				// mail notification
				
				// Recuperation du modele de mail
				$this->mails_text->get('notification-modification-informations-bancaires','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				$id_client = $this->clients->id_client;
				$email_client = $this->clients->email;
				
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
				//$this->email->addBCCRecipient('d.courtier@relance.fr');
			
				$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
				$this->email->setHTMLBody($texteMail);
				Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
				// fin mail
			}
			
			header('location:'.$this->lurl.'/profile/2');
			die;
		}
		
		// Form etape 2
		if(isset($_POST['send_form_etape2']))
		{
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(5,'info bank profile',$this->clients->id_client,$serialize);
			////////////////
			
			$bic_old = $this->lenders_accounts->bic;
			$iban_old = $this->lenders_accounts->iban;
			
			$this->lenders_accounts->bic = str_replace(' ','',strtoupper($_POST['bic']));
			$this->lenders_accounts->iban = '';
			for($i=1;$i<=7;$i++){ $this->lenders_accounts->iban .= str_replace(' ','',strtoupper($_POST['iban-'.$i])); }
			
			// Dans le cas d'un particulier
			if($this->clients->type == 1)
			{
				$origine_des_fonds_old = $this->lenders_accounts->origine_des_fonds;
				$cni_passeport_old = $this->lenders_accounts->cni_passeport;
				
				$this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
				if($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000)$this->lenders_accounts->precision = $_POST['preciser'];
				else $this->lenders_accounts->precision = '';
				
				
				$this->lenders_accounts->cni_passeport = $_POST['radio1'];
			}
			
			$this->form_ok = true;
			
			if(!isset($_POST['bic']) || $_POST['bic'] == $this->lng['etape2']['bic'] || strlen($this->lenders_accounts->bic) < 8 && strlen($this->lenders_accounts->bic) > 11)
			{
				$this->form_ok = false;
			}
			
			if(strlen($this->lenders_accounts->iban) < 27)
			{
				$this->form_ok = false;
			}
			
			// Dans le cas d'un particulier
			if($this->clients->type == 1)
			{
				if(!isset($_POST['origine_des_fonds']) || $_POST['origine_des_fonds'] == 0)
				{
					$this->form_ok = false;
				}
			}

			
			if($this->form_ok == true)
			{
				// On met a jour le lender
				$this->lenders_accounts->update();
				
				
				////////////////////////////////////////////////////
				// Si different on envoie un mail de notification //
				////////////////////////////////////////////////////
				
				// on regarde si les fichiers ont changés
				
				
				
				
				// origine_des_fonds
				// cni_passeport
				// bic
				// iban
				if($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds || $cni_passeport_old != $this->lenders_accounts->cni_passeport || $bic_old != $this->lenders_accounts->bic || $iban_old != $this->lenders_accounts->iban)
				{
					
					$contenu = '<ul>'; 
					if($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds)
					$contenu .= '<li>origine des fonds</li>';
					if($cni_passeport_old != $this->lenders_accounts->cni_passeport)
					$contenu .= '<li>cni / passeport</li>';
					if($bic_old != $this->lenders_accounts->bic)
					$contenu .= '<li>bic</li>';
					if($iban_old != $this->lenders_accounts->iban)
					$contenu .= '<li>iban</li>';
					$contenu .= '</ul>'; 
					
					// destinataire
					$this->settings->get('Adresse notification changement informations bancaires','type');
					$destinataire = $this->settings->value;
					//$destinataire = 'd.courtier@relance.fr';
					
					// mail notification
					
					// Recuperation du modele de mail
					$this->mails_text->get('notification-modification-informations-bancaires','lang = "'.$this->language.'" AND type');
					
					// Variables du mailing
					$surl = $this->surl;
					$url = $this->lurl;
					$id_client = $this->clients->id_client;
					$email_client = $this->clients->email;
					
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
					//$this->email->addBCCRecipient('d.courtier@relance.fr');
				
					$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
					$this->email->setHTMLBody($texteMail);
					Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
					// fin mail
				}



				header('location:'.$this->lurl.'/profile/2');
				die;
			}
		}
		
		///////////////////
		
		$year = date('Y');
		
		
		//// transaction ///
		//$this->lLoans = $this->loans->sumPretsByProject($this->lenders_accounts->id_lender_account,$year,'added DESC');
		
		$this->lLoans = $this->loans->select('id_lender = '.$this->lenders_accounts->id_lender_account.' AND YEAR(added) = '.$year,'added DESC');
		
		$this->lTrans = $this->transactions->select('transaction = 1 AND status = 1 AND etat = 1 AND id_client = '.$this->clients->id_client.' AND YEAR(date_transaction) = '.$year,'added DESC');
		
		
		
		
		
		
		

		$this->lesStatuts = array(1 => $this->lng['profile']['versement-initial'],3 => $this->lng['profile']['alimentation-cb'],4 => $this->lng['profile']['alimentation-virement'],7 => $this->lng['profile']['alimentation-prelevement'],8 => $this->lng['profile']['retrait']);
		
		/*echo '<pre>';
		print_r($this->lTrans);
		echo '</pre>';*/
	}
	
	function _info_perso()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
	}
	function _gestion_secu()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
	}
	
	function _info_bank()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _histo_transac()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	
	/////////
	// NEW //
	/////////
	
	// PARTICULIER //
	function _particulier_perso(){
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _particulier_perso_new(){
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _particulier_bank(){
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _particulier_bank_new(){
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _secu(){
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _particulier_doc(){
		
		// Societe (si on est pas sur la bonne page)
		if(in_array($this->clients->type,array(2,4))){
			header('Location:'.$this->lurl.'/profile/societe_doc');
			die;
		}
		
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
	
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
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		// On recup le preteur
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		
		
		// upload
		if(isset($_POST['send_form_upload_doc'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(12,'upload doc profile',$this->clients->id_client,$serialize);
			////////////////
			
			// carte-nationale-didentite
			if(isset($_FILES['ci']) && $_FILES['ci']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/lenders/cni_passeport/');
				if($this->upload->doUpload('ci'))
				{
					if($this->lenders_accounts->fichier_cni_passeport != '')@unlink($this->path.'protected/lenders/cni_passeport/'.$this->lenders_accounts->fichier_cni_passeport);
					$this->lenders_accounts->fichier_cni_passeport = $this->upload->getName();
					$fichier_cni_passeport = true;
				}
				else{
					$this->error_cni = true;	
				}
			}
			// justificatif-de-domicile
			if(isset($_FILES['justificatif_de_domicile']) && $_FILES['justificatif_de_domicile']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/lenders/justificatif_domicile/');
				if($this->upload->doUpload('justificatif_de_domicile'))
				{
					if($this->lenders_accounts->fichier_justificatif_domicile != '')@unlink($this->path.'protected/companies/justificatif_domicile/'.$this->lenders_accounts->fichier_justificatif_domicile);
					$this->lenders_accounts->fichier_justificatif_domicile = $this->upload->getName();
					$fichier_justificatif_domicile = true;
				}
				else{
					$this->error_justificatif_domicile = true;	
				}
			}
			// rib
			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/lenders/rib/');
				if($this->upload->doUpload('rib'))
				{
					if($this->lenders_accounts->fichier_rib != '')@unlink($this->path.'protected/lenders/rib/'.$this->lenders_accounts->fichier_rib);
					$this->lenders_accounts->fichier_rib = $this->upload->getName();
					$fichier_rib = true;
				}
				else{
					$this->error_rib = true;	
				}
			}
			
			// autre
			if(isset($_FILES['autre']) && $_FILES['autre']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/lenders/autre/');
				if($this->upload->doUpload('autre'))
				{
					if($this->lenders_accounts->fichier_autre != '')@unlink($this->path.'protected/lenders/autre/'.$this->lenders_accounts->fichier_autre);
					$this->lenders_accounts->fichier_autre = $this->upload->getName();
					$fichier_autre = true;
				}
				else{
					$this->error_autre = true;	
				}
			}
			
			
			
			$this->error_fichiers = false;
			
			
			if($this->error_cni == true){
				$this->error_fichiers = true;
			}
			if($this->error_justificatif_domicile == true){
				$this->error_fichiers = true;
			}
			if($this->error_rib == true){
				$this->error_fichiers = true;
			}
			if($this->error_autre == true){
				$this->error_fichiers = true;
			}
			
			// on enregistre une partie pour avoir les images good
			if($this->error_cni == false || $this->error_justificatif_domicile == false || $this->error_rib == false || $this->error_autre == false){
				$this->lenders_accounts->update();	
			}
			
			if($this->error_fichiers == false){
			
				if($fichier_cni_passeport == true || $fichier_justificatif_domicile == true || $fichier_rib == true || $fichier_autre == true){
					
					
					$this->lenders_accounts->update();
					
					
					$contenu = '<ul>';
					
					if($fichier_cni_passeport == true)
						$contenu .= '<li>Fichier cni passeport</li>';
					if($fichier_justificatif_domicile == true)
						$contenu .= '<li>Fichier justificatif de domicile</li>';
					if($fichier_rib == true)
						$contenu .= '<li>Fichier RIB</li>';
					if($fichier_autre == true)
						$contenu .= '<li>Fichier autre</li>';
					
					$contenu .= '</ul>';
					
					if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
					else $statut_client = 50;
					
					// creation du statut "Modification"
					$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
					
					// destinataire
					$this->settings->get('Adresse notification modification preteur','type');
					$destinataire = $this->settings->value;
					
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
					
					/// mail nmp pour le preteur particulier ///
					
					//************************************//
					//*** ENVOI DU MAIL  ***//
					//************************************//
		
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-modification-compte','lang = "'.$this->language.'" AND type');
					
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					// Variables du mailing
					$varMail = array(
					'surl' =>  $this->surl,
					'url' => $this->lurl,
					'prenom' => $this->clients->prenom,
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
					////////////////////////////////
					
					$_SESSION['reponse_upload'] = $this->lng['profile']['sauvegardees'];
				
				}
				header('Location:'.$this->lurl.'/profile/societe_doc');
				die;
			}
		}
		
	}
	// SOCIETE //
	function _societe_perso(){
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _societe_bank(){
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
	}
	function _societe_doc(){
		// Particulier (si on est pas sur la bonne page)
		if(in_array($this->clients->type,array(1,3))){
			header('Location:'.$this->lurl.'/profile/particulier_doc');
			die;
		}
		
		//Recuperation des element de traductions
		$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
		$this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2',$this->language,$this->App);
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
	
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
		$this->companies = $this->loadData('companies');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		// On recup le preteur
		$this->companies->get($this->clients->id_client,'id_client_owner');
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		
		
		// upload
		if(isset($_POST['send_form_upload_doc'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(12,'upload doc profile',$this->clients->id_client,$serialize);
			////////////////
			
			// carte-nationale-didentite dirigeant
			if(isset($_FILES['ci_dirigeant']) && $_FILES['ci_dirigeant']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/cni_passeport_dirigent/');
				if($this->upload->doUpload('ci_dirigeant')){
					if($this->lenders_accounts->fichier_cni_passeport_dirigent != '')@unlink($this->path.'protected/lenders/cni_passeport_dirigent/'.$this->lenders_accounts->fichier_cni_passeport_dirigent);
					$this->lenders_accounts->fichier_cni_passeport_dirigent = $this->upload->getName();
					$fichier_cni_passeport_dirigent = true;
				}
				else{
					$this->error_cni_dirigent = true;
				}
			}
			// Extrait Kbis
			if(isset($_FILES['kbis']) && $_FILES['kbis']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/extrait_kbis/');
				if($this->upload->doUpload('kbis')){
					if($this->lenders_accounts->fichier_extrait_kbis != '')@unlink($this->path.'protected/companies/extrait_kbis/'.$this->lenders_accounts->fichier_extrait_kbis);
					$this->lenders_accounts->fichier_extrait_kbis = $this->upload->getName();
					$fichier_extrait_kbis = true;
				}
				else{
					$this->error_extrait_kbis = true;
				}
			}
			// rib
			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/rib/');
				if($this->upload->doUpload('rib')){
					if($this->lenders_accounts->fichier_rib != '')@unlink($this->path.'protected/lenders/rib/'.$this->lenders_accounts->fichier_rib);
					$this->lenders_accounts->fichier_rib = $this->upload->getName();
					$fichier_rib = true;
				}
				else{
					$this->error_rib = true;
				}
			}
			
			// autre
			if(isset($_FILES['autre']) && $_FILES['autre']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/autre/');
				if($this->upload->doUpload('autre')){
					if($this->lenders_accounts->fichier_autre != '')@unlink($this->path.'protected/lenders/autre/'.$this->lenders_accounts->fichier_autre);
					$this->lenders_accounts->fichier_autre = $this->upload->getName();
					$fichier_autre = true;
				}
				else{
					$this->error_autre = true;
				}
			}
			// Délégation de pouvoir
			if(isset($_FILES['delegation_pouvoir']) && $_FILES['delegation_pouvoir']['name'] != ''){
				$this->upload->setUploadDir($this->path,'protected/lenders/delegation_pouvoir/');
				if($this->upload->doUpload('delegation_pouvoir')){
					if($this->lenders_accounts->fichier_delegation_pouvoir != '')@unlink($this->path.'protected/companies/delegation_pouvoir/'.$this->lenders_accounts->fichier_delegation_pouvoir);
					$this->lenders_accounts->fichier_delegation_pouvoir = $this->upload->getName();
					$fichier_delegation_pouvoir = true;
				}
				else{
					$this->error_delegation_pouvoir = true;
				}
			}
			
			
			$this->error_fichiers = false;
				
			if($this->error_extrait_kbis == true){
				$this->error_fichiers = true;
			}
			if($this->error_rib == true){
				$this->error_fichiers = true;
			}
			if($this->error_autre == true){
				$this->error_fichiers = true;
			}
			if($this->error_cni_dirigent == true){
				$this->error_fichiers = true;
			}
			if($this->error_delegation_pouvoir == true){
				$this->error_fichiers = true;
			}
			
			// on enregistre une partie pour avoir les images good
			if($this->error_cni_dirigent == false || $this->error_extrait_kbis == false || $this->error_rib == false || $this->error_autre == false || $this->error_delegation_pouvoir == false){
				$this->lenders_accounts->update();	
			}
			
			if($this->error_fichiers == false){

				if($fichier_cni_passeport_dirigent == true || $fichier_extrait_kbis == true || $fichier_rib == true || $fichier_autre == true || $fichier_delegation_pouvoir == true){
					
					
					
					$this->lenders_accounts->update();
					
					
					if(in_array($this->clients_status->status,array(20,30,40))) $statut_client = 40;
					else $statut_client = 50;
					
					
					
					$contenu = '<ul>';
					
					if($fichier_cni_passeport_dirigent == true)
						$contenu .= '<li>Fichier cni passeport dirigent</li>';
					if($fichier_extrait_kbis == true)
						$contenu .= '<li>Fichier extrait kbis</li>';
					if($fichier_rib == true)
						$contenu .= '<li>Fichier RIB</li>';
					if($fichier_autre == true)
						$contenu .= '<li>Fichier autre</li>';
					if($fichier_delegation_pouvoir == true)
						$contenu .= '<li>Fichier delegation de pouvoir</li>';
					
					$contenu .= '</ul>';
					
					// creation du statut "Modification"
					$this->clients_status_history->addStatus('-2',$statut_client,$this->clients->id_client,$contenu);
					
					// destinataire
					$this->settings->get('Adresse notification modification preteur','type');
					$destinataire = $this->settings->value;
					
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
					
					/// mail nmp pour le preteur morale ///
					
					//************************************//
					//*** ENVOI DU MAIL  ***//
					//************************************//
		
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-modification-compte','lang = "'.$this->language.'" AND type');
					
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					// Variables du mailing
					$varMail = array(
					'surl' =>  $this->surl,
					'url' => $this->lurl,
					'prenom' => $this->clients->prenom,
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
					////////////////////////////////
					
					$_SESSION['reponse_upload'] = $this->lng['profile']['sauvegardees'];
				
				}
				header('Location:'.$this->lurl.'/profile/societe_doc');
				die;
			}
		}
		
		
	}
}