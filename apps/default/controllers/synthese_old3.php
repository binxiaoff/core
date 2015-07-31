<?php

class syntheseController extends bootstrap
{
	var $Command;
	
	function syntheseController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		//var_dump($_SESSION['client']);
			//die;
		
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
		
		//Recuperation des element de traductions
		$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
		$this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese',$this->language,$this->App);
		
		// Heure fin periode funding
		$this->settings->get('Heure fin periode funding','type');
		$this->heureFinFunding = $this->settings->value;
	
		$this->page = 'synthese';
		
	}
	
	function _default()
	{
		// css
		$this->loadCss('default/synthese');
		
		// Chargement des datas
		$this->transactions = $this->loadData('transactions');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->projects = $this->loadData('projects');
		$this->favoris = $this->loadData('favoris');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->favoris = $this->loadData('favoris');
		$this->bids = $this->loadData('bids');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->projects_status = $this->loadData('projects_status');
		$this->notifications = $this->loadData('notifications');
		$this->clients_status = $this->loadData('clients_status');
		$this->clients_status_history = $this->loadData('clients_status_history');
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		
		// Recuperation du bloc nos-partenaires
		$this->blocs->get('cgv','slug');
		$lElements = $this->blocs_elements->select('id_bloc = '.$this->blocs->id_bloc.' AND id_langue = "'.$this->language.'"');
		foreach($lElements as $b_elt)
		{
			$this->elements->get($b_elt['id_element']);
			$this->bloc_cgv[$this->elements->slug] = $b_elt['value'];
			$this->bloc_cgvComplement[$this->elements->slug] = $b_elt['complement'];	
		}
		
		//echo '<pre>';
		//print_r($this->bloc_cgv);
		//echo '</pre>';
		
		// form qs
		if(isset($_POST['send_form_qs'])){
			$form_ok = true;
			if(!isset($_POST['secret-question']) || $_POST['secret-question'] == ''){
				$form_ok = false;
			}
			if(!isset($_POST['secret-response']) || $_POST['secret-response'] == ''){
				$form_ok = false;
			}
			if(!in_array('',array($this->clients->secrete_question,$this->clients->secrete_reponse))){
				$form_ok = false;
			}
			
			// form ok
			if($form_ok == true){
				$this->clients->secrete_question = $_POST['secret-question'];
				$this->clients->secrete_reponse = md5($_POST['secret-response']);
				$this->clients->update();
				
				$_SESSION['qs_ok'] = 'OK';
				
				header('Location:'.$this->lurl.'/synthese');
				die;
			}
		}
		
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
		//$this->lienConditionsGenerales = 93;
		
		// liste des cgv accpeté
		$listeAccept = $this->acceptations_legal_docs->selectAccepts('id_client = '.$this->clients->id_client);
		//$listeAccept = array();
		
		// Initialisation de la variable
		$this->update_accept = false;
		
		// On cherche si on a déjà le cgv
		if(in_array($this->lienConditionsGenerales,$listeAccept)){
			$this->accept_ok = true;
		}
		else{
			$this->accept_ok = false;
			// Si on a deja des cgv d'accepté
			if($listeAccept != false){
				$this->update_accept = true;
			}
			
			// gestion du cookie
			/*if(isset($_COOKIE["accept_cgv"]) && $_COOKIE["accept_cgv"] == 'no_sign'){
				$this->accept_ok = true;
			}
			else{
				// On créer un cookie
				setcookie("accept_cgv", 'no_sign', time()+86400);  // expire dans 1 jour
			}*/	
		}
		
		// Heure fin periode funding
		$this->settings->get('Heure fin periode funding','type');
		$this->heureFinFunding = $this->settings->value;
		
		// on recup le lender
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		// On recupere les projets favoris
		$lesFav = $this->favoris->projetsFavorisPreteur($this->clients->id_client);
		
		// Liste des projets favoris
		if($lesFav == false)$this->lProjetsFav = 0;
		else $this->lProjetsFav = $this->projects->select('id_project IN ('.$lesFav.')');
		
		// on récup la liste des projets que le lender a bidé
		$lesBids = $this->bids->projetsBidsEnCoursPreteur($this->lenders_accounts->id_lender_account);
		
		// Liste des projets en cours dont le preteur participe
		if($lesBids == false)$this->lProjetsBidsEncours = 0;
		else $this->lProjetsBidsEncours = $this->projects->select('id_project IN ('.$lesBids.')');
		
		// Liste des projets en cours (projets a decouvrir)
		$this->lProjetEncours = $this->projects->selectProjectsByStatus(50,'','p.date_publication_full DESC',0,5);
		
		$this->nbLoan = $this->loans->getNbPprojet($this->lenders_accounts->id_lender_account);
		
		// somme des bids en cours
		$this->sumBidsEncours = $this->bids->sumBidsEncours($this->lenders_accounts->id_lender_account);
		
		// somme Prêté
		$this->sumPrets = $this->loans->sumPrets($this->lenders_accounts->id_lender_account);
		
		// somme remboursé
		$this->sumRembMontant = $this->echeanciers->getSumRemb($this->lenders_accounts->id_lender_account,'capital');
		// somme retant du (capital) (a rajouter en prod)
		$this->sumRestanteARemb = $this->echeanciers->getSumARemb($this->lenders_accounts->id_lender_account,'capital');
		
		// somme retenues fiscales remboursés
		$this->sumRevenuesFiscalesRemb = $this->echeanciers->getSumRevenuesFiscalesRemb($this->lenders_accounts->id_lender_account);
		//$this->sumRembMontant -= $this->sumRevenuesFiscalesRemb;
		
		
		// somme des interets
		$this->sumInterets = $this->echeanciers->getSumRemb($this->lenders_accounts->id_lender_account,'interets');
		$this->sumInterets -= $this->sumRevenuesFiscalesRemb; // interets net
		
		$total = $this->solde+$this->sumBidsEncours+$this->sumPrets; // solde + bids en cours + prets validés
		
		$this->soldePourcent = @round(($this->solde/$total)*100,1); // solde du compte en pourcentage
	
		$this->sumBidsEncoursPourcent = @round(($this->sumBidsEncours/$total)*100,1); // bids en pourcentage
		$this->sumPretsPourcent = @round(($this->sumPrets/$total)*100,1); // pret en pourcentage
		
		$this->SumDepot = $this->wallets_lines->getSumDepot($this->lenders_accounts->id_lender_account,'10,30'); // sommes deposé
		
		
		// Revenus mensuel
		$sumRembParMois = $this->echeanciers->getSumRembByMonthsCapital($this->lenders_accounts->id_lender_account,date('Y')); // captial remboursé / mois
		$sumIntbParMois = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account,date('Y')); // intérets brut / mois
		
		$sumRevenuesfiscalesParMois = $this->echeanciers->getSumRevenuesFiscalesByMonths($this->lenders_accounts->id_lender_account,date('Y')); // revenues fiscales / mois

		
		for($i=1; $i<=12; $i++)		
		{
			$a = $i;
			$a = ($i<10?'0'.$a:$a);			
			$this->sumRembParMois[$i] = number_format(($sumRembParMois[$a] != ''?$sumRembParMois[$a]:0),2,'.',''); // capital remboursé / mois
			$this->sumIntbParMois[$i] = number_format(($sumIntbParMois[$a] != ''?$sumIntbParMois[$a]-$sumRevenuesfiscalesParMois[$a]:0),2,'.',''); // interets net / mois
			$this->sumRevenuesfiscalesParMois[$i] = number_format(($sumRevenuesfiscalesParMois[$a] != ''?$sumRevenuesfiscalesParMois[$a]:0),2,'.',''); // prelevements fiscaux
		}
		
		// Affichage des revenus mensuel directement sur le mois en cours
		$lesmois['01'] = '1';
		$lesmois['02'] = '1';
		$lesmois['03'] = '1';
		$lesmois['04'] = '2';
		$lesmois['05'] = '2';
		$lesmois['06'] = '2';
		$lesmois['07'] = '3';
		$lesmois['08'] = '3';
		$lesmois['09'] = '3';
		$lesmois['10'] = '4';
		$lesmois['11'] = '4';
		$lesmois['12'] = '4';
		
		if($lesmois[date('m')] == 1)
		{
			$this->ordre[1] = 1;
			$this->ordre[2] = 2;
			$this->ordre[3] = 3;
			$this->ordre[4] = 4;
		}
		elseif($lesmois[date('m')] == 2)
		{
			$this->ordre[1] = 2;
			$this->ordre[2] = 3;
			$this->ordre[3] = 4;
			$this->ordre[4] = 1;
		}
		elseif($lesmois[date('m')] == 3)
		{
			$this->ordre[1] = 3;
			$this->ordre[2] = 4;
			$this->ordre[3] = 1;
			$this->ordre[4] = 2;
		}
		elseif($lesmois[date('m')] == 4)
		{
			$this->ordre[1] = 4;
			$this->ordre[2] = 1;
			$this->ordre[3] = 2;
			$this->ordre[4] = 3;
		}
		
		$this->lFavP = $this->projects->getDerniersFav($this->clients->id_client);
		
		
		$this->lRejetB = $this->notifications->select('id_lender = '.$this->lenders_accounts->id_lender_account.' AND type = 1 AND status = 0');
		
		$this->lRembB = $this->notifications->select('id_lender = '.$this->lenders_accounts->id_lender_account.' AND type = 2 AND status = 0');
		
		//$this->lNotif = $this->notifications->select('id_lender = '.$this->lenders_accounts->id_lender_account.' AND status = 0');
		
		/*foreach($this->lNotif as $n)
		{
			$this->notifications->get($n['id_notification'],'id_notification');
			$this->notifications->status = 1;
			$this->notifications->update();
		}*/
		
		$this->nblFavP = count($this->lFavP);
		$this->nblRejetB = count($this->lRejetB);
		$this->nblRembB = count($this->lRembB);
		
		// statut client
		$this->clients_status->getLastStatut($this->clients->id_client);
		
	}
	
}