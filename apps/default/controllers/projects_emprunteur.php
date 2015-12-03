<?php

class projects_emprunteurController extends bootstrap
{
	var $Command;

	function projects_emprunteurController($command,$config,$app)
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
			// Etape de transition
			$this->settings->get('Etape de transition','type');
			$id_tree = $this->settings->value;
			$slug = $this->tree->getSlug($id_tree,$this->language);

			// check preteur ou emprunteur (ou les deux)
			$this->clients->checkStatusPreEmp($this->clients->status_pre_emp,'emprunteur',$this->clients->status_transition,$slug);
		}




		// Heure fin periode funding
		$this->settings->get('Heure fin periode funding','type');
		$this->heureFinFunding = $this->settings->value;

		//Recuperation des element de traductions
		$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
		$this->lng['projects'] = $this->ln->selectFront('emprunteur-projects',$this->language,$this->App);

		$this->page = 'projects';

	}

	function _default()
	{


		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->echeanciers = $this->loadData('echeanciers');

		$this->companies->get($this->clients->id_client,'id_client_owner');
		$this->companies_details->get($this->companies->id_company,'id_company');

		$this->ordreProject = 1;

		$_SESSION['ordreProject'] = $this->ordreProject;

		// Liste des projets en funding
		$this->lProjetsFunding = $this->projects->selectProjectsByStatus('30,50,60,70,80',' AND id_company = '.$this->companies->id_company,$this->tabOrdreProject[$this->ordreProject]);

		// Nb projets en funding
		$this->nbProjects = $this->projects->countSelectProjectsByStatus('30,50,60,70,80','AND id_company = '.$this->companies->id_company);


	}

	function _detail()
	{
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->favoris = $this->loadData('favoris');
		$this->emprunteur = $this->loadData('clients');
		$this->projects_status = $this->loadData('projects_status');
		$this->companies_actif_passif = $this->loadData('companies_actif_passif');
		$this->companies_bilans = $this->loadData('companies_bilans');
		$this->transactions = $this->loadData('transactions');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->loans = $this->loadData('loans');
		$this->bids = $this->loadData('bids');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->transactions = $this->loadData('transactions');
		$this->projects_status_history = $this->loadData('projects_status_history');
		$this->notifications = $this->loadData('notifications');
		$this->preteur = $this->loadData('clients');


		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project') && $this->projects->status == '0' || isset($this->params[0]) && $this->projects->get($this->params[0],'slug') && $this->projects->status == '0')
		{

			// Pret min
			$this->settings->get('Pret min','type');
			$this->pretMin = $this->settings->value;

			// On recup la companie
			$this->companies->get($this->projects->id_company,'id_company');


			// si pas le projet du mec en session on le vire
			if($this->companies->id_client_owner == $this->clients->id_client)
			{

			}
			else
			{
				header('Location:'.$this->lurl);
				die;
			}

			// Notes
			$this->lNotes = array('A' => 'etoile5','B' => 'etoile4','C' => 'etoile3','D' => 'etoile2','E' => 'etoile1');


			// l'emprunteur
			$this->emprunteur->get($this->companies->id_client_owner,'id_client');

			// On recupere le lender
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');

			// Statut du projet
			$this->projects_status->getLastStatut($this->projects->id_project);

			// Nb projets en funding
			$this->nbProjects = $this->projects->countSelectProjectsByStatus(50);

			// dates pour le js
			$this->mois_jour = $this->dates->formatDate($this->projects->date_retrait,'F d');
			$this->annee = $this->dates->formatDate($this->projects->date_retrait,'Y');

			// intervalle aujourd'hui et retrait
			$inter = $this->dates->intervalDates(date('Y-m-d h:i:s'),$this->projects->date_retrait.' '.$this->heureFinFunding);
			if($inter['mois']>0) $this->dateRest = $inter['mois'].' mois';
			else $this->dateRest = '';

			// Date de retrait complete
			if($this->projects_status->status == 50)
			{
				$this->date_retrait = $this->dates->formatDateComplete($this->projects->date_retrait.' '.$this->heureFinFunding);
				$this->heure_retrait = substr($this->heureFinFunding,0,2);
			}
			else
			{
				$this->date_retrait = $this->dates->formatDateComplete($this->projects->date_fin);
				$this->heure_retrait = $this->dates->formatDate($this->projects->date_fin,'G');
			}


			$this->ordreProject = 1;
			if(isset($_SESSION['ordreProject']))
			{
				$this->ordreProject = $_SESSION['ordreProject']	;
			}

			// id_project avant et apres
			$this->positionProject = $this->projects->positionProject($this->projects->id_project,'',$this->tabOrdreProject[$this->ordreProject]);

			// favori
			if($this->favoris->get($this->clients->id_client,'id_project = '.$this->projects->id_project.' AND id_client'))
				$this->favori = 'active';
			else
				$this->favori = '';

			// Liste des actif passif
			$this->listAP = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'"','annee DESC');
			// Totaux actif/passif
			$this->totalAnneeActif = array();
			$this->totalAnneePassif = array();
			foreach($this->listAP as $ap)
			{
				$this->totalAnneeActif[$ap['ordre']] = ($ap['immobilisations_corporelles']+$ap['immobilisations_incorporelles']+$ap['immobilisations_financieres']+$ap['stocks']+$ap['creances_clients']+$ap['disponibilites']+$ap['valeurs_mobilieres_de_placement']);
				$this->totalAnneePassif[$ap['ordre']] = ($ap['capitaux_propres']+$ap['provisions_pour_risques_et_charges']+$ap['dettes_financieres']+$ap['dettes_fournisseurs']+$ap['autres_dettes']);
			}

			// Bilans
			$lBilans = $this->companies_bilans->select('id_company = "'.$this->companies->id_company.'"','date DESC');
			foreach($lBilans as $b)
			{
				$this->lBilans[$b['date']] = $b;
			}

			// les 3 dernieres vrais années
			$this->anneeToday[1] = (date('Y')-1);
			$this->anneeToday[2] = (date('Y')-2);
			$this->anneeToday[3] = (date('Y')-3);

			// la sum des encheres
			$this->soldeBid = $this->bids->getSoldeBid($this->projects->id_project);

			// solde payé
			$this->payer = $this->soldeBid;

			// Reste a payer
			$this->resteApayer = ($this->projects->amount-$this->soldeBid);

			$this->pourcentage = ((1-($this->resteApayer/$this->projects->amount))*100);

			$this->decimales = 2;
			$this->decimalesPourcentage = 1;
			if($this->soldeBid >= $this->projects->amount)
			{
				$this->payer = $this->projects->amount;
				$this->resteApayer = 0;
				$this->pourcentage = 100;
				$this->decimales = 0;
				$this->decimalesPourcentage = 0;

				$this->solde_ok = true;
			}


			// Liste des encheres enregistrées
			$this->lEnchere = $this->bids->select('id_project = '.$this->projects->id_project,'ordre ASC');

			$this->CountEnchere = $this->bids->counter('id_project = '.$this->projects->id_project);

			$this->avgAmount = $this->bids->getAVG($this->projects->id_project,'amount','0');

			//$this->avgRate = $this->bids->getAVG($this->projects->id_project,'rate');

			// moyenne pondéré
			$montantHaut = 0;
			$tauxBas = 0;
			$montantBas = 0;
			if($this->projects_status->status==70)
			{

				foreach($this->bids->select('id_project = '.$this->projects->id_project) as $b)
				{
					$montantHaut += ($b['rate']*($b['amount']/100));
					$montantBas += ($b['amount']/100);
					$tauxBas += $b['rate'];
				}
			}
			// emprun refusé
			elseif($this->projects_status->status==75)
			{
				foreach($this->bids->select('id_project = '.$this->projects->id_project.' AND status = 1') as $b)
				{
					$montantHaut += ($b['rate']*($b['amount']/100));
					$montantBas += ($b['amount']/100);
					$tauxBas += $b['rate'];
				}
			}
			else
			{
				foreach($this->bids->select('id_project = '.$this->projects->id_project.' AND status = 0' ) as $b)
				{
					$montantHaut += ($b['rate']*($b['amount']/100));
					$tauxBas += $b['rate'];
					$montantBas += ($b['amount']/100);
				}
			}

			if($montantHaut!=0 && $montantBas!=0) $this->avgRate = ($montantHaut/$montantBas);
			else $this->avgRate = 0;
			/*if($montantHaut!=0 && $tauxBas!=0) $this->avgAmount = ($montantHaut/$tauxBas)*100;
			else $this->avgAmount = 0;*/

			// status enchere
			$this->status = array($this->lng['preteur-projets']['enchere-en-cours'],$this->lng['preteur-projets']['enchere-ok'],$this->lng['preteur-projets']['enchere-ko']);


			///////////////////////////////
			// Si le projet est en fundé // ou remb
			///////////////////////////////
			if($this->projects_status->status == 60 || $this->projects_status->status == 80)
			{


				// Nb preteurs validés
				$this->NbPreteurs = $this->loans->getNbPreteurs($this->projects->id_project);

				// Taux moyen des encheres validés (all du projet)
				$this->AvgLoans = $this->loans->getAvgLoans($this->projects->id_project,'rate');



				$date1 = strtotime($this->projects->date_publication.' 00:00:00');
				$date2 = strtotime($this->projects->date_fin);
				$this->interDebutFin = $this->dates->dateDiff($date1,$date2);

				//$lProjetAvecBids = $this->bids->getProjetAvecBid();
				//print_r($lProjetAvecBids);


			}

		}
		else
		{
			header('Location:'.$this->lurl);
			die;
		}

		// on met a jour les infos
		if(isset($_POST['send_form_update_project']) &&  $this->projects->get($this->params[0],'id_project'))
		{
			// enregistrement des informations
			$this->projects->id_company = $this->companies->id_company;
			$this->projects->amount = str_replace(',','.',str_replace(' ','',$_POST['montant']));
			$this->projects->period = $_POST['duree'];
			$this->projects->title = $_POST['project-title'];
			$this->projects->objectif_loan = $_POST['credit-objective'];
			$this->projects->presentation_company = $_POST['presentation'];
			$this->projects->means_repayment = $_POST['moyen'];

			$this->form_ok = true;

			if(!isset($_POST['montant']) || $_POST['montant'] == '' || $_POST['montant'] == $this->lng['etape3']['montant'])
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['duree']) || $_POST['duree'] == '' || $_POST['duree'] == 0)
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['project-title']) || $_POST['project-title'] == '' || $_POST['project-title'] == $this->lng['etape3']['titre-projet'])
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['credit-objective']) || $_POST['credit-objective'] == '' || $_POST['credit-objective'] == $this->lng['etape3']['objectif-du-credit'])
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['presentation']) || $_POST['presentation'] == '' || $_POST['presentation'] == $this->lng['etape3']['presentation-de-la-societe'])
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['moyen']) || $_POST['moyen'] == '' || $_POST['moyen'] == $this->lng['etape3']['moyen-de-remboursement-prevu'])
			{
				$this->form_ok = false;
			}

			// Si form ok
			if($this->form_ok == true)
			{
				$this->projects->update();

				header('Location:'.$this->lurl.'/projects_emprunteur/detail/'.$this->projects->slug);
				die;
			}

		}

		// UPLOAD POUVOIR
		$this->projects_pouvoir = $this->loadData('projects_pouvoir');

		if(isset($_POST['form_send_pouvoir']))
		{
			$this->upload_pouvoir = false;
			// pouvoir
			if(isset($_FILES['pouvoir']) && $_FILES['pouvoir']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/pouvoir/');
				if($this->upload->doUpload('pouvoir'))
				{
					if($this->projects_pouvoir->name != '')@unlink($this->path.'protected/pouvoir/'.$this->projects_pouvoir->name);
					$this->projects_pouvoir->name = $this->upload->getName();
				}

				$this->projects_pouvoir->id_project = $this->projects->id_project;
				$this->projects_pouvoir->id_universign = 'no_universign';
				$this->projects_pouvoir->url_pdf = '/pdf/pouvoir/'.$this->clients->hash.'/'.$this->projects->id_project;
				$this->projects_pouvoir->status = 1;

				$this->projects_pouvoir->create();
				$this->upload_pouvoir = true;

			}
		}


		/// form terminer ///
		if(isset($_POST['id_project']) &&  $this->projects->get($_POST['id_project'],'id_project'))
		{
			die;// bloqué
			// on recup la companie
			$this->companies->get($this->projects->id_company,'id_company');

			// on verifie que la personne qui change le projet est la bonne
			if($this->companies->id_client_owner == $this->clients->id_client)
			{

				$this->projects->date_fin = date('Y-m-d H:i:s');
				$this->projects->update();

				// Solde total obtenue dans l'enchere
				$solde = $this->bids->getSoldeBid($this->projects->id_project);

				// Fundé
				if($solde >= $this->projects->amount)
				{
					// on passe le projet en fundé
					$this->projects_status_history->addStatus(-1,60,$this->projects->id_project);


					// on liste les encheres
					$this->lEnchere = $this->bids->select('id_project = '.$this->projects->id_project.' AND status = 0','rate ASC,added ASC');
					$leSoldeE = 0;
					foreach($this->lEnchere as $k => $e)
					{
						// on parcour les encheres jusqu'au montant de l'emprunt
						if($leSoldeE < $this->projects->amount)
						{
							// le montant preteur (x100)
							$amount = $e['amount'];

							// le solde total des encheres
							$leSoldeE += ($e['amount']/100);

							// Pour la partie qui depasse le montant de l'emprunt
							if($leSoldeE > $this->projects->amount)
							{
								// on recup la diff
								$diff = $leSoldeE-$this->projects->amount;
								// on retire le trop plein et ca nous donne la partie de montant a recup
								$amount = ($e['amount']/100)-$diff;

								$amount = $amount*100;

								// Montant a redonner au preteur
								$montant_a_crediter = ($diff*100);

								// On recup lenders_accounts
								$this->lenders_accounts->get($e['id_lender_account'],'id_lender_account');


								// On enregistre la transaction
								$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
								$this->transactions->montant = $montant_a_crediter;
								$this->transactions->id_langue = 'fr';
								$this->transactions->date_transaction = date('Y-m-d H:i:s');
								$this->transactions->status = '1';
								$this->transactions->etat = '1';
								$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
								$this->transactions->type_transaction = 2;
								$this->transactions->transaction = 2; // transaction virtuelle
								$this->transactions->id_transaction = $this->transactions->create();

								// on enregistre la transaction dans son wallet
								$this->wallets_lines->id_lender = $e['id_lender_account'];
								$this->wallets_lines->type_financial_operation = 20;
								$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
								$this->wallets_lines->status = 1;
								$this->wallets_lines->type = 2;
								$this->wallets_lines->amount =  $montant_a_crediter;
								$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();

								$this->notifications->type = 1; // rejet
								$this->notifications->id_lender = $e['id_lender_account'];
								$this->notifications->id_project = $e['id_project'];
								$this->notifications->amount = $montant_a_crediter;
								$this->notifications->id_bid = $e['id_bid'];
								$this->notifications->create();


							}

							// on crée l'emprunt avec l'argent des encheres récupéré
							$this->loans->id_bid = $e['id_bid'];
							$this->loans->id_lender = $e['id_lender_account'];
							$this->loans->id_project = $e['id_project'];
							$this->loans->amount = $amount;
							$this->loans->rate = $e['rate'];
							$this->loans->create();

							// On recupere le bid
							$this->bids->get($e['id_bid'],'id_bid');
							$this->bids->status = 1;
							$this->bids->update();

							/*echo 'id_bid : '.$e['id_bid'].'<br>';
							echo 'id_lender : '.$e['id_lender_account'].'<br>';
							echo ' added : '.$e['added'].'<br>';
							echo ' Rate : '.$e['rate'].'<br>';
							echo ' amount : '.($e['amount']/100).'<br>';
							echo ' le solde : '.$leSoldeE.'<br>';
							echo '--------------------<br>';*/

							// ancient mail bid ok 100% //


						}
						// Pour les encheres qui depassent on rend l'argent
						else
						{
							// On recup lenders_accounts
							$this->lenders_accounts->get($e['id_lender_account'],'id_lender_account');

							// On enregistre la transaction
							$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
							$this->transactions->montant = $e['amount'];
							$this->transactions->id_langue = 'fr';
							$this->transactions->date_transaction = date('Y-m-d H:i:s');
							$this->transactions->status = '1';
							$this->transactions->etat = '1';
							$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
							$this->transactions->type_transaction = 2;
							$this->transactions->transaction = 2; // transaction virtuelle
							$this->transactions->id_transaction = $this->transactions->create();

							// on enregistre la transaction dans son wallet
							$this->wallets_lines->id_lender = $e['id_lender_account'];
							$this->wallets_lines->type_financial_operation = 20;
							$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
							$this->wallets_lines->status = 1;
							$this->wallets_lines->type = 2;
							$this->wallets_lines->amount = $e['amount'];
							$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();

							// On recupere le bid
							$this->bids->get($e['id_bid'],'id_bid');
							$this->bids->status = 2;
							$this->bids->update();

							$this->notifications->type = 1; // rejet
							$this->notifications->id_lender = $e['id_lender_account'];
							$this->notifications->id_project = $e['id_project'];
							$this->notifications->amount = $e['amount'];
							$this->notifications->id_bid = $e['id_bid'];
							$this->notifications->create();

							// ancient mail enchere ko //


						}

					}



					////**************************************//
//					//*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
//					//**************************************//
//
//					// Recuperation du modele de mail
//					$this->mails_text->get('emprunteur-dossier-funde','lang = "'.$this->language.'" AND type');
//
//					// emprunteur
//					$e = $this->loadData('clients');
//					$e->get($this->companies->id_client_owner,'id_client');
//
//					// taux moyen
//					$loans = $this->loadData('loans');
//					$taux_moyen = $loans->getAvgLoans($this->projects->id_project,'rate');
//
//					// Variables du mailing
//					$surl = $this->surl;
//					$url = $this->lurl;
//					$projet = $this->projects->title;
//					$link_mandat = $this->surl.'/images/default/mandat.jpg';
//					$link_pouvoir = $this->lurl.'/pdf/pouvoir/'.$this->clients->hash.'/'.$this->projects->id_project;
//
//
//					// FB
//					$this->settings->get('Facebook','type');
//					$lien_fb = $this->settings->value;
//
//					// Twitter
//					$this->settings->get('Twitter','type');
//					$lien_tw = $this->settings->value;
//
//
//					// Variables du mailing
//					$varMail = array(
//					'surl' => $surl,
//					'url' => $url,
//					'prenom_e' => utf8_decode($e->prenom),
//					'taux_moyen' => $taux_moyen,
//					'link_compte_emprunteur' => $this->lurl.'/projects/detail/'.$this->projects->id_project,
//					'temps_restant' => '',
//					'link_mandat' => $link_mandat,
//					'link_pouvoir' => $link_pouvoir,
//					'projet' => $projet,
//					'lien_fb' => $lien_fb,
//					'lien_tw' => $lien_tw);
//
//
//					// Construction du tableau avec les balises EMV
//					$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
//
//					// Attribution des données aux variables
//					$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);
//					$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
//					$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
//
//					// Envoi du mail
//					$this->email = $this->loadLib('email',array());
//					$this->email->setFrom($this->mails_text->exp_email,$exp_name);
//					$this->email->addRecipient(trim($this->clients->email));
//					//$this->email->addBCCRecipient($this->clients->email);
//
//					$this->email->setSubject(stripslashes($sujetMail));
//					$this->email->setHTMLBody(stripslashes($texteMail));
//					Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->clients->email,$tabFiler);
//
//					// Injection du mail NMP dans la queue
//					//$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
//
//
//
//
//					//*********************************************//
//					//*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//
//					//*********************************************//
//
//					$this->settings->get('Adresse notifications','type');
//					$destinataire = $this->settings->value;
//
//					$this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);
//
//					// Recuperation du modele de mail
//					$this->mails_text->get('notification-projet-funde-a-100','lang = "'.$this->language.'" AND type');
//
//					// Variables du mailing
//					$surl = $this->surl;
//					$url = $this->lurl;
//					$id_projet = $this->projects->id_project;
//					$title_projet = $this->projects->title;
//					$nbPeteurs = $this->nbPeteurs;
//					$tx = $this->projects->target_rate;
//					$periode = $this->projects->period;
//
//					// Attribution des données aux variables
//					$sujetMail = $this->mails_text->subject;
//					eval("\$sujetMail = \"$sujetMail\";");
//
//					$texteMail = $this->mails_text->content;
//					eval("\$texteMail = \"$texteMail\";");
//
//					$exp_name = $this->mails_text->exp_name;
//					eval("\$exp_name = \"$exp_name\";");
//
//					// Nettoyage de printemps
//					$sujetMail = strtr($sujetMail,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
//					$exp_name = strtr($exp_name,'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
//
//					// Envoi du mail
//					$this->email = $this->loadLib('email',array());
//					$this->email->setFrom($this->mails_text->exp_email,$exp_name);
//					$this->email->addRecipient(trim($destinataire));
//					//$this->email->addBCCRecipient('');
//
//					$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
//					$this->email->setHTMLBody($texteMail);
//					Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
//					// fin mail




					////// on appelle la fonction pour créer les echeances //////
					$this->create_echeances($this->projects->id_project);

					// une fois qu' on a tt créé on lance les mails aux preteurs

					// on parcourt les bids ok et ko du projet et on envoie les mail
					$lBids = $this->bids->select('id_project = '.$projects['id_project'].' AND status > 0');

					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;

					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;

					foreach($lBids as $b)
					{
						// On recup le projet
						$this->projects->get($b['id_project'],'id_project');
						// le lender
						$this->lenders_accounts->get($b['id_lender_account'],'id_lender_account');
						// On recup le client
						$preteur = $this->loadData('clients');
						$preteur->get($this->lenders_accounts->id_client_owner,'id_client');

						// on recup la companie de l'emprunteur
						$companies = $this->loadData('companies');
						$companies->get($this->projects->id_company,'id_company');


						// Bid OK
						if($b['status'] == 1)
						{
							//*********************************//
							//*** ENVOI DU MAIL BID OK 100% ***//
							//*********************************//

							// on recup la premiere echeance
							$echeanciers = $this->loadData('echeanciers');
							$echeanciers = getPremiereEcheancePreteur($b['id_project'],$b['id_lender_account']);

							// Recuperation du modele de mail
							$this->mails_text->get('preteur-bid-ok','lang = "'.$this->language.'" AND type');

							// Variables du mailing
							$surl = $this->surl;
							$url = $this->lurl;
							$prenom = $preteur->prenom;
							$projet = $this->projects->title;
							$montant_pret = $this->ficelle->formatNumber($b['amount']/100);
							$taux = $this->ficelle->formatNumber($b['rate']);
							$entreprise = $companies->name;
							$date = $this->dates->formatDate($b['added'],'d/m/Y');
							$heure = $this->dates->formatDate($b['added'],'H');
							$duree = $this->projects->period;

							// Variables du mailing
							$varMail = array(
							'surl' => $surl,
							'url' => $url,
							'prenom_p' => $prenom,
							'valeur_bid' => $montant,
							'taux_bid' => $taux,
							'nom_entreprise' => $entreprise,
							'nbre_echeance' => $duree,
							'mensualite_p' => $echeanciers['montant'],
							'date_debut' => date('d/m/Y',strtotime($echeanciers['date_echeance'])),
							'compte-p' => $this->lurl,
							'projet-p' => $this->lurl.'/projects/detail/'.$this->projects->slug,
							'lien_fb' => $lien_fb,
							'lien_tw' => $lien_tw);


							// Construction du tableau avec les balises EMV
							$tabVars = $this->tnmp->constructionVariablesServeur($varMail);

							// Attribution des données aux variables
							/*$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);
							$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
							$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);

							// Envoi du mail
							$this->email = $this->loadLib('email',array());
							$this->email->setFrom($this->mails_text->exp_email,$exp_name);
							//$this->email->addRecipient(trim('courtier.damien@gmail.com'));
							//$this->email->addBCCRecipient($this->clients->email);

							$this->email->setSubject(stripslashes($sujetMail));
							$this->email->setHTMLBody(stripslashes($texteMail));
							Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.courtier@equinoa.com',$tabFiler);

							// Injection du mail NMP dans la queue
							$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);*/
						}
						// Bid KO
						else
						{
							//****************************//
							//*** ENVOI DU MAIL BID KO ***//
							//****************************//


							// Recuperation du modele de mail
							$this->mails_text->get('preteur-bid-ko','lang = "'.$this->language.'" AND type');

							// Variables du mailing
							$surl = $this->surl;
							$url = $this->lurl;
							$prenom = $preteur->prenom;
							$projet = $this->projects->title;
							$montant = $this->ficelle->formatNumber($e['amount']/100);
							$taux = $this->ficelle->formatNumber($e['rate']);
							$entreprise = $companies->name;
							$date = $this->dates->formatDate($e['added'],'d/m/Y');
							$heure = $this->dates->formatDate($e['added'],'H\hi').'';

							// Variables du mailing
							$varMail = array(
							'surl' => $surl,
							'url' => $url,
							'prenom_p' => $prenom,
							'valeur_bid' => $montant,
							'taux_bid' => $taux,
							'nom_entreprise' => $entreprise,
							'projet-p' => $this->lurl.'/projects/detail/'.$projects->slug,
							'date_bid' => $date,
							'heure_bid' => $heure,
							'lien_fb' => $lien_fb,
							'lien_tw' => $lien_tw);


							// Construction du tableau avec les balises EMV
							$tabVars = $this->tnmp->constructionVariablesServeur($varMail);

							// Attribution des données aux variables
							/*$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);
							$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
							$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);

							// Envoi du mail
							$this->email = $this->loadLib('email',array());
							$this->email->setFrom($this->mails_text->exp_email,$exp_name);
							//$this->email->addRecipient(trim('courtier.damien@gmail.com'));
							//$this->email->addBCCRecipient($this->clients->email);

							$this->email->setSubject(stripslashes($sujetMail));
							$this->email->setHTMLBody(stripslashes($texteMail));
							Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.courtier@equinoa.com',$tabFiler);

							// Injection du mail NMP dans la queue
							$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);*/

							// fin mail enchere ko //
						}
					}


					header('Location:'.$this->lurl.'/projects_emprunteur/detail/'.$this->projects->slug);
					die;
				}
				else
				{
					// no ok
				}



			}

		}
		/////////////////////
	}


	// On créer les echeances des futures remb
	function create_echeances($id_project)
	{

		// chargement des datas
		$this->loans = $this->loadData('loans');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->echeanciers = $this->loadData('echeanciers');

		// Chargement des librairies
		$this->remb = $this->loadLib('remb');

		$this->settings->get('Commission remboursement','type');
		$com = $this->settings->value;

		// On definit le nombre de mois et de jours apres la date de fin pour commencer le remboursement
		$this->settings->get('Nombre de mois apres financement pour remboursement','type');
		$nb_mois = $this->settings->value;
		$this->settings->get('Nombre de jours apres financement pour remboursement','type');
		$nb_jours = $this->settings->value;

		// tva (0.196)
		$this->settings->get('TVA','type');
		$tva = $this->settings->value;


		// EQ-Acompte d'impôt sur le revenu
		$this->settings->get("EQ-Acompte d'impôt sur le revenu",'type');
		$prelevements_obligatoires = $this->settings->value;

		// EQ-Contribution additionnelle au Prélèvement Social
		$this->settings->get('EQ-Contribution additionnelle au Prélèvement Social','type');
		$contributions_additionnelles = $this->settings->value;

		// EQ-CRDS
		$this->settings->get('EQ-CRDS','type');
		$crds = $this->settings->value;

		// EQ-CSG
		$this->settings->get('EQ-CSG','type');
		$csg = $this->settings->value;

		// EQ-Prélèvement de Solidarité
		$this->settings->get('EQ-Prélèvement de Solidarité','type');
		$prelevements_solidarite = $this->settings->value;

		// EQ-Prélèvement social
		$this->settings->get('EQ-Prélèvement social','type');
		$prelevements_sociaux = $this->settings->value;

		// EQ-Retenue à la source
		$this->settings->get('EQ-Retenue à la source','type');
		$retenues_source = $this->settings->value;

		// On recupere le statut
		$this->projects_status->getLastStatut($id_project);

		// Si le projet est bien en funde on créer les echeances
		if($this->projects_status->status == 60)
		{
			// On recupere le projet
			$this->projects->get($id_project,'id_project');

			echo '-------------------<br>';
			echo 'id Projet : '.$this->projects->id_project.'<br>';
			echo 'date fin de financement : '.$this->projects->date_fin.'<br>';
			echo '-------------------<br>';

			// Liste des loans du projet
			$lLoans = $this->loans->select('id_project = '.$this->projects->id_project);

			// on parcourt les loans du projet en remboursement
			foreach($lLoans as $l)
			{
				//////////////////////////////
				// Echeancier remboursement //
				//////////////////////////////

				$this->lenders_accounts->get($l['id_lender'],'id_lender_account');
				$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');

				$capital = ($l['amount']/100);
				$nbecheances = $this->projects->period;
				$taux = ($l['rate']/100);
				$commission = $com;
				$tva = $tva;




				$tabl = $this->remb->echeancier($capital,$nbecheances,$taux,$commission,$tva);

				$donneesEcheances = $tabl[1];
				$lEcheanciers = $tabl[2];


				// on crée les echeances de chaques preteurs
				foreach($lEcheanciers as $k => $e)
				{
					// Date d'echeance preteur
					$dateEcheance = $this->dates->dateAddMoisJours($this->projects->date_fin,$k,$nb_jours);
					$dateEcheance = date('Y-m-d h:i',$dateEcheance).':00';

					// Date d'echeance emprunteur
					$dateEcheance_emprunteur = $this->dates->dateAddMoisJours($this->projects->date_fin,$k,0);
					$dateEcheance_emprunteur = date('Y-m-d h:i',$dateEcheance_emprunteur).':00';


					// particulier
					if($this->clients->type == 1)
					{
						// si exonéré le particulier na pas de montant_prelevements_obligatoires
						if($this->lenders_accounts->exonere == 1) $montant_prelevements_obligatoires = 0;
						else $montant_prelevements_obligatoires = round($prelevements_obligatoires*$e['interets'],2);

						$montant_contributions_additionnelles = round($contributions_additionnelles*$e['interets'],2);
						$montant_crds = round($crds*$e['interets'],2);
						$montant_csg = round($csg*$e['interets'],2);
						$montant_prelevements_solidarite = round($prelevements_solidarite*$e['interets'],2);
						$montant_prelevements_sociaux = round($prelevements_sociaux*$e['interets'],2);
						$montant_retenues_source = 0;
					}
					// entreprise
					else
					{
						$montant_prelevements_obligatoires = 0;
						$montant_contributions_additionnelles = 0;
						$montant_crds = 0;
						$montant_csg = 0;
						$montant_prelevements_solidarite = 0;
						$montant_prelevements_sociaux = 0;
						$montant_retenues_source = round($retenues_source*$e['interets'],2);

					}


					$this->echeanciers->id_lender = $l['id_lender'];
					$this->echeanciers->id_project = $this->projects->id_project;
					$this->echeanciers->id_loan = $l['id_loan'];
					$this->echeanciers->ordre = $k;
					$this->echeanciers->montant = $e['echeance']*100;
					$this->echeanciers->capital = $e['capital']*100;
					$this->echeanciers->interets = $e['interets']*100;
					$this->echeanciers->commission = $e['commission']*100;
					$this->echeanciers->tva = $e['tva']*100;
					$this->echeanciers->prelevements_obligatoires = $montant_prelevements_obligatoires;
					$this->echeanciers->contributions_additionnelles = $montant_contributions_additionnelles;
					$this->echeanciers->crds = $montant_crds;
					$this->echeanciers->csg = $montant_csg;
					$this->echeanciers->prelevements_solidarite = $montant_prelevements_solidarite;
					$this->echeanciers->prelevements_sociaux = $montant_prelevements_sociaux;
					$this->echeanciers->retenues_source = $montant_retenues_source;
					$this->echeanciers->date_echeance = $dateEcheance;
					$this->echeanciers->date_echeance_emprunteur = $dateEcheance_emprunteur;
					$this->echeanciers->create();
				}
			}

		}
	}

}