<?php

class ventesController extends bootstrap
{
	var $Command;
	
	function ventesController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// Check de la plateforme
		if($this->cms == 'iZinoa')
		{
			// Renvoi sur la page de gestion de l'arbo
			header('Location:'.$this->lurl.'/tree');
			die;
		}

		// Controle d'acces à la rubrique
		$this->users->checkAccess('ventes');
		
		// Activation du menu
		$this->menu_admin = 'ventes';
	}
	
	function _default()
	{
		// Chargement du data
		$this->transactions = $this->loadData('transactions');
		$this->partenaires = $this->loadData('partenaires');
		$this->partenaires_types = $this->loadData('partenaires_types');
		
		// Recuperation de la liste des partenaire
		$this->lPartenaires = $this->partenaires->select('status = 1','nom ASC');
		
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
			$this->deb_jour = $this->fin_jour = date('d');
			$this->deb_mois = $this->fin_mois = date('m');
			$this->deb_annee = $this->fin_annee = date('Y');
			
			$_POST['du-jour'] = $this->deb_jour;
			$_POST['du-mois'] = $this->deb_mois;
			$_POST['du-annee'] = $this->deb_annee;
			$_POST['au-jour'] = $this->fin_jour;
			$_POST['au-mois'] = $this->fin_mois;
			$_POST['au-annee'] = $this->fin_annee;
			
			$this->mois = $this->deb_mois;
			$this->annee = $this->deb_annee;
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
		
		// Recupearation d'un rapport
		$this->CAcmd = $this->transactions->getCAcommandes($this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);		
		$this->NBcmd = $this->transactions->getNBcommandes($this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);		
		$this->NBadb = $this->transactions->getNBabandons($this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);
	}
}