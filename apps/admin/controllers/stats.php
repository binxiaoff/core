<?php

class statsController extends bootstrap
{
	var $Command;
	
	function statsController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;

		// Controle d'acces à la rubrique
		$this->users->checkAccess('stats');
		
		// Activation du menu
		$this->menu_admin = 'stats';
	}
	
	function _default()
	{
		// Chargement de la lib google
		$this->ga = $this->loadLib('gapi',array($this->google_mail,$this->google_password,(isset($_SESSION['ga_auth_token'])?$_SESSION['ga_auth_token']:null)));
		
		// Mise en session de la connexion GA
		$_SESSION['ga_auth_token'] = $this->ga->getAuthToken();
		
		// Recuperation de l'ID
		$this->ga->requestAccountData();
		
		foreach($this->ga->getResults() as $result)
		{
			$this->id_profile = $result->getProfileId();
		}
		
		// Traitement des dates du formulaire
		if(isset($_POST['next']))
		{
			$this->mois = intval($_POST['mois']);
			$this->annee = intval($_POST['annee']);
			
			$this->mois++;
			
			if($this->mois > 12)
			{
				$this->mois = 1;
				$this->annee++;
			}
			
			$this->deb_jour = 1;
			$this->deb_mois = $this->fin_mois = $this->mois;
			$this->deb_annee = $this->fin_annee = $this->annee;
			$this->fin_jour = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);
			
			if($this->deb_jour < 10) { $this->deb_jour = '0'.$this->deb_jour; }
			if($this->deb_mois < 10) { $this->deb_mois = '0'.$this->deb_mois; }
			if($this->fin_jour < 10) { $this->fin_jour = '0'.$this->fin_jour; }
			if($this->fin_mois < 10) { $this->fin_mois = '0'.$this->fin_mois; }
		}		
		elseif(isset($_POST['prev']))
		{
			$this->mois = intval($_POST['mois']);
			$this->annee = intval($_POST['annee']);
			
			$this->mois--;
			
			if($this->mois < 1)
			{
				$this->mois = 12;
				$this->annee--;
			}
			
			$this->deb_jour = 1;
			$this->deb_mois = $this->fin_mois = $this->mois;
			$this->deb_annee = $this->fin_annee = $this->annee;
			$this->fin_jour = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);
			
			if($this->deb_jour < 10) { $this->deb_jour = '0'.$this->deb_jour; }
			if($this->deb_mois < 10) { $this->deb_mois = '0'.$this->deb_mois; }
			if($this->fin_jour < 10) { $this->fin_jour = '0'.$this->fin_jour; }
			if($this->fin_mois < 10) { $this->fin_mois = '0'.$this->fin_mois; }
		}		
		elseif(isset($_POST['voir']))
		{
			$this->mois = intval($_POST['mois']);
			$this->annee = intval($_POST['annee']);
			
			$this->deb_jour = 1;
			$this->deb_mois = $this->fin_mois = $this->mois;
			$this->deb_annee = $this->fin_annee = $this->annee;
			$this->fin_jour = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);
			
			if($this->deb_jour < 10) { $this->deb_jour = '0'.$this->deb_jour; }
			if($this->deb_mois < 10) { $this->deb_mois = '0'.$this->deb_mois; }
			if($this->fin_jour < 10) { $this->fin_jour = '0'.$this->fin_jour; }
			if($this->fin_mois < 10) { $this->fin_mois = '0'.$this->fin_mois; }
		}		
		elseif(isset($_POST['intervalle']))
		{
			$this->deb_jour = $_POST['du-jour'];
			$this->deb_mois = $_POST['du-mois'];
			$this->deb_annee = $_POST['du-annee'];
			$this->fin_jour = $_POST['au-jour'];
			$this->fin_mois = $_POST['au-mois'];
			$this->fin_annee = $_POST['au-annee'];
			
			$this->mois = $this->deb_mois;
			$this->annee = $this->deb_annee;
			
			if($this->deb_jour < 10) { $this->deb_jour = '0'.$this->deb_jour; }
			if($this->deb_mois < 10) { $this->deb_mois = '0'.$this->deb_mois; }
			if($this->fin_jour < 10) { $this->fin_jour = '0'.$this->fin_jour; }
			if($this->fin_mois < 10) { $this->fin_mois = '0'.$this->fin_mois; }
		}
		else
		{		
			// Attribution jour,mois,année par defaut
			$this->deb_jour = 1;
			$this->deb_mois = $this->fin_mois = date('m');
			$this->deb_annee = $this->fin_annee = date('Y');
			$this->fin_jour = $this->dates->nb_jour_dans_mois(date('m'), date('Y'));
			
			$_POST['du-jour'] = $this->deb_jour;
			$_POST['du-mois'] = $this->deb_mois;
			$_POST['du-annee'] = $this->deb_annee;
			$_POST['au-jour'] = $this->fin_jour;
			$_POST['au-mois'] = $this->fin_mois;
			$_POST['au-annee'] = $this->fin_annee;
			
			$this->mois = $this->deb_mois;
			$this->annee = $this->deb_annee;
			
			if($this->deb_jour < 10) { $this->deb_jour = '0'.$this->deb_jour; }
			if($this->fin_jour < 10) { $this->fin_jour = '0'.$this->fin_jour; }
		}
		
		// Recuperation du nombre de jours
		$this->nb_jours = $this->dates->intervalleJours($this->deb_jour,$this->deb_mois,$this->deb_annee,$this->fin_jour,$this->fin_mois,$this->fin_annee);
		
		if($this->nb_jours == 0)
		{
			$this->deb_jour = 1;
			$this->deb_mois = $this->fin_mois = date('m');
			$this->deb_annee = $this->fin_annee = date('Y');
			$this->fin_jour = $this->dates->nb_jour_dans_mois(date('m'), date('Y'));

			$_POST['du-jour'] = $this->deb_jour;
			$_POST['du-mois'] = $this->deb_mois;
			$_POST['du-annee'] = $this->deb_annee;
			$_POST['au-jour'] = $this->fin_jour;
			$_POST['au-mois'] = $this->fin_mois;
			$_POST['au-annee'] = $this->fin_annee;
			
			$this->mois = $this->deb_mois;
			$this->annee = $this->deb_annee;
			
			if($this->deb_jour < 10) { $this->deb_jour = '0'.$this->deb_jour; }
			if($this->fin_jour < 10) { $this->fin_jour = '0'.$this->fin_jour; }
			
			$this->nb_jours = $this->dates->intervalleJours($this->deb_jour,$this->deb_mois,$this->deb_annee,$this->fin_jour,$this->fin_mois,$this->fin_annee);
		}
		
		// Recupearation d'un rapport GA
		$this->ga->requestReportData($this->id_profile,array('visitCount'),array('pageviews','visits','newVisits'),null,null,$this->deb_annee.'-'.$this->deb_mois.'-'.$this->deb_jour,$this->fin_annee.'-'.$this->fin_mois.'-'.$this->fin_jour);
	}
	
	
	
	// Ressort un csv avec les process des users
	function _etape_inscription()
	{		
		// Récup des dates
		if($_POST['date1'] != '')
		{
			$d1 = explode('/',$_POST['date1']);
			$date1 = $d1[2].'-'.$d1[1].'-'.$d1[0];
		}
		else
		{
			$_POST['date1'] = date('d/m/Y'); //"01/08/2014";
			$date1 = date("Y-m-d H:i:s"); //"2014-08-01 00:00:00";
		}	
		
		if($_POST['date2'] != '')
		{
			$d2 = explode('/',$_POST['date2']);
			$date2 = $d2[2].'-'.$d2[1].'-'.$d2[0];
		}
		else
		{			
			$_POST['date2'] =  date('d/m/Y',strtotime('last day of this month')); //"31/08/2014";
			$date2 = date('Y-m-d H:i:s',strtotime('last day of this month')); //"2014-08-31 00:00:00";
		}
	
		// récup de tous les clients crée depuis le 1 aout
		$this->clients = $this->loadData('clients');
		$this->L_clients = $this->clients->select('etape_inscription_preteur > 0 AND status = 1 AND added > "'.$date1.'" AND added < "'.$date2.'"');
		
		// Le post est simplement le clic sur un bouton
		if(isset($_POST['recup'])){
			
			header("Content-type: application/vnd.ms-excel"); 
			header("Content-disposition: attachment; filename=\"Export_etape_inscription.csv\"");
			
			// Récup des dates
			if($_POST['spy_date1'] != '')
			{
				$d1 = explode('/',$_POST['spy_date1']);
				$date1 = $d1[2].'-'.$d1[1].'-'.$d1[0];
			}
			else
			{
				$date1 = date("Y-m-d H:i:s"); //"2014-08-01 00:00:00";
			}	
			
			if($_POST['spy_date2'] != '')
			{
				$d2 = explode('/',$_POST['spy_date2']);
				$date2 = $d2[2].'-'.$d2[1].'-'.$d2[0];
			}
			else
			{
				$date2 = date('Y-m-d H:i:s',strtotime('last day of this month')); //"2014-08-31 00:00:00";
			}
	
			$this->L_clients = $this->clients->select('etape_inscription_preteur > 0 AND status = 1 AND added > "'.$date1.'" AND added < "'.$date2.'"');
		
			
			
			$csv = "id_client;nom;prenom;email;tel;date_inscription;etape_inscription;Source;Source 2; Source 3\n";
			// construction de chaque ligne 
			foreach($this->L_clients as $u)
			{ 
				if($u['etape_inscription_preteur'] == 3)
				{
					// On va récupérer le type de paiement
					$this->lenders_accounts = $this->loadData('lenders_accounts');
					$this->lenders_accounts->get($u['id_client'],'id_client_owner');
					
					if($this->lenders_accounts->type_transfert == 1)
					{
						$type = "Virement";
					}
					else
					{
						$type = "CB";
					}
					
					$etape =  "3 - ".$type;
				}
				else
				{
					$etape = $u['etape_inscription_preteur'];
				}
			
				// on concatene a $csv 
				$csv .= utf8_decode($u['id_client']).';'.utf8_decode($u['nom']).';'.utf8_decode($u['prenom']).';'.utf8_decode($u['email']).';'.utf8_decode($u['telephone'].' '.$u['mobile']).';'.utf8_decode($this->dates->formatDate($u['added'],'d/m/Y')).';'.utf8_decode($etape).';'.$u['source'].';'.$u['source2'].';'.$u['slug_origine']."\n"; // le \n final entre " " 
			} 
			
			print($csv); 
			exit; 
		}
	}
	
	function _requete_dossiers()
	{
		
		$this->companies = $this->loadData('companies');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->insee = $this->loadData('insee');
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		
		$this->lEmpr = $this->clients->select('status_pre_emp IN(2,3) AND status = 1');
	}
	
	function _requete_dossiers_csv()
	{
		$this->companies = $this->loadData('companies');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->insee = $this->loadData('insee');
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		
		$this->lEmpr = $this->clients->select('status_pre_emp IN(2,3) AND status = 1');
		
		$header = "Cdos;Dénomination;Adresse;Voie;CodeCommune;commune;CodePostal;Ville;Activités;Siret;APE;F Juridique;Capital;CapitalMonnaie;LieuRCS;Responsable;Fonction;Téléphone;Fax;CatJuridique;CDéclaration;Cbénéficiaire;";
		$header = utf8_encode($header);
		
		$csv = "";
		$csv .= $header." \n";
		
		foreach($this->lEmpr as $e)
		{
			$this->companies->get($e['id_client'],'id_client_owner');
			
			$this->companies->get($e['id_client'],'id_client_owner');
				
			$statutRemb = false;
			$lPorjects = $this->projects->select('id_company = '.$this->companies->id_company);
			if($lPorjects != false){
				foreach($lPorjects as $p){
					$this->projects_status->getLastStatut($p['id_project']);
					if($this->projects_status->status == 80){
						$statutRemb = true;	
					}
				}
			}
			
			if($statutRemb == true){
			
			
				$this->clients_adresses->get($e['id_client'],'id_client');
				
				$this->insee->get($this->clients_adresses->ville,'NCCENR');
				
				// Code commune insee
				$dep = str_pad($this->insee->DEP,2,'0', STR_PAD_LEFT);
				$com = str_pad($this->insee->COM,3,'0', STR_PAD_LEFT);
				$codeCom = $dep.$com;
	
				$pos = strpos(str_replace('.','',$this->companies->rcs), 'RCS');
				$pos +=3;
				$lieuRCS = trim(substr($this->companies->rcs,$pos));
				
				
				$csv .= $e['id_client'].";".$this->companies->name.";;".str_replace(';',',',$this->clients_adresses->adresse1).";".$codeCom.";;".$this->clients_adresses->cp.";".$this->clients_adresses->ville.";".$this->companies->activite.";".$this->companies->siret.";;".$this->companies->forme.";".$this->companies->capital.";\"EUR\";".$lieuRCS.";".$e['prenom'].' '.$e['nom'].";".$e['fonction'].";".$e['telephone'].";;;C;B;";
				
				$csv .= " \n";
			
			}
		}
		
		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		$titre = 'requete_dossiers'.date('Ymd');
		header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
		print(utf8_decode($csv)); 
		exit;
		
		die;

	}
	
	function _requete_donnees_financieres()
	{
		$this->lEmpr = $this->bdd->query("SELECT p.id_project as id_project, c.name,
(SELECT cli.source FROM clients cli WHERE cli.id_client = c.id_client_owner) as source,
title, p.added, 
(SELECT label from projects_status ps where ps.`id_project_status` = (select `id_project_status` FROM projects_status_history psh where psh.id_project = p.id_project order by added desc limit 1)) as status, 
c.altares_scoreVingt, c.risk, p.amount,p.period,
(SELECT ca FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as ca2011,
(SELECT ca FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as ca2012,
(SELECT ca FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as ca2013,

(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rbe2011,
(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rbe2012,
(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rbe2013,


(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rex2011,
(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rex2012,
(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rex2013,


(SELECT investissements FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as invest2011,
(SELECT investissements FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as invest2012,
(SELECT investissements FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as invest2013,


(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immocorp2011,
(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immocorp2012,
(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immocorp2013,


(SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immoincorp2011,
(SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immoincorp2012,
(SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immoincorp2013,


(SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immofin2011,
(SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immofin2012,
(SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immofin2013,


(SELECT stocks FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as stock2011,
(SELECT stocks FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as stock2012,
(SELECT stocks FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as stock2013,


(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as creances2011,
(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as creances2012,
(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as creances2013,


(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dispo2011,
(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dispo2012,
(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dispo2013,

(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as valeursmob2011,
(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as valeursmob2012,
(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as valeursmob2013,

(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as cp2011,
(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as cp2012,
(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as cp2013,

(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as provisions2011,
(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as provisions2012,
(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as provisions2013,

(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as ammort2011,
(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as ammort2012,
(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as ammort2013,

(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfin2011,
(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfin2012,
(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfin2013,

(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfour2011,
(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfour2012,
(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfour2013,

(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as autresdettes2011,
(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as autresdettes2012,
(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as autresdettes2013,
c.forme,c.date_creation

FROM projects p join companies c on c.id_company = p.id_company where id_project in (select `id_project` FROM projects_status_history psh)

");
	}
	
	function _requete_donnees_financieres_csv()
	{
		
		$this->lEmpr = $this->bdd->query("SELECT p.id_project as id_project, c.name,
(SELECT cli.source FROM clients cli WHERE cli.id_client = c.id_client_owner) as source,
title, p.added, 
(SELECT label from projects_status ps where ps.`id_project_status` = (select `id_project_status` FROM projects_status_history psh where psh.id_project = p.id_project order by added desc limit 1)) as status, 
c.altares_scoreVingt, c.risk, p.amount,p.period,
(SELECT ca FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as ca2011,
(SELECT ca FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as ca2012,
(SELECT ca FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as ca2013,

(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rbe2011,
(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rbe2012,
(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rbe2013,


(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rex2011,
(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rex2012,
(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rex2013,


(SELECT investissements FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as invest2011,
(SELECT investissements FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as invest2012,
(SELECT investissements FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as invest2013,


(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immocorp2011,
(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immocorp2012,
(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immocorp2013,


(SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immoincorp2011,
(SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immoincorp2012,
(SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immoincorp2013,


(SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immofin2011,
(SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immofin2012,
(SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immofin2013,


(SELECT stocks FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as stock2011,
(SELECT stocks FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as stock2012,
(SELECT stocks FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as stock2013,


(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as creances2011,
(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as creances2012,
(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as creances2013,


(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dispo2011,
(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dispo2012,
(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dispo2013,

(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as valeursmob2011,
(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as valeursmob2012,
(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as valeursmob2013,

(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as cp2011,
(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as cp2012,
(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as cp2013,

(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as provisions2011,
(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as provisions2012,
(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as provisions2013,

(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as ammort2011,
(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as ammort2012,
(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as ammort2013,

(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfin2011,
(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfin2012,
(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfin2013,

(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfour2011,
(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfour2012,
(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfour2013,

(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as autresdettes2011,
(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as autresdettes2012,
(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as autresdettes2013,
c.forme,c.date_creation

FROM projects p join companies c on c.id_company = p.id_company where id_project in (select `id_project` FROM projects_status_history psh)

");
		
		/*$header = "id_project; name; source; title; added; status; altares_scoreVingt; risk; amount; period; ca2011; ca2012; ca2013; rbe2011; rbe2012; rbe2013; rex2011; rex2012; rex2013; invest2011; invest2012; invest2013; immocorp2011; immocorp2012; immocorp2013; immoincorp2011; immoincorp2012; immoincorp2013; immofin2011; immofin2012; immofin2013; stock2011; stock2012; stock2013; creances2011; creances2012; creances2013; dispo2011; dispo2012; dispo2013; valeursmob2011; valeursmob2012; valeursmob2013; cp2011; cp2012; cp2013; provisions2011; provisions2012; provisions2013; ammort2011; ammort2012; ammort2013; dettesfin2011; dettesfin2012; dettesfin2013; dettesfour2011; dettesfour2012; dettesfour2013; autresdettes2011; autresdettes2012; autresdettes2013; forme; date_creation;";
		$header = utf8_encode($header);
		
		$csv = "";
		$csv .= $header." \n";*/
		$i = 1;
		//foreach($this->lEmpr as $e)
		while($e = $this->bdd->fetch_array($this->lEmpr))
		{	
			if($i==1){
				foreach($e as $key=>$field)
					if(!is_numeric($key))
						$csv .= $key."; ";
				$csv .= " \n";
			}
			
			foreach($e as $key=>$field)
			if(!is_numeric($key))
			$csv .= $field."; ";
			$csv .= " \n";
		$i++;
		}
		
		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		$titre = 'requete_dossiers'.date('Ymd');
		header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
		print(utf8_decode($csv)); 
		exit;
		
		die;

	}
	
	// DC le 22/06/2015
	function _requete_etude_base_preteurs(){
		
		$sql = "SELECT
			c.id_client as ID_client,
			la.id_lender_account as ID_lender,
			c.source,
			CASE c.type
				WHEN 1 THEN 'Physique'
				WHEN 2 THEN 'Moral'
				ELSE ''
			END as Personne_moral_ou_physique,
			
			c.nom as Nom,
			
			c.prenom as Prenom,
			
			CASE c.naissance
				WHEN '0000-00-00' THEN 'No date'
				ELSE c.naissance
			END as age,
			
			CASE c.civilite
				WHEN 'M.' THEN 'Masculin'
				ELSE 'Feminin'
			END as Sexe,
			
			c.email as Email,
			
			c.telephone as Telephone,
			
			(SELECT 
				CASE
					WHEN b.nom_banque = NULL THEN la.bic
					ELSE b.nom_banque
				END 
			FROM banques b WHERE trim(b.swift_code_banque)=trim(bic)) as Banque,
			
			ca.adresse1 as Adresse,
			
			ca.ville as Ville,
			
			ca.cp as Code_postal,
			
			c.added as Date_inscription,
			
			CASE c.status
				WHEN 1 THEN 'Actif'
				ELSE 'Bloque'
			END as Compte_actif_bloque,
			
			c.lastlogin as Date_derniere_connection,
			
			(SELECT ROUND((SUM(t.montant)/100),2) FROM transactions t WHERE t.id_client = c.id_client AND t.type_transaction IN(1,3,4,7) AND t.status = 1 AND t.etat = 1) as montant_verse,
			
			(SELECT COUNT(t.montant) FROM transactions t WHERE t.id_client = c.id_client AND t.type_transaction IN(1,3,4,7) AND t.status = 1 AND t.etat = 1) as Nombre_de_versement,
			
			(SELECT ROUND((t.montant/100),2) FROM transactions t WHERE t.id_client = c.id_client AND t.type_transaction IN(1,3,4,7) AND t.status = 1 AND t.etat = 1 ORDER BY added ASC LIMIT 1) as montant_premier_versement,
			
			(SELECT ROUND((SUM(wl.amount)/100),2) FROM wallets_lines wl WHERE wl.id_lender = la.id_lender_account) as Montant_disponible_a_date,
			
			(SELECT COUNT(bi.id_bid) FROM bids bi WHERE bi.id_lender_account = la.id_lender_account AND bi.status = 0) as Nombre_encheres_en_cours,
			
			((SELECT COUNT(bi.id_bid) FROM bids bi WHERE bi.id_lender_account = la.id_lender_account AND bi.status = 2) +
			
			(SELECT COUNT(lo.id_loan) FROM loans lo WHERE lo.id_lender = la.id_lender_account AND lo.status = 1))  as Nombre_encheres_rejetees,
			
			(SELECT COUNT(lo.id_loan) FROM loans lo WHERE lo.id_lender = la.id_lender_account AND lo.status = 0)  as Nombre_encheres_acceptees,
			
			(SELECT ROUND(SUM(lo.amount)/100,2) FROM loans lo WHERE lo.id_lender = la.id_lender_account AND lo.status = 0) as montant_prete,
			
			NOW() as Date_de_la_requete,
			
			CASE (SELECT id_client_status FROM `clients_status_history` WHERE id_client = la.id_client_owner ORDER BY added DESC LIMIT 1)
				WHEN 6 THEN 'Oui'
				ELSE 'Non'  
			END as Validation ,
			
			(SELECT csh.added FROM clients_status_history csh WHERE csh.id_client = la.id_client_owner AND csh.id_client_status = 6 ORDER BY added ASC LIMIT 1) as date_validation,   
			
			(SELECT CONCAT(u.firstname,' ',u.name) FROM users u WHERE u.id_user = ".$_SESSION['user']['id_user'].") as Personne_qui_a_fait_la_requete
			
			FROM lenders_accounts la
			LEFT JOIN clients c ON la.id_client_owner = c.id_client
			LEFT JOIN clients_adresses ca ON c.id_client = ca.id_client
			WHERE c.id_client <> 'NULL'
			";
		
		$this->result = $this->bdd->query($sql);
		
		if(isset($this->params[0]) && $this->params[0] == "csv"){
			$i = 1;
			//foreach($this->lEmpr as $e)
			while($e = $this->bdd->fetch_array($this->result))
			{	
				if($i==1){
					foreach($e as $key=>$field)
						if(!is_numeric($key))
							$csv .= $key."; ";
					$csv .= " \n";
				}
				
				foreach($e as $key=>$field)
				if(!is_numeric($key))
				$csv .= $field."; ";
				$csv .= " \n";
			$i++;
			}
			
			//********************//
			//*** Envoi du CSV ***//
			//********************//
			//$titre = $this->bdd->generateSlug($this->queries->name);
			$titre = 'requete_etude_base_preteur_'.date('Ymd');
			header("Content-type: application/vnd.ms-excel"); 
			header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
			print(utf8_decode($csv)); 
			exit;
			
			die;
		}
	}
	
	
	function _requete_beneficiaires()
	{
		
		$this->companies = $this->loadData('companies');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->insee = $this->loadData('insee');
		$this->villes = $this->loadData('villes');
		$this->pays = $this->loadData('pays_v2');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->loans = $this->loadData('loans');
		$this->insee_pays = $this->loadData('insee_pays');
		
		// EQ-Retenue à la source
		$this->settings->get(63);
		$this->retenuesource = $this->settings->value;
		//id_client IN(1399,573)
		$this->lPre = $this->clients->selectPreteursByStatus('20,30,40,50,60');
		
		
	}
	
	
	function _requete_beneficiaires_csv()
	{
		
		$this->companies = $this->loadData('companies');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->insee = $this->loadData('insee');
		$this->villes = $this->loadData('villes');
		$this->pays = $this->loadData('pays_v2');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->loans = $this->loadData('loans');
		$this->insee_pays = $this->loadData('insee_pays');
		
		
		// EQ-Retenue à la source
		$this->settings->get(63);
		$this->retenuesource = $this->settings->value;
		//id_client IN(1399,573)
		$this->lPre = $this->clients->selectPreteursByStatus('20,30,40,50,60');
		
		$header = "Cbene;Nom;Qualité;NomJFille;Prénom;DateNaissance;DépNaissance;ComNaissance;LieuNaissance;NomMari;Siret;AdISO;Adresse;Voie;CodeCommune;Commune;CodePostal;Ville / nom pays;IdFiscal;PaysISO;Entité;ToRS;Plib;Tél;Banque;IBAN;BIC;EMAIL;Obs;";
		$header = utf8_encode($header);
		
		$csv = "";
		$csv .= $header." \n";
		
		
		foreach($this->lPre as $e)
		{	
			$this->clients_adresses->get($e['id_client'],'id_client');
			$this->lenders_accounts->get($e['id_client'],'id_client_owner');
			
			if($this->loans->counter('status = 0 AND id_lender = '.$this->lenders_accounts->id_lender_account) > 0)
			{
			
				$entreprise = false	;
				if($this->companies->get($e['id_client'],'id_client_owner') && in_array($e['type'],array(2,4))){
					$entreprise = true;
					if($this->companies->id_pays == 0) $this->companies->id_pays = 1;
					$this->pays->get($this->companies->id_pays,'id_pays');
					$isoFiscal = $this->pays->iso;
					
					$ville_paysFiscal = $this->companies->city;
					
					$cp = substr($this->companies->zip,0,2);
					if($cp[0] == 0)$cp = substr($cp,1);
					
					// Code commune insee ville
					$laville = strtoupper(str_replace(' ','-',trim($this->companies->city)));
					$this->insee->get($laville,'DEP = '.$cp.' AND NCC');
					$dep = str_pad($this->insee->DEP,2,'0', STR_PAD_LEFT);
					$com = str_pad($this->insee->COM,3,'0', STR_PAD_LEFT);
					$codeCom = $dep.$com;
					
					
					
					/*$laville = strtoupper(str_replace(array('saint-','SAINT-','Saint-'),'ST ',$this->companies->city));
					
					if($this->villes->get($laville,'num_departement = '.$cp.' AND ville') == false){
						$laville = strtoupper(str_replace(array('saint ','SAINT ','Saint '),'ST ',str_replace(array("-","'"),' ',$this->companies->city)));
						$this->villes->get($laville,'num_departement = '.$cp.' AND ville');
					}
					
					$dep = str_pad($this->villes->num_departement,2,'0', STR_PAD_LEFT);
					$codeCom = str_pad($this->villes->insee,5,'0', STR_PAD_LEFT);*/
					
					$codeComNaissance = '';
					
					$retenuesource = '';
					
				}
				else{
					
					$this->etranger = 0;
					
					// fr/resident etranger
					if($e['id_nationalite'] <= 1 && $this->clients_adresses->id_pays_fiscal > 1){
						$this->etranger = 1;
					}
					// no fr/resident etranger
					elseif($e['id_nationalite'] > 1 && $this->clients_adresses->id_pays_fiscal > 1){
						$this->etranger = 2;
					}
					
					// on veut adresse fiscal
					if($this->clients_adresses->meme_adresse_fiscal==1){
						$adresse_fiscal = trim($this->clients_adresses->adresse1);
						$cp_fiscal		=  trim($this->clients_adresses->cp);
						$ville_fiscal	=  trim($this->clients_adresses->ville);
						$id_pays_fiscal = ($this->clients_adresses->id_pays==0?1:$this->clients_adresses->id_pays);
					}
					else{
						$adresse_fiscal =  trim($this->clients_adresses->adresse_fiscal);
						$cp_fiscal		=  trim($this->clients_adresses->cp_fiscal);
						$ville_fiscal	=  trim($this->clients_adresses->ville_fiscal);
						$id_pays_fiscal = ($this->clients_adresses->id_pays_fiscal==0?1:$this->clients_adresses->id_pays_fiscal);
					}
					
					// date naissance
					$nais = explode('-',$e['naissance']);
					$naissance = $nais[2].'/'.$nais[1].'/'.$nais[0];
					
					// Iso fiscal
					if($this->clients_adresses->id_pays_fiscal == 0)$this->clients_adresses->id_pays_fiscal = 1;
					$this->pays->get($this->clients_adresses->id_pays_fiscal,'id_pays');
					$isoFiscal = $this->pays->iso;
					
					if($e['id_pays_naissance'] == 0) $id_pays_naissance = 1;
					else $id_pays_naissance = $e['id_pays_naissance'];
					$this->pays->get($id_pays_naissance,'id_pays');
					$isoNaissance = $this->pays->iso;
						
					
					if($this->etranger == 0){	
						
						$cp = substr($cp_fiscal,0,2);
						
						if($cp[0] == 0)$cp = trim(substr($cp,1));
						
						// Code commune insee ville
						$laville = strtoupper(str_replace(' ','-',$ville_fiscal));
						$this->insee->get($laville,'DEP = '.$cp.' AND NCC');
						$dep = str_pad($this->insee->DEP,2,'0', STR_PAD_LEFT);
						$com = str_pad($this->insee->COM,3,'0', STR_PAD_LEFT);
						$codeCom = $dep.$com;
						
						/*$cp = substr($this->clients_adresses->cp,0,2);
						
						$laville = strtoupper(str_replace(array('saint-','SAINT-','Saint-'),'ST ',$this->clients_adresses->ville));
						
						if($this->villes->get($laville,'num_departement = '.$cp.' AND ville') == false){
							$laville = strtoupper(str_replace(array('saint ','SAINT ','Saint '),'ST ',str_replace(array("-","'"),' ',$this->clients_adresses->ville)));	
							$this->villes->get($laville,'num_departement = '.$cp.' AND ville');
						}
						
						$dep = str_pad($this->villes->num_departement,2,'0', STR_PAD_LEFT);
						$codeCom = str_pad($this->villes->insee,5,'0', STR_PAD_LEFT);*/
						
						$commune = '';
						$cp = $cp_fiscal;
						
						$retenuesource = '';
						
						$ville_paysFiscal = $ville_fiscal;
					}
					else{
						$codeCom = $cp_fiscal;
						$commune = $ville_fiscal;
						
						if($id_pays_fiscal == 0) $id_pays = 1;
						else $id_pays = $id_pays_fiscal;
						$this->pays->get($id_pays,'id_pays');
						
						$this->insee_pays->get($this->pays->fr,'LIBCOG');
						$cp = $this->insee_pays->COG;
						
						$retenuesource = number_format($this->retenuesource*100, 2, ',', ' ').'%';
						
						
						if($id_pays_fiscal == 0) $id_pays = 1;
						else $id_pays = $id_pays_fiscal;
						$this->pays->get($id_pays,'id_pays');
						$paysFiscal = $this->pays->fr;
						
						$ville_paysFiscal = $paysFiscal;
							
					}

					
					// Code commune insee ville naissance
					/*$this->insee->get(strtoupper(str_replace(' ','-', trim($e['ville_naissance'])),'NCC');
					$depNaiss = str_pad($this->insee->DEP,2,'0', STR_PAD_LEFT);
					$comNaiss = str_pad($this->insee->COM,3,'0', STR_PAD_LEFT);
					$codeComNaissance = $depNaiss.$comNaiss;*/
					
					// si france
					if($e['id_pays_naissance'] <= 1){
						

						
						$laville = strtoupper(str_replace(' ','-', trim($e['ville_naissance'])));
						if($this->insee->get($laville,'NCC') == false){
							// On regarde si c'est un doublon
							if($this->insee->counter('NCC = "'.$laville.'"') > 1){
								$depNaiss = '';
								$codeComNaissance = 'DOUBLON';
							}
							else{
								$depNaiss = '00';
								$codeComNaissance = '00000';
							}
						}
						// si c'est bon au premier test
						else{
							
							$depNaiss = str_pad($this->insee->DEP,2,'0', STR_PAD_LEFT);
							$comNaiss = str_pad($this->insee->COM,3,'0', STR_PAD_LEFT);
							$codeComNaissance = $depNaiss.$comNaiss;
						}
						
						
						
						
						// premiere combinaison
						/*$laville = strtoupper(str_replace(array('saint-','SAINT-','Saint-'),'ST ',$e['ville_naissance']));
						if($this->villes->get($laville,'ville') == false){
							// On regarde si c'est un doublon
							if($this->villes->counter('ville = "'.$laville.'"') > 1){
								$depNaiss = '';
								$codeComNaissance = 'DOUBLON';
							}
							// Si pas de doublon on test une autre combinaison
							else{
								$laville = strtoupper(str_replace(array('saint ','SAINT ','Saint '),'ST ',str_replace(array("-","'"),' ',$e['ville_naissance'])));	
								if($this->villes->get($laville,'ville') == false){
									// On regarde encore si c'est un doublon
									if($this->villes->counter('ville = "'.$laville.'"') > 1){
										$depNaiss = '';
										$codeComNaissance = 'DOUBLON';
									}
									else{
										$depNaiss = '00';
										$codeComNaissance = '00000';
									}
								}
								// si c'est bon au deuxieme test
								else{
									$depNaiss = str_pad($this->villes->num_departement,2,'0', STR_PAD_LEFT);
									$codeComNaissance = str_pad($this->villes->insee,5,'0', STR_PAD_LEFT);	
								}
							}
						}
						// si c'est bon au premier test
						else{
							$depNaiss = str_pad($this->villes->num_departement,2,'0', STR_PAD_LEFT);
							$codeComNaissance = str_pad($this->villes->insee,5,'0', STR_PAD_LEFT);	
						}*/
						
						
					}
					else{
						
						if($e['id_pays_naissance'] == 0)$id_pays_naissance = 1;
						else $id_pays_naissance = $e['id_pays_naissance'];
						$this->pays->get($id_pays_naissance,'id_pays');
						$depNaiss = '';
						$codeComNaissance = $this->pays->fr;
					}
				} // fin particulier
	
				// Motif 
				$p = substr($this->ficelle->stripAccents(utf8_decode(trim($e['prenom']))),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode(trim($e['nom'])));
				//$id_client = str_pad($e['id_client'],6,0,STR_PAD_LEFT);
				$id_client = $e['id_client'];
				$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				//$motif = mb_strtoupper($id_client.$nom,'UTF-8');
				$motif = substr($motif,0,10);
				
				if($entreprise == true){
					$csv .= $motif.";".$this->companies->name.";;;;;;;;;".$this->companies->siret.";".$isoFiscal.";;".str_replace(';',',',$this->companies->adresse1).";".$codeCom.";;".$this->companies->zip.";".$ville_paysFiscal.";;".$isoFiscal.";X;".$retenuesource.";N;".$this->companies->phone.";;".$this->lenders_accounts->iban.";".$this->lenders_accounts->bic.";".$e['email'].";;";
				}
				else{
					$csv .= $motif.";".$e['nom'].";".$e['civilite'].";".$e['nom'].";".$e['prenom'].";".$naissance.";".$depNaiss.";".$codeComNaissance.";".$e['ville_naissance'].";;;".$isoFiscal.";;".str_replace(';',',',$adresse_fiscal).";".$codeCom.";".$commune.";".$cp.";".$ville_paysFiscal.";;".$isoNaissance.";X;".$retenuesource.";N;".$e['telephone'].";;".$this->lenders_accounts->iban.";".$this->lenders_accounts->bic.";".$e['email'].";;";	
				}
					
				$csv .= " \n";
			}
		}
		
		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		$titre = 'requete_beneficiaires'.date('Ymd');
		header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
		print(utf8_decode($csv)); 
		exit;
		
		die;
		
	}
	
	
	function _requete_infosben()
	{
		$this->companies = $this->loadData('companies');
		$this->emprunteur = $this->loadData('clients');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->insee = $this->loadData('insee');
		$this->pays = $this->loadData('pays_v2');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		
		
		$this->lProjects = $this->projects->selectProjectsByStatus('80');
		
		//id_client IN(1399,573)
		//$this->lPre = $this->clients->selectPreteursByStatus('60');
		
	}
	
	function _requete_infosben_csv()
	{
		$this->companies = $this->loadData('companies');
		$this->emprunteur = $this->loadData('clients');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->insee = $this->loadData('insee');
		$this->pays = $this->loadData('pays_v2');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		
		
		$this->lProjects = $this->projects->selectProjectsByStatus('80');
		
		
		//id_client IN(1399,573)
		//$this->lPre = $this->clients->selectPreteursByStatus('60');
		
		$header = "Cdos;Cbéné;CEtabl;CGuichet;RéfCompte;NatCompte;TypCompte;CDRC;";
		$header = utf8_encode($header);
		
		$csv = "";
		$csv .= $header." \n";
		
		
		foreach($this->lProjects as $p)
		{	
			$this->companies->get($p['id_company'],'id_company');
			$this->emprunteur->get($this->companies->id_client_owner,'id_client');
		
			$this->lLoans = $this->loans->getPreteurs($p['id_project']);
			
			foreach($this->lLoans as $l)
			{
				$this->lenders_accounts->get($l['id_lender'],'id_lender_account');
				$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
					
				// Motif 
				$pre = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
				//$id_client = str_pad($e['id_client'],6,0,STR_PAD_LEFT);
				$id_client = $this->clients->id_client;
				$motif = mb_strtoupper($id_client.$pre.$nom,'UTF-8');
				//$motif = mb_strtoupper($id_client.$nom,'UTF-8');
				$motif = substr($motif,0,10);
				
				$csv .= $this->emprunteur->id_client.";".$motif.";14378;;".$this->clients->id_client.";4;6;P;";
				$csv .= " \n";
			}
			
		}
		
		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		$titre = 'requete_infosben'.date('Ymd');
		header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
		print(utf8_decode($csv)); 
		exit;
		
		die;
		
	}
	
	function _requete_revenus()
	{
		// non utilisé ram trop
		die;
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->companies = $this->loadData('companies');
		$this->emprunteur = $this->loadData('clients');
		$this->clients = $this->loadData('clients');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		
		// Liste des projets en Remb
		$this->lProjects = $this->projects->selectProjectsByStatus('80');
		
	}
	
	function _requete_revenus_csv_old()
	{
		die;
		//ini_set('max_execution_time', 300); //300 seconds = 5 minutes
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->companies = $this->loadData('companies');
		$this->emprunteur = $this->loadData('clients');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		
		// Liste des projets en Remb
		$this->lProjects = $this->projects->selectProjectsByStatus('80');
		
		$header = "Cdos;Cbéné;CodeV;Date;Montant;Monnaie;NbreParts;VAP;";
		$header = utf8_encode($header);
		
		$csv = "";
		$csv .= $header." \n";
		
		foreach($this->lProjects as $p){
	
			$this->companies->get($p['id_company'],'id_company');
			
			// Liste des prêts
			$lPrets = $this->loans->select('id_project = '.$p['id_project'].' AND status = 0');
			
			foreach($lPrets as $pret){
				
				$this->lenders_accounts->get($pret['id_lender'],'id_lender_account');
				
				$csv .= $this->companies->id_client_owner.";";
				$csv .= $this->lenders_accounts->id_client_owner.";";
				$csv .= "117;";
				$csv .= date('Y-m-d',strtotime($pret['added'])).";";
				$csv .= ($pret['amount']/100).";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
			}
			
			$lEcheances = $this->echeanciers->select('id_project = '.$p['id_project'].' AND status = 1');
			
			foreach($lEcheances as $e){
				
				
				$this->lenders_accounts->get($e['id_lender'],'id_lender_account');
				
				
				
				//$retenues = $e['prelevements_obligatoires']+$e['retenues_source']+$e['csg']+$e['prelevements_sociaux']+$e['contributions_additionnelles']+$e['prelevements_solidarite']+$e['crds'];
				
				// personne physique resident fiscal FR  -> prelevements obligatoires  (24%)
				// personne morale ou etrangere -> retenues à la source (15%)
				//$retenues = $e['retenues_source'];
				// personne morale
				if($this->lenders_accounts->id_company_owner  != 0){
					
					$retenues = $e['retenues_source'];
				}
				else{
					$this->clients_adresses->get($this->lenders_accounts->id_client_owner,'id_client');
					// residant fiscal etranger
					if($this->clients_adresses->id_pays_fiscal > 1){
						
						$retenues = $e['retenues_source'];
					}
					// residant fiscal fr
					else{

						$retenues = $e['prelevements_obligatoires'];
					}
				}
				//echo $ici;
				//echo '<br>-------<br>'.$e['id_lender'].'<br>'; 
				
				
				$csv .= $this->companies->id_client_owner.";";
				$csv .= $this->lenders_accounts->id_client_owner.";";
				$csv .= "53;";
				$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
				$csv .= ($e['interets']/100).";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
				
				$csv .= $this->companies->id_client_owner.";";
				$csv .= $this->lenders_accounts->id_client_owner.";";
				$csv .= "54;";
				$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
				$csv .= $retenues.";";
				//$csv .= ($this->lenders_accounts->id_company_owner != 0?$e['retenues_source']:$e['prelevements_obligatoires']).";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
				
				$csv .= $this->companies->id_client_owner.";";
				$csv .= $this->lenders_accounts->id_client_owner.";";
				$csv .= "66;";
				$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
				$csv .= ($e['interets']/100).";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";

				$csv .= $this->companies->id_client_owner.";";
				$csv .= $this->lenders_accounts->id_client_owner.";";
				$csv .= "118;";
				$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
				$csv .= ($e['capital']/100).";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
			
			}
			
		}
		
		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		$titre = 'requete_revenus'.date('Ymd');
		header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
		print(utf8_decode($csv)); 
		exit;
		
		die;
		
	}
	
	function _requete_revenus_csv_old2()
	{
		//ini_set('max_execution_time', 300); //300 seconds = 5 minutes
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->companies = $this->loadData('companies');
		$this->emprunteur = $this->loadData('clients');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		
		// Liste des projets en Remb
		$this->lProjects = $this->projects->selectProjectsByStatus('80');
		
		$header = "Cdos;Cbéné;CodeV;Date;Montant;Monnaie;NbreParts;VAP;";
		$header = utf8_encode($header);
		
		$csv = "";
		$csv .= $header." \n";
		
		foreach($this->lProjects as $p){

	
			$this->companies->get($p['id_company'],'id_company');
			
			// Liste des prêts
			$lPrets = $this->loans->select('id_project = '.$p['id_project'].' AND status = 0');
			
			foreach($lPrets as $pret){
				
				$this->lenders_accounts->get($pret['id_lender'],'id_lender_account');
				
				$csv .= $this->companies->id_client_owner.";";
				$csv .= $this->lenders_accounts->id_client_owner.";";
				$csv .= "117;";
				$csv .= date('Y-m-d',strtotime($pret['added'])).";";
				$csv .= ($pret['amount']/100).";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
			}
			
			//$lEcheances = $this->echeanciers->select('id_project = '.$p['id_project'].' AND status = 1');
			$lEcheances = $this->echeanciers->requete_revenus($p['id_project']);
			
			foreach($lEcheances as $e){
				
				// personne physique resident fiscal FR  -> prelevements obligatoires  (24%)
				// personne morale ou etrangere -> retenues à la source (15%)
				//$retenues = $e['retenues_source'];
				// personne morale
				if($e['id_company_owner'] != 0){
					
					$retenues = $e['retenues_source'];
					
					$csv .= $this->companies->id_client_owner.";";
					$csv .= $e['id_client_owner'].";";
					$csv .= "53;";
					$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
					$csv .= ($e['interets']/100).";";
					$csv .= "EURO;";
					$csv .= ";";
					$csv .= ";";
					
					$csv .= " \n";
				
					$csv .= $this->companies->id_client_owner.";";
					$csv .= $e['id_client_owner'].";";
					$csv .= "54;";
					$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
					$csv .= $retenues.";";
					$csv .= "EURO;";
					$csv .= ";";
					$csv .= ";";
				}
				else{
					
					// residant fiscal etranger
					if($e['resident'] >= 1){
						
						$retenues = $e['retenues_source'];
						
						$csv .= $this->companies->id_client_owner.";";
						$csv .= $e['id_client_owner'].";";
						$csv .= "31;";
						$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
						$csv .= ($e['interets']/100).";";
						$csv .= "EURO;";
						$csv .= ";";
						$csv .= ";";
						
						$csv .= " \n";
				
						$csv .= $this->companies->id_client_owner.";";
						$csv .= $e['id_client_owner'].";";
						$csv .= "32;";
						$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
						$csv .= $retenues.";";
						$csv .= "EURO;";
						$csv .= ";";
						$csv .= ";";
					}
					// residant fiscal fr
					else{

						$retenues = $e['prelevements_obligatoires'];
						
						$csv .= $this->companies->id_client_owner.";";
						$csv .= $e['id_client_owner'].";";
						$csv .= "53;";
						$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
						$csv .= ($e['interets']/100).";";
						$csv .= "EURO;";
						$csv .= ";";
						$csv .= ";";
						
						$csv .= " \n";
					
						$csv .= $this->companies->id_client_owner.";";
						$csv .= $e['id_client_owner'].";";
						$csv .= "54;";
						$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
						$csv .= $retenues.";";
						$csv .= "EURO;";
						$csv .= ";";
						$csv .= ";";
					}
				}

				$csv .= " \n";
				
				$csv .= $this->companies->id_client_owner.";";
				$csv .= $e['id_client_owner'].";";
				$csv .= "66;";
				$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
				$csv .= ($e['interets']/100).";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";

				$csv .= $this->companies->id_client_owner.";";
				$csv .= $e['id_client_owner'].";";
				$csv .= "118;";
				$csv .= date('Y-m-d',strtotime($e['date_echeance_reel'])).";";
				$csv .= ($e['capital']/100).";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
			
			}
			
		}
		
		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		$titre = 'requete_revenus'.date('Ymd');
		header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
		print(utf8_decode($csv)); 
		exit;
		
		die;
		
	}
	
	function _requete_revenus_csv()
	{
		//ini_set('max_execution_time', 300); //300 seconds = 5 minutes
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->companies = $this->loadData('companies');
		$this->emprunteur = $this->loadData('clients');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		

		$header = "Code Entreprise;CodeBénéficiaire;CodeV;Date;Montant;Monnaie;Nombre;VAP;";
		$header = utf8_encode($header);
		
		$csv = "";
		$csv .= $header." \n";
		

		$annee = '2015';
		$date = '31/12/2015';
			
		/*$sql = '
		SELECT 
			c.id_client,
			c.prenom,
			c.nom,
			SUM(l.amount) as montant,
			comp.id_client_owner as id_client_company
		FROM loans l
		LEFT JOIN lenders_accounts la ON l.id_lender = la.id_lender_account
		LEFT JOIN clients c ON la.id_client_owner = c.id_client
		LEFT JOIN projects p ON l.id_project = p.id_project
		LEFT JOIN companies comp ON p.id_company = comp.id_company
		WHERE l.status = 0
		AND LEFT(l.added,4) = "'.$annee.'"
		GROUP BY  l.id_project,l.id_lender';

		$resultat = $this->bdd->query($sql);
		while($record = $this->bdd->fetch_array($resultat))
		{
			// cbéné 
			$p = substr($this->ficelle->stripAccents(utf8_decode(trim($record[1]))),0,1);
			$nom = $this->ficelle->stripAccents(utf8_decode(trim($record[2])));
			$id_client = $record[0];
			$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
			$cbene = substr($motif,0,10);
			
			
			$csv .= $record[4].";";
			$csv .= $cbene.";";
			$csv .= "117;";
			$csv .= $date.";";
			$csv .= ($record[3]/100).";";
			$csv .= "EURO;";
			$csv .= ";";
			$csv .= ";";
			
			$csv .= " \n";
		}*/
		
		
		
		
		
		/*$sql = '
		SELECT 
			c.id_client,
			c.prenom,
			c.nom,
			SUM(e.interets) as interets,
			SUM(e.capital) as capital,
			SUM(ROUND(e.retenues_source,2)) as retenues_source,
			SUM(ROUND(e.prelevements_obligatoires,2)) as prelevements_obligatoires,
			IFNULL((SELECT lih.resident_etranger FROM lenders_imposition_history lih WHERE lih.added <= e.date_echeance_reel AND lih.id_lender = e.id_lender ORDER BY lih.added DESC LIMIT 1),0) as resident,
			(SELECT SUM(lo.amount) FROM loans lo WHERE lo.id_lender=la.id_lender_account) as amount,
			la.id_company_owner
		FROM echeanciers e
		LEFT JOIN lenders_accounts la ON e.id_lender = la.id_lender_account
		LEFT JOIN clients c ON la.id_client_owner = c.id_client
		WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'"
		AND e.status = 1
		GROUP BY e.id_lender,resident
		ORDER BY resident DESC,e.id_lender';*/
		
		
		
		$sql = '
		SELECT 
			c.id_client,
			c.prenom,
			c.nom,
			(SELECT SUM(e.interets) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND e.id_lender = la.id_lender_account) as interets_brut,
			
			(SELECT SUM(e.retenues_source) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND IFNULL((SELECT lih.resident_etranger FROM lenders_imposition_history lih WHERE lih.added <= e.date_echeance_reel AND lih.id_lender = e.id_lender ORDER BY lih.added DESC LIMIT 1),0) > 0 AND e.id_lender = la.id_lender_account OR LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND e.id_lender = la.id_lender_account AND la.id_company_owner != 0) as retenues_source,
			
			(SELECT SUM(ROUND(e.prelevements_obligatoires,2)) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND IFNULL((SELECT lih.resident_etranger FROM lenders_imposition_history lih WHERE lih.added <= e.date_echeance_reel AND lih.id_lender = e.id_lender ORDER BY lih.added DESC LIMIT 1),0) = 0 AND e.id_lender = la.id_lender_account) as prelevements_obligatoires_physique_resident,
			
			(SELECT SUM(e.interets) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND IFNULL((SELECT lih.resident_etranger FROM lenders_imposition_history lih WHERE lih.added <= e.date_echeance_reel AND lih.id_lender = e.id_lender ORDER BY lih.added DESC LIMIT 1),0) = 0 AND e.id_lender = la.id_lender_account) as interets_physique_resident,
			
			(SELECT SUM(lo.amount) FROM loans lo WHERE lo.id_lender=la.id_lender_account AND LEFT(lo.added,4) = "'.$annee.'") as capitaux,
			
			(SELECT SUM(e.capital) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND e.id_lender = la.id_lender_account) as capitaux_remb

		FROM lenders_accounts la
		LEFT JOIN clients c ON la.id_client_owner = c.id_client
		WHERE (SELECT COUNT(lo.id_loan) FROM loans lo WHERE lo.id_lender = la.id_lender_account AND LEFT(lo.added,4) = "'.$annee.'") > 0
		ORDER BY c.id_client
		';
		
		
		$sql = '
		SELECT 
			c.id_client,
			c.prenom,
			c.nom,
			(SELECT SUM(e.interets) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND e.id_lender = la.id_lender_account) as interets_brut,
			
			(SELECT SUM(e.retenues_source) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND IFNULL((SELECT lih.resident_etranger FROM lenders_imposition_history lih WHERE lih.added <= e.date_echeance_reel AND lih.id_lender = e.id_lender ORDER BY lih.added DESC LIMIT 1),0) > 0 AND e.id_lender = la.id_lender_account OR LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND e.id_lender = la.id_lender_account AND la.id_company_owner != 0) as retenues_source,
			
			(SELECT SUM(ROUND(e.prelevements_obligatoires,2)) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND e.id_lender = la.id_lender_account) as prelevements_obligatoires_physique_resident,
			
			(SELECT SUM(e.interets) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND IFNULL((SELECT lih.resident_etranger FROM lenders_imposition_history lih WHERE lih.added <= e.date_echeance_reel AND lih.id_lender = e.id_lender ORDER BY lih.added DESC LIMIT 1),0) = 0 AND e.id_lender = la.id_lender_account AND la.id_company_owner = 0) as interets_physique_resident,
			
			(SELECT SUM(lo.amount) FROM loans lo WHERE lo.id_lender=la.id_lender_account AND LEFT(lo.added,4) = "'.$annee.'") as capitaux,
			
			(SELECT SUM(e.capital) FROM echeanciers e WHERE LEFT(e.date_echeance_reel,4) = "'.$annee.'" AND e.id_lender = la.id_lender_account) as capitaux_remb

		FROM lenders_accounts la
		LEFT JOIN clients c ON la.id_client_owner = c.id_client
		WHERE (SELECT COUNT(lo.id_loan) FROM loans lo WHERE lo.id_lender = la.id_lender_account AND LEFT(lo.added,4) <= "'.$annee.'") > 0
		ORDER BY c.id_client
		';
		
		

		$resultat = $this->bdd->query($sql);
		while($record = $this->bdd->fetch_array($resultat))
		{
			
			// cbéné 
			$p = substr($this->ficelle->stripAccents(utf8_decode(trim($record[1]))),0,1);
			$nom = $this->ficelle->stripAccents(utf8_decode(trim($record[2])));
			$id_client = $record[0];
			$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
			$cbene = substr($motif,0,10);
			
			// Interets
			$csv .= "1;";
			$csv .= $cbene.";";
			$csv .= "53;";
			$csv .= $date.";";
			$csv .= number_format(($record[3]/100), 2, ',', '').";";
			$csv .= "EURO;";
			$csv .= ";";
			$csv .= ";";
			
			$csv .= " \n";

			// personne morale OU resident fiscal etranger
			if($record[4] > 0){
				
				// Retenues à la source
				$csv .= "1;";
				$csv .= $cbene.";";
				$csv .= "2;";
				$csv .= $date.";";
				$csv .= number_format($record[4], 2, ',', '').";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
			}
			// Personne physique resident fr
			//else{
			
			if($record[5] > 0){
				// prélèvements obligatoires
				$csv .= "1;";
				$csv .= $cbene.";";
				$csv .= "54;";
				$csv .= $date.";";
				$csv .= number_format($record[5], 2, ',', '').";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
			}
			if($record[6] > 0){
				// interets resident uniquement
				$csv .= "1;";
				$csv .= $cbene.";";
				$csv .= "66;";
				$csv .= $date.";";
				$csv .= number_format(($record[6]/100), 2, ',', '').";";
				$csv .= "EURO;";
				$csv .= ";";
				$csv .= ";";
				
				$csv .= " \n";
			}
			////////////////////////////
			
			// capitaux souscrit
			$csv .= "1;";
			$csv .= $cbene.";";
			$csv .= "117;";
			$csv .= $date.";";
			$csv .= number_format(($record[7]/100), 2, ',', '').";";
			$csv .= "EURO;";
			$csv .= ";";
			$csv .= ";";
			
			$csv .= " \n";
			
			// Capitaux
			$csv .= "1;";
			$csv .= $cbene.";";
			$csv .= "118;";
			$csv .= $date.";";
			$csv .= number_format(($record[8]/100), 2, ',', '').";";
			$csv .= "EURO;";
			$csv .= ";";
			$csv .= ";";
			
			$csv .= " \n";
		}
		
		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		$titre = 'requete_revenus'.date('Ymd');
		header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
		print(utf8_decode($csv)); 
		exit;
		
		die;
		
	}
	
	function _requete_encheres()
	{
		
		//ini_set('max_execution_time', 300); //300 seconds = 5 minutes
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$header = "id_project;id_bid;id_client;added;statut;amount;rate;";
		$header = utf8_encode($header);
		
		$csv = "";
		$csv .= $header." \n";
		
		$sql = 'SELECT id_project, id_bid, (SELECT id_client_owner FROM lenders_accounts la WHERE la.id_lender_account = b.id_lender_account) as id_client, added, (case status when 0 then "En cours" when 1 then "OK" when 2 then "KO" end) as Statut, ROUND((amount/100),0), REPLACE(rate,".",",") as rate FROM bids b';

		$resultat = $this->bdd->query($sql);
        while($record = $this->bdd->fetch_array($resultat))
        {
			for($i=0;$i<=6;$i++){
           		$csv .= $record[$i].";";
			}
			$csv .= " \n";
        }
		

		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		$titre = 'toutes_les_encheres_'.date('Ymd');
		header("Content-type: application/vnd.ms-excel"); 
		header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
		print(utf8_decode($csv)); 
		exit;
		
		die;
		
	}
	
	
	function _tous_echeanciers_pour_projet()
	{
		if($_POST['form_envoi_params'] == "ok" && $_POST['id_projet'] != "" && isset($_POST['id_projet']))
		{
			
			//$this->echeanciers = $this->loadData('echeanciers');
			//$L_encheanciers = $this->echeanciers->get_echeancier_by_projet($_POST['id_projet']);
			
			
			// csv			
			$header = "Id_echeancier;Id_lender;Id_projet;Id_loan;Ordre;Montant;Capital;Capital_restant;Interets;Prelevements_obligatoires;Retenues_source;CSG;Prelevements_sociaux;Contributions_additionnelles;Prelevements_solidarite;CRDS;Date_echeance;Date_echeance_reel;Date_echeance_emprunteur;Date_echeance_emprunteur_reel;Status;";
			$header = utf8_encode($header);
			
			$csv = "";
			$csv .= $header." \n";
			
			
			$sql = '
				SELECT e.id_echeancier,
					e.id_lender,
					e.id_project,
					e.id_loan,
					e.ordre,
					e.montant,
					e.capital,
					(select sum(capital) FROM echeanciers e2 where e2.id_project = e.id_project and e2.id_lender = e.id_lender and e2.id_loan = e.id_loan and e2.ordre > e.ordre) as capitalRestant,
					e.interets,
					e.prelevements_obligatoires,
					e.retenues_source,
					e.csg,
					e.prelevements_sociaux,
					e.contributions_additionnelles,
					e.prelevements_solidarite,
					e.crds,date_echeance,
					e.date_echeance_reel,
					e.date_echeance_emprunteur,
					e.date_echeance_emprunteur_reel,
					status 
				FROM echeanciers e 
				WHERE e.id_project='.$_POST['id_projet'];

				$resultat = $this->bdd->query($sql);
				$result = array();
				while($record = $this->bdd->fetch_array($resultat))
				{
					$csv .= $record['id_echeancier'].";".$record['id_lender'].";".$record['id_project'].";".$record['id_loan'].";".$record['ordre'].";".$record['montant'].";".$record['capital'].";".$record['capitalRestant'].";".$record['interets'].";".$record['prelevements_obligatoires'].";".$record['retenues_source'].";".$record['csg'].";".$record['prelevements_sociaux'].";".$record['contributions_additionnelles'].";".$record['prelevements_solidarite'].";".$record['crds'].";".$record['date_echeance'].";".$record['date_echeance_reel'].";".$record['date_echeance_emprunteur'].";".$record['date_echeance_emprunteur_reel'].";".$record['status'].";";
						
					$csv .= " \n";	
					//$result[] = $record;
				}
			
				
				//********************//
				//*** Envoi du CSV ***//
				//********************//
				//$titre = $this->bdd->generateSlug($this->queries->name);
				$titre = 'tous_echeanciers_pour_projet_'.date('Ymd');
				header("Content-type: application/vnd.ms-excel"); 
				header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
				print(utf8_decode($csv)); 
				exit;
				
				die;
			

		}
	}
	
	function _infos_preteurs(){
		

		$this->sql = '
			SELECT 
			c.id_client,
			c.civilite,
			c.nom,
			c.nom_usage,
			c.prenom,
			c.fonction,
			c.naissance,
			c.telephone,
			c.email,
			c.source,
			ca.adresse1 as adresse,
			ca.cp,
			ca.ville,
			ca.adresse_fiscal,
			ca.ville_fiscal,
			ca.cp_fiscal,
			
			CASE l.exonere
			WHEN 1 THEN "Oui"
			ELSE "Non"
			END exonere,
			debut_exoneration,
			fin_exoneration,
			CASE 
			WHEN l.origine_des_fonds = 1 AND l.id_company_owner = 0 THEN "Revenu travail/retraite"
			WHEN l.origine_des_fonds = 2 AND l.id_company_owner = 0 THEN "Produit de la vente d\'un bien immobilier"
			WHEN l.origine_des_fonds = 3 AND l.id_company_owner = 0 THEN "Produit de la cession de mon entreprise / de mon fonds de commerce"
			WHEN l.origine_des_fonds = 4 AND l.id_company_owner = 0 THEN "epargne deja constituee"
			WHEN l.origine_des_fonds = 5 AND l.id_company_owner = 0 THEN "heritage / une donation"
			WHEN l.origine_des_fonds = 1 AND l.id_company_owner <> 0 THEN "Tresorerie existante"
			WHEN l.origine_des_fonds = 2 AND l.id_company_owner <> 0 THEN "Resultat dexploitation"
			WHEN l.origine_des_fonds = 3 AND l.id_company_owner <> 0 THEN "Resultat exceptionnel (dont vente d\'actifs)"
			WHEN l.origine_des_fonds = 4 AND l.id_company_owner <> 0 THEN "Augmentation de capital ou autre injection de liquidites"
			WHEN l.origine_des_fonds = 5 AND l.id_company_owner <> 0 THEN "Autres"
			ELSE "Autre"
			END origine_des_fonds,
			
			CASE l.id_company_owner
			WHEN 0 THEN "Personne physique" 
			ELSE (SELECT co.name FROM companies co WHERE co.id_company = l.id_company_owner)
			END Entreprise,
			
			comp.id_company as id_company,
			comp.forme as forme_juridique,
			comp.siren,
			
			CASE comp.execices_comptables
			WHEN 1 THEN "au moins trois exercices"
			ELSE "Non"
			END execices_comptables,
			
			comp.rcs,
			comp.tribunal_com,
			comp.activite,
			comp.lieu_exploi,
			comp.capital,
			comp.date_creation,
			comp.adresse1 as adresse_company,
			comp.zip as cp_company,
			comp.city as ville_company,
			comp.phone as telephone_company,
			
			CASE comp.status_client
			
			WHEN 1 THEN "Dirigeant"
			WHEN 2 THEN "Beneficie d\'une delegation de pouvoir"
			ELSE "externe a l\'entreprise"
			END status_client,
			
			CASE comp.status_conseil_externe_entreprise
			WHEN 1 THEN "Expert-comptable"
			WHEN 2 THEN "Courtier en credit"
			ELSE comp.preciser_conseil_externe_entreprise
			END status_conseil_externe_entreprise,
			
			comp.civilite_dirigeant,
			comp.nom_dirigeant,
			comp.prenom_dirigeant,
			comp.fonction_dirigeant,
			comp.email_dirigeant,
			comp.phone_dirigeant,
			comp.sector,
			comp.risk,
			
			SUBSTRING(l.iban,5,4) as code_banque
			FROM lenders_accounts l
			LEFT JOIN clients c ON l.id_client_owner = c.id_client
			LEFT JOIN clients_adresses ca ON l.id_client_owner = ca.id_client
			LEFT JOIN companies comp ON l.id_company_owner = comp.id_company
			WHERE c.status = 1
			AND status_pre_emp IN (1,3)
			ORDER BY l.added DESC';
			
			
			if(isset($this->params[0]) && $this->params[0] == 'csv'){
				
				$titre = 'infos_preteurs_'.date('Ymd');
				header("Content-type: application/vnd.ms-excel"); 
				header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
				
				// csv			
				$header = "id_client;civilite;nom;nom_usage;prenom;fonction;naissance;telephone;email;source;adresse;cp;ville;adresse_fiscal;ville_fiscal;cp_fiscal;exonere;debut_exoneration;fin_exoneration;origine_des_fonds;Entreprise;id_company;forme_juridique;siren;execices_comptables;rcs;tribunal_com;activite;lieu_exploi;capital;date_creation;adresse_company;cp_company;ville_company;telephone_company;status_client;status_conseil_externe_entreprise;civilite_dirigeant;nom_dirigeant;prenom_dirigeant;fonction_dirigeant;email_dirigeant;phone_dirigeant;sector;risk;code_banque";
				$header = utf8_encode($header);
				
				$csv = "";
				$csv .= $header." \n";
				
				$resultat = $this->bdd->query($this->sql);
				while($record = $this->bdd->fetch_array($resultat))
				{

					for($a=0;$a <=45;$a++){
						$csv .= $record[$a].";";
					}
					$csv .= " \n";
				}
				
				//********************//
				//*** Envoi du CSV ***//
				//********************//
				//$titre = $this->bdd->generateSlug($this->queries->name);
				
				print(utf8_decode($csv)); 
				exit;
				
				die;
			}
			
	}
	
	function _donnees_financieres_emprumteurs()
	{
		$this->sql = 'SELECT p.id_project as id_project, c.name,
			(SELECT cli.source FROM clients cli WHERE cli.id_client = c.id_client_owner) as source,
			title, p.added, 
			(SELECT label from projects_status ps where ps.`id_project_status` = (select `id_project_status` FROM projects_status_history psh where psh.id_project = p.id_project order by added desc limit 1)) as status, 
			c.altares_scoreVingt, c.risk, p.amount,p.period,
			(SELECT ca FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as ca2011,
			(SELECT ca FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as ca2012,
			(SELECT ca FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as ca2013,
			(SELECT ca FROM companies_bilans cb WHERE date=2014 and cb.id_company = c.id_company) as ca2014,
			
			(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rbe2011,
			(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rbe2012,
			(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rbe2013,
			(SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2014 and cb.id_company = c.id_company) as rbe2014,
			
			
			(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rex2011,
			(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rex2012,
			(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rex2013,
			(SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2014 and cb.id_company = c.id_company) as rex2014,
			
			
			(SELECT investissements FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as invest2011,
			(SELECT investissements FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as invest2012,
			(SELECT investissements FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as invest2013,
			(SELECT investissements FROM companies_bilans cb WHERE date=2014 and cb.id_company = c.id_company) as invest2014,
			
			
			(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immocorp2011,
			(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immocorp2012,
			(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immocorp2013,
			(SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as immocorp2014,
			
			
			(SELECT 	immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immoincorp2011,
			(SELECT 	immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immoincorp2012,
			(SELECT 	immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immoincorp2013,
			(SELECT 	immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as immoincorp2014,
			
			
			(SELECT 	immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immofin2011,
			(SELECT 	immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immofin2012,
			(SELECT 	immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immofin2013,
			(SELECT 	immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as immofin2014,
			
			
			(SELECT stocks FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as stock2011,
			(SELECT stocks FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as stock2012,
			(SELECT stocks FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as stock2013,
			(SELECT stocks FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as stock2014,
			
			
			(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as creances2011,
			(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as creances2012,
			(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as creances2013,
			(SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as creances2014,
			
			
			(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dispo2011,
			(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dispo2012,
			(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dispo2013,
			(SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as dispo2014,
			
			(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as valeursmob2011,
			(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as valeursmob2012,
			(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as valeursmob2013,
			(SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as valeursmob2014,
			
			(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as cp2011,
			(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as cp2012,
			(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as cp2013,
			(SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as cp2014,
			
			(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as provisions2011,
			(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as provisions2012,
			(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as provisions2013,
			(SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as provisions2014,
			
			(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as ammort2011,
			(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as ammort2012,
			(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as ammort2013,
			(SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as ammort2014,
			
			(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfin2011,
			(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfin2012,
			(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfin2013,
			(SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as dettesfin2014,
			
			(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfour2011,
			(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfour2012,
			(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfour2013,
			(SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as dettesfour2014,
			
			(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as autresdettes2011,
			(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as autresdettes2012,
			(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as autresdettes2013,
			(SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as autresdettes2014,
			c.forme,c.date_creation
			
			FROM projects p join companies c on c.id_company = p.id_company where id_project in (select `id_project` FROM projects_status_history psh)
			';
			
			if(isset($this->params[0]) && $this->params[0] == 'csv'){
				
				$titre = 'donnees_financieres_emprunteurs_'.date('Ymd');
				header("Content-type: application/vnd.ms-excel"); 
				header("Content-disposition: attachment; filename=\"".$titre.".csv\"");
				
				// csv			
				$header = "id_project;name;source;title;added;status;altares_scoreVingt;risk;amount;period;ca2011;ca2012;ca2013;ca2014;rbe2011;rbe2012;rbe2013;rbe2014;rex2011;rex2012;rex2013;rex2014;invest2011;invest2012;invest2013;invest2014;immocorp2011;immocorp2012;immocorp2013;immocorp2014;immoincorp2011;immoincorp2012;immoincorp2013;immoincorp2014;immofin2011;immofin2012;immofin2013;immofin2014;stock2011;stock2012;stock2013;stock2014;creances2011;creances2012;creances2013;creances2014;dispo2011;dispo2012;dispo2013;dispo2014;valeursmob2011;valeursmob2012;valeursmob2013;valeursmob2014;cp2011;cp2012;cp2013;cp2014;provisions2011;provisions2012;provisions2013;provisions2014;ammort2011;ammort2012;ammort2013;ammort2014;dettesfin2011;dettesfin2012;dettesfin2013;dettesfin2014;dettesfour2011;dettesfour2012;dettesfour2013;dettesfour2014;autresdettes2011;autresdettes2012;autresdettes2013;autresdettes2014;forme;date_creation";
				$header = utf8_encode($header);
				
				$csv = "";
				$csv .= $header." \n";
				
				$resultat = $this->bdd->query($this->sql);
				while($record = $this->bdd->fetch_array($resultat))
				{
					for($a=0;$a <=45;$a++){
						$csv .= $record[$a].";";
					}
					$csv .= " \n";
				}
				
				//********************//
				//*** Envoi du CSV ***//
				//********************//
				//$titre = $this->bdd->generateSlug($this->queries->name);
				
				print(utf8_decode($csv)); 
				exit;
				
				die;
			}
	}
	
}
