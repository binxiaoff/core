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
			$_POST['date1'] = "01/08/2014";
			$date1 = "2014-08-01 00:00:00";
		}	
		
		if($_POST['date2'] != '')
		{
			$d2 = explode('/',$_POST['date2']);
			$date2 = $d2[2].'-'.$d2[1].'-'.$d2[0];
		}
		else
		{
			$_POST['date2'] = "31/08/2014";
			$date2 = "2014-08-31 00:00:00";
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
				$date1 = "2014-08-01 00:00:00";
			}	
			
			if($_POST['spy_date2'] != '')
			{
				$d2 = explode('/',$_POST['spy_date2']);
				$date2 = $d2[2].'-'.$d2[1].'-'.$d2[0];
			}
			else
			{
				$date2 = "2014-08-31 00:00:00";
			}
	
			$this->L_clients = $this->clients->select('etape_inscription_preteur > 0 AND status = 1 AND added > "'.$date1.'" AND added < "'.$date2.'"');
		
			
			
			$csv = "id_client;nom;prenom;email;tel;date_inscription;etape_inscription;Source;Source 2\n";
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
				$csv .= utf8_decode($u['id_client']).';'.utf8_decode($u['nom']).';'.utf8_decode($u['prenom']).';'.utf8_decode($u['email']).';'.utf8_decode($u['telephone'].' '.$u['mobile']).';'.utf8_decode($this->dates->formatDate($u['added'],'d/m/Y')).';'.utf8_decode($etape).';'.$u['source'].';'.$u['source2']."\n"; // le \n final entre " " 
			} 
			
			print($csv); 
			exit; 
		}
	}
}