<?php

class releve_compteController extends bootstrap
{
	var $Command;
	
	function releve_compteController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// Controle d'acces à la rubrique
		$this->users->checkAccess('edition');
		
		// Activation du menu
		$this->menu_admin = 'edition';
	}
	
	function _default()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->loans = $this->loadData('loans');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->projects = $this->loadData('projects');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->bids = $this->loadData('bids');
		$this->transactions = $this->loadData('transactions');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		
		if(isset($_POST['month']) && isset($_POST['year']) && isset($_POST['id_client']))
		{
			$month = $_POST['month'];
			$year = $_POST['year'];
			$id_client = $_POST['id_client'];
		}
		else
		{
			$month = date('m');
			$year = date('Y');
			$id_client = 1;
		}
		
		$datetemp = mktime(0,0,0,$month-1,1,$year);
		$oldMonth = date('m',$datetemp);
		$oldYear = date('Y',$datetemp);
		
		$this->dayEndMonths = date('t',mktime(0,0,0,$month,1,$year));
		$this->month = $month;
		$this->year = $year;
			
		$this->clients->get($id_client,'id_client');
		$this->clients_adresses->get($id_client,'id_client');
		
		$this->lenders_accounts->get($this->clients->id_client,'id_client_owner');
		
		
		//Argent disponible en fin de période
		$this->argentDispoDebutPeriode = $this->transactions->getSoldePreteur($this->clients->id_client,$oldMonth,$oldYear);
		
		//Argent disponible en fin de période
		$this->argentDispoFinPeriode = $this->transactions->getSoldePreteur($this->clients->id_client,$month,$year);
		// Apport d’argent
		$this->apportArgent = $this->transactions->sumByMonthByPreteur($this->clients->id_client,'1,3,4,7',$month,$year);
		// Retrait d’argent
		$this->retraitArgent = $this->transactions->sumByMonthByPreteur($this->clients->id_client,'8',$month,$year);
		
		// Argent prêté
		$this->agentPrete = $this->loans->sumPretsByMonths($this->lenders_accounts->id_lender_account,$month,$year);
		//$this->agentPrete = $this->bids->sumPretsByMonths($this->lenders_accounts->id_lender_account,$month,$year);
		
		
		// Argent bloqué/débloqué pour offre de prêt
		$this->argentBloque = $this->transactions->sumByMonthByPreteur($this->clients->id_client,'2',$month,$year);
		
		$this->argentBloque = $this->agentPrete + $this->argentBloque;
		
		if($this->agentPrete > 0)$this->agentPrete = '-'.$this->agentPrete;
		
		// Argent remboursé
		//$this->argentRemb = $this->transactions->sumByMonthByPreteur($this->clients->id_client,'5',$month,$year);
		
		$lRemb = $this->transactions->select('MONTH(date_transaction) = '.$month.' AND YEAR(date_transaction) = '.$year.' AND etat = 1 AND status = 1 AND type_transaction = 5 AND id_client = '.$this->clients->id_client);
		
		$this->capital = 0;
		$this->interets = 0;
		$this->retenuesFiscales = 0;
		foreach($lRemb as $r)
		{
			$this->echeanciers->get($r['id_echeancier'],'id_echeancier');
			
			// Capital
			$this->capital += $this->echeanciers->capital;
			
			// Intérêts bruts reçus
			$this->interets += $this->echeanciers->interets;
			
			// Retenues fiscales
			$this->retenuesFiscales += $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;
		}
		
		// on remet les retenuesfiscales dasn le remb
		$this->argentRemb = ($this->capital/100);
		
		$this->retenuesFiscales = '-'.$this->retenuesFiscales;
		$this->interets = ($this->interets/100);
		
		
		$this->lTrans = $this->transactions->select('type_transaction IN (1,2,3,4,5,7,8) AND status = 1 AND etat = 1 AND id_client = '.$this->clients->id_client.' AND LEFT(date_transaction,7) = "'.$year.'-'.$month.'"','added ASC');
			
		
		
		
		
		$this->lPrets = $this->loans->select('id_lender = '.$this->lenders_accounts->id_lender_account.' AND LEFT(added,7) = "'.$year.'-'.$month.'" AND status = "0"');
		
		
		$this->lesTransac = array();
		
		
		foreach($this->lTrans as $k => $t)
		{
			$date = strtotime($t['added']);
			
			$this->lesTransac[$date]['montant'] = $t['montant'];
			$this->lesTransac[$date]['type_transaction'] = $t['type_transaction'];
			$this->lesTransac[$date]['date_transaction'] = $t['date_transaction'];
			$this->lesTransac[$date]['id_bid_remb'] = $t['id_bid_remb'];
			$this->lesTransac[$date]['id_transaction'] = $t['id_transaction'];
			$this->lesTransac[$date]['id_echeancier'] = $t['id_echeancier'];
			
		}
		


		$i = 1;
		foreach($this->lPrets as $k => $t)
		{

			$date = strtotime($t['added']);
			
			if(array_key_exists($date,$this->lesTransac))
			{
				
				$date = strtotime($t['added']).'-'.$i;
				$i++;	
			}
			
			
			
			
			$this->lesTransac[$date]['montant'] = '-'.$t['amount'];
			$this->lesTransac[$date]['type_transaction'] = 'pret';
			$this->lesTransac[$date]['date_transaction'] = $t['added'];
			$this->lesTransac[$date]['id_bid_remb'] = 0;
			$this->lesTransac[$date]['id_transaction'] = 0;
			$this->lesTransac[$date]['id_echeancier'] = 0;
			$this->lesTransac[$date]['id_project'] = $t['id_project'];
			
			$this->lesTransac[$date.'-pret']['montant'] = $t['amount'];
			$this->lesTransac[$date.'-pret']['type_transaction'] = 'bid-pret';
			$this->lesTransac[$date.'-pret']['date_transaction'] = $t['added'];
			$this->lesTransac[$date.'-pret']['id_bid_remb'] = 0;
			$this->lesTransac[$date.'-pret']['id_transaction'] = 0;
			$this->lesTransac[$date.'-pret']['id_echeancier'] = 0;
			$this->lesTransac[$date.'-pret']['id_project'] = $t['id_project'];
			
		}
		ksort($this->lesTransac);
		
		/*echo '<pre>';
		print_r($this->lesTransac);
		echo '</pre>';*/
		/*die;
		echo '<pre>';
		print_r($this->lTrans);
		echo '</pre>';*/
	}
	
	function _emprunteur()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->loans = $this->loadData('loans');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->projects = $this->loadData('projects');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->bids = $this->loadData('bids');
		$this->transactions = $this->loadData('transactions');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		
		if(isset($_POST['month']) && isset($_POST['year']) && isset($_POST['id_client']))
		{
			$month = $_POST['month'];
			$year = $_POST['year'];
			$id_client = $_POST['id_client'];
		}
		else
		{
			$month = date('m');
			$year = date('Y');
			$id_client = 7;
		}
		
		$datetemp = mktime(0,0,0,$month-1,1,$year);
		$oldMonth = date('m',$datetemp);
		$oldYear = date('Y',$datetemp);
		
		$this->dayEndMonths = date('t',mktime(0,0,0,$month,1,$year));
		$this->month = $month;
		$this->year = $year;
		
		// on recup l'emprunteur	
		$this->clients->get($id_client,'id_client');
		$this->clients_adresses->get($id_client,'id_client');	
		
		// l'entreprise
		$this->companies->get($id_client,'id_client_owner');
		
		// les projets
		$this->lCompanies = $this->clients->select('id_company = '.$this->companies->id_company);
		
		print_r($this->lCompanies);
	}
}
