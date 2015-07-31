<?php

class alimentationController extends bootstrap
{
	var $Command;
	
	function alimentationController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// On prend le header account
		$this->setHeader('header_account');
		
		// On check si y a un compte
		if(!$this->clients->checkAccess())
		{
			header('Location:'.$this->lurl);
			die;
		}
		else
		{
			// check preteur ou emprunteur (ou les deux)
			$this->clients->checkStatusPreEmp($this->clients->status_pre_emp,'preteur',$this->clients->id_client);
		}
		
		$this->page = 'alimentation';
		
		//Recuperation des element de traductions
		$this->lng['preteur-alimentation'] = $this->ln->selectFront('preteur-alimentation',$this->language,$this->App);
		$this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3',$this->language,$this->App);
		
	}
	
	function _default()
	{
		// On recup la lib et le reste payline
		require_once($this->path.'protected/payline/include.php');
				
		// Chargement des datas
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		$this->transactions = $this->loadData('transactions');
		$this->backpayline = $this->loadData('backpayline');
		$this->virements = $this->loadData('virements');
		$this->prelevements = $this->loadData('prelevements');
		$this->clients_status = $this->loadData('clients_status');
		
		$this->settings->get('Virement - aide par banque','type');
		$this->aide_par_banque = $this->settings->value;
		
		$this->settings->get('Virement - IBAN','type');
		$iban = $this->settings->value;
		
		$this->settings->get('Virement - BIC','type');
		$this->bic = $this->settings->value;
		
		$this->settings->get('Virement - domiciliation','type');
		$this->domiciliation = $this->settings->value;
		
		$this->settings->get('Virement - titulaire du compte','type');
		$this->titulaire = $this->settings->value;
		
		// On recupere l'adresse client
		$this->clients_adresses->get($this->clients->id_client,'id_client');
		
		// On recupere le lender account
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// si c'est une societe
		if($this->clients->type == 2)
		{
			$this->companies->get($this->clients->id_client,'id_client_owner');
			
			// cgu societe
			$this->settings->get('Lien conditions generales inscription preteur societe','type');
			$this->lienConditionsGenerales = $this->settings->value;
		}
		else
		{
			// cgu particulier
			$this->settings->get('Lien conditions generales inscription preteur particulier','type');
			$this->lienConditionsGenerales = $this->settings->value;
		}
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);

		if($this->clients_status->status < 60) $this->retrait_ok = false;
		else $this->retrait_ok = true;
		
		
		if($iban != '')
		{			
			$this->iban[1] = substr($iban,0,4);
			$this->iban[2] = substr($iban,4,4);
			$this->iban[3] = substr($iban,8,4);
			$this->iban[4] = substr($iban,12,4);
			$this->iban[5] = substr($iban,16,4);
			$this->iban[6] = substr($iban,20,4);
			$this->iban[7] = substr($iban,24,3);
			
			$this->etablissement = substr($iban,4,5);
			$this->guichet = substr($iban,9,5);
			$this->compte = substr($iban,14,11);
			$this->cle = substr($iban,25,2);
			
			$this->leiban = '';
			foreach($this->iban as $iban){
				$this->leiban .= $iban.' ';
			}
		}
		else{
			$this->leiban = '';
			$this->etablissement = '';
			$this->guichet = '';
			$this->compte = '';
			$this->cle = '';
		}
		
		/*// Motif virement
		$p = substr($this->clients->prenom,0,1);
		$nom = $this->clients->nom;
		$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
		$this->motif = strtoupper($id_client.$p.$nom);*/
		
		// Motif virement
		$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
		$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
		$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
		$this->motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
		
		
		// Prelevement
		if(isset($_POST['sendPrelevement']))
		{
			$montant = str_replace(array(' ','€'),'',$_POST['montant_prelevement']);
			$montant = str_replace(',','.',$montant);
			
			$form_ok = true;
			
			if(!isset($_POST['montant_prelevement']) || !is_numeric($montant))
			{
				$form_ok = false;
			}
			if(!isset($_POST['rib_prelevement']) || $_POST['rib_prelevement'] == '' || $_POST['rib_prelevement'] == 'RIB')
			{
				$form_ok = false;
			}
			if(!isset($_POST['iban_prelevement']) || $_POST['iban_prelevement'] == '' || $_POST['iban_prelevement'] == 'IBAN')
			{
				$form_ok = false;
			}
			if(!isset($_POST['type_prelevement']) || $_POST['type_prelevement'] == '')
			{
				$form_ok = false;
			}
			if(!isset($_POST['jour_prelevement']) || $_POST['jour_prelevement'] == '')
			{
				$form_ok = false;
			}
			/*if(!isset($_POST['accept-cgu']) || $_POST['accept-cgu'] == false)
			{
				$form_ok = false;
			}*/
			
			// si infos prelevement ok
			if($form_ok == true)
			{

				// acceptation des cgu 
				if($this->acceptations_legal_docs->get($this->lienConditionsGenerales,'id_client = "'.$this->clients->id_client.'" AND id_legal_doc')) $accepet_ok = true;
				else $accepet_ok = false;
					
				$this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
				$this->acceptations_legal_docs->id_client = $this->clients->id_client;
				
				if($accepet_ok == true)$this->acceptations_legal_docs->update();
				else $this->acceptations_legal_docs->create();
				
				
				// transaction
				$this->transactions->id_client = $this->clients->id_client;
				$this->transactions->montant = $montant*100;
				$this->transactions->id_langue = 'fr';
				$this->transactions->date_transaction = date('Y-m-d H:i:s');
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
				$this->transactions->type_transaction = 7; // on signal que c'est une alimentation par prelevement
				$this->transactions->transaction = 1; // transaction physique
				$this->transactions->id_transaction = $this->transactions->create();
				
				// prelevements
				$this->prelevements->id_client = $this->clients->id_client;
				$this->prelevements->id_transaction = $this->transactions->id_transaction;
				$this->prelevements->montant = $_POST['montant_prelevement']*100;
				$this->prelevements->rib = $_POST['rib_prelevement'];
				$this->prelevements->iban = $_POST['iban_prelevement'];
				$this->prelevements->type_prelevement = $_POST['type_prelevement'];
				$this->prelevements->jour_prelevement = $_POST['jour_prelevement'];
				$this->prelevements->type = 1;
				$this->prelevements->motif = $this->motif;
				$this->prelevements->id_prelevement = $this->prelevements->create();
				
				// Motif virement
				/*$p = substr($this->clients->prenom,0,1);
				$nom = $this->clients->nom;
				$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
				$motif = strtoupper($id_client.$p.$nom);*/
				
				// Motif virement
				$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
				$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
				$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				
				//******************************//
				//*** ENVOI DU MAIL preteur-alimentation ***//
				//******************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('preteur-alimentation','lang = "'.$this->language.'" AND type');
				
				// FB
				$this->settings->get('Facebook','type');
				$lien_fb = $this->settings->value;
				
				// Twitter
				$this->settings->get('Twitter','type');
				$lien_tw = $this->settings->value;
				
				$pageProjets = $this->tree->getSlug(4,$this->language);
				
				// Variables du mailing
				$varMail = array(
				'surl' => $this->surl,
				'url' => $this->lurl,
				'prenom_p' => $this->clients->prenom,
				'fonds_depot' => ($_POST['montant_prelevement']/100),
				'solde_p' => $this->solde+($_POST['montant_prelevement']/100),
				'link_mandat' => $this->surl.'/images/default/mandat.jpg',
				'motif_virement' => $motif,
				'projets' => $this->lurl.'/'.$pageProjets,
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
				
				header('location:'.$this->lurl.'/alimentation/confirmation/p');
				die;
			}
			
		}
		
		// Virement
		//if(isset($_POST['sendVirement'])  && isset($_POST['accept-cgu']) && $_POST['accept-cgu'] != false)
		if(isset($_POST['sendVirement']))
		{
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
			$this->clients_history_actions->histo(1,'alim virement',$this->clients->id_client,$serialize);
			////////////////
			
			// aucun envoie de virement ici
			
			// acceptation des cgu 
			/*if($this->acceptations_legal_docs->get($this->lienConditionsGenerales,'id_client = "'.$this->clients->id_client.'" AND id_legal_doc')) $accepet_ok = true;
			else $accepet_ok = false;
				
			$this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
			$this->acceptations_legal_docs->id_client = $this->clients->id_client;
			
			if($accepet_ok == true)$this->acceptations_legal_docs->update();
			else $this->acceptations_legal_docs->create();*/
			
			
			//******************************//
			//*** ENVOI DU MAIL preteur-alimentation ***//
			//******************************//

			// Recuperation du modele de mail
			/*$this->mails_text->get('preteur-alimentation','lang = "'.$this->language.'" AND type');
			
			// Variables du mailing
			$surl = $this->surl;
			$url = $this->lurl;
			$email = $this->clients->email;
			$link_mandat = $this->surl.'/images/default/mandat.jpg';
			$prenom = $this->clients->prenom;
			$message = 'virement en attente';
			
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
			'prenom_p' => utf8_decode($prenom),
			'fonds_depot' => '',
			'solde_p' => $this->solde,
			'link_mandat' => $link_mandat,
			'lien_fb' => $lien_fb,
			'lien_tw' => $lien_tw);	
			
			
			// Construction du tableau avec les balises EMV
			$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
			
			// Attribution des données aux variables
			$sujetMail = strtr($this->mails_text->subject,$tabVars);				
			$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
			$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
			
			// Envoi du mail
			$this->email = $this->loadLib('email',array());
			$this->email->setFrom($this->mails_text->exp_email,$exp_name);
			$this->email->addRecipient(trim($this->clients->email));
			//$this->email->addBCCRecipient($this->clients->email);
			
			$this->email->setSubject(stripslashes($sujetMail));
			$this->email->setHTMLBody(stripslashes($texteMail));
			Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->clients->email,$tabFiler);*/
						
			// Injection du mail NMP dans la queue
			//$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
			// fin mail
			
			
			header('location:'.$this->lurl.'/alimentation/confirmation/v');
			die;
		}
		
		// payment CB
		//if(isset($_POST['sendPaymentCb']) && isset($_POST['accept-cgu']) && $_POST['accept-cgu'] != false)
		if(isset($_POST['sendPaymentCb']))
		{	
		
			$amount = str_replace(array(',',' '),array('.',''),$_POST['amount']);
			
			if(is_numeric($amount) && $amount >= 20 && $amount <=10000){
				$amount = (number_format($amount,2,'.','')*100);
				
				// Histo client //
				$serialize = serialize(array('id_client' => $this->clients->id_client,'post' => $_POST));
				$this->clients_history_actions->histo(2,'alim cb',$this->clients->id_client,$serialize);
				////////////////
				
				
				// acceptation des cgu 
				/*if($this->acceptations_legal_docs->get($this->lienConditionsGenerales,'id_client = "'.$this->clients->id_client.'" AND id_legal_doc')) $accepet_ok = true;
				else $accepet_ok = false;
					
				$this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
				$this->acceptations_legal_docs->id_client = $this->clients->id_client;
				
				if($accepet_ok == true)$this->acceptations_legal_docs->update();
				else $this->acceptations_legal_docs->create();*/
				
				
				
				
				$this->transactions->id_client = $this->clients->id_client;
				$this->transactions->montant = $amount;
				$this->transactions->id_langue = 'fr';
				$this->transactions->date_transaction = date('Y-m-d H:i:s');
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
				$this->transactions->type_transaction = 3; // on signal que une alimentation par cb
				$this->transactions->transaction = 1; // transaction physique
				$this->transactions->id_transaction = $this->transactions->create();
				
				//***************//
				//*** PAYLINE ***//
				//***************//
				
				$array = array();
				$payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
				$payline->returnURL = $this->lurl.'/alimentation/payment/'.$this->clients->hash.'/';
				$payline->cancelURL = $this->lurl.'/alimentation/payment/'.$this->clients->hash.'/';
				$payline->notificationURL = NOTIFICATION_URL;
				
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
				
				$this->transactions->get($this->transactions->id_transaction,'id_transaction');
				$this->transactions->serialize_payline = serialize($result);
				$this->transactions->update();
				
				
				// si on retourne quelque chose
				if(isset($result))
				{
					if($result['result']['code'] == '00000'){
						header("location:".$result['redirectURL']);
						exit();
					}
					// Si erreur on envoie sur mon mail
					elseif(isset($result)) {
						
						mail('d.courtier@equinoa.com','unilend erreur payline','alimentation preteur (client : '.$this->clients->id_client.') | ERROR : '.$result['result']['code']. ' '.$result['result']['longMessage']);
						
						header('location:'.$this->lurl.'/alimentation/erreur/'.$this->clients->hash);
						die;
					}
				}
			}
			
		}
		
		// transferts (executé en ajax)
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
		$this->notifications = $this->loadData('notifications');
		
		$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications'); // add gestion alertes
		$this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif'); // add gestion alertes
		
		
		// On recupere le client
		if(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))
		{
			
			$array = array();
			$payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
			
			// GET TOKEN
			if(isset($_POST['token']))
			{
				$array['token'] = $_POST['token'];
			}
			elseif(isset($_GET['token']))
			{
				$array['token'] = $_GET['token'];
			}
			else
			{
				header('location:'.$this->lurl.'/alimentation');
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
			if(isset($response))
			{
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
				if($response['result']['code'] == '00000')
				{
					if($this->transactions->get($response['order']['ref'],'status = 0 AND etat = 0 AND id_transaction'))
					{
						$this->transactions->id_backpayline = $this->backpayline->id_backpayline;
						$this->transactions->montant = $response['payment']['amount'];
						$this->transactions->id_langue = 'fr';
						$this->transactions->date_transaction = date('Y-m-d H:i:s');
						$this->transactions->status = '1';
						$this->transactions->etat = '1';
						$this->transactions->id_partenaire = $_SESSION['partenaire']['id_partenaire'];
						$this->transactions->type_paiement = ($response['extendedCard']['type'] == 'VISA'?'0':($response['extendedCard']['type'] == 'MASTERCARD'?'3':''));
						$this->transactions->update();
						
						// On recupere le lender
						$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
						$this->lenders_accounts->status = 1;
						$this->lenders_accounts->update();
						
						// On enrgistre la transaction dans le wallet
						$this->wallets_lines->id_lender = $this->lenders_accounts->id_lender_account;
						$this->wallets_lines->type_financial_operation = 30; // Inscription preteur
						$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
						$this->wallets_lines->status = 1;
						$this->wallets_lines->type = 1;
						$this->wallets_lines->amount = $response['payment']['amount'];
						$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
						
						// Transaction physique donc on enregistre aussi dans la bank lines
						$this->bank_lines->id_wallet_line = $this->wallets_lines->id_wallet_line;
						$this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account;
						//$this->bank_lines->type = '' <--- ? 
						$this->bank_lines->status = 1;
						$this->bank_lines->amount = $response['payment']['amount'];
						$this->bank_lines->create();
						
						$this->notifications->type = 6; // alim cb
						$this->notifications->id_lender = $this->lenders_accounts->id_lender_account;
						$this->notifications->amount = $response['payment']['amount'];
						$this->notifications->id_notification = $this->notifications->create();
						
						//////// GESTION ALERTES //////////
						$this->clients_gestion_mails_notif->id_client = $this->lenders_accounts->id_client_owner;
						$this->clients_gestion_mails_notif->id_notif = 7; // alim cb
						$this->clients_gestion_mails_notif->date_notif = date('Y-m-d H:i:s');
						$this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
						$this->clients_gestion_mails_notif->id_transaction = $this->transactions->id_transaction;
						$this->clients_gestion_mails_notif->id_clients_gestion_mails_notif = $this->clients_gestion_mails_notif->create();
						//////// FIN GESTION ALERTES //////////
						
						// on met l'etape inscription a 3
						if($this->clients->etape_inscription_preteur < 3){
							$this->clients->etape_inscription_preteur = 3; // etape 3 ok
							$this->clients->update();
						}
						
						// envoi email bib ok maintenant ou non
						if($this->clients_gestion_notifications->getNotif($this->clients->id_client,7,'immediatement') == true){
							
							//////// GESTION ALERTES //////////
							$this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif,'id_clients_gestion_mails_notif');
							$this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
							$this->clients_gestion_mails_notif->update();
							//////// FIN GESTION ALERTES //////////
						
							// Motif virement
							$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
							$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
							$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
							$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
							
							//******************************//
							//*** ENVOI DU MAIL preteur-alimentation ***//
							//******************************//
				
							// Recuperation du modele de mail
							$this->mails_text->get('preteur-alimentation-cb','lang = "'.$this->language.'" AND type');
							
							// FB
							$this->settings->get('Facebook','type');
							$lien_fb = $this->settings->value;
							
							// Twitter
							$this->settings->get('Twitter','type');
							$lien_tw = $this->settings->value;
							
							$pageProjets = $this->tree->getSlug(4,$this->language);
							
							// Variables du mailing
							$varMail = array(
							'surl' => $this->surl,
							'url' => $this->lurl,
							'prenom_p' => $this->clients->prenom,
							'fonds_depot' => ($response['payment']['amount']/100),
							'solde_p' => $this->solde+($response['payment']['amount']/100),
							'link_mandat' => $this->surl.'/images/default/mandat.jpg',
							'motif_virement' => $motif,
							'projets' => $this->lurl.'/'.$pageProjets,
							'gestion_alertes' => $this->lurl.'/profile',
							'lien_fb' => $lien_fb,
							'lien_tw' => $lien_tw);	
							
							// Construction du tableau avec les balises EMV
							$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
							
							// Attribution des donnÃ©es aux variables
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
						
						
											
						header('location:'.$this->lurl.'/alimentation/confirmation/cb/'.$this->transactions->id_transaction);
						die;
					}
					else // je redirige sur la page par defaut
					{
						header('location:'.$this->lurl.'/alimentation');
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
					
					header('location:'.$this->lurl.'/alimentation');
					die;
				}
				
				// Si erreur
				else
				{
					mail('d.courtier@equinoa.com','unilend payline erreur','erreur sur page payment alimentation preteur (client : '.$this->clients->id_client.') : '.serialize($response));
					
					header('location:'.$this->lurl.'/alimentation/erreur/');
        			die;
				}
			}
		}
	}
	
	function _confirmation()
	{	
		
		if(isset($this->params[0]) && $this->params[0] == 'v')
		{
			header('location:'.$this->lurl.'/'.$this->tree->getSlug(138,$this->language));
		}
		elseif(isset($this->params[0]) && $this->params[0] == 'cb')
		{
			header('location:'.$this->lurl.'/'.$this->tree->getSlug(139,$this->language).'/'.$this->params[1]);
		}
		elseif(isset($this->params[0]) && $this->params[0] == 'p')
		{
			header('location:'.$this->lurl.'/'.$this->tree->getSlug(140,$this->language));
		}
		else
		{
			header('location:'.$this->lurl.'/alimentation');
			die;
		}
	}
	
	function _erreur()
	{
		// On recupere le client
		/*if(isset($this->params[0]) && $this->clients->get($this->params[0],'hash'))
		{
			
		}
		else
		{
			header('location:'.$this->lurl.'/alimentation');
			die;	
		}*/
	}
	
	
	
}