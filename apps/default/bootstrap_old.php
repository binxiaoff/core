<?php

class bootstrap extends Controller
{
	var $Command;
	
	function bootstrap(&$command,$config,$app)
	{
		parent::__construct(&$command,$config,$app);
		
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
		$this->users = $this->loadData('users');
		
		// Chargement des librairies
		$this->ficelle = $this->loadLib('ficelle');
		$this->photos = $this->loadLib('photos',array($this->spath,$this->surl));
		$this->tnmp = $this->loadLib('tnmp',array($this->nmp,$this->nmp_desabo,$this->Config['env']));
		$this->dates = $this->loadLib('dates');
		$this->Web2Pdf = $this->loadLib('Web2Pdf');
		
		// Recuperation de la liste des langue disponibles
		$this->lLangues = $this->Config['multilanguage']['allowed_languages'];
		
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
		$this->loadJs('default/functions');
		$this->loadJs('default/main');
		$this->loadJs('default/ajax');
		
		// Lutte contre le XSS
		if(is_array($_POST)) {
			 foreach($_POST as $key => $value) {
				  $_POST[$key] = strip_tags($value);
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
		
		
		// Récuperation du menu footer
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
		
		// On recupere le formulaire de connexion s'il est passé
		if($this->clients->handleLogin('connect','login','password'))
		{
			$this->clients_history->id_client = $_SESSION['client']['id_client'];
			$this->clients_history->type = $_SESSION['client']['status_pre_emp'];
			$this->clients_history->status = 1; // statut login
			$this->clients_history->create();
			
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
			
			if($statut_preteur == true)
			{
				// Si on est en cours d'inscription on redirige sur le form
				if($_SESSION['client']['etape_inscription_preteur'] < 3){
					$etape = ($_SESSION['client']['etape_inscription_preteur']+1);
					header('Location:'.$this->lurl.'/inscription_preteur/etape'.$etape);
					die;
				}
				else{
					// on check le statut du preteur
					$this->clients_status->getLastStatut($_SESSION['client']['id_client']);
					if(in_array($this->clients_status->status,array(20,30))){
						
						if(in_array($_SESSION['client']['type'],array(1,3))) $lapage = 'particulier_doc';
						else $lapage = 'societe_doc';
						
						header('Location:'.$this->lurl.'/profile/'.$lapage);
						die;	
					}
					/*elseif($this->clients_status->status < 60){
						header('Location:'.$this->lurl.'/profile');
						die;
					}*/
					else{
						header('Location:'.$this->lurl.'/synthese');
						die;
					}
				}
			}
			
		}
		elseif(isset($_POST['login']) && isset($_POST['password']))
		{
			$this->error_login = $this->lng['header']['identifiant-ou-mot-de-passe-inccorect'];
			
		}
		
		$this->connect_ok = false;
		if($this->clients->checkAccess())
		{
			$this->connect_ok = true;
			
			// On recupere les infos du client si il est connecté
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
			}
			
			/////////////////////////////////////////////////
			
			
			// Solde du compte preteur
			$this->solde = $this->transactions->getSolde($this->clients->id_client);
			
			
		}
		
		// page projet tri
		// 1 : terminé bientot
		// 2 : nouveauté
		
		
		$this->tabOrdreProject = array (
		'',
		'lestatut ASC, IF(lestatut = 2, p.date_retrait ,"") DESC, IF(lestatut = 1, p.date_retrait ,"") ASC,status DESC',
		'p.date_publication DESC');
		
		
		// Afficher les projets terminés ? (1 : oui | 0 : non)
		$this->settings->get('Afficher les projets termines','type');
		if($this->settings->value == 1) $this->tabProjectDisplay = '50,60,70,80,90,100,110';
		else $this->tabProjectDisplay = '50';
		
		// Récup du lien fb
		$this->settings->get('Facebook','type');
		$this->like_fb = $this->settings->value;
		
		// Récup du lien Twitter
		$this->settings->get('Twitter','type');
		$this->twitter = $this->settings->value;
		
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
			
			// Variable pour savoir s'il a trouvé un p
			$getta = false;
			
			$i = 0;			
			foreach($params as $p)
			{

				// Si on detecte un p en params
				if($p == 'p')
				{
					
					// Youpi il a trouvé
					$getta = true;
					
					$indexPart = $i + 1;
					
					
					// On regarde si on trouve un partenaire
					if($partenaires->get($params[$indexPart],'hash'))
					{
						
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
			
			// Si il a rien trouvé on regarde si on a un cookie et pas de session
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