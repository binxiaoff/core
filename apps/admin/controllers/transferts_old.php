<?php
class transfertsController extends bootstrap
{
	var $Command;
	
	function transfertsController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;	
		
		// Controle d'acces à la rubrique
		$this->users->checkAccess('transferts');
		
		// Activation du menu
		$this->menu_admin = 'transferts';
				
	}
	
	function _default()
	{
		$this->receptions = $this->loadData('receptions');
		
		// virements
		$this->lvirements = $this->receptions->select('type = 2 AND status_virement = 1','id_reception DESC');
		
		// Status Virement
		$this->statusVirement = array(1 => 'Recu',2 => 'Emis', 3 => 'Rejeté');
		
		// Status Prelevement
		$this->statusPrelevement = array(1 => 'Recu',2 => 'Emis', 3 => 'Rejeté');
		
	}
	
	function _prelevements()
	{
		$this->receptions = $this->loadData('receptions');
		
		// virements
		$this->lprelevements = $this->receptions->select('type = 1 AND status_prelevement = 2','id_reception DESC');
		
		// Status Prelevement
		$this->statusPrelevement = array(1 => 'Recu',2 => 'Emis', 3 => 'Rejeté');
		
	}
	
	function _attribution()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		
		$this->receptions = $this->loadData('receptions');
		
		$this->receptions->get($this->params[0],'id_reception');
		
		if($this->receptions->id_client != 0)
		{
			header('location:'.$this->lurl.'/transferts');
			die;
		}
		
	}
	
	function _attribution_project()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->receptions = $this->loadData('receptions');
		
		$this->receptions->get($this->params[0],'id_reception');
		
		if($this->receptions->id_client != 0)
		{
			header('location:'.$this->lurl.'/transferts/prelevements');
			die;
		}
		
	}
	
}
