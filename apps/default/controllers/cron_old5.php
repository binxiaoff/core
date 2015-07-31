<?php
class cronController extends bootstrap
{
	var $Command;
	
	function cronController(&$command,$config)
	{
		parent::__construct($command,$config,'default');
		
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireView = false;
		$this->autoFireDebug = false;

		// Securisation des acces
		if(isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'],$this->Config['ip_admin'][$this->Config['env']]))
		{
			
			die;
		}
		
	}
	
	//********************//
	//*** A LA DEMANDE ***//
	//********************//
	
	function _default()
	{
		die;
	}
	
	//*******************//
	//*** AUTOMATIQUE ***//
	//*******************//
	
	//*************************************//
	//*** ENVOI DE LA QUEUE DE MAIL NMP ***//
	//*************************************//
	function _queueNMP()
	{
		if($this->Config['env'] == 'prod')
		{
			$this->tnmp->processQueue();
		}
		die;
	}
	
	// Les taches executées toutes les minutes
	function _minute()
	{	
	
		die;
	}	
	
	// Les taches executées tous les jours
	function _jour()
	{	
		//**************************//
		//*** INDEXATION DU SITE ***//
		//**************************//		
		if($this->params[0] == 'recherche')
		{		
			// Chargement de la librairie
			$this->se = $this->loadLib('elgoog',array($this->bdd));
			
			// On index
			$this->se->index();
		}
		
		
		die;
	}
	
	// toutes les minute on check //
	// on regarde si il y a des projets au statut "a funder" et on les passe en statut "en funding"
	function _check_projet_a_funder()
	{
		
		
		// chargement des datas
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->projects_status_history = $this->loadData('projects_status_history');
		
		
		// Heure debut periode funding
		$this->settings->get('Heure debut periode funding','type');
		$this->heureDebutFunding = $this->settings->value;
		
		$this->lProjects = $this->projects->selectProjectsByStatus(40);
		
		foreach($this->lProjects as $projects)
		{
			//$datePublication = $projects['date_publication'];
			//$today = date('Y-m-d H:i');
			
			// EDIT KLE : 
			// On récupère desormais la date full et pas la date avec l'heure en params
			$tabdatePublication = explode(':',$projects['date_publication_full']);
			$datePublication = $tabdatePublication[0].':'.$tabdatePublication[1];
			$today = date('Y-m-d H:i');
			
			//echo 'datePublication : '.$datePublication.' '.$this->heureDebutFunding.'<br>';
			//echo 'today : '.$today.'<br><br>';

			echo 'datePublication : '.$datePublication.'<br>';
			echo 'today : '.$today.'<br><br>';
			
			////////// test ////////////
			//$today = '2013-11-13 07:00'; // mettre la date de publication du projet
			//$this->heureDebutFunding = '07:00';
			////////////////////////////
			
			//if($datePublication.' '.$this->heureDebutFunding == $today ) // on lance le matin a 7h00
			if($datePublication == $today ) // on lance en fonction de l'heure definie dans le bo
			{
				$this->projects_status_history->addStatus(-1,50,$projects['id_project']);
				
				//mail('d.courtier@equinoa.com','unilend '.$this->Config['env'].' cron','check_projet_a_funder date : '.date('d/m/y H:i:s').' id_projet : '.$projects['id_project']);
				
			}
		}
	}
	
	// toutes les 5 minutes on check // (old 10 min)
	// On check les projet a faire passer en fundé ou en funding ko
	function _check_projet_en_funding()
	{
		
		//mail('d.courtier@equinoa.com',' le cronn unilend '.$this->Config['env'].' cron','le cron passe : '.date('d/m/y H:i:s'));
		
		
		// chargement des datas
		$this->bids = $this->loadData('bids');
		$this->loans = $this->loadData('loans');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->transactions = $this->loadData('transactions');
		$this->companies = $this->loadData('companies');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->projects_status_history = $this->loadData('projects_status_history');
		$this->notifications = $this->loadData('notifications');
		$this->offres_bienvenues_details = $this->loadData('offres_bienvenues_details');
		$offres_bienvenues_details = $this->loadData('offres_bienvenues_details');
		
		// Heure fin periode funding
		$this->settings->get('Heure fin periode funding','type');
		$this->heureFinFunding = $this->settings->value;
		
		// Cron fin funding minutes suplémentaires avant traitement
		$this->settings->get('Cron fin funding minutes suplémentaires avant traitement','type');
		$this->minutesEnPlus = $this->settings->value;
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		

		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		$todayTemp = date('Y-m-d H:i:s');
		/////////////// Teste ////////////////
		//$today = '2014-04-02 17:15';
		//////////////////////////////////////
		
		
		// On recup le param
		$settingsControleCheck_projet_en_funding = $this->loadData('settings');
		$settingsControleCheck_projet_en_funding->get('Controle cron check_projet_en_funding','type');
		
		if($settingsControleCheck_projet_en_funding->value == 1)
		{
			// On passe le statut a zero pour signaler qu'on est en cours de traitement
			$settingsControleCheck_projet_en_funding->value = 0;
			$settingsControleCheck_projet_en_funding->update();
		
			// projets en funding
			$this->lProjects = $this->projects->selectProjectsByStatus(50);
			
			foreach($this->lProjects as $projects)
			{
				
				// on transforme la date retrait en time
				//$dateretrait = strtotime($projects['date_retrait'].' '.$this->heureFinFunding.':00');
				//$today = strtotime($todayTemp);				
				
				// EDIT KLE : 
				// On récupère desormais la date full et pas la date avec l'heure en params
				$tabdateretrait = explode(':',$projects['date_retrait_full']);
				$dateretrait = $tabdateretrait[0].':'.$tabdateretrait[1];
				$today = date('Y-m-d H:i');
				
			
				
				// pour fin projet manuel
				if($projects['date_fin'] != '0000-00-00 00:00:00')
				{
					$tabdatefin = explode(':',$projects['date_fin']);
					$datefin = $tabdatefin[0].':'.$tabdatefin[1];					

					$dateretrait = $datefin;
					//$dateretrait = strtotime($projects['date_fin']);
				}
				
				// old On ajoute 30 min a la date de retrait +$this->minutesEnPlus
				//$dateretraitTemp = mktime (date("H",$dateretraitTemp),date("i",$dateretraitTemp)+$this->minutesEnPlus,0,date("m",$dateretraitTemp),date("d",$dateretraitTemp),date("Y",$dateretraitTemp));
				
				// On reorganise la date de retrait au bon format avec les 30 minutes en plus
				//$dateretrait = date('Y-m-d H:i',$dateretraitTemp);
				
				/*echo 'id projet : '.$projects['id_project'].'<br>';
				echo 'date_retrait : '.$dateretrait.'<br>';
				echo 'today : '.$today.'<br>';
				echo '-------------<br>';*/
				
								
				
				if($dateretrait <= $today) // on termine a 16h00
				{
					// on regarde si tout a bien été traité
					//if($this->bids->select('id_project = '.$projects['id_project'].' AND checked = 0') == false)
					//{
						//mail('d.courtier@equinoa.com','unilend '.$this->Config['env'].' cron','check_projet_en_funding date : '.date('d/m/y H:i:s').' id_projet : '.$projects['id_project']);
						
						// On enregistre la date de fin
						$this->projects->get($projects['id_project'],'id_project');
						$this->projects->date_fin = date('Y-m-d H:i:s');
						$this->projects->update();
						
						// Solde total obtenue dans l'enchere
						$solde = $this->bids->getSoldeBid($projects['id_project']);
		
						// Fundé
						if($solde >= $projects['amount'])
						{
							// on passe le projet en fundé
							$this->projects_status_history->addStatus(-1,60,$projects['id_project']);
							
							// on liste les encheres
							$this->lEnchere = $this->bids->select('id_project = '.$projects['id_project'].' AND status = 0','rate ASC,added ASC');
							$leSoldeE = 0;
							foreach($this->lEnchere as $k => $e)
							{
								// on parcour les encheres jusqu'au montant de l'emprunt
								if($leSoldeE < $projects['amount'])
								{
									// le montant preteur (x100)
									$amount = $e['amount'];
									
									// le solde total des encheres
									$leSoldeE += ($e['amount']/100);
									
									// Pour la partie qui depasse le montant de l'emprunt ( ca cest que pour le mec a qui on decoupe son montant)
									if($leSoldeE > $projects['amount'])
									{
										// on recup la diff
										$diff = $leSoldeE-$projects['amount'];
										// on retire le trop plein et ca nous donne la partie de montant a recup
										$amount = ($e['amount']/100)-$diff;
										
										$amount = $amount*100;
										
										// Montant a redonner au preteur
										$montant_a_crediter = ($diff*100);
										
										// On recup lenders_accounts
										$this->lenders_accounts->get($e['id_lender_account'],'id_lender_account');
										// On recupere le bid
										$this->bids->get($e['id_bid'],'id_bid');
										
										// On regarde si on a pas deja un remb pour ce bid
										//if($this->transactions->get($e['id_bid'],'id_bid_remb')==false)
										if($this->bids->status == '0')
										{
											mail('d.courtier@equinoa.com','debug cron degel',$this->lenders_accounts->id_client_owner.' - id bid :'.$e['id_bid']);
											// On enregistre la transaction
											$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
											$this->transactions->montant = $montant_a_crediter;
											$this->transactions->id_bid_remb = $e['id_bid'];
											$this->transactions->id_langue = 'fr';
											$this->transactions->date_transaction = date('Y-m-d H:i:s');
											$this->transactions->status = '1';
											$this->transactions->etat = '1';
											$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
											$this->transactions->type_transaction = 2;
											$this->transactions->id_project = $e['id_project'];
											$this->transactions->transaction = 2; // transaction virtuelle
											$this->transactions->id_transaction = $this->transactions->create();
											
											// on enregistre la transaction dans son wallet
											$this->wallets_lines->id_lender = $e['id_lender_account'];
											$this->wallets_lines->type_financial_operation = 20;
											$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
											$this->wallets_lines->status = 1;
											$this->wallets_lines->type = 2;
											$this->wallets_lines->id_bid_remb = $e['id_bid']; 
											$this->wallets_lines->amount =  $montant_a_crediter;
											$this->wallets_lines->id_project = $e['id_project'];
											$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
											
											$this->notifications->type = 1; // rejet
											$this->notifications->id_lender = $e['id_lender_account'];
											$this->notifications->id_project = $e['id_project'];
											$this->notifications->amount = $montant_a_crediter;
											$this->notifications->id_bid = $e['id_bid'];
											$this->notifications->create();
											
											
											/// OFFRES DE BIENVENUES /// (on remet a disposition les offres du preteur)
											
											/*$sumOffres = $this->offres_bienvenues_details->sum('id_client = '.$this->lenders_accounts->id_client_owner.' AND id_bid = '.$e['id_bid'],'montant');
											
											if($sumOffres > 0){
												//  si les offres depasses le montant qu'on garde dans le pret
												if($sumOffres >= $amount){
													// On fait la diff pour redonner en offre
													$montant_offre_coupe_a_remb = $sumOffres - $amount;
													
													$this->offres_bienvenues_details->montant 				= $montant_offre_coupe_a_remb;	
													$this->offres_bienvenues_details->id_offre_bienvenue 	= 0;
													$this->offres_bienvenues_details->id_client 			= $this->lenders_accounts->id_client_owner;
													$this->offres_bienvenues_details->id_bid 				= 0;
													$this->offres_bienvenues_details->id_bid_remb 			= $e['id_bid'];
													$this->offres_bienvenues_details->status 				= 0;
													$this->offres_bienvenues_details->type					= 2;
													
													$this->offres_bienvenues_details->create();
													
												}
												// depasse pas c'est good on redonne pas les offres
												else{
													
												}
											}*/
											
											
											
											
											/// FIN OFFRES DE BIENVENUES ///
											
										}
										
										
									}
									
									if($this->loans->get($e['id_bid'],'id_bid')==false)
									{
										// On recupere le bid
										$this->bids->get($e['id_bid'],'id_bid');
										$this->bids->status = 1;
										$this->bids->update();
										
										// on crée l'emprunt avec l'argent des encheres récupéré
										$this->loans->id_bid = $e['id_bid'];
										$this->loans->id_lender = $e['id_lender_account'];
										$this->loans->id_project = $e['id_project'];
										$this->loans->amount = $amount;
			
										$this->loans->rate = $e['rate'];
										$this->loans->create();
										
										
									}
									
									/*echo 'id_bid : '.$e['id_bid'].'<br>';
									echo 'id_lender : '.$e['id_lender_account'].'<br>';
									echo ' added : '.$e['added'].'<br>';
									echo ' Rate : '.$e['rate'].'<br>';
									echo ' amount : '.($e['amount']/100).'<br>';
									echo ' le solde : '.$leSoldeE.'<br>';
									echo '--------------------<br>';*/
									
									// ancient mail bid ok 100%
									
									
								}
								// Pour les encheres qui depassent on rend l'argent
								else
								{
									// On recupere le bid
									$this->bids->get($e['id_bid'],'id_bid');
									
									// On regarde si on a pas deja un remb pour ce bid
									//if($this->transactions->get($e['id_bid'],'id_bid_remb')==false)
									if($this->bids->status == '0')
									{
										//mail('d.courtier@equinoa.com','debug cron degel 2',$this->lenders_accounts->id_client_owner.' - id bid :'.$e['id_bid']);
										
										$this->bids->status = 2;
										$this->bids->update();
										
										// On recup lenders_accounts
										$this->lenders_accounts->get($e['id_lender_account'],'id_lender_account');
															
										// On enregistre la transaction
										$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
										$this->transactions->id_bid_remb = $e['id_bid'];
										$this->transactions->montant = $e['amount'];
										$this->transactions->id_langue = 'fr';
										$this->transactions->date_transaction = date('Y-m-d H:i:s');
										$this->transactions->status = '1';
										$this->transactions->etat = '1';
										$this->transactions->id_project = $e['id_project'];
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
										$this->wallets_lines->id_project = $e['id_project'];
										$this->wallets_lines->id_bid_remb = $e['id_bid']; 
										$this->wallets_lines->amount = $e['amount'];
										$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
										
										
										
										$this->notifications->type = 1; // rejet
										$this->notifications->id_lender = $e['id_lender_account'];
										$this->notifications->id_project = $e['id_project'];
										$this->notifications->amount = $e['amount'];
										$this->notifications->id_bid = $e['id_bid'];
										$this->notifications->create();
										// ancient mail enchere ko //
										
										/// OFFRES DE BIENVENUES /// (on remet a disposition les offres du preteur)
											
										/*$sumOffres = $this->offres_bienvenues_details->sum('id_client = '.$this->lenders_accounts->id_client_owner.' AND id_bid = '.$e['id_bid'],'montant');
										if($sumOffres > 0){
											// sum des offres inferieur au montant a remb
											if($sumOffres <= $e['amount']){
												$this->offres_bienvenues_details->montant 			= $sumOffres;
											}
											// Si montant des offres superieur au remb on remb le montant a crediter
											else{
												$this->offres_bienvenues_details->montant 			= $e['amount'];
											}
											
											$this->offres_bienvenues_details->id_offre_bienvenue 	= 0;
											$this->offres_bienvenues_details->id_client 			= $this->lenders_accounts->id_client_owner;
											$this->offres_bienvenues_details->id_bid 				= 0;
											$this->offres_bienvenues_details->id_bid_remb 			= $e['id_bid'];
											$this->offres_bienvenues_details->status 				= 0;
											$this->offres_bienvenues_details->type					= 2;
											
											$this->offres_bienvenues_details->create();
										}*/
										
										/// FIN OFFRES DE BIENVENUES ///
									}
									
		
								}
		
							}
							
		
							////// on appelle la fonction pour créer les echeances //////
							$this->create_echeances($projects['id_project']);
							$this->createEcheancesEmprunteur($projects['id_project']);
							///////////// partie mail emprunteur projet fundé terminé ///////////////////////
							
							// Chargement des datas
							$e = $this->loadData('clients');
							$loan = $this->loadData('loans');
							$project = $this->loadData('projects');
							$companie = $this->loadData('companies');
							$echeancier = $this->loadData('echeanciers');
							$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
							
							//**************************************//
							//*** ENVOI DU MAIL FUNDE EMPRUNTEUR TERMINE ***//
							//**************************************//
							
							// On recup le projet
							$project->get($projects['id_project'],'id_project');
							
							// On recup la companie
							$companie->get($project->id_company,'id_company');
							
							// Recuperation du modele de mail
							$this->mails_text->get('emprunteur-dossier-funde-et-termine','lang = "'.$this->language.'" AND type');
							
							// emprunteur
							$e->get($companie->id_client_owner,'id_client');
							
							// taux moyen
							//$taux_moyen = $loan->getAvgLoans($project->id_project,'rate');
							
							$montantHaut = 0;
							$montantBas = 0;
							foreach($loan->select('id_project = '.$project->id_project) as $b)
							{
								$montantHaut += ($b['rate']*($b['amount']/100));
								$montantBas += ($b['amount']/100);
							}
							$taux_moyen = ($montantHaut/$montantBas);
							
							
							
							//$mensualite = $echeancier->getSumRembEmpruntByMonths($project->id_project);
							//$mensualite = $mensualite[1]['montant'];
							
							$echeanciers_emprunteur->get($project->id_project,'ordre = 1 AND id_project');
							$mensualite = $echeanciers_emprunteur->montant+$echeanciers_emprunteur->commission+$echeanciers_emprunteur->tva;
							$mensualite = ($mensualite/100);
		
							// Variables du mailing
							$surl = $this->surl;
							$url = $this->lurl;
							$projet = $project->title;
							$link_mandat = $this->lurl.'/pdf/mandat/'.$e->hash.'/'.$project->id_project;
							$link_pouvoir = $this->lurl.'/pdf/pouvoir/'.$e->hash.'/'.$project->id_project;
			
				
							// Variables du mailing
							$varMail = array(
							'surl' => $surl,
							'url' => $url,
							'prenom_e' => $e->prenom,
							'nom_e' => $companie->name,
							'mensualite' => number_format($mensualite, 2, ',', ' '),
							'montant' => number_format($project->amount, 0, ',', ' '),
							'taux_moyen' => number_format($taux_moyen, 2, ',', ' '),
							'link_compte_emprunteur' => $this->lurl.'/projects/detail/'.$project->id_project,
							'link_mandat' => $link_mandat,
							'link_pouvoir' => $link_pouvoir,
							'projet' => $projet,
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
								Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$e->email,$tabFiler);			
								// Injection du mail NMP dans la queue
								$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
							}
							else // non nmp
							{
								$this->email->addRecipient(trim($e->email));
								Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
							}
							
							
							////////////////////////////////////
							// NOTIFICATION PROJET FUNDE 100% //
							////////////////////////////////////
							
							// On recup le projet
							$this->projects->get($projects['id_project'],'id_project');
							
							// On recup la companie
							$this->companies->get($this->projects->id_company,'id_company');
							
							// On recup l'emprunteur
							$this->clients->get($this->companies->id_client_owner,'id_client');
							
							// destinataire
							$this->settings->get('Adresse notification projet funde a 100','type');
							$destinataire = $this->settings->value;
							//$destinataire = 'd.courtier@equinoa.com';
							
							// Solde des encheres du project
							$montant_collect = $this->bids->getSoldeBid($this->projects->id_project);
							
							// si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
							if(($montant_collect/100) >= $this->projects->amount) $montant_collect = $this->projects->amount;
							
							$this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);
							
							
							
							// Recuperation du modele de mail
							$this->mails_text->get('notification-projet-funde-a-100','lang = "'.$this->language.'" AND type');
							
							// Variables du mailing
							$surl = $this->surl;
							$url = $this->lurl;
							$id_projet = $this->projects->id_project;
							$title_projet = $this->projects->title;
							$nbPeteurs = $this->nbPeteurs;
							$tx = $taux_moyen;
							$montant_pret = $this->projects->amount;
							$montant = $montant_collect;
							$periode =  $this->projects->period;
							
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
							
							
							////////////////////////////////////
							
							
							///////////// partie mail bid ok ///////////////////////
							
							
							$loans = $this->loadData('loans');
							$preteur = $this->loadData('clients');
							$companies = $this->loadData('companies');
							$echeanciers = $this->loadData('echeanciers');
							
							// on parcourt les bids ok du projet et on envoie les mail
							$lLoans = $this->loans->select('id_project = '.$projects['id_project']);
							
							foreach($lLoans as $l)
							{
								// On recup le projet
								$this->projects->get($l['id_project'],'id_project');
								// le lender
								$this->lenders_accounts->get($l['id_lender'],'id_lender_account');
								// On recup le client
								$preteur->get($this->lenders_accounts->id_client_owner,'id_client');
								// on recup la companie de l'emprunteur
								$companies->get($this->projects->id_company,'id_company');
								
								
								// Bid OK
								
								// Motif virement
								$p = substr($this->ficelle->stripAccents(utf8_decode(trim($preteur->prenom))),0,1);
								$nom = $this->ficelle->stripAccents(utf8_decode(trim($preteur->nom)));
								$id_client = str_pad($preteur->id_client,6,0,STR_PAD_LEFT);
								$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
								
								//*********************************//
								//*** ENVOI DU MAIL BID OK 100% ***//
								//*********************************//
								
								// on recup la premiere echeance
								//$lecheancier = $echeanciers->getPremiereEcheancePreteur($l['id_project'],$l['id_lender']);
								$lecheancier = $echeanciers->getPremiereEcheancePreteurByLoans($l['id_project'],$l['id_lender'],$l['id_loan']);
								
								
								// Recuperation du modele de mail
								$this->mails_text->get('preteur-bid-ok','lang = "'.$this->language.'" AND type');
								
								// Variables du mailing
								$surl = $this->surl;
								$url = $this->lurl;
								$prenom = $preteur->prenom;
								$projet = $this->projects->title;
								$montant_pret = number_format($l['amount']/100, 2, ',', ' ');
								$taux = number_format($l['rate'], 2, ',', ' ');
								$entreprise = $companies->name;
								$date = $this->dates->formatDate($l['added'],'d/m/Y');
								$heure = $this->dates->formatDate($l['added'],'H');
								$duree = $this->projects->period;
								
								$timeAdd = strtotime($lecheancier['date_echeance']);
								$month = $this->dates->tableauMois['fr'][date('n',$timeAdd)];
								
								// Variables du mailing
								$varMail = array(
								'surl' => $this->surl,
								'url' => $this->lurl,
								'prenom_p' => $preteur->prenom,
								'valeur_bid' => number_format($l['amount']/100, 2, ',', ' '),
								'taux_bid' => number_format($l['rate'], 2, ',', ' '),
								'nom_entreprise' => $companies->name,
								'nbre_echeance' => $this->projects->period,
								'mensualite_p' => number_format($lecheancier['montant']/100, 2, ',', ' '),
								'date_debut' => date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd),
								'compte-p' => $this->lurl,
								'projet-p' => $this->lurl.'/projects/detail/'.$this->projects->slug,
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
									Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$preteur->email,$tabFiler);
												
									// Injection du mail NMP dans la queue
									$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
								}
								else // non nmp
								{
									$this->email->addRecipient(trim($preteur->email));
									Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
								}
								
							}
							
							
						}
						// Funding KO (le solde demandé n'a pas ete atteint par les encheres)
						else
						{
							// On passe le projet en funding ko
							$this->projects_status_history->addStatus(-1,70,$projects['id_project']);
							
							
							//*******************************************//
							//*** ENVOI DU MAIL FUNDING KO EMPRUNTEUR ***//
							//*******************************************//
							
							// On recup le projet
							$this->projects->get($projects['id_project'],'id_project');
							
							// On recup la companie
							$this->companies->get($this->projects->id_company,'id_company');
							
							// On recup l'emprunteur
							$this->clients->get($this->companies->id_client_owner,'id_client');
							
							// Recuperation du modele de mail
							$this->mails_text->get('emprunteur-dossier-funding-ko','lang = "'.$this->language.'" AND type');
							
							// Variables du mailing
							$surl = $this->surl;
							$url = $this->lurl;
							$projet = $this->projects->title;
		
							// Variables du mailing
							$varMail = array(
							'surl' => $surl,
							'url' => $url,
							'prenom_e' => $this->clients->prenom,	
							'projet' => $projet,
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
							
		
							// On recupere les encheres
							$this->lEnchere = $this->bids->select('id_project = '.$projects['id_project'],'rate ASC,added ASC');
							// On rend l'argent aux preteurs
							foreach($this->lEnchere as $k => $e)
							{
								// On recup lenders_accounts
								$this->lenders_accounts->get($e['id_lender_account'],'id_lender_account');
													
								// On enregistre la transaction
								$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
								$this->transactions->montant = $e['amount'];
								$this->transactions->id_langue = 'fr';
								$this->transactions->date_transaction = date('Y-m-d H:i:s');
								$this->transactions->status = '1';
								$this->transactions->id_project = $e['id_project'];
								$this->transactions->etat = '1';
								$this->transactions->id_bid_remb = $e['id_bid'];
								$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
								$this->transactions->type_transaction = 2; 
								$this->transactions->transaction = 2; // transaction virtuelle
								$this->transactions->id_transaction = $this->transactions->create();
								
								// on enregistre la transaction dans son wallet
								$this->wallets_lines->id_lender = $e['id_lender_account'];
								$this->wallets_lines->type_financial_operation = 20;
								$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
								$this->wallets_lines->status = 1;
								$this->wallets_lines->id_project = $e['id_project'];
								$this->wallets_lines->type = 2;
								$this->wallets_lines->id_bid_remb = $e['id_bid'];
								$this->wallets_lines->amount = $e['amount'];
								$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
								
								// On recupere le bid
								$this->bids->get($e['id_bid'],'id_bid');
								$this->bids->status = 2;
								$this->bids->update();
								
								/// OFFRES DE BIENVENUES /// (on remet a disposition les offres du preteur)
											
								/*$sumOffres = $this->offres_bienvenues_details->sum('id_client = '.$this->lenders_accounts->id_client_owner.' AND id_bid = '.$e['id_bid'],'montant');
								if($sumOffres > 0){
									// sum des offres inferieur au montant a remb
									if($sumOffres <= $e['amount']){
										$this->offres_bienvenues_details->montant 			= $sumOffres;
									}
									// Si montant des offres superieur au remb on remb le montant a crediter
									else{
										$this->offres_bienvenues_details->montant 			= $e['amount'];
									}
									
									$this->offres_bienvenues_details->id_offre_bienvenue 	= 0;
									$this->offres_bienvenues_details->id_client 			= $this->lenders_accounts->id_client_owner;
									$this->offres_bienvenues_details->id_bid 				= 0;
									$this->offres_bienvenues_details->id_bid_remb 			= $e['id_bid'];
									$this->offres_bienvenues_details->status 				= 0;
									$this->offres_bienvenues_details->type					= 2;
									
									$this->offres_bienvenues_details->create();
								}*/
								
								/// FIN OFFRES DE BIENVENUES ///
								
								// mail enchere ko //
									
								//********************************//
								//*** ENVOI DU MAIL FUNDING KO ***//
								//********************************//
								
								// On recup le projet
								$this->projects->get($e['id_project'],'id_project');
								// On recup le client
								$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
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
								
								$solde_p = $this->transactions->getSolde($this->clients->id_client);
								
								// Recuperation du modele de mail
								$this->mails_text->get('preteur-dossier-funding-ko','lang = "'.$this->language.'" AND type');
								
								$timeAdd = strtotime($e['added']);
								$month = $this->dates->tableauMois['fr'][date('n',$timeAdd)];
								
								// FB
								$this->settings->get('Facebook','type');
								$lien_fb = $this->settings->value;
								
								// Twitter
								$this->settings->get('Twitter','type');
								$lien_tw = $this->settings->value;
								
								// Variables du mailing
								$varMail = array(
								'surl' => $this->surl,
								'url' => $this->lurl,
								'prenom_p' => $this->clients->prenom,
								'entreprise' => $this->companies->name,
								'projet' => $this->projects->title,
								'montant' => number_format($e['amount']/100, 2, ',', ' '),
								'proposition_pret' => number_format(($e['amount']/100), 2, ',', ' '),
								'date_proposition_pret' => date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd),
								'taux_proposition_pret' => $e['rate'],
								'compte-p' => '/projets-a-financer',
								'motif_virement' => $motif,
								'solde_p' => $solde_p,
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
								
								// fin mail enchere ko //
							
							}
							
						} // fin funding ko
						
						//**********************************************//
						//*** ENVOI DU MAIL NOTIFICATION PROJET FINI ***//
						//**********************************************//
						
						// On recup le projet
						$this->projects->get($projects['id_project'],'id_project');
						
						// On recup la companie
						$this->companies->get($this->projects->id_company,'id_company');
						
						// On recup l'emprunteur
						$this->clients->get($this->companies->id_client_owner,'id_client');
						
						// destinataire
						$this->settings->get('Adresse notification projet fini','type');
						$destinataire = $this->settings->value;
						//$destinataire = 'd.courtier@equinoa.com';
						
						// Solde des encheres du project
						$montant_collect = $this->bids->getSoldeBid($this->projects->id_project);
						
						// si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
						if(($montant_collect/100) >= $this->projects->amount) $montant_collect = $this->projects->amount;
						
						$this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);
						
						
						
						// Recuperation du modele de mail
						$this->mails_text->get('notification-projet-fini','lang = "'.$this->language.'" AND type');
						
						// Variables du mailing
						$surl = $this->surl;
						$url = $this->lurl;
						$id_projet = $this->projects->id_project;
						$title_projet = $this->projects->title;
						$nbPeteurs = $this->nbPeteurs;
						$tx = $this->projects->target_rate;
						$montant_pret = $this->projects->amount;
						$montant = $montant_collect;
						
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
					//}
				}
			}
			
			
			$settingsControleCheck_projet_en_funding->value = 1;
			$settingsControleCheck_projet_en_funding->update();
		}
		
	}
	
	
	function _testecheanceeepreteur()
	{
		
		//$this->create_echeances('2784');
		//$this->createEcheancesEmprunteur('2784');
	}
	
	
	// On créer les echeances des futures remb
	function create_echeances($id_project)
	{
		ini_set('max_execution_time', 300); //300 seconds = 5 minutes
		mail('d.courtier@equinoa.com','alerte create echeance','Verification des jours ouvrées sur le projet : '.$id_project);
		// chargement des datas
		$this->loans = $this->loadData('loans');
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->echeanciers = $this->loadData('echeanciers');
		
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		
		// Chargement des librairies
		$this->remb = $this->loadLib('remb');
		$jo = $this->loadLib('jours_ouvres');
		
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
				
				$this->clients_adresses->get($this->lenders_accounts->id_client_owner,'id_client');
				
				// 0 : fr/fr
				// 1 : fr/resident etranger
				// 2 : no fr/resident etranger
				$etranger = 0;
				// fr/resident etranger
				if($this->clients->id_nationalite <= 1 && $this->clients_adresses->id_pays_fiscal > 1){
					$etranger = 1;
				}
				// no fr/resident etranger
				elseif($this->clients->id_nationalite > 1 && $this->clients_adresses->id_pays_fiscal > 1){
					$etranger = 2;
				}
				
				$capital = ($l['amount']/100);
				$nbecheances = $this->projects->period;
				$taux = ($l['rate']/100);
				$commission = $com;
				$tva = $tva;

				$tabl = $this->remb->echeancier($capital,$nbecheances,$taux,$commission,$tva);
				
				$donneesEcheances = $tabl[1];
				$lEcheanciers = $tabl[2];
				
				$nbjoursMois = 0;
				// on crée les echeances de chaques preteurs
				foreach($lEcheanciers as $k => $e)
				{
					// on prend le nombre de jours dans le mois au lieu du mois
					$nbjourstemp = mktime (0,0,0,date("m")+$k ,1,date("Y"));
					$nbjoursMois += date('t',$nbjourstemp);
					
					// Date d'echeance preteur
					$dateEcheance = $this->dates->dateAddMoisJours($this->projects->date_fin,0,$nb_jours+$nbjoursMois);
					$dateEcheance = date('Y-m-d H:i',$dateEcheance).':00';
					
					// Date d'echeance emprunteur
					$dateEcheance_emprunteur = $this->dates->dateAddMoisJours($this->projects->date_fin,0,$nbjoursMois);
					// on retire 6 jours ouvrés
					$dateEcheance_emprunteur = $jo->display_jours_ouvres($dateEcheance_emprunteur,6);
					
					$dateEcheance_emprunteur = date('Y-m-d H:i',$dateEcheance_emprunteur).':00';
			
					
					
					// particulier
					if(in_array($this->clients->type,array(1,3)))
					{
						if($etranger > 0){
							$montant_prelevements_obligatoires = 0;
							$montant_contributions_additionnelles = 0;
							$montant_crds = 0;
							$montant_csg = 0;
							$montant_prelevements_solidarite = 0;
							$montant_prelevements_sociaux = 0;
							$montant_retenues_source = round($retenues_source*$e['interets'],2);
						}
						else{
							if($this->lenders_accounts->exonere == 1){
								
								/// exo date debut et fin ///
								if($this->lenders_accounts->debut_exoneration != '0000-00-00' && $this->lenders_accounts->fin_exoneration != '0000-00-00'){
									if(strtotime($dateEcheance) >= strtotime($this->lenders_accounts->debut_exoneration) && strtotime($dateEcheance) <= strtotime($this->lenders_accounts->fin_exoneration)){
										$montant_prelevements_obligatoires = 0;
									}
									else $montant_prelevements_obligatoires = round($prelevements_obligatoires*$e['interets'],2);	
								}
								/////////////////////////////
								else $montant_prelevements_obligatoires = 0;
								
								
							}
							else $montant_prelevements_obligatoires = round($prelevements_obligatoires*$e['interets'],2);
							
							$montant_contributions_additionnelles = round($contributions_additionnelles*$e['interets'],2);
							$montant_crds = round($crds*$e['interets'],2);
							$montant_csg = round($csg*$e['interets'],2);
							$montant_prelevements_solidarite = round($prelevements_solidarite*$e['interets'],2);
							$montant_prelevements_sociaux = round($prelevements_sociaux*$e['interets'],2);
							$montant_retenues_source = 0;
						}
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
	
	// fonction create echeances emprunteur
	function createEcheancesEmprunteur($id_project)
	{
		mail('d.courtier@equinoa.com','alerte create echeance emprunteur','Verification des jours ouvrées sur le projet : '.$id_project);
		// chargement des datas
		$loans = $this->loadData('loans');
		$projects = $this->loadData('projects');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$echeanciers = $this->loadData('echeanciers');
		
		// Chargement des librairies
		$remb = $this->loadLib('remb');
		$jo = $this->loadLib('jours_ouvres');
		
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
		
		$projects->get($id_project,'id_project');
		
		
		$montantHaut = 0;
		$montantBas = 0;
		foreach($loans->select('id_project = '.$projects->id_project) as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}
		$tauxMoyen = ($montantHaut/$montantBas);
		
		
		$capital = $projects->amount;
		$nbecheances = $projects->period;
		$taux = ($tauxMoyen/100);
		$commission = $com;
		$tva = $tva;
		
		$tabl = $remb->echeancier($capital,$nbecheances,$taux,$commission,$tva);
		
		$donneesEcheances = $tabl[1];
		//$lEcheanciers = $tabl[2];
		
		$lEcheanciers = $echeanciers->getSumRembEmpruntByMonths($projects->id_project);
		
		$nbjoursMois = 0;
		foreach($lEcheanciers as $k => $e)
		{
			$nbjourstemp = mktime (0,0,0,date("m")+$k ,1,date("Y"));
			$nbjoursMois += date('t',$nbjourstemp);
			
			// Date d'echeance emprunteur
			$dateEcheance_emprunteur = $this->dates->dateAddMoisJours($projects->date_fin,0,$nbjoursMois);
			// on retire 6 jours ouvrés
			$dateEcheance_emprunteur = $jo->display_jours_ouvres($dateEcheance_emprunteur,6);
			
			$dateEcheance_emprunteur = date('Y-m-d H:i',$dateEcheance_emprunteur).':00';
			
			$echeanciers_emprunteur->id_project = $projects->id_project;
			$echeanciers_emprunteur->ordre = $k;
			$echeanciers_emprunteur->montant = $e['montant']*100; // sum montant preteurs
			$echeanciers_emprunteur->capital = $e['capital']*100; // sum capital preteurs
			$echeanciers_emprunteur->interets = $e['interets']*100; // sum interets preteurs
			$echeanciers_emprunteur->commission = $donneesEcheances['comParMois']*100; // on recup com du projet
			$echeanciers_emprunteur->tva = $donneesEcheances['tvaCom']*100; // et tva du projet
			$echeanciers_emprunteur->date_echeance_emprunteur = $dateEcheance_emprunteur;
			$echeanciers_emprunteur->create();
		}
		
		//echo 'ok';
	}
	
	// fonction create echeances emprunteur
	function createEcheancesEmprunteur_old($id_project)
	{
		// chargement des datas
		$loans = $this->loadData('loans');
		$projects = $this->loadData('projects');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		
		// Chargement des librairies
		$remb = $this->loadLib('remb');
		
		
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
		
		$projects->get($id_project,'id_project');
		
		
		$montantHaut = 0;
		$montantBas = 0;
		foreach($loans->select('id_project = '.$projects->id_project) as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}
		$tauxMoyen = ($montantHaut/$montantBas);
		
		
		$capital = $projects->amount;
		$nbecheances = $projects->period;
		$taux = (round($tauxMoyen,2)/100);
		$commission = $com;
		$tva = $tva;
		
		$tabl = $remb->echeancier($capital,$nbecheances,$taux,$commission,$tva);
		
		$donneesEcheances = $tabl[1];
		$lEcheanciers = $tabl[2];
		
		
		
		
		foreach($lEcheanciers as $k => $e)
		{
			// Date d'echeance emprunteur
			$dateEcheance_emprunteur = $this->dates->dateAddMoisJours($projects->date_fin,$k,0);
			$dateEcheance_emprunteur = date('Y-m-d H:i',$dateEcheance_emprunteur).':00';
			
			
			$echeanciers_emprunteur->id_project = $projects->id_project;
			$echeanciers_emprunteur->ordre = $k;
			$echeanciers_emprunteur->montant = $e['echeance']*100;
			$echeanciers_emprunteur->capital = $e['capital']*100;
			$echeanciers_emprunteur->interets = $e['interets']*100;
			$echeanciers_emprunteur->commission = $e['commission']*100;
			$echeanciers_emprunteur->tva = $e['tva']*100;
			$echeanciers_emprunteur->date_echeance_emprunteur = $dateEcheance_emprunteur;
			$echeanciers_emprunteur->create();
		}
		
		//echo 'ok';
	}
	
	
	// check les statuts remb
	function _check_status()
	{
		// die temporaire pour eviter de changer le statut du prelevement en retard
		die;
		
		// chargement des datas
		$projects = $this->loadData('projects');
		$projects_status = $this->loadData('projects_status');
		$echeanciers = $this->loadData('echeanciers');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$projects_status_history = $this->loadData('projects_status_history');
		$projects_status = $this->loadData('projects_status');
		$loans = $this->loadData('loans');
		$preteur = $this->loadData('clients');
		$lender = $this->loadData('lenders_accounts');
		$companies = $this->loadData('companies');
		
		// Cabinet de recouvrement
		$this->settings->get('Cabinet de recouvrement','type');
		$ca_recou = $this->settings->value;
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		// Date du jour
		$today = date('Y-m-d');
		//$today = '2015-03-15';
		$time = strtotime($today.' 00:00:00');

		// projets en remb ou en probleme
		$lProjects = $projects->selectProjectsByStatus('80,100');
		
		foreach($lProjects as $p)
		{
			// On recupere le statut
			$projects_status->getLastStatut($p['id_project']);
			
			// On recup les echeances inferieur a la date du jour
			$lEcheancesEmp = $echeanciers_emprunteur->select('id_project = '.$p['id_project'].' AND  	status_emprunteur = 0 AND date_echeance_emprunteur < "'.$today.' 00:00:00"');
			
			foreach($lEcheancesEmp as $e)
			{
				$dateRemb = strtotime($e['date_echeance_emprunteur']);
			
				// si statut remb
				if($projects_status->status == 80)
				{
					// date echeance emprunteur +5j (probleme)
					$laDate = mktime(0,0,0, date("m",$dateRemb), date("d",$dateRemb)+5, date("Y",$dateRemb));
					$type = 'probleme';	
				}
				// statut probleme
				elseif($projects_status->status == 100)
				{
					// date echeance emprunteur +8j (recouvrement)
					$laDate = mktime(0,0,0, date("m",$dateRemb), date("d",$dateRemb)+8, date("Y",$dateRemb));
					$type = 'recouvrement';	
				}
				
				// si la date +nJ est eqale ou depasse
				if($laDate <= $time)
				{
					// probleme
					if($type == 'probleme')
					{
						echo 'probleme<br>';
						$projects_status_history->addStatus(-1,100,$p['id_project']);					
					}
					// recouvrement
					else
					{
						echo 'recouvrement<br>';
						$projects_status_history->addStatus(-1,110,$p['id_project']);
						
							// date du probleme
						$statusProbleme = $projects_status_history->select('id_project = '.$p['id_project'].' AND  	id_project_status = 9','added DESC');
						
						$timeAdd = strtotime($statusProbleme[0]['added']);
						$month = $this->dates->tableauMois['fr'][date('n',$timeAdd)];
						
						$DateProbleme = date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd);
					}
					
					// on recup les prets (donc leurs preteurs)
					$lLoans = $loans->select('id_project = '.$p['id_project']);
					
					// On recup l'entreprise
					$projects->get($p['id_project'],'id_project');
					$companies->get($projects->id_company,'id_company');
					
					
					// on fait le tour des prets
					foreach($lLoans as $l)
					{
						// on recup le preteur
						$lender->get($l['id_lender'],'id_lender_account');
						$preteur->get($lender->id_client_owner,'id_client');
						
						$rembNet = 0;
						
						// Motif virement
						$p = substr($this->ficelle->stripAccents(utf8_decode(trim($preteur->prenom))),0,1);
						$nom = $this->ficelle->stripAccents(utf8_decode(trim($preteur->nom)));
						$id_client = str_pad($preteur->id_client,6,0,STR_PAD_LEFT);
						$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
						
						// probleme
						if($type == 'probleme')
						{
							////////////////////////////////////////////
							// on recup la somme deja remb du preteur //
							////////////////////////////////////////////
							$lEchea = $echeanciers->select('id_loan = '.$l['id_loan'].' AND id_project = '.$p['id_project'].' AND status = 1');
							
							foreach($lEchea as $e)
							{
								// on fait la somme de tout
								$rembNet += ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
							}
							////////////////////////////////////////////
							
							// mail probleme preteur-erreur-remboursement
							
							//**************************************//
							//*** ENVOI DU MAIL PROBLEME PRETEUR ***//
							//**************************************//
				
							// Recuperation du modele de mail
							$this->mails_text->get('preteur-erreur-remboursement','lang = "'.$this->language.'" AND type');
							
							// Variables du mailing
							$varMail = array(
							'surl' => $this->surl,
							'url' => $this->furl,
							'prenom_p' => $preteur->prenom,
							'valeur_bid' => number_format($l['amount']/100, 2, ',', ' '),
							'nom_entreprise' => $companies->name,
							'montant_rembourse' => number_format($rembNet, 2, ',', ' '),
							'cab_recouvrement' => $ca_recou,
							'motif_virement' => $motif,
							'lien_fb' => $lien_fb,
							'lien_tw' => $lien_tw);
							
							// Construction du tableau avec les balises EMV
							/*$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
							
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
								Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$preteur->email,$tabFiler);			
								// Injection du mail NMP dans la queue
								$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
							}
							else // non nmp
							{
								$this->email->addRecipient(trim($preteur->email));
								Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
							}*/
							
							// fin mail pour preteur //
							
						}
						// recouvrement
						else
						{
							// mail recouvrement preteur-dossier-recouvrement
							
							//******************************************//
							//*** ENVOI DU MAIL RECOUVREMENT PRETEUR ***//
							//******************************************//
				
							// Recuperation du modele de mail
							$this->mails_text->get('preteur-dossier-recouvrement','lang = "'.$this->language.'" AND type');
							

							// Variables du mailing
							$varMail = array(
							'surl' => $this->surl,
							'url' => $this->furl,
							'prenom_p' => $preteur->prenom,
							'date_probleme' => $DateProbleme,
							'cab_recouvrement' => $ca_recou,
							'nom_entreprise' => $companies->name,
							'motif_virement' => $motif,
							'lien_fb' => $lien_fb,
							'lien_tw' => $lien_tw);	
							
							// Construction du tableau avec les balises EMV
							/*$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
							
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
								Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$preteur->email,$tabFiler);
											
								// Injection du mail NMP dans la queue
								$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
							}
							else // non nmp
							{
								$this->email->addRecipient(trim($preteur->email));
								Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
							}*/
							
							// fin mail pour preteur //
								
						}
						
						
					}
					
					///////// du plus pour verif ////////////
					// date a chercher
					$laDatesql = date('Y-m-d',$laDate);
					
					/*echo 'today : '.$today.'<br>';
					echo 'date echeance : '.$e['date_echeance_emprunteur'].'<br>';
					echo 'laDate : '.$laDatesql.'<br>';
					echo '------------------<br>';*/
					//////////////////////////////////////////
					
					break;
				}
				

			}
		}
	}

	// On check les virements a envoyer sur le sftp (une fois par jour)
	// les virements sont pour retirer largent du compte unilend vers le true compte client
	function _virements()
	{
		
		// chargement des datas
		$this->virements = $this->loadData('virements');
		$this->clients = $this->loadData('clients');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->compteur_transferts = $this->loadData('compteur_transferts');
		$this->companies = $this->loadData('companies');
		
		// Virement - BIC
		$this->settings->get('Virement - BIC','type');
		$bic = $this->settings->value;
		
		// Virement - domiciliation
		$this->settings->get('Virement - domiciliation','type');
		$domiciliation = $this->settings->value;
		
		// Virement - IBAN
		$this->settings->get('Virement - IBAN','type');
		$iban = $this->settings->value;
		$iban = str_replace(' ','',$iban);
		
		// titulaire du compte
		$this->settings->get('titulaire du compte','type');
		$titulaire = utf8_decode($this->settings->value);
		
		
		// Retrait Unilend - BIC
		$this->settings->get('Retrait Unilend - BIC','type');
		$retraitBic = utf8_decode($this->settings->value);
		// Retrait Unilend - Domiciliation
		$this->settings->get('Retrait Unilend - Domiciliation','type');
		$retraitDom = utf8_decode($this->settings->value);
		// Retrait Unilend - IBAN
		$this->settings->get('Retrait Unilend - IBAN','type');
		$retraitIban = utf8_decode($this->settings->value);
		// Retrait Unilend - Titulaire du compte
		$this->settings->get('Retrait Unilend - Titulaire du compte','type');
		$retraitTitu = utf8_decode($this->settings->value);
		
		// On recupere la liste des virements en cours
		$lVirementsEnCours = $this->virements->select('status = 0 AND added_xml = "0000-00-00 00:00:00" ');
		
		// le nombre de virements
		$nbVirements = $this->virements->counter('status = 0 AND added_xml = "0000-00-00 00:00:00" ');
		
		
		// On recupere la liste des virements en cours
		//$lVirementsEnCours = $this->virements->select('status = 1 AND added_xml = "2014-01-15 11:01:00" ');
		// le nombre de virements
		//$nbVirements = $this->virements->counter('status = 1 AND added_xml = "2014-01-15 11:01:00" ');
		
		// On recupere le total
		$sum = $this->virements->sum('status = 0');
		
		//$sum = $this->virements->sum('status = 1 AND added_xml = "2014-01-15 11:01:00" ');
		$Totalmontants = round($sum/100,2);
		
		// Compteur pour avoir un id différent a chaque fois 
		$nbCompteur = $this->compteur_transferts->counter('type = 1');
		
		// le id_compteur
		$id_compteur = $nbCompteur+1;
		
		// on met a jour le compteur
		$this->compteur_transferts->type = 1;
		$this->compteur_transferts->ordre = $id_compteur;
		$this->compteur_transferts->create();
		
		// date collée
		$dateColle = date('Ymd');
		
		// on recup le id_message
		$id_message = 'SFPMEI/'.$titulaire.'/'.$dateColle.'/'.$id_compteur;
		
		// date creation avec un T entre la date et l'heure
		$date_creation = date('Y-m-d\TH:i:s');
		
		// titulaire compte a debiter
		$compte = $titulaire.'-SFPMEI';
		
		// Date execution
		$date_execution = date('Y-m-d');
		
		
		
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
	<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
		<CstmrCdtTrfInitn>
			<GrpHdr>
				<MsgId>'.$id_message.'</MsgId>
				<CreDtTm>'.$date_creation.'</CreDtTm> 
				<NbOfTxs>'.$nbVirements.'</NbOfTxs>
				<CtrlSum>'.$Totalmontants.'</CtrlSum>
				<InitgPty>
					<Nm>'.$compte.'</Nm>
				</InitgPty>
			</GrpHdr>
			<PmtInf>
				<PmtInfId>'.$titulaire.'/'.$dateColle.'/'.$id_compteur.'</PmtInfId>
				<PmtMtd>TRF</PmtMtd>
				<NbOfTxs>'.$nbVirements.'</NbOfTxs>
				<CtrlSum>'.$Totalmontants.'</CtrlSum>
				<PmtTpInf>
					<SvcLvl>
						<Cd>SEPA</Cd>
					</SvcLvl>
				</PmtTpInf>
				<ReqdExctnDt>'.$date_execution.'</ReqdExctnDt>
				<Dbtr>
					<Nm>SFPMEI</Nm>
					<PstlAdr>
						<Ctry>FR</Ctry>
					</PstlAdr>
				</Dbtr>
				<DbtrAcct>
					<Id>
						<IBAN>'.str_replace(' ','',$iban).'</IBAN>
					</Id>
				</DbtrAcct>
				<DbtrAgt>
					<FinInstnId>
						<BIC>'.str_replace(' ','',$bic).'</BIC>
					</FinInstnId>
				</DbtrAgt>';
		
		
		foreach($lVirementsEnCours as $v)
		{
			$this->clients->get($v['id_client'],'id_client');
			
			// Retrait sfmpei
			if($v['type'] == 4)
			{
				$ibanDestinataire = $retraitIban;
				$bicDestinataire = $retraitBic;
				//$retraitDom;
			}
			elseif($this->clients->status_pre_emp > 1)
			{
				$this->companies->get($v['id_client'],'id_client_owner');
				$ibanDestinataire = $this->companies->iban;
				$bicDestinataire = $this->companies->bic;
			}
			else
			{
				$this->lenders_accounts->get($v['id_client'],'id_client_owner');
				$ibanDestinataire = $this->lenders_accounts->iban;
				$bicDestinataire = $this->lenders_accounts->bic;
			}
			
			
			$this->virements->get($v['id_virement'],'id_virement');
			$this->virements->status = 1; // envoyé
			$this->virements->added_xml = date('Y-m-d H:i').':00';
			$this->virements->update();
			
			// variables
			$id_lot = $titulaire.'/'.$dateColle.'/'.$v['id_virement'];
			$montant = round($v['montant']/100,2);
			
			
			
			$xml .= '
				<CdtTrfTxInf>
					<PmtId>
						<EndToEndId>'.$id_lot.'</EndToEndId>
					</PmtId>
					<Amt>
						<InstdAmt Ccy="EUR">'.$montant.'</InstdAmt>
					</Amt>
					<CdtrAgt>
						<FinInstnId>
							<BIC>'.str_replace(' ','',$bicDestinataire).'</BIC>
						</FinInstnId>
					 </CdtrAgt>
					 <Cdtr>
						 <Nm>'.($v['type'] == 4?$retraitTitu:$this->clients->nom.' '.$this->clients->prenom).'</Nm>
						 <PstlAdr>
							 <Ctry>FR</Ctry>
						 </PstlAdr>
					 </Cdtr>
					 <CdtrAcct>
						 <Id>
							 <IBAN>'.str_replace(' ','',$ibanDestinataire).'</IBAN>
						 </Id>
					 </CdtrAcct>
					 <RmtInf>
						 <Ustrd>'.str_replace(' ','',$v['motif']).'</Ustrd>
					 </RmtInf>
				</CdtTrfTxInf>';
		}
		$xml .= '
			</PmtInf>
		</CstmrCdtTrfInitn>
	</Document>';
		
		echo $xml;
		
		$filename = 'Unilend_Virements_'.date('Ymd');
		
		if($lVirementsEnCours != false)
		{

			if($this->Config['env'] == 'prod')
			{
			   $connection = ssh2_connect('ssh.reagi.com', 22);
			   ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
			   $sftp = ssh2_sftp($connection);
			   $sftpStream = @fopen('ssh2.sftp://'.$sftp.'/home/sfpmei/emissions/virements/'.$filename.'.xml', 'w');
			   fwrite($sftpStream, $xml);
			   fclose($sftpStream);
			}
			
			
			file_put_contents ($this->path.'protected/sftp/virements/'.$filename.'.xml',$xml);
			//mail('d.courtier@equinoa.com','unilend '.$this->Config['env'].' cron','virements date : '.date('d/m/y H:i:s'));
		}
	}
	
	// On check les prelevements a envoyer sur le sftp (une fois par jour)
	function _prelevements()
	{
		// chargement des datas
		$this->prelevements = $this->loadData('prelevements');
		$this->clients = $this->loadData('clients');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->compteur_transferts = $this->loadData('compteur_transferts');
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		$echeanciers = $this->loadData('echeanciers');
		$clients_mandats = $this->loadData('clients_mandats');
		
		// Virement - BIC
		$this->settings->get('Virement - BIC','type');
		$bic = $this->settings->value;
		
		// Virement - domiciliation
		$this->settings->get('Virement - domiciliation','type');
		$domiciliation = $this->settings->value;
		
		// Virement - IBAN
		$this->settings->get('Virement - IBAN','type');
		$iban = $this->settings->value;
		$iban = str_replace(' ','',$iban);
		
		// Virement - titulaire du compte
		$this->settings->get('titulaire du compte','type');
		$titulaire = utf8_decode($this->settings->value);
		
		// Nombre jours avant remboursement pour envoyer une demande de prelevement
		$this->settings->get('Nombre jours avant remboursement pour envoyer une demande de prelevement','type');
		$nbJoursAvant = $this->settings->value;
		
		
		// ICS
		$this->settings->get('ICS de SFPMEI','type');
		$ics = $this->settings->value;
		
		
		$today = date('Y-m-d');
		//// test ////
		//$today = '2014-01-09';
		//////////////
		
		///////////////////////
		/// preteur ponctuel //
		///////////////////////
		
		// On recupere la liste des prelevements en cours preteur ponctuel
		$lPrelevementsEnCoursPeteurPonctuel = $this->prelevements->select('status = 0 AND type = 1 AND type_prelevement = 2');
		//$lPrelevementsEnCoursPeteurPonctuel = $this->prelevements->select();
		
		// le nombre de prelevements preteur ponctuel
		$nbPrelevementsPeteurPonctuel = $this->prelevements->counter('status = 0 AND type = 1 AND type_prelevement = 2');
		// On recupere le total preteur ponctuel
		$sum = $this->prelevements->sum('status = 0 AND type = 1 AND type_prelevement = 2');
		$TotalmontantsPreteurPonctuel = round($sum/100,2);
		////////////////////////
		
		////////////////////////
		/// preteur recurrent //
		////////////////////////
		
		// On recupere la liste des prelevements en cours preteur recurrent
		$lPrelevementsEnCoursPeteurRecu = $this->prelevements->select('type = 1 AND type_prelevement = 1 AND status <> 3');
		// le nombre de prelevements preteur recurrent
		$nbPrelevementsPeteurRecu = $this->prelevements->counter('type = 1 AND type_prelevement = 1 AND status <> 3');
		// On recupere le total preteur recurrent
		//$sum = $this->prelevements->sum('type = 1 AND type_prelevement = 1');
		//$TotalmontantsPreteurRecu = round($sum/100,2);
		
		
		$nbPermanent = 0;
		foreach($lPrelevementsEnCoursPeteurRecu as $p)
		{
			//si jamais eu de prelevement avant
			if($p['status'] == 0)
			{
				$val = 'FRST'; // prelevement ponctuel
			}
			else
			{
				$val = 'RCUR';
				
				// date du xml généré au premier prelevement
				$date_xml = strtotime($p['added_xml']);
				
				// date xml + 1 mois
				$dateXmlPlusUnMois = mktime(date("H",$date_xml), date("i",$date_xml),0, date("m",$date_xml)+1  , date("d",$date_xml), date("Y",$date_xml));
				
				$dateXmlPlusUnMois = date('Y-m-d',$dateXmlPlusUnMois);
				
				////////// test ////////////
				//$dateXmlPlusUnMois = date('Y-m-d');
				///////////////////////////
			}
			
			// si status est a 0 (en cours) ou si le satut est supperieur et que la date du jour est égale a la date xml + 1 mois
			
			// 2 cas possible = 1 : premier prelevement | 2 : prelevement recurrent
			if($p['status'] == 0 || $p['status'] > 0 && $dateXmlPlusUnMois == $today)
			{
				$nbPermanent += 1;
				$montantPermanent += $p['montant'];
			}
			
		}
		
		$nbPrelevementsPeteurRecu = $nbPermanent;
		$TotalmontantsPreteurRecu = $montantPermanent/100;
		
		////////////////////////
		
		///////////////////////////
		/// emprunteur recurrent // <-------------|
		///////////////////////////
		
		
		
		// On recupere la liste des prelevements en cours preteur recurrent
		$lPrelevementsEnCoursEmprunteur = $this->prelevements->select('type = 2 AND type_prelevement = 1 AND status = 0 AND date_execution_demande_prelevement = "'.$today.'"');
		// le nombre de prelevements preteur recurrent
		$nbPrelevementsEmprunteur = $this->prelevements->counter('type = 2 AND type_prelevement = 1 AND status = 0 AND date_execution_demande_prelevement = "'.$today.'"');
		// On recupere le total preteur recurrent
		$sum = $this->prelevements->sum('type = 2 AND type_prelevement = 1 AND status = 0 AND date_execution_demande_prelevement = "'.$today.'"');
		$TotalmontantsEmprunteur = round($sum/100,2);
		
		///////////////////////////
		
		
		
		
		
		// Compteur pour avoir un id différent a chaque fois 
		$nbCompteur = $this->compteur_transferts->counter('type = 2');
		
		// le id_compteur
		$id_compteur = $nbCompteur+1;
		
		// on met a jour le compteur
		$this->compteur_transferts->type = 2; // 2 : prelevement
		$this->compteur_transferts->ordre = $id_compteur;
		$this->compteur_transferts->create();
		
		// date collée
		$dateColle = date('Ymd');
		
		// on recup le id_message
		$id_message = 'SFPMEI/'.$titulaire.'/'.$dateColle.'/'.$id_compteur;
		
		// date creation avec un T entre la date et l'heure
		$date_creation = mktime(date("H"), date("i"),0, date("m")  , date("d")+1, date("Y"));
		
		$date_creation = date('Y-m-d\TH:i:s',$date_creation);
		
		// titulaire compte a debiter
		$compte = $titulaire.'-SFPMEI';
		
		// Nombre de prelevements
		$nbPrelevements = $nbPrelevementsPeteurPonctuel+$nbPrelevementsPeteurRecu+$nbPrelevementsEmprunteur;
		// Montant total
		$Totalmontants = $TotalmontantsPreteurPonctuel+$TotalmontantsPreteurRecu+$TotalmontantsEmprunteur;
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
	<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
		<CstmrDrctDbtInitn>
			<GrpHdr>
				<MsgId>'.$id_message.'</MsgId>
				<CreDtTm>'.$date_creation.'</CreDtTm>
				<NbOfTxs>'.$nbPrelevements.'</NbOfTxs>
				<CtrlSum>'.$Totalmontants.'</CtrlSum>
				<InitgPty>
					<Nm>'.$compte.'</Nm>
				</InitgPty>
			</GrpHdr>';
		
		//////////////////////////////////////////
		/// lPrelevementsEnCoursPeteurPonctuel ///
		foreach($lPrelevementsEnCoursPeteurPonctuel as $p)
		{
			
			$this->clients->get($p['id_client'],'id_client');
			$this->lenders_accounts->get($p['id_client'],'id_client_owner');
			
			// on met a jour le prelevement
			$this->prelevements->get($p['id_prelevement'],'id_prelevement');
			$this->prelevements->status = 1; // envoyé
			$this->prelevements->added_xml = date('Y-m-d H:i').':00';
			$this->prelevements->update();
			
			
			// variables
			$id_lot = $titulaire.'/'.$dateColle.'/'.$p['id_prelevement'];
			$montant = round($p['montant']/100,2);
			
			// Date execution
			
			// nb jour avant pour de prelevement
			$datePlusNbjour = mktime(date("H"), date("i"),0, date("m")  , date("d")-$nbJoursAvant, date("Y"));
			
			// si preteur
			if($p['type'] == 1)
			{
				// date d'execution du prelevement
				$date_execution = mktime(date("H"), date("i"),0, date("m")  , $p['jour_prelevement'], date("Y"));
				
				// si la date demandé est inferieur au nombre de jour min on rajoute 1 mois
				if($datePlusNbjour < $date_execution)
				$date_execution = mktime(date("H"), date("i"),0, date("m")+1  , $p['jour_prelevement'], date("Y"));
			}
			
			$clients_mandats->get($p['id_client'],'id_project = 0 AND id_client');
			
			$refmandat = $p['motif'];
			$date_mandat = date('Y-m-d',strtotime($clients_mandats->updated));
			
			//si jamais eu de prelevement avant
			$val = 'FRST'; // prelevement ponctuel
			
			$table['id_lot'] = $id_lot ;
			$table['montant'] = $montant;
			$table['val'] = $val;
			$table['date_execution'] = date('Y-m-d',$date_execution);
			$table['iban'] = $iban;
			$table['bic'] = $bic;
			$table['ics'] = $ics;
			$table['refmandat'] = $refmandat;
			$table['date_mandat'] = $date_mandat;
			$table['bicPreteur'] = $p['bic']; // bic
			$table['ibanPreteur'] = $p['iban'];
			$table['nomPreteur'] = $this->clients->nom;
			$table['prenomPreteur'] = $this->clients->prenom;
			$table['motif'] = $p['motif'];
			$table['id_prelevement'] = $p['id_prelevement'];

			$xml .= $this->xmPrelevement($table);
			
		}
		
		///////////////////////////////////////
		/// $lPrelevementsEnCoursPeteurRecu ///
		foreach($lPrelevementsEnCoursPeteurRecu as $p)
		{
			
			$this->clients->get($p['id_client'],'id_client');
			$this->lenders_accounts->get($p['id_client'],'id_client_owner');
			
			// variables
			$id_lot = $titulaire.'/'.$dateColle.'/'.$p['id_prelevement'];
			$montant = round($p['montant']/100,2);
			
			// Date execution
			
			// nb jour avant pour de prelevement
			$datePlusNbjour = mktime(date("H"), date("i"),0, date("m")  , date("d")-$nbJoursAvant, date("Y"));
			
			// si preteur
			if($p['type'] == 1)
			{
				// date d'execution du prelevement
				$date_execution = mktime(date("H"), date("i"),0, date("m")  , $p['jour_prelevement'], date("Y"));
				
				// si la date demandé est inferieur au nombre de jour min on rajoute 1 mois
				if($datePlusNbjour < $date_execution)
				{
				
				$date_execution = mktime(date("H"), date("i"),0, date("m")+1  , $p['jour_prelevement'], date("Y"));
				
				}
				
			}
			
			//echo date('d/m/Y',$date_execution).'<bR>';
			// On recup le mandat
			$clients_mandats->get($p['id_client'],'id_project = 0 AND id_client');
			
			$refmandat = $p['motif'];
			$date_mandat = date('Y-m-d',strtotime($clients_mandats->updated));
			
			
			//si jamais eu de prelevement avant
			if($p['status'] == 0)
			{
				$val = 'FRST'; // prelevement ponctuel
			}
			else
			{
				$val = 'RCUR';
				
				// date du xml généré au premier prelevement
				$date_xml = strtotime($p['added_xml']);
				
				// date xml + 1 mois
				$dateXmlPlusUnMois = mktime(date("H",$date_xml), date("i",$date_xml),0, date("m",$date_xml)+1  , date("d",$date_xml), date("Y",$date_xml));
				
				$dateXmlPlusUnMois = date('Y-m-d',$dateXmlPlusUnMois);
				
				////////// test ////////////
				//$dateXmlPlusUnMois = date('Y-m-d');
				///////////////////////////
			}
			
			// si status est a 0 (en cours) ou si le satut est supperieur et que la date du jour est égale a la date xml + 1 mois
			
			// 2 cas possible = 1 : premier prelevement | 2 : prelevement recurrent
			if($p['status'] == 0 || $p['status'] > 0 && $dateXmlPlusUnMois == $today)
			{
				$table['id_lot'] = $id_lot ;
				$table['montant'] = $montant;
				$table['val'] = $val;
				$table['date_execution'] = date('Y-m-d',$date_execution);
				$table['iban'] = $iban;
				$table['bic'] = $bic;
				$table['ics'] = $ics;
				$table['refmandat'] = $refmandat;
				$table['date_mandat'] = $date_mandat;
				$table['bicPreteur'] = $p['bic']; // bic
				$table['ibanPreteur'] = $p['iban'];
				$table['nomPreteur'] = $this->clients->nom;
				$table['prenomPreteur'] = $this->clients->prenom;
				$table['motif'] = $p['motif'];
				$table['id_prelevement'] = $p['id_prelevement'];
				
				
				
				$xml .= $this->xmPrelevement($table);
				
				// on met a jour le prelevement
				$this->prelevements->get($p['id_prelevement'],'id_prelevement');
				$this->prelevements->status = 1; // envoyé
				$this->prelevements->added_xml = date('Y-m-d H:i').':00';
				$this->prelevements->update();
			}
				
		}
		
		///////////////////////////////////////
		/// $lPrelevementsEnCoursEmprunteur ///
		
		$old_iban = '';
		$old_bic = '';
		foreach($lPrelevementsEnCoursEmprunteur as $p)
		{
			// on recup le dernier prelevement effectué pour voir si c'est le meme iban ou bic
			$first = false;
			if($p['num_prelevement'] > 1)
			{
				$lastRembEmpr = $this->prelevements->select('type = 2 AND type_prelevement = 1 AND status = 1 AND id_project = '.$p['id_project'],'num_prelevement DESC',0,1);
				$last_iban = $lastRembEmpr[0]['iban'];
				$last_bic = $lastRembEmpr[0]['bic'];
				
				if($last_iban != $p['iban'] || $last_bic != $p['bic'])
				{
					$first = true;
				}
			}
			
			// variables
			$id_lot = $titulaire.'/'.$dateColle.'/'.$p['id_prelevement'];
			$montant = round($p['montant']/100,2);
			
			// On recup le mandat
			$clients_mandats->get($p['id_project'],'id_project');
			
			$refmandat = $p['motif'];
			$date_mandat = date('Y-m-d',strtotime($clients_mandats->updated));
			
			// si premier remb
			if($p['num_prelevement'] == 1 || $first == true)
			//if($p['num_prelevement'] == 1)
			{
				$val = 'FRST';
			}
			else
			{
				$val = 'RCUR';
			}
			$old_iban = $p['iban'];
			$old_bic = $p['bic'];
			
			///////////////////////////////////////////////////////////
			// Temporaire pour régulariser le future prelevement du projet 374 qui passera le 2014-08-13
			//if($p['id_project'] == '374' && date('n') < 9){
				//$val = 'FRST';
			//}
			///////////////////////////////////////////////////////////

			$this->clients->get($p['id_client'],'id_client');
			
			$table['id_lot'] = $id_lot ;
			$table['montant'] = $montant;
			$table['val'] = $val;
			$table['date_execution'] = $p['date_echeance_emprunteur'];
			$table['iban'] = $iban;
			$table['bic'] = $bic;
			$table['ics'] = $ics;
			$table['refmandat'] = $refmandat;
			$table['date_mandat'] = $date_mandat;
			$table['bicPreteur'] = $p['bic']; // bic
			$table['ibanPreteur'] = $p['iban'];
			$table['nomPreteur'] = $this->clients->nom;
			$table['prenomPreteur'] = $this->clients->prenom;
			$table['motif'] = $refmandat;
			$table['id_prelevement'] = $p['id_prelevement'];
			
			$xml .= $this->xmPrelevement($table);
			
			// on met a jour le prelevement
			$this->prelevements->get($p['id_prelevement'],'id_prelevement');
			$this->prelevements->status = 1; // envoyé
			$this->prelevements->added_xml = date('Y-m-d H:i').':00';
			$this->prelevements->update();
				
		}
		
		
		$xml .= '
		</CstmrDrctDbtInitn>
	</Document>';
		echo $xml;
		$filename = 'Unilend_Prelevements_'.date('Ymd');
		
		if($nbPrelevements > 0)
		{
			if($this->Config['env'] == 'prod')
			{
				$connection = ssh2_connect('ssh.reagi.com', 22);
				ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
				$sftp = ssh2_sftp($connection);
				$sftpStream = @fopen('ssh2.sftp://'.$sftp.'/home/sfpmei/emissions/prelevements/'.$filename.'.xml', 'w');
				fwrite($sftpStream, $xml);
				fclose($sftpStream);
			}
			
			file_put_contents ($this->path.'protected/sftp/prelevements/'.$filename.'.xml',$xml);
			//mail('d.courtier@equinoa.com','unilend '.$this->Config['env'].' cron','prelevements date : '.date('d/m/y H:i:s'));
		}
	}
	
	// xml prelevement
	function xmPrelevement($table)
	{
		$id_lot = $table['id_lot'];
		$montant = $table['montant'];
		$val = $table['val'];
		$date_execution = date('Y-m-d',strtotime($table['date_execution']));;
		$iban = $table['iban'];
		$bic = $table['bic'];
		$ics = $table['ics'];
		$refmandat = $table['refmandat'];
		$date_mandat = $table['date_mandat'];
		$bicPreteur = $table['bicPreteur'];
		$ibanPreteur = $table['ibanPreteur'];
		$nomPreteur = $table['nomPreteur'];
		$prenomPreteur = $table['prenomPreteur'];
		$motif = $table['motif'];
		$id_prelevement = $table['id_prelevement'];
		
		
		return $xml .= '
			<PmtInf>
				<PmtInfId>'.$id_lot.'</PmtInfId>
				<PmtMtd>DD</PmtMtd>
				<NbOfTxs>1</NbOfTxs>
				<CtrlSum>'.$montant.'</CtrlSum>
				<PmtTpInf>
					<SvcLvl>
						<Cd>SEPA</Cd>
					</SvcLvl>
					<LclInstrm>
						<Cd>CORE</Cd>
					</LclInstrm>
					<SeqTp>'.$val.'</SeqTp>
				</PmtTpInf>
				<ReqdColltnDt>'.$date_execution.'</ReqdColltnDt>
				<Cdtr>
					<Nm>SFPMEI</Nm>
					<PstlAdr>
						<Ctry>FR</Ctry>
					</PstlAdr>
				</Cdtr>
				<CdtrAcct>
					<Id>
						<IBAN>'.$iban.'</IBAN>
					</Id>
					<Ccy>EUR</Ccy>
				</CdtrAcct>
				<CdtrAgt>
					<FinInstnId>
						<BIC>'.$bic.'</BIC>
					</FinInstnId>
				</CdtrAgt>
				<ChrgBr>SLEV</ChrgBr>
				<CdtrSchmeId>
					<Id>
						<PrvtId>
							<Othr>
								<Id>'.$ics.'</Id>
								<SchmeNm>
									<Prtry>SEPA</Prtry>
							   </SchmeNm>
						   </Othr>
					   </PrvtId>
					</Id>
				</CdtrSchmeId>
				<DrctDbtTxInf>
					<PmtId>
						<EndToEndId>'.$id_lot.'</EndToEndId>
					</PmtId>
					<InstdAmt Ccy="EUR">'.$montant.'</InstdAmt>
					<DrctDbtTx>
						<MndtRltdInf>
							<MndtId>'.$refmandat.'</MndtId>
							<DtOfSgntr>'.$date_mandat.'</DtOfSgntr>
							<AmdmntInd>false</AmdmntInd>
						</MndtRltdInf>
					</DrctDbtTx>
					<DbtrAgt>
						<FinInstnId>
							<BIC>'.$bicPreteur.'</BIC>
						</FinInstnId>
					 </DbtrAgt>
					 <Dbtr>
						 <Nm>'.$nomPreteur.' '.$prenomPreteur.'</Nm>
						 <PstlAdr>
							 <Ctry>FR</Ctry>
						 </PstlAdr>
					 </Dbtr>
					 <DbtrAcct>
						 <Id>
							 <IBAN>'.$ibanPreteur.'</IBAN>
						 </Id>
					 </DbtrAcct>
					 <RmtInf>
						<Ustrd>'.$motif.'</Ustrd>
					 </RmtInf>
				</DrctDbtTxInf>
			</PmtInf>';	
	}
	
	
	// cron toutes les heures
	// On relance le mail stand by plusieur fois H+12, H+24, J+3, J+7 (stand by plus present dans la derniere version depot de dossier)
	function _relance_stand_by()
	{
		
		die;
		// chargement des datas
		$this->clients = $this->loadData('clients');
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		
		
		//$lprojects = $this->projects->select('stand_by = 1');
		$lEmprunteurs = $this->clients->select('email IS NOT NULL AND status_depot_dossier > 0 AND status_pre_emp > 1 AND status = 0');

		
		$time = date('Y-m-d H');
		
		// test //
		//$time = '2013-11-12 21';
		//////////
		
		foreach($lEmprunteurs as $p)
		{
			//$this->companies->get($p['id_company'],'id_company');
			$this->clients->get($p['id_client'],'id_client');
			
			if($this->clients->status == 0)
			{
				
				// ladate
				$ladate = strtotime($p['added']);
				
				// ladate +12h
				$ladatePlus12H = mktime(date("H",$ladate)+12, date("i",$ladate),0, date("m",$ladate)  , date("d",$ladate), date("Y",$ladate));
				
				// ladate +24h
				$ladatePlus24H = mktime(date("H",$ladate), date("i",$ladate),0, date("m",$ladate)  , date("d",$ladate)+1, date("Y",$ladate));
				
				// ladate +3j
				$ladatePlus3J = mktime(date("H",$ladate), date("i",$ladate),0, date("m",$ladate)  , date("d",$ladate)+3, date("Y",$ladate));
				
				// ladate +7j
				$ladatePlus7J = mktime(date("H",$ladate), date("i",$ladate),0, date("m",$ladate)  , date("d",$ladate)+7, date("Y",$ladate));
				
				$ladatePlus12H = date('Y-m-d H',$ladatePlus12H);
				$ladatePlus24H = date('Y-m-d H',$ladatePlus24H);
				$ladatePlus3J = date('Y-m-d H',$ladatePlus3J);
				$ladatePlus7J = date('Y-m-d H',$ladatePlus7J);
				
				echo 'emprunteur : '.$this->clients->id_client.' - Nom : '.$this->clients->prenom.' '.$this->clients->nom.'<br>';
				echo $p['added'].'<br>';
				echo '+12h : '.$ladatePlus12H.'<br>';
				echo '+24h : '.$ladatePlus24H.'<br>';
				echo '+3j : '.$ladatePlus3J.'<br>';
				echo '+7j : '.$ladatePlus7J.'<br>';
				echo '---------------<br>';
				
				if($ladatePlus12H == $time || $ladatePlus24H == $time || $ladatePlus3J == $time || $ladatePlus7J == $time)
				{
					
					//******************************//
					//*** ENVOI DU MAIL STAND-BY ***//
					//******************************//
					
					// Recuperation du modele de mail
					$this->mails_text->get('emprunteur-stand-by-depot-de-dossier','lang = "'.$this->language.'" AND type');
					
					// Variables du mailing
					$surl = $this->surl;
					$url = $this->lurl;
					$email = $this->clients->email;
					$link_login = $this->lurl.'/depot_de_dossier/stand_by/'.$this->clients->hash;
					$prenom = $this->clients->prenom;
					$date = date('d/m/Y',strtotime($this->projects->added));
					
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
					'prenom_e' => $prenom,
					'date' => $date,
					'link_compte_emprunteur' => $link_login,
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
				}
			}
		}

		die;
	}
	
	// cron toutes les heures
	// lors des virements si on a toujours pas recu on relance le client
	function _relance_payment_preteur()
	{
		// relance retiré apres demande
		die;
		
		// chargement des datas
		$this->clients = $this->loadData('clients');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		
		$lLenderNok = $this->lenders_accounts->select('status = 0');
		
		// date du jour
		$time = date('Y-m-d H');
		
		// test //
		//$time = '2013-10-11 00';
		//////////

		
		foreach($lLenderNok as $l)
		{
			$this->clients->get($l['id_client_owner'],'id_client');
			
			// ladate
			$ladate = strtotime($l['added']);
			
			// ladate +12h
			$ladatePlus12H = mktime(date("H",$ladate)+12, date("i",$ladate),0, date("m",$ladate)  , date("d",$ladate), date("Y",$ladate));
			
			// ladate +24h
			$ladatePlus24H = mktime(date("H",$ladate), date("i",$ladate),0, date("m",$ladate)  , date("d",$ladate)+1, date("Y",$ladate));
			
			// ladate +3j
			$ladatePlus3J = mktime(date("H",$ladate), date("i",$ladate),0, date("m",$ladate)  , date("d",$ladate)+3, date("Y",$ladate));
			
			// ladate +7j
			$ladatePlus7J = mktime(date("H",$ladate), date("i",$ladate),0, date("m",$ladate)  , date("d",$ladate)+7, date("Y",$ladate));
			
			$ladatePlus12H = date('Y-m-d H',$ladatePlus12H);
			$ladatePlus24H = date('Y-m-d H',$ladatePlus24H);
			$ladatePlus3J = date('Y-m-d H',$ladatePlus3J);
			$ladatePlus7J = date('Y-m-d H',$ladatePlus7J);
			
			echo 'Preteur : '.$this->clients->id_client.' - Nom : '.$this->clients->prenom.' '.$this->clients->nom.'<br>';
			echo $l['added'].'<br>';
			echo '+12h : '.$ladatePlus12H.'<br>';
			echo '+24h : '.$ladatePlus24H.'<br>';
			echo '+3j : '.$ladatePlus3J.'<br>';
			echo '+7j : '.$ladatePlus7J.'<br>';
			echo '---------------<br>';
			
			if($ladatePlus12H == $time || $ladatePlus24H == $time || $ladatePlus3J == $time || $ladatePlus7J == $time)
			{
				// Motif virement
				$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
				$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
				$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				
				//*********************************************************//
				//*** ENVOI DU MAIL RELANCE PAYMENT INSCRIPTION PRETEUR ***//
				//*********************************************************//
				
				// Recuperation du modele de mail
				$this->mails_text->get('preteur-relance-paiement-inscription','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				$email = $this->clients->email;
				
				// FB
				$this->settings->get('Facebook','type');
				$lien_fb = $this->settings->value;
				
				// Twitter
				$this->settings->get('Twitter','type');
				$lien_tw = $this->settings->value;
				
				//'compte-p' => $this->lurl.'/alimentation',
				//'compte-p-virement' => $this->lurl.'/alimentation',
	
				// Variables du mailing
				$varMail = array(
				'surl' => $surl,
				'url' => $url,
				'prenom_p' => $this->clients->prenom,
				'date_p' => date('d/m/Y',strtotime($this->clients->added)),
				'compte-p' => $this->lurl.'/inscription_preteur/etape3/'.$this->clients->hash.'/2',
				'compte-p-virement' => $this->lurl.'/inscription_preteur/etape3/'.$this->clients->hash,
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
			}
			
		}
		
	}
	
	// (cron passe toujours dessus chez oxeva  0 * * * * )
	function _check_prelevement_remb()
	{
		// plus utilisé
		die;
		
		// Chargement des librairies
		$jo = $this->loadLib('jours_ouvres');
		
		// chargement des datas
		$this->projects = $this->loadData('projects');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->prelevements = $this->loadData('prelevements');
		$this->companies = $this->loadData('companies');
		$this->transactions = $this->loadData('transactions');
		
		// today
		$today = date('Y-m-d');
		// test //
		//$today = '2013-11-15';
		//////////
		
		// les projets en statut remboursement
		$this->lProjects = $this->projects->selectProjectsByStatus(80);
		
		foreach($this->lProjects as $k => $p)
		{
			// on recup la companie
			$this->companies->get($p['id_company'],'id_company');
			
			// les echeances non remboursé du projet
			$lEcheances = $this->echeanciers->getSumRembEmpruntByMonths($p['id_project'],'','0');
			/*echo '<pre>';
			print_r($lEcheances);
			echo '</pre>';*/
			
			foreach($lEcheances as $e)
			{
				$date = strtotime($e['date_echeance_emprunteur'].':00');
				// retourne la date - 5 jours ouvrés
				$result = $jo->getDateOuvre($date,5,1);
				echo 'echeance : '.$e['ordre'].' -> '.date('Y-m-d',strtotime($result)).'<br>';
				
				// premier remb
				if($e['ordre'] == 1)
				{
					//retourne la date - 5 jours ouvrés
					$result = $jo->getDateOuvre(strtotime($e['date_echeance_emprunteur'].':00'),5,1);
				}
				else
				{
					//retourne la date - 2 jours ouvrés
					$result = $jo->getDateOuvre(strtotime($e['date_echeance_emprunteur'].':00'),2,1);
				}
				
				$result = date('Y-m-d',strtotime($result));
				
				if($result == $today)
				{
					$lemontant = ($e['montant']+ $e['commission']+ $e['tva']);
					// On enregistre la transaction
					$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
					$this->transactions->montant = $lemontant*100;
					$this->transactions->id_langue = 'fr';
					$this->transactions->date_transaction = date('Y-m-d H:i:s');
					$this->transactions->status = '0'; // statut payement no ok
					$this->transactions->etat = '0'; // etat en attente
					$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
					$this->transactions->type_transaction = 6; // remb emprunteur 
					$this->transactions->transaction = 1; // transaction virtuelle
					$this->transactions->id_transaction = $this->transactions->create();
					
					$this->prelevements->id_client = $this->companies->id_client_owner;
					$this->prelevements->id_transaction = $this->transactions->id_transaction;
					$this->prelevements->id_project = $p['id_project'];
					$this->prelevements->motif = 'Remboursement projet '.$p['id_project'];
					$this->prelevements->montant = $lemontant*100;
					$this->prelevements->bic = $this->companies->bic;
					$this->prelevements->iban = $this->companies->iban;
					if($e['ordre'] == 1)$this->prelevements->type_prelevement = 2; // ponctuel
					else $this->prelevements->type_prelevement = 1; // recurrent
					$this->prelevements->type = 2; // emprunteur
					$this->prelevements->status = 0; // en cours
					$this->prelevements->create();
					/*echo '<br>';
					echo 'yeah';
					echo '<br>';*/
				}
			}
			
			
		}
		
		//$date = strtotime('2013-11-8 00:00:00');
		// retourne la date - 5 jours ouvrés
		//$result = $jo->getDateOuvre($date,5,1);
		//echo date('d-m-Y',strtotime($result));
		//var_dump($result);
		
		//mail('d.courtier@equinoa.com','unilend '.$this->Config['env'].' cron','check_prelevement_remb  date : '.date('d/m/y H:i:s'));
		
		
	}
	
	
	// transforme le fichier txt format truc en tableau
	function recus2array($file)
	{
		
		$tablemontant = array(
		'{' => 0,
		'A' => 1,
		'B' => 2,
		'C' => 3,
		'D' => 4,
		'E' => 5,
		'F' => 6,
		'G' => 7,
		'H' => 8,
		'I' => 9,
		'}' => 0,
		'J' => 1,
		'K' => 2,
		'L' => 3,
		'M' => 4,
		'N' => 5,
		'O' => 6,
		'P' => 7,
		'Q' => 8,
		'R' => 9);
		
		
		
		$url = $file;
		
		$array = array();
		$handle = @fopen($url,"r"); //lecture du fichier
		if($handle)
		{
			
			$i = 0;
			while(($ligne = fgets($handle)) !== false)
			{
				if(strpos($ligne,'CANTONNEMENT') == true || strpos($ligne,'DECANTON') == true || strpos($ligne,'REGULARISATION DIGITAL') == true || strpos($ligne,'00374 REGULARISATION DIGITAL') == true || strpos($ligne,'REGULARISATION') == true || strpos($ligne,'régularisation') == true || strpos($ligne,'00374 régularisation') == true || strpos($ligne,'REGULARISAT') == true)
				{
					$codeEnregi = substr($ligne,0,2);
					if($codeEnregi == 04)$i++;
					//echo $i.' '.$ligne.'<br>';
					$tabRestriction[$i] = $i;
				}
				else
				{
				
					$codeEnregi = substr($ligne,0,2);
					
					if($codeEnregi == 04)
					{
						$i++;
						$laligne = 1;
						
						$array[$i]['codeEnregi'] = substr($ligne,0,2);
						$array[$i]['codeBanque'] = substr($ligne,2,5);
						$array[$i]['codeOpBNPP'] = substr($ligne,7,4);
						$array[$i]['codeGuichet'] = substr($ligne,11,5);
						$array[$i]['codeDevises'] = substr($ligne,16,3);
						$array[$i]['nbDecimales'] = substr($ligne,19,1);
						$array[$i]['zoneReserv1'] = substr($ligne,20,1);
						$array[$i]['numCompte'] = substr($ligne,21,11);
						$array[$i]['codeOpInterbancaire'] = substr($ligne,32,2);
						$array[$i]['dateEcriture'] = substr($ligne,34,6);
						$array[$i]['codeMotifRejet'] = substr($ligne,40,2);
						$array[$i]['dateValeur'] = substr($ligne,42,6);
						//$array[$i]['libelleOpe1'] = substr($ligne,48,31);
						$array[$i]['zoneReserv2'] = substr($ligne,79,2);
						$array[$i]['numEcriture'] = substr($ligne,81,7);
						$array[$i]['codeExoneration'] = substr($ligne,88,1);
						$array[$i]['zoneReserv3'] = substr($ligne,89,1);
						
						$array[$i]['refOp'] = substr($ligne,104,16);
						$array[$i]['ligne1'] = $ligne;
						
						// On affiche la ligne seulement si c'est un virement
						if(!in_array(substr($ligne,32,2),array(23,25,'A1','B1')))
						{
							$array[$i]['libelleOpe1'] = substr($ligne,48,31);	
						}
						
						
						// on recup le champ montant
						$montant = substr($ligne,90,14);
						// on retire les zeros du debut et le dernier caractere
						$Debutmontant = ltrim(substr($montant,0,13),'0');
						// On recup le dernier caractere
						$dernier = substr($montant,-1,1);
						$array[$i]['montant'] = $Debutmontant.$tablemontant[$dernier];
					}
					
					if($codeEnregi == 05)
					{
						// si prelevement
						if(in_array(substr($ligne,32,2),array(23,25,'A1','B1')))
						{
							// On veut recuperer ques ces 2 lignes
							if(in_array(trim(substr($ligne,45,3)),array('LCC','LC2')))
							{
								
								$laligne += 1;
								//$array[$i]['ligne'.$laligne] = $ligne;
								$array[$i]['libelleOpe'.$laligne] = trim(substr($ligne,45));
							}
						}
						// virement
						else
						{
							$laligne += 1;
							//$array[$i]['ligne'.$laligne] = $ligne;
							$array[$i]['libelleOpe'.$laligne] = trim(substr($ligne,45));	
						}
					}
				
				}
				
					
				
			}
			if (!feof($handle)) {
				return "Erreur: fgets() a échoué\n";
			}
			fclose($handle);
			
			// on retire les indésirables
			if($tabRestriction != false){
				foreach($tabRestriction as $r){ unset($array[$r]); }
			}
			
			return $array;
		}
	}
	
	function _letest()
	{
		// connexion
		$connection = ssh2_connect('ssh.reagi.com', 22);
		ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
		$sftp = ssh2_sftp($connection);
		
		// Lien
		$lien = 'ssh2.sftp://'.$sftp.'/home/sfpmei/receptions/UNILEND-00040631007-'.date('Ymd').'.txt';
		
		// enregistrement chez nous
		$file = file_get_contents($lien);
		if($file === false)
		{
			echo 'pas de fichier';	
		}
		else
		{
			file_put_contents($this->path.'protected/sftp/reception/UNILEND-00040631007-'.date('Ymd').'.txt',$file);

			// lecture du fichier
			$lrecus = $this->recus2array($lien);
			
			/*echo '<pre>';
			print_r($lrecus);
			echo '</pre>';*/
		}
		
	}
	
	// reception virements/prelevements (toutes les 30 min)
	function _reception()
	{
		// chargement des datas
		$receptions = $this->loadData('receptions');
		
		$clients = $this->loadData('clients');
		$lenders = $this->loadData('lenders_accounts');
		$transactions = $this->loadData('transactions');
		$wallets = $this->loadData('wallets_lines');
		$bank = $this->loadData('bank_lines');
		
		$projects = $this->loadData('projects');
		$companies = $this->loadData('companies');
		$prelevements = $this->loadData('prelevements');
		$echeanciers = $this->loadData('echeanciers');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$bank_unilend = $this->loadData('bank_unilend');
		
		$projects_remb = $this->loadData('projects_remb');
		
		// Statuts virements
		$statusVirementRecu = array(05,18,45,13);
		$statusVirementEmis = array(06,21);
		$statusVirementRejet = array(12);
		
		//Statuts prelevements
		$statusPrelevementEmi = array(23,25,'A1','B1');
		$statusPrelevementRejete = array(10,27,'A3','B3');
		
		if($this->Config['env'] == 'prod'){
			// connexion
			$connection = ssh2_connect('ssh.reagi.com', 22);
			ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
			$sftp = ssh2_sftp($connection);
		}

		// Lien
		$lien = 'ssh2.sftp://'.$sftp.'/home/sfpmei/receptions/UNILEND-00040631007-'.date('Ymd').'.txt';
		
		// test //
		//$lien = $this->path.'protected/sftp/reception/test.txt';
		//$lien = $this->path.'protected/sftp/reception_test/test'.date('Ymd').'.txt';
		// test //
		
		// enregistrement chez nous
		$file = @file_get_contents($lien);
		if($file === false){
			//die; // pour le test
			//echo 'pas de fichier';	
			
			$ladate = time();
			
			// le cron passe a 15 et 45, nous on va check a 15
			$NotifHeure = mktime( 10, 0, 0, date('m'), date('d'),date('Y'));
			
			$NotifHeurefin = mktime( 10, 20, 0, date('m'), date('d'),date('Y'));
			
			// Si a 10h on a pas encore de fichier bah on lance un mail notif
			if($ladate >= $NotifHeure && $ladate <= $NotifHeurefin){
				
				//************************************//
				//*** ENVOI DU MAIL ETAT QUOTIDIEN ***//
				//************************************//
				
				// destinataire
				$this->settings->get('Adresse notification aucun virement','type');
				//$destinataire = $this->settings->value;
				$destinataire = 'd.courtier@equinoa.com';
		
				// Recuperation du modele de mail
				$this->mails_text->get('notification-aucun-virement','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				
				// Attribution des données aux variables
				$sujetMail = $this->mails_text->subject;
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
			
				$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
				$this->email->setHTMLBody($texteMail);
				Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
				// fin mail
			}
			
		}
		else{
			// lecture du fichier
			$lrecus = $this->recus2array($lien);
			
			/*echo '<pre>';
			print_r($lrecus);
			echo '</pre>';*/
			
			// on regarde si on a deja des truc d'aujourd'hui
			$recep = $receptions->select('LEFT(added,10) = "'.date('Y-m-d').'"'); // <------------------------------------------------------------ a remettre
			
			// si on a un fichier et qu'il n'est pas deja present en bdd
			// on enregistre qu'une fois par jour
			if($lrecus != false && $recep == false)
			{
				file_put_contents($this->path.'protected/sftp/reception/UNILEND-00040631007-'.date('Ymd').'.txt',$file); // <------------------ a remettre

				$type = 0;
				$status_virement = 0;
				$status_prelevement = 0;
				
				foreach($lrecus as $r){	
					$code = $r['codeOpInterbancaire'];
					
					// Status virement/prelevement
					if(in_array($code,$statusVirementRecu)){
						$type = 2; // virement
						$status_virement = 1; // recu
						$status_prelevement = 0;
					}
					elseif(in_array($code,$statusVirementEmis)){
						$type = 2; // virement
						$status_virement = 2; // emis
						$status_prelevement = 0;
					}
					elseif(in_array($code,$statusVirementRejet)){
						$type = 2; // virement
						$status_virement = 3; // rejet
						$status_prelevement = 0;
					}
					elseif(in_array($code,$statusPrelevementEmi)){
						$type = 1; // prelevement
						$status_virement = 0;
						$status_prelevement = 2; // emis
					}
					elseif(in_array($code,$statusPrelevementRejete)){
						$type = 1; // prelevement
						$status_virement = 0;
						$status_prelevement = 3; // rejete/impaye
					}
					// Si pas dans les criteres
					else{
						$type = 4; // recap payline
						$status_virement = 0;
						$status_prelevement = 0; 
					}
					
					$motif = '';
					for($i=1;$i<=5;$i++){
						if($r['libelleOpe'.$i] != false)
						$motif .= $r['libelleOpe'.$i].'<br>';
					}
					
					// Si on a un virement unilend offre de bienvenue
					//if($r['unilend_bienvenue'] == true){
					if(5 == 6){
						
						// transact
						$transactions->id_prelevement = $receptions->id_reception;
						$transactions->id_client = 0;
						$transactions->montant = $receptions->montant;
						$transactions->id_langue = 'fr';
						$transactions->date_transaction = date('Y-m-d H:i:s');
						$transactions->status = 1;
						$transactions->etat = 1;
						$transactions->transaction = 1;
						$transactions->type_transaction = 18; // Unilend virement offre de bienvenue
						$transactions->ip_client = $_SERVER['REMOTE_ADDR'];
						//$transactions->id_transaction = $transactions->create();
						
						// bank unilend
						$bank_unilend->id_transaction = $transactions->id_transaction;
						$bank_unilend->montant = $receptions->montant;
						$bank_unilend->type = 4; // Unilend offre de bienvenue
						//$bank_unilend->create();
						
					}
					// Sinon comme d'hab
					else{
						$receptions->id_client = 0;
						$receptions->id_project = 0;
						$receptions->status_bo = 0;
						$receptions->remb = 0;
						$receptions->motif = $motif;
						$receptions->montant = $r['montant'];
						$receptions->type = $type;
						$receptions->status_virement = $status_virement;
						$receptions->status_prelevement = $status_prelevement;
						$receptions->ligne = $r['ligne1'];
						$receptions->id_reception = $receptions->create();
					
					
						/////////////////////////////// ATTRIBUTION AUTO PRELEVEMENT (VIREMENTS EMPRUNTEUR) /////////////////////////////
						if($type == 1 && $status_prelevement == 2){
							
							// On cherche une suite de chiffres
							preg_match_all('#[0-9]+#',$motif,$extract);
							$nombre = (int)$extract[0][0]; // on retourne un int pour retirer les zeros devant
							
							$listPrel = $prelevements->select('id_project = '.$nombre.' AND status = 0');
							
							// on regarde si on a une corespondance
							if(count($listPrel) > 0){
								
								// on compare les 2 motif
								$mystring = trim($motif);
								$findme   = $listPrel[0]['motif'];
								$pos = strpos($mystring, $findme);
								
								// on laisse en manuel
								if ($pos === false) {
									//echo 'Recu';
								}
								// Automatique (on attribue le prelevement au preteur)
								else{
									//echo 'Auto';
									if($transactions->get($receptions->id_reception,'status = 1 AND etat = 1 AND type_transaction = 6 AND id_prelevement')==false){
									
										$projects->get($nombre,'id_project');
										// On recup l'entreprise
										$companies->get($projects->id_company,'id_company');
										// On recup le client
										$clients->get($companies->id_client_owner,'id_client');
										
										// reception
										$receptions->get($receptions->id_reception,'id_reception');
										$receptions->id_client = $clients->id_client;
										$receptions->id_project = $projects->id_project;
										$receptions->status_bo = 2;
										$receptions->remb = 1;
										$receptions->update();
										
										// transact
										$transactions->id_prelevement = $receptions->id_reception;
										$transactions->id_client = $clients->id_client;
										$transactions->montant = $receptions->montant;
										$transactions->id_langue = 'fr';
										$transactions->date_transaction = date('Y-m-d H:i:s');
										$transactions->status = 1;
										$transactions->etat = 1;
										$transactions->transaction = 1;
										$transactions->type_transaction = 6; // remb emprunteur
										$transactions->ip_client = $_SERVER['REMOTE_ADDR'];
										$transactions->id_transaction = $transactions->create();
										
										// bank unilend
										$bank_unilend->id_transaction = $transactions->id_transaction;
										$bank_unilend->id_project = $projects->id_project;
										$bank_unilend->montant = $receptions->montant;
										$bank_unilend->type = 1;
										$bank_unilend->create();
										
										// on parcourt les echeances
										$eche = $echeanciers_emprunteur->select('status_emprunteur = 0 AND id_project = '.$projects->id_project,'ordre ASC');
										$sumRemb = ($receptions->montant/100);
										
										$newsum = $sumRemb;
										foreach($eche as $e)
										{
											$ordre = $e['ordre'];
											
											// on récup le montant que l'emprunteur doit rembourser
											$montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant']/100,$e['commission']/100,$e['tva']/100);
											// On verifie si le montant a remb est inferieur ou égale a la somme récupéré
											if($montantDuMois <= $newsum)
											{
												// On met a jour les echeances du mois
												$echeanciers->updateStatusEmprunteur($projects->id_project,$ordre);
												
												$echeanciers_emprunteur->get($projects->id_project,'ordre = '.$ordre.' AND id_project');
												$echeanciers_emprunteur->status_emprunteur = 1;
												$echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
												$echeanciers_emprunteur->update();
												
												// et on retire du wallet unilend 
												$newsum = $newsum - $montantDuMois;
												
												if($projects_remb->counter('id_project = "'.$projects->id_project.'" AND ordre = "'.$ordre.'" AND status IN(0,1)') <= 0){
													
													$date_echeance_preteur = $echeanciers->select('id_project = "'.$projects->id_project.'" AND ordre = "'.$ordre.'"','',0,1);
													
													// file d'attente pour les remb auto preteurs
													$projects_remb->id_project = $projects->id_project;
													$projects_remb->ordre = $ordre;
													$projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
													$projects_remb->date_remb_preteurs = $date_echeance_preteur[0]['date_echeance'];
													$projects_remb->date_remb_preteurs_reel = '0000-00-00 00:00:00';
													$projects_remb->status = 0; // nom remb aux preteurs
													$projects_remb->create();
												}
												
											}
											else break;
										}// fin boucle
										
									} // fin check transaction
								}// fin auto
								
							}// fin check id projet
							
						}
						
						////////////////////////// VIREMENT AUTOMATIQUE PRETEUR //////////////////////////////////////
						// on fait ca que pour les virements recu
						elseif($type == 2 && $status_virement == 1){
							
							// On cherche une suite de chiffres
							preg_match_all('#[0-9]+#',$motif,$extract);
							$nombre = (int)$extract[0][0]; // on retourne un int pour retirer les zeros devant
							
							// si existe en bdd
							if($clients->get($nombre,'id_client')){
								// on créer le motif qu'on devrait avoir
								$p = substr($this->ficelle->stripAccents(utf8_decode(trim($clients->prenom))),0,1);
								$nom = $this->ficelle->stripAccents(utf8_decode(trim($clients->nom)));
								$id_client = str_pad($clients->id_client,6,0,STR_PAD_LEFT);
								$returnMotif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
								
								$mystring = str_replace(' ','',$motif); // retire les espaces au cas ou le motif soit mal ecrit
								$findme   = str_replace(' ','',$returnMotif);
								$pos = strpos($mystring, $findme);
								
								// on laisse en manuel
								if ($pos === false) {
									//echo 'Recu';
								}
								// Automatique (on attribue le virement au preteur)
								else{
									//echo 'Auto';
									
									if($transactions->get($receptions->id_reception,'status = 1 AND etat = 1 AND id_virement') == false){
									
										// reception
										$receptions->get($receptions->id_reception,'id_reception');
										$receptions->id_client = $clients->id_client;
										$receptions->status_bo = 2;
										$receptions->remb = 1;
										$receptions->update();
																		
										// lender
										$lenders->get($clients->id_client,'id_client_owner');
										$lenders->status = 1;
										$lenders->update();
										
										// transact
										$transactions->id_virement = $receptions->id_reception;
										$transactions->id_client = $lenders->id_client_owner;
										$transactions->montant = $receptions->montant;
										$transactions->id_langue = 'fr';
										$transactions->date_transaction = date('Y-m-d H:i:s');
										$transactions->status = 1;
										$transactions->etat = 1;
										$transactions->transaction = 1;
										$transactions->type_transaction = 4; // alimentation virement
										$transactions->ip_client = $_SERVER['REMOTE_ADDR'];
										$transactions->id_transaction = $transactions->create();
										
										// wallet
										$wallets->id_lender = $lenders->id_lender_account;
										$wallets->type_financial_operation = 30; // alimenation
										$wallets->id_transaction = $transactions->id_transaction;
										$wallets->type = 1; // physique
										$wallets->amount = $receptions->montant;
										$wallets->status = 1;
										$wallets->id_wallet_line = $wallets->create();
										
										// bank line
										$bank->id_wallet_line = $wallets->id_wallet_line;
										$bank->id_lender_account = $lenders->id_lender_account;
										$bank->status = 1;
										$bank->amount = $receptions->montant;
										$bank->create();
										
										// on met l'etape inscription a 3
										if($clients->etape_inscription_preteur < 3){
											$clients->etape_inscription_preteur = 3; // etape 3 ok
											$clients->update();
										}
										
									
										// email
										
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
									
										// Solde du compte preteur
										$solde = $transactions->getSolde($receptions->id_client);
										
										// Variables du mailing
										$varMail = array(
										'surl' => $this->surl,
										'url' => $this->furl,
										'prenom_p' => utf8_decode($clients->prenom),
										'fonds_depot' => number_format($receptions->montant/100, 2, ',', ' '),
										'solde_p' => number_format($solde, 2, ',', ' '),
										'motif_virement' => $returnMotif,
										'compte-p' => $this->furl,
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
											Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$clients->email,$tabFiler);
											// Injection du mail NMP dans la queue
											$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
										}
										else // non nmp
										{
											$this->email->addRecipient(trim($clients->email));
											Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
										}
										// fin mail
									} // fin check transaction
										
								}
							}
							else{
								//echo 'no correspondance id client';	
							}
							//echo $returnMotif;
							//echo '<BR>-------------------------<BR>';
						}
						////////////////////////// FIN VIREMENT AUTOMATIQUE PRETEUR //////////////////////////////////////
					}
				}
			
			}
		}
	}
	
	// 1 fois pr jour a  1h du matin
	function _etat_quotidien()
	{
		$jour = date('d');
		
		// si on veut mettre a jour une date on met le jour ici mais attention ca va sauvegarder enbdd et sur l'etat quotidien fait ce matin a 1h du mat
		
		//$jour = 1;
		
		
		// modif manuelle dans etat quotidien de janvier 2014 total solde sfpme 0.69 € retiré
		
		//$num = '02';
		//$ladatedetest = '201409'.$num;
		if($jour == 1)
		{
			// On recup le nombre de jour dans le mois
			$mois = mktime( 0, 0, 0, date('m')-1, 1,date('Y')); 
			
			//$mois = mktime( 0, 0, 0, 9, $num,date('Y')); 
			//$jour = $num;
			
			$nbJours = date("t",$mois);
			
			$leMois = date('m',$mois);
			$lannee = date('Y',$mois);
			$leJour = $nbJours;
			
			// affiche les données avant cette date
			$InfeA = mktime( 0, 0, 0, date('m'), 1,date('Y')); 
			//$InfeA = mktime( 0, 0, 0, 9, $num,date('Y')); 
			
			$lanneeLemois = $lannee.'-'.$leMois;
			
			// affichage de la date du fichier
			$laDate = $jour.'-'.date('m').'-'.date('Y');
			//$laDate = $jour.'-09-'.$lannee;
			
			$lemoisLannee2 = $leMois.'/'.$lannee;
		}
		else
		{
			// On recup le nombre de jour dans le mois
			$mois = mktime( 0, 0, 0, date('m'), 1,date('Y')); 
			$nbJours = date("t",$mois);
			
			$leMois = date('m');
			$lannee = date('Y');
			$leJour = $nbJours;
			
			$InfeA = mktime( 0, 0, 0, date('m'), date('d'),date('Y')); 
			//$InfeA = mktime( 0, 0, 0, 11, 03,2014); 
			
			
			$lanneeLemois = date('Y-m');
			
			$laDate = date('d-m-Y');
			
			$lemoisLannee2 = date('m/Y');
		}
		
		// chargement des datas
		$transac = $this->loadData('transactions');
		$echeanciers = $this->loadData('echeanciers');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$loans = $this->loadData('loans');
		$virements = $this->loadData('virements');
		$prelevements = $this->loadData('prelevements');
		$etat_quotidien = $this->loadData('etat_quotidien');
		$bank_unilend = $this->loadData('bank_unilend');
		
		
		// Les remboursements preteurs
		$lrembPreteurs = $bank_unilend->sumMontantByDayMonths('type = 2 AND status = 1',$leMois,$lannee);
		
		// On recup les echeances le jour où ils ont été remb aux preteurs
		$listEcheances = $bank_unilend->ListEcheancesByDayMonths('type = 2 AND status = 1',$leMois,$lannee);
		
		
		// alimentations CB
		$alimCB = $transac->sumByday(3,$leMois,$lannee);
		
		// 2 : alimentations virements
		$alimVirement = $transac->sumByday(4,$leMois,$lannee);
		
		// 7 : alimentations prelevements
		$alimPrelevement = $transac->sumByday(7,$leMois,$lannee);
		
		// 6 : remb Emprunteur (prelevement)
		$rembEmprunteur = $transac->sumByday(6,$leMois,$lannee);
		
		// 9 : virement emprunteur (octroi prêt : montant | commissions octoi pret : unilend_montant)
		$virementEmprunteur = $transac->sumByday(9,$leMois,$lannee);
		
		// 11 : virement unilend (argent gagné envoyé sur le compte)
		$virementUnilend = $transac->sumByday(11,$leMois,$lannee);
		
		// 12 virerment pour l'etat
		$virementEtat = $transac->sumByday(12,$leMois,$lannee);

		// 8 : retrait preteur
		$retraitPreteur = $transac->sumByday(8,$leMois,$lannee);
		
		// 13 regul commission
		$regulCom = $transac->sumByday(13,$leMois,$lannee);
		
		// 14 regul Preteurs
		//$regulPreteurs = $transac->sumByday(14,$leMois,$lannee);
		
		$listDates = array();
		for($i=1;$i<=$nbJours;$i++)
		{
			$listDates[$i] = $lanneeLemois.'-'.(strlen($i)<2?'0':'').$i;
		}
		
		// recup des prelevements permanent
		
		$listPrel = array();
		foreach($prelevements->select('type_prelevement = 1 AND status > 0 AND type = 1') as $prelev)
		{
			$addedXml = strtotime($prelev['added_xml']);
			$added = strtotime($prelev['added']);
			
			$dateaddedXml = date('Y-m',$addedXml);
			$date = date('Y-m',$added);
			$i = 1;
			
			// on enregistre dans la table la premier prelevement
			$listPrel[date('Y-m-d',$added)] += $prelev['montant'];
			
			// tant que la date de creation n'est pas egale on rajoute les mois entre
			while($date != $dateaddedXml)
			{
				$newdate = mktime(0,0,0,date('m',$added)+$i,date('d',$addedXml),date('Y',$added)); 
				
				$date = date('Y-m',$newdate);
				$added = date('Y-m-d',$newdate).' 00:00:00';
				
				$listPrel[date('Y-m-d',$newdate)] += $prelev['montant'];
				
				$i++;	
			}
			
			
		}
		
		// on recup totaux du mois dernier
		$oldDate = mktime(0,0,0,$leMois-1,1,$lannee); 
		$oldDate = date('Y-m',$oldDate);
		$etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);
		
		/*echo '<pre>';
		print_r($etat_quotidienOld);
		echo '</pre>';
		*/
		if($etat_quotidienOld != false)
		{
			$soldeDeLaVeille = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
			$soldeReel = $etat_quotidienOld['totalNewSoldeReel'];
			
			$soldeReel_old = $soldeReel;
			
			$soldeSFFPME_old = $etat_quotidienOld['totalSoldeSFFPME'];
			
			$soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
			
		}
		else
		{
			// Solde theorique
			$soldeDeLaVeille = 0;

			// solde reel
			$soldeReel = 0;
			$soldeReel_old = 0;
			
			$soldeSFFPME_old = 0;
			
			$soldeAdminFiscal_old = 0;
			
		}
		
		$newsoldeDeLaVeille = $soldeDeLaVeille;
		$newSoldeReel = $soldeReel;
		
		// ecart
		$oldecart = $soldeDeLaVeille-$soldeReel;
		
		// Solde SFF PME
		$soldeSFFPME = $soldeSFFPME_old;
			
		// Solde Admin. Fiscale
		$soldeAdminFiscal = $soldeAdminFiscal_old;
		
		//$bank_unilend->sumMontant('type ')
		
		// -- totaux -- //
		$totalAlimCB = 0;
		$totalAlimVirement = 0;
		$totalAlimPrelevement = 0;
		$totalRembEmprunteur = 0;
		$totalVirementEmprunteur = 0;
		$totalVirementCommissionUnilendEmprunteur = 0;
		$totalCommission = 0;
		
		// Retenues fiscales
		$totalPrelevements_obligatoires = 0;
		$totalRetenues_source = 0;
		$totalCsg = 0;
		$totalPrelevements_sociaux = 0;
		$totalContributions_additionnelles = 0;
		$totalPrelevements_solidarite = 0;
		$totalCrds = 0;
		
		$totalRetraitPreteur = 0;
		$totalSommeMouvements = 0;
		
		$totalNewSoldeReel = 0;
		
		$totalEcartSoldes = 0;
		
		// Solde SFF PME
		$totalSoldeSFFPME = $soldeSFFPME_old;
			
		// Solde Admin. Fiscale
		$totalSoldeAdminFiscal = $soldeAdminFiscal_old;
		
		// Remboursement des preteurs
		$affectationEchEmpr = 0;

		
		// -- fin totaux -- //
		
	/*	header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=etat_quotidien.xls");*/
		
		$tableau = '
		<style>
			table th,table td{width:80px;height:20px;border:1px solid black;}
			table td.dates{text-align:center;}
			.right{text-align:right;}
			.center{text-align:center;}
			.boder-top{border-top:1px solid black;}
			.boder-bottom{border-bottom:1px solid black;}
			.boder-left{border-left:1px solid black;}
			.boder-right{border-right:1px solid black;}
		</style>
        
		<table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
			<tr>
				<th colspan="31" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
			</tr>
			<tr>
				<th rowspan="2">'.$laDate.'</th>
				<th colspan="3">Chargements compte prêteurs</th>
				<th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="5">Soldes</th>
                <th colspan="5">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Prélèvements</th>

			</tr>
			<tr>
				
				<td class="center">Carte<br>bancaire</td>        
				<td class="center">Virement</td>
				<td class="center">Prélèvement</td>
				<td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements<br />sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart<br />global</td>
				<td class="center">Solde<br />SFF PME</td>
				<td class="center">Solde Admin.<br>Fiscale</td>
                
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
				<td class="center">Affectation<br />Ech. Empr.</td>
                <td class="center">Ecart<br />fiscal</td>
                
                <td class="center">Fichier<br />virements</td>
                <td class="center">Dont<br />SFF PME</td>
				<td class="center">Administration<br />Fiscale</td>
                <td class="center">Fichier<br />prélèvements</td>
			</tr>
			<tr>
				<td colspan="17">Début du mois</td>
                <td class="right">'.number_format($soldeDeLaVeille, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeReel, 2, ',', ' ').'</td>
                <td class="right">'.number_format($oldecart, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeSFFPME_old, 2, ',', ' ').'</td>
				<td class="right">'.number_format($soldeAdminFiscal_old, 2, ',', ' ').'</td>
				<td colspan="9">&nbsp;</td>
			</tr>';
            

			foreach($listDates as $key => $date)
			{

				if(strtotime($date.' 00:00:00') < $InfeA)
				{
					
				// sommes des echeance par jour
				$echangeDate = $echeanciers->getEcheanceByDayAll($date,'1');
				
				// on recup com de lecheance emprunteur a la date de mise a jour de la ligne (ddonc au changement de statut remboursé)
				//$commission = $echeanciers_emprunteur->sum('commission','LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" AND status_emprunteur = 1');
				
				// on met la commission au moment du remb preteurs
				$commission = $echeanciers_emprunteur->sum('commission','id_echeancier_emprunteur IN('.$listEcheances[$date].')');
				
				// commission sommes remboursé 
				$commission = ($commission/100);
				
				
				//$latva = $echeanciers_emprunteur->sum('tva','LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" AND status_emprunteur = 1');
				
				// On met la TVA au moment du remb preteurs
				$latva = $echeanciers_emprunteur->sum('tva','id_echeancier_emprunteur IN('.$listEcheances[$date].')');
				
				// la tva
				$latva = ($latva/100);
				
				$commission += $latva;
				
				////////////////////////////
				/// add regul commission ///
				
				$commission += $regulCom[$date]['montant'];
				
				
				///////////////////////////
				
				
				//prelevements_obligatoires
				$prelevements_obligatoires = $echangeDate['prelevements_obligatoires'];
				//retenues_source
				$retenues_source = $echangeDate['retenues_source'];
				//csg
				$csg = $echangeDate['csg'];
				//prelevements_sociaux 	
				$prelevements_sociaux = $echangeDate['prelevements_sociaux'];
				//contributions_additionnelles
				$contributions_additionnelles = $echangeDate['contributions_additionnelles'];
				//prelevements_solidarite
				$prelevements_solidarite = $echangeDate['prelevements_solidarite'];
				//crds
				$crds = $echangeDate['crds'];
				
				// Retenues Fiscales
				$retenuesFiscales = $prelevements_obligatoires+$retenues_source+$csg+$prelevements_sociaux+$contributions_additionnelles+$prelevements_solidarite+$crds;
				
				
				
				// total Mouvements
				$entrees = ($alimCB[$date]['montant']+$alimVirement[$date]['montant']+$alimPrelevement[$date]['montant']+$rembEmprunteur[$date]['montant']);
				$sorties = (str_replace('-','',$virementEmprunteur[$date]['montant'])+$virementEmprunteur[$date]['montant_unilend']+$commission+$retenuesFiscales+str_replace('-','',$retraitPreteur[$date]['montant']));
				
				// Total mouvementsc de la journée
				$sommeMouvements = ($entrees-$sorties);
				
				
;				// solde De La Veille (solde theorique)
				
				// addition du solde theorique et des mouvements
				$newsoldeDeLaVeille += $sommeMouvements;
				
				// On ajoute la regularisation des preteurs
				//$newsoldeDeLaVeille += $regulPreteurs[$date]['montant'];
				
				// Solde reel de base
				$soldeReel += $transac->getSoldeReelDay($date);
				
				// on rajoute les virements des emprunteurs
				$soldeReelUnilend = $transac->getSoldeReelUnilendDay($date);
				
				
				// solde pour l'etat
				$soldeReelEtat = $transac->getSoldeReelEtatDay($date);
				
				
				
				// la partie pour l'etat des remb unilend + la commission qu'on retire a chaque fois du solde
				$laComPlusLetat = $commission+$soldeReelEtat;
				
				// Solde réel  = solde reel unilend
				$soldeReel += $soldeReelUnilend - $laComPlusLetat;
				
				// on addition les solde precedant
				$newSoldeReel = $soldeReel; // on retire la commission des echeances du jour ainsi que la partie pour l'etat
				
				// On recupere le solde dans une autre variable
				$soldeTheorique = $newsoldeDeLaVeille;
				
				
				$leSoldeReel = $newSoldeReel;
				
				if(strtotime($date.' 00:00:00') > time())
				{
					$soldeTheorique = 0;
					$leSoldeReel = 0;
				}
				
				// ecart global soldes
				$ecartSoldes = ($soldeTheorique-$leSoldeReel);
				
				
				
				// Solde SFF PME
				$soldeSFFPME += $virementEmprunteur[$date]['montant_unilend']-$virementUnilend[$date]['montant']+$commission;
				
			
				
				// Solde Admin. Fiscale
				$soldeAdminFiscal += $retenuesFiscales-$virementEtat[$date]['montant'];
				
				////////////////////////////
				/// add regul partie etat fiscal ///
				
				$soldeAdminFiscal += $regulCom[$date]['montant_unilend'];
				
				
				///////////////////////////
				
				// somme capital preteurs par jour
				$capitalPreteur = $echangeDate['capital'];
				$capitalPreteur = ($capitalPreteur/100);
				
				// somme net net preteurs par jour
				$interetNetPreteur = ($echangeDate['interets']/100)-$retenuesFiscales;
				
				// Montant preteur
				$montantPreteur = ($interetNetPreteur+$capitalPreteur);

				// Affectation Ech. Empr.
				//$affectationEchEmpr = $lrembPreteurs[$date]['montant']+$lrembPreteurs[$date]['etat'];
				$affectationEchEmpr = $lrembPreteurs[$date]['montant']+$lrembPreteurs[$date]['etat']+$commission;
				
				

				// ecart Mouv Internes
				//$ecartMouvInternes = ($rembEmprunteur[$date]['montant'])-$commission-$retenuesFiscales-$capitalPreteur-$interetNetPreteur;
				$ecartMouvInternes = round(($affectationEchEmpr)-$commission-$retenuesFiscales-$capitalPreteur-$interetNetPreteur,2);
				
				
				
				
				// solde bids validés
				//$octroi_pret = $loans->sumLoansbyDay($date);
				$octroi_pret = (str_replace('-','',$virementEmprunteur[$date]['montant'])+$virementEmprunteur[$date]['montant_unilend']);
				
				
				// Virements ok (fichier virements)
				$virementsOK = $virements->sumVirementsbyDay($date,'status > 0');
				
				//dont sffpme virements (argent gagné a donner a sffpme)
				$virementsAttente = $virementUnilend[$date]['montant'];
				
				// Administration Fiscale
				$adminFiscalVir = $virementEtat[$date]['montant'];
				
				
				// prelevements
				$prelevPonctuel = $prelevements->sum('LEFT(added_xml,10) = "'.$date.'" AND status > 0');
				
				if($listPrel[$date] != false)
				{
					$sommePrelev = $prelevPonctuel+$listPrel[$date];
					//echo $prelevPonctuel .'<br>';
				}
				else $sommePrelev = $prelevPonctuel;
				
				$sommePrelev = $sommePrelev/100;
				
				// additions //

				$totalAlimCB += $alimCB[$date]['montant'];
				$totalAlimVirement += $alimVirement[$date]['montant'];
				$totalAlimPrelevement += $alimPrelevement[$date]['montant'];
				$totalRembEmprunteur += $rembEmprunteur[$date]['montant'];
				$totalVirementEmprunteur += str_replace('-','',$virementEmprunteur[$date]['montant']);
				$totalVirementCommissionUnilendEmprunteur += $virementEmprunteur[$date]['montant_unilend'];
				
				
				$totalCommission += $commission;
				
				
				$totalPrelevements_obligatoires += $prelevements_obligatoires;
				$totalRetenues_source += $retenues_source;
				$totalCsg += $csg;
				$totalPrelevements_sociaux += $prelevements_sociaux;
				$totalContributions_additionnelles += $contributions_additionnelles;
				$totalPrelevements_solidarite += $prelevements_solidarite;
				$totalCrds += $crds;
				
				$totalRetraitPreteur += $retraitPreteur[$date]['montant'];
				$totalSommeMouvements += $sommeMouvements;
				$totalNewsoldeDeLaVeille = $newsoldeDeLaVeille; // Solde théorique
				$totalNewSoldeReel = $newSoldeReel;
				$totalEcartSoldes = $ecartSoldes;
				$totalAffectationEchEmpr += $affectationEchEmpr;
				
				
				
				// Solde SFF PME
				$totalSoldeSFFPME = $soldeSFFPME;
				// Solde Admin. Fiscale
				$totalSoldeAdminFiscal = $soldeAdminFiscal;
				
				$totalOctroi_pret += $octroi_pret;
				$totalCapitalPreteur += $capitalPreteur;
				$totalInteretNetPreteur += $interetNetPreteur;
				
				$totalEcartMouvInternes += $ecartMouvInternes;
				
				$totalVirementsOK += $virementsOK;
				
				// dont sff pme
				$totalVirementsAttente += $virementsAttente;
				
				$totaladdsommePrelev += $sommePrelev;
				
				$totalAdminFiscalVir += $adminFiscalVir;
				
				
				
				$tableau .= '
				<tr>
					<td class="dates">'.(strlen($key)<2?'0':'').$key.'/'.$lemoisLannee2.'</td>
                    <td class="right">'.number_format($alimCB[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($alimVirement[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($alimPrelevement[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($rembEmprunteur[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format(str_replace('-','',$virementEmprunteur[$date]['montant']), 2, ',', ' ').'</td>
                    <td class="right">'.number_format($virementEmprunteur[$date]['montant_unilend'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($commission, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_obligatoires, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($retenues_source, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($csg, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_sociaux, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($contributions_additionnelles, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_solidarite, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($crds, 2, ',', ' ').'</td>

                    <td class="right">'.number_format(str_replace('-','',$retraitPreteur[$date]['montant']), 2, ',', ' ').'</td>
                    <td class="right">'.number_format($sommeMouvements, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($soldeTheorique, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($leSoldeReel, 2, ',', ' ').'</td>
                    <td class="right">'.number_format(round($ecartSoldes,2), 2, ',', ' ').'</td>
					<td class="right">'.number_format($soldeSFFPME, 2, ',', ' ').'</td>
					<td class="right">'.number_format($soldeAdminFiscal, 2, ',', ' ').'</td>
                   	
                    <td class="right">'.number_format($octroi_pret, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($capitalPreteur, 2, ',', ' ').'</td>
                   	<td class="right">'.number_format($interetNetPreteur, 2, ',', ' ').'</td>
					<td class="right">'.number_format($affectationEchEmpr, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($ecartMouvInternes, 2, ',', ' ').'</td>
					
                    <td class="right">'.number_format($virementsOK, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($virementsAttente, 2, ',', ' ').'</td>
					<td class="right">'.number_format($adminFiscalVir, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($sommePrelev, 2, ',', ' ').'</td>
				</tr>';
				
				}
				else
				{
				$tableau .= '
                <tr>
                    <td class="dates">'.(strlen($key)<2?'0':'').$key.'/'.$lemoisLannee2.'</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
					<td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
					<td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
                </tr>';
                	
				}
			}
			
			$tableau .= '
            <tr>
				<td colspan="31">&nbsp;</td>
			</tr>
            <tr>
				<th>Total mois</th>
                <th class="right">'.number_format($totalAlimCB, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalAlimVirement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalAlimPrelevement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalRembEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementCommissionUnilendEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCommission, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_obligatoires, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalRetenues_source, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCsg, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_sociaux, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalContributions_additionnelles, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_solidarite, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCrds, 2, ',', ' ').'</th>
                <th class="right">'.number_format(str_replace('-','',$totalRetraitPreteur), 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalSommeMouvements, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalNewsoldeDeLaVeille, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalNewSoldeReel, 2, ',', ' ').'</th>
                <th class="right">'.number_format(round($totalEcartSoldes,2), 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalSoldeSFFPME, 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalSoldeAdminFiscal, 2, ',', ' ').'</th>
				
                <th class="right">'.number_format($totalOctroi_pret, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCapitalPreteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalInteretNetPreteur, 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalAffectationEchEmpr, 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalEcartMouvInternes, 2, ',', ' ').'</th>
				
                <th class="right">'.number_format($totalVirementsOK, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementsAttente, 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalAdminFiscalVir, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totaladdsommePrelev, 2, ',', ' ').'</th>
			</tr>
		</table>';
		
		$table[1]['name'] = 'totalAlimCB';
		$table[1]['val'] = $totalAlimCB;
		$table[2]['name'] = 'totalAlimVirement';
		$table[2]['val'] = $totalAlimVirement;
		$table[3]['name'] = 'totalAlimPrelevement';
		$table[3]['val'] = $totalAlimPrelevement;
		$table[4]['name'] = 'totalRembEmprunteur';
		$table[4]['val'] = $totalRembEmprunteur;
		$table[5]['name'] = 'totalVirementEmprunteur';
		$table[5]['val'] = $totalVirementEmprunteur;
		$table[6]['name'] = 'totalVirementCommissionUnilendEmprunteur';
		$table[6]['val'] = $totalVirementCommissionUnilendEmprunteur;
		$table[7]['name'] = 'totalCommission';
		$table[7]['val'] = $totalCommission;
		
		$table[8]['name'] = 'totalPrelevements_obligatoires';
		$table[8]['val'] = $totalPrelevements_obligatoires;
		$table[9]['name'] = 'totalRetenues_source';
		$table[9]['val'] = $totalRetenues_source;
		$table[10]['name'] = 'totalCsg';
		$table[10]['val'] = $totalCsg;
		$table[11]['name'] = 'totalPrelevements_sociaux';
		$table[11]['val'] = $totalPrelevements_sociaux;
		$table[12]['name'] = 'totalContributions_additionnelles';
		$table[12]['val'] = $totalContributions_additionnelles;
		$table[13]['name'] = 'totalPrelevements_solidarite';
		$table[13]['val'] = $totalPrelevements_solidarite;
		$table[14]['name'] = 'totalCrds';
		$table[14]['val'] = $totalCrds;
		
		$table[15]['name'] = 'totalRetraitPreteur';
		$table[15]['val'] = $totalRetraitPreteur;
		$table[16]['name'] = 'totalSommeMouvements';
		$table[16]['val'] = $totalSommeMouvements;
		$table[17]['name'] = 'totalNewsoldeDeLaVeille';
		//$table[17]['val'] = $totalNewsoldeDeLaVeille-$soldeDeLaVeille;
		$table[17]['val'] = $totalNewsoldeDeLaVeille;
		$table[18]['name'] = 'totalNewSoldeReel';
		//$table[18]['val'] = $totalNewSoldeReel-$soldeReel_old;
		$table[18]['val'] = $totalNewSoldeReel;
		$table[19]['name'] = 'totalEcartSoldes';
		$table[19]['val'] = $totalEcartSoldes;
		
		$table[20]['name'] = 'totalOctroi_pret';
		$table[20]['val'] = $totalOctroi_pret;
		
		$table[21]['name'] = 'totalCapitalPreteur';
		$table[21]['val'] = $totalCapitalPreteur;
		$table[22]['name'] = 'totalInteretNetPreteur';
		$table[22]['val'] = $totalInteretNetPreteur;
		$table[23]['name'] = 'totalEcartMouvInternes';
		$table[23]['val'] = $totalEcartMouvInternes;
		
		$table[24]['name'] = 'totalVirementsOK';
		$table[24]['val'] = $totalVirementsOK;
		$table[25]['name'] = 'totalVirementsAttente';
		$table[25]['val'] = $totalVirementsAttente;
		$table[26]['name'] = 'totaladdsommePrelev';
		$table[26]['val'] = $totaladdsommePrelev;
		
		// Solde SFF PME
		$table[27]['name'] = 'totalSoldeSFFPME';
		$table[27]['val'] = $totalSoldeSFFPME;
		//$table[27]['val'] = $totalSoldeSFFPME-$soldeSFFPME_old;
		
		// Solde Admin. Fiscale
		$table[28]['name'] = 'totalSoldeAdminFiscal';
		//$table[28]['val'] = $totalSoldeAdminFiscal-$soldeAdminFiscal_old;
		$table[28]['val'] = $totalSoldeAdminFiscal;
		
		// Solde Admin. Fiscale (virement)
		$table[29]['name'] = 'totalAdminFiscalVir';
		$table[29]['val'] = $totalAdminFiscalVir;
		
		$table[30]['name'] = 'totalAffectationEchEmpr';
		$table[30]['val'] = $totalAffectationEchEmpr;
		
		// create sav solde
		$etat_quotidien->createEtat_quotidient($table,$leMois,$lannee);
		
		// on recup toataux du mois de decembre de l'année precedente
		$oldDate = mktime(0,0,0,12,$jour,$lannee-1); 
		$oldDate = date('Y-m',$oldDate);
		$etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);
		
		
		
		if($etat_quotidienOld != false)
		{
			$soldeDeLaVeille = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
			$soldeReel = $etat_quotidienOld['totalNewSoldeReel'];
			
			$soldeSFFPME_old = $etat_quotidienOld['totalSoldeSFFPME'];
			
			$soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
		}
		else
		{
			// Solde theorique
			$soldeDeLaVeille = 0;

			// solde reel
			$soldeReel = 0;
			
			$soldeSFFPME_old = 0;
			
			$soldeAdminFiscal_old = 0;
			
		}
		
		$newsoldeDeLaVeille = $soldeDeLaVeille;
		$newSoldeReel = $soldeReel;
		
		$soldeSFFPME = $soldeSFFPME_old;
			
		$soldeAdminFiscal = $soldeAdminFiscal_old;
		
		// ecart
		$oldecart = $soldeDeLaVeille-$soldeReel;
		
		$tableau .= '
		<table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
			
            <tr>
				<th colspan="31" style="font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">&nbsp;</th>
			</tr>
            <tr>
				<th colspan="31" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
			</tr>
			<tr>
				<th rowspan="2">'.$lannee.'</th>
				<th colspan="3">Chargements compte prêteurs</th>
				<th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="5">Soldes</th>
                <th colspan="5">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Prélèvements</th>

			</tr>
			<tr>
				
				<td class="center">Carte<br />bancaire</td>        
				<td class="center">Virement</td>
				<td class="center">Prélèvement</td>
				<td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements<br />sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart<br />global</td>
				<td class="center">Solde<br />SFF PME</td>
				<td class="center">Solde Admin.<br>Fiscale</td>
                
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
				<td class="center">Affectation<br />Ech. Empr.</td>
                <td class="center">Ecart<br />fiscal</td>
                
                <td class="center">Fichier<br />virements</td>
                <td class="center">Dont<br />SFF PME</td>
				<td class="center">Administration<br />Fiscale</td>
                <td class="center">Fichier<br />prélèvements</td>
			</tr>
			<tr>
				<td colspan="17">Début d\'année</td>
                <td class="right">'.number_format($soldeDeLaVeille, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeReel, 2, ',', ' ').'</td>
                <td class="right">'.number_format($oldecart, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeSFFPME_old, 2, ',', ' ').'</td>
				<td class="right">'.number_format($soldeAdminFiscal_old, 2, ',', ' ').'</td>
				
				<td colspan="9">&nbsp;</td>
			</tr>';
			
			$sommetotalAlimCB = 0;
			$sommetotalAlimVirement = 0;
			$sommetotalAlimPrelevement = 0;
			$sommetotalRembEmprunteur = 0;
			$sommetotalVirementEmprunteur = 0;
			$sommetotalVirementCommissionUnilendEmprunteur = 0;
			$sommetotalCommission = 0;
			
			// Retenues fiscales
			$sommetotalPrelevements_obligatoires = 0;
			$sommetotalRetenues_source = 0;
			$sommetotalCsg = 0;
			$sommetotalPrelevements_sociaux = 0;
			$sommetotalContributions_additionnelles = 0;
			$sommetotalPrelevements_solidarite = 0;
			$sommetotalCrds = 0;
			$sommetotalAffectationEchEmpr = 0;
			
			// Remboursements aux prêteurs
			$sommetotalRetraitPreteur = 0;
			
			$sommetotalSommeMouvements = 0;
			
			// Soldes
			/*$sommetotalNewsoldeDeLaVeille = $newsoldeDeLaVeille;
			$sommetotalNewSoldeReel = $soldeReel;
			$sommetotalEcartSoldes = $oldecart;
			$sommetotalSoldeSFFPME = $soldeSFFPME;
			$sommetotalSoldeAdminFiscal = $soldeAdminFiscal;*/
			
			/*$sommetotalNewsoldeDeLaVeille = $totalNewsoldeDeLaVeille;
			$sommetotalNewSoldeReel = $totalNewSoldeReel;
			$sommetotalEcartSoldes = $totalEcartSoldes;
			$sommetotalSoldeSFFPME = $totalSoldeSFFPME;
			$sommetotalSoldeAdminFiscal = $totalSoldeAdminFiscal;*/
			
			$sommetotalNewsoldeDeLaVeille = 0;
			$sommetotalNewSoldeReel = 0;
			$sommetotalEcartSoldes = 0;
			$sommetotalSoldeSFFPME = 0;
			$sommetotalSoldeAdminFiscal = 0;
			
			// Mouvements internes
			$sommetotalOctroi_pret = 0;
			$sommetotalCapitalPreteur = 0;
			$sommetotalInteretNetPreteur = 0;
			$sommetotalEcartMouvInternes = 0;
			
			// Virements
			$sommetotalVirementsOK = 0;
			$sommetotalVirementsAttente = 0;
			$sommetotalAdminFiscalVir = 0;	
			
			// Prélèvements
			$sommetotaladdsommePrelev = 0;
			
			
			
			for($i=1;$i<=12;$i++)
			{
				
				
				if(strlen($i)<2)$numMois = '0'.$i;
				else $numMois = $i;
				
				$lemois = $etat_quotidien->getTotauxbyMonth($lannee.'-'.$numMois);
				
				$sommetotalAlimCB += $lemois['totalAlimCB'];
				$sommetotalAlimVirement += $lemois['totalAlimVirement'];
				$sommetotalAlimPrelevement += $lemois['totalAlimPrelevement'];
				$sommetotalRembEmprunteur += $lemois['totalRembEmprunteur'];
				$sommetotalVirementEmprunteur += $lemois['totalVirementEmprunteur'];
				$sommetotalVirementCommissionUnilendEmprunteur += $lemois['totalVirementCommissionUnilendEmprunteur'];
				$sommetotalCommission += $lemois['totalCommission'];
				
				// Retenues fiscales
				$sommetotalPrelevements_obligatoires += $lemois['totalPrelevements_obligatoires'];
				$sommetotalRetenues_source += $lemois['totalRetenues_source'];
				$sommetotalCsg += $lemois['totalCsg'];
				$sommetotalPrelevements_sociaux += $lemois['totalPrelevements_sociaux'];
				$sommetotalContributions_additionnelles += $lemois['totalContributions_additionnelles'];
				$sommetotalPrelevements_solidarite += $lemois['totalPrelevements_solidarite'];
				$sommetotalCrds += $lemois['totalCrds'];
				
				// Remboursements aux prêteurs
				$sommetotalRetraitPreteur += $lemois['totalRetraitPreteur'];
				
				$sommetotalSommeMouvements += $lemois['totalSommeMouvements'];
				
				// Soldes
				if($lemois != false)
				{
				$sommetotalNewsoldeDeLaVeille = $lemois['totalNewsoldeDeLaVeille'];
				$sommetotalNewSoldeReel = $lemois['totalNewSoldeReel'];
				$sommetotalEcartSoldes = $lemois['totalEcartSoldes'];
				$sommetotalSoldeSFFPME = $lemois['totalSoldeSFFPME'];
				$sommetotalSoldeAdminFiscal = $lemois['totalSoldeAdminFiscal'];
				}
				
				// Mouvements internes
				$sommetotalOctroi_pret += $lemois['totalOctroi_pret'];
				$sommetotalCapitalPreteur += $lemois['totalCapitalPreteur'];
				$sommetotalInteretNetPreteur += $lemois['totalInteretNetPreteur'];
				$sommetotalEcartMouvInternes += $lemois['totalEcartMouvInternes'];
				
				// Virements
				$sommetotalVirementsOK += $lemois['totalVirementsOK'];
				$sommetotalVirementsAttente += $lemois['totalVirementsAttente'];
				$sommetotalAdminFiscalVir += $lemois['totalAdminFiscalVir'];
				
				// Prélèvements
				$sommetotaladdsommePrelev += $lemois['totaladdsommePrelev'];
				
				
				$sommetotalAffectationEchEmpr += $lemois['totalAffectationEchEmpr'];

				$tableau .= '
                <tr>
                	<th>'.$this->dates->tableauMois['fr'][$i].'</th>';
                    
					if($lemois != false)
					{
						$tableau .= '
						<td class="right">'.number_format($lemois['totalAlimCB'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAlimVirement'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAlimPrelevement'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalRembEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementCommissionUnilendEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCommission'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_obligatoires'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalRetenues_source'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCsg'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_sociaux'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalContributions_additionnelles'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_solidarite'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCrds'], 2, ',', ' ').'</td>
						<td class="right">'.number_format(str_replace('-','',$lemois['totalRetraitPreteur']), 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalSommeMouvements'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalNewsoldeDeLaVeille'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalNewSoldeReel'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalEcartSoldes'], 2, ',', ' ').'</td>
						
						<td class="right">'.number_format($lemois['totalSoldeSFFPME'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalSoldeAdminFiscal'], 2, ',', ' ').'</td>
												
						<td class="right">'.number_format($lemois['totalOctroi_pret'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCapitalPreteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalInteretNetPreteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAffectationEchEmpr'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalEcartMouvInternes'], 2, ',', ' ').'</td>
						
						<td class="right">'.number_format($lemois['totalVirementsOK'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementsAttente'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAdminFiscalVir'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totaladdsommePrelev'], 2, ',', ' ').'</td>';

					}
					else
					{
						$tableau .= '
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>

						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						';
					}
					
				$tableau .= '</tr>';
				
			}
			
            $tableau .= '
            <tr>
				<th>Total année</th>
                
				<th class="right">'.number_format($sommetotalAlimCB, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalAlimVirement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalAlimPrelevement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalRembEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementCommissionUnilendEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCommission, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_obligatoires, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalRetenues_source, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCsg, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_sociaux, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalContributions_additionnelles, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_solidarite, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCrds, 2, ',', ' ').'</th>
                <th class="right">'.number_format(str_replace('-','',$sommetotalRetraitPreteur), 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalSommeMouvements, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalNewsoldeDeLaVeille, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalNewSoldeReel, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalEcartSoldes, 2, ',', ' ').'</th>
				<th class="right">'.number_format($sommetotalSoldeSFFPME, 2, ',', ' ').'</th>
				<th class="right">'.number_format($sommetotalSoldeAdminFiscal, 2, ',', ' ').'</th>
				
                <th class="right">'.number_format($sommetotalOctroi_pret, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCapitalPreteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalInteretNetPreteur, 2, ',', ' ').'</th>
				 <th class="right">'.number_format($sommetotalAffectationEchEmpr, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalEcartMouvInternes, 2, ',', ' ').'</th>
				
                <th class="right">'.number_format($sommetotalVirementsOK, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementsAttente, 2, ',', ' ').'</th>
				<th class="right">'.number_format($sommetotalAdminFiscalVir, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotaladdsommePrelev, 2, ',', ' ').'</th>
					
            </tr>
            
		</table>';
			
			
			
			
		if($this->Config['env'] == 'prod')echo utf8_decode($tableau);
		else echo ($tableau);
		//die;
		// si on met un param on peut regarder sans enregister de fichier ou d'envoie de mail
		if(isset($this->params[0]))
		{
			die;
		}
		//die;
		$filename = 'Unilend_etat_'.date('Ymd');
		//$filename = 'Unilend_etat_'.$ladatedetest;
		//$filename = 'Unilend_etat_20140301';
		
		
		
		if($this->Config['env'] == 'prod')
		{
			$connection = ssh2_connect('ssh.reagi.com', 22);
			ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
			$sftp = ssh2_sftp($connection);
			$sftpStream = @fopen('ssh2.sftp://'.$sftp.'/home/sfpmei/emissions/etat_quotidien/'.$filename.'.xls', 'w');
			fwrite($sftpStream, $tableau);
			fclose($sftpStream);
		}
		
		
		
  		file_put_contents($this->path.'protected/sftp/etat_quotidien/'.$filename.'.xls',$tableau);
		//file_put_contents($this->path.'protected/sftp/etat_quotidien_temp/'.$filename.'.xls',$tableau);
		//die;
		//mail('d.courtier@equinoa.com','unilend '.$this->Config['env'].' cron','etat quotidien date : '.date('d/m/y H:i:s'));
		
		//die;
		
		//************************************//
		//*** ENVOI DU MAIL ETAT QUOTIDIEN ***//
		//************************************//
		
		// destinataire
		$this->settings->get('Adresse notification etat quotidien','type');
		$destinataire = $this->settings->value;
		//$destinataire = 'd.courtier@equinoa.com';

		// Recuperation du modele de mail
		$this->mails_text->get('notification-etat-quotidien','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		
		// Attribution des données aux variables
		$sujetMail = $this->mails_text->subject;
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
		$this->email->attachFromString($tableau,$filename.'.xls');
		$this->email->addRecipient(trim($destinataire));
		
		//if($this->Config['env'] == 'prod'){
			//$this->email->addRecipient('emmanuel.perezduarte@unilend.fr');
		//}
	
		$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
		$this->email->setHTMLBody($texteMail);
		Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
		// fin mail
		
	}
	
	// 1 fois pr jour a  1h du matin
	function _etat_quotidien_old2()
	{
		$jour = date('d');
		
		// si on veut mettre a jour une date on met le jour ici mais attention ca va sauvegarder enbdd et sur l'etat quotidien fait ce matin a 1h du mat
		
		//$jour = 1;
		
		
		// modif manuelle dans etat quotidien de janvier 2014 total solde sfpme 0.69 € retiré
		
		//$num = '31';
		//$ladatedetest = '201403'.$num;
		if($jour == 1)
		{
			// On recup le nombre de jour dans le mois
			$mois = mktime( 0, 0, 0, date('m')-1, 1,date('Y')); 
			
			//$mois = mktime( 0, 0, 0, date('m'), $num,date('Y')); 
			//$jour = $num;
			
			$nbJours = date("t",$mois);
			
			$leMois = date('m',$mois);
			$lannee = date('Y',$mois);
			$leJour = $nbJours;
			
			// affiche les données avant cette date
			$InfeA = mktime( 0, 0, 0, date('m'), 1,date('Y')); 
			//$InfeA = mktime( 0, 0, 0, date('m'), $num,date('Y')); 
			
			$lanneeLemois = $lannee.'-'.$leMois;
			
			// affichage de la date du fichier
			$laDate = $jour.'-'.date('m').'-'.date('Y');
			
			$lemoisLannee2 = $leMois.'/'.$lannee;
		}
		else
		{
			// On recup le nombre de jour dans le mois
			$mois = mktime( 0, 0, 0, date('m'), 1,date('Y')); 
			$nbJours = date("t",$mois);
			
			$leMois = date('m');
			$lannee = date('Y');
			$leJour = $nbJours;
			
			$InfeA = mktime( 0, 0, 0, date('m'), date('d'),date('Y')); 
			//$InfeA = mktime( 0, 0, 0, 12, 30,2013); 
			
			
			$lanneeLemois = date('Y-m');
			
			$laDate = date('d-m-Y');
			
			$lemoisLannee2 = date('m/Y');
		}
		
		// chargement des datas
		$transac = $this->loadData('transactions');
		$echeanciers = $this->loadData('echeanciers');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$loans = $this->loadData('loans');
		$virements = $this->loadData('virements');
		$prelevements = $this->loadData('prelevements');
		$etat_quotidien = $this->loadData('etat_quotidien');
		$bank_unilend = $this->loadData('bank_unilend');
		
		// alimentations CB
		$alimCB = $transac->sumByday(3,$leMois,$lannee);
		
		// 2 : alimentations virements
		$alimVirement = $transac->sumByday(4,$leMois,$lannee);
		
		// 7 : alimentations prelevements
		$alimPrelevement = $transac->sumByday(7,$leMois,$lannee);
		
		// 6 : remb Emprunteur (prelevement)
		$rembEmprunteur = $transac->sumByday(6,$leMois,$lannee);
		
		// 9 : virement emprunteur (octroi prêt : montant | commissions octoi pret : unilend_montant)
		$virementEmprunteur = $transac->sumByday(9,$leMois,$lannee);
		
		// 11 : virement unilend (argent gagné envoyé sur le compte)
		$virementUnilend = $transac->sumByday(11,$leMois,$lannee);
		
		// 12 virerment pour l'etat
		$virementEtat = $transac->sumByday(12,$leMois,$lannee);

		// 8 : retrait preteur
		$retraitPreteur = $transac->sumByday(8,$leMois,$lannee);
		
		// 13 regul commission
		$regulCom = $transac->sumByday(13,$leMois,$lannee);
		
		// 14 regul Preteurs
		$regulPreteurs = $transac->sumByday(14,$leMois,$lannee);
		
		$listDates = array();
		for($i=1;$i<=$nbJours;$i++)
		{
			$listDates[$i] = $lanneeLemois.'-'.(strlen($i)<2?'0':'').$i;
		}
		
		// recup des prelevements permanent
		
		$listPrel = array();
		foreach($prelevements->select('type_prelevement = 1 AND status > 0 AND type = 1') as $prelev)
		{
			$addedXml = strtotime($prelev['added_xml']);
			$added = strtotime($prelev['added']);
			
			$dateaddedXml = date('Y-m',$addedXml);
			$date = date('Y-m',$added);
			$i = 1;
			
			// on enregistre dans la table la premier prelevement
			$listPrel[date('Y-m-d',$added)] += $prelev['montant'];
			
			// tant que la date de creation n'est pas egale on rajoute les mois entre
			while($date != $dateaddedXml)
			{
				$newdate = mktime(0,0,0,date('m',$added)+$i,date('d',$addedXml),date('Y',$added)); 
				
				$date = date('Y-m',$newdate);
				$added = date('Y-m-d',$newdate).' 00:00:00';
				
				$listPrel[date('Y-m-d',$newdate)] += $prelev['montant'];
				
				$i++;	
			}
			
			
		}
		
		// on recup totaux du mois dernier
		$oldDate = mktime(0,0,0,$leMois-1,1,$lannee); 
		$oldDate = date('Y-m',$oldDate);
		
		$etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);
		
		/*echo '<pre>';
		print_r($etat_quotidienOld);
		echo '</pre>';
		*/
		if($etat_quotidienOld != false)
		{
			$soldeDeLaVeille = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
			$soldeReel = $etat_quotidienOld['totalNewSoldeReel'];
			
			$soldeReel_old = $soldeReel;
			
			$soldeSFFPME_old = $etat_quotidienOld['totalSoldeSFFPME'];
			
			$soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
			
		}
		else
		{
			// Solde theorique
			$soldeDeLaVeille = 0;

			// solde reel
			$soldeReel = 0;
			$soldeReel_old = 0;
			
			$soldeSFFPME_old = 0;
			
			$soldeAdminFiscal_old = 0;
			
		}
		
		$newsoldeDeLaVeille = $soldeDeLaVeille;
		$newSoldeReel = $soldeReel;
		
		// ecart
		$oldecart = $soldeDeLaVeille-$soldeReel;
		
		// Solde SFF PME
		$soldeSFFPME = $soldeSFFPME_old;
			
		// Solde Admin. Fiscale
		$soldeAdminFiscal = $soldeAdminFiscal_old;
		
		//$bank_unilend->sumMontant('type ')
		
		// -- totaux -- //
		$totalAlimCB = 0;
		$totalAlimVirement = 0;
		$totalAlimPrelevement = 0;
		$totalRembEmprunteur = 0;
		$totalVirementEmprunteur = 0;
		$totalVirementCommissionUnilendEmprunteur = 0;
		$totalCommission = 0;
		
		// Retenues fiscales
		$totalPrelevements_obligatoires = 0;
		$totalRetenues_source = 0;
		$totalCsg = 0;
		$totalPrelevements_sociaux = 0;
		$totalContributions_additionnelles = 0;
		$totalPrelevements_solidarite = 0;
		$totalCrds = 0;
		
		$totalRetraitPreteur = 0;
		$totalSommeMouvements = 0;
		
		$totalNewSoldeReel = 0;
		
		$totalEcartSoldes = 0;
		
		// Solde SFF PME
		$totalSoldeSFFPME = $soldeSFFPME_old;
			
		// Solde Admin. Fiscale
		$totalSoldeAdminFiscal = $soldeAdminFiscal_old;

		
		// -- fin totaux -- //
		
	/*	header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=etat_quotidien.xls");*/
		
		$tableau = '
		<style>
			table th,table td{width:80px;height:20px;border:1px solid black;}
			table td.dates{text-align:center;}
			.right{text-align:right;}
			.center{text-align:center;}
			.boder-top{border-top:1px solid black;}
			.boder-bottom{border-bottom:1px solid black;}
			.boder-left{border-left:1px solid black;}
			.boder-right{border-right:1px solid black;}
		</style>
        
		<table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
			<tr>
				<th colspan="30" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
			</tr>
			<tr>
				<th rowspan="2">'.$laDate.'</th>
				<th colspan="3">Chargements compte prêteurs</th>
				<th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="5">Soldes</th>
                <th colspan="4">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Prélèvements</th>

			</tr>
			<tr>
				
				<td class="center">Carte<br>bancaire</td>        
				<td class="center">Virement</td>
				<td class="center">Prélèvement</td>
				<td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements<br />sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart<br />global</td>
				<td class="center">Solde<br />SFF PME</td>
				<td class="center">Solde Admin.<br>Fiscale</td>
                
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
                <td class="center">Ecart<br />fiscal</td>
                
                <td class="center">Fichier<br />virements</td>
                <td class="center">Dont<br />SFF PME</td>
				<td class="center">Administration<br />Fiscale</td>
                <td class="center">Fichier<br />prélèvements</td>
			</tr>
			<tr>
				<td colspan="17">Début du mois</td>
                <td class="right">'.number_format($soldeDeLaVeille, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeReel, 2, ',', ' ').'</td>
                <td class="right">'.number_format($oldecart, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeSFFPME_old, 2, ',', ' ').'</td>
				<td class="right">'.number_format($soldeAdminFiscal_old, 2, ',', ' ').'</td>
				<td colspan="8">&nbsp;</td>
			</tr>';
            

			foreach($listDates as $key => $date)
			{

				if(strtotime($date.' 00:00:00') < $InfeA)
				{
					
				// sommes des echeance par jour
				$echangeDate = $echeanciers->getEcheanceByDayAll($date,'1');
				
				// on recup com de lecheance emprunteur a la date de mise a jour de la ligne (ddonc au changement de statut remboursé)
				$commission = $echeanciers_emprunteur->sum('commission','LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" AND status_emprunteur = 1');
				
				// commission sommes remboursé 
				$commission = ($commission/100);
				
				
				$latva = $echeanciers_emprunteur->sum('tva','LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" AND status_emprunteur = 1');
				// la tva
				$latva = ($latva/100);
				
				$commission += $latva;
				
				////////////////////////////
				/// add regul commission ///
				
				$commission += $regulCom[$date]['montant'];
				
				
				///////////////////////////
				
				
				//prelevements_obligatoires
				$prelevements_obligatoires = $echangeDate['prelevements_obligatoires'];
				//retenues_source
				$retenues_source = $echangeDate['retenues_source'];
				//csg
				$csg = $echangeDate['csg'];
				//prelevements_sociaux 	
				$prelevements_sociaux = $echangeDate['prelevements_sociaux'];
				//contributions_additionnelles
				$contributions_additionnelles = $echangeDate['contributions_additionnelles'];
				//prelevements_solidarite
				$prelevements_solidarite = $echangeDate['prelevements_solidarite'];
				//crds
				$crds = $echangeDate['crds'];
				
				// Retenues Fiscales
				$retenuesFiscales = $prelevements_obligatoires+$retenues_source+$csg+$prelevements_sociaux+$contributions_additionnelles+$prelevements_solidarite+$crds;
				
				
				
				// total Mouvements
				$entrees = ($alimCB[$date]['montant']+$alimVirement[$date]['montant']+$alimPrelevement[$date]['montant']+$rembEmprunteur[$date]['montant']);
				$sorties = (str_replace('-','',$virementEmprunteur[$date]['montant'])+$virementEmprunteur[$date]['montant_unilend']+$commission+$retenuesFiscales+str_replace('-','',$retraitPreteur[$date]['montant']));
				
				// Total mouvementsc de la journée
				$sommeMouvements = ($entrees-$sorties);
				
				
;				// solde De La Veille (solde theorique)
				
				// addition du solde theorique et des mouvements
				$newsoldeDeLaVeille += $sommeMouvements;
				
				// On ajoute la regularisation des preteurs
				//$newsoldeDeLaVeille += $regulPreteurs[$date]['montant'];
				
				// Solde reel de base
				$soldeReel += $transac->getSoldeReelDay($date);
				
				// on rajoute les virements des emprunteurs
				$soldeReelUnilend = $transac->getSoldeReelUnilendDay($date);
				
				
				// solde pour l'etat
				$soldeReelEtat = $transac->getSoldeReelEtatDay($date);
				
				
				
				// la partie pour l'etat des remb unilend + la commission qu'on retire a chaque fois du solde
				$laComPlusLetat = $commission+$soldeReelEtat;
				
				// Solde réel  = solde reel unilend
				$soldeReel += $soldeReelUnilend - $laComPlusLetat;
				
				// on addition les solde precedant
				$newSoldeReel = $soldeReel; // on retire la commission des echeances du jour ainsi que la partie pour l'etat
				
				// On recupere le solde dans une autre variable
				$soldeTheorique = $newsoldeDeLaVeille;
				
				
				$leSoldeReel = $newSoldeReel;
				
				if(strtotime($date.' 00:00:00') > time())
				{
					$soldeTheorique = 0;
					$leSoldeReel = 0;
				}
				
				// ecart global soldes
				$ecartSoldes = ($soldeTheorique-$leSoldeReel);
				
				
				
				// Solde SFF PME
				$soldeSFFPME += $virementEmprunteur[$date]['montant_unilend']-$virementUnilend[$date]['montant']+$commission;
				
			
				
				// Solde Admin. Fiscale
				$soldeAdminFiscal += $retenuesFiscales-$virementEtat[$date]['montant'];
				
				////////////////////////////
				/// add regul partie etat fiscal ///
				
				$soldeAdminFiscal += $regulCom[$date]['montant_unilend'];
				
				
				///////////////////////////
				
				// somme capital preteurs par jour
				$capitalPreteur = $echangeDate['capital'];
				$capitalPreteur = ($capitalPreteur/100);
				
				// somme net net preteurs par jour
				$interetNetPreteur = ($echangeDate['interets']/100)-$retenuesFiscales;
				
				// Montant preteur
				$montantPreteur = ($interetNetPreteur+$capitalPreteur);

				// ecart Mouv Internes
				$ecartMouvInternes = ($rembEmprunteur[$date]['montant'])-$commission-$retenuesFiscales-$capitalPreteur-$interetNetPreteur;
				
				// solde bids validés
				//$octroi_pret = $loans->sumLoansbyDay($date);
				$octroi_pret = (str_replace('-','',$virementEmprunteur[$date]['montant'])+$virementEmprunteur[$date]['montant_unilend']);
				
				
				// Virements ok (fichier virements)
				$virementsOK = $virements->sumVirementsbyDay($date,'status > 0');
				
				//dont sffpme virements (argent gagné a donner a sffpme)
				$virementsAttente = $virementUnilend[$date]['montant'];
				
				// Administration Fiscale
				$adminFiscalVir = $virementEtat[$date]['montant'];
				
				
				// prelevements
				$prelevPonctuel = $prelevements->sum('LEFT(added_xml,10) = "'.$date.'" AND status > 0');
				
				if($listPrel[$date] != false)
				{
					$sommePrelev = $prelevPonctuel+$listPrel[$date];
					//echo $prelevPonctuel .'<br>';
				}
				else $sommePrelev = $prelevPonctuel;
				
				$sommePrelev = $sommePrelev/100;
				
				// additions //

				$totalAlimCB += $alimCB[$date]['montant'];
				$totalAlimVirement += $alimVirement[$date]['montant'];
				$totalAlimPrelevement += $alimPrelevement[$date]['montant'];
				$totalRembEmprunteur += $rembEmprunteur[$date]['montant'];
				$totalVirementEmprunteur += str_replace('-','',$virementEmprunteur[$date]['montant']);
				$totalVirementCommissionUnilendEmprunteur += $virementEmprunteur[$date]['montant_unilend'];
				
				
				$totalCommission += $commission;
				
				
				$totalPrelevements_obligatoires += $prelevements_obligatoires;
				$totalRetenues_source += $retenues_source;
				$totalCsg += $csg;
				$totalPrelevements_sociaux += $prelevements_sociaux;
				$totalContributions_additionnelles += $contributions_additionnelles;
				$totalPrelevements_solidarite += $prelevements_solidarite;
				$totalCrds += $crds;
				
				$totalRetraitPreteur += $retraitPreteur[$date]['montant'];
				$totalSommeMouvements += $sommeMouvements;
				$totalNewsoldeDeLaVeille = $newsoldeDeLaVeille; // Solde théorique
				$totalNewSoldeReel = $newSoldeReel;
				$totalEcartSoldes = $ecartSoldes;
				
				
				
				
				// Solde SFF PME
				$totalSoldeSFFPME = $soldeSFFPME;
				// Solde Admin. Fiscale
				$totalSoldeAdminFiscal = $soldeAdminFiscal;
				
				$totalOctroi_pret += $octroi_pret;
				$totalCapitalPreteur += $capitalPreteur;
				$totalInteretNetPreteur += $interetNetPreteur;
				
				$totalEcartMouvInternes += $ecartMouvInternes;
				
				$totalVirementsOK += $virementsOK;
				
				// dont sff pme
				$totalVirementsAttente += $virementsAttente;
				
				$totaladdsommePrelev += $sommePrelev;
				
				$totalAdminFiscalVir += $adminFiscalVir;
				
				
				
				$tableau .= '
				<tr>
					<td class="dates">'.(strlen($key)<2?'0':'').$key.'/'.$lemoisLannee2.'</td>
                    <td class="right">'.number_format($alimCB[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($alimVirement[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($alimPrelevement[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($rembEmprunteur[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format(str_replace('-','',$virementEmprunteur[$date]['montant']), 2, ',', ' ').'</td>
                    <td class="right">'.number_format($virementEmprunteur[$date]['montant_unilend'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($commission, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_obligatoires, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($retenues_source, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($csg, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_sociaux, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($contributions_additionnelles, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_solidarite, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($crds, 2, ',', ' ').'</td>

                    <td class="right">'.number_format(str_replace('-','',$retraitPreteur[$date]['montant']), 2, ',', ' ').'</td>
                    <td class="right">'.number_format($sommeMouvements, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($soldeTheorique, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($leSoldeReel, 2, ',', ' ').'</td>
                    <td class="right">'.number_format(round($ecartSoldes,2), 2, ',', ' ').'</td>
					<td class="right">'.number_format($soldeSFFPME, 2, ',', ' ').'</td>
					<td class="right">'.number_format($soldeAdminFiscal, 2, ',', ' ').'</td>
                   	
                    <td class="right">'.number_format($octroi_pret, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($capitalPreteur, 2, ',', ' ').'</td>
                   	<td class="right">'.number_format($interetNetPreteur, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($ecartMouvInternes, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($virementsOK, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($virementsAttente, 2, ',', ' ').'</td>
					<td class="right">'.number_format($adminFiscalVir, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($sommePrelev, 2, ',', ' ').'</td>
				</tr>';
				
				}
				else
				{
				$tableau .= '
                <tr>
                    <td class="dates">'.(strlen($key)<2?'0':'').$key.'/'.$lemoisLannee2.'</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
					<td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
                </tr>';
                	
				}
			}
			
			$tableau .= '
            <tr>
				<td colspan="30">&nbsp;</td>
			</tr>
            <tr>
				<th>Total mois</th>
                <th class="right">'.number_format($totalAlimCB, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalAlimVirement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalAlimPrelevement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalRembEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementCommissionUnilendEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCommission, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_obligatoires, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalRetenues_source, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCsg, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_sociaux, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalContributions_additionnelles, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_solidarite, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCrds, 2, ',', ' ').'</th>
                <th class="right">'.number_format(str_replace('-','',$totalRetraitPreteur), 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalSommeMouvements, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalNewsoldeDeLaVeille, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalNewSoldeReel, 2, ',', ' ').'</th>
                <th class="right">'.number_format(round($totalEcartSoldes,2), 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalSoldeSFFPME, 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalSoldeAdminFiscal, 2, ',', ' ').'</th>
				
                <th class="right">'.number_format($totalOctroi_pret, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCapitalPreteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalInteretNetPreteur, 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalEcartMouvInternes, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementsOK, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementsAttente, 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalAdminFiscalVir, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totaladdsommePrelev, 2, ',', ' ').'</th>
			</tr>
		</table>';
		
		$table[1]['name'] = 'totalAlimCB';
		$table[1]['val'] = $totalAlimCB;
		$table[2]['name'] = 'totalAlimVirement';
		$table[2]['val'] = $totalAlimVirement;
		$table[3]['name'] = 'totalAlimPrelevement';
		$table[3]['val'] = $totalAlimPrelevement;
		$table[4]['name'] = 'totalRembEmprunteur';
		$table[4]['val'] = $totalRembEmprunteur;
		$table[5]['name'] = 'totalVirementEmprunteur';
		$table[5]['val'] = $totalVirementEmprunteur;
		$table[6]['name'] = 'totalVirementCommissionUnilendEmprunteur';
		$table[6]['val'] = $totalVirementCommissionUnilendEmprunteur;
		$table[7]['name'] = 'totalCommission';
		$table[7]['val'] = $totalCommission;
		
		$table[8]['name'] = 'totalPrelevements_obligatoires';
		$table[8]['val'] = $totalPrelevements_obligatoires;
		$table[9]['name'] = 'totalRetenues_source';
		$table[9]['val'] = $totalRetenues_source;
		$table[10]['name'] = 'totalCsg';
		$table[10]['val'] = $totalCsg;
		$table[11]['name'] = 'totalPrelevements_sociaux';
		$table[11]['val'] = $totalPrelevements_sociaux;
		$table[12]['name'] = 'totalContributions_additionnelles';
		$table[12]['val'] = $totalContributions_additionnelles;
		$table[13]['name'] = 'totalPrelevements_solidarite';
		$table[13]['val'] = $totalPrelevements_solidarite;
		$table[14]['name'] = 'totalCrds';
		$table[14]['val'] = $totalCrds;
		
		$table[15]['name'] = 'totalRetraitPreteur';
		$table[15]['val'] = $totalRetraitPreteur;
		$table[16]['name'] = 'totalSommeMouvements';
		$table[16]['val'] = $totalSommeMouvements;
		$table[17]['name'] = 'totalNewsoldeDeLaVeille';
		//$table[17]['val'] = $totalNewsoldeDeLaVeille-$soldeDeLaVeille;
		$table[17]['val'] = $totalNewsoldeDeLaVeille;
		$table[18]['name'] = 'totalNewSoldeReel';
		//$table[18]['val'] = $totalNewSoldeReel-$soldeReel_old;
		$table[18]['val'] = $totalNewSoldeReel;
		$table[19]['name'] = 'totalEcartSoldes';
		$table[19]['val'] = $totalEcartSoldes;
		
		$table[20]['name'] = 'totalOctroi_pret';
		$table[20]['val'] = $totalOctroi_pret;
		
		$table[21]['name'] = 'totalCapitalPreteur';
		$table[21]['val'] = $totalCapitalPreteur;
		$table[22]['name'] = 'totalInteretNetPreteur';
		$table[22]['val'] = $totalInteretNetPreteur;
		$table[23]['name'] = 'totalEcartMouvInternes';
		$table[23]['val'] = $totalEcartMouvInternes;
		
		$table[24]['name'] = 'totalVirementsOK';
		$table[24]['val'] = $totalVirementsOK;
		$table[25]['name'] = 'totalVirementsAttente';
		$table[25]['val'] = $totalVirementsAttente;
		$table[26]['name'] = 'totaladdsommePrelev';
		$table[26]['val'] = $totaladdsommePrelev;
		
		// Solde SFF PME
		$table[27]['name'] = 'totalSoldeSFFPME';
		$table[27]['val'] = $totalSoldeSFFPME;
		//$table[27]['val'] = $totalSoldeSFFPME-$soldeSFFPME_old;
		
		
		
			
			
		
		// Solde Admin. Fiscale
		$table[28]['name'] = 'totalSoldeAdminFiscal';
		//$table[28]['val'] = $totalSoldeAdminFiscal-$soldeAdminFiscal_old;
		$table[28]['val'] = $totalSoldeAdminFiscal;
		
		// Solde Admin. Fiscale (virement)
		$table[29]['name'] = 'totalAdminFiscalVir';
		$table[29]['val'] = $totalAdminFiscalVir;
		
		// create sav solde
		$etat_quotidien->createEtat_quotidient($table,$leMois,$lannee);
		
		// on recup toataux du mois de decembre de l'année precedente
		$oldDate = mktime(0,0,0,12,$jour,$lannee-1); 
		$oldDate = date('Y-m',$oldDate);
		$etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);
		
		
		
		if($etat_quotidienOld != false)
		{
			$soldeDeLaVeille = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
			$soldeReel = $etat_quotidienOld['totalNewSoldeReel'];
			
			$soldeSFFPME_old = $etat_quotidienOld['totalSoldeSFFPME'];
			
			$soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
		}
		else
		{
			// Solde theorique
			$soldeDeLaVeille = 0;

			// solde reel
			$soldeReel = 0;
			
			$soldeSFFPME_old = 0;
			
			$soldeAdminFiscal_old = 0;
			
		}
		
		$newsoldeDeLaVeille = $soldeDeLaVeille;
		$newSoldeReel = $soldeReel;
		
		$soldeSFFPME = $soldeSFFPME_old;
			
		$soldeAdminFiscal = $soldeAdminFiscal_old;
		
		// ecart
		$oldecart = $soldeDeLaVeille-$soldeReel;
		
		$tableau .= '
		<table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
			
            <tr>
				<th colspan="30" style="font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">&nbsp;</th>
			</tr>
            <tr>
				<th colspan="30" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
			</tr>
			<tr>
				<th rowspan="2">'.$lannee.'</th>
				<th colspan="3">Chargements compte prêteurs</th>
				<th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="5">Soldes</th>
                <th colspan="4">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Prélèvements</th>

			</tr>
			<tr>
				
				<td class="center">Carte<br />bancaire</td>        
				<td class="center">Virement</td>
				<td class="center">Prélèvement</td>
				<td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements<br />sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart<br />global</td>
				<td class="center">Solde<br />SFF PME</td>
				<td class="center">Solde Admin.<br>Fiscale</td>
                
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
                <td class="center">Ecart<br />fiscal</td>
                
                <td class="center">Fichier<br />virements</td>
                <td class="center">Dont<br />SFF PME</td>
				<td class="center">Administration<br />Fiscale</td>
                <td class="center">Fichier<br />prélèvements</td>
			</tr>
			<tr>
				<td colspan="17">Début d\'année</td>
                <td class="right">'.number_format($soldeDeLaVeille, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeReel, 2, ',', ' ').'</td>
                <td class="right">'.number_format($oldecart, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeSFFPME_old, 2, ',', ' ').'</td>
				<td class="right">'.number_format($soldeAdminFiscal_old, 2, ',', ' ').'</td>
				
				<td colspan="8">&nbsp;</td>
			</tr>';
			
			$sommetotalAlimCB = 0;
			$sommetotalAlimVirement = 0;
			$sommetotalAlimPrelevement = 0;
			$sommetotalRembEmprunteur = 0;
			$sommetotalVirementEmprunteur = 0;
			$sommetotalVirementCommissionUnilendEmprunteur = 0;
			$sommetotalCommission = 0;
			
			// Retenues fiscales
			$sommetotalPrelevements_obligatoires = 0;
			$sommetotalRetenues_source = 0;
			$sommetotalCsg = 0;
			$sommetotalPrelevements_sociaux = 0;
			$sommetotalContributions_additionnelles = 0;
			$sommetotalPrelevements_solidarite = 0;
			$sommetotalCrds = 0;
			
			// Remboursements aux prêteurs
			$sommetotalRetraitPreteur = 0;
			
			$sommetotalSommeMouvements = 0;
			
			// Soldes
			/*$sommetotalNewsoldeDeLaVeille = $newsoldeDeLaVeille;
			$sommetotalNewSoldeReel = $soldeReel;
			$sommetotalEcartSoldes = $oldecart;
			$sommetotalSoldeSFFPME = $soldeSFFPME;
			$sommetotalSoldeAdminFiscal = $soldeAdminFiscal;*/
			
			/*$sommetotalNewsoldeDeLaVeille = $totalNewsoldeDeLaVeille;
			$sommetotalNewSoldeReel = $totalNewSoldeReel;
			$sommetotalEcartSoldes = $totalEcartSoldes;
			$sommetotalSoldeSFFPME = $totalSoldeSFFPME;
			$sommetotalSoldeAdminFiscal = $totalSoldeAdminFiscal;*/
			
			$sommetotalNewsoldeDeLaVeille = 0;
			$sommetotalNewSoldeReel = 0;
			$sommetotalEcartSoldes = 0;
			$sommetotalSoldeSFFPME = 0;
			$sommetotalSoldeAdminFiscal = 0;
			
			// Mouvements internes
			$sommetotalOctroi_pret = 0;
			$sommetotalCapitalPreteur = 0;
			$sommetotalInteretNetPreteur = 0;
			$sommetotalEcartMouvInternes = 0;
			
			// Virements
			$sommetotalVirementsOK = 0;
			$sommetotalVirementsAttente = 0;
			$sommetotalAdminFiscalVir = 0;	
			
			// Prélèvements
			$sommetotaladdsommePrelev = 0;
			
			
			
			for($i=1;$i<=12;$i++)
			{
				
				
				if(strlen($i)<2)$numMois = '0'.$i;
				else $numMois = $i;
				
				$lemois = $etat_quotidien->getTotauxbyMonth($lannee.'-'.$numMois);
				
				$sommetotalAlimCB += $lemois['totalAlimCB'];
				$sommetotalAlimVirement += $lemois['totalAlimVirement'];
				$sommetotalAlimPrelevement += $lemois['totalAlimPrelevement'];
				$sommetotalRembEmprunteur += $lemois['totalRembEmprunteur'];
				$sommetotalVirementEmprunteur += $lemois['totalVirementEmprunteur'];
				$sommetotalVirementCommissionUnilendEmprunteur += $lemois['totalVirementCommissionUnilendEmprunteur'];
				$sommetotalCommission += $lemois['totalCommission'];
				
				// Retenues fiscales
				$sommetotalPrelevements_obligatoires += $lemois['totalPrelevements_obligatoires'];
				$sommetotalRetenues_source += $lemois['totalRetenues_source'];
				$sommetotalCsg += $lemois['totalCsg'];
				$sommetotalPrelevements_sociaux += $lemois['totalPrelevements_sociaux'];
				$sommetotalContributions_additionnelles += $lemois['totalContributions_additionnelles'];
				$sommetotalPrelevements_solidarite += $lemois['totalPrelevements_solidarite'];
				$sommetotalCrds += $lemois['totalCrds'];
				
				// Remboursements aux prêteurs
				$sommetotalRetraitPreteur += $lemois['totalRetraitPreteur'];
				
				$sommetotalSommeMouvements += $lemois['totalSommeMouvements'];
				
				// Soldes
				if($lemois != false)
				{
				$sommetotalNewsoldeDeLaVeille = $lemois['totalNewsoldeDeLaVeille'];
				$sommetotalNewSoldeReel = $lemois['totalNewSoldeReel'];
				$sommetotalEcartSoldes = $lemois['totalEcartSoldes'];
				$sommetotalSoldeSFFPME = $lemois['totalSoldeSFFPME'];
				$sommetotalSoldeAdminFiscal = $lemois['totalSoldeAdminFiscal'];
				}
				
				// Mouvements internes
				$sommetotalOctroi_pret += $lemois['totalOctroi_pret'];
				$sommetotalCapitalPreteur += $lemois['totalCapitalPreteur'];
				$sommetotalInteretNetPreteur += $lemois['totalInteretNetPreteur'];
				$sommetotalEcartMouvInternes += $lemois['totalEcartMouvInternes'];
				
				// Virements
				$sommetotalVirementsOK += $lemois['totalVirementsOK'];
				$sommetotalVirementsAttente += $lemois['totalVirementsAttente'];
				$sommetotalAdminFiscalVir += $lemois['totalAdminFiscalVir'];
				
				// Prélèvements
				$sommetotaladdsommePrelev += $lemois['totaladdsommePrelev'];
				
				


				$tableau .= '
                <tr>
                	<th>'.$this->dates->tableauMois['fr'][$i].'</th>';
                    
					if($lemois != false)
					{
						$tableau .= '
						<td class="right">'.number_format($lemois['totalAlimCB'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAlimVirement'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAlimPrelevement'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalRembEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementCommissionUnilendEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCommission'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_obligatoires'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalRetenues_source'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCsg'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_sociaux'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalContributions_additionnelles'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_solidarite'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCrds'], 2, ',', ' ').'</td>
						<td class="right">'.number_format(str_replace('-','',$lemois['totalRetraitPreteur']), 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalSommeMouvements'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalNewsoldeDeLaVeille'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalNewSoldeReel'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalEcartSoldes'], 2, ',', ' ').'</td>
						
						<td class="right">'.number_format($lemois['totalSoldeSFFPME'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalSoldeAdminFiscal'], 2, ',', ' ').'</td>
												
						<td class="right">'.number_format($lemois['totalOctroi_pret'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCapitalPreteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalInteretNetPreteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalEcartMouvInternes'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementsOK'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementsAttente'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAdminFiscalVir'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totaladdsommePrelev'], 2, ',', ' ').'</td>';

					}
					else
					{
						$tableau .= '
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						';
					}
					
				$tableau .= '</tr>';
				
			}
			
            $tableau .= '
            <tr>
				<th>Total année</th>
                
				<th class="right">'.number_format($sommetotalAlimCB, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalAlimVirement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalAlimPrelevement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalRembEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementCommissionUnilendEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCommission, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_obligatoires, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalRetenues_source, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCsg, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_sociaux, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalContributions_additionnelles, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_solidarite, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCrds, 2, ',', ' ').'</th>
                <th class="right">'.number_format(str_replace('-','',$sommetotalRetraitPreteur), 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalSommeMouvements, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalNewsoldeDeLaVeille, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalNewSoldeReel, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalEcartSoldes, 2, ',', ' ').'</th>
				<th class="right">'.number_format($sommetotalSoldeSFFPME, 2, ',', ' ').'</th>
				<th class="right">'.number_format($sommetotalSoldeAdminFiscal, 2, ',', ' ').'</th>
				
                <th class="right">'.number_format($sommetotalOctroi_pret, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCapitalPreteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalInteretNetPreteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalEcartMouvInternes, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementsOK, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementsAttente, 2, ',', ' ').'</th>
				<th class="right">'.number_format($sommetotalAdminFiscalVir, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotaladdsommePrelev, 2, ',', ' ').'</th>
					
            </tr>
            
		</table>';
			
			
			
			
		
		echo utf8_decode($tableau);
		
		//die;
		
		$filename = 'Unilend_etat_'.date('Ymd');
		//$filename = 'Unilend_etat_'.$ladatedetest;
		//$filename = 'Unilend_etat_20140301';
		
		if($this->Config['env'] == 'prod')
		{
		
			$connection = ssh2_connect('ssh.reagi.com', 22);
			ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
			$sftp = ssh2_sftp($connection);
			$sftpStream = @fopen('ssh2.sftp://'.$sftp.'/home/sfpmei/emissions/etat_quotidien/'.$filename.'.xls', 'w');
			fwrite($sftpStream, $tableau);
			fclose($sftpStream);
		
		}
		
		
		file_put_contents ($this->path.'protected/sftp/etat_quotidien/'.$filename.'.xls',$tableau);
		//mail('d.courtier@equinoa.com','unilend '.$this->Config['env'].' cron','etat quotidien date : '.date('d/m/y H:i:s'));
		
		//die;
		
		//************************************//
		//*** ENVOI DU MAIL ETAT QUOTIDIEN ***//
		//************************************//
		
		// destinataire
		$this->settings->get('Adresse notifications','type');
		$destinataire = $this->settings->value;
		//$destinataire = 'd.courtier@equinoa.com';

		// Recuperation du modele de mail
		$this->mails_text->get('notification-etat-quotidien','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		
		// Attribution des données aux variables
		$sujetMail = $this->mails_text->subject;
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
		$this->email->attachFromString($tableau,$filename.'.xls');
		$this->email->addRecipient(trim($destinataire));
		
		//if($this->Config['env'] == 'prod')
		//$this->email->addRecipient('emmanuel.perezduarte@unilend.fr');
	
		$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
		$this->email->setHTMLBody($texteMail);
		Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
		// fin mail
		
	}
	
	// 1 fois pr jour a  1h du matin (old n'est plus en place)
	function _etat_quotidien_old()
	{
		$jour = date('d');
		
		// si on veut mettre a jour une date on met le jour ici mais attention ca va sauvegarder enbdd et sur l'etat quotidien fait ce matin a 1h du mat
		//$jour = 1;
		if($jour == 1)
		{
			// On recup le nombre de jour dans le mois
			$mois = mktime( 0, 0, 0, date('m')-1, 1,date('Y')); 
			$nbJours = date("t",$mois);
			
			$leMois = date('m',$mois);
			$lannee = date('Y',$mois);
			$leJour = $nbJours;
			
			// affiche les données avant cette date
			$InfeA = mktime( 0, 0, 0, 1, date('m'),date('Y')); 
			
			$lanneeLemois = $lannee.'-'.$leMois;
			
			// affichage de la date du fichier
			$laDate = $jour.'-'.date('m').'-'.date('Y');
			
			$lemoisLannee2 = $leMois.'/'.$lannee;
		}
		else
		{
			// On recup le nombre de jour dans le mois
			$mois = mktime( 0, 0, 0, date('m'), 1,date('Y')); 
			$nbJours = date("t",$mois);
			
			$leMois = date('m');
			$lannee = date('Y');
			$leJour = $nbJours;
			
			$InfeA = mktime( 0, 0, 0, date('m'), date('d'),date('Y')); 
			
			$lanneeLemois = date('Y-m');
			
			$laDate = date('d-m-Y');
			
			$lemoisLannee2 = date('m/Y');
		}
		
		// chargement des datas
		$transac = $this->loadData('transactions');
		$echeanciers = $this->loadData('echeanciers');
		$loans = $this->loadData('loans');
		$virements = $this->loadData('virements');
		$prelevements = $this->loadData('prelevements');
		$etat_quotidien = $this->loadData('etat_quotidien');
		$bank_unilend = $this->loadData('bank_unilend');
		
		// alimentations CB
		$alimCB = $transac->sumByday(3,$leMois,$lannee);
		
		
		
		// 2 : alimentations virements
		$alimVirement = $transac->sumByday(4,$leMois,$lannee);
		
		// 7 : alimentations prelevements
		$alimPrelevement = $transac->sumByday(7,$leMois,$lannee);
		
		// 6 : remb Emprunteur (prelevement)
		$rembEmprunteur = $transac->sumByday(6,$leMois,$lannee);
		
		// 9 : virement emprunteur (octroi prêt : montant | commissions octoi pret : unilend_montant)
		$virementEmprunteur = $transac->sumByday(9,$leMois,$lannee);
		
		// 8 : retrait preteur
		$retraitPreteur = $transac->sumByday(8,$leMois,$lannee);
		
		$listDates = array();
		for($i=1;$i<=$nbJours;$i++)
		{
			$listDates[$i] = $lanneeLemois.'-'.(strlen($i)<2?'0':'').$i;
		}
		
		// recup des prelevements permanent
		
		$listPrel = array();
		foreach($prelevements->select('type_prelevement = 1 AND status > 0 AND type = 1') as $prelev)
		{
			$addedXml = strtotime($prelev['added_xml']);
			$added = strtotime($prelev['added']);
			
			$dateaddedXml = date('Y-m',$addedXml);
			$date = date('Y-m',$added);
			$i = 1;
			
			// on enregistre dans la table la premier prelevement
			$listPrel[date('Y-m-d',$added)] += $prelev['montant'];
			
			// tant que la date de creation n'est pas egale on rajoute les mois entre
			while($date != $dateaddedXml)
			{
				$newdate = mktime(0,0,0,date('m',$added)+$i,date('d',$addedXml),date('Y',$added)); 
				
				$date = date('Y-m',$newdate);
				$added = date('Y-m-d',$newdate).' 00:00:00';
				
				$listPrel[date('Y-m-d',$newdate)] += $prelev['montant'];
				
				$i++;	
			}
			
			
		}
		
		// on recup toataux du mois dernier
		$oldDate = mktime(0,0,0,$leMois-1,$jour,$lannee); 
		$oldDate = date('Y-m',$oldDate);
		$etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);
		
		if($etat_quotidienOld != false)
		{
			$soldeDeLaVeille = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
			$soldeReel = $etat_quotidienOld['totalNewSoldeReel'];
			
		}
		else
		{
			// Solde theorique
			$soldeDeLaVeille = 0;

			// solde reel
			$soldeReel = 0;
			
		}
		
		$newsoldeDeLaVeille = $soldeDeLaVeille;
		$newSoldeReel = $soldeReel;
		
		// ecart
		$oldecart = $soldeDeLaVeille-$soldeReel;
		
		
		// -- totaux -- //
		$totalAlimCB = 0;
		$totalAlimVirement = 0;
		$totalAlimPrelevement = 0;
		$totalRembEmprunteur = 0;
		$totalVirementEmprunteur = 0;
		$totalVirementCommissionUnilendEmprunteur = 0;
		$totalCommission = 0;
		
		// Retenues fiscales
		$totalPrelevements_obligatoires = 0;
		$totalRetenues_source = 0;
		$totalCsg = 0;
		$totalPrelevements_sociaux = 0;
		$totalContributions_additionnelles = 0;
		$totalPrelevements_solidarite = 0;
		$totalCrds = 0;
		
		$totalRetraitPreteur = 0;
		$totalSommeMouvements = 0;
		
		$totalNewSoldeReel = 0;
		
		$totalEcartSoldes = 0;
		// -- fin totaux -- //
		
	/*	header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=etat_quotidien.xls");*/
		
		$tableau = '
		<style>
			table th,table td{width:80px;height:20px;border:1px solid black;}
			table td.dates{text-align:center;}
			.right{text-align:right;}
			.center{text-align:center;}
			.boder-top{border-top:1px solid black;}
			.boder-bottom{border-bottom:1px solid black;}
			.boder-left{border-left:1px solid black;}
			.boder-right{border-right:1px solid black;}
		</style>
        
		<table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
			<tr>
				<th colspan="27" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
			</tr>
			<tr>
				<th rowspan="2">'.$laDate.'</th>
				<th colspan="3">Chargements compte prêteurs</th>
				<th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="3">Soldes</th>
                <th colspan="4">Mouvements internes</th>
                <th colspan="2">Virements</th>
                <th>Prélèvements</th>

			</tr>
			<tr>
				
				<td class="center">Carte bancaire</td>        
				<td class="center">Virement</td>
				<td class="center">Prélèvement</td>
				<td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart global</td>
                
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
                <td class="center">Ecart fiscal</td>
                
                <td class="center">Fichier virements</td>
                <td class="center">Virements en<br />attente</td>
                <td class="center">Fichier prélèvements</td>
			</tr>
			<tr>
				<td colspan="17">Début du mois</td>
                <td class="right">'.number_format($soldeDeLaVeille, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeReel, 2, ',', ' ').'</td>
                <td class="right">'.number_format($oldecart, 2, ',', ' ').'</td>
                <td colspan="4">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
			</tr>';
            
           	
			
			foreach($listDates as $key => $date)
			{

				if(strtotime($date.' 00:00:00') < $InfeA)
				{
					
				// sommes des echeance par jour
				$echangeDate = $echeanciers->getEcheanceByDayAll($date,'1');
				
				// commission sommes remboursé 
				$commission = $echangeDate['commission'];
				$commission = ($commission/100);
				
				//prelevements_obligatoires
				$prelevements_obligatoires = $echangeDate['prelevements_obligatoires'];
				//retenues_source
				$retenues_source = $echangeDate['retenues_source'];
				//csg
				$csg = $echangeDate['csg'];
				//prelevements_sociaux 	
				$prelevements_sociaux = $echangeDate['prelevements_sociaux'];
				//contributions_additionnelles
				$contributions_additionnelles = $echangeDate['contributions_additionnelles'];
				//prelevements_solidarite
				$prelevements_solidarite = $echangeDate['prelevements_solidarite'];
				//crds
				$crds = $echangeDate['crds'];
				
				// total Mouvements
				$entrees = ($alimCB[$date]['montant']+$alimVirement[$date]['montant']+$alimPrelevement[$date]['montant']);
				$sorties = (str_replace('-','',$virementEmprunteur[$date]['montant'])+$virementEmprunteur[$date]['montant_unilend']+$commission+$prelevements_obligatoires+$retenues_source+$csg+$prelevements_sociaux+$contributions_additionnelles+$prelevements_solidarite+$crds+str_replace('-','',$retraitPreteur[$date]['montant']));
				
				$sommeMouvements = ($entrees-$sorties);
				
				// solde De La Veille (solde theorique)
				
				// addition du solde theorique et des mouvements
				$newsoldeDeLaVeille += $sommeMouvements;
				
				// Solde reel de base
				$soldeReel += $transac->getSoldeReelDay($date);
				
				// on rajoute les virements des emprunteurs
				$soldeReelUnilend = $transac->getSoldeReelUnilendDay($date);
				$soldeReelUnilend = $soldeReelUnilend;
				
				$soldeReel += $soldeReelUnilend;
				
				// on addition les solde precedant
				$newSoldeReel = $soldeReel;
				
				// On recupere le solde dans une autre variable
				$soldeTheorique = $newsoldeDeLaVeille;
				
				
				$leSoldeReel = $newSoldeReel;
				
				if(strtotime($date.' 00:00:00') > time())
				{
					$soldeTheorique = 0;
					$leSoldeReel = 0;
				}
				
				// ecart global soldes
				$ecartSoldes = ($soldeTheorique-$leSoldeReel);
				
				// somme capital preteurs par jour
				$capitalPreteur = $echangeDate['capital'];
				$capitalPreteur = ($capitalPreteur/100);
				
				// somme net net preteurs par jour
				$interetNetPreteur = ($echangeDate['interets']/100)-($echangeDate['prelevements_obligatoires']+$echangeDate['retenues_source']+$echangeDate['csg']+$echangeDate['prelevements_sociaux']+$echangeDate['contributions_additionnelles']+$echangeDate['prelevements_solidarite']+$echangeDate['crds']);
				
				// Montant preteur
				$montantPreteur = ($interetNetPreteur+$capitalPreteur);
				
				// Retenues Fiscales
				$retenuesFiscales = ($prelevements_obligatoires+$retenues_source+$csg+$prelevements_sociaux+$contributions_additionnelles+$prelevements_solidarite+$crds);
				
				// ecart Mouv Internes
				$ecartMouvInternes = (($rembEmprunteur[$date]['montant']/100)+$commission)-$retenuesFiscales-$capitalPreteur-$interetNetPreteur;
				
				// solde bids validés
				$octroi_pret = $loans->sumLoansbyDay($date);
				
				// Virements ok (fichier virements)
				$virementsOK = $virements->sumVirementsbyDay($date,'status > 0');
				
				
				// Virements en attente
				//$virementsAttente = $virements->sumVirementsbyDay($date,'status = 0');
				
				$virementsAttente = $bank_unilend->sum($date);
				
				
				
				
				// prelevements
				$prelevPonctuel = $prelevements->sum('LEFT(added_xml,10) = "'.$date.'" AND status > 0');
				
				if($listPrel[$date] != false)
				{
					$sommePrelev = $prelevPonctuel+$listPrel[$date];
					//echo $prelevPonctuel .'<br>';
				}
				else $sommePrelev = $prelevPonctuel;
				
				$sommePrelev = $sommePrelev/100;
				
				// additions //

				$totalAlimCB += $alimCB[$date]['montant'];
				$totalAlimVirement += $alimVirement[$date]['montant'];
				$totalAlimPrelevement += $alimPrelevement[$date]['montant'];
				$totalRembEmprunteur += $rembEmprunteur[$date]['montant'];
				$totalVirementEmprunteur += str_replace('-','',$virementEmprunteur[$date]['montant']);
				$totalVirementCommissionUnilendEmprunteur += $virementEmprunteur[$date]['montant_unilend'];
				
				
				$totalCommission += $commission;
				
				
				$totalPrelevements_obligatoires += $prelevements_obligatoires;
				$totalRetenues_source += $retenues_source;
				$totalCsg += $csg;
				$totalPrelevements_sociaux += $prelevements_sociaux;
				$totalContributions_additionnelles += $contributions_additionnelles;
				$totalPrelevements_solidarite += $prelevements_solidarite;
				$totalCrds += $crds;
				
				$totalRetraitPreteur += $retraitPreteur[$date]['montant'];
				$totalSommeMouvements += $sommeMouvements;
				$totalNewsoldeDeLaVeille = $newsoldeDeLaVeille; // Solde théorique
				$totalNewSoldeReel = $newSoldeReel;
				$totalEcartSoldes += $ecartSoldes;
			
				
				$totalOctroi_pret += $octroi_pret;
				$totalCapitalPreteur += $capitalPreteur;
				$totalInteretNetPreteur += $interetNetPreteur;
				
				$totalEcartMouvInternes += $ecartMouvInternes;
				
				$totalVirementsOK += $virementsOK;
				
				$totalVirementsAttente += $virementsAttente;
				
				$totaladdsommePrelev += $sommePrelev;
				
				$tableau .= '
				<tr>
					<td class="dates">'.(strlen($key)<2?'0':'').$key.'/'.$lemoisLannee2.'</td>
                    <td class="right">'.number_format($alimCB[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($alimVirement[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($alimPrelevement[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($rembEmprunteur[$date]['montant'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format(str_replace('-','',$virementEmprunteur[$date]['montant']), 2, ',', ' ').'</td>
                    <td class="right">'.number_format($virementEmprunteur[$date]['montant_unilend'], 2, ',', ' ').'</td>
                    <td class="right">'.number_format($commission, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_obligatoires, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($retenues_source, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($csg, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_sociaux, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($contributions_additionnelles, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($prelevements_solidarite, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($crds, 2, ',', ' ').'</td>

                    <td class="right">'.number_format(str_replace('-','',$retraitPreteur[$date]['montant']), 2, ',', ' ').'</td>
                    <td class="right">'.number_format($sommeMouvements, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($soldeTheorique, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($leSoldeReel, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($ecartSoldes, 2, ',', ' ').'</td>
                   	
                    <td class="right">'.number_format($octroi_pret, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($capitalPreteur, 2, ',', ' ').'</td>
                   	<td class="right">'.number_format($interetNetPreteur, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($ecartMouvInternes, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($virementsOK, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($virementsAttente, 2, ',', ' ').'</td>
                    <td class="right">'.number_format($sommePrelev, 2, ',', ' ').'</td>
				</tr>';
				
				}
				else
				{
				$tableau .= '
                <tr>
                    <td class="dates">'.(strlen($key)<2?'0':'').$key.'/'.$lemoisLannee2.'</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>';
                	
				}
			}
			
			$tableau .= '
            <tr>
				<td colspan="27">&nbsp;</td>
			</tr>
            <tr>
				<th>Total mois</th>
                <th class="right">'.number_format($totalAlimCB, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalAlimVirement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalAlimPrelevement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalRembEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementCommissionUnilendEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCommission, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_obligatoires, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalRetenues_source, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCsg, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_sociaux, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalContributions_additionnelles, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalPrelevements_solidarite, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCrds, 2, ',', ' ').'</th>
                <th class="right">'.number_format(str_replace('-','',$totalRetraitPreteur), 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalSommeMouvements, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalNewsoldeDeLaVeille, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalNewSoldeReel, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalEcartSoldes, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalOctroi_pret, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalCapitalPreteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalInteretNetPreteur, 2, ',', ' ').'</th>
				<th class="right">'.number_format($totalEcartMouvInternes, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementsOK, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totalVirementsAttente, 2, ',', ' ').'</th>
                <th class="right">'.number_format($totaladdsommePrelev, 2, ',', ' ').'</th>
			</tr>
		</table>';
		
		$table[1]['name'] = 'totalAlimCB';
		$table[1]['val'] = $totalAlimCB;
		$table[2]['name'] = 'totalAlimVirement';
		$table[2]['val'] = $totalAlimVirement;
		$table[3]['name'] = 'totalAlimPrelevement';
		$table[3]['val'] = $totalAlimPrelevement;
		$table[4]['name'] = 'totalRembEmprunteur';
		$table[4]['val'] = $totalRembEmprunteur;
		$table[5]['name'] = 'totalVirementEmprunteur';
		$table[5]['val'] = $totalVirementEmprunteur;
		$table[6]['name'] = 'totalVirementCommissionUnilendEmprunteur';
		$table[6]['val'] = $totalVirementCommissionUnilendEmprunteur;
		$table[7]['name'] = 'totalCommission';
		$table[7]['val'] = $totalCommission;
		
		$table[8]['name'] = 'totalPrelevements_obligatoires';
		$table[8]['val'] = $totalPrelevements_obligatoires;
		$table[9]['name'] = 'totalRetenues_source';
		$table[9]['val'] = $totalRetenues_source;
		$table[10]['name'] = 'totalCsg';
		$table[10]['val'] = $totalCsg;
		$table[11]['name'] = 'totalPrelevements_sociaux';
		$table[11]['val'] = $totalPrelevements_sociaux;
		$table[12]['name'] = 'totalContributions_additionnelles';
		$table[12]['val'] = $totalContributions_additionnelles;
		$table[13]['name'] = 'totalPrelevements_solidarite';
		$table[13]['val'] = $totalPrelevements_solidarite;
		$table[14]['name'] = 'totalCrds';
		$table[14]['val'] = $totalCrds;
		
		$table[15]['name'] = 'totalRetraitPreteur';
		$table[15]['val'] = $totalRetraitPreteur;
		$table[16]['name'] = 'totalSommeMouvements';
		$table[16]['val'] = $totalSommeMouvements;
		$table[17]['name'] = 'totalNewsoldeDeLaVeille';
		$table[17]['val'] = $totalNewsoldeDeLaVeille;
		$table[18]['name'] = 'totalNewSoldeReel';
		$table[18]['val'] = $totalNewSoldeReel;
		$table[19]['name'] = 'totalEcartSoldes';
		$table[19]['val'] = $totalEcartSoldes;
		
		$table[20]['name'] = 'totalOctroi_pret';
		$table[20]['val'] = $totalOctroi_pret;
		
		$table[21]['name'] = 'totalCapitalPreteur';
		$table[21]['val'] = $totalCapitalPreteur;
		$table[22]['name'] = 'totalInteretNetPreteur';
		$table[22]['val'] = $totalInteretNetPreteur;
		$table[23]['name'] = 'totalEcartMouvInternes';
		$table[23]['val'] = $totalEcartMouvInternes;
		
		$table[24]['name'] = 'totalVirementsOK';
		$table[24]['val'] = $totalVirementsOK;
		$table[25]['name'] = 'totalVirementsAttente';
		$table[25]['val'] = $totalVirementsAttente;
		$table[26]['name'] = 'totaladdsommePrelev';
		$table[26]['val'] = $totaladdsommePrelev;
	
		$etat_quotidien->createEtat_quotidient($table,$leMois,$lannee);
		
		// on recup toataux du mois de decembre de l'année precedente
		$oldDate = mktime(0,0,0,12,$jour,$lannee-1); 
		$oldDate = date('Y-m',$oldDate);
		$etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);
		
		if($etat_quotidienOld != false)
		{
			$soldeDeLaVeille = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
			$soldeReel = $etat_quotidienOld['totalNewSoldeReel'];
		}
		else
		{
			// Solde theorique
			$soldeDeLaVeille = 0;

			// solde reel
			$soldeReel = 0;
			
		}
		
		$newsoldeDeLaVeille = $soldeDeLaVeille;
		$newSoldeReel = $soldeReel;
		
		// ecart
		$oldecart = $soldeDeLaVeille-$soldeReel;
		
		$tableau .= '
		<table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
			
            <tr>
				<th colspan="27" style="font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">&nbsp;</th>
			</tr>
            <tr>
				<th colspan="27" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
			</tr>
			<tr>
				<th rowspan="2">'.$lannee.'</th>
				<th colspan="3">Chargements compte prêteurs</th>
				<th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="3">Soldes</th>
                <th colspan="4">Mouvements internes</th>
                <th colspan="2">Virements</th>
                <th>Prélèvements</th>

			</tr>
			<tr>
				
				<td class="center">Carte bancaire</td>        
				<td class="center">Virement</td>
				<td class="center">Prélèvement</td>
				<td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart global</td>
                
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
                <td class="center">Ecart fiscal</td>
                
                <td class="center">Fichier virements</td>
                <td class="center">Virements en<br />attente</td>
                <td class="center">Fichier prélèvements</td>
			</tr>
			<tr>
				<td colspan="17">Début d\'année</td>
                <td class="right">'.number_format($soldeDeLaVeille, 2, ',', ' ').'</td>
                <td class="right">'.number_format($soldeReel, 2, ',', ' ').'</td>
                <td class="right">'.number_format($oldecart, 2, ',', ' ').'</td>
                <td colspan="4">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
			</tr>';
			
			$sommetotalAlimCB = 0;
			$sommetotalAlimVirement = 0;
			$sommetotalAlimPrelevement = 0;
			$sommetotalRembEmprunteur = 0;
			$sommetotalVirementEmprunteur = 0;
			$sommetotalVirementCommissionUnilendEmprunteur = 0;
			$sommetotalCommission = 0;
			$sommetotalPrelevements_obligatoires = 0;
			$sommetotalRetenues_source = 0;
			$sommetotalCsg = 0;
			$sommetotalPrelevements_sociaux = 0;
			$sommetotalContributions_additionnelles = 0;
			$sommetotalPrelevements_solidarite = 0;
			$sommetotalCrds = 0;
			$sommetotalRetraitPreteur = 0;
			$sommetotalSommeMouvements = 0;
			$sommetotalNewsoldeDeLaVeille = $soldeDeLaVeille;
			$sommetotalNewSoldeReel = $soldeReel;
			$sommetotalEcartSoldes = 0;
			$sommetotalOctroi_pret = 0;
			$sommetotalCapitalPreteur = 0;
			$sommetotalInteretNetPreteur = 0;
			$sommetotalEcartMouvInternes = 0;
			$sommetotalVirementsOK = 0;
			$sommetotalVirementsAttente = 0;
			$sommetotaladdsommePrelev = 0;
			
			
			
			for($i=1;$i<=12;$i++)
			{
				
				if(strlen($i)<2)$numMois = '0'.$i;
				else $numMois = $i;
				
				$lemois = $etat_quotidien->getTotauxbyMonth($lannee.'-'.$numMois);
				
				$sommetotalAlimCB += $lemois['totalAlimCB'];
				$sommetotalAlimVirement += $lemois['totalAlimVirement'];
				$sommetotalAlimPrelevement += $lemois['totalAlimPrelevement'];
				$sommetotalRembEmprunteur += $lemois['totalRembEmprunteur'];
				$sommetotalVirementEmprunteur += $lemois['totalVirementEmprunteur'];
				$sommetotalVirementCommissionUnilendEmprunteur += $lemois['totalVirementCommissionUnilendEmprunteur'];
				$sommetotalCommission += $lemois['totalCommission'];
				$sommetotalPrelevements_obligatoires += $lemois['totalPrelevements_obligatoires'];
				$sommetotalRetenues_source += $lemois['totalRetenues_source'];
				$sommetotalCsg += $lemois['totalCsg'];
				$sommetotalPrelevements_sociaux += $lemois['totalPrelevements_sociaux'];
				$sommetotalContributions_additionnelles += $lemois['totalContributions_additionnelles'];
				$sommetotalPrelevements_solidarite += $lemois['totalPrelevements_solidarite'];
				$sommetotalCrds += $lemois['totalCrds'];
				$sommetotalRetraitPreteur += $lemois['totalRetraitPreteur'];
				$sommetotalSommeMouvements += $lemois['totalSommeMouvements'];
				$sommetotalNewsoldeDeLaVeille += $lemois['totalNewsoldeDeLaVeille'];
				$sommetotalNewSoldeReel += $lemois['totalNewSoldeReel'];
				$sommetotalEcartSoldes += $lemois['totalEcartSoldes'];
				$sommetotalOctroi_pret += $lemois['totalOctroi_pret'];
				$sommetotalCapitalPreteur += $lemois['totalCapitalPreteur'];
				$sommetotalInteretNetPreteur += $lemois['totalInteretNetPreteur'];
				$sommetotalEcartMouvInternes += $lemois['totalEcartMouvInternes'];
				$sommetotalVirementsOK += $lemois['totalVirementsOK'];
				$sommetotalVirementsAttente += $lemois['totalVirementsAttente'];
				$sommetotaladdsommePrelev += $lemois['totaladdsommePrelev'];

				$tableau .= '
                <tr>
                	<th>'.$this->dates->tableauMois['fr'][$i].'</th>';
                    
					if($lemois != false)
					{
						$tableau .= '
						<td class="right">'.number_format($lemois['totalAlimCB'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAlimVirement'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalAlimPrelevement'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalRembEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementCommissionUnilendEmprunteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCommission'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_obligatoires'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalRetenues_source'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCsg'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_sociaux'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalContributions_additionnelles'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalPrelevements_solidarite'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCrds'], 2, ',', ' ').'</td>
						<td class="right">'.number_format(str_replace('-','',$lemois['totalRetraitPreteur']), 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalSommeMouvements'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalNewsoldeDeLaVeille'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalNewSoldeReel'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalEcartSoldes'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalOctroi_pret'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalCapitalPreteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalInteretNetPreteur'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalEcartMouvInternes'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementsOK'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totalVirementsAttente'], 2, ',', ' ').'</td>
						<td class="right">'.number_format($lemois['totaladdsommePrelev'], 2, ',', ' ').'</td>';

					}
					else
					{
						$tableau .= '
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						';
					}
					
				$tableau .= '</tr>';
				
			}
			
            $tableau .= '
            <tr>
				<th>Total année</th>
                
				<th class="right">'.number_format($sommetotalAlimCB, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalAlimVirement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalAlimPrelevement, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalRembEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementCommissionUnilendEmprunteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCommission, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_obligatoires, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalRetenues_source, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCsg, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_sociaux, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalContributions_additionnelles, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalPrelevements_solidarite, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCrds, 2, ',', ' ').'</th>
                <th class="right">'.number_format(str_replace('-','',$sommetotalRetraitPreteur), 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalSommeMouvements, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalNewsoldeDeLaVeille, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalNewSoldeReel, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalEcartSoldes, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalOctroi_pret, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalCapitalPreteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalInteretNetPreteur, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalEcartMouvInternes, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementsOK, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotalVirementsAttente, 2, ',', ' ').'</th>
                <th class="right">'.number_format($sommetotaladdsommePrelev, 2, ',', ' ').'</th>
					
            </tr>
            
		</table>';
			
			
			
			
		
		echo $tableau;
		
		//$ladatefichier = mktime(0,0,0,$lemois,date('d')-1,$lannee); 
		
		$filename = 'unilend'.date('Ymd');
		
		if($this->Config['env'] == 'prod')
		{
		
			$connection = ssh2_connect('ssh.reagi.com', 22);
			ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
			$sftp = ssh2_sftp($connection);
			$sftpStream = @fopen('ssh2.sftp://'.$sftp.'/home/sfpmei/emissions/etat_quotidien/'.$filename.'.xls', 'w');
			fwrite($sftpStream, $tableau);
			fclose($sftpStream);
		
		}
		
		
		
		file_put_contents ($this->path.'protected/sftp/etat_quotidien/'.$filename.'.xls',$tableau);
		mail('d.courtier@equinoa.com','unilend '.$this->Config['env'].' cron','etat quotidien date : '.date('d/m/y H:i:s'));
		
		
		//************************************//
		//*** ENVOI DU MAIL ETAT QUOTIDIEN ***//
		//************************************//
		
		// destinataire
		$this->settings->get('Adresse notifications','type');
		$destinataire = $this->settings->value;
		//$destinataire = 'd.courtier@equinoa.com';

		// Recuperation du modele de mail
		$this->mails_text->get('notification-etat-quotidien','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		
		// Attribution des données aux variables
		$sujetMail = $this->mails_text->subject;
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
		$this->email->attachFromString($tableau,$filename.'.xls');
		$this->email->addRecipient(trim($destinataire));
	
		$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
		$this->email->setHTMLBody($texteMail);
		Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
		// fin mail
	}
	
	
	// check  le 1 er et le 15 du mois si y a un virement a faire  (1h du matin)
	function _retraitUnilend()
	{
		
		$jour = date('d');
		//$jour = 15;
		
		$datesVirements = array(1,15);
		
		if(in_array($jour,$datesVirements))
		{
			
			// chargement des datas
			$virements = $this->loadData('virements');
			$bank_unilend = $this->loadData('bank_unilend');
			$transactions = $this->loadData('transactions');
			
			// 3%+tva  + les retraits Unilend
			$comProjet = $bank_unilend->sumMontant('status IN(0,3) AND type IN(0,3) AND retrait_fiscale = 0');
			// com sur remb
			$comRemb = $bank_unilend->sumMontant('status = 1 AND type IN(1,2)');
			
			$etatRemb = $bank_unilend->sumMontantEtat('status = 1 AND type IN(2)');
			
			// On prend la com projet + la com sur les remb et on retire la partie pour l'etat
			echo $total = $comRemb+$comProjet-$etatRemb;
			
			if($total > 0)
			{
				// On enregistre la transaction
				$transactions->id_client = 0;
				$transactions->montant = $total;
				$transactions->id_langue = 'fr';
				$transactions->date_transaction = date('Y-m-d H:i:s');
				$transactions->status = '1';
				$transactions->etat = '1';
				$transactions->ip_client = $_SERVER['REMOTE_ADDR'];
				$transactions->type_transaction = 11; // virement Unilend (retrait)
				$transactions->transaction = 1; // transaction virtuelle
				$transactions->id_transaction = $transactions->create();
				
				// on créer le virement
				$virements->id_client = 0;
				$virements->id_project = 0;
				$virements->id_transaction = $transactions->id_transaction;
				$virements->montant = $total;
				$virements->motif = 'UNILEND_'.date('dmY');
				$virements->type = 4; // Unilend
				$virements->status = 0;
				$virements->id_virement = $virements->create();
				
				// on enregistre le mouvement
				$bank_unilend->id_transaction = $transactions->id_transaction;
				$bank_unilend->id_echeance_emprunteur = 0;
				$bank_unilend->id_project = 0;
				$bank_unilend->montant = '-'.$total;
				$bank_unilend->type = 3;
				$bank_unilend->status = 3;
				$bank_unilend->create();
			}
		}

	}
	
	// 1 fois par jour on check 12 jours avant si la demande de prelevement est parti (01:00:00)
	function _alertePrelevement()
	{
		// chargement des datas
		$prelevements = $this->loadData('prelevements');
		
		// today
		$today = time();	
		
		//$today = '2014-01-15 00:00:00';
		//$today = strtotime($today);
		
		// today +12j 
		$todayPlus12 = mktime(0,0,0, date("m",$today), date("d",$today)+15, date("Y",$today));
		
		$todayPlus12 = date('Y-m-d',$todayPlus12);
		
		// On recupere la liste des prelevements en cours preteur recurrent
		$lPrelevements = $prelevements->select('type = 2 AND type_prelevement = 1 AND status = 0 AND date_echeance_emprunteur <= "'.$todayPlus12.'"');
		
		
		if(count($lPrelevements)>0)
		{
			foreach($lPrelevements as $p)
			{
				// multiple recipients
				//$to  = 'aidan@example.com' . ', '; // note the comma
				//$to .= 'wez@example.com';
				$to  = 'unilend@equinoa.fr';
				
				// subject
				$subject = '[Alerte] ordre de prelevement';
				
				// message
				$message = '
				<html>
				<head>
				  <title>[Alerte] ordre de prelevement</title>
				</head>
				<body>
				  <p>Un ordre de prelevement doit partir ce soir</p>
				  <table>
					<tr>
					  <th>Id prelevement : </th><td>'.$p['id_prelevement'].'</td>
					</tr>
					<tr>
					  <th>Id client : </th><td>'.$p['id_client'].'</td>
					</tr>
					<tr>
					  <th>id project : </th><td>'.$p['id_project'].'</td>
					</tr>
					<tr>
					  <th>motif : </th><td>'.$p['motif'].'</td>
					</tr>
					<tr>
					  <th>montant : </th><td>'.($p['montant']/100).' euros</td>
					</tr>
					<tr>
					  <th>Date execution <br>de l\'ordre de virement : </th><td>'.$p['date_execution_demande_prelevement'].'</td>
					</tr>
					<tr>
					  <th>Date echeance Emprunteur : </th><td>'.$p['date_echeance_emprunteur'].'</td>
					</tr>
				  </table>
				</body>
				</html>
				';
				
				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				
				// Additional headers
				//$headers .= 'To: Damien <d.courtier@equinoa.com>, Kelly <kelly@example.com>' . "\r\n";
				$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
				$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
				
				// Mail it
				mail($to, $subject, $message, $headers);
			}
		}
		
	}
	
	// passe a 1h30 (pour decaler avec l'etat fiscal) du matin le 1er du mois
	function _echeances_par_mois(){
		// les echeances du mois passé
		$dateMoins1Mois = mktime (date("H"),date("i"),0,date("m")-1,date("d"),date("Y"));
		
		$dateMoins1Mois = date('Y-m',$dateMoins1Mois);
		
		$lpreteurs = $this->clients->selectPreteurs($dateMoins1Mois);
		
		$csv = "id_client;id_lender_account;type;exonere;id_project;id_loan;ordre;montant;capital;interets;prelevements_obligatoires;retenues_source;csg;prelevements_sociaux;contributions_additionnelles;prelevements_solidarite;crds;date_echeance;date_echeance_reel;status_remb_preteur;date_echeance_emprunteur;date_echeance_emprunteur_reel;\n";
		foreach($lpreteurs as $p)
		{
			for($i=0;$i<=22;$i++)
			{
				$csv .= str_replace('.',',',$p[$i]).";";
			}
			$csv .= "\n";
		}
		
		$filename = 'echeances_'.date('Ymd');
		
		file_put_contents ($this->path.'protected/sftp/etat_fiscal/'.$filename.'.csv',$csv);
		//die;
		
		// Enregistrement sur le sftp
		$connection = ssh2_connect('ssh.reagi.com', 22);
		ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
		$sftp = ssh2_sftp($connection);
		$sftpStream = @fopen('ssh2.sftp://'.$sftp.'/home/sfpmei/emissions/etat_fiscal/'.$filename.'.csv', 'w');
		fwrite($sftpStream, $csv);
		fclose($sftpStream);
		
		
		/*header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"echeances.csv\"");
		print(utf8_decode($csv)); 
		exit;*/
		
		die;
	}
	
	// passe a 1h du matin le 1er du mois
	function _etat_fiscal()
	{
		// chargement des datas
		$echeanciers = $this->loadData('echeanciers');
		$bank_unilend = $this->loadData('bank_unilend');
		$transactions = $this->loadData('transactions');

		// EQ-Acompte d'impôt sur le revenu
		$this->settings->get("EQ-Acompte d'impôt sur le revenu",'type');
		$prelevements_obligatoires = $this->settings->value*100;
		
		// EQ-Contribution additionnelle au Prélèvement Social
		$this->settings->get('EQ-Contribution additionnelle au Prélèvement Social','type');
		$txcontributions_additionnelles = $this->settings->value*100;
		
		// EQ-CRDS
		$this->settings->get('EQ-CRDS','type');
		$txcrds = $this->settings->value*100;
		
		// EQ-CSG
		$this->settings->get('EQ-CSG','type');
		$txcsg = $this->settings->value*100;
		
		// EQ-Prélèvement de Solidarité
		$this->settings->get('EQ-Prélèvement de Solidarité','type');
		$txprelevements_solidarite = $this->settings->value*100;
		
		// EQ-Prélèvement social
		$this->settings->get('EQ-Prélèvement social','type');
		$txprelevements_sociaux = $this->settings->value*100;
		
		// EQ-Retenue à la source
		$this->settings->get('EQ-Retenue à la source','type');
		$tauxRetenuSoucre = $this->settings->value*100;
		
		
		$jour = date("d");
		$mois = date("m");
		// test //////
		//$mois = 06;
		// fin test //
		$annee = date("Y");
		
		$dateDebutTime = mktime(0,0,0, $mois-1, 1, $annee);
		
		$dateDebutSql = date('Y-m-d',$dateDebutTime);
		$dateDebut = date('d/m/Y',$dateDebutTime);
		
		$dateFinTime = mktime(0,0,0, $mois, 0, $annee);
		
		$dateFinSql = date('Y-m-d',$dateFinTime);
		$dateFin = date('d/m/Y',$dateFinTime);
		
		
		
		//////////////////////////
		// Physique non exoneré //
		$PhysiqueNoExo = $echeanciers->getEcheanceBetweenDates($dateDebutSql,$dateFinSql,'0','1');
		$PhysiqueNoExoInte = ($PhysiqueNoExo['interets']/100);
		
		// prelevements pour physiques non exonéré
		$lesPrelevSurPhysiqueNoExo = $PhysiqueNoExo['prelevements_obligatoires'];
		
		
		////////////////////////
		
		//////////////////////
		// Physique exoneré //
		$PhysiqueExo = $echeanciers->getEcheanceBetweenDates($dateDebutSql,$dateFinSql,'1','1');
		$PhysiqueExoInte = ($PhysiqueExo['interets']/100);
		
		// prelevements pour physiques exonéré
		$lesPrelevSurPhysiqueExo = $PhysiqueExo['prelevements_obligatoires'];
		
		//////////////////////
		
		//////////////
		// Physique //
		$Physique = $echeanciers->getEcheanceBetweenDates($dateDebutSql,$dateFinSql,'','1');
		$PhysiqueInte = ($Physique['interets']/100);
		
		// prelevements pour physiques
		$lesPrelevSurPhysique = $Physique['prelevements_obligatoires'];
		

		$csg = $Physique['csg'];
		$prelevements_sociaux = $Physique['prelevements_sociaux'];
		$contributions_additionnelles = $Physique['contributions_additionnelles'];
		$prelevements_solidarite = $Physique['prelevements_solidarite'];
		$crds = $Physique['crds'];
		
		///////////////////////////////
		
		//////////////////////
		// personnes morale //
		$Morale = $echeanciers->getEcheanceBetweenDates($dateDebutSql,$dateFinSql,'0','2');
		$MoraleInte = ($Morale['interets']/100);
		
		// on recup les personnes morales et les personnes physique exonéré
		//$InteRetenuSoucre = $PhysiqueExoInte + $MoraleInte;
		$InteRetenuSoucre = $MoraleInte;
		
		//$prelevementRetenuSoucre = $PhysiqueExo['retenues_source'] + $Morale['retenues_source'];
		$prelevementRetenuSoucre = $Morale['retenues_source'];
		
		/////////////////////

		$table = '
		
		<style>
			table th,table td{width:80px;height:20px;border:1px solid black;}
			table td.dates{text-align:center;}
			.right{text-align:right;}
			.center{text-align:center;}
			.boder-top{border-top:1px solid black;}
			.boder-bottom{border-bottom:1px solid black;}
			.boder-left{border-left:1px solid black;}
			.boder-right{border-right:1px solid black;}
		</style>
		
        <table border="1" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 650px;">
        	<tr>
            	<th colspan="4">UNILEND</th>
            </tr>
            <tr>
            	<th style="background-color:#C9DAF2;">Période :</th>
                <th style="background-color:#C9DAF2;">'.$dateDebut.'</th>
                <th style="background-color:#C9DAF2;">au</th>
                <th style="background-color:#C9DAF2;">'.$dateFin.'</th>
            </tr>
			
			<tr>
            	<th style="background-color:#ECAEAE;" colspan="4">Prélèvements obligatoires</th>
            </tr>
			<tr>
            	<th>&nbsp;</th>
                <th style="background-color:#F4F3DA;">Base (Intérêts bruts)</th>
                <th style="background-color:#F4F3DA;">Montant prélèvements</th>
                <th style="background-color:#F4F3DA;">Taux</th>
            </tr>
			<tr>
				<th style="background-color:#E6F4DA;">Soumis au prélèvement</th>
				<td class="right">'.number_format($PhysiqueNoExoInte, 2, ',', ' ').'</td>
				<td class="right">'.number_format($lesPrelevSurPhysiqueNoExo, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format($prelevements_obligatoires, 2, ',', ' ').'%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Dispensé</th>
				<td class="right">'.number_format($PhysiqueExoInte, 2, ',', ' ').'</td>
				<td class="right">'.number_format($lesPrelevSurPhysiqueExo, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format(0, 2, ',', ' ').'%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Total</th>
				<td class="right">'.number_format($PhysiqueInte, 2, ',', ' ').'</td>
				<td class="right">'.number_format($lesPrelevSurPhysique, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format($prelevements_obligatoires, 2, ',', ' ').'%</td>
			</tr>
			
			<tr>
            	<th style="background-color:#ECAEAE;" colspan="4">Retenue à la source</th>
            </tr>
			<tr>
				<th style="background-color:#E6F4DA;">Retenue à la source</th>
				<td class="right">'.number_format($InteRetenuSoucre, 2, ',', ' ').'</td>
				<td class="right">'.number_format($prelevementRetenuSoucre, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format($tauxRetenuSoucre, 2, ',', ' ').'%</td>
			</tr>
			
			<tr>
            	<th style="background-color:#ECAEAE;" colspan="4">Prélèvements sociaux</th>
            </tr>
			<tr>
				<th style="background-color:#E6F4DA;">CSG</th>
				<td class="right">'.number_format($PhysiqueInte, 2, ',', ' ').'</td>
				<td class="right">'.number_format($csg, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format($txcsg, 2, ',', ' ').'%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Prélèvement social</th>
				<td class="right">'.number_format($PhysiqueInte, 2, ',', ' ').'</td>
				<td class="right">'.number_format($prelevements_sociaux, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format($txprelevements_sociaux, 2, ',', ' ').'%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Contribution additionnelle</th>
				<td class="right">'.number_format($PhysiqueInte, 2, ',', ' ').'</td>
				<td class="right">'.number_format($contributions_additionnelles, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format($txcontributions_additionnelles, 2, ',', ' ').'%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Prélèvement de solidarité</th>
				<td class="right">'.number_format($PhysiqueInte, 2, ',', ' ').'</td>
				<td class="right">'.number_format($prelevements_solidarite, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format($txprelevements_solidarite, 2, ',', ' ').'%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">CRDS</th>
				<td class="right">'.number_format($PhysiqueInte, 2, ',', ' ').'</td>
				<td class="right">'.number_format($crds, 2, ',', ' ').'</td>
				<td style="background-color:#DDDAF4;" class="right">'.number_format($txcrds, 2, ',', ' ').'%</td>
			</tr>
        </table>
		';
        
		
		/*header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=etat_quotidien.xls");
		echo $table;*/
		
		echo utf8_decode($table);
		
		
		$filename = 'Unilend_etat_fiscal_'.date('Ymd');
		//$filename = 'Unilend_etat_'.$ladatedetest;
		file_put_contents ($this->path.'protected/sftp/etat_fiscal/'.$filename.'.xls',$table);
		
		// Enregistrement sur le sftp
		$connection = ssh2_connect('ssh.reagi.com', 22);
		ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
		$sftp = ssh2_sftp($connection);
		$sftpStream = @fopen('ssh2.sftp://'.$sftp.'/home/sfpmei/emissions/etat_fiscal/'.$filename.'.xls', 'w');
		fwrite($sftpStream, $table);
		fclose($sftpStream);
		
		
		// les echeances du mois passé
		/*$dateMoins1Mois = mktime (date("H"),date("i"),0,date("m")-1,date("d"),date("Y"));
		
		$dateMoins1Mois = date('Y-m',$dateMoins1Mois);
		
		$lpreteurs = $this->clients->selectPreteurs($dateMoins1Mois);
		
		$csv = "id_client;id_lender_account;type;exonere;id_project;id_loan;ordre;montant;capital;interets;prelevements_obligatoires;retenues_source;csg;prelevements_sociaux;contributions_additionnelles;prelevements_solidarite;crds;date_echeance;date_echeance_reel;status_remb_preteur;date_echeance_emprunteur;date_echeance_emprunteur_reel;\n";
		foreach($lpreteurs as $p)
		{
			for($i=0;$i<=22;$i++)
			{
				$csv .= str_replace('.',',',$p[$i]).";";
			}
			$csv .= "\n";
		}*/
		
		
		//file_put_contents ($this->path.'protected/sftp/etat_fiscal/echeances_'.date('Y-m-d').'.csv',$csv);
		//die;
		/*header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"echeances.csv\"");
		print(utf8_decode($csv)); 
		exit;*/
		
		
		
		//************************************//
		//*** ENVOI DU MAIL ETAT FISCAL + echeances mois ***//
		//************************************//
		
		// destinataire
		//$destinataire = 'd.courtier@equinoa.com';
		
		// Recuperation du modele de mail
		$this->mails_text->get('notification-etat-fiscal','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		
		
		// Attribution des données aux variables
		$sujetMail = $this->mails_text->subject;
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
		$this->email->attachFromString($table,$filename.'.xls');
		//$this->email->attachFromString($csv,'echeances_'.date('Y-m-d').'.csv');
		
		if($this->Config['env'] == 'prod'){
			//$this->email->addRecipient('d.courtier@equinoa.com');
			$this->settings->get('Adresse notification etat fiscal','type');
			$this->email->addRecipient($this->settings->value);
		}
		else{
			$this->email->addRecipient('DCourtier.Auto@equinoa.fr');
		}
	
		$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
		$this->email->setHTMLBody($texteMail);
		Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
		// fin mail
		
		
		
		
		/////////////////////////////////////////////////////
		// On retire de bank unilend la partie  pour letat //
		/////////////////////////////////////////////////////
		
		$dateRembtemp = mktime (date("H"),date("i"),date("s"),date("m")-1,date("d"),date("Y"));
		$dateRemb = date("Y-m",$dateRembtemp);
		$dateRembM = date("m",$dateRembtemp);
		$dateRembY = date("Y",$dateRembtemp);
		$dateRembtotal = date("Y-m-d",$dateRembtemp);
		
		$etatRemb = $bank_unilend->sumMontantEtat('status = 1 AND type IN(2) AND LEFT(added,7) = "'.$dateRemb.'"');
		
		// 13 regul commission
		$regulCom = $transactions->sumByday(13,$dateRembM,$dateRembY);

		$sommeRegulDuMois = 0;
		foreach($regulCom as $r)
		{
			$sommeRegulDuMois += $r['montant_unilend']*100;
		}
		
		$etatRemb += $sommeRegulDuMois;

		if($etatRemb>0 )
		{
			// on créer un transaction sortante
			
			// On enregistre la transaction
			$transactions->id_client = 0;
			$transactions->montant = $etatRemb;
			$transactions->id_langue = 'fr';
			$transactions->date_transaction = date('Y-m-d H:i:s');
			$transactions->status = '1';
			$transactions->etat = '1';
			$transactions->ip_client = $_SERVER['REMOTE_ADDR'];
			$transactions->type_transaction = 12; // virement etat (retrait)
			$transactions->transaction = 1; // transaction virtuelle
			$transactions->id_transaction = $transactions->create();
			
			// on enregistre le mouvement
			$bank_unilend->id_transaction = $transactions->id_transaction;
			$bank_unilend->id_echeance_emprunteur = 0;
			$bank_unilend->id_project = 0;
			$bank_unilend->montant = '-'.$etatRemb;
			$bank_unilend->type = 3;
			$bank_unilend->status = 3;
			$bank_unilend->retrait_fiscale = 1;
			$bank_unilend->create();
		}
		
        
	}
	
	// On verifie toutes a 17h le solde des preteurs
	function _checkSoldes()
	{
		// die pour linstant
		//die;
		
		// chargement des datas
		$transactions = $this->loadData('transactions');
		$clients = $this->loadData('clients');
		$lenders_accounts = $this->loadData('lenders_accounts');
		$loans = $this->loadData('loans');
		$bids = $this->loadData('bids');
		$echeanciers = $this->loadData('echeanciers');
		
		$lClients = $clients->select('status = 1 AND status_pre_emp IN(1,3)');
		
		//$listBids = $bids->select('id_project = 796');
		
		foreach($lClients as $c)
		{
			
			//if($c['id_client'] == 330)
			//{
				$lenders_accounts->get($c['id_client'],'id_client_owner');
				
				// on recup l'alimentation du compte
				$alimentation = $transactions->sum('id_client = '.$c['id_client'].' AND type_transaction IN(1,3,4,7) AND etat = 1 AND status = 1','montant');
				$alimentation = ($alimentation/100);
				
				// les interets sans les retenues fiscales
				$sumRemb = $echeanciers->getSumRembV2($lenders_accounts->id_lender_account);
				$sumInteretSansfiscale = round($sumRemb['interets'],2);
				$sumMontantRembSansfiscale = round($sumRemb['montant'],2);
				
				// total argent
				$total = $alimentation + $sumInteretSansfiscale;
				
				// argent dispo sur compte
				$solde = $transactions->getSolde($c['id_client']);
				
				// somme prêté
				$sumPrets = $loans->sumPrets($lenders_accounts->id_lender_account);
				
				// somme des bids en cours
				$sumBidsEncours = $bids->sumBidsEncours($lenders_accounts->id_lender_account);
				
				$sumMouv = $transactions->sum('id_client = '.$c['id_client'].' AND etat = 1 AND status = 1','montant');
				$sumMouv = ($sumMouv/100);
				
				$sumRetrait = $transactions->sum('id_client = '.$c['id_client'].' AND etat = 1 AND status = 1 AND type_transaction = 8','montant');
				$sumRetrait = ($sumRetrait/100);
				
				// solde reel du compte
				$soldeReel = $alimentation-$sumPrets-$sumBidsEncours+$sumMontantRembSansfiscale+$sumRetrait;
				
				echo '------------------------------ <br>';
				echo 'client : '.$c['id_client'].'<br>';
				echo 'solde :'.$solde.' euros<br><br>';
				echo 'alimentation : '.$alimentation.' euros<br>';
				echo 'sumRemb : '.$sumMontantRembSansfiscale.' euros<br>';
				echo 'interets : '.$sumInteretSansfiscale.' euros<br>';
				echo 'total : '.$total.' euros<br>';
				echo 'sumRetraits : '.$sumRetrait.' euros<br>';
				echo '<br>';
				echo 'sumPrets : '.$sumPrets.' euros<br>';
				echo 'sumBidsEncours : '.$sumBidsEncours.' euros<br>';
				echo '<br>';
				echo 'sumMouv : '.$sumMouv.' euros<br>';
				echo 'soldeReel : '.$soldeReel.' euros<br>';
				
				
				$cond1 = round($soldeReel,2);
				$cond2 = round($sumMouv,2);
				
				
				if($cond1 < $cond2)
				{
					
					echo '------------------------------ <br>';
					echo 'client : '.$c['id_client'].'<br>';
					echo 'solde :'.$solde.' euros<br><br>';
					echo 'alimentation : '.$alimentation.' euros<br>';
					echo 'sumRemb : '.$sumMontantRembSansfiscale.' euros<br>';
					echo 'interets : '.$sumInteretSansfiscale.' euros<br>';
					echo 'total : '.$total.' euros<br>';
					echo 'sumRetraits : '.$sumRetrait.' euros<br>';
					echo '<br>';
					echo 'sumPrets : '.$sumPrets.' euros<br>';
					echo 'sumBidsEncours : '.$sumBidsEncours.' euros<br>';
					echo '<br>';
					echo 'sumMouv : '.$sumMouv.' euros<br>';
					echo 'soldeReel : '.$soldeReel.' euros<br>';
					
					
					echo '--------------------<br>';
	
					echo '<br>';
					
					echo $c['id_client'].' - trop<br>';
					
					
					$to  = 'unilend@equinoa.fr';
					//$to  = 'courtier.damien@gmail.com';
				
					// subject
					$subject = '[Alerte] Solde preteur';
					
					// message
					$message = '
					<html>
					<head>
					  <title>[Alerte] Solde preteur</title>
					</head>
					<body>
					  <p>Le solde du client est plus eleve que le solde theorique</p>
					  <table>
						<tr>
						  <th>Id client : </th><td>'.$c['id_client'].'</td>
						</tr>
						<tr>
						  <th>Alimentation : </th><td>'.$alimentation.'</td>
						</tr>
						<tr>
						  <th>Somme des remboursements : </th><td>'.$sumMontantRembSansfiscale.' euros</td>
						</tr>
						<tr>
						  <th>Interets (sans retenue fiscale) : </th><td>'.$sumInteretSansfiscale.'</td>
						</tr>
						<tr>
						  <th>Total (alim + interets) : </th><td>'.$total.'</td>
						</tr>
						<tr>
						  <th>Somme des retraits : </th><td>'.$sumRetrait.'</td>
						</tr>
						<tr>
						  <th>Somme des prets : </th><td>'.$sumPrets.'</td>
						</tr>
						<tr>
						  <th>Somme des Bids en cours : </th><td>'.$sumBidsEncours.'</td>
						</tr>
						<tr>
						  <th>Somme des mouvement d\'argent : </th><td>'.$sumMouv.'</td>
						</tr>';
						
						if($soldeReel < 0)
						{
							$message .= '
							<tr>
							  <th>Somme manquante sur le compte : </th><td style="color:red;">'.str_replace('-','',$soldeReel).'</td>
							</tr>';
						}
						else
						{
						$message .= '
						<tr>
						  <th>Somme en trop  : </th><td style="color:red;">'.($sumMouv-$soldeReel).'</td>
						</tr>';
						}
						
					  	$message .= '
						<tr>
						  <th>Solde du compte errone : </th><td>'.$solde.'</td>
						</tr>
					  	<tr>
						  <th>Solde reel du compte : </th><td>'.$soldeReel.'</td>
						</tr>
					  </table>
					</body>
					</html>
					';
					
					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					
					// Additional headers
					
					//$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
					$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
					
					
					
					// Mail it
					mail($to, $subject, $message, $headers);
					
				}
				
			//}
				
		}
		
	
		
		// On regarde si y a des doubles degel
		
		$lesRejets = $transactions->select('id_bid_remb <> 0');
		/*echo '<pre>';
		print_r($lesRejets);
		echo '</pre>';*/

		foreach($lesRejets as $r)
		{
			
			$lsDoubles = $transactions->counter('id_bid_remb = '.$r['id_bid_remb']);
			if($lsDoubles>1)
			{

				echo 'id_client : '.$r['id_client'].' bid '.$r['id_bid_remb'].'<br>';
				
				$bids->get($r['id_bid_remb'],'id_bid');
				
				$to  = 'unilend@equinoa.fr';
				//$to  = 'courtier.damien@gmail.com';
				//$to  = 'd.courtier@equinoa.com';
			
				// subject
				$subject = '[Alerte] Degel en double';
				
				// message
				$message = '
				<html>
				<head>
				  <title>[Alerte] Degel en double</title>
				</head>
				<body>
					<p>Un degel a ete effectue en double sur un bid</p>
					<table>
						<tr>
							<th>Id client : </th><td>'.$r['id_client'].'</td>
						</tr>
						<tr>
							<th>Id bid Remb : </th><td>'.$r['id_bid_remb'].'</td>
						</tr>
						<tr>
							<th>Id projet : </th><td>'.$bids->id_project.'</td>
						</tr>
						<tr>
							<th>Montant : </th><td>'.($r['montant']/100).'</td>
						</tr>
						<tr>
							<th>Date degel : </th><td>'.$r['added'].'</td>
						</tr>
					</table>
				</body>
				</html>
				';
				
				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				
				// Additional headers
				
				$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
				//$headers .= 'From: Unilend <courtier.damien@gmail.com>' . "\r\n";
				//$headers .= 'From: Unilend <d.courtier@equinoa.com>' . "\r\n";
				
				// Mail it
				mail($to, $subject, $message, $headers);
				//break;
				
			}
			
		}
		
		
	}
	
	function _checkSoldesV2()
	{
		// die pour linstant
		//die;
		
		// chargement des datas
		$transactions = $this->loadData('transactions');
		$clients = $this->loadData('clients');
		$lenders_accounts = $this->loadData('lenders_accounts');
		$loans = $this->loadData('loans');
		$bids = $this->loadData('bids');
		$echeanciers = $this->loadData('echeanciers');
		
		//$lClients = $clients->select('status = 1 AND status_pre_emp IN(1,3)');
		
		$listBids = $bids->select('id_project = 796');
		
		foreach($listBids as $b)
		{
			
			//if($c['id_client'] == 330)
			//{
				$lenders_accounts->get($b['id_lender_account'],'id_lender_account');
				
				$c = $clients->select('status = 1 AND status_pre_emp IN(1,3) AND id_client = '.$lenders_accounts->id_client_owner);
				$c = $c[0];
				
				// on recup l'alimentation du compte
				$alimentation = $transactions->sum('id_client = '.$c['id_client'].' AND type_transaction IN(1,3,4,7) AND etat = 1 AND status = 1','montant');
				$alimentation = ($alimentation/100);
				
				// les interets sans les retenues fiscales
				$sumRemb = $echeanciers->getSumRembV2($lenders_accounts->id_lender_account);
				$sumInteretSansfiscale = round($sumRemb['interets'],2);
				$sumMontantRembSansfiscale = round($sumRemb['montant'],2);
				
				// total argent
				$total = $alimentation + $sumInteretSansfiscale;
				
				// argent dispo sur compte
				$solde = $transactions->getSolde($c['id_client']);
				
				// somme prêté
				$sumPrets = $loans->sumPrets($lenders_accounts->id_lender_account);
				
				// somme des bids en cours
				$sumBidsEncours = $bids->sumBidsEncours($lenders_accounts->id_lender_account);
				
				$sumMouv = $transactions->sum('id_client = '.$c['id_client'].' AND etat = 1 AND status = 1','montant');
				$sumMouv = ($sumMouv/100);
				
				$sumRetrait = $transactions->sum('id_client = '.$c['id_client'].' AND etat = 1 AND status = 1 AND type_transaction = 8','montant');
				$sumRetrait = ($sumRetrait/100);
				
				// solde reel du compte
				$soldeReel = $alimentation-$sumPrets-$sumBidsEncours+$sumMontantRembSansfiscale+$sumRetrait;
				
				/*echo '------------------------------ <br>';
				echo 'client : '.$c['id_client'].'<br>';
				echo 'solde :'.$solde.' euros<br><br>';
				echo 'alimentation : '.$alimentation.' euros<br>';
				echo 'sumRemb : '.$sumMontantRembSansfiscale.' euros<br>';
				echo 'interets : '.$sumInteretSansfiscale.' euros<br>';
				echo 'total : '.$total.' euros<br>';
				echo 'sumRetraits : '.$sumRetrait.' euros<br>';
				echo '<br>';
				echo 'sumPrets : '.$sumPrets.' euros<br>';
				echo 'sumBidsEncours : '.$sumBidsEncours.' euros<br>';
				echo '<br>';
				echo 'sumMouv : '.$sumMouv.' euros<br>';
				echo 'soldeReel : '.$soldeReel.' euros<br>';*/
				
				
				$cond1 = round($soldeReel,2);
				$cond2 = round($sumMouv,2);
				
				
				if($cond1 < $cond2)
				{
					
					echo '------------------------------ <br>';
					echo 'client : '.$c['id_client'].'<br>';
					echo 'solde :'.$solde.' euros<br><br>';
					echo 'alimentation : '.$alimentation.' euros<br>';
					echo 'sumRemb : '.$sumMontantRembSansfiscale.' euros<br>';
					echo 'interets : '.$sumInteretSansfiscale.' euros<br>';
					echo 'total : '.$total.' euros<br>';
					echo 'sumRetraits : '.$sumRetrait.' euros<br>';
					echo '<br>';
					echo 'sumPrets : '.$sumPrets.' euros<br>';
					echo 'sumBidsEncours : '.$sumBidsEncours.' euros<br>';
					echo '<br>';
					echo 'sumMouv : '.$sumMouv.' euros<br>';
					echo 'soldeReel : '.$soldeReel.' euros<br>';
					
					
					echo '--------------------<br>';
	
					echo '<br>';
					
					echo $c['id_client'].' - trop<br>';
					
					
					$to  = 'unilend@equinoa.fr';
					//$to  = 'courtier.damien@gmail.com';
				
					// subject
					$subject = '[Alerte] Solde preteur';
					
					// message
					$message = '
					<html>
					<head>
					  <title>[Alerte] Solde preteur</title>
					</head>
					<body>
					  <p>Le solde du client est plus eleve que le solde theorique</p>
					  <table>
						<tr>
						  <th>Id client : </th><td>'.$c['id_client'].'</td>
						</tr>
						<tr>
						  <th>Alimentation : </th><td>'.$alimentation.'</td>
						</tr>
						<tr>
						  <th>Somme des remboursements : </th><td>'.$sumMontantRembSansfiscale.' euros</td>
						</tr>
						<tr>
						  <th>Interets (sans retenue fiscale) : </th><td>'.$sumInteretSansfiscale.'</td>
						</tr>
						<tr>
						  <th>Total (alim + interets) : </th><td>'.$total.'</td>
						</tr>
						<tr>
						  <th>Somme des retraits : </th><td>'.$sumRetrait.'</td>
						</tr>
						<tr>
						  <th>Somme des prets : </th><td>'.$sumPrets.'</td>
						</tr>
						<tr>
						  <th>Somme des Bids en cours : </th><td>'.$sumBidsEncours.'</td>
						</tr>
						<tr>
						  <th>Somme des mouvement d\'argent : </th><td>'.$sumMouv.'</td>
						</tr>';
						
						if($soldeReel < 0)
						{
							$message .= '
							<tr>
							  <th>Somme manquante sur le compte : </th><td style="color:red;">'.str_replace('-','',$soldeReel).'</td>
							</tr>';
						}
						else
						{
						$message .= '
						<tr>
						  <th>Somme en trop  : </th><td style="color:red;">'.($sumMouv-$soldeReel).'</td>
						</tr>';
						}
						
					  	$message .= '
						<tr>
						  <th>Solde du compte errone : </th><td>'.$solde.'</td>
						</tr>
					  	<tr>
						  <th>Solde reel du compte : </th><td>'.$soldeReel.'</td>
						</tr>
					  </table>
					</body>
					</html>
					';
					
					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					
					// Additional headers
					
					//$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
					$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
					
					
					
					// Mail it
					mail($to, $subject, $message, $headers);
					
				}
				
				//break;
			//}
				
		}
		
		
		// On regarde si y a des doubles degel
		
		$lesRejets = $transactions->select('id_bid_remb <> 0');
		/*echo '<pre>';
		print_r($lesRejets);
		echo '</pre>';*/

		foreach($lesRejets as $r)
		{
			
			$lsDoubles = $transactions->counter('id_bid_remb = '.$r['id_bid_remb']);
			if($lsDoubles>1)
			{

				echo 'id_client : '.$r['id_client'].' bid '.$r['id_bid_remb'].'<br>';
				
				$bids->get($r['id_bid_remb'],'id_bid');
				
				$to  = 'unilend@equinoa.fr';
				//$to  = 'courtier.damien@gmail.com';
				//$to  = 'd.courtier@equinoa.com';
			
				// subject
				$subject = '[Alerte] Degel en double';
				
				// message
				$message = '
				<html>
				<head>
				  <title>[Alerte] Degel en double</title>
				</head>
				<body>
					<p>Un degel a ete effectue en double sur un bid</p>
					<table>
						<tr>
							<th>Id client : </th><td>'.$r['id_client'].'</td>
						</tr>
						<tr>
							<th>Id bid Remb : </th><td>'.$r['id_bid_remb'].'</td>
						</tr>
						<tr>
							<th>Id projet : </th><td>'.$bids->id_project.'</td>
						</tr>
						<tr>
							<th>Montant : </th><td>'.($r['montant']/100).'</td>
						</tr>
						<tr>
							<th>Date degel : </th><td>'.$r['added'].'</td>
						</tr>
					</table>
				</body>
				</html>
				';
				
				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				
				// Additional headers
				
				$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
				//$headers .= 'From: Unilend <courtier.damien@gmail.com>' . "\r\n";
				//$headers .= 'From: Unilend <d.courtier@equinoa.com>' . "\r\n";
				
				// Mail it
				mail($to, $subject, $message, $headers);
				//break;
				
			}
			
		}
		
		
	}
	
	// part une fois par jour a 1h du matin afin de checker les mail de la veille
	function _checkMailNoDestinataire()
	{
		// chargement des datas
		$nmp = $this->loadData('nmp');
		
		$date = mktime (0,0,0,date("m"),date("d")-1,date("Y"));
		$date = date('Y-m-d',$date);
		
		$lNoMail = $nmp->select('mailto = "" AND added LIKE "'.$date.'%"');
		
		if($lNoMail != false)
		{
			foreach($lNoMail as $m)
			{
				$to  = 'unilend@equinoa.fr';
				//$to  = 'courtier.damien@gmail.com';
			
				// subject
				$subject = '[Alerte] Mail Sans destinataire';
				
				// message
				$message = '
				<html>
				<head>
				  <title>[Alerte] Mail Sans destinataire</title>
				</head>
				<body>
					<p>Un mail a ete envoye sans destinataire</p>
					<table>
						<tr>
							<th>id_nmp : </th><td>'.$m['id_nmp'].'</td>
						</tr>
					</table>
				</body>
				</html>
				';
				
				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				
				// Additional headers
				
				//$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
				$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
				
				//$headers .= 'To: equinoa <courtier.damien@gmail.com>' . "\r\n";
				//$headers .= 'From: Unilend <courtier.damien@gmail.com>' . "\r\n";
				
				// Mail it
				mail($to, $subject, $message, $headers);
			}
		}
		
		
	}
	
	
	function _declarationContratPret()
	{
		
		
		// Check de sécurité de params 
		if($this->params[0] != 7 && $this->params[0] != 180 && $this->params[0] != 19)
		{
			echo "Blocage de s&eacute;curit&eacute; : L'id projet ajout&eacute; en params ne correspond pas &agrave; un projet cible";
			die;
		}
		
		if(!isset($this->params[0]) || $this->params[0] == "")
		{
			echo "Aucun id_projet renseign&eacute;";
			die;
		}
		
		
		if($_SERVER['REMOTE_ADDR'] != '93.26.42.99')
		{
			die;
		}
		
		// chargement des datas
		$loans = $this->loadData('loans');
		$projects = $this->loadData('projects');
		
		$lProjects = $projects->selectProjectsByStatus('80,90,100,110,120');
		
		if(count($lProjects)>0)
		{
		
			$a = 0;
			$lesProjets = '';
			foreach($lProjects as $p)
			{
				$lesProjets .= ($a == 0?'':',').$p['id_project'];
				$a++;	
			}
	
			// On recupere que le premier loan
			$lLoans = $loans->select('status = "0" AND fichier_declarationContratPret = "" AND id_project IN('.$this->params[0].')','id_loan ASC','0','');
			
			if(count($lLoans)>0)
			{
				
				foreach($lLoans as $l)
				{
				
					//$l = $lLoans[0];
					
					$annee = substr($l['added'],0,4);
					
					// On recup le projet
					$projects->get($l['id_project'],'id_project');
					
					// Dossier année
					$pathAnnee = $this->path.'protected/declarationContratPret/'.$annee.'/';
					// chemin où l'on enregistre
					$path = $this->path.'protected/declarationContratPret/'.$annee.'/'.$projects->slug.'/';
					
					// Si le dossier existe pas on créer
					if(!file_exists($pathAnnee))
					{
						mkdir($pathAnnee);
					}
					// Si le dossier existe pas on créer
					if(!file_exists($path))
					{
						mkdir($path);
					}
					
					// Nom du fichier	
					$nom = 'Unilend_declarationContratPret_'.$l['id_loan'].'.pdf';
					
					// Génération pdf
					$this->Web2Pdf->convertSimple($this->lurl.'/pdf/declarationContratPret_html/'.$l['id_loan'],$path,$nom);
					
					// On met a jour le loan pour savoir qu'on la deja enregistré
					$loans->get($l['id_loan'],'id_loan');
					$loans->fichier_declarationContratPret = $nom;	
					$loans->update();
				}
			}
			echo "Toutes les d&eacute;clarations sont g&eacute;n&eacute;r&eacute;es <br />";
		}
		
		
		
	}
	
	// Fonctions Annexes
	function ftp_is_dir($connexion,$dir)
	{
		if(ftp_chdir($connexion,$dir))
		{
			ftp_chdir($connexion, '..');
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/////////////////////////
	/// POUR LA DEMO ONLY ///
	/////////////////////////
	
	// On copie le backup recu pas oxeva
	function copyBackup()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		$this->autoFireView = false;
		
		if($this->Config['env'] != 'demo')
		{
			die;
		}
		
		// Dossier backup
		$backup = $this->path.'backup/';
		
		$backup2 = $this->path.'backup2/';
		
		/////////////////////////////////////////////////
		// On parcour le dossier backup2 pour le vider //
		/////////////////////////////////////////////////
		
		$dir = opendir($backup2); 
		
		while($file = readdir($dir))
		{
			// On retire les dossiers et les "." ".." ainsi que le fichier schemas.sql
			if($file != '.' && $file != '..' && !is_dir($backup2.$file))
			{
				// On reverifie si on a bien le fichier GZ
				if(file_exists($backup2.$file))
				{
					unlink($backup2.$file);
				}
				// Fin fichier sql.gz
			}
			
		}
		
		closedir($dir);
		
		//////////////////////////////////////////////////
		
		
		///////////////////////////////////////////////////
		// On parcour le dossier backup rempli par oxeva //
		///////////////////////////////////////////////////
		
		$dir = opendir($backup); 
		
		while($file = readdir($dir))
		{
			// On retire les dossiers et les "." ".." ainsi que le fichier schemas.sql
			if($file != '.' && $file != '..' && !is_dir($backup.$file))
			{
				// On reverifie si on a bien le fichier
				if(file_exists($backup.$file))
				{
					// On le copie dans backup2
					copy($backup.$file,$backup2.$file);
				}
			}
			
		}
		
		closedir($dir);
		//////////////////////////////////////////////////////
		
	}
	
	// Mise a jour de la bdd demo tous les jours a 2h du matin
	function _updateDemoBDD()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		$this->autoFireView = false;
		
		if($this->Config['env'] != 'demo')
		{
			die;
		}
		
		// On copie le backup oxeva dans backup2
		$this->copyBackup();
		
		// Dossier backup
		$dirname = $this->path.'backup2/';
		
		// Informations pour la connexion à la BDD
		$mysqlDatabaseName = $this->Config['bdd_config'][$this->Config['env']]['BDD'];
		$mysqlUserName = $this->Config['bdd_config'][$this->Config['env']]['USER'];
		$mysqlPassword = $this->Config['bdd_config'][$this->Config['env']]['PASSWORD'];
		$mysqlHostName = $this->Config['bdd_config'][$this->Config['env']]['HOST'];
	
		// Si on a un fichier schemas.sql (permet de supprimer et de recrer les tables)
		if(file_exists($dirname.'schemas.sql'))
		{
			// chemin fichier
			$mysqlImportFilename = $dirname.'schemas.sql';
			
			// Commande
			$command='mysql -h' .$mysqlHostName .' -u' .$mysqlUserName .' -p' .$mysqlPassword .' ' .$mysqlDatabaseName .' < ' .$mysqlImportFilename;
			
			// Exec commande
			exec($command,$output=array(),$worked);
			
			switch($worked)
			{
				case 0:
					echo 'IMPORT SCHEMAS.SQL OK<br>';
					break;
				case 1:
					echo 'IMPORT SCHEMAS.SQL NOK<br>';
					break;
			}
			
		}
		echo '---------------<br>';
				
		$dir = opendir($dirname); 
		
		while($fileGZ = readdir($dir))
		{
			// On retire les dossiers et les "." ".." ainsi que le fichier schemas.sql
			if($fileGZ != '.' && $fileGZ != '..' && !is_dir($dirname.$fileGZ) && $fileGZ != 'schemas.sql')
			{
				// On reverifie si on a bien le fichier GZ
				if(file_exists($dirname.$fileGZ))
				{
					// Fichier .SQL.GZ
					$fichierGZ = $dirname.$fileGZ;
					// Fichier .SQL
					$file = str_replace('.gz','',$fileGZ);
					$fichier = $dirname.$file;
					
					// Commande dezip (remplace le fihcier compressé pas un non compressé)
					$command = "gunzip ".$fichierGZ;
					exec($command,$output=array(),$worked);
					switch($worked)
					{
						case 0:
							echo 'GUNZIP '.$fileGZ.' OK<br>';
							break;
						case 1:
							echo 'GUNZIP '.$fileGZ.' NOK<br>';
							break;
					}
					
					// Si on a un fichier .SQL
					if(file_exists($fichier))
					{
						$mysqlImportFilename = $fichier;
						
						// Commande
						$command='mysql -h' .$mysqlHostName .' -u' .$mysqlUserName .' -p' .$mysqlPassword .' ' .$mysqlDatabaseName .' < ' .$mysqlImportFilename;
						
						// Exec commande
						exec($command,$output=array(),$worked);
						
						switch($worked)
						{
							case 0:
								echo 'IMPORT '.$file.' OK<br>';
								break;
							case 1:
								echo 'IMPORT '.$file.' NOK<br>';
								break;
						}
					}
					// Fin fichier sql
					
				}
				// Fin fichier sql.gz
			}
			echo '----------------<br>';
		}
		
		closedir($dir);
		
		/////////////////////////////
		// Mise a jour des données //
		/////////////////////////////
		
		// On change l'adresse mail de tout les clients
		$this->bdd->query("UPDATE `unilend`.`clients` SET `email` = 'DCourtier.Auto@equinoa.fr';");
		// Et on change l'adresse de notifiaction
		$this->bdd->query("UPDATE `unilend`.`settings` SET `value` = 'DCourtier.Auto@equinoa.fr' WHERE id_setting = 44;");
		// email facture
		$this->bdd->query("UPDATE `unilend`.`companies` SET `email_facture` = 'DCourtier.Auto@equinoa.fr';");
		
		//////////////////////////////
		
		
		///////////////////////////////////////////
		// Email pour prevenir de la mise a jour //
		///////////////////////////////////////////
		
		
		// multiple recipients
		//$to  = 'aidan@example.com' . ', '; // note the comma
		//$to .= 'wez@example.com';
		$to  = 'unilend@equinoa.fr';
		//$to  = 'courtier.damien@gmail.com';
		
		// subject
		$subject = '[UNILEND DEMO] La BDD a ete mise à jour';
		
		// message
		$message = '
		<html>
		<head>
		  <title>[UNILEND DEMO] La BDD a ete mise à jour</title>
		</head>
		<body>
		  <p>[UNILEND DEMO] La BDD a ete mise à jour</p>
		</body>
		</html>
		';
		
		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		
		// Additional headers
		//$headers .= 'To: Damien <d.courtier@equinoa.com>, Kelly <kelly@example.com>' . "\r\n";
		$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
		$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
		
		// Mail it
		mail($to, $subject, $message, $headers);
	}
	
	// Toutes les minutes on check les bids pour les passer en ENCOURS/OK/NOK (check toutes les 5 min et toutes les minutes de 15h30 à 16h00)
	function _checkBids()
	{
		$debut = time();
		
		// On fait notre cron toutes les  5 minutes et toutes les minutes entre 15h30 et 16h00
		$les5 = array(00,05,10,15,20,25,30,35,40,45,50,55);
		$minutes = date('i');
		
		$dateDeb = mktime (15,30,0,date("m"),date("d"),date("Y"));
		$dateFin = mktime (16,00,0,date("m"),date("d"),date("Y"));
		//cron 5 min et toutes les minutes de 15h30 à 16h00
		if(in_array($minutes,$les5) || time() >= $dateDeb && time() <= $dateFin)
		{
		
			// On recup le param
			$settingsControleCheckBids = $this->loadData('settings');
			$settingsControleCheckBids->get('Controle cron checkBids','type');
			
			// on rentre dans le cron si statut égale 1 pour eviter les double passages
			if($settingsControleCheckBids->value == 1)
			{
				
				// On passe le statut a zero pour signaler qu'on est en cours de traitement
				$settingsControleCheckBids->value = 0;
				$settingsControleCheckBids->update();
				
				// Chargement des datas
				$this->projects = $this->loadData('projects');
				$this->projects_status = $this->loadData('projects_status');
				$this->emprunteur = $this->loadData('clients');
				$this->companies = $this->loadData('companies');
				$this->bids = $this->loadData('bids');
				$this->lenders_accounts = $this->loadData('lenders_accounts');
				$this->preteur = $this->loadData('clients');
				$this->notifications = $this->loadData('notifications');
				$this->wallets_lines = $this->loadData('wallets_lines');
				$this->bids_logs = $this->loadData('bids_logs');
				
				// Liste des projets
				$lProjects = $this->projects->select('status = 0');
				
				foreach($lProjects as $p)
				{
					
					// Statut du projet
					$this->projects_status->getLastStatut($p['id_project']);
					
					if($this->projects_status->status == 50)
					{
						// Logs bids
						$this->bids_logs->debut = date('Y-m-d H:i:s');
						$this->bids_logs->id_project = $p['id_project'];
						
						// Variables logs bids
						$bids_logs = false;
						$nb_bids_ko = 0;
						$nb_bids_encours = $this->bids->counter('id_project = '.$p['id_project'].' AND status = 0');
						$total_bids = $this->bids->counter('id_project = '.$p['id_project']);
						
						// la sum des encheres
						$soldeBid = $this->bids->getSoldeBid($p['id_project']);
						
						// Reste a payer
						$montantEmprunt = $p['amount'];
						
						// si solde bid supperieur au montant voulu
						if($soldeBid >= $montantEmprunt)
						{
							// on recup les bid en statut en cours
							$this->lEnchere = $this->bids->select('id_project = '.$p['id_project'].' AND status = 0','rate ASC,added ASC');
							
							// on parcour les bids
							$leSoldeE = 0;
							foreach($this->lEnchere as $k => $e)
							{
								// on parcour les encheres jusqu'au montant de l'emprunt
								if($leSoldeE < $montantEmprunt)
								{
									// le montant preteur (x100)
									$amount = $e['amount'];
									
									// le solde total des encheres
									$leSoldeE += ($e['amount']/100);	
								}
								// Les bid qui depassent on leurs redonne leur argent et on met en ko
								else 
								{
									// Variable bids logs pour savoir si on a un traitement a enregistrer
									$bids_logs = true;
									
									/*echo '<br>------------<br>';
									echo 'projet : '.$p['id_project'].'<br>';
									echo 'lender : '.$e['id_lender_account'].'<br>';
									echo 'montant preteur : '.($e['amount']/100).'<br>';
									echo '$leSoldeE : '.$leSoldeE.'<br>';
									echo 'tx preteur : '.($e['rate']).'<br>';
									echo 'id_bid : '.($e['id_bid']).'<br>';*/
									
									// On recupere le bid
									$this->bids->get($e['id_bid'],'id_bid');
								
									// On fait un double chek pour eviter un doublon
									if($this->bids->status == 0)
									{
										$this->bids->status = 2; // statut bid ko
										$this->bids->update();
										
										// On recup les infos du preteur
										$this->lenders_accounts->get($e['id_lender_account'],'id_lender_account');											
										$this->preteur->get($this->lenders_accounts->id_client_owner,'id_client');
	
										// On enregistre la transaction
										$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
										$this->transactions->montant = $e['amount'];
										$this->transactions->id_langue = 'fr';
										$this->transactions->date_transaction = date('Y-m-d H:i:s');
										$this->transactions->status = '1';
										$this->transactions->etat = '1';
										$this->transactions->id_project = $p['id_project'];
										$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
										$this->transactions->type_transaction = 2;
										$this->transactions->id_bid_remb = $e['id_bid']; 
										$this->transactions->transaction = 2; // transaction virtuelle
										$this->transactions->id_transaction = $this->transactions->create();
										
										// on enregistre la transaction dans son wallet
										$this->wallets_lines->id_lender = $e['id_lender_account'];
										$this->wallets_lines->type_financial_operation = 20;
										$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
										$this->wallets_lines->status = 1;
										$this->wallets_lines->type = 2;
										$this->wallets_lines->id_bid_remb = $e['id_bid']; 
										$this->wallets_lines->amount = $e['amount'];
										$this->wallets_lines->id_project = $p['id_project'];
										$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
										
										
										$this->notifications->type = 1; // rejet
										$this->notifications->id_lender = $e['id_lender_account'];
										$this->notifications->id_project = $p['id_project'];
										$this->notifications->amount = $e['amount'];
										$this->notifications->id_bid = $e['id_bid'];
										$this->notifications->create();
										
										$nb_bids_ko++;
										
									} // fin condition eviter double
								} // fin bids qui depassent
								
								$this->bids->get($e['id_bid'],'id_bid');
								$this->bids->checked = 1;
								$this->bids->update();	
								
							} // fin boucle bids en cours
							
							////////////////////////////
							// EMAIL EMPRUNTEUR FUNDE //
							////////////////////////////
							
							if($p['status_solde'] == 0)
							{
								// Mise a jour du statut pour envoyer qu'une seule fois le mail a l'emprunteur
								$this->projects->get($p['id_project'],'id_project');
								$this->projects->status_solde = 1;
								$this->projects->update();
								
								// FB
								$this->settings->get('Facebook','type');
								$lien_fb = $this->settings->value;
								
								// Twitter
								$this->settings->get('Twitter','type');
								$lien_tw = $this->settings->value;
								
								// Heure fin periode funding
								$this->settings->get('Heure fin periode funding','type');
								$this->heureFinFunding = $this->settings->value;
								
								// On recup la companie
								$this->companies->get($p['id_company'],'id_company');
								// l'emprunteur
								$this->emprunteur->get($this->companies->id_client_owner,'id_client');
								
								$tab_date_retrait = explode(' ',$p['date_retrait_full']);
								$tab_date_retrait = explode(':',$tab_date_retrait[1]);
								$heure_retrait = $tab_date_retrait[0].':'.$tab_date_retrait[1];
								
								if($heure_retrait == '00:00') $heure_retrait = $this->heureFinFunding;
								
								
								// On recup le temps restant
								$inter = $this->dates->intervalDates(date('Y-m-d H:i:s'),$p['date_retrait'].' '.$heure_retrait.':00'); 
								
								$dateRetrait = $p['date_retrait'].' '.$heure_retrait.':00';
								
								
										
								if($inter['mois']>0) $tempsRest = $inter['mois'].' mois';
								elseif($inter['jours']>0) $tempsRest = $inter['jours'].' jours';
								elseif($inter['heures']>0 && $inter['minutes'] >= 120) $tempsRest = $inter['heures'].' heures';
								elseif($inter['minutes']>0 && $inter['minutes']< 120) $tempsRest = $inter['minutes'].' min';
								else $tempsRest = $inter['secondes'].' secondes';
								
								//**************************************//
								//*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
								//**************************************//
								
								// Recuperation du modele de mail
								$this->mails_text->get('emprunteur-dossier-funde','lang = "'.$this->language.'" AND type');
								
								// Taux moyen pondéré
								$montantHaut = 0;
								$montantBas = 0;
								foreach($this->bids->select('id_project = '.$p['id_project'].' AND status = 0' ) as $b)
								{
									$montantHaut += ($b['rate']*($b['amount']/100));
									$montantBas += ($b['amount']/100);
								}
								$taux_moyen = ($montantHaut/$montantBas);
								$taux_moyen = number_format($taux_moyen, 2, ',', ' ');
	
								// Variables du mailing
								$varMail = array(
								'surl' => $this->surl,
								'url' => $this->lurl,
								'prenom_e' => utf8_decode($this->emprunteur->prenom),
								'taux_moyen' => $taux_moyen,
								'link_compte_emprunteur' => $this->lurl.'/synthese_emprunteur',
								'temps_restant' => $tempsRest,
								'projet' => $p['title'],
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
									Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->emprunteur->email,$tabFiler);			
									// Injection du mail NMP dans la queue
									$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
								}
								else // non nmp
								{
									$this->email->addRecipient(trim($this->emprunteur->email));
									Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
								}
	
								//*********************************************//
								//*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//
								//*********************************************//
								
								$this->settings->get('Adresse notification projet funde a 100','type');
								$destinataire = $this->settings->value;
								
								// Nombre de preteurs
								$nbPeteurs = $this->bids->getNbPreteurs($p['id_project']);
								
								// Recuperation du modele de mail
								$this->mails_text->get('notification-projet-funde-a-100','lang = "'.$this->language.'" AND type');
								
								// Variables du mailing
								$surl = $this->surl;
								$url = $this->lurl;
								$id_projet = $p['id_project'];
								$title_projet = utf8_encode($p['title']);
								$nbPeteurs = $nbPeteurs;
								$tx = $taux_moyen;
								$periode = $tempsRest;
								
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
							
							} // Fin partie mail emprunteur
						} // Fin solde atteint
						
						// Si on a au moins un bid ko de traité
						if($bids_logs == true)
						{
							// Logs bids
							$total_bids_ko = $this->bids->counter('id_project = '.$p['id_project'].' AND status = 2');
							$this->bids_logs->nb_bids_encours = $nb_bids_encours;
							$this->bids_logs->nb_bids_ko = $nb_bids_ko;
							$this->bids_logs->total_bids = $total_bids;
							$this->bids_logs->total_bids_ko = $total_bids_ko;
							$this->bids_logs->fin = date('Y-m-d H:i:s');
							$this->bids_logs->create();
						}
					} // Fin projet en funding
				} // Fin boucle projets
	
				$settingsControleCheckBids->value = 1;
				$settingsControleCheckBids->update();
					
			} // Fin settingsControleCheckBids	
		} // Fin cron 5 min et toutes les minutes de 15h30 a 16h00
		$fin = time();
		echo 'Time : '.($fin - $debut);
	}
	
	// On check bid ko si oui ou non un mail de degel est parti. Si c'est non on envoie un mail
	function _checkEmailBidKO()
	{
		
		//die;// a retirer <<<-----------------------------------------------|
		$debut = time();
		
		// On fait notre cron toutes les  5 minutes et toutes les minutes entre 15h30 et 16h00
		$les5 = array(00,05,10,15,20,25,30,35,40,45,50,55);
		$minutes = date('i');
		
		$dateDeb = mktime (15,30,0,date("m"),date("d"),date("Y"));
		$dateFin = mktime (16,00,0,date("m"),date("d"),date("Y"));
		//cron 5 min et toutes les minutes de 15h30 à 16h00
		if(in_array($minutes,$les5) || time() >= $dateDeb && time() <= $dateFin)
		{
			// On recup le param
			$settingsControleCheckEmailBidsKO = $this->loadData('settings');
			$settingsControleCheckEmailBidsKO->get('Controle cron checkEmailBidKO','type');
			
			if($settingsControleCheckEmailBidsKO->value == 1)
			{
				// On passe le statut a zero pour signaler qu'on est en cours de traitement
				$settingsControleCheckEmailBidsKO->value = 0;
				$settingsControleCheckEmailBidsKO->update();
			
				// Chargement des datas
				$this->projects = $this->loadData('projects');
				$this->projects_status = $this->loadData('projects_status');
				$this->emprunteur = $this->loadData('clients');
				$this->companies = $this->loadData('companies');
				$this->bids = $this->loadData('bids');
				$this->lenders_accounts = $this->loadData('lenders_accounts');
				$this->preteur = $this->loadData('clients');
				$this->notifications = $this->loadData('notifications');
				$this->wallets_lines = $this->loadData('wallets_lines');
				$this->bids_logs = $this->loadData('bids_logs');
				$this->current_projects_status = $this->loadData('projects_status');
				
				// FB
				$this->settings->get('Facebook','type');
				$lien_fb = $this->settings->value;
				
				// Twitter
				$this->settings->get('Twitter','type');
				$lien_tw = $this->settings->value;
				
				// Heure fin periode funding
				$this->settings->get('Heure fin periode funding','type');
				$this->heureFinFunding = $this->settings->value;
				
				// On recup les bid ko qui n'ont pas de mail envoyé
				$lBidsKO = $this->bids->select('status = 2 AND status_email_bid_ko = 0');
				
				

				foreach($lBidsKO as $e)
				{
					// On check si on a pas de changement en cours de route
					$this->bids->get($e['id_bid'],'id_bid');
					// on recup le statut du projet
					$this->current_projects_status->getLastStatut($e['id_project']);
					
					// si pas de mail est que le projet est statut "enfunding", "fundé", "rembourssement"
					if($this->bids->status_email_bid_ko == '0' && in_array($this->current_projects_status->status,array(50,60,80)))
					{
						
						$this->bids->status_email_bid_ko = 1;
						$this->bids->update();
						
						// On recup les infos du preteur
						$this->lenders_accounts->get($e['id_lender_account'],'id_lender_account');											
						$this->preteur->get($this->lenders_accounts->id_client_owner,'id_client');
						$this->projects->get($e['id_project'],'id_project');
						$this->companies->get($this->projects->id_company,'id_company');
						
						$tab_date_retrait = explode(' ',$this->projects->date_retrait_full);
						$tab_date_retrait = explode(':',$tab_date_retrait[1]);
						$heure_retrait = $tab_date_retrait[0].':'.$tab_date_retrait[1];
						
						if($heure_retrait == '00:00') $heure_retrait = $this->heureFinFunding;
						
						// On recup le temps restant
						$inter = $this->dates->intervalDates(date('Y-m-d H:i:s'),$this->projects->date_retrait.' '.$heure_retrait.':00'); 		
						if($inter['mois']>0) $tempsRest = $inter['mois'].' mois';
						elseif($inter['jours']>0){
							$tempsRest = $inter['jours'].' jours';
							if($inter['jours'] == 1) $tempsRest = $inter['jours'].' jour';
						}
						elseif($inter['heures']>0 && $inter['minutes'] >= 120) $tempsRest = $inter['heures'].' heures';
						elseif($inter['minutes']>0 && $inter['minutes']< 120) $tempsRest = $inter['minutes'].' min';
						else $tempsRest = $inter['secondes'].' secondes';
						
							
						//****************************//
						//*** ENVOI DU MAIL BID KO ***//
						//****************************//
						
						// Motif virement
						$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->preteur->prenom))),0,1);
						$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->preteur->nom)));
						$id_client = str_pad($this->preteur->id_client,6,0,STR_PAD_LEFT);
						$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
						
						$retrait = strtotime($this->projects->date_retrait.' '.$heure_retrait.':00');
						
						if($retrait <= time())
						{
							// Recuperation du modele de mail
							$this->mails_text->get('preteur-bid-ko-apres-fin-de-periode-projet','lang = "'.$this->language.'" AND type');
						}
						else
						{
							// Recuperation du modele de mail
							$this->mails_text->get('preteur-bid-ko','lang = "'.$this->language.'" AND type');
						}
				
						$timedate_bid = strtotime($e['added']);
						$month = $this->dates->tableauMois['fr'][date('n',$timedate_bid)];
				
						// Variables du mailing
						$varMail = array(
						'surl' =>  $this->surl,
						'url' => $this->lurl,
						'prenom_p' => $this->preteur->prenom,
						'valeur_bid' => number_format($e['amount']/100, 2, ',', ' '),
						'taux_bid' => number_format($e['rate'], 2, ',', ' '),
						'nom_entreprise' => $this->companies->name,
						'projet-p' => $this->lurl.'/projects/detail/'.$this->projects->slug,
						'date_bid' => date('d',$timedate_bid).' '.$month.' '.date('Y',$timedate_bid),
						'heure_bid' => $this->dates->formatDate($e['added'],'H\hi'),
						'fin_chrono' => $tempsRest,
						'projet-bid' => $this->lurl.'/projects/detail/'.$this->projects->slug,
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
							Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->preteur->email,$tabFiler);
							// Injection du mail NMP dans la queue
							$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
						}
						else // non nmp
						{
							$this->email->addRecipient(trim($this->preteur->email));
							Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
						}
						//********************************//
						//*** FIN ENVOI DU MAIL BID KO ***//
						//********************************//
						
						
					}
				}
			
				$settingsControleCheckEmailBidsKO->value = 1;
				$settingsControleCheckEmailBidsKO->update();
			}
			$fin = time();	
			echo 'time : '.($fin - $debut);
		}
	}
	
	// a 16 h 10 (10 16 * * *) 
	function _checkFinProjet()
	{
		// Chargement des datas
		$projects = $this->loadData('projects');
		$bids = $this->loadData('bids');
		$loans = $this->loadData('loans');
		$transactions = $this->loadData('transactions');
		$projects_check = $this->loadData('projects_check');
		
		// 60 : fundé | on recup que ceux qui se sont terminé le jour meme
		$lProjets = $projects->selectProjectsByStatus('60',' AND LEFT(p.date_fin,10) = "'.date('Y-m-d').'"');
		
		
		foreach($lProjets as $p)
		{
			if($projects_check->get($p['id_project'],'id_project'))
			{
				
			}
			else
			{
				// BIDS //
				
				// tous les bids
				$montantBidsTotal = $bids->getSoldeBid($p['id_project']);
				// Bids ok
				$montantBidsOK = $bids->sum('id_project = '.$p['id_project'].' AND status = 1','amount');
				$montantBidsOK = ($montantBidsOK/100);
				// bids ko
				$montantBidsKO = $bids->sum('id_project = '.$p['id_project'].' AND status = 2','amount');
				$montantBidsKO = ($montantBidsKO/100);
				
				// LOANS //
				$montantLoans = $loans->sum('id_project = '.$p['id_project'],'amount');
				$montantLoans = ($montantLoans/100);
				
				// TRANSACTIONS //
				
				// total mouvements (encheres + degels)
				$montantTransTotal = $transactions->sum('id_project = '.$p['id_project'].' AND type_transaction = 2','montant');
				$montantTransTotal = str_replace('-','',($montantTransTotal/100));
				// degel
				$montantTransDegel = $transactions->sum('id_project = '.$p['id_project'].' AND type_transaction = 2 AND id_bid_remb != 0','montant');
				$montantTransDegel = ($montantTransDegel/100);
				
				// encheres
				$montantTransEnchere = $transactions->sum('id_project = '.$p['id_project'].' AND type_transaction = 2 AND id_bid_remb != 0','montant');
				$montantTransEnchere = ($montantTransEnchere/100);
				
				// PLUS //
				
				// Difference entre la sommes des bids ok et le montant projet
				$diffMontantBidsEtProjet = str_replace('-','',($montantBidsOK-$p['amount']));
				// Difference entre Bids KO et les degels
				$diffEntreBidsKoEtDegel = ($montantTransEnchere - $montantBidsKO);
				
				$contenu = '';
				$contenu .= '<br>-------- PROJET '.$p['id_project'].' --------<br><br>';
				$contenu .= 'Montant projet : '.$p['amount'].'<br>';
				$contenu .= '<br>--------BIDS--------<br>';
				$contenu .= 'montantBids : '.$montantBidsTotal.'<br>';
				$contenu .= 'montantBidsOK : '.$montantBidsOK.'<br>';
				$contenu .= 'montantBidsKO : '.$montantBidsKO.'<br>';
				$contenu .= '<br>--------LOANS--------<br>';
				$contenu .= 'montantLoans : '.$montantLoans.'<br>';
				$contenu .= '<br>--------TRANSACTIONS--------<br>';
				$contenu .= 'montantTransTotal : '.$montantTransTotal.'<br>';
				$contenu .= 'montantTransDegel : '.$montantTransDegel.'<br>';
				$contenu .= 'montantTransEnchere : '.$montantTransEnchere.'<br>';
				$contenu .= '<br>--------PLUS--------<br>';
				$contenu .= 'diffMontantBidsEtProjet : '.$diffMontantBidsEtProjet.'<br>';
				$contenu .= 'diffEntreBidsKoEtDegel : '.$diffEntreBidsKoEtDegel.'<br>';
				$contenu .= '<br>-------- FIN PROJET '.$p['id_project'].' --------<br>';
				
				$verif_no_good = false;
				
				if($montantTransTotal != $p['amount'])
				{
					$verif_no_good = true;
				}
				if($montantLoans != $p['amount'])
				{
					$verif_no_good = true;
				}
				if($diffEntreBidsKoEtDegel != $diffMontantBidsEtProjet)
				{
					$verif_no_good = true;	
				}
				
				if($verif_no_good == true)
				{
					// MAIL //	
					
					///////////////////////////////////////////
					// Email pour prevenir de la mise a jour //
					///////////////////////////////////////////
					
					
					// multiple recipients
					//$to  = 'aidan@example.com' . ', '; // note the comma
					//$to .= 'wez@example.com';
					$to  = 'unilend@equinoa.fr';
					//$to  = 'd.courtier@relance.fr';
					
					// subject
					$subject = '[ALERTE] Une incoherence est présente dans le projet '.$p['id_project'];
					
					// message
					$message = '
					<html>
					<head>
					  <title>[ALERTE] Une incoherence est présente dans le projet '.$p['id_project'].'</title>
					</head>
					<body>
						<p>[ALERTE] Une incoherence est présente dans le projet '.$p['id_project'].'</p>
						<p>'.$contenu.'</p>
					</body>
					</html>
					';
					
					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					
					// Additional headers
					//$headers .= 'To: Damien <d.courtier@relance.fr>' . "\r\n";
					$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
					$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
					
					// Mail it
					mail($to, $subject, $message, $headers);
					
					$projects_check->status = 2;
					
				}
				// pas d'erreur
				else
				{
					$projects_check->status = 1;
				}
				$projects_check->id_project = $p['id_project'];
				
				$projects_check->create();
			}
		} // fin de la boucle
		
	}
	
	
	// cron journalier (00h00  0 0 * * *) new => 0 * * * * (toutes les heures)
	function _checkControles()
	{
		// Chargement des datas
		$settings = $this->loadData('settings');
		
		// verif sur la date et le value
		$settings->get('Controle cron checkBids','type');
		$ctrlCheckBids = $settings->value;
		$updateCheckBids = $settings->updated;
		
		// verif sur la date et le value
		$settings->get('Controle cron checkEmailBidKO','type');
		$ctrlCheckEmailBidKO = $settings->value;
		$updateCheckEmailBidKO = $settings->updated;
		
		// verif sur le value 1
		$settings->get('Controle cron check_projet_en_funding','type');
		$ctrlCheck_projet_en_funding = $settings->value;
		$updateCheck_projet_en_funding = $settings->updated;
		
		$settings->get('Controle statut remboursement','type');
		$ctrlRemb = $settings->value;
		$updateRemb = $settings->updated;
		
		
		$settings->get('Controle remboursements','type');
		$ctrlRembPreteurs = $settings->value;
		$updateRembPreteurs = $settings->updated;
		
		// Si on a une  valeur a zero
		if($ctrlCheckBids == 0 || $ctrlCheckEmailBidKO == 0 || $ctrlCheck_projet_en_funding == 0 || $ctrlRemb == 0)
		{
			// aujourdhui - 1h
			$todayMoins1h = mktime(date("H")-1,date("i"),date("s"),date("m"),date("d"),date("Y"));
			
			// Si la valeur est a zero et que la derniere mise a jour date de plus d'une heure
			if($ctrlCheckBids == 0 && strtotime($updateCheckBids) < $todayMoins1h)
			{
				//echo 'alerte planté rejet bid';
				mail('unilend@equinoa.fr','[ALERTE] Controle cron checkBids','[ALERTE] Controle cron checkBids plante '.date('Y-m-d H:i:s').' - '.$this->Config['env']);
			}
			// Si la valeur est a zero et que la derniere mise a jour date de plus d'une heure
			if($ctrlCheckEmailBidKO == 0 && strtotime($updateCheckEmailBidKO) < $todayMoins1h)
			{
				//echo 'alerte planté email rejet bid';
				mail('unilend@equinoa.fr','[ALERTE] Controle cron checkEmailBidKO','[ALERTE] Controle cron checkEmailBidKO plante '.date('Y-m-d H:i:s').' - '.$this->Config['env']);
			}
			// si la valeur est a zero
			if($ctrlCheck_projet_en_funding == 0 && strtotime($updateCheck_projet_en_funding) < $todayMoins1h)
			{
				//echo 'alerte planté traitement fin projet';
				mail('unilend@equinoa.fr','[ALERTE] Controle cron check_projet_en_fundings','[ALERTE] Controle cron check_projet_en_fundings plante '.date('Y-m-d H:i:s').' - '.$this->Config['env']);
			}
			if($ctrlRemb == 0)
			{
				//echo 'alerte planté traitement fin projet';
				mail('unilend@equinoa.fr','[ALERTE] Controle statut remboursement','[ALERTE] Controle statut remboursement planté '.date('Y-m-d H:i:s').' - '.$this->Config['env']);
			}
			if($ctrlRembPreteurs == 0)
			{
				//echo 'alerte planté traitement fin projet';
				mail('unilend@equinoa.fr','[ALERTE] Controle remboursements','[ALERTE] Controle statut remboursements des prêteurs a plante '.date('Y-m-d H:i:s').' - '.$this->Config['env']);
			}
			
			
		}
		else
		{
			echo 'OK';	
		}
		die;
	}
	
	// relance une completude a j+8 (add le 22/07/2014)
	// a mettre dans un cron qui passe tous les jours (tous les matin à 6h du matin)
	function _relance_completude()
	{
		
		die;
		$this->clients = $this->loadData('clients');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		
		// time  - 8 jours
		$timeMoins8 = mktime(0,0,0,date("m"),date("d")-8,date("Y"));
		// les preteurs en completude
		$lPreteurs = $this->clients->selectPreteursByStatus('20','','added_status DESC');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		/*echo '<pre>';
		print_r($lPreteurs);
		echo '</pre>';*/
		
		foreach($lPreteurs as $p){
			if($p['added_status'] <= $timeMoins8){
				
				// histo actions
				$this->clients_status_history->get($p['id_client_status_history'],'id_client_status_history');
				
				
				// Recuperation du modele de mail
				$this->mails_text->get('completude','lang = "'.$this->language.'" AND type');
				
				$timeCreate = strtotime($p['added_status']);
				$month = $this->dates->tableauMois['fr'][date('n',$timeCreate)];
		
				// Variables du mailing
				$varMail = array(
				'surl' => $surl,
				'url' => $url,
				'prenom_p' => $p['prenom'],
				'date_creation' => date('d',$timeCreate).' '.$month.' '.date('Y',$timeCreate),
				'content' => $this->clients_status_history->content,
				'lien_fb' => $lien_fb,
				'lien_tw' => $lien_tw);		
				// Construction du tableau avec les balises EMV
				$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
				
				// Attribution des données aux variables
				$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);
				$sujetMail = 'RAPPEL : '.$sujetMail;			
				$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
				$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
				
				// Envoi du mail
				$this->email = $this->loadLib('email',array());
				$this->email->setFrom($this->mails_text->exp_email,$exp_name);
				$this->email->setSubject(stripslashes($sujetMail));
				$this->email->setHTMLBody(stripslashes($texteMail));
				
				if($this->Config['env'] == 'prod') // nmp
				{
					Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$p['email'],$tabFiler);			
					// Injection du mail NMP dans la queue
					$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
				}
				else // non nmp
				{
					$this->email->addRecipient(trim($p['email']));
					Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
				}
				
				// creation du statut "Modification"
				$this->clients_status_history->addStatus('-1',30,$p['id_client'],$this->clients_status_history->content);
			}
		}
		
		
		
	}
	
	// généré à 1h du matin
	function _xmlProjects()
	{
		$projects = $this->loadData('projects');
		$companies = $this->loadData('companies');		
		$bids = $this->loadData('bids');	
		
		$lProjets = $projects->selectProjectsByStatus('50');
		
		//echo 'la';
		
		/*echo '<pre>';
		print_r($lProjets);
		echo '</pre>';*/
		
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<partenaire>';
		
		foreach($lProjets as $p)
		{
			$companies->get($p['id_company'],'id_company');
			
			$monantRecolt = $bids->sum('id_project = '.$p['id_project'].' AND status = 0','amount');
			$monantRecolt = ($monantRecolt/100);
			
			if($monantRecolt > $p['amount']) $monantRecolt = $p['amount'];
			
			$xml .=	'<projet>';
			$xml .= '<reference_partenaire>045</reference_partenaire>';
			$xml .= '<date_export>'.date('Y-m-d').'</date_export>';
			$xml .= '<reference_projet>'.$p['id_project'].'</reference_projet>';
			$xml .= '<impact_social>NON</impact_social>'; 
			$xml .= '<impact_environnemental>NON</impact_environnemental>';
			$xml .= '<impact_culturel>NON</impact_culturel>'; 
			$xml .= '<impact_eco>OUI</impact_eco>'; 
			$xml .= '<mots_cles_nomenclature_operateur></mots_cles_nomenclature_operateur>'; 
			$xml .= '<mode_financement>PRR</mode_financement>';
			$xml .= '<type_porteur_projet>ENT</type_porteur_projet>';
			$xml .= '<qualif_ESS>NON</qualif_ESS>';
			$xml .= '<code_postal>'.$companies->zip.'</code_postal>'; ////////////////////////////////////////
			$xml .= '<ville><![CDATA["'.utf8_encode($companies->city).'"]]></ville>'; 
			$xml .= '<titre><![CDATA["'.$p['title'].'"]]></titre>';
			$xml .= '<description><![CDATA["'.$p['nature_project'].'"]]></description>';
			$xml .= '<url><![CDATA["'.$this->lurl.'/projects/detail/'.$p['slug'].'/?utm_source=TNProjets&utm_medium=Part&utm_campaign=Permanent"]]></url>';
			$xml .= '<url_photo><![CDATA["'.$this->surl.'/var/images/photos_projets/photo_projet_moy_'.$p['photo_projet'].'"]]></url_photo>'; // http://unilend.demo2.equinoa.net/var/images/photos_projets/photo_projet_moy_montage.png
			$xml .= '<date_debut_collecte>'.$p['date_publication'].'</date_debut_collecte>';
			$xml .= '<date_fin_collecte>'.$p['date_retrait'].'</date_fin_collecte>';
			$xml .= '<montant_recherche>'.$p['amount'].'</montant_recherche>';
			$xml .= '<montant_collecte>'.number_format($monantRecolt, 0, ',', '').'</montant_collecte>';
			$xml .= '</projet>';
		}
		$xml .= '</partenaire>';
		//echo $this->spath.'fichiers/045.xml';
		file_put_contents($this->spath.'fichiers/045.xml',$xml);
		//header("Content-Type: application/xml; charset=utf-8");
		//echo $xml;
		die;
	}
}
