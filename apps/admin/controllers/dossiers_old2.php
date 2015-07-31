<?
class dossiersController extends bootstrap
{
	var $Command;
	
	function dossiersController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// Controle d'acces à la rubrique
		$this->users->checkAccess('dossiers');
		
		// Activation du menu
		$this->menu_admin = 'emprunteurs';
		
		// Login altares
		$this->settings->get('Altares login','type');
		$login = $this->settings->value;
		// Mdp altares
		$this->settings->get('Altares mot de passe','type');
		$mdp = $this->settings->value;
		// Url wsdl
		$this->settings->get('Altares wsdl','type');
		$this->wsdl = $this->settings->value;
		// Identification
		$this->identification = $login.'|'.$mdp;
		
		// Liste deroulante conseil externe de l'entreprise
		$this->settings->get("Liste deroulante conseil externe de l'entreprise",'type');
		$this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);
		
	}
	
	function _default()
	{
		// Chargement du data
		$this->projects_status = $this->loadData('projects_status');
		$this->projects = $this->loadData('projects');
		
		// liste les status
		$this->lProjects_status = $this->projects_status->select('',' status ASC ');
		
		// liste des users bo
		$this->lUsers = $this->users->select('status = 1 AND id_user_type = 2');
		
		
		if(isset($_POST['form_search_dossier']))
		{
			if($_POST['date1'] != '')
			{
				$d1 = explode('/',$_POST['date1']);
				$date1 = $d1[2].'-'.$d1[1].'-'.$d1[0];
			}
			else
			{
				$date1 = '';
			}
			
			if($_POST['date2'] != '')
			{
				$d2 = explode('/',$_POST['date2']);
				$date2 = $d2[2].'-'.$d2[1].'-'.$d2[0];
			}
			else
			{
				$date2 = '';
			}
			
			$this->lProjects = $this->projects->searchDossiers($date1,$date2,$_POST['montant'],$_POST['duree'],$_POST['status'],$_POST['analyste'],$_POST['siren'],$_POST['id'],$_POST['raison-sociale']);
			
		}
		// statut
		elseif(isset($this->params[0]))
		{
			$this->lProjects = $this->projects->searchDossiers('','','','',$this->params[0]);
		}
		
	}
	
	function _edit()
	{
		// Chargement du data
		$this->projects = $this->loadData('projects');
		$this->projects_notes = $this->loadData('projects_notes');
		$this->companies = $this->loadData('companies');
		$this->companies_bilans = $this->loadData('companies_bilans');
		$this->companies_details = $this->loadData('companies_details');
		$this->companies_actif_passif = $this->loadData('companies_actif_passif');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->projects_comments = $this->loadData('projects_comments');
		$this->projects_status = $this->loadData('projects_status');
		$this->current_projects_status = $this->loadData('projects_status');
		$this->projects_status_history = $this->loadData('projects_status_history');
		$this->loans = $this->loadData('loans');
		$this->projects_pouvoir = $this->loadData('projects_pouvoir');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->echeanciers = $this->loadData('echeanciers');
		
		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{
			$this->projects_notes->get($this->params[0],'id_project');
			
			// Liste deroulante secteurs
			$this->settings->get('Liste deroulante secteurs','type');
			$this->lSecteurs = explode(';',$this->settings->value);
			
			// Cabinet de recouvrement
			$this->settings->get('Cabinet de recouvrement','type');
			$this->cab = $this->settings->value;
			
			$this->settings->get('Heure debut periode funding','type');
			$this->debutFunding = $this->settings->value;
			
			$this->settings->get('Heure fin periode funding','type');
			$this->finFunding = $this->settings->value;
			
			$debutFunding = explode(':',$this->debutFunding);
			$this->HdebutFunding = $debutFunding[0];
			
			$finFunding = explode(':',$this->finFunding);
			$this->HfinFunding = $finFunding[0];
			
			// on check le statut, si c'est la premiere fois qu'il est consulté on le passe en "à l'étude"
			$this->current_projects_status->getLastStatut($this->projects->id_project);
			
			if($this->current_projects_status->status == 10)
			{
				$this->projects_status_history->addStatus($_SESSION['user']['id_user'],20,$this->projects->id_project);
				// on reactualise l'affichage
				$this->current_projects_status->getLastStatut($this->projects->id_project);
			}
			
			// On recup l'entreprise
			$this->companies->get($this->projects->id_company,'id_company');
			
			
			// On recup le detail de l'entreprise
			if(!$this->companies_details->get($this->projects->id_company,'id_company')){
				$this->companies_details->id_company = $this->projects->id_company;
				
				$this->companies_details->date_dernier_bilan 		= (date('Y')-1).'-12-31';
				$this->companies_details->date_dernier_bilan_mois 	= '12';
				$this->companies_details->date_dernier_bilan_annee  = (date('Y')-1);
				
				$this->companies_details->create();	
			}
			
			// On recup le client
			$this->clients->get($this->companies->id_client_owner,'id_client');
			
			//On recup adresses client
			$this->clients_adresses->get($this->companies->id_client_owner,'id_client');
			
			// liste des users bo
			$this->lUsers = $this->users->select('status = 1 AND id_user_type = 2');
			
			// meme adresse que le siege
			if($this->companies->status_adresse_correspondance == 1)
			{
				$this->adresse = $this->companies->adresse1;
				$this->city = $this->companies->city;
				$this->zip = $this->companies->zip;
				$this->phone = $this->companies->phone;
			}
			else
			{
				$this->adresse = $this->clients_adresses->adresse1;
				$this->city = $this->clients_adresses->ville;
				$this->zip = $this->clients_adresses->cp;
				$this->phone = $this->clients_adresses->telephone;
			}
			
			if($this->companies_details->date_dernier_bilan != '0000-00-00'){
				$dernierBilan = explode('-',$this->companies_details->date_dernier_bilan);
				$dernierBilan = $dernierBilan[0];
			}
			else $dernierBilan = date('Y');
			
			// Liste des actif passif
			$this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'"','annee DESC');
			
			// Si existe pas on créer les champs
			if($this->lCompanies_actif_passif == false)
			{
				$i = 1;
				foreach($this->lCompanies_actif_passif as $c)
				//for($i=1;$i<=3;$i++)
				{
					if($c['annee'] <= $dernierBilan){
						$a = 0;
						$this->companies_actif_passif->ordre = $i;
						$this->companies_actif_passif->annee = date('Y')-$a;
						$this->companies_actif_passif->id_company = $this->companies->id_company;
						$this->companies_actif_passif->create();
						$a++;
					}
				}
				
				header('location:'.$this->lurl.'/dossiers/edit/'.$this->params[0]);
        		die;
			}
			
			$this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'" AND annee <= "'.$dernierBilan.'"','annee DESC');
			
			// Debut mise a jour actif/passif //
			
			// On verifie si on a bien les 3 dernieres années
			$a = 1;
			foreach($this->lCompanies_actif_passif as $k => $cap)
			{
				// recuperation des années en bdd
				//$lesDates[$cap['ordre']] = $cap['annee'];
				$lesDates[$k] = $cap['annee'];
				$a++;
			}	
			
			if($this->companies_details->date_dernier_bilan != '0000-00-00'){
				$dernierBilan = explode('-',$this->companies_details->date_dernier_bilan);
				$dernierBilan = $dernierBilan[0];
				$date[1] = $dernierBilan;
				$date[2] = ($dernierBilan-1);
				$date[3] = ($dernierBilan-2);
			}
			else{
				$date[1] = date('Y');
				$date[2] = (date('Y')-1);
				$date[3] = (date('Y')-2);
			}
			$dates_nok = false;
			
			for($i=1;$i<=3;$i++)
			{
				// si premiere année existe pas on crée
				if(!in_array($date[$i],$lesDates))
				{
					$this->companies_actif_passif->annee = $date[$i];
					$this->companies_actif_passif->id_company = $this->companies->id_company;
					$this->companies_actif_passif->create();
					$dates_nok = true;
				}
			}
			
			if($this->lCompanies_actif_passif[0]['ordre'] != 1){
				$dates_nok = true;
			}
			
			if($dates_nok == true)
			{
				// on relance la Liste des actif passif classé par date DESC
				$this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'"','annee DESC');
				
				$i = 1;
				// On parcoure les lignes
				foreach($this->lCompanies_actif_passif as $k => $cap)
				{
					$this->companies_actif_passif->get($this->companies->id_company,'annee = "'.$cap['annee'].'" AND id_company');
					if($cap['annee'] <= $dernierBilan && $i <= 3){
					// On met a jour l'ordre
					$this->companies_actif_passif->ordre = $i;
					$i++;
					}
					else{
						$this->companies_actif_passif->ordre = 0;
					}
					$this->companies_actif_passif->update();
					// une fois qu'on a dépassé 3 années on supprimes les autres
					/*if($i>3)
					{
						$this->companies_actif_passif->delete($this->companies->id_company,'annee = "'.$cap['annee'].'" AND id_company');
					}*/
					
				}
				header('location:'.$this->lurl.'/dossiers/edit/'.$this->params[0]);
        		die;
			}
			
			// fin mise a jour actif/passif //
			
			// memo
			$this->lProjects_comments = $this->projects_comments->select('id_project = '.$this->projects->id_project,'added ASC');
			
			// on recup l'année du projet
			//$anneeProjet = explode('-',$this->projects->added);
			//$anneeProjet = $anneeProjet[0];
			
			/// date dernier bilan ///
			if($this->companies_details->date_dernier_bilan == '0000-00-00'){
				
				$this->date_dernier_bilan_jour = '31';
				$this->date_dernier_bilan_mois = '12';
				$this->date_dernier_bilan_annee = (date('Y')-1);
				
				$this->companies_details->date_dernier_bilan 		= (date('Y')-1).'-12-31';
				$this->companies_details->date_dernier_bilan_mois 	= '12';
				$this->companies_details->date_dernier_bilan_annee  = (date('Y')-1);
				
				$anneeProjet = (date('Y')-1);
				
			}
			else{
				$dateDernierBilan = explode('-',$this->companies_details->date_dernier_bilan);
				$this->date_dernier_bilan_jour = $dateDernierBilan[2];
				$this->date_dernier_bilan_mois = $dateDernierBilan[1];
				$this->date_dernier_bilan_annee = $dateDernierBilan[0];
				
				$anneeProjet = $dateDernierBilan[0];
			}
			
			/////////////////////////////
			
			$ldateBilan[4] = $anneeProjet+2;
			$ldateBilan[3] = $anneeProjet+1;
			$ldateBilan[2] = $anneeProjet;
			$ldateBilan[1] = $anneeProjet-1; 
			$ldateBilan[0] = $anneeProjet-2;
			//$ldateBilan[0] = $anneeProjet-3; 
			
			$ldateBilantrueYear[4] = $anneeProjet+2;
			$ldateBilantrueYear[3] = $anneeProjet+1;
			$ldateBilantrueYear[2] = $anneeProjet;
			$ldateBilantrueYear[1] = $anneeProjet-1; 
			$ldateBilantrueYear[0] = $anneeProjet-2;
			//$ldateBilantrueYear[0] = $anneeProjet-3; 

			// on recup les années bilans en se basant sur la date de creation du projet
			/*$ldateBilan[4] = $anneeProjet+1;
			$ldateBilan[3] = $anneeProjet;
			$ldateBilan[2] = $anneeProjet-1; 
			$ldateBilan[1] = $anneeProjet-2;
			$ldateBilan[0] = $anneeProjet-3; 
			
			$ldateBilantrueYear[4] = date('Y')+1;
			$ldateBilantrueYear[3] = date('Y');
			$ldateBilantrueYear[2] = date('Y')-1; 
			$ldateBilantrueYear[1] = date('Y')-2;
			$ldateBilantrueYear[0] = date('Y')-3; */
			
			// liste des bilans
			$this->lbilans = $this->companies_bilans->select('date BETWEEN "'.$ldateBilan[0].'" AND "'.$ldateBilan[4].'" AND id_company = '.$this->companies->id_company,'date ASC');
			
			////////////////////////////////////////////////
			// On verifie si on est a jour sur les années //
			
			// On recupe les années bilans qu'on a en bdd
			$tableAnneesBilans = array();
			foreach($this->lbilans as $b)
			{
				$tableAnneesBilans[$b['date']] = $b['date'];
			}
			// On parcour les années courrantes pour voir si on les a
			$creationbilansmanquant = false;
			foreach($ldateBilantrueYear as $annee)
            {
				// si existe pas on crée
				if(!in_array($annee,$tableAnneesBilans))
				{
					$this->companies_bilans->id_company = $this->companies->id_company;
					$this->companies_bilans->ca = '';
					$this->companies_bilans->resultat_exploitation = '';
					$this->companies_bilans->resultat_brute_exploitation = '';
					$this->companies_bilans->investissements = '';
					$this->companies_bilans->date = $annee;
					$this->companies_bilans->create();
					$creationbilansmanquant = true;
				}
			}
			if($creationbilansmanquant == true){
				header('location:'.$this->lurl.'/dossiers/edit/'.$this->params[0]);
        		die;
			}
			////////////////////////////////////////////////
			
			// liste les status
			// les statuts dispo sont conditionnés par le statut courant
			if($this->current_projects_status->status == 20)
				$this->lProjects_status = $this->projects_status->select(' status <= 20 ',' status ASC ');
			elseif(in_array($this->current_projects_status->status,array(35)))
				$this->lProjects_status = $this->projects_status->select(' status IN (35,40)',' status ASC ');
			elseif($this->current_projects_status->status >= 80)
				$this->lProjects_status = $this->projects_status->select(' status >= 80 ',' status ASC ');
			else
				$this->lProjects_status = array();
			
			
			/*$dateDernierBilan = explode('-',$this->companies_details->date_dernier_bilan);
			$this->date_dernier_bilan_jour = $dateDernierBilan[2];
			$this->date_dernier_bilan_mois = $dateDernierBilan[1];
			$this->date_dernier_bilan_annee = $dateDernierBilan[0];*/
			
			//******************//
			// On lance Altares //
			//******************//
			if(isset($this->params[1]) && $this->params[1] == 'altares')
			{
				// SIREN
				$this->siren = $this->companies->siren;
				// Web Service Altares
				$result = $this->ficelle->ws($this->wsdl,$this->identification,$this->siren);
				
				$this->altares_ok = false;
				
				// Si pas d'erreur
				if($result->exception == '')
				{
					// verif reponse
					$eligibility = $result->myInfo->eligibility;
					$score = $result->myInfo->score;
					$identite = $result->myInfo->identite;
					
					// statut
					$this->tablStatus = array('Oui','Pas de bilan');
					
					// date -3 ans
					$todayMoins3 = date('Y')-3;
					
					// On enregistre
					$this->companies->altares_eligibility = $eligibility;
					
					$dateValeur = substr($score->dateValeur,0,10);
					$this->companies->altares_dateValeur = $dateValeur;
					$this->companies->altares_niveauRisque = $score->niveauRisque;
					$this->companies->altares_scoreVingt = $score->scoreVingt;
					
					// si pas ok
					if($eligibility == 'Société radiée' || $eligibility == 'Non' || $eligibility == 'SIREN inconnu')
					{
						// Mise en session du message
						$_SESSION['freeow']['title'] = 'Donn&eacute;es Altares';
						$_SESSION['freeow']['message'] = 'soci&eacute;t&eacute; non &eacute;ligible';
						
						header('location:'.$this->lurl.'/dossiers/edit/'.$this->params[0]);
        				die;
					}
					// si pas ok 2
					//elseif(in_array($eligibility,$this->tablStatus) && $score->scoreVingt < 12 || in_array($eligibility,$this->tablStatus) && substr($identite->dateCreation,0,4) > $todayMoins3 )
					elseif(in_array($eligibility,$this->tablStatus) && substr($identite->dateCreation,0,4) > $todayMoins3)
					{
						// Mise en session du message
						$_SESSION['freeow']['title'] = 'Donn&eacute;es Altares';
						$_SESSION['freeow']['message'] = 'soci&eacute;t&eacute; non &eacute;ligible';
						
						header('location:'.$this->lurl.'/dossiers/edit/'.$this->params[0]);
        				die;
					}
					// si ok
					else
					{
						$this->altares_ok = true;
						
						
						$siege = $result->myInfo->siege;
						
						$identite = $result->myInfo->identite;

						$syntheseFinanciereInfo = $result->myInfo->syntheseFinanciereInfo;
						$syntheseFinanciereList = $result->myInfo->syntheseFinanciereInfo->syntheseFinanciereList;
						
						$posteActifList = array();
						$postePassifList = array();
						$syntheseFinanciereInfo = array();
						$syntheseFinanciereList = array();
						$derniersBilans = array();
						$i=0;
						foreach($result->myInfo->bilans as $b)
						{
	
							$annee = substr($b->bilan->dateClotureN,0,4);
							$posteActifList[$annee] = $b->bilanRetraiteInfo->posteActifList;
							$postePassifList[$annee] = $b->bilanRetraiteInfo->postePassifList;
							$syntheseFinanciereInfo[$annee] = $b->syntheseFinanciereInfo;
							$syntheseFinanciereList[$annee] = $b->syntheseFinanciereInfo->syntheseFinanciereList;
							
							$soldeIntermediaireGestionInfo[$annee] = $b->soldeIntermediaireGestionInfo->SIGList;
							
							$investissement[$annee] = $b->bilan->posteList[0]->valeur;
							
							// date des derniers bilans
							$derniersBilans[$i] = $annee;
							
							$i++;
						}
						
						
						
						$this->companies->name = $identite->raisonSociale;
						$this->companies->forme = $identite->formeJuridique;
						$this->companies->capital = $identite->capital;
						$this->companies->siret = $identite->siret;
						
						$this->companies->adresse1 = $identite->rue;
						$this->companies->city = $identite->ville;
						$this->companies->zip = $identite->codePostal;
						
						
						// on decoupe
						$dateCreation = substr($identite->dateCreation,0,10);
						// on enregistre
						$this->companies->date_creation = $dateCreation;
						// on fait une version fr
						$dateCreation = explode('-',$dateCreation);
						$this->date_creation = $dateCreation[2].'/'.$dateCreation[1].'/'.$dateCreation[0];
						
						
						// dernier bilan 
						$dateDernierBilanString = substr($identite->dateDernierBilan,0,10);
						$dateDernierBilan = explode('-',$dateDernierBilanSting);
						
						if($dateDernierBilanString == false || $dateDernierBilanString == '0000-00-00' ){
							
							$this->date_dernier_bilan_jour 	= '31';
							$this->date_dernier_bilan_mois 	= '12';
							$this->date_dernier_bilan_annee = (date('Y')-1);
							
							$this->companies_details->date_dernier_bilan 		= (date('Y')-1).'-12-31';
							$this->companies_details->date_dernier_bilan_mois 	= '12';
							$this->companies_details->date_dernier_bilan_annee  = (date('Y')-1);
						}
						else{
							$this->companies_details->date_dernier_bilan = $dateDernierBilanString;
							
							$this->date_dernier_bilan_jour = $dateDernierBilan[2];
							$this->date_dernier_bilan_mois = $dateDernierBilan[1];
							$this->date_dernier_bilan_annee = $dateDernierBilan[0];
						}
						
						// Mise a jour
						$this->companies_details->update();
						$this->companies->update();
						
						// date courrante
						$ldate[4] = date('Y')+1;
						$ldate[3] = date('Y');
						$ldate[2] = date('Y')-1; 
						$ldate[1] = date('Y')-2;
						$ldate[0] = date('Y')-3; 
						
						// on génère un tableau avec les données
						for($i=0;$i<5;$i++) // on parcourt les 5 années
						{
							for($a=0;$a<3;$a++)// on parcourt les 3 dernieres années
							{
								// si y a une année du bilan qui correxpond a une année du tableau
								if($derniersBilans[$a] == $ldate[$i])
								{
									// On recup les données de cette année
									
									$montant1 = $posteActifList[$ldate[$i]][1]->montant;
									$montant2 = $posteActifList[$ldate[$i]][2]->montant;
									$montant3 = $posteActifList[$ldate[$i]][3]->montant;
									$montant = $montant1+$montant2+$montant3;

									$this->companies_bilans->get($this->companies->id_company,'date = '.$ldate[$i].' AND id_company');
									$this->companies_bilans->ca = $syntheseFinanciereList[$ldate[$i]][0]->montantN;
									$this->companies_bilans->resultat_exploitation = $syntheseFinanciereList[$ldate[$i]][1]->montantN;
									$this->companies_bilans->resultat_brute_exploitation = $soldeIntermediaireGestionInfo[$ldate[$i]][9]->montantN;
									$this->companies_bilans->investissements = $investissement[$ldate[$i]];
									$this->companies_bilans->update();
									
								}
							}
	
						}
						
						// Debut actif/passif

						foreach($derniersBilans as $annees)
						{
							foreach($posteActifList[$annees] as $a)
							{
								$ActifPassif[$annees][$a->posteCle] = $a->montant;
							}
							foreach($postePassifList[$annees] as $p)
							{
								$ActifPassif[$annees][$p->posteCle] = $p->montant;
							}
						}

						$i=0;
						
						foreach($this->lCompanies_actif_passif as $k => $ap)
						{
							
							
							if($this->companies_actif_passif->get($ap['annee'],'id_company = '.$ap['id_company'].' AND annee'))
							{
								
								//$this->companies_actif_passif->annee = $derniersBilans[$i];
								//$this->companies_actif_passif->ordre = $i+1;
								
								// Actif
								$this->companies_actif_passif->immobilisations_corporelles = $ActifPassif[$ap['annee']]['posteBR_IMCOR'];
								$this->companies_actif_passif->immobilisations_incorporelles = $ActifPassif[$ap['annee']]['posteBR_IMMINC'];
								$this->companies_actif_passif->immobilisations_financieres = $ActifPassif[$ap['annee']]['posteBR_IMFI'];
								$this->companies_actif_passif->stocks = $ActifPassif[$ap['annee']]['posteBR_STO'];
								//creances_clients = Avances et acomptes + creances clients + autre creances et cca + autre creances hors exploitation
								$this->companies_actif_passif->creances_clients = $ActifPassif[$ap['annee']]['posteBR_BV']+$ActifPassif[$ap['annee']]['posteBR_BX']+$ActifPassif[$ap['annee']]['posteBR_ACCCA']+$ActifPassif[$ap['annee']]['posteBR_ACHE_'];
								$this->companies_actif_passif->disponibilites = $ActifPassif[$ap['annee']]['posteBR_CF'];
								$this->companies_actif_passif->valeurs_mobilieres_de_placement = $ActifPassif[$ap['annee']]['posteBR_CD'];
								
								// passif
								
								// capitaux_propres = capitaux propres + non valeurs
								$this->companies_actif_passif->capitaux_propres = $ActifPassif[$ap['annee']]['posteBR_CPRO']+$ActifPassif[$ap['annee']]['posteBR_NONVAL'];
								// provisions_pour_risques_et_charges = Provisions pour risques et charges + Provisions actif circulant
								$this->companies_actif_passif->provisions_pour_risques_et_charges = $ActifPassif[$ap['annee']]['posteBR_PROVRC']+$ActifPassif[$ap['annee']]['posteBR_PROAC'];
								
								$this->companies_actif_passif->amortissement_sur_immo = $ActifPassif[$ap['annee']]['posteBR_AMPROVIMMO'];
								// dettes_financieres = Emprunts + Dettes groupe et associés + Concours bancaires courants
								$this->companies_actif_passif->dettes_financieres = $ActifPassif[$ap['annee']]['posteBR_EMP']+$ActifPassif[$ap['annee']]['posteBR_VI']+$ActifPassif[$ap['annee']]['posteBR_EH'];
								
								// dettes_fournisseurs = Avances et Acomptes clients + Dettes fournisseurs
								$this->companies_actif_passif->dettes_fournisseurs = $ActifPassif[$ap['annee']]['posteBR_DW']+$ActifPassif[$ap['annee']]['posteBR_DX'];
								
								// autres_dettes = autres dettes exploi + Dettes sur immos et comptes rattachés + autres dettes hors exploi
								$this->companies_actif_passif->autres_dettes = $ActifPassif[$ap['annee']]['posteBR_AUTDETTEXPL']+$ActifPassif[$ap['annee']]['posteBR_DZ']+$ActifPassif[$ap['annee']]['posteBR_AUTDETTHEXPL'];
								$this->companies_actif_passif->update();
							}
							
							$i++;
						}
						// Fin actif/passif
						
						// Mise en session du message
						$_SESSION['freeow']['title'] = 'Donn&eacute;es Altares';
						$_SESSION['freeow']['message'] = 'Donn&eacute;es Altares r&eacute;cup&eacute;r&eacute; !';
					}
				}
				else
				{
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Donn&eacute;es Altares';
					$_SESSION['freeow']['message'] = 'Donn&eacute;es Altares &eacute;rreur !';
				}
				
				header('location:'.$this->lurl.'/dossiers/edit/'.$this->params[0]);
        		die;
			}
			
			// date valeur altares
			$dateValeur = explode('-',$this->companies->altares_dateValeur);
			$this->altares_dateValeur = $dateValeur[2].'/'.$dateValeur[1].'/'.$dateValeur[0];
			
			// Là dedans on traite plein de truc important
			//		_
			//		|
			//		|
			//		V
			//
			
			
			// Formulaire sauvegarder resume et actions
			if(isset($_POST['send_form_dossier_resume']))
			{
				
				// On check avant la validation que la date de publication & date de retrait sont OK sinon on bloque(KLE)
				/* La date de publication doit être au minimum dans 5min et la date de retrait à plus de 5min (pas de contrainte)*/
				$tab_date_pub_post = explode('/',$_POST['date_publication']);
				$date_publication_full_test = $tab_date_pub_post[2].'-'.$tab_date_pub_post[1].'-'.$tab_date_pub_post[0].' '.$_POST['date_publication_heure'].':'.$_POST['date_publication_minute'].':00';
				
				
				$tab_date_retrait_post = explode('/',$_POST['date_retrait']);
				$date_retrait_full_test = $tab_date_retrait_post[2].'-'.$tab_date_retrait_post[1].'-'.$tab_date_retrait_post[0].' '.$_POST['date_retrait_heure'].':'.$_POST['date_retrait_minute'].':00';
				
				$date_auj_plus_5min = date("Y-m-d H:i:s", mktime(date('H'), date('i')+5, date('s'), date("m"), date("d"),  date("Y")));
				$date_auj_plus_1jour = date("Y-m-d H:i:s", mktime(date('H'), date('i'), date('s'), date("m"), date("d")+1,  date("Y")));

				$dates_valide = false;
				if($date_publication_full_test > $date_auj_plus_5min && $date_retrait_full_test > $date_auj_plus_1jour ){
					$dates_valide =true;
				}
				
				$this->retour_dates_valides = "";
						
				if(!$dates_valide && in_array('40',array($_POST['status'],$this->current_projects_status->status))){
					$this->retour_dates_valides = "La date de publication du dossier doit être au minimum dans 5min et la date de retrait dans plus de 24h.";					
				}
				// si date valide
				else{
					// Histo user //
					$serialize = serialize(array('id_project' => $this->projects->id_project,'post' => $_POST));
					$this->users_history->histo(10,'dossier edit Resume & actions',$_SESSION['user']['id_user'],$serialize);
					////////////////
					
					// Projects
					
					// photo_projet
					if(isset($_FILES['photo_projet']) && $_FILES['photo_projet']['name'] != '')
					{
						$this->upload->setUploadDir($this->path,'public/default/var/images/photos_projets/');
						if($this->upload->doUpload('photo_projet'))
						{
							if($this->projects->photo_projet != '')@unlink($this->path.'public/default/var/images/photos_projets/'.$this->projects->photo_projet);
							$this->projects->photo_projet = $this->upload->getName();
						}
					}
					
					// photo_projet
					if(isset($_FILES['upload_pouvoir']) && $_FILES['upload_pouvoir']['name'] != '')
					{
						$this->upload->setUploadDir($this->path,'protected/pdf/pouvoir/');
						if($this->upload->doUpload('upload_pouvoir'))
						{
							if($this->projects_pouvoir->name != '')@unlink($this->path.'protected/pdf/pouvoir/'.$this->projects->photo_projet);
							$this->projects_pouvoir->name = $this->upload->getName();
							$this->projects_pouvoir->id_project = $this->projects->id_project;
							$this->projects_pouvoir->id_universign = 'no_universign';
							$this->projects_pouvoir->url_pdf = '/pdf/pouvoir/'.$this->clients->hash.'/'.$this->projects->id_project;
							$this->projects_pouvoir->status = 1;
							
							$this->projects_pouvoir->create();
						}
					}
					
					
					$this->projects->title = $_POST['title'];
					$this->projects->title_bo = $_POST['title_bo'];
					$this->projects->period = $_POST['duree'];
					$this->projects->nature_project = $_POST['nature_project'];
					$this->projects->amount = str_replace(' ','',str_replace(',','.',$_POST['montant']));
					$this->projects->target_rate = '-';
					$this->projects->id_analyste = $_POST['analyste'];
					$this->projects->lien_video = $_POST['lien_video'];
					$this->projects->display = $_POST['display_project'];
					
					
					// en prep funding
					if($this->current_projects_status->status >= 35) $this->projects->risk = $_POST['risk'];
					
					// --- Génération du slug --- //
					// Génération du slug avec titre projet fo
					if($this->current_projects_status->status <= 40)
					{
						$leSlugProjet = $this->ficelle->generateSlug($this->projects->title.'-'.$this->projects->id_project);
						$this->projects->slug = $leSlugProjet;
					}
					// Si slug n'existe pas encore c'est good
					//if($this->projects->select('slug = "'.$leSlugProjet.'"') == false)$this->projects->slug = $leSlugProjet;
					// Sinon on rajoute l'id du projet
					//else $this->projects->slug = $leSlugProjet.'-'.$this->projects->id_project;
					// on met a jour le projet
					$this->projects->update();
					// --- Fin Génération du slug --- //
					
					// en prep funding
					if($this->current_projects_status->status >= 35)
					{
						if(isset($_POST['date_publication'])){
							$this->projects->date_publication = $this->dates->formatDateFrToMysql($_POST['date_publication']);
							// Récupération des heures/minutes/sec
							$this->projects->date_publication_full = $this->projects->date_publication.' '.$_POST['date_publication_heure'].':'.$_POST['date_publication_minute'].':0';
						}
						if(isset($_POST['date_retrait'])){
							$this->projects->date_retrait = $this->dates->formatDateFrToMysql($_POST['date_retrait']);
							// Récupération des heures/minutes/sec
							$this->projects->date_retrait_full = $this->projects->date_retrait.' '.$_POST['date_retrait_heure'].':'.$_POST['date_retrait_minute'].':0';
						}
					}
					// Status
					if($this->current_projects_status->status != $_POST['status'])
					{
						$this->projects_status_history->addStatus($_SESSION['user']['id_user'],$_POST['status'],$this->projects->id_project);
						
						// Si statut a funder, en funding ou fundé
						if(in_array($_POST['status'],array(40,50,60)))
						{
							//mail('courtier.damien@gmail.com','alert change statut 1','statut : '.$_POST['status'].' projet : '.$this->projects->id_project );
							/////////////////////////////////////
							// Partie check données manquantes //
							/////////////////////////////////////
							
							$companies = $this->loadData('companies');
							$clients = $this->loadData('clients');
							$clients_adresses = $this->loadData('clients_adresses');
							
							// on recup la companie
							$companies->get($this->projects->id_company,'id_company');
							// et l'emprunteur
							$clients->get($companies->id_client_owner,'id_client');
							// son adresse
							$clients_adresses->get($companies->id_client_owner,'id_client');
							
							
							$mess = '<ul>';
							
							if($this->projects->title == '')$mess .= '<li>Titre projet</li>';
							if($this->projects->title_bo == '')$mess .= '<li>Titre projet BO</li>';
							if($this->projects->period == '0')$mess .= '<li>Periode projet</li>';
							if($this->projects->amount == '0')$mess .= '<li>Montant projet</li>';
							
							if($companies->name == '')$mess .= '<li>Nom entreprise</li>';
							if($companies->forme == '')$mess .= '<li>Forme juridique</li>';
							if($companies->siren == '')$mess .= '<li>SIREN entreprise</li>';
							if($companies->siret == '')$mess .= '<li>SIRET entreprise</li>';
							if($companies->iban == '')$mess .= '<li>IBAN entreprise</li>';
							if($companies->bic == '')$mess .= '<li>BIC entreprise</li>';
							if($companies->rcs == '')$mess .= '<li>RCS entreprise</li>';
							if($companies->tribunal_com == '')$mess .= '<li>Tribunal de commerce entreprise</li>';
							if($companies->capital == '0')$mess .= '<li>Capital entreprise</li>';
							if($companies->date_creation == '0000-00-00')$mess .= '<li>Date creation entreprise</li>';
							if($companies->sector == 0)$mess .= '<li>Secteur entreprise</li>';
							
							if($clients->nom == '')$mess .= '<li>Nom emprunteur</li>';
							if($clients->prenom == '')$mess .= '<li>Prenom emprunteur</li>';
							if($clients->fonction == '')$mess .= '<li>Fonction emprunteur</li>';
							if($clients->telephone == '')$mess .= '<li>Telephone emprunteur</li>';
							if($clients->email == '')$mess .= '<li>Email emprunteur</li>';
							
							if($clients_adresses->adresse1 == '')$mess .= '<li>Adresse emprunteur</li>';
							if($clients_adresses->cp == '')$mess .= '<li>CP emprunteur</li>';
							if($clients_adresses->ville == '')$mess .= '<li>Ville emprunteur</li>';
							
							$mess .= '</ul>';
							
							if(strlen($mess) > 9)
							{
								//mail('courtier.damien@gmail.com','alert change statut 2','statut : '.$_POST['status'].' projet : '.$this->projects->id_project .' strlen : '.strlen($mess).' mess : '.$mess);
								
								$to  = 'unilend@equinoa.fr' . ', ';
								$to  .= 'nicolas.lesur@unilend.fr';
								//$to  = 'courtier.damien@gmail.com';
							
								// subject
								$subject = '[Rappel] Donnees projet manquantes';
								
								// message
								$message = '
								<html>
								<head>
								  <title>[Rappel] Donnees projet manquantes</title>
								</head>
								<body>
									<p>Un projet qui vient d\'etre publie ne dispose pas de toutes les donnees necessaires</p>
									<p>Listes des informations manquantes sur le projet '.$this->projects->id_project.' : </p>
									'.$mess.'
								</body>
								</html>
								';
								
								// To send HTML mail, the Content-type header must be set
								$headers  = 'MIME-Version: 1.0' . "\r\n";
								$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
								
								// Additional headers
								
								$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
								//$headers .= 'From: Unilend <courtier.damien@gmail.com>' . "\r\n";
								
								// Mail it
								mail($to, $subject, $message, $headers);
							}
						}
						
						// si statut = default
						if($_POST['status'] == '120')
						{
							
							// on envoie un mail aux preteurs
							$lPreteurs = $this->loans->select('id_project = '.$this->projects->id_project);
							
							$this->companies->get($this->projects->id_company,'id_company');
							
							// FB
							$this->settings->get('Facebook','type');
							$lien_fb = $this->settings->value;
							
							// Twitter
							$this->settings->get('Twitter','type');
							$lien_tw = $this->settings->value;
							
							//if($lPreteurs != false)
//							{
//								foreach($lPreteurs as $p)
//								{
//									$this->lenders_accounts->get($p['id_lender'],'id_lender_account');
//									$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
//									
//									// on recup la premiere echeance
//									
//									
//									////////////////////////////////////////////
//									// on recup la somme deja remb du preteur //
//									////////////////////////////////////////////
//									$lEchea = $this->echeanciers->select('id_loan = '.$p['id_loan'].' AND id_project = '.$this->projects->id_project.' AND status = 1');
//									$rembNet = 0;
//									foreach($lEchea as $e)
//									{
//										// on fait la somme de tout
//										$rembNet += ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
//									}
//									////////////////////////////////////////////
//									
//									// le mail pour le lender
//									
//									// Motif virement
//									$p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)),0,1);
//									$nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
//									$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
//									$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
//									
//									//********************************************************//
//									//*** ENVOI DU MAIL STATUT DOSSIER DEFAUT POUR PRETEUR ***//
//									//********************************************************//
//									
//									// Recuperation du modele de mail
//									$this->mails_text->get('preteur-dossier-defaut','lang = "'.$this->language.'" AND type');
//									
//									// Variables du mailing
//									$surl = $this->surl;
//									$url = $this->furl;
//									$projet = $this->projects->title;
//									
//									// Variables du mailing
//									$varMail = array(
//									'surl' => $surl,
//									'url' => $url,
//									'prenom_p' => $this->clients->prenom,
//									'cab_recouvrement' => $this->cab,
//									'nom_entreprise' => $this->companies->name,
//									'montant_rembourse' => number_format($rembNet, 2, ',', ' '),
//									'valeur_bid' => number_format($p['amount']/100, 2, ',', ' '),
//									'motif_virement' => $motif,
//									'lien_fb' => $lien_fb,
//									'lien_tw' => $lien_tw);	
//									
//									
//									// Construction du tableau avec les balises EMV
//									$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
//									
//									/*echo '<pre>';
//									print_r($tabVars);
//									echo '</pre>';*/
//									
//									// Attribution des données aux variables
//									$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);				
//									$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
//									$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
//									
//									// Envoi du mail
//									/*$this->email = $this->loadLib('email',array());
//									$this->email->setFrom($this->mails_text->exp_email,$exp_name);
//									$this->email->setSubject(stripslashes($sujetMail));
//									$this->email->setHTMLBody(stripslashes($texteMail));
//									
//									if($this->Config['env'] == 'prod') // nmp
//									{
//										Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->clients->email,$tabFiler);			
//										// Injection du mail NMP dans la queue
//										$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
//									}
//									else // non nmp
//									{
//										$this->email->addRecipient(trim($this->clients->email));
//										Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
//									}*/
//									// fin mail lender
//									
//								}
//							}
						}
						// fin statut probleme
						elseif($_POST['status'] == '100')
						{
							$lPreteurs = $this->loans->select('id_project = '.$this->projects->id_project);
							
							$this->companies->get($this->projects->id_company,'id_company');
							
							// FB
							$this->settings->get('Facebook','type');
							$lien_fb = $this->settings->value;
							
							// Twitter
							$this->settings->get('Twitter','type');
							$lien_tw = $this->settings->value;
							
							if($lPreteurs != false)
							{
								foreach($lPreteurs as $p)
								{
									$this->lenders_accounts->get($p['id_lender'],'id_lender_account');
									$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
									
									// on recup la premiere echeance
									
									// Motif virement
									$lettrePrenom = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)),0,1);
									$nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
									$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
									$motif = mb_strtoupper($id_client.$lettrePrenom.$nom,'UTF-8');
									
									////////////////////////////////////////////
									// on recup la somme deja remb du preteur //
									////////////////////////////////////////////
									$lEchea = $this->echeanciers->select('id_loan = '.$p['id_loan'].' AND id_project = '.$this->projects->id_project.' AND status = 1');
									$rembNet = 0;
									foreach($lEchea as $e)
									{
										// on fait la somme de tout
										$rembNet += ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
									}
									////////////////////////////////////////////
							
									//**************************************//
									//*** ENVOI DU MAIL PROBLEME PRETEUR ***//
									//**************************************//
						
									// Recuperation du modele de mail
									$this->mails_text->get('preteur-erreur-remboursement','lang = "'.$this->language.'" AND type');
									
									// Variables du mailing
									$varMail = array(
									'surl' => $this->surl,
									'url' => $this->furl,
									'prenom_p' => $this->clients->prenom,
									'valeur_bid' => number_format($p['amount']/100, 2, ',', ' '),
									'nom_entreprise' => $this->companies->name,
									'montant_rembourse' => number_format($rembNet, 2, ',', ' '),
									'cab_recouvrement' =>  $this->cab,
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
									// fin mail pour preteur //
								}
							}
						}
						// fin statut recouvrement
						elseif($_POST['status'] == '110')
						{
							
							// date du dernier probleme
							$statusProbleme = $this->projects_status_history->select('id_project = '.$this->projects->id_project.' AND id_project_status = 9','added DESC');
							$DateProbleme = date('d/m/Y',strtotime($statusProbleme[0]['added']));
							
							$lPreteurs = $this->loans->select('id_project = '.$this->projects->id_project);
							
							$this->companies->get($this->projects->id_company,'id_company');
							
							// FB
							$this->settings->get('Facebook','type');
							$lien_fb = $this->settings->value;
							
							// Twitter
							$this->settings->get('Twitter','type');
							$lien_tw = $this->settings->value;
							
							if($lPreteurs != false)
							{
								foreach($lPreteurs as $p)
								{
									$this->lenders_accounts->get($p['id_lender'],'id_lender_account');
									$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
									
									// Motif virement
									$p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)),0,1);
									$nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
									$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
									$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
									
									//******************************************//
									//*** ENVOI DU MAIL RECOUVREMENT PRETEUR ***//
									//******************************************//
						
									// Recuperation du modele de mail
									$this->mails_text->get('preteur-dossier-recouvrement','lang = "'.$this->language.'" AND type');
									
				
									// Variables du mailing
									$varMail = array(
									'surl' => $this->surl,
									'url' => $this->furl,
									'prenom_p' => $this->clients->prenom,
									'date_probleme' => $DateProbleme,
									'cab_recouvrement' => $this->cab,
									'nom_entreprise' => $this->companies->name,
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
									/*$this->email = $this->loadLib('email',array());
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
									}*/
									// fin mail pour preteur //	
								}
							}
						}
						// remboursé
						elseif($_POST['status'] == '90')
						{
							// date du dernier probleme
							$statusProbleme = $this->projects_status_history->select('id_project = '.$this->projects->id_project.' AND id_project_status = 9','added DESC');
							$DateProbleme = date('d/m/Y',strtotime($statusProbleme[0]['added']));
							
							$lPreteurs = $this->loans->select('id_project = '.$this->projects->id_project);
							
							$this->companies->get($this->projects->id_company,'id_company');
							
							// FB
							$this->settings->get('Facebook','type');
							$lien_fb = $this->settings->value;
							
							// Twitter
							$this->settings->get('Twitter','type');
							$lien_tw = $this->settings->value;
							
							if($lPreteurs != false)
							{
								foreach($lPreteurs as $p)
								{
									$this->lenders_accounts->get($p['id_lender'],'id_lender_account');
									$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
							
									//******************************************//
									//*** ENVOI DU MAIL RECOUVREMENT PRETEUR ***//
									//******************************************//
						
									// Recuperation du modele de mail
									$this->mails_text->get('preteur-dossier-recouvrement','lang = "'.$this->language.'" AND type');
									
				
									// Variables du mailing
									$varMail = array(
									'surl' => $this->surl,
									'url' => $this->furl,
									'prenom_p' => $this->clients->prenom,
									'date_probleme' => $DateProbleme,
									'cab_recouvrement' => $this->cab,
									'nom_entreprise' => $this->companies->name,
									'lien_fb' => $lien_fb,
									'lien_tw' => $lien_tw);	
									
									// Construction du tableau avec les balises EMV
									$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
									
									/*echo '<pre>';
									print_r($tabVars);
									echo '</pre>';*/
									
									// Attribution des données aux variables
									/*$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);				
									$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
									$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
									
									// Envoi du mail
									$this->email = $this->loadLib('email',array());
									$this->email->setFrom($this->mails_text->exp_email,$exp_name);
									//$this->email->addRecipient(trim($this->clients->email));
									//$this->email->addBCCRecipient($this->clients->email);
									
									$this->email->setSubject(stripslashes($sujetMail));
									$this->email->setHTMLBody(stripslashes($texteMail));
									Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.courtier@equinoa.com',$tabFiler);
												
									// Injection du mail NMP dans la queue
									$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);*/
									
									// fin mail pour preteur //	
								}
							}
						}
						// fin statut remboursé
						
					}
					
					// Companies
					$this->companies->siren = $_POST['siren'];
					$this->companies->siret = $_POST['siret'];
					$this->companies->name = $_POST['societe'];
					$this->companies->rcs = $_POST['rcs'];
					$this->companies->sector = $_POST['sector'];
					$this->companies->id_client_owner = $_POST['id_client'];
					//$this->companies->risk = $_POST['risk'];
					
					$this->companies->tribunal_com = $_POST['tribunal_com'];
					$this->companies->activite = $_POST['activite'];
					$this->companies->lieu_exploi = $_POST['lieu_exploi'];
					
					if($this->companies->status_adresse_correspondance == 1)
					{
						$this->companies->adresse1 = $_POST['adresse'];
						$this->companies->city = $_POST['city'];
						$this->companies->zip = $_POST['zip'];
						$this->companies->phone = $_POST['phone'];
					}
					else
					{
						$this->clients_adresses->adresse1 = $_POST['adresse'];
						$this->clients_adresses->ville = $_POST['city'];
						$this->clients_adresses->cp = $_POST['zip'];
						$this->clients_adresses->telephone = $_POST['phone'];
					}
					
					// Clients
					$this->clients->get($this->companies->id_client_owner,'id_client');
					$this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
					$this->clients->nom = $this->ficelle->majNom($_POST['nom']);
					
					$this->projects->update();
					$this->companies->update();
					$this->clients->update();
					$this->clients_adresses->update();
					
					
					// PRET REFUSE //
					
					if(isset($_POST['pret_refuse']) && $_POST['pret_refuse'] == 1)
					{
						
						// Chargement des datas
						$loans = $this->loadData('loans');
						$transactions = $this->loadData('transactions');
						$lenders = $this->loadData('lenders_accounts');
						$clients = $this->loadData('clients');
						$wallets_lines = $this->loadData('wallets_lines');
						$companies = $this->loadData('companies');
						$projects = $this->loadData('projects');
					
					
						// FB
						$this->settings->get('Facebook','type');
						$lien_fb = $this->settings->value;
						
						// Twitter
						$this->settings->get('Twitter','type');
						$lien_tw = $this->settings->value;
						
					
						$nb_loans = $loans->getNbPreteurs($this->projects->id_project);
						
						// On passe le projet en remboursement
						$this->projects_status_history->addStatus($_SESSION['user']['id_user'],75,$this->projects->id_project);
						$lesloans = $loans->select('id_project = '.$this->projects->id_project);
						
						foreach($lesloans as $l)
						{
							
							// On regarde si on a pas deja un remb pour ce bid
							
							if($transactions->get($l['id_loan'],'id_loan_remb')==false)
							{
							
								// On recup le projet
								$projects->get($l['id_project'],'id_project');
								// On recup l'entreprise
								$companies->get($projects->id_company,'id_company');
								
								// On recup lender
								$lenders->get($l['id_lender'],'id_lender_account');
								// on recup les infos du lender
								$clients->get($lenders->id_client_owner,'id_client');
								
								// On change le satut des loans du projet refusé
								$loans->get($l['id_loan'],'id_loan');
								$loans->status = 1;
								$loans->update();
								
								// On redonne l'argent aux preteurs
								
													
								// On enregistre la transaction
								$transactions->id_client = $lenders->id_client_owner;
								$transactions->montant = $l['amount'];
								$transactions->id_langue = 'fr';
								$transactions->id_loan_remb = $l['id_loan'];
								$transactions->date_transaction = date('Y-m-d H:i:s');
								$transactions->status = '1';
								$transactions->etat = '1';
								$transactions->ip_client = $_SERVER['REMOTE_ADDR'];
								$transactions->type_transaction = 2; 
								$transactions->transaction = 2; // transaction virtuelle
								$transactions->id_transaction = $transactions->create();
								
								
								// on enregistre la transaction dans son wallet
								$wallets_lines->id_lender = $l['id_lender'];
								$wallets_lines->type_financial_operation = 20;
								$wallets_lines->id_transaction = $transactions->id_transaction;
								$wallets_lines->status = 1;
								$wallets_lines->type = 2;
								$wallets_lines->amount = $l['amount'];
								$wallets_lines->id_wallet_line = $wallets_lines->create();
								
								// Motif virement
								$p = substr($this->ficelle->stripAccents(utf8_decode($clients->prenom)),0,1);
								$nom = $this->ficelle->stripAccents(utf8_decode($clients->nom));
								$id_client = str_pad($clients->id_client,6,0,STR_PAD_LEFT);
								$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
								
								//**************************************//
								//*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
								//**************************************//
								
								// Recuperation du modele de mail
								$this->mails_text->get('preteur-pret-refuse','lang = "'.$this->language.'" AND type');
		
					
								// Variables du mailing
								$varMail = array(
								'surl' => $this->surl,
								'url' => $this->furl,
								'prenom_p' => $clients->prenom,
								'valeur_bid' => number_format($l['amount']/100, 0, ',', ' '),
								'nom_entreprise' => $companies->name,
								'nb_preteurMoinsUn' => ($nb_loans-1),
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
									Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$clients->email,$tabFiler);		
									// Injection du mail NMP dans la queue
									$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
								}
								else // non nmp
								{
									$this->email->addRecipient(trim($clients->email));
									Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
								}
							}
							
						}
		
					}
					
					/////////////////
					
					
					
					// REMBOURSEMENT //
					
					// si on a le pouvoir
					if($this->projects_pouvoir->get($this->projects->id_project,'id_project') && $this->projects_pouvoir->status_remb == 0)
					{
						mail('unilend@equinoa.fr','[ALERTE] Controle statut remboursement Debut','[ALERTE] Controle statut remboursement pour le projet : '.$this->projects->id_project.' - '.date('Y-m-d H:i:s').' - '.$this->Config['env']);
						
						$this->projects_pouvoir->status_remb = $_POST['satut_pouvoir'];
						$this->projects_pouvoir->update();
						
						// si on a validé le pouvoir
						if($this->projects_pouvoir->status_remb == 1)
						{
							
							// debut processe chagement statut remboursement //
							
							// On recup le param
							$settingsControleRemb = $this->loadData('settings');
							$settingsControleRemb->get('Controle statut remboursement','type');
							
							// on rentre dans le cron si statut égale 1 
							if($settingsControleRemb->value == 1)
							{
								// On passe le statut a zero pour signaler qu'on est en cours de traitement
								$settingsControleRemb->value = 0;
								$settingsControleRemb->update();
							
								// On passe le projet en remboursement
								$this->projects_status_history->addStatus($_SESSION['user']['id_user'],80,$this->projects->id_project);
								
								
								//*** virement emprunteur ***//
								
								// Chargement du data
								$this->transactions = $this->loadData('transactions');
								$virements = $this->loadData('virements');
								$prelevements = $this->loadData('prelevements');
								$bank_unilend = $this->loadData('bank_unilend');
								$loans = $this->loadData('loans');
								$echeanciers = $this->loadData('echeanciers');
								$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
								$companies = $this->loadData('companies');
								
								// Part unilend
								$this->settings->get('Part unilend','type');
								$PourcentageUnliend = $this->settings->value;
								
								// montant
								$montant = $loans->sumPretsProjet($this->projects->id_project);
								
								// part unilend
								$partUnliend = ($montant*$PourcentageUnliend);
								
								// montant - la part unilend
								$montant -= $partUnliend;
								
								// si existe pas
								if($this->transactions->get($this->projects->id_project,'type_transaction = 9 AND id_project')==false)
								{
								
									// transaction
									$this->transactions->id_client = $this->clients->id_client;
									$this->transactions->montant = '-'.($montant*100); // moins car c'est largent qui part d'unilend
									$this->transactions->montant_unilend = ($partUnliend*100);
									$this->transactions->id_langue = 'fr';
									$this->transactions->id_project = $this->projects->id_project;
									$this->transactions->date_transaction = date('Y-m-d H:i:s');
									$this->transactions->status = '1'; // pas d'attente on valide a lenvoie
									$this->transactions->etat = '1'; // pas d'attente on valide a lenvoie
									$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
									$this->transactions->civilite_fac = $this->clients->civilite;
									$this->transactions->nom_fac = $this->clients->nom;
									$this->transactions->prenom_fac = $this->clients->prenom;
									if($this->clients->type == 2)$this->transactions->societe_fac = $this->companies->name;
									$this->transactions->adresse1_fac = $this->clients_adresses->adresse1;
									$this->transactions->cp_fac = $this->clients_adresses->cp;
									$this->transactions->ville_fac = $this->clients_adresses->ville;
									$this->transactions->id_pays_fac = $this->clients_adresses->id_pays;
									$this->transactions->type_transaction = 9; // on signal que c'est un virement emprunteur
									$this->transactions->transaction = 1; // transaction physique
									$this->transactions->id_transaction = $this->transactions->create();
									
									//bank_unilend
									$bank_unilend->id_transaction = $this->transactions->id_transaction;
									$bank_unilend->id_project = $this->projects->id_project;
									$bank_unilend->montant = $partUnliend*100;
									$bank_unilend->create();
	
									// Motif mandat emprunteur
									/*$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
									$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
									$id_project = str_pad($this->projects->id_project,6,0,STR_PAD_LEFT);
									$motif = mb_strtoupper('UNILEND'.$id_project.'E'.$p.$nom,'UTF-8');*/
									
									// Motif mandat emprunteur
									$motif = $this->ficelle->motif_mandat($this->clients->prenom,$this->clients->nom,$this->projects->id_project);
									
									
									//virements
									$virements->id_client = $this->clients->id_client;
									$virements->id_project = $this->projects->id_project;
									$virements->id_transaction = $this->transactions->id_transaction;
									$virements->montant = ($montant*100);
									$virements->motif = $motif;
									$virements->type = 2;
									$virements->create();
									
									
									// mail emprunteur facture a la fin
									
									//*** fin virement emprunteur ***//
									
									
									//*** prelevement emprunteur ***//
									
									
									$prelevements = $this->loadData('prelevements');
									
									$jo = $this->loadLib('jours_ouvres');
			
									// On recup les echeances de remb emprunteur
									//$echea = $echeanciers->getSumRembEmpruntByMonths($this->projects->id_project);
									$echea = $echeanciers_emprunteur->select('id_project = '.$this->projects->id_project);
									
									foreach($echea as $key => $e)
									{
										
										/*if($e['ordre'] == 1)
										{
											//retourne la date - 8 jours - les jours non ouvrés entre
											$result = $jo->getNbJourNonOuvre(strtotime($e['date_echeance_emprunteur']),8,'1');
										}
										else
										{
											//retourne la date - 2 jours ouvrés
											$result = $jo->getNbJourNonOuvre(strtotime($e['date_echeance_emprunteur']),5,'1');
										}
										
										// date n - jour ouvré avant date de remb
										$dateExec = date('Y-m-d',strtotime($result));*/
										
										$dateEcheEmp = strtotime($e['date_echeance_emprunteur']);
										$result = mktime(0,0,0, date("m",$dateEcheEmp), date("d",$dateEcheEmp)-15, date("Y",$dateEcheEmp));
										$dateExec = date('Y-m-d',$result);
										
										
										
										// montant emprunteur a remb
										$montant = $echeanciers->getMontantRembEmprunteur($e['montant'],$e['commission'],$e['tva']);
									
										// on enregistre le prelevement recurent a effectuer chaque mois
										$prelevements->id_client = $this->clients->id_client;
										$prelevements->id_project = $this->projects->id_project;
										$prelevements->motif = $motif;
										$prelevements->montant = $montant;
										$prelevements->bic = str_replace(' ','',$this->companies->bic); // bic
										$prelevements->iban = str_replace(' ','',$this->companies->iban);
										$prelevements->type_prelevement = 1; // recurrent
										$prelevements->type = 2; //emprunteur
										$prelevements->num_prelevement = $e['ordre'];
										$prelevements->date_execution_demande_prelevement = $dateExec;
										$prelevements->date_echeance_emprunteur = $e['date_echeance_emprunteur'];
										$prelevements->create();
									}
									//*** fin prelevement emprunteur ***// 
									
									
									// les contrats a envoyer //
									
									
									$lLoans = $this->loans->select('id_project = '.$this->projects->id_project);
									
									$preteur = $this->loadData('clients');
									$lender = $this->loadData('lenders_accounts');
									$leProject = $this->loadData('projects');
									$laCompanie = $this->loadData('companies');
									
									// FB
									$this->settings->get('Facebook','type');
									$lien_fb = $this->settings->value;
									
									// Twitter
									$this->settings->get('Twitter','type');
									$lien_tw = $this->settings->value;
									
									foreach($lLoans as $l)
									{
										// lender
										$lender->get($l['id_lender'],'id_lender_account');
										// preteur (client)
										$preteur->get($lender->id_client_owner,'id_client');
										
										
										// Motif virement
										$p = substr($this->ficelle->stripAccents(utf8_decode(trim($preteur->prenom))),0,1);
										$nom = $this->ficelle->stripAccents(utf8_decode(trim($preteur->nom)));
										$id_client = str_pad($preteur->id_client,6,0,STR_PAD_LEFT);
										$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
										
										//******************************//
										//*** ENVOI DU MAIL CONTRAT ***//
										//******************************//
										
										// Recuperation du modele de mail
										$this->mails_text->get('preteur-contrat','lang = "'.$this->language.'" AND type');
										
										$lecheancier = $echeanciers->getPremiereEcheancePreteurByLoans($l['id_project'],$l['id_lender'],$l['id_loan']);
										
										
										
										$leProject->get($l['id_project'],'id_project');
										$laCompanie->get($leProject->id_company,'id_company');
									
										// Variables du mailing
										$surl = $this->surl;
										$url = $this->furl;
										$prenom = $preteur->prenom;
										$projet = $this->projects->title;
										$montant_pret = number_format($l['amount']/100, 2, ',', ' ');
										$taux = number_format($l['rate'], 2, ',', ' ');
										$entreprise = $laCompanie->name;
										$date = $this->dates->formatDate($l['added'],'d/m/Y');
										$heure = $this->dates->formatDate($l['added'],'H');
										$duree = $this->projects->period;
										$link_contrat = $this->furl.'/pdf/contrat/'.$preteur->hash.'/'.$l['id_loan'];
										
										$timeAdd = strtotime($lecheancier['date_echeance']);
										$month = $this->dates->tableauMois['fr'][date('n',$timeAdd)];
							
										// Variables du mailing
										$varMail = array(
										'surl' => $surl,
										'url' => $url,
										'prenom_p' => $prenom,
										'valeur_bid' => $montant_pret,
										'taux_bid' => $taux,
										'nom_entreprise' => $entreprise,
										'nbre_echeance' => $duree,
										'mensualite_p' => number_format($lecheancier['montant']/100, 2, ',', ' '),
										'date_debut' => date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd),
										'compte-p' => $this->furl,
										'projet-p' => $this->furl.'/projects/detail/'.$this->projects->slug,
										'link_contrat' => $link_contrat,
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
											Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($preteur->email),$tabFiler);			
											// Injection du mail NMP dans la queue
											$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
										}
										else // non nmp
										{
											$this->email->addRecipient(trim($preteur->email));
											Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
										}
										// fin mail
			
									}
								}
								
								
								// Renseigner l'id projet
								$id_project = $this->projects->id_project;
								
								$month = $this->dates->tableauMois['fr'][date('n')];
								$dateStatutRemb = date('d').' '.$month.' '.date('Y');
								
								//********************************//
								//*** ENVOI DU MAIL FACTURE EF ***//
								//********************************//
								
								// Recuperation du modele de mail
								$this->mails_text->get('facture-emprunteur','lang = "'.$this->language.'" AND type');
								
								$leProject = $this->loadData('projects');
								$lemprunteur = $this->loadData('clients');
								$laCompanie = $this->loadData('companies');
								
								$leProject->get($id_project,'id_project');
								$laCompanie->get($leProject->id_company,'id_company');
								$lemprunteur->get($laCompanie->id_client_owner,'id_client');
								
								// FB
								$this->settings->get('Facebook','type');
								$lien_fb = $this->settings->value;
								
								// Twitter
								$this->settings->get('Twitter','type');
								$lien_tw = $this->settings->value;
								
								// Variables du mailing
								$varMail = array(
								'surl' =>  $this->surl,
								'url' => $this->furl,
								'prenom' => $lemprunteur->prenom,
								'entreprise' => $laCompanie->name,
								'pret' => number_format($leProject->amount, 2, ',', ' '),
								'projet-title' => $leProject->title,
								'compte-p' => $this->furl,
								'projet-p' => $this->furl.'/projects/detail/'.$leProject->slug,
								'link_facture' => $this->furl.'/pdf/facture_EF/'.$lemprunteur->hash.'/'.$leProject->id_project.'/',
								'datedelafacture' => $dateStatutRemb,
								'mois' => strtolower($this->dates->tableauMois['fr'][date('n')]),
								'annee' => date('Y'),
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
								if($this->Config['env'] == 'prod')
								{
									$this->email->addBCCRecipient('nicolas.lesur@unilend.fr');
									$this->email->addBCCRecipient('d.nandji@equinoa.com');
									
									//$this->email->addBCCRecipient('d.courtier@equinoa.com');
									//$this->email->addBCCRecipient('courtier.damien@gmail.com');
								}
								$this->email->setSubject(stripslashes($sujetMail));
								$this->email->setHTMLBody(stripslashes($texteMail));
								
								if($this->Config['env'] == 'prod') // nmp
								{
									Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($laCompanie->email_facture),$tabFiler);	
									// Injection du mail NMP dans la queue
									$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
								}
								else // non nmp
								{
									$this->email->addRecipient(trim($laCompanie->email_facture));
									Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
								}
								
								
								// creation pdf facture financement //
								
								// Nom du fichier	
								$vraisNom = 'FACTURE-UNILEND-'. $leProject->slug;
								
								$hashclient = $lemprunteur->hash;
								$id_project = $leProject->id_project;
								
								$url = $this->furl.'/pdf/facture_EF_html/'.$hashclient.'/'.$id_project.'/';
								
								$path = $this->path.'protected/pdf/facture/';
								$footer = $this->furl.'/pdf/footer_facture/';
								
								// fonction pdf
								$this->Web2Pdf->convert($path,$hashclient,$url,'facture_EF',$vraisNom,$id_project,'','',$footer,'nodisplay');
								
								///////////////////////////////
								
								
								
								$settingsControleRemb->value = 1;
								$settingsControleRemb->update();
								mail('unilend@equinoa.fr','[ALERTE] Controle statut remboursement OK','[ALERTE] Controle statut remboursement est bien passe pour le projet : '.$this->projects->id_project.' - '.date('Y-m-d H:i:s').' - '.$this->Config['env']);
							}
							// fin processe changement statut remboursement
						}
						
						////////////////////////////
						
						
						
					}
					
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Sauvegarde du r&eacute;sum&eacute;';
					$_SESSION['freeow']['message'] = 'La sauvegarde du r&eacute;sum&eacute; a bien &eacute;t&eacute; faite !';
					
					header('location:'.$this->lurl.'/dossiers/edit/'.$this->params[0]);
					die;
				}// end if dates_valide
				
			}
			
			
			// Modification de la date de retrait
			if(isset($_POST['send_form_date_retrait'])){
				
				$form_ok = true;
				
				if(!isset($_POST['date_de_retrait'])){
					$form_ok = false;
				}
				if(!isset($_POST['date_retrait_heure'])){
					$form_ok = false;
				}
				elseif($_POST['date_retrait_heure'] < 0){
					$form_ok = false;
				}
				
				if(!isset($_POST['date_retrait_minute'])){
					$form_ok = false;
				}
				elseif($_POST['date_retrait_minute'] < 0){
					$form_ok = false;
				}
				if($this->current_projects_status->status > 50){
					$form_ok = false;
				}
				
				
				if($form_ok == true){
					
					$date = explode('/',$_POST['date_de_retrait']);
					$date = $date[2].'-'.$date[1].'-'.$date[0];
					
					$dateComplete = $date.' '.$_POST['date_retrait_heure'].':'.$_POST['date_retrait_minute'].':00';
					// on check si la date est superieur a la date actuelle
					if(strtotime($dateComplete) > time())
					{
						$this->projects->date_retrait_full = $dateComplete;
						$this->projects->date_retrait = $date;
						$this->projects->update();
					}
					
				}
			}
			
		}
		else
		{
			// Renvoi sur la page de gestion des dossiers
			header('Location:'.$this->lurl.'/dossiers');
			die;
		}
	}
	
	function _changeClient()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// Chargement du data
		$this->clients = $this->loadData('clients');
		
		if(isset($this->params[0]) && $this->params[0] != '')
		{
			$this->lClients = $this->clients->select('nom LIKE "%'.$this->params[0].'%" OR prenom LIKE "%'.$this->params[0].'%"');
		}
	}
	
	function _addMemo()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// Chargement des datas
		$this->projects_comments = $this->loadData('projects_comments');
		
		if(isset($this->params[0]) && isset($this->params[1]) && $this->projects_comments->get($this->params[1],'id_project_comment'))
		{
			$this->type = 'edit';
		}
		else
		{
			$this->type = 'add';	
		}
		
	}
	
	
	function _upload_csv()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->companies_bilans = $this->loadData('companies_bilans');
		$this->companies_actif_passif = $this->loadData('companies_actif_passif');
		
		if(isset($_POST['send_csv']) && isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{

			if(isset($_FILES['csv']) && $_FILES['csv']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'public/default/var/uploads/');
				if($this->upload->doUpload('csv'))
				{
					$this->name_csv = $this->upload->getName();
			
			
					// On recup la companie
					$this->companies->get($this->projects->id_company,'id_company');
					
					// On recup la companie details
					$this->companies_details->get($this->projects->id_company,'id_company');
					
					// liste des bilans de la companies
					$this->lCompanies_bilans = $this->companies_bilans->select('id_company = "'.$this->projects->id_company.'"');
					// liste des actif passif
					$this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "'.$this->projects->id_company.'"');
					
					
					
					// lecture csv
					$row = 0;
					if (($handle = fopen($this->surl."/var/uploads/".$this->name_csv, "r")) !== FALSE) {
						while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
							
							$result[$row] = $data;
							$row++;
							
						}

						fclose($handle);
					}
				
					echo '<pre>';
					print_r($this->lCompanies_bilans);
					echo '</pre>';
					
					// Date du dernier bilan certifié
					$mois = $result[1][1];
					$mois = $result[1][2];
					$annee = $result[1][3];
					
					// Bilan
			
					$bilan[0]['annee'] = $result[3][1];
					$bilan[1]['annee'] = $result[3][2];
					$bilan[2]['annee'] = $result[3][3];
					$bilan[3]['annee'] = $result[3][4];
					$bilan[4]['annee'] = $result[3][5];
					
					$bilan[0]['ca'] = $result[4][1];
					$bilan[1]['ca'] = $result[4][2];
					$bilan[2]['ca'] = $result[4][3];
					$bilan[3]['ca'] = $result[4][4];
					$bilan[4]['ca'] = $result[4][5];
					
					$bilan[0]['rbe'] = $result[5][1];
					$bilan[1]['rbe'] = $result[5][2];
					$bilan[2]['rbe'] = $result[5][3];
					$bilan[3]['rbe'] = $result[5][4];
					$bilan[4]['rbe'] = $result[5][5];
					
					$bilan[0]['re'] = $result[6][1];
					$bilan[1]['re'] = $result[6][2];
					$bilan[2]['re'] = $result[6][3];
					$bilan[3]['re'] = $result[6][4];
					$bilan[4]['re'] = $result[6][5];
					
					$bilan[0]['invest'] = $result[7][1];
					$bilan[1]['invest'] = $result[7][2];
					$bilan[2]['invest'] = $result[7][3];
					$bilan[3]['invest'] = $result[7][4];
					$bilan[4]['invest'] = $result[7][5];
					
					// fin bilan
					
					// Encours actuel de la dette financière
					$encours_actuel = $result[9][1];
					
					// Remboursements à venir cette annee
					$remb_a_venir_cette_annee = $result[10][1];
					
					// Remboursements à venir annee prochaine
					$remb_a_venir_annee_prochaine = $result[11][1];
				
					// Trésorerie disponible actuellement
					$tresorie_dispo = $result[12][1];
					
					// Autres demandes de financements pévues
					$autre_demande_financement = $result[13][1];
					
					// Vous souhaitez apporter des précisions
					$precisions = utf8_encode($result[14][1]);
					
					// actif
					
					// Ordre
					$actif[0]['ordre'] = $result[17][1];
					$actif[1]['ordre'] = $result[17][2];
					$actif[2]['ordre'] = $result[17][3];
					
					// Immobilisations corporelles
					$actif[0]['ic'] = $result[18][1];
					$actif[1]['ic'] = $result[18][2];
					$actif[2]['ic'] = $result[18][3];
					
					// Immobilisations incorporelles
					$actif[0]['ii'] = $result[19][1];
					$actif[1]['ii'] = $result[19][2];
					$actif[2]['ii'] = $result[19][3];
					
					// Immobilisations financières
					$actif[0]['if'] = $result[20][1];
					$actif[1]['if'] = $result[20][2];
					$actif[2]['if'] = $result[20][3];
					
					// Stocks
					$actif[0]['stocks'] = $result[21][1];
					$actif[1]['stocks'] = $result[21][2];
					$actif[2]['stocks'] = $result[21][3];
					
					// Créances clients
					$actif[0]['cc'] = $result[22][1];
					$actif[1]['cc'] = $result[22][2];
					$actif[2]['cc'] = $result[22][3];
					
					// Disponibilités
					$actif[0]['dispo'] = $result[23][1];
					$actif[1]['dispo'] = $result[23][2];
					$actif[2]['dispo'] = $result[23][3];
					
					// Valeurs mobilières de placement
					$actif[0]['vmp'] = $result[24][1];
					$actif[1]['vmp'] = $result[24][2];
					$actif[2]['vmp'] = $result[24][3];
					
					// fin actif
					
					// Passif
					
					// Ordre
					$passif[0]['ordre'] = $result[27][1];
					$passif[1]['ordre'] = $result[27][2];
					$passif[2]['ordre'] = $result[27][3];
					
					// Capitaux propres
					$passif[0]['cp'] = $result[28][1];
					$passif[1]['cp'] = $result[28][2];
					$passif[2]['cp'] = $result[28][3];
					
					// Provisions pour risques & charges
					$passif[0]['pprc'] = $result[29][1];
					$passif[1]['pprc'] = $result[29][2];
					$passif[2]['pprc'] = $result[29][3];
					
					// Armotissements sur immobilisations
					$passif[0]['asi'] = $result[30][1];
					$passif[1]['asi'] = $result[30][2];
					$passif[2]['asi'] = $result[30][3];
					
					// Dettes financières
					$passif[0]['df'] = $result[31][1];
					$passif[1]['df'] = $result[31][2];
					$passif[2]['df'] = $result[31][3];
					
					// Dettes fournisseurs
					$passif[0]['dfo'] = $result[32][1];
					$passif[1]['dfo'] = $result[32][2];
					$passif[2]['dfo'] = $result[32][3];
					
					// Autres dettes
					$passif[0]['ad'] = $result[33][1];
					$passif[1]['ad'] = $result[33][2];
					$passif[2]['ad'] = $result[33][3];
					
					// fin passif
					
					// Découverts bancaires
					$decouverts_bancaires = $result[34][1];
					// Lignes de trésorerie
					$ligens_tresories = $result[35][1];
					// Affacturage
					$affacturage = $result[36][1];
					// Escompte
					$escompte = $result[37][1];
					// Financement Dailly
					$financement_dailly = $result[38][1];
					// Crédit de trésorerie
					$credit_tresorerie = $result[39][1];
					// Crédit bancaire investissements maériels
					$credit_bancaire_i_ma = $result[40][1];
					// Crédit bancaire investissements immaériels
					$credit_bancaire_i_imma = $result[41][1];
					// Rachat d'entreprise ou de titres
					$rachat_entreprise_ou_titres = $result[42][1];
					// Crédit immobilier
					$credit_immobilier = $result[43][1];
					// Crédit bail immobilier
					$credit_bail_immobilier = $result[44][1];
					// Crédit bail
					$credit_bail = $result[45][1];
					// Location avec option d'achat
					$location_avec_option_achat = $result[46][1];
					// Location financi�re
					$location_financiere = $result[47][1];
					// Location longue duree
					$location_longue_duree = $result[48][1];
					// Pret OSEO
					$pret_oseo = $result[49][1];
					// Pret participatif
					$pret_participatif = $result[50][1];
					
					// companies_details
					$this->companies_details->date_dernier_bilan = $annee.'-'.$mois.'-'.$jour;
					
					$this->companies_details->encours_actuel_dette_fianciere = $encours_actuel;
					$this->companies_details->remb_a_venir_cette_annee = $remb_a_venir_cette_annee;
					$this->companies_details->remb_a_venir_annee_prochaine = $remb_a_venir_annee_prochaine;
					$this->companies_details->tresorie_dispo_actuellement = $tresorie_dispo;
					$this->companies_details->autre_demandes_financements_prevues = $autre_demande_financement;
					$this->companies_details->precisions = $precisions;
					$this->companies_details->decouverts_bancaires = $decouverts_bancaires;
					$this->companies_details->lignes_de_tresorerie = $ligens_tresories;
					$this->companies_details->affacturage = $affacturage;
					$this->companies_details->escompte = $escompte;
					$this->companies_details->financement_dailly = $financement_dailly;
					$this->companies_details->credit_de_tresorerie = $credit_tresorerie;
					$this->companies_details->credit_bancaire_investissements_materiels = $credit_bancaire_i_ma;
					$this->companies_details->credit_bancaire_investissements_immateriels = $credit_bancaire_i_imma;
					$this->companies_details->rachat_entreprise_ou_titres = $rachat_entreprise_ou_titres;
					$this->companies_details->credit_immobilier = $credit_immobilier;
					$this->companies_details->credit_bail_immobilier = $credit_bail_immobilier;
					$this->companies_details->credit_bail = $credit_bail;
					$this->companies_details->location_avec_option_achat = $location_avec_option_achat;
					$this->companies_details->location_financiere = $location_financiere;
					$this->companies_details->location_longue_duree = $location_longue_duree;
					$this->companies_details->pret_oseo = $pret_oseo;
					$this->companies_details->pret_participatif = $pret_participatif;
					
					// On met a jour 	
					$this->companies_details->update();
					
					
					// Bilans
					foreach($this->lCompanies_bilans as $k => $cb)
					{
						
						$this->companies_bilans->get($this->projects->id_company,'date = "'.$bilan[$k]['annee'].'" AND id_company');
						$this->companies_bilans->ca = $bilan[$k]['ca'];
						$this->companies_bilans->resultat_brute_exploitation = $bilan[$k]['rbe'];
						$this->companies_bilans->resultat_exploitation = $bilan[$k]['re'];	
						$this->companies_bilans->investissements = $bilan[$k]['invest'];
		
						// on met a jour le bilan
						$this->companies_bilans->update();
					}
					
					// Actif / passif
					foreach($this->lCompanies_actif_passif as $k => $cap)
					{
						
						$this->companies_actif_passif->get($this->projects->id_company,'ordre = "'.$actif[$k]['ordre'].'" AND id_company');
						$this->companies_actif_passif->immobilisations_corporelles = $actif[$k]['ic'];
						$this->companies_actif_passif->immobilisations_incorporelles = $actif[$k]['ii'];
						$this->companies_actif_passif->immobilisations_financieres = $actif[$k]['if'];	
						$this->companies_actif_passif->stocks = $actif[$k]['stocks'];
						$this->companies_actif_passif->creances_clients = $actif[$k]['cc'];
						$this->companies_actif_passif->disponibilites = $actif[$k]['dispo'];
						$this->companies_actif_passif->valeurs_mobilieres_de_placement = $actif[$k]['vmp'];	
						$this->companies_actif_passif->capitaux_propres = $passif[$k]['cp'];
						$this->companies_actif_passif->provisions_pour_risques_et_charges = $passif[$k]['pprc'];
						$this->companies_actif_passif->amortissement_sur_immo = $passif[$k]['asi'];
						$this->companies_actif_passif->dettes_financieres = $passif[$k]['df'];
						$this->companies_actif_passif->dettes_fournisseurs = $passif[$k]['dfo'];	
						$this->companies_actif_passif->autres_dettes = $passif[$k]['ad'];
		
						// on met a jour passif actif 
						$this->companies_actif_passif->update();
						
					}
					
					@unlink($this->path.'public/default/var/uploads/'.$this->name_csv);
					
					$this->result = 'ok';
				}
			}
		}
		else
		{
			$this->result = 'nok';
		}
		
		
	}
	
	function _file()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->companies_details = $this->loadData('companies_details');
		
		// Initialisation
		$this->tablResult['fichier1'] = 'nok';
		$this->tablResult['fichier2'] = 'nok';
		$this->tablResult['fichier3'] = 'nok';
		$this->tablResult['fichier4'] = 'nok';
		$this->tablResult['fichier5'] = 'nok';
		$this->tablResult['fichier6'] = 'nok';
		$this->tablResult['fichier7'] = 'nok';
		$this->tablResult['fichier8'] = 'nok';
		$this->tablResult['fichier9'] = 'nok';
		$this->tablResult['fichier10'] = 'nok';
		$this->tablResult['fichier11'] = 'nok';
		$this->tablResult['fichier12'] = 'nok';
		$this->tablResult['fichier13'] = 'nok';
		//$this->tablResult['fichier14'] = 'nok';
		$this->tablResult['fichier15'] = 'nok';
		$this->tablResult['fichier16'] = 'nok';
		$this->tablResult['fichier17'] = 'nok';
		
		
		if(isset($_POST['send_etape5']) && isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{
			
			// Histo user //
			$serialize = serialize(array('id_project' => $this->params[0],'files' => $_FILES));
			$this->users_history->histo(9,'dossier edit etapes 5',$_SESSION['user']['id_user'],$serialize);
			////////////////
			
			// On recup le detail de l'entreprise
			$this->companies_details->get($this->projects->id_company,'id_company');
			
			// extrait_kbis
			if(isset($_FILES['fichier1']) && $_FILES['fichier1']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/extrait_kbis/');
				if($this->upload->doUpload('fichier1'))
				{
					if($this->companies_details->fichier_extrait_kbis != '')@unlink($this->path.'protected/companies/extrait_kbis/'.$this->companies_details->fichier_extrait_kbis);
					$this->companies_details->fichier_extrait_kbis = $this->upload->getName();
					$this->tablResult['fichier1'] = 'ok';
				}
			}
			// fichier_rib 
			if(isset($_FILES['fichier2']) && $_FILES['fichier2']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/rib/');
				if($this->upload->doUpload('fichier2'))
				{
					if($this->companies_details->fichier_rib != '')@unlink($this->path.'protected/companies/rib/'.$this->companies_details->fichier_rib);
					$this->companies_details->fichier_rib = $this->upload->getName();
					$this->tablResult['fichier2'] = 'ok';
				}
			}
			// fichier_delegation_pouvoir 
			if(isset($_FILES['fichier3']) && $_FILES['fichier3']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/delegation_pouvoir/');
				if($this->upload->doUpload('fichier3'))
				{
					if($this->companies_details->fichier_delegation_pouvoir != '')@unlink($this->path.'protected/companies/delegation_pouvoir/'.$this->companies_details->fichier_delegation_pouvoir);
					$this->companies_details->fichier_delegation_pouvoir = $this->upload->getName();
					$this->tablResult['fichier3'] = 'ok';
				}
			}
			// fichier_logo_societe 
			if(isset($_FILES['fichier4']) && $_FILES['fichier4']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'public/default/var/images/logos_companies/');
				if($this->upload->doUpload('fichier4'))
				{
					if($this->companies_details->fichier_logo_societe != '')@unlink($this->path.'public/default/var/images/logos_companies/'.$this->companies_details->fichier_logo_societe);
					$this->companies_details->fichier_logo_societe = $this->upload->getName();
					$this->tablResult['fichier4'] = 'ok';
				}
			}
			// fichier_photo_dirigeant 
			if(isset($_FILES['fichier5']) && $_FILES['fichier5']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/photo_dirigeant/');
				if($this->upload->doUpload('fichier5'))
				{
					if($this->companies_details->fichier_photo_dirigeant != '')@unlink($this->path.'protected/companies/photo_dirigeant/'.$this->companies_details->fichier_photo_dirigeant);
					$this->companies_details->fichier_photo_dirigeant = $this->upload->getName();
					$this->tablResult['fichier5'] = 'ok';
				}
			}
			
			
			
			// fichier_cni_passeport
			if(isset($_FILES['fichier6']) && $_FILES['fichier6']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/cni_passeport/');
				if($this->upload->doUpload('fichier6'))
				{
					if($this->companies_details->fichier_cni_passeport != '')@unlink($this->path.'protected/companies/cni_passeport/'.$this->companies_details->fichier_cni_passeport);
					$this->companies_details->fichier_cni_passeport = $this->upload->getName();
					$this->tablResult['fichier6'] = 'ok';
				}
			}
			// fichier_derniere_liasse_fiscale
			if(isset($_FILES['fichier7']) && $_FILES['fichier7']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/derniere_liasse_fiscale/');
				if($this->upload->doUpload('fichier7'))
				{
					if($this->companies_details->fichier_derniere_liasse_fiscale != '')@unlink($this->path.'protected/companies/derniere_liasse_fiscale/'.$this->companies_details->fichier_derniere_liasse_fiscale);
					$this->companies_details->fichier_derniere_liasse_fiscale = $this->upload->getName();
					$this->tablResult['fichier7'] = 'ok';
				}
			}
			// fichier_derniers_comptes_approuves
			if(isset($_FILES['fichier8']) && $_FILES['fichier8']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/derniers_comptes_approuves/');
				if($this->upload->doUpload('fichier8'))
				{
					if($this->companies_details->fichier_derniers_comptes_approuves != '')@unlink($this->path.'protected/companies/derniers_comptes_approuves/'.$this->companies_details->fichier_derniers_comptes_approuves);
					$this->companies_details->fichier_derniers_comptes_approuves = $this->upload->getName();
					$this->tablResult['fichier8'] = 'ok';
				}
			}
			// fichier_derniers_comptes_consolides_groupe 
			if(isset($_FILES['fichier9']) && $_FILES['fichier9']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/derniers_comptes_consolides_groupe/');
				if($this->upload->doUpload('fichier9'))
				{
					if($this->companies_details->fichier_derniers_comptes_consolides_groupe != '')@unlink($this->path.'protected/companies/derniers_comptes_consolides_groupe/'.$this->companies_details->fichier_derniers_comptes_consolides_groupe);
					$this->companies_details->fichier_derniers_comptes_consolides_groupe = $this->upload->getName();
					$this->tablResult['fichier9'] = 'ok';
				}
			}
			// fichier_annexes_rapport_special_commissaire_compte 
			if(isset($_FILES['fichier10']) && $_FILES['fichier10']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/annexes_rapport_special_commissaire_compte/');
				if($this->upload->doUpload('fichier10'))
				{
					if($this->companies_details->fichier_annexes_rapport_special_commissaire_compte != '')@unlink($this->path.'protected/companies/annexes_rapport_special_commissaire_compte/'.$this->companies_details->fichier_annexes_rapport_special_commissaire_compte);
					$this->companies_details->fichier_annexes_rapport_special_commissaire_compte = $this->upload->getName();
					$this->tablResult['fichier10'] = 'ok';
				}
			}
			// fichier_arret_comptable_recent 
			if(isset($_FILES['fichier11']) && $_FILES['fichier11']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/arret_comptable_recent/');
				if($this->upload->doUpload('fichier11'))
				{
					if($this->companies_details->fichier_arret_comptable_recent != '')@unlink($this->path.'protected/companies/arret_comptable_recent/'.$this->companies_details->fichier_arret_comptable_recent);
					$this->companies_details->fichier_arret_comptable_recent = $this->upload->getName();
					$this->tablResult['fichier11'] = 'ok';
				}
			}
			// fichier_budget_exercice_en_cours_a_venir 
			if(isset($_FILES['fichier12']) && $_FILES['fichier12']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/budget_exercice_en_cours_a_venir/');
				if($this->upload->doUpload('fichier12'))
				{
					if($this->companies_details->fichier_budget_exercice_en_cours_a_venir != '')@unlink($this->path.'protected/companies/budget_exercice_en_cours_a_venir/'.$this->companies_details->fichier_budget_exercice_en_cours_a_venir);
					$this->companies_details->fichier_budget_exercice_en_cours_a_venir = $this->upload->getName();
					$this->tablResult['fichier12'] = 'ok';
				}
			}
			// fichier_notation_banque_france 
			if(isset($_FILES['fichier13']) && $_FILES['fichier13']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/notation_banque_france/');
				if($this->upload->doUpload('fichier13'))
				{
					if($this->companies_details->fichier_notation_banque_france != '')@unlink($this->path.'protected/companies/notation_banque_france/'.$this->companies_details->fichier_notation_banque_france);
					$this->companies_details->fichier_notation_banque_france = $this->upload->getName();
					$this->tablResult['fichier13'] = 'ok';
				}
			}
		
			
			
			
			
			
			// fichier_dernier_bilan_certifie
			/*if(isset($_FILES['fichier14']) && $_FILES['fichier14']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/dernier_bilan_certifie/');
				if($this->upload->doUpload('fichier14'))
				{
					if($this->companies_details->fichier_dernier_bilan_certifie != '')@unlink($this->path.'protected/companies/dernier_bilan_certifie/'.$this->companies_details->fichier_dernier_bilan_certifie);
					$this->companies_details->fichier_dernier_bilan_certifie = $this->upload->getName();
				}
			}*/
			
			// fichier_autre_1
			if(isset($_FILES['fichier15']) && $_FILES['fichier15']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/autres/');
				if($this->upload->doUpload('fichier15'))
				{
					if($this->companies_details->fichier_autre_1 != '')@unlink($this->path.'protected/companies/autres/'.$this->companies_details->fichier_autre_1);
					$this->companies_details->fichier_autre_1 = $this->upload->getName();
					$this->tablResult['fichier15'] = 'ok';
				}
			}
			
			// fichier_autre_2
			if(isset($_FILES['fichier16']) && $_FILES['fichier16']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/autres/');
				if($this->upload->doUpload('fichier16'))
				{
					if($this->companies_details->fichier_autre_2 != '')@unlink($this->path.'protected/companies/autres/'.$this->companies_details->fichier_autre_2);
					$this->companies_details->fichier_autre_2 = $this->upload->getName();
					$this->tablResult['fichier16'] = 'ok';
				}
			}
			
			// fichier_autre_3 
			if(isset($_FILES['fichier17']) && $_FILES['fichier17']['name'] != '')
			{
				$this->upload->setUploadDir($this->path,'protected/companies/autres/');
				if($this->upload->doUpload('fichier17'))
				{
					if($this->companies_details->fichier_autre_3 != '')@unlink($this->path.'protected/companies/autres/'.$this->companies_details->fichier_autre_3);
					$this->companies_details->fichier_autre_3 = $this->upload->getName();
					$this->tablResult['fichier17'] = 'ok';
				}
			}
			
			// Enregistrement des images
			$this->companies_details->update();
			
			$this->result = json_encode($this->tablResult);
			
			//$this->result = 'testtttt';
		}
			
	}
	
	function _add()
	{
		// Chargement du data
		$this->projects_status = $this->loadData('projects_status');
		$this->projects = $this->loadData('projects');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->companies_bilans = $this->loadData('companies_bilans');
		$this->companies_actif_passif = $this->loadData('companies_actif_passif');
		
		if(isset($_POST['send_create_etape1']))
		{
			// Si le client existe
			if($_POST['leclient'] == 1 && $this->clients->get($_POST['id_client'],'id_client'))
			{
				// On met a jour 
				//$this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
				//$this->clients->nom = $this->ficelle->majNom($_POST['nom']);
				//$this->clients->update();
				
				header('location:'.$this->lurl.'/dossiers/add/create_etape2/'.$_POST['id_client']);
        		die;
			}
			// Si le client n'existe pas
			elseif($_POST['leclient'] == 2)
			{
				//$_POST['newPrenom']
				//$_POST['newNom']
				
				header('location:'.$this->lurl.'/dossiers/add/create_etape2');
        		die;
			}
			else
			{
				header('location:'.$this->lurl.'/dossiers/add/create');
        		die;	
			}
			
		}
		
		
		if(isset($this->params[0]) && $this->params[0] == 'create_etape2')
		{
			// Si on a deja un client
			if(isset($this->params[1]) && $this->clients->get($this->params[1],'id_client'))
			{
				// Si l'entreprise existe on a pas besoin de la creer
				if($this->companies->get($this->clients->id_client,'id_client_owner'))
				{
					
				}
				// Sinon on la creer
				else
				{
					// Creation companie
					$this->companies->id_company = $this->companies->create();
					
					// Creation companie detail
					$this->companies_details->id_company = $this->companies->id_company;
					$this->companies_details->create();
					
					// Creation companie bilans
					$tablAnneesBilans = array(date('Y')-3,date('Y')-2,date('Y')-1,date('Y'),date('Y')+1);
					foreach($tablAnneesBilans as $a)
					{
						$this->companies_bilans->id_company = $this->companies->id_company;
						$this->companies_bilans->date = $a;
						$this->companies_bilans->create();
					}
				}
			}
			// Si on a pas encore de client on créer l'entreprise
			else
			{
				// Creation companie
				$this->companies->id_company = $this->companies->create();
				
				// Creation companie detail
				$this->companies_details->id_company = $this->companies->id_company;
				
				$this->companies_details->date_dernier_bilan 		= (date('Y')-1).'-12-31';
				$this->companies_details->date_dernier_bilan_mois 	= '12';
				$this->companies_details->date_dernier_bilan_annee 	= (date('Y')-1);
				
				$this->companies_details->create();
				
				// Creation companie bilans
				$tablAnneesBilans = array(date('Y')-3,date('Y')-2,date('Y')-1,date('Y'),date('Y')+1);
				foreach($tablAnneesBilans as $a)
				{
					$this->companies_bilans->id_company = $this->companies->id_company;
					$this->companies_bilans->date = $a;
					$this->companies_bilans->create();
				}
			
			}
			
			// Creation du projet
			$this->projects->id_company = $this->companies->id_company;
			$this->projects->create_bo = 1; // on signale que le projet a été créé en Bo
			$this->projects->id_project = $this->projects->create();
			
			
			// Histo user //
			$serialize = serialize(array('id_project' => $this->projects->id_project));
			$this->users_history->histo(7,'dossier create',$_SESSION['user']['id_user'],$serialize);
			////////////////
			
			// Liste des actif passif
			$this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'"','annee DESC');
			
			// Si existe pas on créer les champs
			if($this->lCompanies_actif_passif == false)
			{
				
				// les 3 dernieres vrais années
				$date[1] = (date('Y')-1);
				$date[2] = (date('Y')-2);
				$date[3] = (date('Y')-3);
				
				for($i=1;$i<=3;$i++)
				{
					$this->companies_actif_passif->annee = $date[$i];
					$this->companies_actif_passif->ordre = $i;
					$this->companies_actif_passif->id_company = $this->companies->id_company;
					$this->companies_actif_passif->create();
				}
				
				
			}
			
			
			
			
			header('location:'.$this->lurl.'/dossiers/add/'.$this->projects->id_project);
        	die;
			
		}
		elseif(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{
			
			$this->create_etape_ok = true;
			
			// On recup l'entreprise
			$this->companies->get($this->projects->id_company,'id_company');
			
			// On recup le detail de l'entreprise
			$this->companies_details->get($this->projects->id_company,'id_company');
			
			// On recup le client
			$this->clients->get($this->companies->id_client_owner,'id_client');
			
			// On recup le adresse client
			$this->clients_adresses->get($this->companies->id_client_owner,'id_client');
			
			// liste des users bo
			$this->lUsers = $this->users->select('status = 1 AND id_user_type = 2');
			
			// meme adresse que le siege
			if($this->companies->status_adresse_correspondance == 1)
			{
				$this->adresse = $this->companies->adresse1;
				$this->city = $this->companies->city;
				$this->zip = $this->companies->zip;
				$this->phone = $this->companies->phone;
			}
			else
			{
				$this->adresse = $this->clients_adresses->adresse1;
				$this->city = $this->clients_adresses->ville;
				$this->zip = $this->clients_adresses->cp;
				$this->phone = $this->clients_adresses->telephone;
			}
			
			// on recup l'année du projet
			//$anneeProjet = explode('-',$this->projects->added);
			//$anneeProjet = $anneeProjet[0];
			
			
			/// date dernier bilan ///
			if($this->companies_details->date_dernier_bilan == '0000-00-00'){
				
				$this->date_dernier_bilan_jour = '31';
				$this->date_dernier_bilan_mois = '12';
				$this->date_dernier_bilan_annee = (date('Y')-1);
				
				$this->companies_details->date_dernier_bilan 		= (date('Y')-1).'-12-31';
				$this->companies_details->date_dernier_bilan_mois 	= '12';
				$this->companies_details->date_dernier_bilan_annee  = (date('Y')-1);
				
				$anneeProjet = (date('Y')-1);
				
			}
			else{
				$dateDernierBilan = explode('-',$this->companies_details->date_dernier_bilan);
				$this->date_dernier_bilan_jour = $dateDernierBilan[2];
				$this->date_dernier_bilan_mois = $dateDernierBilan[1];
				$this->date_dernier_bilan_annee = $dateDernierBilan[0];
				
				$anneeProjet = $dateDernierBilan[0];
			}
			
			/////////////////////////////
			
			$ldateBilan[4] = $anneeProjet+2;
			$ldateBilan[3] = $anneeProjet+1;
			$ldateBilan[2] = $anneeProjet;
			$ldateBilan[1] = $anneeProjet-1; 
			$ldateBilan[0] = $anneeProjet-2;
			//$ldateBilan[0] = $anneeProjet-3; 
			
			$ldateBilantrueYear[4] = $anneeProjet+2;
			$ldateBilantrueYear[3] = $anneeProjet+1;
			$ldateBilantrueYear[2] = $anneeProjet;
			$ldateBilantrueYear[1] = $anneeProjet-1; 
			$ldateBilantrueYear[0] = $anneeProjet-2;
			//$ldateBilantrueYear[0] = $anneeProjet-3; 
			
			

			// on recup les années bilans en se basant sur la date de creation du projet
			/*$ldateBilan[4] = $anneeProjet+1;
			$ldateBilan[3] = $anneeProjet;
			$ldateBilan[2] = $anneeProjet-1; 
			$ldateBilan[1] = $anneeProjet-2;
			$ldateBilan[0] = $anneeProjet-3; 
			
			$ldateBilantrueYear[4] = date('Y')+1;
			$ldateBilantrueYear[3] = date('Y');
			$ldateBilantrueYear[2] = date('Y')-1; 
			$ldateBilantrueYear[1] = date('Y')-2;
			$ldateBilantrueYear[0] = date('Y')-3; */
			
			// liste des bilans
			$this->lbilans = $this->companies_bilans->select('date BETWEEN "'.$ldateBilan[0].'" AND "'.$ldateBilan[4].'" AND id_company = '.$this->companies->id_company,'date ASC');
			
			// Liste des actif passif
			$this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'"','annee DESC');
			
			$dateDernierBilan = explode('-',$this->companies_details->date_dernier_bilan);
			
			$this->date_dernier_bilan_jour = $dateDernierBilan[2];
			$this->date_dernier_bilan_mois = $dateDernierBilan[1];
			$this->date_dernier_bilan_annee = $dateDernierBilan[0];
			
			
			//******************//
			// On lance Altares //
			//******************//
			if(isset($this->params[1]) && $this->params[1] == 'altares')
			{
				
				// SIREN
				$this->siren = $this->companies->siren;
				// Web Service Altares
				$result = $this->ficelle->ws($this->wsdl,$this->identification,$this->siren);
				
				$this->altares_ok = false;
				
				// Si pas d'erreur
				if($result->exception == '')
				{
					
					// verif reponse
					$eligibility = $result->myInfo->eligibility;
					$score = $result->myInfo->score;
					$identite = $result->myInfo->identite;
					
					// statut
					$this->tablStatus = array('Oui','Pas de bilan');
					
					// date -3 ans
					$todayMoins3 = date('Y')-3;
					
					// On enregistre
					$this->companies->altares_eligibility = $eligibility;
					
					$dateValeur = substr($score->dateValeur,0,10);
					$this->companies->altares_dateValeur = $dateValeur;
					$this->companies->altares_niveauRisque = $score->niveauRisque;
					$this->companies->altares_scoreVingt = $score->scoreVingt;
					
					// si pas ok
					if($eligibility == 'Société radiée' || $eligibility == 'Non' || $eligibility == 'SIREN inconnu')
					{
						// Mise en session du message
						$_SESSION['freeow']['title'] = 'Donn&eacute;es Altares';
						$_SESSION['freeow']['message'] = 'soci&eacute;t&eacute; non &eacute;ligible';
						
						header('location:'.$this->lurl.'/dossiers/add/'.$this->projects->id_project);
        				die;
					}
					// si pas ok 2
					//elseif(in_array($eligibility,$this->tablStatus) && $score->scoreVingt < 12 || in_array($eligibility,$this->tablStatus) && substr($identite->dateCreation,0,4) > $todayMoins3 )
					elseif(in_array($eligibility,$this->tablStatus) && substr($identite->dateCreation,0,4) > $todayMoins3)
					{
						// Mise en session du message
						$_SESSION['freeow']['title'] = 'Donn&eacute;es Altares';
						$_SESSION['freeow']['message'] = 'soci&eacute;t&eacute; non &eacute;ligible';
						
						header('location:'.$this->lurl.'/dossiers/add/'.$this->projects->id_project);
        				die;
					}
					// si ok
					else
					{
						$this->altares_ok = true;
						
						$identite = $result->myInfo->identite;
						$syntheseFinanciereInfo = $result->myInfo->syntheseFinanciereInfo;
						$syntheseFinanciereList = $result->myInfo->syntheseFinanciereInfo->syntheseFinanciereList;
						
						$posteActifList = array();
						$postePassifList = array();
						$syntheseFinanciereInfo = array();
						$syntheseFinanciereList = array();
						$derniersBilans = array();
						$i=0;
						foreach($result->myInfo->bilans as $b)
						{
	
							$annee = substr($b->bilan->dateClotureN,0,4);
							$posteActifList[$annee] = $b->bilanRetraiteInfo->posteActifList;
							$postePassifList[$annee] = $b->bilanRetraiteInfo->postePassifList;
							$syntheseFinanciereInfo[$annee] = $b->syntheseFinanciereInfo;
							$syntheseFinanciereList[$annee] = $b->syntheseFinanciereInfo->syntheseFinanciereList;
							
							$soldeIntermediaireGestionInfo[$annee] = $b->soldeIntermediaireGestionInfo->SIGList;
							
							$investissement[$annee] = $b->bilan->posteList[0]->valeur;
							
							// date des derniers bilans
							$derniersBilans[$i] = $annee;
							
							$i++;
						}
						
						$this->companies->name = $identite->raisonSociale;
						$this->companies->forme = $identite->formeJuridique;
						$this->companies->capital = $identite->capital;
						
						$this->companies->adresse1 = $identite->rue;
						$this->companies->city = $identite->ville;
						$this->companies->zip = $identite->codePostal;
						
						
						// on decoupe
						$dateCreation = substr($identite->dateCreation,0,10);
						// on enregistre
						$this->companies->date_creation = $dateCreation;
						// on fait une version fr
						$dateCreation = explode('-',$dateCreation);
						$this->date_creation = $dateCreation[2].'/'.$dateCreation[1].'/'.$dateCreation[0];
						
						// dernier bilan 
						$dateDernierBilanString = substr($identite->dateDernierBilan,0,10);
						$dateDernierBilan = explode('-',$dateDernierBilan);

						$this->companies_details->date_dernier_bilan = $dateDernierBilanString;
						
						$this->date_dernier_bilan_jour = $dateDernierBilan[2];
						$this->date_dernier_bilan_mois = $dateDernierBilan[1];
						$this->date_dernier_bilan_annee = $dateDernierBilan[0];
						
						$this->companies->update();
						$this->companies_details->update();
						
						// date courrante
						$ldate[4] = date('Y')+1;

						$ldate[3] = date('Y');
						$ldate[2] = date('Y')-1; 
						$ldate[1] = date('Y')-2;
						$ldate[0] = date('Y')-3; 
						
						// on génère un tableau avec les données
						for($i=0;$i<5;$i++) // on parcourt les 5 années
						{
							for($a=0;$a<3;$a++)// on parcourt les 3 dernieres années
							{
								// si y a une année du bilan qui correxpond a une année du tableau
								if($derniersBilans[$a] == $ldate[$i])
								{
									// On recup les données de cette année	
									$montant1 = $posteActifList[$ldate[$i]][1]->montant;
									$montant2 = $posteActifList[$ldate[$i]][2]->montant;
									$montant3 = $posteActifList[$ldate[$i]][3]->montant;
									$montant = $montant1+$montant2+$montant3;
									
									$this->companies_bilans->get($this->companies->id_company,'date = '.$ldate[$i].' AND id_company');
									$this->companies_bilans->ca = $syntheseFinanciereList[$ldate[$i]][0]->montantN;
									$this->companies_bilans->resultat_exploitation = $syntheseFinanciereList[$ldate[$i]][1]->montantN;
									$this->companies_bilans->resultat_brute_exploitation = $soldeIntermediaireGestionInfo[$ldate[$i]][9]->montantN;
									$this->companies_bilans->investissements = $investissement[$ldate[$i]];
									$this->companies_bilans->update();
									
								}
							}
	
						}
						
						// Debut actif/passif

						foreach($derniersBilans as $annees)
						{
							foreach($posteActifList[$annees] as $a)
							{
								$ActifPassif[$annees][$a->posteCle] = $a->montant;
							}
							foreach($postePassifList[$annees] as $p)
							{
								$ActifPassif[$annees][$p->posteCle] = $p->montant;
							}
						}
						
						
						
						$i=0;
						foreach($this->lCompanies_actif_passif as $k => $ap)
						{
							// que la derniere année
							if($this->companies_actif_passif->get($ap['annee'],'id_company = '.$ap['id_company'].' AND annee'))
							{
								
								//$this->companies_actif_passif->annee = $derniersBilans[$i];
								//$this->companies_actif_passif->ordre = $i+1;
								
								// Actif
								$this->companies_actif_passif->immobilisations_corporelles = $ActifPassif[$ap['annee']]['posteBR_IMCOR'];
								$this->companies_actif_passif->immobilisations_incorporelles = $ActifPassif[$ap['annee']]['posteBR_IMMINC'];
								$this->companies_actif_passif->immobilisations_financieres = $ActifPassif[$ap['annee']]['posteBR_IMFI'];
								$this->companies_actif_passif->stocks = $ActifPassif[$ap['annee']]['posteBR_STO'];
								//creances_clients = Avances et acomptes + creances clients + autre creances et cca + autre creances hors exploitation
								$this->companies_actif_passif->creances_clients = $ActifPassif[$ap['annee']]['posteBR_BV']+$ActifPassif[$ap['annee']]['posteBR_BX']+$ActifPassif[$ap['annee']]['posteBR_ACCCA']+$ActifPassif[$ap['annee']]['posteBR_ACHE_'];
								$this->companies_actif_passif->disponibilites = $ActifPassif[$ap['annee']]['posteBR_CF'];
								$this->companies_actif_passif->valeurs_mobilieres_de_placement = $ActifPassif[$ap['annee']]['posteBR_CD'];
								
								// passif
								
								// capitaux_propres = capitaux propres + non valeurs
								$this->companies_actif_passif->capitaux_propres = $ActifPassif[$ap['annee']]['posteBR_CPRO']+$ActifPassif[$ap['annee']]['posteBR_NONVAL'];
								// provisions_pour_risques_et_charges = Provisions pour risques et charges + Provisions actif circulant
								$this->companies_actif_passif->provisions_pour_risques_et_charges = $ActifPassif[$ap['annee']]['posteBR_PROVRC']+$ActifPassif[$ap['annee']]['posteBR_PROAC'];
								
								$this->companies_actif_passif->amortissement_sur_immo = $ActifPassif[$ap['annee']]['posteBR_AMPROVIMMO'];
								// dettes_financieres = Emprunts + Dettes groupe et associés + Concours bancaires courants
								$this->companies_actif_passif->dettes_financieres = $ActifPassif[$ap['annee']]['posteBR_EMP']+$ActifPassif[$ap['annee']]['posteBR_VI']+$ActifPassif[$ap['annee']]['posteBR_EH'];
								
								// dettes_fournisseurs = Avances et Acomptes clients + Dettes fournisseurs
								$this->companies_actif_passif->dettes_fournisseurs = $ActifPassif[$ap['annee']]['posteBR_DW']+$ActifPassif[$ap['annee']]['posteBR_DX'];
								
								// autres_dettes = autres dettes exploi + Dettes sur immos et comptes rattachés + autres dettes hors exploi
								$this->companies_actif_passif->autres_dettes = $ActifPassif[$ap['annee']]['posteBR_AUTDETTEXPL']+$ActifPassif[$ap['annee']]['posteBR_DZ']+$ActifPassif[$ap['annee']]['posteBR_AUTDETTHEXPL'];
								$this->companies_actif_passif->update();
							}
							$i++;
						}
						// Fin actif/passif
						
						// Mise en session du message
						$_SESSION['freeow']['title'] = 'Donn&eacute;es Altares';
						$_SESSION['freeow']['message'] = 'Donn&eacute;es Altares r&eacute;cup&eacute;r&eacute; !';
					}
				}
				else
				{
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Donn&eacute;es Altares';
					$_SESSION['freeow']['message'] = 'Donn&eacute;es Altares &eacute;rreur !';
				}
				
				header('location:'.$this->lurl.'/dossiers/add/'.$this->projects->id_project);
        		die;
			}
			
		}
		
	}
	
	function _funding()
	{
		// Chargement du data
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->bids = $this->loadData('bids');
		
		// Liste des projets en funding
		$this->lProjects = $this->projects->selectProjectsByStatus(50);
	}
	
	function _remboursements()
	{
		// Chargement du data
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->clients = $this->loadData('clients');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		
		// TVA
		$this->settings->get('TVA','type');
		$this->tva = $this->settings->value;
		
		if(isset($_POST['form_search_remb']))
		{
			// Liste des projets en remb
			$this->lProjects = $this->projects->searchDossiersRemb($_POST['siren'],$_POST['societe'],$_POST['nom'],$_POST['prenom'],$_POST['projet'],$_POST['email']);
		}
		else
		{
			// Liste des projets en remb
			$this->lProjects = $this->projects->searchDossiersRemb();
		}
	}
	
	function _no_remb()
	{
		// Chargement du data
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->clients = $this->loadData('clients');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		
		// TVA
		$this->settings->get('TVA','type');
		$this->tva = $this->settings->value;
		
		if(isset($_POST['form_search_remb']))
		{
			// Liste des projets en remb
			$this->lProjects = $this->projects->searchDossiersNoRemb($_POST['siren'],$_POST['societe'],$_POST['nom'],$_POST['prenom'],$_POST['projet'],$_POST['email']);
		}
		else
		{
			// Liste des projets en remb
			$this->lProjects = $this->projects->searchDossiersNoRemb();
		}
	}
	
	function _detail_remb()
	{
		ini_set('max_execution_time', 300); //300 seconds = 5 minutes
		// Chargement du data
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->projects_status_history = $this->loadData('projects_status_history');
		$this->companies = $this->loadData('companies');
		$this->clients = $this->loadData('clients');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$this->transactions = $this->loadData('transactions');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->notifications = $this->loadData('notifications');
		$this->bank_unilend = $this->loadData('bank_unilend');

		// Cabinet de recouvrement
		$this->settings->get('Cabinet de recouvrement','type');
		$this->cab = $this->settings->value;
		
		// TVA
		$this->settings->get('TVA','type');
		$this->tva = $this->settings->value;
		
		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{
			$this->companies->get($this->projects->id_company,'id_company');
			
			$this->clients->get($this->companies->id_client_owner,'id_client');
			
			$this->users->get($this->projects->id_analyste,'id_user');
			
			$this->projects_status->getLastStatut($this->projects->id_project);
			
			$this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);
			
			$today = date('Y-m-d H:i');
			
			// liste des echeances emprunteur par mois
			$lRembs  = $this->echeanciers_emprunteur->select('id_project = '.$this->projects->id_project);
			// ON recup la date de statut remb
			$dernierStatut = $this->projects_status_history->select('id_project = '.$this->projects->id_project,'added DESC',0,1);
			$dateDernierStatut = $dernierStatut[0]['added'];
			
			
			$this->nbRembEffet = 0;
			$this->nbRembaVenir = 0;
			
			$this->totalEffet = 0;
			$this->totalaVenir = 0;
			
			$this->interetEffet = 0;
			$this->interetaVenir = 0;
			
			$this->capitalEffet = 0;
			$this->capitalaVenir = 0;
			
			$this->commissionEffet = 0;
			$this->commissionaVenir = 0;
			
			$this->tvaEffet = 0;
			$this->tvaaVenir = 0;
			
			$this->nextRemb = '';
			
			
		
			foreach($lRembs as $k => $r)
			{				
				// remboursement effectué
				if($r['status_emprunteur'] == 1)
				{
					$this->nbRembEffet += 1;
					$MontantRemb = $this->echeanciers->getMontantRembEmprunteur($r['montant'],$r['commission'],$r['tva']);
					$this->totalEffet += $MontantRemb;
					$this->interetEffet += $r['interets'];
					$this->capitalEffet += $r['capital'];
					$this->commissionEffet += $r['commission'];
					$this->tvaEffet += $r['tva'];
				}
				// remb a venir
				else
				{
					if($this->nextRemb == '') $this->nextRemb = $r['date_echeance_emprunteur'];
					
					$this->nbRembaVenir += 1;
					$MontantRemb = $this->echeanciers->getMontantRembEmprunteur($r['montant'],$r['commission'],$r['tva']);
					$this->totalaVenir += $MontantRemb;
					$this->interetaVenir += $r['interets'];
					$this->capitalaVenir += $r['capital'];
					$this->commissionaVenir += $r['commission'];
					$this->tvaaVenir += $r['tva'];
				}
			}
			
			// com unilend
			$this->commissionUnilend = ($this->commissionEffet + $this->commissionaVenir);
			
			// CTA On rembourse les preteurs pour le mois en cours
			if(isset($this->params[1]) && $this->params[1] == 'remb')
			{
				// On recup le param
				$settingsControleRemb = $this->loadData('settings');
				$settingsControleRemb->get('Controle remboursements','type');
				
				// on rentre dans le cron si statut égale 1 
				if($settingsControleRemb->value == 1)
				{
					// On passe le statut a zero pour signaler qu'on est en cours de traitement
					$settingsControleRemb->value = 0;
					$settingsControleRemb->update();
					
					//mail('d.courtier@equinoa.com','alerte demande remb BO','un remb a ete demande sur le projet '.$this->params[0].' par '.$_SESSION['user']['id_user']);
					/////////////////////
					// Remb emprunteur //
					/////////////////////
					
					// FB
					$this->settings->get('Facebook','type');
					$lien_fb = $this->settings->value;
					
					// Twitter
					$this->settings->get('Twitter','type');
					$lien_tw = $this->settings->value;
					
					
					// On parcourt les remb emprunteurs
					$lEcheancesRembEmprunteur = $this->echeanciers_emprunteur->select('id_project = '.$this->projects->id_project.' AND status_emprunteur = 1');
					
					if($lEcheancesRembEmprunteur != false)
					{
						foreach($lEcheancesRembEmprunteur as $RembEmpr)
						{
							
							// On déclare les variables
							$montant = 0;
							$capital = 0;
							$interets = 0;
							$commission = 0;
							$tva = 0;
							$prelevements_obligatoires = 0;
							$retenues_source = 0;
							$csg = 0;
							$prelevements_sociaux = 0;
							$contributions_additionnelles = 0;
							$prelevements_solidarite = 0;
							$crds = 0;
							
							$rembNet = 0;
							$etat = 0;
							
							$rembNetTotal = 0;
							$TotalEtat = 0;
							
							// On recup les echeanches non remboursé aux preteurs mais remb par l'emprunteur
							$lEcheances = $this->echeanciers->select('id_project = '.$RembEmpr['id_project'].' AND status_emprunteur = 1 AND ordre = '.$RembEmpr['ordre'].' AND status = 0');
							
				
							
							foreach($lEcheances as $e)
							{
								// on fait la somme de tout
								$montant += ($e['montant']/100);
								$capital += ($e['capital']/100);
								$interets += ($e['interets']/100);
								$commission += ($e['commission']/100);
								$tva += ($e['tva']/100);
								$prelevements_obligatoires += $e['prelevements_obligatoires'];
								$retenues_source += $e['retenues_source'];
								$csg += $e['csg'];
								$prelevements_sociaux += $e['prelevements_sociaux'];
								$contributions_additionnelles += $e['contributions_additionnelles'];
								$prelevements_solidarite += $e['prelevements_solidarite'];
								$crds += $e['crds'];
								
								// Remb net preteur
								$rembNet = ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
								
								// Partie pour l'etat sur un remb preteur
								$etat = $e['prelevements_obligatoires'] + $e['retenues_source'] + $e['csg'] + $e['prelevements_sociaux'] + $e['contributions_additionnelles'] + $e['prelevements_solidarite'] + $e['crds'];
								
								//echo 'Preteur '.$e['id_lender'].' remb net : '.$rembNet.' €<br>';
								
								// Partie on enregistre les mouvements
								
								// On regarde si on a pas deja
								if($this->transactions->get($e['id_echeancier'],'id_echeancier')==false)
								{
								
									// On recup lenders_accounts
									$this->lenders_accounts->get($e['id_lender'],'id_lender_account');
									// On recup le client
									$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
									
									// echeance preteur
									$this->echeanciers->get($e['id_echeancier'],'id_echeancier');
									$this->echeanciers->status = 1; // remboursé
									$this->echeanciers->date_echeance_reel = date('Y-m-d H:i:s');
									$this->echeanciers->update();
														
									// On enregistre la transaction
									$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
									$this->transactions->montant = ($rembNet*100);
									$this->transactions->id_echeancier = $e['id_echeancier']; // id de l'echeance remb
									$this->transactions->id_langue = 'fr';
									$this->transactions->date_transaction = date('Y-m-d H:i:s');
									$this->transactions->status = '1';
									$this->transactions->etat = '1';
									$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
									$this->transactions->type_transaction = 5; // remb enchere
									$this->transactions->transaction = 2; // transaction virtuelle
									$this->transactions->id_transaction = $this->transactions->create();
									
									// on enregistre la transaction dans son wallet
									$this->wallets_lines->id_lender = $e['id_lender'];
									$this->wallets_lines->type_financial_operation = 40;
									$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
									$this->wallets_lines->status = 1; // non utilisé
									$this->wallets_lines->type = 2; // transaction virtuelle
									$this->wallets_lines->amount = ($rembNet*100);
									$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
									
									// On enregistre la notification pour le preteur
									$this->notifications->type = 2; // remb
									$this->notifications->id_lender = $this->lenders_accounts->id_lender_account;
									$this->notifications->id_project = $this->projects->id_project;
									$this->notifications->amount = ($rembNet*100);
									$this->notifications->create();
									
									// Motif virement
									$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
									$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
									$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
									$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
									
									///////////////////////////
									// rajouter verif statut projet
									if($this->projects_status->status == 110)
									{
										// mail recouvré
										// on envoie un mail recouvré au lieu du mail remboursement
										
										//*******************************************//
										//*** ENVOI DU MAIL RECOUVRE PRETEUR ***//
										//*******************************************//
							
										// Recuperation du modele de mail
										$this->mails_text->get('preteur-dossier-recouvre','lang = "'.$this->language.'" AND type');
										$this->companies->get($this->projects->id_company,'id_company');
													
										// Variables du mailing
										$varMail = array(
										'surl' => $this->surl,
										'url' => $this->furl,
										'prenom_p' => $this->clients->prenom,
										'cab_recouvrement' => $this->cab,
										'mensualite_p' => number_format($rembNet, 2, ',', ' '),
										'nom_entreprise' => $this->companies->name,
										'solde_p' =>$this->transactions->getSolde($this->clients->id_client),
										'link_echeancier' => $this->furl,
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
										
										// et on fait passer le satut recouvrement en remboursement
										
										////////////////////////////
									}
									// mail remboursement
									else
									{
										// mail pour les preteurs //
				
										//*******************************************//
										//*** ENVOI DU MAIL REMBOURSEMENT PRETEUR ***//
										//*******************************************//
							
										// Recuperation du modele de mail
										$this->mails_text->get('preteur-remboursement','lang = "'.$this->language.'" AND type');
										$this->companies->get($this->projects->id_company,'id_company');
										
										$nbpret = $this->loans->counter('id_lender = '.$e['id_lender'].' AND id_project = '.$e['id_project']);
										
										// Variables du mailing
										$surl = $this->surl;
										$url = $this->furl;
										
										// euro avec ou sans "s"
										if($rembNet >= 2)$euros = ' euros';
										else $euros = ' euro';
										$rembNetEmail = number_format($rembNet, 2, ',', ' ').$euros;
										
										if($this->transactions->getSolde($this->clients->id_client)>=2)$euros = ' euros';
										else $euros = ' euro';
										$solde = number_format($this->transactions->getSolde($this->clients->id_client), 2, ',', ' ').$euros;
							
										$timeAdd = strtotime($dateDernierStatut);
										$month = $this->dates->tableauMois['fr'][date('n',$timeAdd)];
										
										// Variables du mailing
										$varMail = array(
										'surl' => $surl,
										'url' => $url,
										'prenom_p' => $this->clients->prenom,
										'mensualite_p' => $rembNetEmail,
										'mensualite_avantfisca' => ($e['montant']/100),
										'nom_entreprise' => $this->companies->name,
										'date_bid_accepte' => date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd),
										'nbre_prets' => $nbpret,
										'solde_p' => $solde,
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
										// fin mail pour preteur //
									}
								}
								
							}
							
							// partie a retirer de bank unilend
							$rembNetTotal = $montant - $prelevements_obligatoires - $retenues_source - $csg -$prelevements_sociaux - $contributions_additionnelles - $prelevements_solidarite - $crds;
							
							// partie pour l'etat
							$TotalEtat = $prelevements_obligatoires + $retenues_source + $csg + $prelevements_sociaux + $contributions_additionnelles + $prelevements_solidarite + $crds;
							
							// On evite de créer une ligne qui sert a rien
							if($rembNetTotal != 0)
							{
								
								// On enregistre la transaction
								$this->transactions->montant = 0;
								$this->transactions->id_echeancier = 0; // on reinitialise
								$this->transactions->id_client = 0; // on reinitialise
								$this->transactions->montant_unilend = '-'.$rembNetTotal*100;
								$this->transactions->montant_etat = $TotalEtat*100;
								$this->transactions->id_echeancier_emprunteur = $RembEmpr['id_echeancier_emprunteur']; // id de l'echeance emprunteur
								$this->transactions->id_langue = 'fr';
								$this->transactions->date_transaction = date('Y-m-d H:i:s');
								$this->transactions->status = '1';
								$this->transactions->etat = '1';
								$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
								$this->transactions->type_transaction = 10; // remb unilend pour les preteurs
								$this->transactions->transaction = 2; // transaction virtuelle
								
								$this->transactions->id_transaction = $this->transactions->create();
								
								
								// bank_unilend (on retire l'argent redistribué)
								$this->bank_unilend->id_transaction = $this->transactions->id_transaction;
								$this->bank_unilend->id_project = $this->projects->id_project; 
								$this->bank_unilend->montant = '-'.$rembNetTotal*100;
								$this->bank_unilend->etat = $TotalEtat*100;
								$this->bank_unilend->type = 2; // remb unilend
								$this->bank_unilend->id_echeance_emprunteur = $RembEmpr['id_echeancier_emprunteur'];
								$this->bank_unilend->status = 1;
								$this->bank_unilend->create();
								
								// MAIL FACTURE REMBOURSEMENT EMPRUNTEUR //
								
								
								// Chargement des datas
								$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
								$projects = $this->loadData('projects');
								$companies = $this->loadData('companies');
								$emprunteur = $this->loadData('clients');
								$projects_status_history = $this->loadData('projects_status_history'); 
								
								// On recup les infos de l'emprunteur
								$projects->get($e['id_project'],'id_project');											
								$companies->get($projects->id_company,'id_company');
								$emprunteur->get($companies->id_client_owner,'id_client');
								
								$link = $this->furl.'/pdf/facture_ER/'.$emprunteur->hash.'/'.$e['id_project'].'/'.$e['ordre'];
								
								$dateRemb = $projects_status_history->select('id_project = '.$projects->id_project.' AND id_project_status = 8');
			//print_r($projects->id_project);
			
								$timeAdd = strtotime($dateRemb[0]['added']);
								$month = $this->dates->tableauMois['fr'][date('n',$timeAdd)];
								
								$dateRemb =  date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd);
								
								
								//********************************//
								//*** ENVOI DU MAIL FACTURE ER ***//
								//********************************//
								
								// Recuperation du modele de mail
								$this->mails_text->get('facture-emprunteur-remboursement','lang = "'.$this->language.'" AND type');
								
					
								// Variables du mailing
								$varMail = array(
								'surl' =>  $this->surl,
								'url' => $this->furl,
								'prenom' => $emprunteur->prenom,
								'pret' => number_format($projects->amount, 2, ',', ' '),
								'entreprise' => stripslashes(trim($companies->name)),
								'projet-title' => $projects->title,
								'compte-p' => $this->furl,
								'projet-p' => $this->furl.'/projects/detail/'.$projects->slug,
								'link_facture' => $link,
								'datedelafacture' => $dateRemb,
								'mois' => strtolower($this->dates->tableauMois['fr'][date('n')]),
								'annee' => date('Y'),
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
								if($this->Config['env'] == 'prod')
								{
									$this->email->addBCCRecipient('nicolas.lesur@unilend.fr');
									$this->email->addBCCRecipient('d.nandji@equinoa.com');
								}
								
								$this->email->setSubject(stripslashes($sujetMail));
								$this->email->setHTMLBody(stripslashes($texteMail));
								
								if($this->Config['env'] == 'prod') // nmp
								{
									Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($companies->email_facture),$tabFiler);
									// Injection du mail NMP dans la queue
									$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
								}
								else // non nmp
								{
									$this->email->addRecipient(trim($companies->email_facture));
									Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
								}
								//////////////////////////////////////////////
								
								// creation pdf facture ER //
								
								/*$hashclient = $emprunteur->hash;
								$id_project = $projects->id_project;
								$ordre = $e['ordre'];
								
								// Nom du fichier	
								$vraisNom = 'FACTURE-UNILEND-'.$projects->slug.'-'.$ordre;
								
								$url = $this->furl.'/pdf/facture_ER_html/'.$hashclient.'/'.$id_project.'/'.$ordre;
								
								$path = $this->path.'protected/pdf/facture/';
								$footer = $this->furl.'/pdf/footer_facture/';
								
								// fonction pdf
								$this->Web2Pdf->convert($path,$hashclient,$url,'facture_ER',$vraisNom,$id_project.'-'.$ordre,'','',$footer,'nodisplay');*/
								
								/////////////////////////////
								
							}
							/*echo '---------------------<br>';
							echo 'etat : '.$TotalEtat.'<br>';
							echo 'total a remb : '.$rembNetTotal.'<br>';
							echo 'montant : '.$montant.'<br>';
							echo '---------------------<br>';*/
							
							
							
							
							
						}
					}
					// bank_unilend
					$lesRembEmprun = $this->bank_unilend->select('type = 1 AND status = 0 AND id_project = '.$this->projects->id_project);
					// On parcourt les remb non reversé aux preteurs dans bank unilend et on met a jour le satut pour dire que c'est remb
					foreach($lesRembEmprun as $r)
					{
						$this->bank_unilend->get($r['id_unilend'],'id_unilend');
						$this->bank_unilend->status = 1;
						$this->bank_unilend->update();
					}
					
					if($this->projects_status->status == 110)
					{
						$this->projects_status_history->addStatus($_SESSION['user']['id_user'],80,$this->params['0']);
					}
					// Mise en session du message
					$_SESSION['freeow']['title'] = 'Remboursement preteur';
					$_SESSION['freeow']['message'] = 'Les preteurs ont bien &eacute;t&eacute; rembours&eacute; !';
				
					$settingsControleRemb->value = 1;
					$settingsControleRemb->update();
				
				}
				header('location:'.$this->lurl.'/dossiers/detail_remb/'.$this->params[0]);

				die;
				
				///////////////////////////////// fin ///////////////////////////////


			}
			
			
		}
	}
	
	function _detail_remb_preteur()
	{
		// Chargement du data
		$this->clients = $this->loadData('clients');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		
		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{
			
			
			$this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);
			
			$this->tauxMoyen = $this->loans->getAvgLoans($this->projects->id_project,'rate');
			
			$montantHaut = 0;
			$montantBas = 0;
			// si fundé ou remboursement
			
			foreach($this->loans->select('id_project = '.$this->projects->id_project) as $b)
			{
				$montantHaut += ($b['rate']*($b['amount']/100));
				$montantBas += ($b['amount']/100);
			}
			$this->tauxMoyen = ($montantHaut/$montantBas);
			
			// liste des echeances emprunteur par mois
			$lRembs  = $this->echeanciers->getSumRembEmpruntByMonths($this->projects->id_project);
			
			
			
			$this->montant = 0;
			$this->MontantRemb = 0;
			
			foreach($lRembs as $r)
			{
				$this->montant += $r['montant'];

				$this->MontantRemb += $this->echeanciers->getMontantRembEmprunteur($r['montant'],$r['commission'],$r['tva']);
				
			}
			
			// liste des encheres
			$this->lLenders = $this->loans->select('id_project = '.$this->projects->id_project,'rate ASC');

			
		}

	}
	
	function _detail_echeance_preteur()
	{
		// Chargement du data
		$this->clients = $this->loadData('clients');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->projects = $this->loadData('projects');
		
		// les remb d'une enchere
		$this->lRemb = $this->echeanciers->select('id_loan = '.$this->params[1],'ordre ASC');

	}
	
	function _echeancier_emprunteur()
	{
		// Chargement du data
		$this->clients = $this->loadData('clients');
		$this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->projects = $this->loadData('projects');
		
		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project'))
		{


			// liste des echeances emprunteur par mois
			$this->lRemb = $this->echeanciers_emprunteur->select('id_project = '.$this->projects->id_project,'ordre ASC');
			
			
			$this->montantPreteur = 0;
			$this->MontantEmprunteur = 0;
			$this->commission = 0;
			$this->comParMois = 0;
			$this->comTtcParMois = 0;
			$this->tva = 0;
			$this->totalTva = 0;
			$this->capital = 0;
			
			foreach($this->lRemb as $r)
			{
				$this->montantPreteur += $r['montant'];
	
				$this->MontantEmprunteur += $this->echeanciers->getMontantRembEmprunteur($r['montant'],$r['commission'],$r['tva']);
				$this->commission += $r['commission'];
				$this->comParMois = $r['commission'];
				$this->comTtcParMois = $r['commission']+$r['tva'];
				$this->tva = $r['tva'];
				$this->totalTva += $r['tva'];
				
				$this->capital += $r['capital'];
			}
			
		}

	}
	
}