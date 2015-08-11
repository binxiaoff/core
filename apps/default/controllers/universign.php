<?php

class universignController extends bootstrap
{
	var $Command;
	
	function universignController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;	
		
		//$this->uni_url = "https://t.raymond@equinoa.com:g5rtohav@ws.universign.eu/sign/rpc/";
		$this->uni_url = "https://t.raymond@equinoa.com:GZ1I7ZQN@ws.universign.eu/sign/rpc/";
		
		
	}
	
	function _default()
	{
		// chargement des datas
		$clients = $this->loadData('clients');
		$clients_mandats = $this->loadData('clients_mandats');
		$companies = $this->loadData('companies');
		$projects_pouvoir = $this->loadData('projects_pouvoir');
		$projects = $this->loadData('projects');
		
		
		// Retour pdf Mandat
		if(isset($this->params[2])&&  isset($this->params[1]) && $this->params[1] == 'mandat' && $clients_mandats->get($this->params[2],'id_mandat') && $clients_mandats->status == 0)
		{	
			
			if($this->params[0] == 'success')
			{
				//echo 'success';
							
				///////////////////////////
				//// UNIVERSIGN REPONSE ///
				///////////////////////////
				
				include($this->path.'protected/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
				
				//used variables
				$uni_url = $this->uni_url;
				//$uni_url = "https://login:password@ws.universign.eu/sign/rpc/"; // address of the universign server with basic authentication
				$uni_id = $clients_mandats->id_universign; // a collection id
				
				//create the request
				$c = new xmlrpc_client($uni_url);
				$f = new xmlrpcmsg('requester.getDocumentsByTransactionId', array(new xmlrpcval($uni_id, "string")));
				
				//Send request and analyse response
				$r = &$c->send($f);
				
				if (!$r->faultCode()) {
				   //if the request succeeded
				   $doc['name'] = $r->value()->arrayMem(0)->structMem('name')->scalarVal();
				   $doc['content'] = $r->value()->arrayMem(0)->structMem('content')->scalarVal(); 
				   
				   // On met a jour le pdf en bdd
					file_put_contents($doc['name'],$doc['content']);
					$clients_mandats->status = 1;
					$clients_mandats->update();
                                        
                                        
                                        // on ajoute une ligne d'historique au BO (emprunteurs/edit/ID_CLIENT)
                                        $clients_histo = $this->loadData('clients');
                                        $clients_histo->get($clients_mandats->id_client,'id_client');
                                        
                                        // recup du RIB
                                        // Companies
                                        $this->companies->get($clients_histo->id_client,'id_client_owner');
                                        
                                        $ligne_historique = "<tr><td><b>Signature du mandat</b> pour l’IBAN ".$this->companies->iban." / ".$this->companies->bic." le ".date('d/m/Y')." &agrave; ".date('H:i')."</td></tr>";                                        
                                        
                                        $clients_histo->history = $ligne_historique;
                                        $clients_histo->update();
                                        
                                        
					// redirection sur page confirmation : mandat signé
					
					
					// on verif si on a le mandat de déjà signé
					if($projects_pouvoir->get($clients_mandats->id_project,'id_project') && $projects_pouvoir->status == 1)
					{
					
						// mail notifiaction admin
						
						// Adresse notifications
						$this->settings->get('Adresse notification pouvoir mandat signe','type');
						$destinaire = $this->settings->value;
						
						// on recup le projet
						$projects->get($projects_pouvoir->id_project,'id_project');
						// on recup la companie
						$companies->get($projects->id_company,'id_company');
						// on recup l'emprunteur
						$clients->get($companies->id_client_owner,'id_client');
						
						$lien_pdf_pouvoir = $this->lurl.$projects_pouvoir->url_pdf;
						$lien_pdf_mandat = $this->lurl.$clients_mandats->url_pdf;
						
						
						// Recuperation du modele de mail
						$this->mails_text->get('notification-pouvoir-mandat-signe','lang = "'.$this->language.'" AND type');
						
						// Variables du mailing
						$surl = $this->surl;
						$url = $this->lurl;
						$id_projet = $projects->id_project;
						$nomProjet = utf8_decode($projects->title_bo);
						$nomCompany = utf8_decode($companies->name);
						$lien_pouvoir = $lien_pdf_pouvoir;
						$lien_mandat = $lien_pdf_mandat;
						
						// Attribution des données aux variables
						$sujetMail = htmlentities($this->mails_text->subject);
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
						$this->email->addRecipient(trim($destinaire));
						//$this->email->addBCCRecipient('');
					
						$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
						$this->email->setHTMLBody(utf8_decode($texteMail));
						Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
						// fin mail
						
						
						// fin mail //
					}
					
					
				} else {
				   //displays the error code and the fault message
				   
				   mail('d.courtier@equinoa.com','unilend erreur universign reception','id mandat : '.$clients_mandats->id_mandat.' | An error occurred: Code: ' . $r->faultCode(). ' Reason: "' . $r->faultString()); 
				   // 73027 Reason: 'documents not signed.cancel
				   
				}
				///////////////////////////
				//// UNIVERSIGN REPONSE ///
				///////////////////////////
				
				
			}
			elseif($this->params[0] == 'fail')
			{
				//echo 'fail';
				$clients_mandats->status = 3;
				$clients_mandats->update();
				
				// redirection sur page confirmation : une erreur est parvenue essayez plus tard
			}
			elseif($this->params[0] == 'cancel')
			{
				//echo 'cancel';
				$clients_mandats->status = 2;
				$clients_mandats->update();
				
				// redirection sur page confirmation : vous avez annulé voulez vous signer votre mandat ?
			}
			
			header("location:".$this->lurl.'/universign/confirmation/mandat/'.$clients_mandats->id_mandat);
			die;
		}
		//
		
		// Retour pouvoir
		elseif(isset($this->params[2]) && isset($this->params[1]) && $this->params[1] == 'pouvoir' && $projects_pouvoir->get($this->params[2],'id_pouvoir') && $projects_pouvoir->status == 0)
		{	
			
			if($this->params[0] == 'success')
			{
				//echo 'success pouvoir';
							
				///////////////////////////
				//// UNIVERSIGN REPONSE ///
				///////////////////////////
				
				include($this->path.'protected/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
				
				//used variables
				$uni_url = $this->uni_url;
				//$uni_url = "https://login:password@ws.universign.eu/sign/rpc/"; // address of the universign server with basic authentication
				$uni_id = $projects_pouvoir->id_universign; // a collection id
				
				//create the request
				$c = new xmlrpc_client($uni_url);
				$f = new xmlrpcmsg('requester.getDocumentsByTransactionId', array(new xmlrpcval($uni_id, "string")));
				
				//Send request and analyse response
				$r = &$c->send($f);
				
				if (!$r->faultCode()) {
				   //if the request succeeded
				   $doc['name'] = $r->value()->arrayMem(0)->structMem('name')->scalarVal();
				   $doc['content'] = $r->value()->arrayMem(0)->structMem('content')->scalarVal(); 
				   
				   // On met a jour le pdf en bdd
					file_put_contents($doc['name'],$doc['content']);
					$projects_pouvoir->status = 1;
					$projects_pouvoir->update();
					// redirection sur page confirmation : pouvoir signé 
					
					
					// on verif si on a le mandat de déjà signé
					if($clients_mandats->get($projects_pouvoir->id_project,'id_project') && $clients_mandats->status == 1)
					{
					  
						// mail notifiaction admin
						
						// Adresse notifications
						$this->settings->get('Adresse notification pouvoir mandat signe','type');
						$destinaire = $this->settings->value;
						
						// on recup le projet
						$projects->get($projects_pouvoir->id_project,'id_project');
						// on recup la companie
						$companies->get($projects->id_company,'id_company');
						// on recup l'emprunteur
						$clients->get($companies->id_client_owner,'id_client');
						
						$lien_pdf_pouvoir = $this->lurl.$projects_pouvoir->url_pdf;
						$lien_pdf_mandat = $this->lurl.$clients_mandats->url_pdf;
						
						
						// Recuperation du modele de mail
						$this->mails_text->get('notification-pouvoir-mandat-signe','lang = "'.$this->language.'" AND type');
						
						// Variables du mailing
						$surl = $this->surl;
						$url = $this->lurl;
						$id_projet = $projects->id_project;
						$nomProjet = utf8_decode($projects->title_bo);
						$nomCompany = utf8_decode($companies->name);
						$lien_pouvoir = $lien_pdf_pouvoir;
						$lien_mandat = $lien_pdf_mandat;
						
						// Attribution des données aux variables
						$sujetMail = htmlentities($this->mails_text->subject);
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
						$this->email->addRecipient(trim($destinaire));
						//$this->email->addRecipient(trim('d.courtier@equinoa.com'));
					
						$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
						$this->email->setHTMLBody(utf8_decode($texteMail));
						Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
						// fin mail
						
						
						// fin mail //
						
						 //mail('unilend@equinoa.fr','unilend pouvoir signé','id projet : '.$projects->id_project); 
						
					}
				   
				} else {
				   //displays the error code and the fault message
				   
				  // mail('d.courtier@equinoa.com','unilend erreur universign reception','id pouvoir : '.$projects_pouvoir->id_pouvoir.' | An error occurred: Code: ' . $r->faultCode(). ' Reason: "' . $r->faultString()); 
				   // 73027 Reason: 'documents not signed.cancel
				   
				}
				///////////////////////////
				//// UNIVERSIGN REPONSE ///
				///////////////////////////
				
				
			}
			elseif($this->params[0] == 'fail')
			{
				//echo 'fail pouvoir';
				$projects_pouvoir->status = 3;
				$projects_pouvoir->update();
				
				// redirection sur page confirmation : une erreur est parvenue essayez plus tard
			}
			elseif($this->params[0] == 'cancel')
			{
				//echo 'cancel pouvoir';
				$projects_pouvoir->status = 2;
				$projects_pouvoir->update();
				
				// redirection sur page confirmation : vous avez annulé voulez vous signer votre mandat ?
			}
			
			header("location:".$this->lurl.'/universign/confirmation/pouvoir/'.$projects_pouvoir->id_pouvoir);
			die;
		} 
		else
		{
			header("location:".$this->lurl);
			die;
		}
	}
	
	function _mandat()
	{
		
		// chargement des datas
		$clients = $this->loadData('clients');
		$clients_mandats = $this->loadData('clients_mandats');
		
		if($clients_mandats->get($this->params[0],'id_mandat') && $clients_mandats->status != 1)
		{
			if($clients_mandats->url_universign != '' && $clients_mandats->status == 0)
			{
				
				header("location:".$clients_mandats->url_universign);
				die;
			}
			else
			{
			
				$clients->get($clients_mandats->id_client,'id_client');
				
				include($this->path.'protected/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
				
				//used variables
				
				$uni_url = $this->uni_url; // address of the universign server with basic authentication
				$firstname = utf8_decode($clients->prenom); // the signatory first name
				$lastname = utf8_decode($clients->nom); // the signatory last name
				$phoneNumber = str_replace(' ','',$clients->telephone); // the signatory mobile phone number
				$email = $clients->email; // the signatory mobile phone number
				$doc_name = $this->path.'protected/pdf/mandat/'.$clients_mandats->name; // the name of the PDF document to sign
				$doc_content = file_get_contents($doc_name); // the binary content of the PDF file
				$returnPage = array (
				  "success" => $this->lurl."/universign/success/mandat/".$clients_mandats->id_mandat,
				  "fail" => $this->lurl."/universign/fail/mandat/".$clients_mandats->id_mandat,
				  "cancel" => $this->lurl."/universign/cancel/mandat/".$clients_mandats->id_mandat
				);
				
				// positionnement signature
				$page = 1;
				$x = 255;
				$y = 314;
				
				//create the request
				$c = new xmlrpc_client($uni_url);
				
				$docSignatureField = array(
					"page" => new xmlrpcval($page, "int"),
					"x" => new xmlrpcval($x, "int"),
					"y" => new xmlrpcval($y, "int"),
					"signerIndex" => new xmlrpcval(0, "int"),
					"label" => new xmlrpcval("Unilend", "string")
				);
				
				$signer = array(
				  "firstname" => new xmlrpcval($firstname, "string"),
				  "lastname" => new xmlrpcval($lastname, "string"),
				  "phoneNum"=> new xmlrpcval($phoneNumber, "string"),
				  "emailAddress"=> new xmlrpcval($email, "string")
				);
				
				$doc = array(
				  "content" => new xmlrpcval($doc_content, "base64"),
				  "name" => new xmlrpcval($doc_name, "string"),
				  "signatureFields"=> new xmlrpcval(array(new xmlrpcval($docSignatureField, "struct")), "array")
				);
				 
				$language = "fr";
				
				$signers = array(new xmlrpcval($signer, "struct"));
				
				$request = array(
				  "documents" => new xmlrpcval(array(new xmlrpcval($doc, "struct")), "array"),
				  "signers" => new xmlrpcval($signers, "array"),
				  // the return urls
				  "successURL" =>  new xmlrpcval($returnPage["success"], "string"),
				  "failURL" =>  new xmlrpcval($returnPage["fail"], "string"),
				  "cancelURL" =>  new xmlrpcval($returnPage["cancel"], "string"),
				  //the types of accepted certificate : timestamp for simple signature
				  "certificateTypes" =>  new xmlrpcval(array(new xmlrpcval("timestamp", "string")), "array"),
				  "language" => new xmlrpcval($language, "string"),
				  //The OTP will be sent by Email
				  "identificationType" => new xmlrpcval("sms", "string"),
				  "description" => new xmlrpcval("Mandat id : ".$clients_mandats->id_mandat, "string")
				);
				
				
				$f = new xmlrpcmsg('requester.requestTransaction', array(new xmlrpcval($request, "struct")));
				
				
				//send request and stores response values
				$r = &$c->send($f);
				

				if (!$r->faultCode()) {
					
					//if the request succeeded
					$url = $r->value()->structMem('url')->scalarVal(); //you should redirect the signatory to this url
					$id = $r->value()->structMem('id')->scalarVal(); //you should store this id
					
					$clients_mandats->id_universign = $id;
					$clients_mandats->url_universign = $url;
					$clients_mandats->status = 0;
					$clients_mandats->update();
					
					header("location:".$url);
					die;
				   
				} else {
				   //displays the error code and the fault message
				   //print "An error occurred: ";
				   //print "Code: " . $r->faultCode(). " Reason: '" . $r->faultString();
				   
				    mail('d.courtier@equinoa.com','unilend erreur universign reception',' creatioon mandat id mandat : '.$clients_mandats->id_mandat.' | An error occurred: Code: ' . $r->faultCode(). ' Reason: "' . $r->faultString()); 
				}
			}
		}
			
	}
	
	
	function _pouvoir()
	{
		
		// chargement des datas
		$companies = $this->loadData('companies');
		$projects_pouvoir = $this->loadData('projects_pouvoir');
		$clients = $this->loadData('clients');
		$projects = $this->loadData('projects');
		
		
		// on check les id et si le pdf n'est pas deja signé
		if($projects_pouvoir->get($this->params[0],'id_pouvoir') && $projects_pouvoir->status != 1)
		{
			
			// on check si deja existant en bdd avec l'url universign et si encore en cours
			//if($projects_pouvoir->url_universign != '' && $projects_pouvoir->status == 0)
			if(isset($this->params[1]) && $this->params[1] == 'NoUpdateUniversign' && $projects_pouvoir->url_universign != '' && $projects_pouvoir->status == 0) // si le meme jour alors on regenere pas le pdf universign
			{
				//echo 'on crée pas';
				//die;
				
				header("location:".$projects_pouvoir->url_universign);
				die;
			}
			// Sinon on crée 
			else
			{
				
				//echo 'on crée';
				//die;
				// on recup le projet
				$projects->get($projects_pouvoir->id_project,'id_project');
				// on recup la companie
				$companies->get($projects->id_company,'id_company');
				// on recup l'emprunteur
				$clients->get($companies->id_client_owner,'id_client');
				
				include($this->path.'protected/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
				

				//used variables
				$uni_url = $this->uni_url; // address of the universign server with basic authentication
				$firstname = utf8_decode($clients->prenom); // the signatory first name
				$lastname = utf8_decode($clients->nom); // the signatory last name
				$organization = $companies->name;
				$phoneNumber = str_replace(' ','',$clients->telephone); // the signatory mobile phone number
				$email = $clients->email; // the signatory mobile phone number
				$doc_name = $this->path.'protected/pdf/pouvoir/'.$projects_pouvoir->name; // the name of the PDF document to sign
				$doc_content = file_get_contents($doc_name); // the binary content of the PDF file
				$returnPage = array (
				  "success" => $this->lurl."/universign/success/pouvoir/".$projects_pouvoir->id_pouvoir,
				  "fail" => $this->lurl."/universign/fail/pouvoir/".$projects_pouvoir->id_pouvoir,
				  "cancel" => $this->lurl."/universign/cancel/pouvoir/".$projects_pouvoir->id_pouvoir
				);
				// positionnement signature
				$page = 1;
				$x = 335;
				$y = 350;
				
				
				//create the request
				$c = new xmlrpc_client($uni_url);
				
				$docSignatureField = array(
					"page" => new xmlrpcval($page, "int"),
					"x" => new xmlrpcval($x, "int"),
					"y" => new xmlrpcval($y, "int"),
					"signerIndex" => new xmlrpcval(0, "int"),
					"label" => new xmlrpcval("Unilend", "string")
				);
				
				
				$signer = array(
				  "firstname" => new xmlrpcval($firstname, "string"),
				  "lastname" => new xmlrpcval($lastname, "string"),
				  "organization" => new xmlrpcval($organization, "string"),
				  "phoneNum"=> new xmlrpcval($phoneNumber, "string"),
				  "emailAddress"=> new xmlrpcval($email, "string")
				);
				
				$doc = array(
				  "content" => new xmlrpcval($doc_content, "base64"),
				  "name" => new xmlrpcval($doc_name, "string"),
				  "signatureFields"=> new xmlrpcval(array(new xmlrpcval($docSignatureField, "struct")), "array")
				);
				 
				$language = "fr";
				
				$signers = array(new xmlrpcval($signer, "struct"));
				
				$request = array(
				  "documents" => new xmlrpcval(array(new xmlrpcval($doc, "struct")), "array"),
				  "signers" => new xmlrpcval($signers, "array"),
				  // the return urls
				  "successURL" =>  new xmlrpcval($returnPage["success"], "string"),
				  "failURL" =>  new xmlrpcval($returnPage["fail"], "string"),
				  "cancelURL" =>  new xmlrpcval($returnPage["cancel"], "string"),
				  //the types of accepted certificate : timestamp for simple signature
				  "certificateTypes" =>  new xmlrpcval(array(new xmlrpcval("timestamp", "string")), "array"),
				  "language" => new xmlrpcval($language, "string"),
				  //The OTP will be sent by Email
				  "identificationType" => new xmlrpcval("sms", "string"),
				  "description" => new xmlrpcval("Pouvoir id : ".$projects_pouvoir->id_pouvoir, "string"),
				);
				
				
				$f = new xmlrpcmsg('requester.requestTransaction', array(new xmlrpcval($request, "struct")));
				
				
				//send request and stores response values
				$r = &$c->send($f);
				
				/*echo '<pre>';
				print_r($r);
				echo '</pre>';
				die;*/
				if (!$r->faultCode()) {
					//if the request succeeded
					$url = $r->value()->structMem('url')->scalarVal(); //you should redirect the signatory to this url
					$id = $r->value()->structMem('id')->scalarVal(); //you should store this id
					
					$projects_pouvoir->id_universign = $id;
					$projects_pouvoir->url_universign = $url;
					$projects_pouvoir->status = 0;
					$projects_pouvoir->update();
					
					header("location:".$url);
					die;
				   
				} else {
					
				   //displays the error code and the fault message
				   //print "An error occurred: ";
				   //print "Code: " . $r->faultCode(). " Reason: '" . $r->faultString();
				    mail('d.courtier@equinoa.com','unilend erreur universign reception','id mandat : '.$projects_pouvoir->id_pouvoir.' | An error occurred: Code: ' . $r->faultCode(). ' Reason: "' . $r->faultString()); 
				}
			}
		}
		else
		{
			echo 'error';	
		}
			
	}
	
	function _confirmation()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = true;
		$this->autoFireHead = true;
		$this->autoFireFooter = true;
		
		
		// chargement des datas
		$clients = $this->loadData('clients');
		$clients_mandats = $this->loadData('clients_mandats');
		$companies = $this->loadData('companies');
		$projects_pouvoir = $this->loadData('projects_pouvoir');
		$projects = $this->loadData('projects');
		
		// Si on a 2 parmas
		if(isset($this->params[0]) && isset($this->params[1]))
		{
			// si on a le mandat
			if($this->params[0] == 'mandat' && $clients_mandats->get($this->params[1],'id_mandat'))
			{
				$clients->get($clients_mandats->id_client,'id_client');
				
				$this->lien_pdf = $this->lurl.$clients_mandats->url_pdf;
				
				// si mandat ok
				if($clients_mandats->status == 1)
				{
					$this->titre = 'Confirmation mandat';
					$this->message = 'Votre mandat a bien été signé';
				}
				// mandat annulé
				elseif($clients_mandats->status == 2)
				{
					$this->titre = 'Confirmation mandat';
					$this->message = 'Votre mandat a bien été annulé vous pouvez le signer plus tard.';
				}
				// mandat fail
				elseif($clients_mandats->status == 3)
				{
					$this->titre = 'Confirmation mandat';
					$this->message = 'Une erreur s\'est produite ressayez plus tard';
				}
				else
				{
					$this->titre = 'Confirmation mandat';
					$this->message = 'Vous n\'avez pas encore signé votre mandat';
				}
				
			}
			// si on a le pouvoir
			elseif($this->params[0] == 'pouvoir' && $projects_pouvoir->get($this->params[1],'id_pouvoir'))
			{
				
				// on recup le projet
				$projects->get($projects_pouvoir->id_project,'id_project');
				// on recup la companie
				$companies->get($projects->id_company,'id_company');
				// on recup l'emprunteur
				$clients->get($companies->id_client_owner,'id_client');
				
				$this->titre = 'Confirmation pouvoir';
				$this->lien_pdf = $this->lurl.$projects_pouvoir->url_pdf;
				
				// si pouvoir ok
				if($projects_pouvoir->status == 1)
				{
					$this->message = 'Votre pouvoir a bien été signé';
				}
				// pouvoir annulé
				elseif($projects_pouvoir->status == 2)
				{
					$this->message = 'Votre pouvoir a bien été annulé vous pouvez le signer plus tard.';
				}
				// pouvoir fail
				elseif($projects_pouvoir->status == 3)
				{
					$this->message = 'Une erreur s\'est produite ressayez plus tard';
				}
				else
				{
					$this->message = 'Vous n\'avez pas encore signé votre pouvoir';
				}
			}
			else
			{
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
	
}