<?php
class mailsController extends bootstrap
{
	var $Command;
	
	function mailsController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;

		// Controle d'acces � la rubrique
		$this->users->checkAccess('configuration');
		
		// Activation du menu
		$this->menu_admin = 'configuration';
		
		// Init NMP
		$this->nmpEMV = false;
		
		// SI NMP ACTIF
		/*echo $this->login_api.'<br>';
		echo $this->pwd_api.'<br>';
		echo $this->key_api.'<br>';*/
		
		if($config['env'] == 'prod' && $this->key_api != '' && $this->login_api != '' && $this->pwd_api != '' && $this->serveur_api != '')
		{		
			// Connection au serveur NMP
			$this->location_nmpsoap = 'https://'.$this->serveur_api.'/apitransactional/services/TransactionalService?wsdl';
			$this->nmpsoap = new SoapClient($this->location_nmpsoap);
		
			// Connexion � l'API NMP
			$result = $this->nmpsoap->openApiConnection(array('login'=>$this->login_api,'pwd'=>$this->pwd_api,'key'=>$this->key_api));
		
			// Recuperation du token de session
			$this->token_soap = $result->return;
			
			// Youpi
			$this->nmpEMV = true;
		}
	}
	
	function _default()
	{
		// Suppression d'un mail
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Suppression du template
			$this->mails_text->delete($this->params[1],'type');	
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'un mail';
			$_SESSION['freeow']['message'] = 'Le mail a bien &eacute;t&eacute; supprim&eacute; !';
			
			// Renvoi sur la liste des eamils
			header('Location:'.$this->lurl.'/mails');
			die;
		}
		
		// Recuperation de la liste des emails
		$this->lMails = $this->mails_text->select('lang = "'.$this->language.'"','name ASC');
	}
	
	function _add()
	{
		// Formulaire d'ajout d'un email
		if(isset($_POST['form_add_mail']))
		{
			// Creation de toutes les langues
			foreach($this->lLangues as $key => $lng)
			{
				// Enregistrement
				$this->mails_text->type = $this->bdd->generateSlug($_POST['name_'.$_POST['lng_encours']]);
				$this->mails_text->lang = $key;
				$this->mails_text->name = $_POST['name_'.$key];
				$this->mails_text->mode = $_POST['mode_'.$key];
				$this->mails_text->exp_name = $_POST['exp_name_'.$key];
				$this->mails_text->exp_email = $_POST['exp_email_'.$key];
				$this->mails_text->subject = str_replace('"','\'',$_POST['subject_'.$key]);
				$this->mails_text->content = str_replace('"','\'',$_POST['content_'.$key]);
				$this->mails_text->id_textemail  = $this->mails_text->create();
				
				// Chargement
				$this->mails_text->get($this->mails_text->id_textemail,'id_textemail');
				
				// Si NMP
				if($this->nmpEMV)
				{				
					// Creation du templte NMP
					$nmp = $this->nmpsoap->cloneTemplate(array('token'=>$this->token_soap,'id'=>$this->id_clone_nmp,'newName'=>$this->mails_text->type));
				
					// Recuperation des elements NMP
					$detNMP = $this->nmpsoap->getTemplate(array('token'=>$this->token_soap,'id'=>$nmp->return));
				
					// MAJ des infos du mail NMP en BDD
					
					
					
					$this->mails_text->id_nmp = $detNMP->return->id;
					$this->mails_text->nmp_unique = $detNMP->return->random;
					$this->mails_text->nmp_secure = $detNMP->return->encrypt;
					$this->mails_text->update();
				
					// Creation objet NMP
					$template->id = $this->mails_text->id_nmp;
					$template->description = $this->mails_text->name;
					$template->name = $this->mails_text->type;					
					$template->subject = $this->mails_text->subject;
					$template->from = $this->mails_text->exp_name;
					$template->fromEmail = $this->frommail_api;
					$template->to = '';
					$template->encoding = 'UTF-8';
					$template->body = '[EMV HTMLPART]'.$this->mails_text->content;
					$template->replyTo = $this->mails_text->exp_name;
					$template->type = 'TRANSACTIONAL';
					$template->sent = 1;
					$template->replyToEmail = $this->mails_text->exp_email;
					
					
					
					// MAJ du template NMP				
					$this->nmpsoap->updateTemplateByObj(array('token'=>$this->token_soap,'template'=>$template));
				
					// On Track tous les liens
					//$this->nmpsoap->trackAllTemplateLinks(array('token'=>$this->token_soap,'id'=>$template->id));
				}
			}
			
			// Deconnexion de l'API NMP
			if($this->nmpEMV){ $this->nmpsoap->closeApiConnection(array('token'=>$this->token_soap)); }
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un mail';
			$_SESSION['freeow']['message'] = 'Le mail a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la liste des eamils
			header('Location:'.$this->lurl.'/mails');
			die;
		}
	}
	
	function _edit()
	{
		if(isset($this->params[0]) && $this->params[0] != '')
		{
			// Formulaire de modification d'un mail
			if(isset($_POST['form_mod_mail']))
			{
				foreach($this->lLangues as $key => $lng)
				{
					// MAJ Locale
					$this->mails_text->get($_POST['id_textemail_'.$key],'id_textemail');				
					$this->mails_text->name = $_POST['name_'.$key];
					$this->mails_text->mode = $_POST['mode_'.$key];
					$this->mails_text->exp_name = $_POST['exp_name_'.$key];
					$this->mails_text->exp_email = $_POST['exp_email_'.$key];
					$this->mails_text->subject = str_replace('"','\'',$_POST['subject_'.$key]);
					$this->mails_text->content = str_replace('"','\'',$_POST['content_'.$key]);
					$this->mails_text->update();
					
					// Chargement
					$this->mails_text->get($_POST['id_textemail_'.$key],'id_textemail');
					

					// Si NMP
					if($this->nmpEMV)
					{
						// Si NMP n'est pas cr��
						if($this->mails_text->id_nmp == '' && $this->mails_text->nmp_unique == '' && $this->mails_text->nmp_secure == '')
						{
							// Creation du templte NMP
							$nmp = $this->nmpsoap->cloneTemplate(array('token'=>$this->token_soap,'id'=>$this->id_clone_nmp,'newName'=>$this->mails_text->type));
						
							// Recuperation des elements NMP
							$detNMP = $this->nmpsoap->getTemplate(array('token'=>$this->token_soap,'id'=>$nmp->return));
						
							// MAJ des infos du mail NMP en BDD
							
							$this->mails_text->id_nmp = $detNMP->return->id;
							$this->mails_text->nmp_unique = $detNMP->return->random;
							$this->mails_text->nmp_secure = $detNMP->return->encrypt;
							$this->mails_text->update();
						}
											
						// Creation objet NMP
						$template->id = $this->mails_text->id_nmp;
						$template->description = $this->mails_text->name;
						$template->name = $this->mails_text->type;					
						$template->subject = $this->mails_text->subject;
						$template->from = $this->mails_text->exp_name;
						$template->fromEmail = $this->frommail_api;
						$template->to = '';
						$template->encoding = 'UTF-8';
						$template->body = '[EMV HTMLPART]'.$this->mails_text->content;
						$template->replyTo = $this->mails_text->exp_name;
						$template->type = 'TRANSACTIONAL';
						$template->sent = 1;
						$template->replyToEmail = $this->mails_text->exp_email;
						
						
					
						//print_r(array('token'=>$this->token_soap,'template'=>$template));
						
						// MAJ du template NMP				
						$this->nmpsoap->updateTemplateByObj(array('token'=>$this->token_soap,'template'=>$template));		
						
						
						// On Track tous les liens
						//$this->nmpsoap->trackAllTemplateLinks(array('token'=>$this->token_soap,'id'=>$template->id));
					}
				}
				
				// Deconnexion de l'API NMP
				if($this->nmpEMV){ $this->nmpsoap->closeApiConnection(array('token'=>$this->token_soap)); }
				
				// Mise en session du message
				$_SESSION['freeow']['title'] = 'Modification d\'un mail';
				$_SESSION['freeow']['message'] = 'Le mail a bien &eacute;t&eacute; modifi&eacute; !';
				
				// Renvoi sur la liste des mails
				header('Location:'.$this->url.'/mails');
				die;
			}
		}
		else
		{
			// Renvoi sur la liste des mails
			header('Location:'.$this->lurl.'/mails');
			die;
		}
	}
	
	function _purge()
	{
		// On purge les traductions en cours
		//$this->mails_filer->purge();
		
		// Mise en session du message
		$_SESSION['freeow']['title'] = 'Purge des mails';
		$_SESSION['freeow']['message'] = 'Tous les mails ont &eacute;t&eacute; supprim&eacute;s !';
		
		// Renvoi sur la liste des eamils
		header('Location:'.$this->lurl.'/mails/logs');
		die;
	}
	
	function _logs()
	{
		if(isset($_POST['form_send_search']))
		{
			$where .= ' AND `from` LIKE "%'.$_POST['from'].'%" AND `to` LIKE "%'.$_POST['to'].'%" OR `email_nmp` LIKE "%'.$_POST['to'].'%" AND subject LIKE "%'.$_POST['subject'].'%"';
			
			if(isset($_POST['date_from']) && $_POST['date_from'] != '')
			{
				$where .= ' AND added > "'.$this->dates->formatDate($_POST['date_from'],'Y-m-d').' 00:00:00"';
			}
			
			if(isset($_POST['date_to']) && $_POST['date_to'] != '')
			{
				$where .= ' AND added < "'.$this->dates->formatDate($_POST['date_to'],'Y-m-d').' 23:59:59"';
			}
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Recherche d\'un mail';
			$_SESSION['freeow']['message'] = 'La recherche a bien &eacute;t&eacute; execut&eacute;e !';
			
			$start = '';
			$limit = '';
		}
		else
		{
			$where = '';
			$start = '0';
			$limit = '200'; // on recup les 200 derniers
			
		}
		
		
		
		// Recuperation de la liste des emails
		$this->lMails = $this->mails_filer->select('1=1'.$where,'added DESC',$start,$limit);
		$this->nbMails = $this->mails_filer->counter('1=1'.$where);
		$this->nbMails = count($this->lMails);
	}
	
	function _recherche()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->lurl;
	}
	
	function _logsdetails()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->lurl;
		
		// Recuperation des infos du mail
		$this->mails_filer->get($this->params[0],'id_filermails');
	}
	
	function _logsdisplay()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->lurl;
		
		// Recuperation des infos du mail
		$this->mails_filer->get($this->params[0],'id_filermails');
		
		echo stripslashes($this->mails_filer->content);
		die;
	}
}