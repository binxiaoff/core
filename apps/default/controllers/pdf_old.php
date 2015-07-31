<?php 

class pdfController extends bootstrap
{
	var $Command;
	
	function pdfController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		
		// Recuperation du bloc
		$this->blocs->get('pdf-contrat','slug');
		$lElements = $this->blocs_elements->select('id_bloc = '.$this->blocs->id_bloc.' AND id_langue = "'.$this->language.'"');
		foreach($lElements as $b_elt)
		{
			$this->elements->get($b_elt['id_element']);
			$this->bloc_pdf_contrat[$this->elements->slug] = $b_elt['value'];
			$this->bloc_pdf_contratComplement[$this->elements->slug] = $b_elt['complement'];	
		}
		
	}
	
	
	function _default()
	{
		
	}
	
	function _mandat_preteur()
	{
		// chargement des datas
		$clients = $this->loadData('clients');
		$clients->get($this->params[0],'hash');
		
		$vraisNom = 'MANDAT-UNILEND-'.$clients->id_client;
		
		$this->Web2Pdf->convert($this->path.'protected/pdf/mandat/',$this->params[0],$this->lurl.'/pdf/mandat_html/'.$this->params[0],'mandat_preteur',$vraisNom);
	}
	
	// mandat emprunteur
	function _mandat()
	{
		// chargement des datas
		$clients = $this->loadData('clients');
		$clients_mandats = $this->loadData('clients_mandats');
		$companies = $this->loadData('companies');
		$projects = $this->loadData('projects');
		
		// On recup le client
		if($clients->get($this->params[0],'hash') && isset($this->params[1]))
		{
			// si on a un params 1 on check si on a une entreprise et un projet
				
			// on chek si le projet est bien au client
			if($companies->get($clients->id_client,'id_client_owner') && $projects->get($this->params[1],'id_project') && $projects->id_company == $companies->id_company)
			{
				
				// la c'est good on peut faire le traitement
				
				$path = $this->path.'protected/pdf/mandat/';
				$slug = $this->params[0];
				$urlsite = $this->lurl.'/pdf/mandat_html/'.$this->params[0].'/'.(isset($this->params[1])?$this->params[1].'/':'');
				$name = 'mandat';
				$param = $this->params[1];
				$sign = '';
				$vraisNom = 'MANDAT-UNILEND-'.$projects->slug.'-'.$clients->id_client;
				
				
				// on check si y a deja un traitement universign de fait
				$exist = false;
				if($clients_mandats->get($clients->id_client,'id_project = '.$this->params[1].' AND id_client'))
				{
					// Si on a affaire a un mandat charger manuelement
					if($clients_mandats->id_universign == 'no_universign')
					{
						// on recup directement le pdf
						$this->Web2Pdf->lecture($path.$clients_mandats->name,$clients_mandats->name);
						die;
					}
					
					if($clients_mandats->status > 0) $sign = $clients_mandats->status;
					
					$exist = true;
					
				}
				
				// On ouvre le pdf et si c'est la premiere fois on redirige sur universign pour le faire signer
				if($this->Web2Pdf->convert($path,$slug,$urlsite,$name,$vraisNom,$param,$sign) == 'universign')
				{
					$nom_fichier = $name.'-'.$slug.".pdf";
					if($param != '')$nom_fichier = $name.'-'.$slug."-".$param.".pdf";
		
					
					
					// On enregistre le mandat en statut pas encore traité
					$clients_mandats->id_client = $clients->id_client;
					$clients_mandats->url_pdf = '/pdf/mandat/'.$this->params[0].'/'.(isset($this->params[1])?$this->params[1].'/':'');
					$clients_mandats->name = $nom_fichier;
					$clients_mandats->id_project = $projects->id_project;
					
					if($exist == false)$clients_mandats->id_mandat = $clients_mandats->create();
					else $clients_mandats->update();
					
					
					header("location:".$url.'/universign/mandat/'.$clients_mandats->id_mandat);
					die;
					
				}
			}
			else
			{
				// pas good on redirige
				header("location:".$this->lurl);
				die;
			}
		}
		else
		{
			header("location:".$this->lurl);
			die;
		}
	}
	function _mandat_html()


	{
		// si le client existe
		if($this->clients->get($this->params[0],'hash'))
		{
			$this->companies = $this->loadData('companies');
			$this->projects = $this->loadData('projects');
			$this->pays = $this->loadData('pays');
			$this->clients_adresses = $this->loadData('clients_adresses');
			$this->lenders_accounts = $this->loadData('lenders_accounts');
			
			$this->clients_adresses->get($this->clients->id_client,'id_client');
			
			$this->pays->get($this->clients->id_langue,'id_langue');
			
			// preteur
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
			
			$this->iban[1] = substr($this->lenders_accounts->iban,0,4);
			$this->iban[2] = substr($this->lenders_accounts->iban,4,4);
			$this->iban[3] = substr($this->lenders_accounts->iban,8,4);
			$this->iban[4] = substr($this->lenders_accounts->iban,12,4);
			$this->iban[5] = substr($this->lenders_accounts->iban,16,4);
			$this->iban[6] = substr($this->lenders_accounts->iban,20,4);
			$this->iban[7] = substr($this->lenders_accounts->iban,24,3);
			
			$this->leIban = $this->lenders_accounts->iban;
			
			$this->entreprise = false;
			if($this->companies->get($this->clients->id_client,'id_client_owner'))
			{
				
				$this->entreprise = true;
				
				$this->iban[1] = substr($this->companies->iban,0,4);
				$this->iban[2] = substr($this->companies->iban,4,4);
				$this->iban[3] = substr($this->companies->iban,8,4);
				$this->iban[4] = substr($this->companies->iban,12,4);
				$this->iban[5] = substr($this->companies->iban,16,4);
				$this->iban[6] = substr($this->companies->iban,20,4);
				$this->iban[7] = substr($this->companies->iban,24,3);
				
				$this->leIban = $this->companies->iban;
				
				// Motif mandat
				/*$nom = $this->clients->nom;
				$id_project = str_pad($this->projects->id_project,6,0,STR_PAD_LEFT);
				$this->motif = strtoupper('UNILEND'.$id_project.$nom);*/
				
				
				
			}
			
			// pour savoir si Preteur ou emprunteur
			if(isset($this->params[1]) && $this->projects->get($this->params[1],'id_project'))
			{
				// Motif mandat emprunteur
				$p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
				$id_project = str_pad($this->projects->id_project,6,0,STR_PAD_LEFT);
				$this->motif = mb_strtoupper($id_project.'E'.$p.$nom,'UTF-8');
				$this->motif = $this->ficelle->str_split_unicode('UNILEND'.$this->motif);
			}
			else
			{
				// Motif mandat preteur
				$p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
				$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
				$this->motif = mb_strtoupper($id_client.'P'.$p.$nom,'UTF-8');
				$this->motif = $this->ficelle->str_split_unicode('UNILEND'.$this->motif);
			}
			
			
			
			
			
			// Créancier adresse
			$this->settings->get('Créancier adresse','type');
			$this->creancier_adresse = $this->settings->value;
			// Créancier cp
			$this->settings->get('Créancier cp','type');
			$this->creancier_cp = $this->settings->value;
			// Créancier identifiant
			$this->settings->get('ICS de SFPMEI','type');
			$this->creancier_identifiant = $this->settings->value;
			// Créancier nom
			$this->settings->get('Créancier nom','type');
			$this->creancier = $this->settings->value;
			// Créancier pays
			$this->settings->get('Créancier pays','type');
			$this->creancier_pays = $this->settings->value;
			// Créancier ville
			$this->settings->get('Créancier ville','type');
			$this->creancier_ville = $this->settings->value;
			// Créancier code identifiant	
			$this->settings->get('Créancier code identifiant','type');
			$this->creancier_code_id = $this->settings->value;
			
			
			
			// Adresse retour	
			$this->settings->get('Adresse retour','type');
			$this->adresse_retour = $this->settings->value;
			
			
			
		}
	}
	
	function _pouvoir()
	{
		// si le client existe
		if($this->clients->get($this->params[0],'hash') && isset($this->params[1]))
		{
			$this->companies = $this->loadData('companies');
			$this->projects = $this->loadData('projects');
			$projects_pouvoir = $this->loadData('projects_pouvoir');
			$clients = $this->loadData('clients');
			  
			//on recup l'entreprise
			$this->companies->get($this->clients->id_client,'id_client_owner');
			
			if($this->projects->get($this->params[1],'id_company = '.$this->companies->id_company.' AND id_project'))
			{
				
				// On recup le client
				$clients->get($this->params[0],'hash');
				
		
				$path = $this->path.'protected/pdf/pouvoir/';
				$slug = $this->params[0];
				$urlsite = $this->lurl.'/pdf/pouvoir_html/'.$this->params[0].'/'.$this->params[1].'/';
				$name = 'pouvoir';
				$param = $this->params[1];
				$entete = $this->lurl.'/pdf/entete/';
				$piedpage = $this->lurl.'/pdf/piedpage/';
				$vraisNom = 'POUVOIR-UNILEND-'.$this->projects->slug.'-'.$this->clients->id_client;
				$sign = '';
				
				$exist = false;
				// on check si y a deja un pouvoir
				if($projects_pouvoir->get($this->projects->id_project,'id_project'))
				{
					// Si on a affaire a un mandat charger manuelement
					if($projects_pouvoir->id_universign == 'no_universign')
					{
						// on recup directement le pdf
						$this->Web2Pdf->lecture($path.$projects_pouvoir->name,$vraisNom);
						die;
					}
					
					if($projects_pouvoir->status > 0)$sign = $projects_pouvoir->status;
					
					$exist = true;
					
				}
						
				
				if($this->Web2Pdf->convert($path,$slug,$urlsite,$name,$vraisNom,$param,$sign,$entete,$piedpage) == 'universign')
				{
					$nom_fichier = $name.'-'.$slug.".pdf";
					if($param != '')$nom_fichier = $name.'-'.$slug."-".$param.".pdf";
		
					// Si on a encore aucun id universign (pas de ligne mandat pour ce pdf)
					
					
					// On enregistre le mandat en statut pas encore traité
					$projects_pouvoir->id_project = $this->projects->id_project;
					$projects_pouvoir->url_pdf = '/pdf/pouvoir/'.$this->params[0].'/'.$this->params[1].'/';
					$projects_pouvoir->name = $nom_fichier;
					
					if($exist == false)$projects_pouvoir->id_pouvoir = $projects_pouvoir->create();
					else $projects_pouvoir->update();
					
					
					header("location:".$url.'/universign/pouvoir/'.$projects_pouvoir->id_pouvoir);
					die;
				}
				
				 
			}
			else
			{
				header('Location:'.$this->lurl);
				die;
			}
		}
		else
		{
			header('Location:'.$this->lurl);
			die;
		}
	}
	function _pouvoir_html()
	{
		// si le client existe
		if($this->clients->get($this->params[0],'hash'))
		{
			//Recuperation des element de traductions
			$this->lng['pdf-pouvoir'] = $this->ln->selectFront('pdf-pouvoir',$this->language,$this->App);
			
			// Recuperation du bloc
			$this->blocs->get('pouvoir','slug');
			$lElements = $this->blocs_elements->select('id_bloc = '.$this->blocs->id_bloc.' AND id_langue = "'.$this->language.'"');
			foreach($lElements as $b_elt)
			{
				$this->elements->get($b_elt['id_element']);
				$this->bloc_pouvoir[$this->elements->slug] = $b_elt['value'];
				$this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];	
			}
				
			$this->companies = $this->loadData('companies');
			$this->companies_details = $this->loadData('companies_details');
			$this->projects = $this->loadData('projects');
			$this->loans = $this->loadData('loans');
			$this->echeanciers = $this->loadData('echeanciers');
			$this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
			$this->lenders_accounts = $this->loadData('lenders_accounts');
			$this->companies_actif_passif = $this->loadData('companies_actif_passif');
			
			//on recup l'entreprise
			$this->companies->get($this->clients->id_client,'id_client_owner');

			if($this->projects->get($this->params[1],'id_company = '.$this->companies->id_company.' AND id_project'))
			{
				$this->companies_details->get($this->companies->id_company,'id_company');
				
				
				// date_dernier_bilan_mois
				$date_dernier_bilan = explode('-',$this->companies_details->date_dernier_bilan);
				$this->date_dernier_bilan_annee = $date_dernier_bilan[0];
				$this->date_dernier_bilan_mois = $date_dernier_bilan[1];
				$this->date_dernier_bilan_jour = $date_dernier_bilan[2];
				
				// Montant prété a l'emprunteur
				$this->montantPrete = $this->projects->amount;
				
				// moyenne pondéré
				$montantHaut = 0;
				$montantBas = 0;
				// si fundé ou remboursement
				
				foreach($this->loans->select('id_project = '.$this->projects->id_project) as $b)
				{
					$montantHaut += ($b['rate']*($b['amount']/100));
					$montantBas += ($b['amount']/100);
				}
				$this->taux = ($montantHaut/$montantBas);	
				
				$this->nbLoans = $this->loans->counter('id_project = '.$this->projects->id_project);
				
				// Remb emprunteur par mois
				$this->echeanceEmprun = $this->echeanciers_emprunteur->select('id_project = '.$this->projects->id_project.' AND ordre = 1');
				
				$this->rembByMonth = $this->echeanciers->getMontantRembEmprunteur($this->echeanceEmprun[0]['montant'],$this->echeanceEmprun[0]['commission'],$this->echeanceEmprun[0]['tva']);
				$this->rembByMonth = ($this->rembByMonth/100);
				
				// date premiere echance
				//$this->dateFirstEcheance = $this->echeanciers->getDatePremiereEcheance($this->projects->id_project);
				$this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheance($this->projects->id_project);
				
				
				
				// liste des echeances emprunteur par mois
				
				$this->lRemb = $this->echeanciers_emprunteur->select('id_project = '.$this->projects->id_project,'ordre ASC');
				
				$this->capital = 0;
				foreach($this->lRemb as $r)
				{
					$this->capital += $r['capital'];
				}
				//echo $this->capital;
				
				// Liste des actif passif
				$this->l_AP = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'" AND annee = '.$this->date_dernier_bilan_annee,'annee DESC');
				
				
				
				$this->totalActif = ($this->l_AP[0]['immobilisations_corporelles']+$this->l_AP[0]['immobilisations_incorporelles']+$this->l_AP[0]['immobilisations_financieres']+$this->l_AP[0]['stocks']+$this->l_AP[0]['creances_clients']+$this->l_AP[0]['disponibilites']+$this->l_AP[0]['valeurs_mobilieres_de_placement']);
				
				$this->totalPassif = ($this->l_AP[0]['capitaux_propres']+$this->l_AP[0]['provisions_pour_risques_et_charges']+$this->l_AP[0]['amortissement_sur_immo']+$this->l_AP[0]['dettes_financieres']+$this->l_AP[0]['dettes_fournisseurs']+$this->l_AP[0]['autres_dettes']);
				
				// liste des encheres
				$this->lLenders = $this->loans->select('id_project = '.$this->projects->id_project,'rate ASC');
				
			}
			else
			{
				header('Location:'.$this->lurl);
				die;
			}
		}
		else
		{
			header('Location:'.$this->lurl);
			die;
		}
	}
	
	function _contrat()
	{
		// preteur
		if($this->clients->checkAccess() && $this->clients->hash == $this->params[0]){
			
		}
		// admin
		elseif(isset($_SESSION['user']['id_user']) && $_SESSION['user']['id_user'] != ''){
			
		}
		else{
			header('Location:'.$this->lurl);
			die;
		}
		
		/*if(!$this->clients->checkAccess()){
			header('Location:'.$this->lurl);
			die;
		}*/
		
		$projects = $this->loadData('projects');
		$clients = $this->loadData('clients');
		$loans = $this->loadData('loans');
		$lenders_accounts = $this->loadData('lenders_accounts');
		
		// on recup ca
		$clients->get($this->params[0],'hash');
		$lenders_accounts->get($clients->id_client,'id_client_owner');
		$loans->get($this->params[1],'id_lender = '.$lenders_accounts->id_lender_account.' AND id_loan');
		$projects->get($loans->id_project,'id_project');
		
		$vraisNom = 'CONTRAT-UNILEND-'.$projects->slug.'-'.$loans->id_loan;
		
		$this->Web2Pdf->convert($this->path.'protected/pdf/contrat/',$this->params[0],$this->lurl.'/pdf/contrat_html/'.$this->params[0].'/'.$this->params[1].'/','contrat',$vraisNom,$this->params[1]);
	}
	function _contrat_html()
	{
		
		// si le client existe
		if($this->clients->get($this->params[0],'hash'))
		{
			$this->loans = $this->loadData('loans');
			$this->lenders_accounts = $this->loadData('lenders_accounts');
			$this->echeanciers = $this->loadData('echeanciers');
			$this->projects = $this->loadData('projects');
			$this->companiesEmprunteur = $this->loadData('companies');
			$this->companies_detailsEmprunteur = $this->loadData('companies_details');
			$this->companiesPreteur = $this->loadData('companies');
			$this->emprunteur = $this->loadData('clients');
			$this->clients_adresses = $this->loadData('clients_adresses');
			$this->companies_actif_passif = $this->loadData('companies_actif_passif');
			$this->projects_pouvoir = $this->loadData('projects_pouvoir');
			
			// on recup adresse preteur
			$this->clients_adresses->get($this->clients->id_client,'id_client');
			
			// preteur
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
			
			// si le loan existe
			if($this->loans->get($this->params[1],'id_lender = '.$this->lenders_accounts->id_lender_account.' AND id_loan'))
			{
				// On recup le projet
				$this->projects->get($this->loans->id_project,'id_project');
				// On recup l'entreprise
				$this->companiesEmprunteur->get($this->projects->id_company,'id_company');
				// On recup le detail entreprise emprunteur
				$this->companies_detailsEmprunteur->get($this->projects->id_company,'id_company');
				
				// On recup l'emprunteur
				$this->emprunteur->get($this->companiesEmprunteur->id_client_owner,'id_client');
				
				// Si preteur morale
				if($this->clients->type == 2) 
				{
					// entreprise preteur;

					$this->companiesPreteur->get($this->clients->id_client,'id_client_owner');
				
				}
				
				// date premiere echance
				//$this->dateFirstEcheance = $this->echeanciers->getDatePremiereEcheance($this->projects->id_project);
				$this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheance($this->projects->id_project);
				
				// date_dernier_bilan_mois
				$date_dernier_bilan = explode('-',$this->companies_detailsEmprunteur->date_dernier_bilan);
				$this->date_dernier_bilan_annee = $date_dernier_bilan[0];
				$this->date_dernier_bilan_mois = $date_dernier_bilan[1];
				$this->date_dernier_bilan_jour = $date_dernier_bilan[2];
				
				// Liste des actif passif
				//$this->l_AP = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'" AND annee = '.$this->date_dernier_bilan_annee,'annee DESC');
				
				// Liste des actif passif
				$this->l_AP = $this->companies_actif_passif->select('id_company = "'.$this->companiesEmprunteur->id_company.'" AND annee = '.$this->date_dernier_bilan_annee,'annee DESC');
				
				$this->totalActif = ($this->l_AP[0]['immobilisations_corporelles']+$this->l_AP[0]['immobilisations_incorporelles']+$this->l_AP[0]['immobilisations_financieres']+$this->l_AP[0]['stocks']+$this->l_AP[0]['creances_clients']+$this->l_AP[0]['disponibilites']+$this->l_AP[0]['valeurs_mobilieres_de_placement']);
				
				$this->totalPassif = ($this->l_AP[0]['capitaux_propres']+$this->l_AP[0]['provisions_pour_risques_et_charges']+$this->l_AP[0]['amortissement_sur_immo']+$this->l_AP[0]['dettes_financieres']+$this->l_AP[0]['dettes_fournisseurs']+$this->l_AP[0]['autres_dettes']);
				
				
				// les remb d'une enchere
				$this->lRemb = $this->echeanciers->select('id_loan = '.$this->loans->id_loan,'ordre ASC');
				
				$this->capital = 0;
				foreach($this->lRemb as $r)
				{
					$this->capital += $r['capital'];
				}
				
				// si on a le pouvoir
				if($this->projects_pouvoir->get($this->projects->id_project,'id_project'))
				{
					$this->dateContrat = date('d/m/Y',strtotime($this->projects_pouvoir->updated));
				}
				else $this->dateContrat = date('d/m/Y');
				
			}
			else
			{
				header('Location:'.$this->lurl);
				die;
			}
			
		}
		else
		{
			header('Location:'.$this->lurl);
			die;
		}
	}
	
	function _entete()
	{
		
	}
	function _piedpage()
	{
		// Recuperation du bloc
		$this->blocs->get('pouvoir','slug');
		$lElements = $this->blocs_elements->select('id_bloc = '.$this->blocs->id_bloc.' AND id_langue = "'.$this->language.'"');
		foreach($lElements as $b_elt)
		{
			$this->elements->get($b_elt['id_element']);
			$this->bloc_pouvoir[$this->elements->slug] = $b_elt['value'];
			$this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];	
		}
	}
	
	function _declarationContratPret_html()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->loans = $this->loadData('loans');
		$this->projects = $this->loadData('projects');
		$this->companiesEmp = $this->loadData('companies');
		$this->emprunteur = $this->loadData('clients');
		$this->lender = $this->loadData('lenders_accounts');
		$this->preteur = $this->loadData('clients');
		$this->preteurCompanie = $this->loadData('companies');
		$this->preteur_adresse = $this->loadData('clients_adresses');
		$this->echeanciers = $this->loadData('echeanciers');
		
		if(isset($this->params[0]) && $this->loans->get($this->params[0],'status = "0" AND id_loan'))
		{
			
			$this->settings->get('Declaration contrat pret - adresse','type');
			$this->adresse = $this->settings->value;
			
			$this->settings->get('Declaration contrat pret - raison sociale','type');
			$this->raisonSociale = $this->settings->value;
			
			// Coté emprunteur
			
			// On recup le projet
			$this->projects->get($this->loans->id_project,'id_project');
			// On recup la companie
			$this->companiesEmp->get($this->projects->id_company,'id_company');
			// On recup l'emprunteur
			$this->emprunteur->get($this->companiesEmp->id_client_owner,'id_client');
			
			
			// Coté preteur
			
			// On recup le lender
			$this->lender->get($this->loans->id_lender,'id_lender_account');
			// On recup le preteur
			$this->preteur->get($this->lender->id_client_owner,'id_client');
			// On recup l'adresse preteur
			$this->preteur_adresse->get($this->lender->id_client_owner,'id_client');
			
			$this->lEcheances = $this->echeanciers->getSumByAnnee($this->loans->id_loan);
			
			if($this->preteur->type == 2)
			{
				$this->preteurCompanie->get($this->lender->id_company_owner,'id_company');	
				
				$this->nomPreteur = $this->preteurCompanie->name;
				$this->adressePreteur = $this->preteurCompanie->adresse1;
				$this->cpPreteur = $this->preteurCompanie->zip;
				$this->villePreteur = $this->preteurCompanie->city;
			}
			else
			{
				$this->nomPreteur = $this->preteur->prenom.' '.$this->preteur->nom;
				$this->adressePreteur = $this->preteur_adresse->adresse1;
				$this->cpPreteur = $this->preteur_adresse->cp;
				$this->villePreteur = $this->preteur_adresse->ville;
			}
			
			/*echo '<pre>';
			print_r($leche);
			echo '</pre>';*/
			
			
		}
		
	}
	
	
	function _facture_EF()
	{
		// si le client existe
		if($this->clients->get($this->params[0],'hash') && isset($this->params[1]))
		{
			$this->companies = $this->loadData('companies');
			$this->projects = $this->loadData('projects');
			  
			//on recup l'entreprise
			$this->companies->get($this->clients->id_client,'id_client_owner');
			
			// et on recup le projet
			if($this->projects->get($this->params[1],'id_company = '.$this->companies->id_company.' AND id_project'))
			{
				
				$vraisNom = 'FACTURE-UNILEND-'.$this->projects->slug;
				
				// fonction pdf
				$this->Web2Pdf->convert($this->path.'protected/pdf/facture/',$this->params[0],$this->lurl.'/pdf/facture_EF_html/'.$this->params[0].'/'.$this->params[1].'/','facture_EF',$vraisNom,$this->params[1],'','',$this->lurl.'/pdf/footer_facture/');
			}
		}
	}
	
	function _footer_facture()
	{
		$this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture',$this->language,$this->App);
		
		// titulaire du compte
		$this->settings->get('titulaire du compte','type');
		$this->titreUnilend = mb_strtoupper($this->settings->value,'UTF-8');
		
		// Declaration contrat pret - raison sociale
		$this->settings->get('Declaration contrat pret - raison sociale','type');
		$this->raisonSociale = mb_strtoupper($this->settings->value,'UTF-8');
		
		// Facture - SFF PME
		$this->settings->get('Facture - SFF PME','type');
		$this->sffpme = mb_strtoupper($this->settings->value,'UTF-8');
		
		// Facture - capital
		$this->settings->get('Facture - capital','type');
		$this->capital = mb_strtoupper($this->settings->value,'UTF-8');
		
		// Declaration contrat pret - adresse
		$this->settings->get('Declaration contrat pret - adresse','type');
		$this->raisonSocialeAdresse = mb_strtoupper($this->settings->value,'UTF-8');
		
		// Facture - telephone
		$this->settings->get('Facture - telephone','type');
		$this->telephone = mb_strtoupper($this->settings->value,'UTF-8');
		
		// Facture - RCS
		$this->settings->get('Facture - RCS','type');
		$this->rcs = mb_strtoupper($this->settings->value,'UTF-8');
		
		// Facture - TVA INTRACOMMUNAUTAIRE
		$this->settings->get('Facture - TVA INTRACOMMUNAUTAIRE','type');
		$this->tvaIntra = mb_strtoupper($this->settings->value,'UTF-8');
	}
	
	function _facture_EF_html()
	{
		$this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture',$this->language,$this->App);
		
		// si le client existe
		if($this->clients->get($this->params[0],'hash') && isset($this->params[1]))
		{
			$this->companies = $this->loadData('companies');
			$this->projects = $this->loadData('projects');
			$this->compteur_factures = $this->loadData('compteur_factures');
			$this->transactions = $this->loadData('transactions');
			$this->projects_status_history = $this->loadData('projects_status_history');
			$this->factures = $this->loadData('factures');
			
			// TVA
			$this->settings->get('TVA','type');
			$this->tva = $this->settings->value;
			
			//on recup l'entreprise
			$this->companies->get($this->clients->id_client,'id_client_owner');
			
			// et on recup le projet
			if($this->projects->get($this->params[1],'id_company = '.$this->companies->id_company.' AND id_project'))
			{
				$histoRemb = $this->projects_status_history->select('id_project = '.$this->projects->id_project.' AND id_project_status = 8','added DESC',0,1);
				
				if($histoRemb != false)
				{
					$this->dateRemb = $histoRemb[0]['added'];
				
					$timeDateRemb = strtotime($this->dateRemb);
				
					$compteur = $this->compteur_factures->compteurJournalier($this->projects->id_project);
					
					$this->num_facture = 'FR-E'.date('Ymd',$timeDateRemb).str_pad($compteur,5,"0",STR_PAD_LEFT);
					
					$this->transactions->get($this->projects->id_project,'type_transaction = 9 AND status = 1 AND etat = 1 AND id_project');
					
					$this->ttc = ($this->transactions->montant_unilend/100);
					
					$cm = ($this->tva+1); // CM
					$this->ht = ($this->ttc/$cm); // HT
					$this->taxes = ($this->ttc-$this->ht); // TVA
					
					$montant = ((str_replace('-','',$this->transactions->montant)+$this->transactions->montant_unilend)/100); // Montant pret
					$txCom = round(($this->ht/$montant)*100,0); // taux commission
					
					if(!$this->factures->get($this->projects->id_project,'type_commission = 1 AND id_company = '.$this->companies->id_company.' AND id_project')){
						$this->factures->num_facture = $this->num_facture;
						$this->factures->date = $this->dateRemb;
						$this->factures->id_company = $this->companies->id_company;
						$this->factures->id_project = $this->projects->id_project;
						$this->factures->ordre = 0;
						$this->factures->type_commission = 1; // financement
						$this->factures->commission = $txCom;
						$this->factures->montant_ht = ($this->ht*100);
						$this->factures->tva = ($this->taxes*100);
						$this->factures->montant_ttc = ($this->ttc*100);
						$this->factures->create();
						
					}
				}
				
			}
		}
	}
	
	function _facture_ER()
	{
		// si le client existe
		if($this->clients->get($this->params[0],'hash') && isset($this->params[1]))
		{
			$this->companies = $this->loadData('companies');
			$this->projects = $this->loadData('projects');
			$this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
			  
			//on recup l'entreprise
			$this->companies->get($this->clients->id_client,'id_client_owner');
			
			// et on recup le projet
			if($this->projects->get($this->params[1],'id_company = '.$this->companies->id_company.' AND id_project'))
			{
				// on recup l'echeance concernée
				if($this->echeanciers_emprunteur->get($this->projects->id_project,'ordre = '.$this->params[2].'  AND id_project'))
				{
					$vraisNom = 'FACTURE-UNILEND-'.$this->projects->slug.'-'.$this->params[2];
					
					// fonction pdf
					$this->Web2Pdf->convert($this->path.'protected/pdf/facture/',$this->params[0],$this->lurl.'/pdf/facture_ER_html/'.$this->params[0].'/'.$this->params[1].'/'.$this->params[2].'/','facture_ER',$vraisNom,$this->params[1].'-'.$this->params[2],'','',$this->lurl.'/pdf/footer_facture/');
				}
			}
		}
	}
	
	function _facture_ER_html()
	{
		$this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture',$this->language,$this->App);
		
		// si le client existe
		if($this->clients->get($this->params[0],'hash') && isset($this->params[1]) && isset($this->params[2]))
		{
			$this->companies = $this->loadData('companies');
			$this->projects = $this->loadData('projects');
			$this->compteur_factures = $this->loadData('compteur_factures');
			$this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
			$this->echeanciers = $this->loadData('echeanciers');
			$this->factures = $this->loadData('factures');
			
			// TVA
			$this->settings->get('TVA','type');
			$this->tva = $this->settings->value;
			
			// Commission remboursement
			$this->settings->get('Commission remboursement','type');
			$txcom = $this->settings->value;
			
			
			
			//on recup l'entreprise
			$this->companies->get($this->clients->id_client,'id_client_owner');
			
			// et on recup le projet
			if($this->projects->get($this->params[1],'id_company = '.$this->companies->id_company.' AND id_project'))
			{
				$uneEcheancePreteur = $this->echeanciers->select('id_project = '.$this->projects->id_project.' AND ordre = '.$this->params[2],'',0,1);
				$this->date_echeance_reel = $uneEcheancePreteur[0]['date_echeance_reel'];
				
				$time_date_echeance_reel = strtotime($this->date_echeance_reel);
				
				// on recup l'echeance concernée
				if($this->echeanciers_emprunteur->get($this->projects->id_project,'ordre = '.$this->params[2].'  AND id_project'))
				{
					$compteur = $this->compteur_factures->compteurJournalier($this->projects->id_project);
					
					$this->num_facture = 'FR-E'.date('Ymd',$time_date_echeance_reel).str_pad($compteur,5,"0",STR_PAD_LEFT);
					
					$this->ht = ($this->echeanciers_emprunteur->commission/100);
					$this->taxes = ($this->echeanciers_emprunteur->tva/100);
					$this->ttc = ($this->ht+$this->taxes);
				
					
					if(!$this->factures->get($this->projects->id_project,'ordre = '.$this->params[2].' AND  type_commission = 2 AND id_company = '.$this->companies->id_company.' AND id_project')){
						$this->factures->num_facture = $this->num_facture;
						$this->factures->date = $this->date_echeance_reel;
						$this->factures->id_company = $this->companies->id_company;
						$this->factures->id_project = $this->projects->id_project;
						$this->factures->ordre = $this->params[2];
						$this->factures->type_commission = 2; // remboursement
						$this->factures->commission = ($txcom*100);
						$this->factures->montant_ht = ($this->ht*100);
						$this->factures->tva = ($this->taxes*100);
						$this->factures->montant_ttc = ($this->ttc*100);
						$this->factures->create();
						
					}
				
				}
				
			}
		}
	}
	
}