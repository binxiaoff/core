<?php

class unilend_emprunteurController extends bootstrap
{
	var $Command;
	
	function unilend_emprunteurController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
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
			// Etape de transition
			$this->settings->get('Etape de transition','type');
			$id_tree = $this->settings->value;
			$slug = $this->tree->getSlug($id_tree,$this->language);
			
			// check preteur ou emprunteur (ou les deux)
			$this->clients->checkStatusPreEmp($this->clients->status_pre_emp,'emprunteur',$this->clients->status_transition,$slug);
		}
		
		
		$this->page = 'unilend_emprunteur';
		
	}
	
	function _default()
	{
		//Recuperation des element de traductions
		$this->lng['emprunteur-unilend'] = $this->ln->selectFront('emprunteur-unilend',$this->language,$this->App);
		
		$this->clients_mandats = $this->loadData('clients_mandats');
		
		if(isset($_POST['form_send_mandat']))
		{
			$this->upload_mandat = false;
			// carte-nationale-didentite-passeport
			if(isset($_FILES['mandat']) && $_FILES['mandat']['name'] != '')
			{
				
				if($this->clients_mandats->get($this->clients->id_client,'id_client'))$create = false;
				else $create = true;
				
				$this->upload->setUploadDir($this->path,'protected/mandats/');
				if($this->upload->doUpload('mandat'))
				{
					if($this->clients_mandats->name != '')@unlink($this->path.'protected/mandat/'.$this->clients_mandats->name);
					$this->clients_mandats->name = $this->upload->getName();
				}
				
				$this->clients_mandats->id_client = $this->clients->id_client;
				
				if($create == true)$this->clients_mandats->create();
				else $this->clients_mandats->update();
				
				$this->upload_mandat = true;
				
			}
		}
		
		
		if(isset($_POST['send_form_profile']))
		{
			
			// Histo client //
			$serialize = serialize(array('id_client' => $this->clients->id_client,'genre' => $_POST['genre'],'nom' => $_POST['nom'],'prenom' => $_POST['prenom'],'fonction' => $_POST['fonction'],'phone' => $_POST['phone'],'email' => $_POST['email'],'pass' => md5($_POST['pass']),'secret-questionE' => $_POST['secret-questionE'],'secret-responseE' => md5($_POST['secret-responseE'])));
			$this->clients_history_actions->histo(11,'unilend emprunteur',$this->clients->id_client,$serialize);
			////////////////
			
			$this->form_ok = true;
			
			$this->email = $this->clients->email;
			$this->reponse_email = false;
			
			// nom
			if(!isset($_POST['nom']) || $_POST['nom'] == $this->lng['emprunteur-unilend']['nom'] || $_POST['nom'] == '')
			{
				$this->form_ok = false;
			}
			// prenom
			if(!isset($_POST['prenom']) || $_POST['prenom'] == $this->lng['emprunteur-unilend']['prenom'] || $_POST['prenom'] == '')
			{
				$this->form_ok = false;
			}
			// email
			if(!isset($_POST['email']) || $_POST['email'] == $this->lng['emprunteur-unilend']['email'] || $_POST['email'] == '')
			{
				$this->form_ok = false;
			}
			elseif(isset($_POST['email']) && $this->ficelle->isEmail($_POST['email']) == false)
			{
				$this->form_ok = false;
			}
			elseif($_POST['email'] != $_POST['conf_email'])
			{
				$this->form_ok = false;
			}
			elseif($this->clients->existEmail($_POST['email']) == false)
			{
				// et si l'email n'est pas celle du client
				if($_POST['email'] != $this->email)
				{
					// check si l'adresse mail est deja utilisé
					$this->reponse_email = true;
					$this->form_ok = false;
				}
			}
			// fonction
			if(!isset($_POST['fonction']) || $_POST['fonction'] == $this->lng['emprunteur-unilend']['fonction'] || $_POST['fonction'] == '')
			{
				$this->form_ok = false;
			}
			// pass
			if(!isset($_POST['pass']) || $_POST['pass'] == $this->lng['emprunteur-unilend']['pass'] || $_POST['pass'] == '')
			{
				$this->form_ok = false;
			}
			if(!isset($_POST['pass2']) || $_POST['pass2'] == $this->lng['emprunteur-unilend']['pass2'] || $_POST['pass2'] == '')
			{
				$this->form_ok = false;
			}
			if(isset($_POST['pass']) && isset($_POST['pass2']) && $_POST['pass'] != $_POST['pass2'])
			{
				$this->form_ok = false;
			}
			// response
			if(!isset($_POST['secret-responseE']) || $_POST['secret-responseE'] == $this->lng['emprunteur-unilend']['response'] || $_POST['secret-responseE'] == '')
			{
				$this->form_ok = false;
			}
			// secret-questionE
			if(!isset($_POST['secret-questionE']) || $_POST['secret-questionE'] == $this->lng['emprunteur-unilend']['question-secrete'] || $_POST['secret-questionE'] == '')
			{
				$this->form_ok = false;
			}
			
			// si form ok
			if($this->form_ok == true)
			{
				$this->clients->nom = $this->ficelle->majNom($_POST['nom']);
				$this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
				$this->clients->fonction = $_POST['fonction'];
				$this->clients->telephone = $_POST['phone'];
				$this->clients->civilite = $_POST['genre'];
				$this->clients->password = md5($_POST['pass']);
				$this->clients->secrete_question = $_POST['secret-questionE'];
				$this->clients->secrete_reponse = md5($_POST['secret-responseE']);
				$_SESSION['client']['password'] = $this->clients->password;
				$this->clients->update();
			}
			
		}
	}
	
}