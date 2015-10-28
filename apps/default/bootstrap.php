<?

class bootstrap extends Controller
{
	var $Command;
	
	function bootstrap($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		// Mise en session de l'url demand�e pour un retour si deconnect� sauf pour la fonction login du controller root
		if($this->current_function != 'login') { $_SESSION['redirection_url'] = $_SERVER['REQUEST_URI']; }
		
		// Chargement des librairies
		$this->upload = $this->loadLib('upload');
		
		// Chargement des datas
		$this->settings = $this->loadData('settings');
		$this->tree_elements = $this->loadData('tree_elements');
		$this->blocs_elements = $this->loadData('blocs_elements');
		$this->tree = $this->loadData('tree',array('url'=>$this->url,'surl'=>$this->surl,'tree_elements'=>$this->tree_elements,'blocs_elements'=>$this->blocs_elements,'upload'=>$this->upload,'spath'=>$this->spath));
		$this->templates = $this->loadData('templates');
		$this->elements = $this->loadData('elements');
		$this->blocs_templates = $this->loadData('blocs_templates');
		$this->blocs = $this->loadData('blocs');
		$this->mails_filer = $this->loadData('mails_filer');
		$this->mails_text = $this->loadData('mails_text');
		$this->ln = $this->loadData('textes');
		//$this->produits_elements = $this->loadData('produits_elements');
		//$this->produits = $this->loadData('produits',array('url'=>$this->url,'surl'=>$this->surl,'produits_elements'=>$this->produits_elements,'upload'=>$this->upload,'spath'=>$this->spath));
		//$this->produits_images = $this->loadData('produits_images');
		//$this->produits_details = $this->loadData('produits_details');
		$this->routages = $this->loadData('routages',array('url'=>$this->lurl,'route'=>$this->Config['route_url']));
		$this->nmp = $this->loadData('nmp');	
		$this->nmp_desabo = $this->loadData('nmp_desabo');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->clients_history = $this->loadData('clients_history');
		$this->villes = $this->loadData('villes');
		$this->transactions = $this->loadData('transactions');
		$this->clients_history_actions = $this->loadData('clients_history_actions');
		$this->clients_status = $this->loadData('clients_status');
		$this->login_log = $this->loadData('login_log');
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		$this->convert_api_compteur = $this->loadData('convert_api_compteur');
		$this->accept_cookies = $this->loadData('accept_cookies');
		
		// Chargement des librairies
		$this->ficelle = $this->loadLib('ficelle');
		$this->photos = $this->loadLib('photos',array($this->spath,$this->surl));
		$this->tnmp = $this->loadLib('tnmp',array($this->nmp,$this->nmp_desabo,$this->Config['env']));
		$this->dates = $this->loadLib('dates');
		$this->Web2Pdf = $this->loadLib('Web2Pdf',$this->convert_api_compteur);
		
		// Recuperation de la liste des langue disponibles
		$this->lLangues = $this->Config['multilanguage']['allowed_languages'];
		//unset($_SESSION['utm_source']);
		
		
		// Formulaire de modification d'un texte de traduction
		if(isset($_POST['form_mod_traduction']))
		{
			foreach($this->lLangues as $key => $lng)
			{
				$values[$key] = $_POST['texte-'.$key];	
			}
			
			$this->ln->updateTextTranslations($_POST['section'],$_POST['nom'],$values);
		}
		
		// Chargement des fichiers CSS
		//$this->loadCss('../scripts/default/colorbox/colorbox');
		$this->loadCss('default/izicom');
		$this->loadCss('default/colorbox');
		$this->loadCss('default/fonts');
		$this->loadCss('default/jquery.c2selectbox');
		$this->loadCss('default/jquery-ui-1.10.3.custom');
		$this->loadCss('default/custom-theme/jquery-ui-1.10.3.custom');//datepicker
		$this->loadCss('default/style',0,'all','css',date('Ymd')); // permet d'avoir un nouveau cache de js par jour chez l'utilisateur
		$this->loadCss('default/style-edit',0,'all','css',date('Ymd'));
		
		
		// Chargement des fichier JS
		$this->loadJs('default/jquery/jquery-1.10.2.min');
		//$this->loadJs('default/colorbox/jquery.colorbox-min');
		$this->loadJs('default/bootstrap-tooltip');
		$this->loadJs('default/jquery.carouFredSel-6.2.1-packed');
		$this->loadJs('default/jquery.c2selectbox');
		$this->loadJs('default/livevalidation_standalone.compressed');
		$this->loadJs('default/jquery.colorbox-min');
		$this->loadJs('default/jquery-ui-1.10.3.custom.min');
		$this->loadJs('default/jquery-ui-1.10.3.custom2');
		$this->loadJs('default/ui.datepicker-fr');
		$this->loadJs('default/highcharts.src');
		$this->loadJs('default/functions',0,date('Ymd'));
		$this->loadJs('default/main',0,date('YmdH'));
		$this->loadJs('default/ajax',0,date('Ymd'));
		
		// Lutte contre le XSS
		if(is_array($_POST)) {
			 foreach($_POST as $key => $value) {
				  $_POST[$key] = htmlspecialchars(strip_tags($value));
			 }
		}
		
		// Mise en tableau de l'url
		$urlParams = explode('/',$_SERVER['REQUEST_URI']);
		
		// On sniff le partenaire
		$this->handlePartenaire($urlParams);
		
		// Recuperation du code Google Webmaster Tools
		$this->settings->get('Google Webmaster Tools','type');
		$this->google_webmaster_tools = $this->settings->value;
		
		// Recuperation du code Google Analytics
		$this->settings->get('Google Analytics','type');
		$this->google_analytics = $this->settings->value;
		
		// Recuperation de la Baseline Title
		$this->settings->get('Baseline Title','type');
		$this->baseline_title = $this->settings->value;
		
		// super login //
		
		/////////////////
		
		
		
		// R�cuperation du menu footer
		$this->menuFooter = $this->tree->getMenu('footer',$this->language,$this->lurl);
		
		$this->navFooter1 = $this->tree->getMenu('footer-nav-1',$this->language,$this->lurl);
		$this->navFooter2 = $this->tree->getMenu('footer-nav-2',$this->language,$this->lurl);
		$this->navFooter3 = $this->tree->getMenu('footer-nav-3',$this->language,$this->lurl);
		$this->navFooter4 = $this->tree->getMenu('footer-nav-4',$this->language,$this->lurl);
		
		// Notes
		$this->lNotes = array(
		'A' => 'etoile1',
		'B' => 'etoile2',
		'C' => 'etoile3',
		'D' => 'etoile4',
		'E' => 'etoile5',
		'F' => 'etoile6',
		'G' => 'etoile7',
		'H' => 'etoile8',
		'I' => 'etoile9',
		'J' => 'etoile10');
		
		// Recuperation du bloc nos-partenaires
		$this->blocs->get('nos-partenaires','slug');
		$lElements = $this->blocs_elements->select('id_bloc = '.$this->blocs->id_bloc.' AND id_langue = "'.$this->language.'"');
		foreach($lElements as $b_elt)
		{
			$this->elements->get($b_elt['id_element']);
			$this->bloc_partenaires[$this->elements->slug] = $b_elt['value'];
			$this->bloc_partenairesComplement[$this->elements->slug] = $b_elt['complement'];	
		}
		
		//Recuperation des element de traductions
		$this->lng['header'] = $this->ln->selectFront('header',$this->language,$this->App);
		$this->lng['footer'] = $this->ln->selectFront('footer',$this->language,$this->App);
		$this->lng['home'] = $this->ln->selectFront('home',$this->language,$this->App);
		
		
		//gestion du captcha
		if(isset($_POST["captcha"])){
			if (isset($_SESSION["securecode"]) && $_SESSION["securecode"]==strtolower($_POST["captcha"])){
				$content_captcha = 'ok';
			}
			else{
				$content_captcha = 'ko';
				$this->displayCaptchaError = true;
			}
		}
		
		// Connexion
		if(isset($_POST['login']) && isset($_POST['password']))
		{
			$this->login = $_POST['login'];
			$this->passsword = $_POST['password'];
			
			
			
			
			// SI on a le captcha d'actif, et qu'il est faux, on bloque avant tout pour ne pas laisser de piste sur le couple login/mdp
			if(isset($_POST["captcha"]) && $content_captcha == "ko"){	   
				//on trace la tentative
				$this->login_log = $this->loadData('login_log');
				$this->login_log->pseudo = $_POST['login'];
				$this->login_log->IP = $_SERVER["REMOTE_ADDR"];
				$this->login_log->date_action = date('Y-m-d H:i:s');
				$this->login_log->statut = 0;
				$this->login_log->retour = 'erreur captcha';
				$this->login_log->create();
				
				$_SESSION['login']['displayCaptchaError'] = $this->displayCaptchaError;
			}
			else{                                      
				$no_error = true;
				$captcha_vrai = true;
			
				if($_POST['login'] == '' || $_POST['password'] == ''){
					$no_error = false;
				}                                                         
				elseif($this->clients->exist($_POST['login'],'email') == false){
					$no_error = false;
				}
				elseif($this->clients->login($_POST['login'],$_POST['password']) == false){
					$no_error = false;
				}
				
				// Si erreur on affiche le message
				if($no_error)
				{
					// On recupere le formulaire de connexion s'il est pass�
					if($this->clients->handleLogin('connect','login','password'))
					{
						//vidage des trackeurs d'echec en session
						unset($_SESSION['login']);
						
						$this->clients_history->id_client = $_SESSION['client']['id_client'];
						$this->clients_history->type = $_SESSION['client']['status_pre_emp'];
						$this->clients_history->status = 1; // statut login
						$this->clients_history->create();
						
						if(isset($_COOKIE['acceptCookies'])){
							$this->create_cookies = false;
							
							if($this->accept_cookies->get($_COOKIE['acceptCookies'],'id_client = 0 AND id_accept_cookies'))
							{
								$this->accept_cookies->id_client = $_SESSION['client']['id_client'];
								$this->accept_cookies->update();
							}
						}
						
						$statut_preteur = false;
						
						// On renvoi chez le preteur
						if($_SESSION['client']['status_pre_emp'] == 1){
							$statut_preteur = true;
						}
						// On renvoi chez l'emprunteur
						elseif($_SESSION['client']['status_pre_emp'] == 2){
							header('Location:'.$this->lurl.'/synthese_emprunteur');
							die;
						}
						elseif($_SESSION['client']['status_pre_emp'] == 3){
							$_SESSION['status_pre_emp'] = 1;
							$statut_preteur = true;
						}
						
						// On est un preteur
						if($statut_preteur == true)
						{
							
							/// creation de champs en bdd pour la gestion des mails de notifiaction ////
							
							$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
							$this->clients_gestion_type_notif = $this->loadData('clients_gestion_type_notif');
							
							////// Liste des notifs //////
							$this->lTypeNotifs = $this->clients_gestion_type_notif->select();
							$this->lNotifs = $this->clients_gestion_notifications->select('id_client = '.$_SESSION['client']['id_client']);
							
							if($this->lNotifs == false){
								foreach($this->lTypeNotifs as $n){
									$this->clients_gestion_notifications->id_client = $_SESSION['client']['id_client'];
									$this->clients_gestion_notifications->id_notif = $n['id_client_gestion_type_notif'];
									$id_notif = $n['id_client_gestion_type_notif'];
									// immediatement
									if(in_array($id_notif,array(3,6,7,8,9)))
										$this->clients_gestion_notifications->immediatement = 1;
									else 
										$this->clients_gestion_notifications->immediatement = 0;
									// quotidienne
									if(in_array($id_notif,array(1,2,4,5)))
										$this->clients_gestion_notifications->quotidienne	= 1;
									else
										$this->clients_gestion_notifications->quotidienne	= 0;
									// hebdomadaire
									if(in_array($id_notif,array(1,4)))
										$this->clients_gestion_notifications->hebdomadaire	= 1;
									else
										$this->clients_gestion_notifications->hebdomadaire	= 0;
									// mensuelle
									$this->clients_gestion_notifications->mensuelle			= 0;
									$this->clients_gestion_notifications->create();
								}
							}
							
							////////////////////////////////////////////////////////////////////////////
							
							// Si on est en cours d'inscription on redirige sur le formulaire d'inscription
							if($_SESSION['client']['etape_inscription_preteur'] < 3){
								$etape = ($_SESSION['client']['etape_inscription_preteur']+1);
								header('Location:'.$this->lurl.'/inscription_preteur/etape'.$etape);
								die;
							}
							// Sinon
							else{
								
								// on check le statut du preteur pour voir si on doit le rediriger sur la page des doc � uploader
								$this->clients_status->getLastStatut($_SESSION['client']['id_client']);
								if(in_array($this->clients_status->status,array(20,30))){
									
									if(in_array($_SESSION['client']['type'],array(1,3))) $lapage = 'particulier_doc';
									else $lapage = 'societe_doc';
			
									header('Location:'.$this->lurl.'/profile/'.$lapage);
									die;	
								}
								// Sinon
								else{
									
									// On regarde le CGU du preteur pour voir si il est sign� ou pas (cgv particulier et entreprise ont le mm contenu)
									$this->clients->get($_SESSION['client']['id_client'],'id_client');
									// cgu societe
									if(in_array($this->clients->type,array(2,4))){
										$this->settings->get('Lien conditions generales inscription preteur societe','type');
										$this->lienConditionsGenerales = $this->settings->value;
									}
									// cgu particulier
									else{
										$this->settings->get('Lien conditions generales inscription preteur particulier','type');
										$this->lienConditionsGenerales = $this->settings->value;
									}
									
									// liste des cgv accpet�
									$listeAccept = $this->acceptations_legal_docs->selectAccepts('id_client = '.$this->clients->id_client);
									
									// On cherche si on a d�j� le cgv (si pas sign� on redirige sur la page synthese pour qu'il signe)
									if(!in_array($this->lienConditionsGenerales,$listeAccept)){
										header('Location:'.$this->lurl.'/synthese');
										die;
									}
									// Sinon
									else{
										// On va sur la session de redirection
										if(isset($_SESSION['redirection_url']) && $_SESSION['redirection_url'] != '' && $_SESSION['redirection_url'] != 'login' && $_SESSION['redirection_url'] != 'captcha')
										{
											// on redirige que si on vient de projects
											$redirect = explode('/',$_SESSION['redirection_url']);
											if($redirect[1] == 'projects'){
												header('location:'.$_SESSION['redirection_url']);
												die;
											}
											else{
												header('Location:'.$this->lurl.'/synthese');
												die;
											}
										}
										// Sinon page synthese si pas de session
										else{
											header('Location:'.$this->lurl.'/synthese');
											die;
										}
									}
	
								}

							}
							
							
						}
						
					}
					else{
						$this->error_login = $this->lng['header']['identifiant-ou-mot-de-passe-inccorect'];
					}
				}
				else{
					/* A chaque tentative on double le temps d'attente entre 2 demande.
					
					- tentative 2 = 1seconde d'attente
					- tentative 3 = 2 sec
					- tentative 4 = 4 sec 
					- etc...
					
					Au bout de 10 demandes (avec la m�me IP) DANS LES 10min
					- Ajout d'un captcha + @ admin                                                             
					
					*/
					
					// H - 10min
					$h_moins_dix_min = date('Y-m-d H:i:s', mktime(date('H'), date('i')-10, 0, date('m'), date('d'), date('Y')));

					//on r�cup�re le nombre de tentative d�j� faite avec l'ip du user
					$this->login_log = $this->loadData('login_log');
					$this->nb_tentatives_precedentes = $this->login_log->counter('IP = "'.$_SERVER["REMOTE_ADDR"].'" AND date_action >= "'.$h_moins_dix_min.'" AND statut = 0');
					
					$this->duree_waiting = 0;
					
					//parametrage de la boucle de temps
					$coef_multiplicateur = 2;
					$resultat_precedent = 1;
					
					if($this->nb_tentatives_precedentes>0 && $this->nb_tentatives_precedentes < 1000) // 1000 pour ne pas bloquer le site
					{
						for($i= 1;$i <= $this->nb_tentatives_precedentes; $i++){
							$this->duree_waiting = $resultat_precedent * $coef_multiplicateur;
							$resultat_precedent = $this->duree_waiting;
						}
					}
					
					// DEBUG
					//$this->duree_waiting = 1;
																			 
					//retour
					$this->error_login = $this->lng['header']['identifiant-ou-mot-de-passe-inccorect'];
					
					//mise en session
					$_SESSION['login']['duree_waiting'] = $this->duree_waiting;
					$_SESSION['login']['nb_tentatives_precedentes'] = $this->nb_tentatives_precedentes;
					$_SESSION['login']['displayCaptchaError'] = $this->displayCaptchaError;
					
					
					
					//on trace la tentative
					$this->login_log = $this->loadData('login_log');
					$this->login_log->pseudo = $_POST['login'];
					$this->login_log->IP = $_SERVER["REMOTE_ADDR"];
					$this->login_log->date_action = date('Y-m-d H:i:s');
					$this->login_log->statut = 0;
					$this->login_log->retour = $this->error_login;
					$this->login_log->create();
					
				}
			} // end if isset($_POST["captcha"])
		}// fin login

		//print_r($_SESSION['login']);
		// On recupere le formulaire de connexion s'il est pass�
		//if($this->clients->handleLogin('connect','login','password'))
//		{
//			
//			
//			$this->clients_history->id_client = $_SESSION['client']['id_client'];
//			$this->clients_history->type = $_SESSION['client']['status_pre_emp'];
//			$this->clients_history->status = 1; // statut login
//			$this->clients_history->create();
//			
//			$statut_preteur = false;
//			
//			// On renvoi chez le preteur
//			if($_SESSION['client']['status_pre_emp'] == 1){
//				$statut_preteur = true;
//			}
//			// On renvoi chez l'emprunteur
//			elseif($_SESSION['client']['status_pre_emp'] == 2){
//				header('Location:'.$this->lurl.'/synthese_emprunteur');
//				die;
//			}
//			elseif($_SESSION['client']['status_pre_emp'] == 3){
//				$_SESSION['status_pre_emp'] = 1;
//				$statut_preteur = true;
//			}
//			
//			if($statut_preteur == true)
//			{
//				
//				// Si on est en cours d'inscription on redirige sur le form
//				if($_SESSION['client']['etape_inscription_preteur'] < 3){
//					$etape = ($_SESSION['client']['etape_inscription_preteur']+1);
//					header('Location:'.$this->lurl.'/inscription_preteur/etape'.$etape);
//					die;
//				}
//				else{
//					
//					
//					
//					// on check le statut du preteur
//					$this->clients_status->getLastStatut($_SESSION['client']['id_client']);
//					if(in_array($this->clients_status->status,array(20,30))){
//						
//						if(in_array($_SESSION['client']['type'],array(1,3))) $lapage = 'particulier_doc';
//						else $lapage = 'societe_doc';
//
//						header('Location:'.$this->lurl.'/profile/'.$lapage);
//						die;	
//					}
//					/*elseif($this->clients_status->status < 60){
//						header('Location:'.$this->lurl.'/profile');
//						die;
//					}*/
//					else{
//						header('Location:'.$this->lurl.'/synthese');
//						die;
//					}
//				}
//				
//				
//			}
//			
//		}
//		elseif(isset($_POST['login']) && isset($_POST['password']))
//		{
//			$this->error_login = $this->lng['header']['identifiant-ou-mot-de-passe-inccorect'];
//			
//		}
		
		$this->connect_ok = false;
		if($this->clients->checkAccess())
		{
			$this->connect_ok = true;
			
			// On recupere les infos du client si il est connect�
			$this->clients->get($_SESSION['client']['id_client'],'id_client');
			$this->clients_adresses->get($this->clients->id_client,'id_client');
			
			// si emprunteur
			if($this->clients->status_pre_emp>1)
			{
				// si si statut transition = 1 c'est qu'on est en transition
				if($this->clients->status_transition == 1)
				{
					$this->etape_transition = true;	
				}
				
				$this->companies = $this->loadData('companies');
				$this->projects = $this->loadData('projects');
				$this->companies->get($this->clients->id_client,'id_client_owner');
				$this->nbProjets = $this->projects->countSelectProjectsByStatus('30,50,60,70,80',' AND id_company = '. $this->companies->id_company.' AND p.status = 0 AND p.display = 0');	
				
				/////////////////////////////////////////////////
				
				// pour les emprunteurs donc satut 2 ou 3
				// Lien conditions generales depot dossier
				$this->settings->get('Lien conditions generales depot dossier','type');
				$this->cguDepotDossier = $this->settings->value;
				
				// Recuperation du contenu de la page
				$contenu = $this->tree_elements->select('id_tree = "'.$this->cguDepotDossier.'" AND id_langue = "'.$this->language.'"');
				foreach($contenu as $elt)
				{
					$this->elements->get($elt['id_element']);
					$this->contentCGUDepotDossier[$this->elements->slug] = $elt['value'];
					$this->complementCGUDepotDossier[$this->elements->slug] = $elt['complement'];
				}
				
				/////////////////////////////////////////////////
			}
			
			/////////////////////////////////////////////////
			
			// preteur ou les deux mais pas que les emprunteurs
			if($this->clients->status_pre_emp == 1 || $this->clients->status_pre_emp == 3)
			{
				
				// particulier
				if($this->clients->type == 1)
				{
					// cgu particulier
					$this->settings->get('Lien conditions generales inscription preteur particulier','type');
					$this->lienConditionsGenerales = $this->settings->value;
				}
				// morale
				else
				{
					// cgu societe
					$this->settings->get('Lien conditions generales inscription preteur societe','type');
					$this->lienConditionsGenerales = $this->settings->value;
				}
				
				// Recuperation du contenu de la page
				$contenu = $this->tree_elements->select('id_tree = "'.$this->lienConditionsGenerales.'" AND id_langue = "'.$this->language.'"');
				foreach($contenu as $elt)
				{
					$this->elements->get($elt['id_element']);
					$this->contentCGU[$this->elements->slug] = $elt['value'];
					$this->complementCGU[$this->elements->slug] = $elt['complement'];
				}
				
				
				// DATAS
				$this->lenders_accounts = $this->loadData('lenders_accounts');
				$this->notifications = $this->loadData('notifications');
				$this->bids = $this->loadData('bids');
				$this->projects_notifs = $this->loadData('projects');
				$this->companies_notifs = $this->loadData('companies');
				$this->loans = $this->loadData('loans');
				
				// trad
				$this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese',$this->language,$this->App);
				$this->lng['notifications'] = $this->ln->selectFront('preteur-notifications',$this->language,$this->App);
				
				
				$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
				
				$this->nbNotifdisplay = 10;
				
				$this->lNotifHeader = $this->notifications->select('id_lender = '.$this->lenders_accounts->id_lender_account,'added DESC',0,$this->nbNotifdisplay);
				$this->NbNotifHeader = $this->notifications->counter('id_lender = '.$this->lenders_accounts->id_lender_account.' AND status = 0');
				
				$this->NbNotifHeaderEnTout = $this->notifications->counter('id_lender = '.$this->lenders_accounts->id_lender_account);
				
				
				
			}
			
			/////////////////////////////////////////////////
			
			
			// Solde du compte preteur
			$this->solde = $this->transactions->getSolde($this->clients->id_client);
			
			
		}
		
		// page projet tri
		// 1 : termin� bientot
		// 2 : nouveaut�
		
		
		$this->tabOrdreProject = array (
		'',
		'lestatut ASC, IF(lestatut = 2, p.date_retrait ,"") DESC, IF(lestatut = 1, p.date_retrait ,"") ASC,status DESC',
		'p.date_publication DESC');
		
		
		// Afficher les projets termin�s ? (1 : oui | 0 : non)
		$this->settings->get('Afficher les projets termines','type');
		if($this->settings->value == 1) $this->tabProjectDisplay = '50,60,70,80,90,100,110,120,130,150,160,170';
		else $this->tabProjectDisplay = '50';
		
		// R�cup du lien fb
		$this->settings->get('Facebook','type');
		$this->like_fb = $this->settings->value;
		
		// R�cup du lien Twitter
		$this->settings->get('Twitter','type');
		$this->twitter = $this->settings->value;
		
		// lien page cookies (id_tree)
		$this->settings->get('id page cookies','type');
		$this->id_tree_cookies = $this->settings->value;
		
		$this->create_cookies = true;
		if(isset($_COOKIE['acceptCookies'])){
			$this->create_cookies = false;
		}
		
		if($this->lurl == 'http://prets-entreprises-unilend.capital.fr'){
			
			if($command->Name == 'root' && $command->Function == 'capital'){
				//echo 'ok';	
			}
			elseif($command->Name == 'root' && $command->Function == 'default'){
				//echo 'ok';	
			}
			elseif($command->Name == 'projects' && $command->Function == 'detail'){
				//echo 'ok';	
			}
			elseif($command->Name == 'ajax'){
				//echo 'ok';	
			}
			else{
				header('location: http://prets-entreprises-unilend.capital.fr/capital/');
				die;
			}
			
			//print_r($command);
		}
		elseif($this->lurl == 'http://partenaire.unilend.challenges.fr'){
			
			if($command->Name == 'root' && $command->Function == 'challenges'){
				//echo 'ok';	
			}
			elseif($command->Name == 'root' && $command->Function == 'default'){
				//echo 'ok';	
			}
			elseif($command->Name == 'projects' && $command->Function == 'detail'){
				//echo 'ok';	
			}
			elseif($command->Name == 'ajax'){
				//echo 'ok';	
			}
			else{
				header('location: http://partenaire.unilend.challenges.fr/challenges/');
				die;
			}
			
			//print_r($command);
		}
		elseif($this->lurl == 'http://lexpress.unilend.fr')
                {

			if($command->Name == 'root' && $command->Function == 'lexpress'){
				//echo 'ok';	
			}
			elseif($command->Name == 'root' && $command->Function == 'default'){
				// voir dans root autres restrictions
			}
			else{
				
				header('location: '.$this->surl);
				die;
			}
		}
                elseif($this->lurl == 'http://pret-entreprise.votreargent.lexpress.fr')
                {

			if($command->Name == 'root' && $command->Function == 'lexpress'){
				//echo 'ok';	
			}
			elseif($command->Name == 'root' && $command->Function == 'default'){
				// voir dans root autres restrictions
			}
			else{
				
				header('location: '.$this->surl);
				die;
			}
		}
                elseif($this->lurl == 'http://emprunt-entreprise.lentreprise.lexpress.fr')
                {

			if($command->Name == 'root' && $command->Function == 'lexpress'){
				//echo 'ok';	
			}
			elseif($command->Name == 'root' && $command->Function == 'default'){
				// voir dans root autres restrictions
			}
			else{
				
				header('location: '.$this->surl);
				die;
			}
		}
		
		
	}
	
	function handlePartenaire($params)
	{
		// Chargement des datas
		$partenaires = $this->loadData('partenaires');
		$promotions = $this->loadData('promotions');
		$partenaires_clics = $this->loadData('partenaires_clics');
		
		// On check les params pour voir si on a un partenaire
		if(count($params) > 0)
		{
			
			// Variable pour savoir s'il a trouv� un p
			$getta = false;
			
			$i = 0;			
			foreach($params as $p)
			{

				// Si on detecte un p en params
				if($p == 'p')
				{
					
					// Youpi il a trouv�
					$getta = true;
					
					$indexPart = $i + 1;
					
					
					// On regarde si on trouve un partenaire
					if($partenaires->get($params[$indexPart],'hash'))
					{
						// on controle qu'on a pas un double clique
						if(!isset($_SESSION['partenaire_click'][$partenaires->id_partenaire]) || $_SESSION['partenaire_click'][$partenaires->id_partenaire] != $partenaires->id_partenaire)
						{
							$_SESSION['partenaire_click'][$partenaires->id_partenaire] = $partenaires->id_partenaire;
						
							// On ajoute un clic
							if($partenaires_clics->get(array('id_partenaire'=>$partenaires->id_partenaire,'date'=>date('Y-m-d'))))
							{
								$partenaires_clics->nb_clics = $partenaires_clics->nb_clics + 1;
								$partenaires_clics->update(array('id_partenaire'=>$partenaires->id_partenaire,'date'=>date('Y-m-d')));
							}
							else
							{
								$partenaires_clics->id_partenaire = $partenaires->id_partenaire;
								$partenaires_clics->date = date('Y-m-d');
								$partenaires_clics->ip_adress = $_SERVER['REMOTE_ADDR'];
								$partenaires_clics->nb_clics = 1;
								$partenaires_clics->create(array('id_partenaire'=>$partenaires->id_partenaire,'date'=>date('Y-m-d')));
							}
						}
						
						// On met le partenaire en session
						$_SESSION['partenaire']['id_partenaire'] = $partenaires->id_partenaire;
						
						// On regarde si on a un code promo actif
						if($promotions->get($partenaires->id_code,'id_code'))
						{
							// On ajoute le code en session
							$_SESSION['partenaire']['code_promo'] = $promotions->code;
							$_SESSION['partenaire']['id_promo'] = $promotions->id_code;
						}
						else
						{
							unset($_SESSION['partenaire']['code_promo']);
							unset($_SESSION['partenaire']['id_promo']);
						}
						
						
						// On enregistre le partenaire en cookie
						setcookie('izicom_partenaire',$partenaires->hash,time() + 3153600,'/');
						
						// On regarde si le dernier param commence par ?
						if(substr($params[count($params)-1],0,1) == '?')
						{
							$gogole = $params[count($params)-1];
						}
						
						// On rebidouille l'url
						$params = array_slice($params,0,count($params)-3);
						$reeurl = implode('/',$params);
						
						// On renvoi
						header('Location:'.$this->url.$reeurl.($gogole!=''?'/'.$gogole:''));
						die;
					}
				}
				
				$i++;
			}
			
			// Si il a rien trouv� on regarde si on a un cookie et pas de session
			if(!isset($_SESSION['partenaire']['id_partenaire']) && isset($_COOKIE['izicom_partenaire']) && !$getta)
			{
				// On regarde si on trouve toujours un partenaire
				if($partenaires->get($_COOKIE['izicom_partenaire'],'hash'))
				{
					// On met le partenaire en session
					$_SESSION['partenaire']['id_partenaire'] = $partenaires->id_partenaire;
				}
			}			
		}
	}
}