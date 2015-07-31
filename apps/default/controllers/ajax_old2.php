<?php
class ajaxController extends bootstrap
{	
	var $Command;
	
	function ajaxController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->lurl;
		
		$this->autoFireHeader = false;
		$this->autoFireDebug = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
	}
	
	/* Modification de la modifcation des traductions à la volée */
	function _activeModificationsTraduction()
	{
		// On desactive la vue qui sert à rien
		$this->autoFireView = false;
		
		// On renseigne la session avec l'etat demandé
		$_SESSION['modification'] = $this->params[0];
	}
	
	function _session_etape2_lender()
	{
		$this->autoFireView = false;
		
		unset($_SESSION['inscription_etape2']);
		
		$_SESSION['inscription_etape2']['bic'] = str_replace(' ','',$_POST['bic']);
		for($i=1;$i<=7;$i++)
		{
			$_SESSION['inscription_etape2']['iban'] .= str_replace(' ','',$_POST['iban'.$i]);
		}
		$_SESSION['inscription_etape2']['origine_des_fonds'] = $_POST['origine_des_fonds'];
		$_SESSION['inscription_etape2']['cni_passeport'] = $_POST['cni_passeport'];
		$_SESSION['inscription_etape2']['preciser'] = $_POST['preciser'];
		
	}
	
	function _autocompleteCp()
	{
		$this->autoFireView = false;
		
		if(isset($_POST['ville']) && $this->villes->get($this->bdd->escape_string($_POST['ville']),'ville'))
		{	

			if(strlen($this->villes->cp) == 4)$lecp = '0'.$this->villes->cp;
			else $lecp = $this->villes->cp;
			
			echo $lecp;
		}
		else echo 'nok';
	}
	
	function _checkCp()
	{
		$this->autoFireView = false;
		
		
		
		if(isset($_POST['cp'])){
			
			$error = 'ok';
			
			// Ville existante
			if(isset($_POST['ville']) && $this->villes->get($this->bdd->escape_string($_POST['ville']),'ville'))
			{
				if($_POST['cp'] == ''){
					$error = 'nok';	
				}
				if(!is_numeric($_POST['cp'])){
					$error = 'nok';	
				}
				if(strlen($_POST['cp']) != 5){
					$error = 'nok';	
				}
			}
			// Ville n'existe pas en bdd
			else{
				// on n'a pas de cp
				if($_POST['cp'] == ''){
					$error = 'nok';	
				}
			}
		}
		else{
			$error = 'nok';	
		}
		
		echo $error;
		die;
	}
	
	/*function _autocompleteVille()
	{
		$this->autoFireView = false;
		
		if(isset($_POST['cp']) && $this->villes->get($_POST['cp'],'ville'))
		{

			if(strlen($this->villes->cp) == 4)$lecp = '0'.$this->villes->cp;
			else $lecp = $this->villes->cp;
			
			echo $lecp;
		}
		else echo 'nok';
	}*/
	
	function _load_project()
	{
		$this->autoFireView = false;
		
		//Recuperation des element de traductions
		$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
		
		// Heure fin periode funding
		$this->settings->get('Heure fin periode funding','type');
		$this->heureFinFunding = $this->settings->value;
		
		// tri par taux
		$this->settings->get('Tri par taux','type');
		$this->triPartx = $this->settings->value;
		$this->triPartx = explode(';',$this->triPartx);
		
		// tri par taux intervalles
		$this->settings->get('Tri par taux intervalles','type');
		$this->triPartxInt = $this->settings->value;
		$this->triPartxInt = explode(';',$this->triPartxInt);
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->favoris = $this->loadData('favoris');
		$this->bids = $this->loadData('bids');
		$this->loans = $this->loadData('loans');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		
		$ordre = $this->tabOrdreProject[$_POST['ordreProject']];
		
		$_SESSION['ordreProject'] = $_POST['ordreProject'];
		
		if(isset($_POST['where']) && $_POST['where'] != '')
		{
			$key = $_SESSION['tri']['taux'];
			$val = explode('-',$this->triPartxInt[$key-1]);
			
			// where pour la requete
			$where .= ' AND p.target_rate BETWEEN "'.$val[0].'" AND "'.$val[1].'" ';
			$this->where = $key;
		}
		else $this->where = '';
		
		// type
		if(isset($_POST['type']) && $_POST['type'] != '')
		{
			
			$this->type = $_POST['type'];
			
			if($this->type == 1)
			{
				// tout
				$restriction = '';
			}
			elseif($this->type == 2)
			{
				// favori
				$listProjectFav = $this->favoris->select('id_client = '.$this->clients->id_client);	
				
				// On initialise les variables
				$restriction = '';
				$lesIdProjects = '';
				$i=0;
				
				if($listProjectFav != false)
				{
					// On parcour les project en fav
					foreach($listProjectFav as $f)
					{
						$lesIdProjects .= ($i==0?'':',').'"'.$f['id_project'].'"';
						$i++;
					}
					// On crée la restriction
					$restriction = ' AND p.id_project IN('.$lesIdProjects.')';
				}

			}
			elseif($this->type == 3)
			{
				$restriction = '';
				$lesIdProjects = '';
				$i=0;
				
				$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
				
				$lProjetAvecBids = $this->bids->getProjetAvecBid($this->lenders_accounts->id_lender_account);
				foreach($lProjetAvecBids as $f)
				{
					$lesIdProjects .= ($i==0?'':',').'"'.$f['id_project'].'"';
					$i++;
				}
				$restriction = ' AND p.id_project IN('.$lesIdProjects.')';
			}
			elseif($this->type == 4)
			{
				$restriction = ' AND p.date_fin <> "0000-00-00 00:00:00"';
			}
		}
		else $restriction = '';
		
		// statut
		// where
		// order
		// start
		// nb
		//mail('d.courtier@equinoa.com','test',$_POST['positionStart']);
		$this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay,$where.$restriction.' AND p.status = 0 AND p.display = 0',$ordre,$_POST['positionStart'],10);
		$affichage = "";
		foreach($this->lProjetsFunding as $k => $pf)
		{
			
			$this->projects_status->getLastStatut($pf['id_project']);
			
			$this->companies->get($pf['id_company'],'id_company');
			$this->companies_details->get($pf['id_company'],'id_company');
			
			$inter = $this->dates->intervalDates(date('Y-m-d h:i:s'),$pf['date_retrait_full']); // date fin 21h a chaque fois
			if($inter['mois']>0) $dateRest = $inter['mois'].' '.$this->lng['preteur-projets']['mois'];
			else $dateRest = '';
			
			// dates pour le js
			$mois_jour = $this->dates->formatDate($pf['date_retrait'],'F d');
			$annee = $this->dates->formatDate($pf['date_retrait'],'Y');
			
			// favori
			if($this->favoris->get($this->clients->id_client,'id_project = '.$pf['id_project'].' AND id_client'))
				$favori = 'active';
			else
				$favori = '';
			
			
			$CountEnchere = $this->bids->counter('id_project = '.$pf['id_project']);
			//$avgRate = $this->bids->getAVG($pf['id_project'],'rate');
			
			// moyenne pondéré
			$montantHaut = 0;
			$montantBas = 0;
			// si fundé ou remboursement
			if($this->projects_status->status==60 || $this->projects_status->status>=80)
			{
				foreach($this->loans->select('id_project = '.$pf['id_project']) as $b)
				{
					$montantHaut += ($b['rate']*($b['amount']/100));
					$montantBas += ($b['amount']/100);
				}
			}
			// funding ko
			elseif($this->projects_status->status==70)
			{
				foreach($this->bids->select('id_project = '.$pf['id_project']) as $b)
				{
					$montantHaut += ($b['rate']*($b['amount']/100));
					$montantBas += ($b['amount']/100);
				}	
			}
			// emprun refusé
			elseif($this->projects_status->status==75)
			{
				foreach($this->bids->select('id_project = '.$pf['id_project'].' AND status = 1') as $b)
				{
					$montantHaut += ($b['rate']*($b['amount']/100));
					$montantBas += ($b['amount']/100);
				}	
			}
			else
			{
				foreach($this->bids->select('id_project = '.$pf['id_project'].' AND status = 0') as $b)
				{
					$montantHaut += ($b['rate']*($b['amount']/100));
					$montantBas += ($b['amount']/100);
				}
			}
			if($montantHaut>0 && $montantBas >0)
			$avgRate = ($montantHaut/$montantBas);
			else $avgRate = 0;
			
			$affichage .= "
			<tr class='unProjet' id='project".$pf['id_project']."'>
				<td>";
					if($this->projects_status->status >= 60)
					{
						$dateRest = 'Terminé';
					}
					else
					{
						$tab_date_retrait = explode(' ',$pf['date_retrait_full']);
						$tab_date_retrait = explode(':',$tab_date_retrait[1]);
						$heure_retrait = $tab_date_retrait[0].':'.$tab_date_retrait[1];
						
						$affichage .= "
						<script>
							var cible".$pf['id_project']." = new Date('".$mois_jour.", ".$annee." ".$heure_retrait.":00');
							var letime".$pf['id_project']." = parseInt(cible".$pf['id_project'].".getTime() / 1000, 10);
							setTimeout('decompte(letime".$pf['id_project'].",\"val".$pf['id_project']."\")', 500);
						</script>";
					}
					
					if($pf['photo_projet'] != '')
					{
						$affichage .= "<a class='lien' href='".$this->lurl."/projects/detail/".$pf['slug']."'><img src='".$this->photos->display($pf['photo_projet'],'photos_projets','photo_projet_min')."' alt='".$pf['photo_projet']."' class='thumb'></a>";
					}
									
			$affichage .= "
					<div class='description'>";
					if($_SESSION['page_projet'] == 'projets_fo')
					{
						$affichage .= "<h5><a href='".$this->lurl.'/projects/detail/'.$pf['slug']."'>".$pf['title']."</a></h5>";
					}
					else
					{
						$affichage .= "<h5><a href='".$this->lurl."/projects/detail/".$pf['slug']."'>".$pf['title']."</a></h5>";
					}
						$affichage .= "<h6>".$this->companies->city.($this->companies->zip!=''?', ':'').$this->companies->zip."</h6>
						<p>".$pf['nature_project']."</p>
					</div><!-- /.description -->
				</td>
				<td>
					<a class='lien' href='".$this->lurl."/projects/detail/".$pf['slug']."'>
						<div class='cadreEtoiles'><div class='etoile ".$this->lNotes[$pf['risk']]."'></div></div>
					</a>
				</td>
				<td style='white-space:nowrap;'>
					<a class='lien' href='".$this->lurl."/projects/detail/".$pf['slug']."'>
						".number_format($pf['amount'], 0, ',', ' ')."€
					</a>
				</td>
				<td style='white-space:nowrap;'>
				<a class='lien' href='".$this->lurl."/projects/detail/".$pf['slug']."'>
					".($pf['period']==1000000?$this->lng['preteur-projets']['je-ne-sais-pas']:$pf['period'].' '.$this->lng['preteur-projets']['mois'])."
					</a>
				</td>";
				
				$affichage .= "<td><a class='lien' href='".$this->lurl."/projects/detail/".$pf['slug']."'>";
				if($CountEnchere>0)
				{
					$affichage .= number_format($avgRate, 1, ',', ' ')."%";
				}
				else
				{
					$affichage .= ($pf['target_rate']=='-'?$pf['target_rate']:number_format($pf['target_rate'], 1, ',', ' '))."%";
				}
				$affichage .= "</a></td>";
				
				
				$affichage .= "<td><a class='lien' href='".$this->lurl."/projects/detail/".$pf['slug']."'><strong id='val".$pf['id_project']."'>".$dateRest."</strong></a></td>
				<td>";
				//if($_SESSION['page_projet'] == 'projets_fo')
				//{
					//$affichage .= "<a href=".$this->lurl.'/projects/detail/'.$pf['slug']." class='btn btn-info btn-small'>PRÊTez</a>";
					
					if($this->projects_status->status >= 60)
					{
						$affichage .= "<a href='".$this->lurl."/projects/detail/".$pf['slug']."' class='btn btn-info btn-small multi grise1 btn-grise'>".$this->lng['preteur-projets']['voir-le-projet']."</a>";
					}
					else
					{
						$affichage .= "<a href='".$this->lurl."/projects/detail/".$pf['slug']."' class='btn btn-info btn-small'>".$this->lng['preteur-projets']['pretez']."</a>";
					}
				/*}
				else
				{
					$affichage .= "<a href='".$this->lurl."/projects/detail/".$pf['slug']."' class='btn btn-info btn-small multi'>".$this->lng['preteur-projets']['voir-le-projet']."</a>";
					
				}*/
				if(isset($_SESSION['client']))
				{
				$affichage .= "<a class='fav-btn ".$favori."' id='fav".$pf['id_project']."' onclick=\"favori(".$pf['id_project'].",'fav".$pf['id_project']."',".$this->clients->id_client.",0);\">".$this->lng['preteur-projets']['favori']." <i></i></a>";
				}
				$affichage .= "</td>
			</tr>
			";
		}
		
		$table = array('affichage'=> $affichage,'positionStart' => $this->lProjetsFunding[0]['positionStart']);
		echo json_encode($table);

	}
	
	function _triProject()
	{
		$this->autoFireView = true;
		
		//Recuperation des element de traductions
		$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
		
		// Heure fin periode funding
		$this->settings->get('Heure fin periode funding','type');
		$this->heureFinFunding = $this->settings->value;
		
		// tri par taux
		$this->settings->get('Tri par taux','type');
		$this->triPartx = $this->settings->value;
		$this->triPartx = explode(';',$this->triPartx);
		
		// tri par taux intervalles
		$this->settings->get('Tri par taux intervalles','type');
		$this->triPartxInt = $this->settings->value;
		$this->triPartxInt = explode(';',$this->triPartxInt);
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->projects_status = $this->loadData('projects_status');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->favoris = $this->loadData('favoris');
		$this->bids = $this->loadData('bids');
		$this->loans = $this->loadData('loans');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		
		
		// Recuperation du id et de sa valeur
		if(isset($_POST['val']) && isset($_POST['id']))
		{
			$val = $_POST['val'];
			$id = $_POST['id'];
			
			$_SESSION['tri'][$id] = $val;
		}
		
		// Reset du tri
		if(isset($_POST['rest_val']) && $_POST['rest_val'] == 1)
		{
			unset($_SESSION['tri']);
		}
	
		// Si session on execute
		if(isset($_SESSION['tri']))
		{
			$where = '';
			$this->where = '';
			
			// tri temps
			if(isset($_SESSION['tri']['temps'])) $this->ordreProject = $_SESSION['tri']['temps'];
			else $this->ordreProject = 1;
			
			// tri taux
			if(isset($_SESSION['tri']['taux']))
			{
				
				
				
				$key = $_SESSION['tri']['taux'];
				$val = explode('-',$this->triPartxInt[$key-1]);
				
				// where pour la requete
				$where .= ' AND p.target_rate BETWEEN "'.$val[0].'" AND "'.$val[1].'" ';
				
				// where pour le js
				$this->where = $key;
			}
			
			// tri type
			if(isset($_SESSION['tri']['type']))
			{
				$this->type = $_SESSION['tri']['type'];
				
				// tous les projets
				if($this->type == 1)
				{
					$restriction = '';
				}
				// projets favoris
				elseif($this->type == 2)
				{
					// favori
					$listProjectFav = $this->favoris->select('id_client = '.$this->clients->id_client);	
					
					$restriction = '';
					$lesIdProjects = '';
					$i=0;
					
					if($listProjectFav != false)
					{
						foreach($listProjectFav as $f)
						{
							$lesIdProjects .= ($i==0?'':',').'"'.$f['id_project'].'"';
							$i++;
						}
						$restriction = ' AND p.id_project IN('.$lesIdProjects.')';
					}
					else
					{
						$restriction = ' AND p.id_project IN(0)';
					}
				}
				// les projets avec au moins 1 bid
				elseif($this->type == 3)
				{
					$restriction = '';
					$lesIdProjects = '';
					$i=0;
					
					$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
					
					$lProjetAvecBids = $this->bids->getProjetAvecBid($this->lenders_accounts->id_lender_account);
					foreach($lProjetAvecBids as $f)
					{
						$lesIdProjects .= ($i==0?'':',').'"'.$f['id_project'].'"';
						$i++;
					}
					$restriction = ' AND p.id_project IN('.$lesIdProjects.')';
					
				}
				// les projets terminés
				elseif($this->type == 4)
				{
					$restriction = ' AND p.date_fin <> "0000-00-00 00:00:00"';
				}
			}
			
			$_SESSION['ordreProject'] = $this->ordreProject;
			
			// Liste des projets en funding
			$this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay,$where.$restriction.' AND p.status = 0 AND p.display = 0',$this->tabOrdreProject[$this->ordreProject],0,10);
			
			// Nb projets en funding
			$this->nbProjects = $this->projects->countSelectProjectsByStatus($this->tabProjectDisplay,$where.$restriction.' AND p.status = 0 AND p.display = 0');
			

		}
		else
		{
		
			$this->ordreProject = 1; 
			$this->type = 0;
			
			$_SESSION['ordreProject'] = $this->ordreProject;
			
			$this->where = '';
			
			
			// Liste des projets en funding
			
			
			$this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay,' AND p.status = 0',$this->tabOrdreProject[$this->ordreProject],0,10);
			
			// Nb projets en funding
			$this->nbProjects = $this->projects->countSelectProjectsByStatus($this->tabProjectDisplay.' AND p.status = 0');	
		}
	}
	
	
	function _favori()
	{
		$this->autoFireView = false;
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->companies_details = $this->loadData('companies_details');
		$this->favoris = $this->loadData('favoris');
		
		if(isset($_POST['id_project']) && isset($_POST['id_client']) && $this->clients->get($_POST['id_client'],'id_client') && $this->projects->get($_POST['id_project'],'id_project'))
		{
			// si deja dans favori
			if($this->favoris->get($this->clients->id_client,'id_project = '.$this->projects->id_project.' AND id_client'))
			{
				// on supprime
				$this->favoris->delete($this->clients->id_client,'id_project = '.$this->projects->id_project.' AND id_client');
				echo 'delete';
			}
			// Sinon on ajoute aux favoris
			else
			{
				$this->favoris->id_client = $this->clients->id_client;
				$this->favoris->id_project = $this->projects->id_project;
				$this->favoris->create();
				echo 'create';
			}
			
			// Histo client //
			$serialize = serialize(array('id_client' => $_POST['id_client'],'post' => $_POST,'action' => $val));
			$this->clients_history_actions->histo(8,'favoris',$_POST['id_client'],$serialize);
			////////////////
		}
		else echo 'nok';
		
	}
	
	function _villes()
	{
		$this->autoFireView = false;
		
		// Chargement des datas
		$this->villes = $this->loadData('villes');
		
		// cp
		if(isset($this->params[0]) && $this->params[0] == 'cp')
		{
			if(isset($_GET['term']))
			{
				$getCp = $this->bdd->escape_string($_GET['term']);	
			}
			
			
			if($getCp!='')
			{
				
				$lcp = $this->villes->select('cp LIKE "%'.$getCp.'%"');
				
				$tabCp = array();
				
				
				
				foreach($lcp as $key => $cp)
				{
					if(strlen($cp['cp']) == 4)$lecp = '0'.$cp['cp'];
					else $lecp = $cp['cp'];
					
					$tabCp[$key] = $lecp;
				}
				echo json_encode(array_unique($tabCp));
			}
			else echo '{}';
			
		}
		// villes
		else
		{
		
			if(isset($_GET['term']))
			{
				$ville = $this->bdd->escape_string($_GET['term']);	
			}
			
			if($ville!='')
			{
				$lVilles = $this->villes->select('ville LIKE "%'.$ville.'%"');
				
				$tabVilles = array();
				foreach($lVilles as $key => $v)
				{
					$tabVilles[$key] = $v['ville'];
				}
	
				echo json_encode($tabVilles);
			}
			else echo '{}';
		
		}
	}
	
	
	function _displayAll()
	{
		$this->autoFireView = true;
		
		// Chargement des datas
		$this->bids = $this->loadData('bids');
		$this->projects = $this->loadData('projects');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		//Recuperation des element de traductions
		$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
		
		// On recup le projet
		$this->projects->get($this->bdd->escape_string($_POST['id']),'id_project');
		
		if(isset($_POST['tri']))
		{
			$order = $_POST['tri'];
		}
		else
		{
			$order = 'ordre';
		}
		
		if(isset($_POST['direction']))
		{
			if($_POST['direction'] == 1){
				$direction = 'ASC';
				$this->direction = 2;
			}
			else{
				$direction = 'DESC';
				$this->direction = 1;
			}
		}
		
		if($order == 'rate') $order = 'rate '.$direction.', ordre '.$direction;
		elseif($order == 'amount')$order = 'amount '.$direction.', rate '.$direction.', ordre '.$direction;
		elseif($order == 'status')$order = 'status '.$direction.', rate '.$direction.', ordre '.$direction;
		else $order = 'ordre '.$direction;
		
		
		// Liste des encheres enregistrées
		$this->lEnchere = $this->bids->select('id_project = '.$this->projects->id_project,$order);
		
		$this->CountEnchere = $this->bids->counter('id_project = '.$this->projects->id_project);
		
		$this->avgAmount = $this->bids->getAVG($this->projects->id_project,'amount','0');
		
		$this->avgRate = $this->bids->getAVG($this->projects->id_project,'rate');
		
		// moyenne pondéré
			$montantHaut = 0;
			$tauxBas = 0;
			$montantBas = 0;
			foreach($this->bids->select('id_project = '.$this->projects->id_project.' AND status = 0' ) as $b)
			{
				$montantHaut += ($b['rate']*($b['amount']/100));
				$tauxBas += $b['rate'];
				$montantBas += ($b['amount']/100);
			}
			
			if($montantHaut>0 && $montantBas >0)
			$this->avgRate = ($montantHaut/$montantBas);
			else $this->avgRate = 0;
			
			//$this->avgAmount = ($montantHaut/$tauxBas)*100;
		
		// status enchere
		$this->status = array($this->lng['preteur-projets']['enchere-en-cours'],$this->lng['preteur-projets']['enchere-ok'],$this->lng['preteur-projets']['enchere-ko']);
		
	}
	
	function _loadGraph()
	{
		$this->autoFireView = true;
		
		// Chargement des datas
		$this->transactions = $this->loadData('transactions');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->loans = $this->loadData('loans');
		$this->echeanciers = $this->loadData('echeanciers');
		
		//Recuperation des element de traductions
		$this->lng['preteur-mouvement'] = $this->ln->selectFront('preteur-mouvement',$this->language,$this->App);
		
		if(isset($_POST['year']))
		{
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
			
			$sumVersParMois = $this->transactions->getSumDepotByMonths($this->clients->id_client,$_POST['year']);
			
			$sumPretsParMois = $this->loans->getSumPretsByMonths($this->lenders_accounts->id_lender_account,$_POST['year']);
			
			$sumRembParMois = $this->echeanciers->getSumRembByMonths($this->lenders_accounts->id_lender_account,$_POST['year']);
			
			$sumIntbParMois = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account,$_POST['year']);
			
			$sumRevenuesfiscalesParMois = $this->echeanciers->getSumRevenuesFiscalesByMonths($this->lenders_accounts->id_lender_account,$_POST['year']);
			
			
			for($i=1; $i<=12; $i++)		
			{
				$i = ($i<10?'0'.$i:$i);			
				$this->sumVersParMois[$i] = number_format(($sumVersParMois[$i] != ''?$sumVersParMois[$i]:0),2,'.','');	
				$this->sumPretsParMois[$i] = number_format(($sumPretsParMois[$i] != ''?$sumPretsParMois[$i]:0),2,'.','');
				$this->sumRembParMois[$i] = number_format(($sumRembParMois[$i] != ''?$sumRembParMois[$i]-$sumRevenuesfiscalesParMois[$i]:0),2,'.','');
				$this->sumIntbParMois[$i] = number_format(($sumIntbParMois[$i] != ''?$sumIntbParMois[$i]-$sumRevenuesfiscalesParMois[$i]:0),2,'.','');
			}
		}
	}
	
	
	// profil preteur
	function _changeMdp()
	{
		$this->autoFireView = false;
		
		// Chargement des datas
		$this->clients = $this->loadData('clients');
		
		if(isset($_POST['newMdp']) && isset($_POST['oldMdp']) && isset($_POST['id']) && $this->clients->get($_POST['id'],'id_client'))
		{
			
			// Histo client //
			$serialize = serialize(array('id_client' => $_POST['id'],'newmdp' => md5($_POST['newMdp']),'question' => $_POST['question'],'reponse' => md5($_POST['reponse'])));
			$this->clients_history_actions->histo(7,'change mdp',$_POST['id'],$serialize);
			////////////////
			
			if(md5($_POST['oldMdp']) != $this->clients->password)
			{
				echo 'nok';	
			}
			else
			{
				$this->clients->password = md5($_POST['newMdp']);
				$_SESSION['client']['password'] = $this->clients->password;
				// question / reponse
				if(isset($_POST['question']) && isset($_POST['reponse']) && $_POST['question'] != '' && $_POST['reponse'] != '')
				{
					$this->clients->secrete_question = $_POST['question'];
					$this->clients->secrete_reponse = md5($_POST['reponse']);
				}
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
				
				echo 'ok';
			}
			
		}
	}
	
	function _mdp_lost()
	{
		$this->autoFireView = false;
		
		// Chargement des datas
		$clients = $this->loadData('clients');
		
		
		
		if(isset($_POST['email']) && $this->ficelle->isEmail($_POST['email']) && $clients->get($_POST['email'],'email')){
			

			//mail('d.courtier@equinoa.com','test mdp',$_POST['email'].' - '.$this->clients->id_client);

			/*$mdp = $this->ficelle->generatePassword(8);
			$this->clients->password = md5($mdp);
			$this->clients->update();*/
			
			//*************************//
			//*** ENVOI DU MAIL MDP ***//
			//*************************//

			// Recuperation du modele de mail
			$this->mails_text->get('mot-de-passe-oublie','lang = "'.$this->language.'" AND type');
			
			// Variables du mailing
			$surl = $this->surl;
			$url = $this->lurl;
			$prenom = $clients->prenom;
			$login = $clients->email;
			$link_password = $this->lurl.'/'.$this->tree->getSlug(119,$this->language).'/'.$clients->hash; 
			
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
			'prenom' => $prenom,
			'login' => $login,
			'link_password' => $link_password,
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
			
			echo 'ok';
			
		}
		else
		{
			echo 'nok';	
		}
	}
	
	function _load_finances()
	{
		$this->autoFireView = true;
		
		if(isset($_POST['year']) && isset($_POST['id_lender']))
		{
			
			// Chargement des datas
			$this->lenders_accounts = $this->loadData('lenders_accounts');
			$this->companies = $this->loadData('companies');
			$this->loans = $this->loadData('loans');
			$this->projects = $this->loadData('projects');
			$this->echeanciers = $this->loadData('echeanciers');
			$this->projects_status = $this->loadData('projects_status');
			
			$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
			
			$year = $_POST['year'];
		
			//$this->lLoans = $this->loans->sumPretsByProject($_POST['id_lender'],$year);
			$this->lLoans = $this->loans->select('id_lender = '.$_POST['id_lender'].' AND YEAR(added) = '.$year.' AND status = 0','added DESC');
		}
	}
	
	function _load_transac()
	{
		$this->autoFireView = true;
		
		$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
		
		if(isset($_POST['year']) && isset($_POST['id_client']))
		{
			// Chargement des datas
			$this->clients = $this->loadData('clients');
			$this->transactions = $this->loadData('transactions');
			$this->echeanciers = $this->loadData('echeanciers');
			$this->projects = $this->loadData('projects');
			$this->companies = $this->loadData('companies');
			
			$this->lng['profile'] = $this->ln->selectFront('preteur-profile',$this->language,$this->App);
			
			// Offre de bienvenue motif
			$this->settings->get('Offre de bienvenue motif','type');
			$this->motif_offre_bienvenue = $this->settings->value;
			
			$year = $_POST['year'];
			
			$this->lTrans = $this->transactions->select('type_transaction IN (1,3,4,5,7,8,16,17) AND status = 1 AND etat = 1 AND display = 0 AND id_client = '.$_POST['id_client'].' AND YEAR(date_transaction) = '.$year,'added DESC');
			
			$this->lesStatuts = array(1 => $this->lng['profile']['versement-initial'],3 => $this->lng['profile']['alimentation-cb'],4 => $this->lng['profile']['alimentation-virement'],5 => $this->lng['profile']['remboursement'],7 => $this->lng['profile']['alimentation-prelevement'],8 => $this->lng['profile']['retrait'],16 => $this->motif_offre_bienvenue,17 => 'Retrait offre de bienvenue');
		}
	}
	
	// page alimentation / transferer des fonds (retrait d'argent)
	function _transfert()
	{
		$this->autoFireView = false;
		
		// Chargement des datas
		$this->clients = $this->loadData('clients');
		$this->transactions = $this->loadData('transactions');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->bank_lines = $this->loadData('bank_lines');
		$this->virements = $this->loadData('virements');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_status = $this->loadData('clients_status');
		$this->offres_bienvenues_details = $this->loadData('offres_bienvenues_details');
		
		// On verfie la presence de l'id_client, mdp et montant
		if(isset($_POST['id_client']) && $this->clients->get($_POST['id_client'],'id_client') && isset($_POST['mdp']) && isset($_POST['montant']))
		{
			
			// Histo client //
			$serialize = serialize(array('id_client' => $_POST['id_client'],'montant' => $_POST['montant'],'mdp' => md5($_POST['mdp'])));
			$this->clients_history_actions->histo(3,'retrait argent',$_POST['id_client'],$serialize);
			////////////////
			
			// statut client
			$this->clients_status->getLastStatut($this->clients->id_client);
			
			if($this->clients_status->status < 60){
				echo 'nok'; 
				die;
			}
			
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
			
			$montant = str_replace(',','.',$_POST['montant']);
			
			$verif = 'ok';
			
			// on verifie le mdp
			if(md5($_POST['mdp']) != $this->clients->password)
			{
				$verif = 'noMdp';
			}
			else
			{
				// on verifie si le montant est bien un chiffre
				if(!is_numeric($montant))
				{
					$verif = 'noMontant';
				}
				elseif($this->lenders_accounts->bic == '')
				{
					$verif = 'noBic';
				}
				elseif($this->lenders_accounts->iban == '')
				{
					$verif = 'noIban';
				}
				// Si c'est un chiffre on verifie que le montant est inferieur ou egale au solde du client
				else
				{
					// On recup les offres
					
					//if($_SERVER['REMOTE_ADDR'] == '93.26.42.99'){
					
						$sumOffres = $this->offres_bienvenues_details->sum('id_client = '.$this->clients->id_client.' AND status = 0','montant');
					//}
					//else
					//$sumOffres = 0;
					
					$offre_presente = false;
					if($sumOffres > 0){
							$sumOffres = ($sumOffres/100);
							$offre_presente = true;
					}
					else 				$sumOffres = 0;
					
					if(($montant+$sumOffres) > $this->transactions->getSolde($this->clients->id_client) || $montant <= 0){
						if($offre_presente == true)$verif = 'noMontant3';
						else $verif = 'noMontant2';
					}
				}
					
			}
			
			
			// Si tout est ok
			if($verif == 'ok')
			{
		
				// Motif virement
				$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
				$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
				$this->motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				
				// on effectue une demande de virement
				
				
				// on retire la somme dur les transactions, bank_line et wallet
				
				// transaction
				$this->transactions->id_client = $this->clients->id_client;
				$this->transactions->montant = '-'.($montant*100);
				$this->transactions->id_langue = 'fr';
				$this->transactions->date_transaction = date('Y-m-d H:i:s');
				$this->transactions->status = '1'; // on met en mode reglé pour ne plus avoir la somme sur le compte
				$this->transactions->etat = '1';
				$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
				$this->transactions->civilite_fac = $this->clients->civilite;
				$this->transactions->nom_fac = $this->clients->nom;
				$this->transactions->prenom_fac = $this->clients->prenom;
				if($this->clients->type == 2)$this->transactions->societe_fac = $this->companies->name;
				$this->transactions->adresse1_fac = $this->clients_adresses->adresse1;
				$this->transactions->cp_fac = $this->clients_adresses->cp;
				$this->transactions->ville_fac = $this->clients_adresses->ville;
				$this->transactions->id_pays_fac = $this->clients_adresses->id_pays;
				$this->transactions->type_transaction = 8; // on signal que c'est un retrait
				$this->transactions->transaction = 1; // transaction physique
				$this->transactions->id_transaction = $this->transactions->create();
				
				
				// On enrgistre la transaction dans le wallet
				$this->wallets_lines->id_lender = $this->lenders_accounts->id_lender_account;
				$this->wallets_lines->type_financial_operation = 30; // Inscription preteur
				$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
				$this->wallets_lines->status = 1;
				$this->wallets_lines->type = 1;
				$this->wallets_lines->amount = '-'.($montant*100);
				$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
				
				// Transaction physique donc on enregistre aussi dans la bank lines
				$this->bank_lines->id_wallet_line = $this->wallets_lines->id_wallet_line;
				$this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account; 
				$this->bank_lines->status = 1;
				$this->bank_lines->amount = '-'.($montant*100);
				$this->bank_lines->create();
				
				// on enregistre a la demande de virement
				$this->virements->id_client = $this->clients->id_client;
				$this->virements->id_transaction = $this->transactions->id_transaction;
				$this->virements->montant = $montant*100;
				$this->virements->motif = $this->motif;
				$this->virements->type = 1; // preteur
				$this->virements->status = 0;
				$this->virements->create();
				
				//******************************************//
				//*** ENVOI DU MAIL NOTIFICATION RETRAIT ***//
				//******************************************//
				
				// destinataire
				$this->settings->get('Adresse notification controle fond','type');
				$destinataire = $this->settings->value;
				//$destinataire = 'd.courtier@equinoa.com';
				
				$transac = $this->loadData('transactions');
				$loans = $this->loadData('loans');
				
				// on recup la somme versé a l'inscription si y en a 1
				$transac->get($this->clients->id_client,'type_transaction = 1 AND status = 1 AND etat = 1 AND transaction = 1 AND id_client');
				
				/*$soldeRetrait = $transac->sum('type_transaction = 8 AND status = 1 AND etat = 1 AND id_client = '.$this->clients->id_client,'montant');
				$soldeRetrait = str_replace('-','',$soldeRetrait);
				if($soldeRetrait > 0) $soldeRetrait = ($soldeRetrait/100);
				else $soldeRetrait = 0;*/
				
				
				$soldePrets = $loans->sumPrets($this->lenders_accounts->id_lender_account);
				
				// Recuperation du modele de mail
				$this->mails_text->get('notification-retrait-de-fonds','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				$idPreteur = $this->clients->id_client;
				$nom = utf8_decode($this->clients->nom);
				$prenom = utf8_decode($this->clients->prenom);
				$email = $this->clients->email;
				$dateinscription = date('d/m/Y',strtotime($this->clients->added));
				if($transac->montant != false)
					$montantInscription = number_format($transac->montant/100, 2, ',', ' ');
				else
					$montantInscription = number_format(0, 2, ',', ' ');
				$montantPreteDepuisInscription = number_format($soldePrets, 2, ',', ' ');
				$montantRetirePlateforme = number_format($montant, 2,',', ' ');
				$solde =  number_format($transac->getSolde($this->clients->id_client), 2,',', ' ');

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
				//if($this->Config['env'] == 'prod')
				//$this->email->addRecipient('emmanuel.perezduarte@unilend.fr');
			
				$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
				$this->email->setHTMLBody($texteMail);
				Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
				// fin mail
				
			}
			
			echo $verif;
				
		}
		else
		{
			echo 'nok'; 	
		}
	}
	
	function _solde()
	{
		$this->autoFireView = false;
		
		$this->transactions = $this->loadData('transactions');
		
		if(isset($_POST['id_client']) && $this->clients->id_client == $_POST['id_client'])
		{
		
			// Solde du compte preteur
			$solde = $this->transactions->getSolde($this->clients->id_client);
			echo $solde = number_format($solde, 2, ',', ' ');
		}
		else
		{
			echo 'nok';	
		}
	}
	
	function _dernier_bilan()
	{
		$this->autoFireView = false;
		
		//Recuperation des element de traductions
		$this->lng['etape4'] = $this->ln->selectFront('depot-de-dossier-etape-4',$this->language,$this->App);
		
		
		if(isset($_POST['annee']) && isset($_POST['mois']))
		{
			$retourne = '
			
			<select name="mois" id="mois" class="field-mini custom-select">
				<option value="0">'.$this->lng['etape4']['mois'].'</option>';
				
				foreach($this->dates->tableauMois['fr'] as $k => $mois)
				{
					if($k > 0)
					{
						if(strlen($k)<2) $numMois = '0'.$k;
						else $numMois = $k;
						
						if(date('Y') == $_POST['annee'])
						{
							if($numMois <= date('m'))
							$retourne .= '<option '.($_POST['mois'] == $numMois?'selected':'').' value="'.$numMois.'">'.$mois.'</option>';
						}
						else
						{
							$retourne .= '<option '.($_POST['mois'] == $numMois?'selected':'').' value="'.$numMois.'">'.$mois.'</option>';
						}
						
					}
				}
				
			 $retourne .= '</select>
			 
			 <script>
			 $(".custom-select").c2Selectbox();
			 </script>
			 
			 ';
			
			
			echo $retourne;
		}
	}

	function _load_mensual()
	{
		$this->autoFireView = false;
		
		if(isset($_POST['montant']) && isset($_POST['tx']) && isset($_POST['nb_echeances']))
		{
			
			// Chargement des librairies
			$this->remb = $this->loadLib('remb');
			
			$this->settings->get('Commission remboursement','type');
			$com = $this->settings->value;
			
			// tva (0.196)
			$this->settings->get('TVA','type');
			$tva = $this->settings->value;
			
			$montant = str_replace(' ','',$_POST['montant']);
			$tx = $_POST['tx']/100;
					
			$tabl = $this->remb->echeancier($montant,$_POST['nb_echeances'],$tx,$com,$tva);
				
			$donneesEcheances = $tabl[1];
			$lEcheanciers = $tabl[2];
			
			// mensualité preteur
			echo number_format($lEcheanciers[1]['echeance'], 2, ',', ' ');
			
		}
	}
	
	function _verifEmail()
	{
		$this->autoFireView = false;
		
		$validMail = 'nok';
		if(isset($_POST['email']) && isset($_POST['oldemail']))
		{
			$validMail = 'ok';
			
			if($this->ficelle->isEmail($_POST['email']) == false)
			{
				$validMail = 'nok';
			}
			elseif($this->clients->existEmail($_POST['email']) == false)
			{
				
				// et si l'email n'est pas celle du client
				if($_POST['email'] != $_POST['oldemail'])
				{
					// check si l'adresse mail est deja utilisé
					$validMail = 'nok';
				}
			}
		}
		echo $validMail;
	}
	
	
	function _captcha_login()
	{
		$this->autoFireView = false;
		
		echo $captcha = '<iframe style="margin-top:-16px;margin-left:-5px;" class="captcha_login" width="133" height="33" src="'.$this->surl.'/images/default/securitecode.php"></iframe>';
	}
	
	function _captcha()
	{
		$this->autoFireView = false;
		
		if(isset($_POST['security'])){
			if(strtolower($_POST['security']) != $_SESSION['securecode']){
				echo $captcha = '<iframe width="133" src="'.$this->surl.'/images/default/securitecode.php"></iframe>';
				//echo $_SESSION['securecode'];
			}
			else echo 'nok';
		}
		else echo 'nok';
	}
	function _contact_form()
	{
		$this->autoFireView = false;
		
		// Chargement des librairies
		$this->clients = $this->loadData('clients');
		$this->demande_contact = $this->loadData('demande_contact');
		
				
		if(isset($_POST['name']) && isset($_POST['prenom']) && isset($_POST['email'])){
			
			$form_ok = true;
			
			if(!isset($_POST['name']) || $_POST['name'] == '' || $_POST['name'] == $this->lng['contact']['nom'])
			{
				
				$form_ok = false;
			}
			
			if(!isset($_POST['prenom']) || $_POST['prenom'] == '' || $_POST['prenom'] == $this->lng['contact']['prenom'])
			{
				
				$form_ok = false;
			}
			
			if(!isset($_POST['email']) || $_POST['email'] == '' || $_POST['email'] == $this->lng['contact']['email'])
			{
				
				$form_ok = false;
			}
			elseif(!$this->ficelle->isEmail($_POST['email']))
			{
				
				$form_ok = false;
			}
			if(!isset($_POST['security']) || $_POST['security'] == '' || $_POST['security'] == $this->lng['contact']['captcha'])
			{
				
				$form_ok = false;
			}
			elseif($_SESSION['securecode'] != strtolower($_POST['security']))
			{
				
				$form_ok = false;
			}
			
			if($form_ok == true){
				
				$this->demande_contact->demande = 2;
				$this->demande_contact->nom = $this->ficelle->majNom($_POST['name']);
				$this->demande_contact->prenom = $this->ficelle->majNom($_POST['prenom']);
				$this->demande_contact->email = $_POST['email'];
				$this->demande_contact->telephone = ($this->lng['contact']['telephone']!=$_POST['phone']?$_POST['phone']:'');
				$this->demande_contact->societe = ($this->lng['contact']['societe']!=$_POST['societe']?$_POST['societe']:'');
				$this->demande_contact->message = ($this->lng['contact']['message']!=$_POST['message']?$_POST['message']:'');
				$this->demande_contact->create();
				
				$this->settings->get('Adresse preteur','type');
				$destinataire = $this->settings->value;
				
				//$destinataire = 'd.courtier@equinoa.com';
				
				//*****************************//
				//*** ENVOI DU MAIL CONTACT ***//
				//*****************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('demande-de-contact','lang = "'.$this->language.'" AND type');
				
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
				'email_c' => $this->demande_contact->email,
				'prenom_c' => $this->demande_contact->prenom,
				'nom_c' => $this->demande_contact->nom,
				'objet' => 'Demande preteur',
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
					Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->demande_contact->email,$tabFiler);
					// Injection du mail NMP dans la queue
					$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
				}
				else // non nmp
				{
					$this->email->addRecipient(trim($this->demande_contact->email));
					Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
				}
				
				//***************************************//
				//*** ENVOI DU MAIL CONTACT A UNILEND ***//
				//***************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('notification-demande-de-contact','lang = "'.$this->language.'" AND type');
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->lurl;
				$email = $this->demande_contact->email;
				$nom = ($this->demande_contact->nom);
				$prenom = ($this->demande_contact->prenom);
				$objet = ($objets[$this->demande_contact->demande]);
				
				$this->demande_contact->demande = 2;
				$this->demande_contact->nom = $this->ficelle->majNom($_POST['name']);
				$this->demande_contact->prenom = $this->ficelle->majNom($_POST['prenom']);
				$this->demande_contact->email = $_POST['email'];
				$this->demande_contact->telephone = ($this->lng['contact']['telephone']!=$_POST['phone']?$_POST['phone']:'');
				$this->demande_contact->societe = ($this->lng['contact']['societe']!=$_POST['societe']?$_POST['societe']:'');
				$this->demande_contact->message = ($this->lng['contact']['message']!=$_POST['message']?$_POST['message']:'');
				
				$infos = '<ul>';
				$infos .= '<li>Type demande : Demande preteur</li>';
				$infos .= '<li>Nom : '.utf8_decode($this->demande_contact->nom).'</li>';
				$infos .= '<li>Prenom : '.utf8_decode($this->demande_contact->prenom).'</li>';
				$infos .= '<li>Email : '.utf8_decode($this->demande_contact->email).'</li>';
				$infos .= '<li>telephone : '.utf8_decode($this->demande_contact->telephone).'</li>';
				$infos .= '<li>Societe : '.utf8_decode($this->demande_contact->societe).'</li>';
				$infos .= '<li>Message : '.utf8_decode($this->demande_contact->message).'</li>';
				$infos .= '</ul>';
				
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
				
				echo $captcha = '<iframe width="133" src="'.$this->surl.'/images/default/securitecode.php"></iframe>';
				
			}
			else echo 'nok';
		}else echo 'nok';
		
	}
	
	// on oblige l'utilisateur a mettre un mdp complexe
	function _complexMdp()
	{
		$this->autoFireView = false;
		
		if(isset($_POST['mdp'])){
			if($this->ficelle->password_fo($_POST['mdp'],6))echo 'ok';
			else echo 'nok';
		}
		else echo 'nok';
	}
	
	// on verifie que c'est bien son mdp
	function _controleYourMdp()
	{
		$this->autoFireView = false;
		
		if(isset($_POST['mdp']) && md5($_POST['mdp']) == $this->clients->password){
			echo 'ok';
		}
		else echo 'nok';
	}
	
	// on verifie que c'est bien son mdp
	function _controleAge()
	{
		$this->autoFireView = false;
		if(isset($_POST['d']) && $_POST['d'] != '' && isset($_POST['m']) && $_POST['m'] != '' && isset($_POST['y']) && $_POST['y'] != ''){
			
			$date = $_POST['y'].'-'.$_POST['m'].'-'.$_POST['d'];
			
			if($this->dates->ageplus18($date) == true){
				echo 'ok';
			}
			else echo 'nok';
		}
		else echo 'nok';
		
		die;
	}
	
	function _accept_cgv()
	{
		$this->autoFireView = false;
		
		// Chargement des librairies
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		
		// on checl qu'on a tout
		if($this->clients->checkAccess() && isset($_POST['terms']) && isset($_POST['id_legal_doc'])){
			
			// on verifie qu'on a pas deja en bdd
			if(!$this->acceptations_legal_docs->get($_POST['id_legal_doc'],'id_client = "'.$this->clients->id_client.'" AND id_legal_doc')){
				$this->acceptations_legal_docs->id_legal_doc = $_POST['id_legal_doc'];
				$this->acceptations_legal_docs->id_client = $this->clients->id_client;
				$this->acceptations_legal_docs->create();
				
				unset($_COOKIE['accept_cgv']);
			}	
		}
		
		die;	
	}
	
	function _syntheses_mouvements()
	{
		if($this->clients->checkAccess() && isset($_POST['duree']) && in_array($_POST['duree'],array('mois','trimestres','annees')) || 5 == 5){
			
			//Recuperation des element de traductions
			$this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese',$this->language,$this->App);
			
			// Chargement des datas
			$this->echeanciers = $this->loadData('echeanciers');
			$this->lenders_accounts = $this->loadData('lenders_accounts');
			
			$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
			
			// Revenus mensuel
			$sumRembParMois = $this->echeanciers->getSumRembByMonthsCapital($this->lenders_accounts->id_lender_account,date('Y')); // captial remboursé / mois
			$sumIntbParMois = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account,date('Y')); // intérets brut / mois
			
			$sumRevenuesfiscalesParMois = $this->echeanciers->getSumRevenuesFiscalesByMonths($this->lenders_accounts->id_lender_account,date('Y')); // revenues fiscales / mois
	
			
			for($i=1; $i<=12; $i++){
				$a = $i;
				$a = ($i<10?'0'.$a:$a);			
				$this->sumRembParMois[$i] = number_format(($sumRembParMois[$a] != ''?$sumRembParMois[$a]:0),2,'.',''); // capital remboursé / mois
				$this->sumIntbParMois[$i] = number_format(($sumIntbParMois[$a] != ''?$sumIntbParMois[$a]-$sumRevenuesfiscalesParMois[$a]:0),2,'.',''); // interets net / mois
				$this->sumRevenuesfiscalesParMois[$i] = number_format(($sumRevenuesfiscalesParMois[$a] != ''?$sumRevenuesfiscalesParMois[$a]:0),2,'.',''); // prelevements fiscaux
			}
			
			if($_POST['duree'] == 'mois'){
			
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
				
				if($lesmois[date('m')] == 1){
					$this->ordre[1] = 1;
					$this->ordre[2] = 2;
					$this->ordre[3] = 3;
					$this->ordre[4] = 4;
				}
				elseif($lesmois[date('m')] == 2){
					$this->ordre[1] = 2;
					$this->ordre[2] = 3;
					$this->ordre[3] = 4;
					$this->ordre[4] = 1;
				}
				elseif($lesmois[date('m')] == 3){
					$this->ordre[1] = 3;
					$this->ordre[2] = 4;
					$this->ordre[3] = 1;
					$this->ordre[4] = 2;
				}
				elseif($lesmois[date('m')] == 4){
					$this->ordre[1] = 4;
					$this->ordre[2] = 1;
					$this->ordre[3] = 2;
					$this->ordre[4] = 3;
				}
			}
			elseif($_POST['duree'] == 'trimestres'){
				
				// par trimestres
			
				// remb
				$this->sumRembPartrimestre[1] = $this->sumRembParMois[1]+$this->sumRembParMois[2]+$this->sumRembParMois[3]+$this->sumRembParMois[4];
				$this->sumRembPartrimestre[2] = $this->sumRembParMois[5]+$this->sumRembParMois[6]+$this->sumRembParMois[7]+$this->sumRembParMois[8];
				$this->sumRembPartrimestre[3] = $this->sumRembParMois[9]+$this->sumRembParMois[10]+$this->sumRembParMois[11]+$this->sumRembParMois[12];
				
				// interets
				$this->sumIntPartrimestre[1] = $this->sumIntbParMois[1]+$this->sumIntbParMois[2]+$this->sumIntbParMois[3]+$this->sumIntbParMois[4];
				$this->sumIntPartrimestre[2] = $this->sumIntbParMois[5]+$this->sumIntbParMois[6]+$this->sumIntbParMois[7]+$this->sumIntbParMois[8];
				$this->sumIntPartrimestre[3] = $this->sumIntbParMois[9]+$this->sumIntbParMois[10]+$this->sumIntbParMois[11]+$this->sumIntbParMois[12];
				
				// fiscal
				$this->sumRevenuesfiscalesPartrimestre[1] = $this->sumRevenuesfiscalesParMois[1]+$this->sumRevenuesfiscalesParMois[2]+$this->sumRevenuesfiscalesParMois[3]+$this->sumRevenuesfiscalesParMois[4];
				$this->sumRevenuesfiscalesPartrimestre[2] = $this->sumRevenuesfiscalesParMois[5]+$this->sumRevenuesfiscalesParMois[6]+$this->sumRevenuesfiscalesParMois[7]+$this->sumRevenuesfiscalesParMois[8];
				$this->sumRevenuesfiscalesPartrimestre[3] = $this->sumRevenuesfiscalesParMois[9]+$this->sumRevenuesfiscalesParMois[10]+$this->sumRevenuesfiscalesParMois[11]+$this->sumRevenuesfiscalesParMois[12];
			}
			else{
				
				$this->debut = date('Y')-5;
				$this->fin = date('Y');
	
				// par an
				$this->sumRembParAn = $this->echeanciers->getSumRembByYearCapital($this->lenders_accounts->id_lender_account,$this->debut,$this->fin);
				$this->sumIntParAn = $this->echeanciers->getSumIntByYear($this->lenders_accounts->id_lender_account,$this->debut,$this->fin);
				$this->sumFiscalParAn = $this->echeanciers->getSumRevenuesFiscalesByYear($this->lenders_accounts->id_lender_account,$this->debut,$this->fin);
				
			}
			
		} // fin check condition
		
	}
	
}