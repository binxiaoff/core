<?php

class profileController extends bootstrap
{
	var $Command;

	/**
	 * @var attachment_helper
	 */
	private $attachmentHelper;
	
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
		$this->lng['gestion-alertes'] = $this->ln->selectFront('preteur-profile-gestion-alertes',$this->language,$this->App);
	
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
		$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
		$this->clients_gestion_type_notif = $this->loadData('clients_gestion_type_notif');
        $this->attachment = $this->loadData('attachment');
        $this->attachment_type = $this->loadData('attachment_type');
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		// recuperation info lender
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
        $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

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
		
		$this->infosNotifs['title'] = array(
		1 => $this->lng['gestion-alertes']['annonce-des-nouveaux-projets'],
		2 => $this->lng['gestion-alertes']['offres-realisees'],
		3 => $this->lng['gestion-alertes']['offres-refusees'],
		4 => $this->lng['gestion-alertes']['offres-acceptees'],
		5 => $this->lng['gestion-alertes']['remboursements'],
		6 => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-virement'],
		7 => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-carte-bancaire'],
		8 => $this->lng['gestion-alertes']['retrait']);
		
		$this->infosNotifs['info'] = array(
		1 => $this->lng['gestion-alertes']['annonce-des-nouveaux-projets-info'],
		2 => $this->lng['gestion-alertes']['offres-realisees-info'],
		3 => $this->lng['gestion-alertes']['offres-refusees-info'],
		4 => $this->lng['gestion-alertes']['offres-acceptees-info'],
		5 => $this->lng['gestion-alertes']['remboursements-info'],
		6 => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-virement-info'],
		7 => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-carte-bancaire-info'],
		8 => $this->lng['gestion-alertes']['retrait-info']);
		
		$this->lTypeNotifs = $this->clients_gestion_type_notif->select();
		
		$this->NotifC = $this->clients_gestion_notifications->getNotifs($this->clients->id_client);
		
		/*echo '<pre>';
		print_r($this->arrayNotifClient);
		echo '</pre>';*/


		// formulaire particulier gestion de vos alertes
		if(isset($_POST['send_gestion_alertes'])){
			foreach($this->lTypeNotifs as $k => $n){
				$id_notif = $n['id_client_gestion_type_notif'];
				
				$_POST['immediatement_'.$id_notif];
				$_POST['quotidienne_'.$id_notif];
				$_POST['hebdomadaire_'.$id_notif];
				$_POST['mensuelle_'.$id_notif];
				$_POST['uniquement_notif_'.$id_notif];
				
				if($_POST['immediatement_'.$id_notif] == true)$immediatement[$id_notif] = 1;
				else $immediatement[$id_notif] = 0;
				
				if($_POST['quotidienne_'.$id_notif] == true)$quotidienne[$id_notif] = 1;
				else $quotidienne[$id_notif] = 0;
				
				if($_POST['hebdomadaire_'.$id_notif] == true)$hebdomadaire[$id_notif] = 1;
				else $hebdomadaire[$id_notif] = 0;
				
				if($_POST['mensuelle_'.$id_notif] == true)$mensuelle[$id_notif] = 1;
				else $mensuelle[$id_notif] = 0;
				
				if($_POST['uniquement_notif_'.$id_notif] == true)$uniquement_notif[$id_notif] = 1;
				else $uniquement_notif[$id_notif] = 0;
				
				// uniquement notif
				if($uniquement_notif[$id_notif] == 1){
					$this->clients_gestion_notifications->get(array('id_client' => $this->clients->id_client,'id_notif' => $id_notif));
					$this->clients_gestion_notifications->immediatement 	= 0;
					$this->clients_gestion_notifications->quotidienne 		= 0;
					$this->clients_gestion_notifications->hebdomadaire 		= 0;
					$this->clients_gestion_notifications->mensuelle 		= 0;
					$this->clients_gestion_notifications->uniquement_notif 	= 1;
					$this->clients_gestion_notifications->update(array('id_client' => $this->clients->id_client,'id_notif' => $id_notif));
				}
				// notif et email
				else{
					$this->clients_gestion_notifications->get(array('id_client' => $this->clients->id_client,'id_notif' => $id_notif));
					$this->clients_gestion_notifications->immediatement		= $immediatement[$id_notif];
					
					if(in_array($id_notif,array(6,7,8)))
						$this->clients_gestion_notifications->quotidienne 	= 0;
					else 
						$this->clients_gestion_notifications->quotidienne 	= $quotidienne[$id_notif];
					
					if(in_array($id_notif,array(2,3,6,7,8)))
						$this->clients_gestion_notifications->hebdomadaire 	= 0; // on change pas pour les nouveau projet
					else
						$this->clients_gestion_notifications->hebdomadaire 	= $hebdomadaire[$id_notif];
					
					if(in_array($id_notif,array(1,2,3,6,7,8)))
						$this->clients_gestion_notifications->mensuelle 	= 0; // on change pas pour les nouveau projet et offres realisees
					else 
						$this->clients_gestion_notifications->mensuelle 	= $mensuelle[$id_notif];
					$this->clients_gestion_notifications->uniquement_notif 	= 0;
					$this->clients_gestion_notifications->update(array('id_client' => $this->clients->id_client,'id_notif' => $id_notif));
				}
			}

			header('Location:'.$this->lurl.'/profile/particulier/');
			die;
		}
		////////////////////////////

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
			$bRibUpdated = false;
            $fichier_rib =  isset($this->attachments[attachment_type::RIB]['id']) ? $this->attachments[attachment_type::RIB]['id'] : null;

			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != ''){
                $fichier_rib = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB);
				if (is_numeric($fichier_rib)) {

					$bRibUpdated = true;
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
			if (false === is_numeric($fichier_rib)) {
				$this->form_ok = false;
                $this->error_rib = true;
			}


			///////////////////////////////////////////////////////////////////

			// si form particulier ok
			if($this->form_ok == true){
				//////////////
				// FICHIERS //
				$bDocumentFiscalUpdated                 = false;
				$bCniPasseportUpdated                   = false;
				$bCniPasseporVersotUpdated              = false;
				$bJustificatifDomicileUpdated           = false;
				$bAttestationHebergementTiersUpdated    = false;
				$bCniPassportTiersHebergeantIUpdated    = false;
				$bAutreUpdated                          = false;

				// si etrangé
				if ($this->etranger == 1 || $this->etranger == 2){
                    if(isset($_FILES['document_fiscal']) && $_FILES['document_fiscal']['name'] != ''){
                        $fichier_document_fiscal = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::JUSTIFICATIF_FISCAL);
                        if (is_numeric($fichier_document_fiscal)) {
                            $bDocumentFiscalUpdated = true;
                        }
                    }
				}
				
				// carte-nationale-didentite
                if(isset($_FILES['cni_passeport']) && $_FILES['cni_passeport']['name'] != ''){
                    $fichier_cni_passeport = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE);
                    if (is_numeric($fichier_cni_passeport)) {
                        $bCniPasseportUpdated = true;
                    }
                }


                // carte-nationale-didentite verso
                if(isset($_FILES['cni_passeport_verso']) && $_FILES['cni_passeport_verso']['name'] != ''){
                    $fichier_cni_passeport_verso = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_VERSO);
                    if (is_numeric($fichier_cni_passeport_verso)) {
                        $bCniPasseporVersotUpdated = true;
                    }
                 }


				// justificatif-de-domicile
                if(isset($_FILES['justificatif_domicile']) && $_FILES['justificatif_domicile']['name'] != '') {
                    $fichier_justificatif_domicile = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::JUSTIFICATIF_DOMICILE);
                    if (is_numeric($fichier_justificatif_domicile)) {
                        $bJustificatifDomicileUpdated = true;
                    }
                }

                // attestation hebergement tiers
                if(isset($_FILES['attestation_hebergement_tiers']) && $_FILES['attestation_hebergement_tiers']['name'] != '') {
                    $fichier_attestation_hebergement_tiers = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::ATTESTATION_HEBERGEMENT_TIERS);
                    if (is_numeric($fichier_attestation_hebergement_tiers)) {
                        $bAttestationHebergementTiersUpdated = true;
                    }
                }

                // CNI passport tiers heberageant
                if(isset($_FILES['cni_passport_tiers_hebergeant']) && $_FILES['cni_passport_tiers_hebergeant']['name'] != '') {
                    $fichier_cni_passport_tiers_hebergeant = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT);
                    if (is_numeric($fichier_cni_passport_tiers_hebergeant)) {
                        $bCniPassportTiersHebergeantIUpdated = true;
                    }
                }

                // autre
                if(isset($_FILES['autre1']) && $_FILES['autre1']['name'] != '') {
                    $fichier_autre = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::AUTRE1);
                    if (is_numeric($fichier_autre)) {
                        $bAutreUpdated = true;
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
                $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);


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
                $bCniPasseportUpdated === true ||
                $bJustificatifDomicileUpdated === true ||
                $bCniPasseporVersotUpdated === true ||
                $bAttestationHebergementTiersUpdated === true ||
                $bCniPassportTiersHebergeantIUpdated === true ||
                $bAutreUpdated === true ||
				$this->etranger > 0 && $bDocumentFiscalUpdated === true ||
				$origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds || 
				$bic_old != $this->lenders_accounts->bic || 
				$iban_old != $this->lenders_accounts->iban ||
                $bRibUpdated === true
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
                    if ($bCniPasseportUpdated)
                        $contenu .= '<li>fichier cni passeport</li>';
                    if ($bCniPasseporVersotUpdated)
                        $contenu .= '<li>fichier cni passeport verso</li>';
                    if ($bJustificatifDomicileUpdated)
                        $contenu .= '<li>fichier justificatif domicile</li>';
                    if ($bAttestationHebergementTiersUpdated)
                        $contenu .= '<li>fichier attestation hebergement tiers</li>';
                    if ($bCniPassportTiersHebergeantIUpdated)
                        $contenu .= '<li>fichier cni passeport du tiers hebergeant</li>';
                    if ($bAutreUpdated)
                        $contenu .= '<li>fichier autre</li>';
                    if ($bDocumentFiscalUpdated)
                        $contenu .= '<li>fichier document fiscal</li>';

					////////////// PARTIE BANQUE ////////////////////////
					
					if($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds)
						$contenu .= '<li>Origine des fonds</li>';
					if($bic_old != $this->lenders_accounts->bic)
						$contenu .= '<li>BIC</li>';
					if($iban_old != $this->lenders_accounts->iban)
						$contenu .= '<li>IBAN</li>';
					if($bRibUpdated)
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
			$serialize = serialize(array('id_client' => $this->clients->id_client,'newmdp' => md5($_POST['passNew'])));
			$this->clients_history_actions->histo(7,'change mdp',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			// old mdp
			if(!isset($_POST['passOld']) || $_POST['passOld'] == '' || $_POST['passOld'] == $this->lng['etape1']['ancien-mot-de-passe']){
				$this->form_ok = false;
                                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
			}
			elseif(isset($_POST['passOld']) && md5($_POST['passOld']) != $this->clients->password){
				$this->form_ok = false;
				
				$_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
				header('Location:'.$this->lurl.'/profile/particulier/2');
				die;
			}
			
			// new pass
			if(!isset($_POST['passNew']) || $_POST['passNew'] == '' || $_POST['passNew'] == $this->lng['etape1']['nouveau-mot-de-passe']){
				$this->form_ok = false;
                                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
			}
			elseif(isset($_POST['passNew']) && $this->ficelle->password_fo($_POST['passNew'],6) == false){
				$this->form_ok = false;
                                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
			}
			
			// confirmation new pass
			if(!isset($_POST['passNew2']) || $_POST['passNew2'] == '' || $_POST['passNew2'] == $this->lng['etape1']['confirmation-nouveau-mot-de-passe']){
                            $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
                            $this->form_ok = false;
			}
			// check new pass != de confirmation
			if(isset($_POST['passNew']) && isset($_POST['passNew2']) && $_POST['passNew'] != $_POST['passNew2']){
				$this->form_ok = false;
                                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
			}
			
			// si good
			if($this->form_ok == true){
				
				$this->clients->password = md5($_POST['passNew']);
				$_SESSION['client']['password'] = $this->clients->password;
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
                        else
                        {
                            header('Location:'.$this->lurl.'/profile/particulier/2');
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
		$this->lng['gestion-alertes'] = $this->ln->selectFront('preteur-profile-gestion-alertes',$this->language,$this->App);
	
		
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
		$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
		$this->clients_gestion_type_notif = $this->loadData('clients_gestion_type_notif');
        $this->attachment = $this->loadData('attachment');
        $this->attachment_type = $this->loadData('attachment_type');
		
		// Liste des pays
		$this->lPays = $this->pays->select('','ordre ASC');
		
		// Liste deroulante conseil externe de l'entreprise
		$this->settings->get("Liste deroulante conseil externe de l'entreprise",'type');
		$this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);
		
		// On recup le preteur
		$this->companies->get($this->clients->id_client,'id_client_owner');
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
        $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
		
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
		
		$this->infosNotifs['title'] = array(
		1 => $this->lng['gestion-alertes']['annonce-des-nouveaux-projets'],
		2 => $this->lng['gestion-alertes']['offres-realisees'],
		3 => $this->lng['gestion-alertes']['offres-refusees'],
		4 => $this->lng['gestion-alertes']['offres-acceptees'],
		5 => $this->lng['gestion-alertes']['remboursements'],
		6 => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-virement'],
		7 => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-carte-bancaire'],
		8 => $this->lng['gestion-alertes']['retrait']);
		
		$this->infosNotifs['info'] = array(
		1 => $this->lng['gestion-alertes']['annonce-des-nouveaux-projets-info'],
		2 => $this->lng['gestion-alertes']['offres-realisees-info'],
		3 => $this->lng['gestion-alertes']['offres-refusees-info'],
		4 => $this->lng['gestion-alertes']['offres-acceptees-info'],
		5 => $this->lng['gestion-alertes']['remboursements-info'],
		6 => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-virement-info'],
		7 => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-carte-bancaire-info'],
		8 => $this->lng['gestion-alertes']['retrait-info']);
		
		////// Liste des notifs //////
		$this->lTypeNotifs = $this->clients_gestion_type_notif->select();
		$this->lNotifs = $this->clients_gestion_notifications->select('id_client = '.$this->clients->id_client);
		
		if($this->lNotifs == false){
			foreach($this->lTypeNotifs as $n){
				$this->clients_gestion_notifications->id_client = $this->clients->id_client;
				$this->clients_gestion_notifications->id_notif = $n['id_client_gestion_type_notif'];
				$this->clients_gestion_notifications->immediatement = 1;
				$this->clients_gestion_notifications->create();
			}
		}
		
		$this->NotifC = $this->clients_gestion_notifications->getNotifs($this->clients->id_client);
		
		// formulaire particulier gestion de vos alertes
		if(isset($_POST['send_gestion_alertes'])){
			foreach($this->lTypeNotifs as $k => $n){
				$id_notif = $n['id_client_gestion_type_notif'];
				
				$_POST['immediatement_'.$id_notif];
				$_POST['quotidienne_'.$id_notif];
				$_POST['hebdomadaire_'.$id_notif];
				$_POST['mensuelle_'.$id_notif];
				$_POST['uniquement_notif_'.$id_notif];
				
				if($_POST['immediatement_'.$id_notif] == true)$immediatement[$id_notif] = 1;
				else $immediatement[$id_notif] = 0;
				
				if($_POST['quotidienne_'.$id_notif] == true)$quotidienne[$id_notif] = 1;
				else $quotidienne[$id_notif] = 0;
				
				if($_POST['hebdomadaire_'.$id_notif] == true)$hebdomadaire[$id_notif] = 1;
				else $hebdomadaire[$id_notif] = 0;
				
				if($_POST['mensuelle_'.$id_notif] == true)$mensuelle[$id_notif] = 1;
				else $mensuelle[$id_notif] = 0;
				
				if($_POST['uniquement_notif_'.$id_notif] == true)$uniquement_notif[$id_notif] = 1;
				else $uniquement_notif[$id_notif] = 0;
				
				// uniquement notif
				if($uniquement_notif[$id_notif] == 1){
					$this->clients_gestion_notifications->get(array('id_client' => $this->clients->id_client,'id_notif' => $id_notif));
					$this->clients_gestion_notifications->immediatement 	= 0;
					$this->clients_gestion_notifications->quotidienne 		= 0;
					$this->clients_gestion_notifications->hebdomadaire 		= 0;
					$this->clients_gestion_notifications->mensuelle 		= 0;
					$this->clients_gestion_notifications->uniquement_notif 	= 1;
					$this->clients_gestion_notifications->update(array('id_client' => $this->clients->id_client,'id_notif' => $id_notif));
				}
				// notif et email
				else{
					$this->clients_gestion_notifications->get(array('id_client' => $this->clients->id_client,'id_notif' => $id_notif));
					$this->clients_gestion_notifications->immediatement		= $immediatement[$id_notif];
					$this->clients_gestion_notifications->quotidienne 		= $quotidienne[$id_notif];
					if($id_notif != 2)
						$this->clients_gestion_notifications->hebdomadaire 	= 0; // on change pas pour les nouveau projet
					else
						$this->clients_gestion_notifications->hebdomadaire 	= $hebdomadaire[$id_notif];
					if($id_notif != 1 && $id_notif != 2)
						$this->clients_gestion_notifications->mensuelle 	= 0; // on change pas pour les nouveau projet et offres realisees
					else 
						$this->clients_gestion_notifications->mensuelle 	= $mensuelle[$id_notif];
					$this->clients_gestion_notifications->uniquement_notif 	= 0;
					$this->clients_gestion_notifications->update(array('id_client' => $this->clients->id_client,'id_notif' => $id_notif));
				}
			}
			header('Location:'.$this->lurl.'/profile/societe/');
			die;
		}
		
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
            $this->error_fichier = false;

            $bCniDirigeantUpdated       = false;
            $bKbisUpdated               = false;
            $bRibUdated                 = false;
            $bCniPasseportVersoUpdated  = false;
            $bDelegationPouvoirUpdated  = false;

			// carte-nationale-didentite dirigeant
            $fichier_cni_dirigeant = isset($this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]['id']) ? $this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]['id'] : null;
            if (isset($_FILES['cni_passeport_dirigeant']) && $_FILES['cni_passeport_dirigeant']['name'] != ''){
                $fichier_cni_dirigeant = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_DIRIGEANT);
                if (is_numeric($fichier_cni_dirigeant)) {
                    $bCniDirigeantUpdated = true;
                }
            }

			// Extrait Kbis
            $fichier_kbis = isset($this->attachments[attachment_type::KBIS]['id']) ? $this->attachments[attachment_type::KBIS]['id'] : null;
            if(isset($_FILES['extrait_kbis']) && $_FILES['extrait_kbis']['name'] != ''){
                $fichier_kbis = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::KBIS);
                if (is_numeric($fichier_kbis)) {
                    $bKbisUpdated = true;
                }
			}
			// rib
            $fichier_rib =  isset($this->attachments[attachment_type::RIB]['id']) ? $this->attachments[attachment_type::RIB]['id'] : null;
            if(isset($_FILES['rib']) && $_FILES['rib']['name'] != ''){
                $fichier_rib= $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB);
                if (is_numeric($fichier_rib)) {
                    $bRibUdated = true;
                }
			}
			
			// CNI verso
            $fichier_cni_passeport_verso = isset($this->attachments[attachment_type::CNI_PASSPORTE_VERSO]['id']) ? $this->attachments[attachment_type::CNI_PASSPORTE_VERSO]['id'] : null;
            if(isset($_FILES['cni_passeport_verso']) && $_FILES['cni_passeport_verso']['name'] != ''){
                $fichier_cni_passeport_verso = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_VERSO);
                if (is_numeric($fichier_cni_passeport_verso)) {
                    $bCniPasseportVersoUpdated = true;
                }

            }
			// Délégation de pouvoir
            $fichier_delegation_pouvoir = isset($this->attachments[attachment_type::DELEGATION_POUVOIR]['id']) ? $this->attachments[attachment_type::DELEGATION_POUVOIR]['id'] : null;
            if(isset($_FILES['delegation_pouvoir']) && $_FILES['delegation_pouvoir']['name'] != ''){
                $fichier_delegation_pouvoir = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::DELEGATION_POUVOIR);
                if (is_numeric($fichier_delegation_pouvoir)) {
                    $bDelegationPouvoirUpdated = true;
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


			if(false === is_numeric($fichier_cni_dirigeant)
                || false === is_numeric($fichier_kbis)
                || false === is_numeric($fichier_rib)
                || false === is_numeric($fichier_delegation_pouvoir)
            ){
				//$this->form_ok = false;
				//$this->error_fichier = true;
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
                $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

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
				$bCniDirigeantUpdated == true ||
				$bKbisUpdated == true ||
				$bRibUdated == true ||
				$bCniPasseportVersoUpdated == true ||
				$bDelegationPouvoirUpdated == true
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
					if($bCniDirigeantUpdated == true)
						$contenu .= '<li>Fichier cni passeport dirigent</li>';
					if($bKbisUpdated == true)
						$contenu .= '<li>Fichier extrait kbis</li>';
					if($bRibUdated == true)
						$contenu .= '<li>Fichier RIB</li>';
					if($bCniPasseportVersoUpdated == true)
						$contenu .= '<li>Fichier cni passeport verso</li>';
					if($bDelegationPouvoirUpdated == true)
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
			$serialize = serialize(array('id_client' => $this->clients->id_client,'newmdp' => md5($_POST['passNew'])));
			$this->clients_history_actions->histo(7,'change mdp',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			// old mdp
			if(!isset($_POST['passOld']) || $_POST['passOld'] == '' || $_POST['passOld'] == $this->lng['etape1']['ancien-mot-de-passe']){
				$this->form_ok = false;
                                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
			}
			elseif(isset($_POST['passOld']) && md5($_POST['passOld']) != $this->clients->password){
				$this->form_ok = false;
				
				$_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
				header('Location:'.$this->lurl.'/profile/particulier/2');
				die;
			}
			
			// new pass
			if(!isset($_POST['passNew']) || $_POST['passNew'] == '' || $_POST['passNew'] == $this->lng['etape1']['nouveau-mot-de-passe']){
				$this->form_ok = false;
                                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
			}
			elseif(isset($_POST['passNew']) && $this->ficelle->password_fo($_POST['passNew'],6) == false){
				$this->form_ok = false;
                                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
			}
			
			// confirmation new pass
			if(!isset($_POST['passNew2']) || $_POST['passNew2'] == '' || $_POST['passNew2'] == $this->lng['etape1']['confirmation-nouveau-mot-de-passe']){
                            $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
                            $this->form_ok = false;
			}
			// check new pass != de confirmation
			if(isset($_POST['passNew']) && isset($_POST['passNew2']) && $_POST['passNew'] != $_POST['passNew2']){
				$this->form_ok = false;
                                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
			}
			
			// si good
			if($this->form_ok == true){
				
				$this->clients->password = md5($_POST['passNew']);
				$_SESSION['client']['password'] = $this->clients->password;
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
                        else
                        {
                            header('Location:'.$this->lurl.'/profile/particulier/2');
                            die;
                        }
		}
		
		
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
	function _gestion_alertes(){
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
        $this->attachment = $this->loadData('attachment');
        $this->attachment_type = $this->loadData('attachment_type');

        // On recup le preteur
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
        $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		
		
		// upload
		if(isset($_POST['send_form_upload_doc'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(12,'upload doc profile',$this->clients->id_client,$serialize);
			////////////////
            $this->error_fichiers = false;

            $bCniPasseportUpdated = false;
            $bJustificatifDomicileUpdated = false;
            $bRibUpdated = false;
            $bCniPasseportVersoUpdated = false;

			// carte-nationale-didentite
			if(isset($_FILES['cni_passeport']) && $_FILES['cni_passeport']['name'] != '')
			{
				$fichier_cni_passeport = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE);
                if(is_numeric($fichier_cni_passeport)) {
                    $bCniPasseportUpdated = true;
                }
                $this->error_cni = false === $fichier_cni_passeport;
			}

			// justificatif-de-domicile
			if(isset($_FILES['justificatif_domicile']) && $_FILES['justificatif_domicile']['name'] != '')
			{
                $fichier_justificatif_domicile = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::JUSTIFICATIF_DOMICILE);
                if(is_numeric($fichier_justificatif_domicile)) {
                    $bJustificatifDomicileUpdated = true;
                }
			}
			// rib
			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != '')
			{
                $fichier_rib = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB);
                if(is_numeric($fichier_rib)) {
                    $bRibUpdated = true;
                }

			}
			
			// CNI verso
			if(isset($_FILES['cni_passeport_verso']) && $_FILES['cni_passeport_verso']['name'] != '')
			{
                $fichier_cni_passeport_verso = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_VERSO);
                if(is_numeric($fichier_cni_passeport_verso)) {
                    $bCniPasseportVersoUpdated = true;
                }
			}
			
			if($this->error_fichiers == false){
			
				if($bCniPasseportUpdated == true || $bJustificatifDomicileUpdated == true || $bRibUpdated == true || $bCniPasseportVersoUpdated == true){
					
					$contenu = '<ul>';
					
					if($bCniPasseportUpdated == true)
						$contenu .= '<li>Fichier cni passeport</li>';
					if($bJustificatifDomicileUpdated == true)
						$contenu .= '<li>Fichier justificatif de domicile</li>';
					if($bRibUpdated == true)
						$contenu .= '<li>Fichier RIB</li>';
					if($bCniPasseportVersoUpdated == true)
						$contenu .= '<li>Fichier cni passport verso</li>';
					
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
        $this->attachment = $this->loadData('attachment');
        $this->attachment_type = $this->loadData('attachment_type');
		
		// On recup le preteur
		$this->companies->get($this->clients->id_client,'id_client_owner');
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
        $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
		
		
		// upload
		if(isset($_POST['send_form_upload_doc'])){
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(12,'upload doc profile',$this->clients->id_client,$serialize);
			////////////////
            $this->error_fichiers = false;

            $bCniPasseportDirigeantUpdated = false;
            $bKbisUpdated = false;
            $bRibUpdated = false;
            $bCniPasseportVersoUpdated = false;
            $bDelegationPouvoirUpdated = false;

			// carte-nationale-didentite dirigeant
            if(isset($_FILES['cni_passeport_dirigeant']) && $_FILES['cni_passeport_dirigeant']['name'] != ''){
                $fichier_cni_passeport_dirigent = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_DIRIGEANT);
                if (is_numeric($fichier_cni_passeport_dirigent)) {
                    $bCniPasseportDirigeantUpdated = true;
                }
			}
			// Extrait Kbis
			if(isset($_FILES['extrait_kbis']) && $_FILES['extrait_kbis']['name'] != ''){
                $fichier_extrait_kbis = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::KBIS);
                if (is_numeric($fichier_extrait_kbis)) {
                    $bKbisUpdated = true;
                }
			}
			// rib
			if(isset($_FILES['rib']) && $_FILES['rib']['name'] != ''){
                $fichier_rib = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB);
                if (is_numeric($fichier_rib)) {
                    $bRibUpdated = true;
                }
            }
			
			// CNI passport verso
			if(isset($_FILES['cni_passeport_verso']) && $_FILES['cni_passeport_verso']['name'] != ''){
                $fichier_cni_passeport_verso = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_VERSO);
                if (is_numeric($fichier_cni_passeport_verso)) {
                    $bCniPasseportVersoUpdated = true;
                }
            }
			// Délégation de pouvoir
            $fichier_delegation_pouvoir = $this->attachments[attachment_type::DELEGATION_POUVOIR];
			if(isset($_FILES['delegation_pouvoir']) && $_FILES['delegation_pouvoir']['name'] != ''){
                $fichier_delegation_pouvoir  = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::DELEGATION_POUVOIR);
                if (is_numeric($fichier_delegation_pouvoir)) {
                    $bDelegationPouvoirUpdated = true;
                }
            }

			if($this->error_fichiers == false){

				if($bCniPasseportDirigeantUpdated == true || $bKbisUpdated == true || $bRibUpdated == true || $bCniPasseportVersoUpdated == true || $bDelegationPouvoirUpdated == true){
					
					if(in_array($this->clients_status->status,array(20,30,40)))
                    {
                        $statut_client = 40;
                    } else {
                        $statut_client = 50;
                    }
					
					$contenu = '<ul>';
					
					if($bCniPasseportDirigeantUpdated == true)
						$contenu .= '<li>Fichier cni passeport dirigent</li>';
					if($bKbisUpdated == true)
						$contenu .= '<li>Fichier extrait kbis</li>';
					if($bRibUpdated == true)
						$contenu .= '<li>Fichier RIB</li>';
					if($bCniPasseportVersoUpdated == true)
						$contenu .= '<li>Fichier cni passeport verso</li>';
					if($bDelegationPouvoirUpdated == true)
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

    /**
     * @param integer $lenderAccountId
     * @param integer $attachmentType
     * @return bool
     */
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

        switch($attachmentType) {
            case attachment_type::CNI_PASSPORTE :
                $field = 'cni_passeport';
                break;
            case attachment_type::CNI_PASSPORTE_VERSO :
                $field = 'cni_passeport_verso';
                break;
            case attachment_type::JUSTIFICATIF_DOMICILE :
                $field = 'justificatif_domicile';
                break;
            case attachment_type::RIB :
                $field = 'rib';
                break;
            case attachment_type::ATTESTATION_HEBERGEMENT_TIERS :
                $field = 'attestation_hebergement_tiers';
                break;
            case attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT :
                $field = 'cni_passport_tiers_hebergeant';
                break;
            case attachment_type::CNI_PASSPORTE_DIRIGEANT :
                $field = 'cni_passeport_dirigeant';
                break;
            case attachment_type::DELEGATION_POUVOIR :
                $field = 'delegation_pouvoir';
                break;
            case attachment_type::KBIS :
                $field = 'extrait_kbis';
                break;
            case attachment_type::JUSTIFICATIF_FISCAL :
                $field = 'document_fiscal';
                break;
            case attachment_type::AUTRE1 :
                $field = 'autre1';
                break;
            case attachment_type::AUTRE2 :
                $field = 'autre2';
                break;
            case attachment_type::AUTRE3:
                $field = 'autre3';
                break;
            default :
                return false;
        }

        $resultUpload = $this->attachmentHelper->upload($lenderAccountId, attachment::LENDER, $attachmentType, $field, $this->path, $this->upload, $this->attachment);

        if(false === $resultUpload) {
            $this->form_ok = false;
            $this->error_fichier = true;
        }

        return $resultUpload;
    }
}