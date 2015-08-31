<?php

class rootController extends bootstrap
{
	var $Command;
	
	function rootController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
	}
	
	function _default()
	{
		// Activation du cache
		$this->fireCache();
		
		
		
			// ajout du slash car capital rajout un Get
			if(substr($this->params[0],0,8) == 'capital?')
			{
				header('location:'.$this->lurl.'/capital/?');
             	die;
			}
			
			
			// On check pour les tracker google defonce pas la home
			if(substr($this->params[0],0,1) == '?')
			{
				$paramSlug = '';
			}
			else
			{
				$paramSlug = $this->params[0];
			}
			
			// On regarde si on a une redirection sur ce slug
			$this->redirections = $this->loadData('redirections');
			
			if($this->redirections->get(array('from_slug'=>$paramSlug,'id_langue'=>$this->language)))
			{
				header('location:'.$this->lurl.'/'.$this->redirections->to_slug,true,301);
             	die;	
			}
			
			// Recuperation des infos de la page
			if($this->tree->get(array('slug'=>$paramSlug,'id_langue'=>$this->language)))
			{					
				if($this->tree->prive == 1)
				{
					$this->etatLogin = true;
					
					// Declarations des datas 
					$this->clients = $this->loadData('clients');	
					
					// On regarde si on a un client dans la salle
					if(!$this->clients->checkAccess())
					{
						$this->etatLogin = false;
					}
				}
				
				// Redirection inscription preteur
				if($this->tree->id_tree == 127)
				{
					if($this->clients->checkAccess() && in_array($this->clients->status_pre_emp,array(1,3)))
					{
						
						
						if($this->clients->etape_inscription_preteur < 3){
							
							
							$etape = ($this->clients->etape_inscription_preteur+1);
							
							header('Location:'.$this->lurl.'/inscription_preteur/etape'.$etape);
							die;
						}
						
						header('Location:'.$this->lurl.'/projects');
						die;
					}
					else
					{
						// source
						$this->ficelle->source($_GET['utm_source'],$this->lurl.'/inscription_preteur/etape1',$_GET['utm_source2']);
						
						header('Location:'.$this->lurl.'/inscription_preteur');
						die;
					}
				}
				
				// Redirection depot de dossier
				if($this->tree->id_tree == 128)
				{
					// source
					$this->ficelle->source($_GET['utm_source'],$this->lurl.'/depot_de_dossier/etape1',$_GET['utm_source2']);
					
					header('Location:'.$this->lurl.'/depot_de_dossier');
					die;
				}
				
			
				// Modification du title,menu_title et template dans la previsualisation de la page
				if(isset($_POST['preview']) && $_POST['preview'] == md5($this->url.'/'.$this->tree->slug))
				{
					$this->tree->id_template = $_POST['id_template_'.$this->language];
					$this->tree->title = $_POST['title_'.$this->language];
					$this->tree->menu_title = $_POST['menu_title_'.$this->language];
				}
			
				// Declaration des metas pour l'arbo
				$this->meta_title = $this->tree->meta_title;
				$this->meta_description = $this->tree->meta_description;
				$this->meta_keywords = $this->tree->meta_keywords;				
				
				// Recuperation du template de la page
				$this->templates->get($this->tree->id_template);
				$this->current_template = $this->templates->name.' | '.$this->templates->slug;
				
				if($this->templates->affichage == 1)
				{
					// On renvoi vers le premier parent qui a un affichage à 0
					header('Location:'.$this->lurl.'/'.$this->tree->getSlug($this->tree->getFirstUnlock($this->tree->id_tree,$this->language),$this->language));
					die;
				}
							
				// Dans le cas ou on a pas de template cet a dire pas de page derriere le lien on prend le premier enfant
				if($this->tree->id_template == 0)
				{
					$this->subpages = $this->tree->select('id_parent = "'.$this->tree->id_tree.'" AND id_langue = "'.$this->language.'"','ordre ASC');
					
					if(count($this->subpages) > 0)
					{
						header('Location:'.$this->lurl.'/'.$this->subpages[0]['slug']);
						die;	
					}
				}
							
				// Recuperation du contenu de la page
				$contenu = $this->tree_elements->select('id_tree = "'.$this->tree->id_tree.'" AND id_langue = "'.$this->language.'"');
				foreach($contenu as $elt)
				{
					$this->elements->get($elt['id_element']);
					$this->content[$this->elements->slug] = $elt['value'];
					$this->complement[$this->elements->slug] = $elt['complement'];
				}
				
				// Recuperation du contenu de la page dans la previsualisation de la page
				if(isset($_POST['preview']) && $_POST['preview'] == md5($this->url.'/'.$this->tree->slug))
				{
					// Remplissage des éléments
					foreach($this->content as $key => $value)
					{
						$this->content[$key] = stripslashes($_POST[$key.'_'.$this->language]);
					}
				}
				
				// Recuperation des positions des blocs
				$this->lBlocsPosition = $this->bdd->getEnum('blocs_templates','position');
				
				// Recuperation des blocs pour chaque position
				foreach($this->lBlocsPosition as $pos)
				{
					$this->lBlocs[$pos] = $this->blocs_templates->selectBlocs('position = "'.$pos.'" AND id_template = '.$this->tree->id_template,'ordre ASC');
					
					// Recuperation du contenu de chaque bloc
					foreach($this->lBlocs[$pos] as $bloc)
					{
						$lElements = $this->blocs_elements->select('id_bloc = '.$bloc['id_bloc'].' AND id_langue = "'.$this->language.'"');
						foreach($lElements as $b_elt)
						{
							$this->elements->get($b_elt['id_element']);
							$this->bloc_content[$this->elements->slug] = $b_elt['value'];	
							$this->bloc_complement[$this->elements->slug] = $b_elt['complement'];	
						}			
					}				
				}
				
				// Recuperation du contenu des enfants
				$this->childs = $this->tree->select('id_parent = "'.$this->tree->id_tree.'" AND status = 1 AND id_langue = "'.$this->language.'"','ordre ASC');
				foreach($this->childs as $child)
				{
					$contenu = $this->tree_elements->select('id_tree = "'.$child['id_tree'].'" AND id_langue = "'.$this->language.'"');
					
					foreach($contenu as $elt)
					{
						$this->elements->get($elt['id_element']);
						$this->childsContent[$child['id_tree']][$this->elements->slug] = $elt['value'];
						$this->childsComplement[$child['id_tree']][$this->elements->slug] = $elt['complement'];
					}
				}
			   
				// Creation du breadcrumb
				$this->breadCrumb = $this->tree->getBreadCrumb($this->tree->id_tree,$this->language);
				$this->nbBreadCrumb = count($this->breadCrumb);
				
				// Si on n'est pas connecté, on n'a pas acces aux pages preteur et emprunteur
				if($this->tree->arbo == 1 || $this->tree->arbo == 2)
				{
					if(!$this->clients->checkAccess())
					{
						header('Location:'.$this->lurl);
						die;
					}
					else
					{
						// 1 preteur
						if($this->tree->arbo == 1)
						{
							$this->clients->checkStatusPreEmp($this->clients->status_pre_emp,'preteur',$this->clients->id_client);	
						}
						// 2 emprunteur
						elseif($this->tree->arbo == 2)
						{
							$this->clients->checkStatusPreEmp($this->clients->status_pre_emp,'emprunteur',$this->clients->id_client);	
						}
						
						// On prend le header account
						$this->setHeader('header_account');
						
					}
					
				}
				
				
				
				////////////////////////////////////////
				// DEBUT TEMPLATE ETAPE DE TRANSITION //
				////////////////////////////////////////
				if($this->tree->id_template == 10)
				{
					
				}
				//////////////////////////////////////
				// FIN TEMPLATE ETAPE DE TRANSITION //
				//////////////////////////////////////
				
				//////////////////////////////
				// DEBUT TEMPLATE LESXPRESS //
				//////////////////////////////
				
				
		
				
				// landing page restriction pour pas aller sur d'autres pages	
				if($this->lurl == 'http://lexpress.unilend.fr'){
					
					if($this->tree->id_template != 18 && $this->tree->id_template != 20 && $this->tree->id_template != 21)
					{
						header('location: '.$this->surl);
						die;
					}
				}
                                
                                // landing page restriction pour pas aller sur d'autres pages	
				if($this->lurl == 'http://pret-entreprise.votreargent.lexpress.fr'){
					
					if($this->tree->id_template != 18 && $this->tree->id_template != 20 && $this->tree->id_template != 21)
					{
						header('location: '.$this->surl);
						die;
					}
				}
                                
                                // landing page restriction pour pas aller sur d'autres pages	
				if($this->lurl == 'http://emprunt-entreprise.lentreprise.lexpress.fr'){
					
					if($this->tree->id_template != 18 && $this->tree->id_template != 20 && $this->tree->id_template != 21)
					{
						header('location: '.$this->surl);
						die;
					}
				}
                                
                                
				
				
				
				if($this->tree->id_template == 15)
				{
					$_SESSION['lexpress']['id_template'] = $this->tree->id_template;
					$_SESSION['lexpress']['header'] = $this->content['header'];
					$_SESSION['lexpress']['footer'] = $this->content['footer'];
					
					header('location:'.$this->lurl);
             		die;
					
				}
				////////////////////////////
				// FIN TEMPLATE LESXPRESS //
				////////////////////////////
				
				//////////////////////////////
				// DEBUT TEMPLATE LESXPRESS Votre argent //
				//////////////////////////////
				if($this->tree->id_template == 19)
				{
					$_SESSION['lexpress']['id_template'] = $this->tree->id_template;
					$_SESSION['lexpress']['header'] = $this->content['header-277'];
					$_SESSION['lexpress']['footer'] = $this->content['footer-278'];
					
					header('location:'.$this->lurl);
             		die;
					
				}
				////////////////////////////
				// FIN TEMPLATE LESXPRESS Votre argent //
				////////////////////////////
				
				
				////////////////////////////
				// DEBUT TEMPLATE PROJETS //
				////////////////////////////
				if($this->tree->id_template == 14)
				{
					//Recuperation des element de traductions
					$this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets',$this->language,$this->App);
					
					$this->bids = $this->loadData('bids');
					
					// Heure fin periode funding
					$this->settings->get('Heure fin periode funding','type');
					$this->heureFinFunding = $this->settings->value;
					
					
					// Chargement des datas
					$this->projects = $this->loadData('projects');
					$this->projects_status = $this->loadData('projects_status');
					$this->companies = $this->loadData('companies');
					$this->companies_details = $this->loadData('companies_details');
					$this->favoris = $this->loadData('favoris');
					
					// tri par taux
					$this->settings->get('Tri par taux','type');
					$this->triPartx = $this->settings->value;
					$this->triPartx = explode(';',$this->triPartx);
					
					// tri par taux intervalles
					$this->settings->get('Tri par taux intervalles','type');
					$this->triPartxInt = $this->settings->value;
					$this->triPartxInt = explode(';',$this->triPartxInt);
					
					
					// page projet tri
					// 1 : terminé bientot
					// 2 : nouveauté
					//$this->tabOrdreProject[....] <--- dans le bootstrap pour etre accessible partout (page default et ajax)
					
					$this->ordreProject = 1; 
					$this->type = 0;
					
					$_SESSION['ordreProject'] = $this->ordreProject;
					
					// Liste des projets en funding
					$this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay,' AND p.status = 0 AND p.display = 0',$this->tabOrdreProject[$this->ordreProject],0,10);
					
					// Nb projets en funding. Ajout du statut 75 (prêts refusés) au comptage, demande nicolas d'Aout 2015
					$this->nbProjects = $this->projects->countSelectProjectsByStatus($this->tabProjectDisplay.',75',' AND p.status = 0 AND p.display = 0');
					
					// on signal que c'est une page du fo
					$this->page = 'projets_fo';
					$_SESSION['page_projet'] = $this->page;
					
					// restriction pour capital
					
					if($this->lurl == 'http://prets-entreprises-unilend.capital.fr' 
						|| $this->lurl == 'http://partenaire.unilend.challenges.fr'
						|| $this->lurl == 'http://financementparticipatifpme.lefigaro.fr' 
						|| $this->lurl == 'http://financementparticipatifpme.lefigaro.fr' 
					){
					//if($this->lurl == 'http://capital.unilend.fr' || $this->lurl == 'http://challenges.unilend.fr'){
						$this->autoFireHeader = true;
						$this->autoFireDebug = false;
						$this->autoFireHead = true;
						$this->autoFireFooter = false;
					}
					
				}
				
				// restriction pour capital
				if($this->lurl == 'http://prets-entreprises-unilend.capital.fr' && $this->tree->id_template != 14){
					header('location: http://prets-entreprises-unilend.capital.fr/capital/');
             		die;
				}
				/*if($this->lurl == 'http://capital.unilend.fr' && $this->tree->id_template != 14){
					header('location: http://capital.unilend.fr/capital/');
             		die;
				}*/
				elseif($this->lurl == 'http://partenaire.unilend.challenges.fr' && $this->tree->id_template != 14){
					header('location: http://partenaire.unilend.challenges.fr/challenges/');
             		die;
				}
				elseif($this->lurl == 'http://financementparticipatifpme.lefigaro.fr' && $this->tree->id_template != 14){
					header('location: http://financementparticipatifpme.lefigaro.fr/figaro/');
             		die;
				}
				elseif($this->lurl == 'http://financementparticipatifpme.lefigaro.fr' && $this->tree->id_template != 14){
					header('location: http://financementparticipatifpme.lefigaro.fr/figaro/');
             		die;
				}
				
				//////////////////////////
				// FIN TEMPLATE PROJETS //
				//////////////////////////
				
				
				/////////////////////////////////////////
				// DEBUT TEMPLATE NOUVEAU MOT DE PASSE //
				/////////////////////////////////////////
				if($this->tree->id_template == 12)
				{
					//Recuperation des element de traductions
$this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1',$this->language,$this->App);
					
					if(isset($this->params[1]) && $this->clients->get($this->params[1],'hash'))
					{
						$this->reponse = false;
						if(isset($this->params[2]) == 'valide')
						{
							$this->reponse = true;
						}
						
						if(isset($_POST['send_form_new_mdp']))
						{
							$form_ok = true;
							
							// pass
							if(!isset($_POST['pass']) || $_POST['pass'] == '' || $_POST['pass'] == $this->lng['etape1']['mot-de-passe'])
							{
								$form_ok = false;
							}
							// pass2
							if(!isset($_POST['pass2']) || $_POST['pass2'] == '' || $_POST['pass2'] == $this->lng['etape1']['confirmation-de-mot-de-passe'])
							{
								$form_ok = false;
							}
							// pass et pass2
							if(isset($_POST['pass']) && isset($_POST['pass2']) && $_POST['pass'] != $_POST['pass2'])
							{
								$form_ok = false;
							}
							// repionse secrete
							if(!isset($_POST['secret-response']) || $_POST['secret-response'] == '' || $_POST['secret-response'] == $this->lng['etape1']['response'])
							{
								$form_ok = false;
							}
							elseif(md5($_POST['secret-response']) != $this->clients->secrete_reponse)
							{
								$form_ok = false;
								$this->erreur_reponse_secrete = true;
							}
							
							if($form_ok == true)
							{
								$mdp = $_POST['pass'];
								$this->clients->password = md5($mdp);
								$this->clients->update();
								
								//
//								//************************************//
//								//*** ENVOI DU MAIL GENERATION MDP ***//
//								//************************************//
//					
//								// Recuperation du modele de mail
//								$this->mails_text->get('generation-mot-de-passe','lang = "'.$this->language.'" AND type');
//								
//								// Variables du mailing
//								$surl = $this->surl;
//								$url = $this->lurl;
//								$login = $this->clients->email;
//								$mdp = 'Mot de passe : '.$mdp;
//								
//								// FB
//								$this->settings->get('Facebook','type');
//								$lien_fb = $this->settings->value;
//								
//								// Twitter
//								$this->settings->get('Twitter','type');
//								$lien_tw = $this->settings->value;
//								
//					
//								// Variables du mailing
//								$varMail = array(
//								'surl' => $surl,
//								'url' => $url,
//								'login' => $login,
//								'mdp' => $mdp,
//								'prenom_p' => $this->clients->prenom,
//								'lien_fb' => $lien_fb,
//								'lien_tw' => $lien_tw);	
//								
//								
//								// Construction du tableau avec les balises EMV
//								$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
//								
//								// Attribution des données aux variables
//								$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);				
//								$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
//								$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
//								
//								// Envoi du mail
//								$this->email = $this->loadLib('email',array());
//								$this->email->setFrom($this->mails_text->exp_email,$exp_name);
//								$this->email->setSubject(stripslashes($sujetMail));
//								$this->email->setHTMLBody(stripslashes($texteMail));
//								
//								if($this->Config['env'] == 'prod') // nmp
//								{
//									Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->clients->email,$tabFiler);
//									// Injection du mail NMP dans la queue
//									$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
//								}
//								else // non nmp
//								{
//									$this->email->addRecipient(trim($this->clients->email));
//									Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
//								}
//								// fin mail
//								
								
								
								header('location:'.$this->lurl.'/'.$this->params[0].'/'.$this->params[1].'/valide');
             					die;
							}
						}
					}
					else
					{
						header('location:'.$this->lurl);
             			die;
					}
				}
				///////////////////////////////////////
				// FIN TEMPLATE NOUVEAU MOT DE PASSE //
				///////////////////////////////////////
				
				
				////////////////////////////
				// DEBUT TEMPLATE CONTACT //
				////////////////////////////
				
				if($this->tree->id_template == 4)
				{
					//Recuperation des element de traductions
					$this->lng['contact'] = $this->ln->selectFront('contact',$this->language,$this->App);
					
					// Chargement des datas
					$this->demande_contact = $this->loadData('demande_contact');
					
					// Form envoyé
					if(isset($_POST['send_form_contact']))
					{
						
						$this->demande_contact->demande = $_POST['demande'];
						$this->demande_contact->preciser = $_POST['preciser'];
						$this->demande_contact->nom = $this->ficelle->majNom($_POST['nom']);
						$this->demande_contact->prenom = $this->ficelle->majNom($_POST['prenom']);
						$this->demande_contact->email = $_POST['email'];
						$this->demande_contact->message = $_POST['message'];
						$this->demande_contact->societe = $_POST['societe'];
						$this->demande_contact->telephone = $_POST['telephone'];

						
						$this->form_ok = true;
						
						$this->error_demande = 'ok';
						$this->error_message = 'ok';
						$this->error_nom = 'ok';
						$this->error_prenom = 'ok';
						$this->error_email = 'ok';
						$this->error_captcha = 'ok';
						
	
						if(isset($_POST['telephone']) && $_POST['telephone'] != '' && $_POST['telephone'] != $this->lng['contact']['telephone'])
						{
							$this->error_telephone = 'ok';
							
							if(!is_numeric($_POST['telephone']))
							{
								$this->form_ok = false;
								$this->error_telephone = 'nok';
							}
						}
						
						if(!isset($_POST['demande']) || $_POST['demande'] == '' || $_POST['demande'] == 0)
						{
							$this->form_ok = false;
							$this->error_demande = 'nok';
						}
						
						if(!isset($_POST['nom']) || $_POST['nom'] == '' || $_POST['nom'] == $this->lng['contact']['nom'])
						{
							$this->form_ok = false;
							$this->error_nom = 'nok';
						}
						
						if(!isset($_POST['prenom']) || $_POST['prenom'] == '' || $_POST['prenom'] == $this->lng['contact']['prenom'])
						{
							$this->form_ok = false;
							$this->error_prenom = 'nok';
						}
						
						if(!isset($_POST['email']) || $_POST['email'] == '' || $_POST['email'] == $this->lng['contact']['email'])
						{
							$this->form_ok = false;
							$this->error_email = 'nok';
						}
						elseif(!$this->ficelle->isEmail($_POST['email']))
						{
							$this->form_ok = false;
							$this->error_email = 'nok';
						}
						
						if(!isset($_POST['message']) || $_POST['message'] == '' || $_POST['message'] == $this->lng['contact']['message'])
						{
							$this->form_ok = false;
							$this->error_message = 'nok';
						}
						
						if(!isset($_POST['captcha']) || $_POST['captcha'] == '' || $_POST['captcha'] == $this->lng['contact']['captcha'])
						{
							$this->form_ok = false;
							$this->error_captcha = 'nok';
						}
						elseif($_SESSION['securecode'] != strtolower($_POST['captcha']))
						{
							$this->form_ok = false;
							$this->error_captcha = 'nok';
						}
						
						if($this->form_ok == true)
						{
							$this->confirmation = $this->lng['contact']['confirmation'];
							
							if($this->demande_contact->demande!=5)$this->demande_contact->preciser = '';
							
							$this->demande_contact->create();
							
							
							
							
							// Destinataire Unilend
							if($this->demande_contact->demande == 1)$this->settings->get('Adresse presse','type');
							elseif($this->demande_contact->demande == 2)$this->settings->get('Adresse preteur','type');
							elseif($this->demande_contact->demande == 3)$this->settings->get('Adresse emprunteur','type');
							elseif($this->demande_contact->demande == 4)$this->settings->get('Adresse recrutement','type');
							elseif($this->demande_contact->demande == 5)$this->settings->get('Adresse autre','type');
							elseif($this->demande_contact->demande == 6)$this->settings->get('Adresse partenariat','type');
							
							$destinataire = $this->settings->value;
							
							// Liste des objets
							$objets = array('','Relation presse','Demande preteur','Demande Emprunteur','Recrutement','Autre','Partenariat');
							
							
							//*****************************//
							//*** ENVOI DU MAIL CONTACT ***//
							//*****************************//
				
							// Recuperation du modele de mail
							$this->mails_text->get('demande-de-contact','lang = "'.$this->language.'" AND type');
							
							// Variables du mailing
							$surl = $this->surl;
							$url = $this->lurl;
							$email = $this->demande_contact->email;
							$nom = $this->demande_contact->nom;
							$prenom = $this->demande_contact->prenom;
							$objet = $objets[$this->demande_contact->demande];
							
							// FB
							$this->settings->get('Facebook','type');
							$lien_fb = $this->settings->value;
							
							// Twitter
							$this->settings->get('Twitter','type');
							$lien_tw = $this->settings->value;
							
							$pageProjets = $this->tree->getSlug(4,$this->language);
				
							// Variables du mailing
							$varMail = array(
							'surl' => $surl,
							'url' => $url,
							'email_c' => $email,
							'prenom_c' => $prenom,
							'nom_c' => $nom,
							'objet' => $objet,
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
								Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$_POST['email'],$tabFiler);
								// Injection du mail NMP dans la queue
								$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
							}
							else // non nmp
							{
								$this->email->addRecipient(trim($_POST['email']));
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
							$nom = utf8_decode($this->demande_contact->nom);
							$prenom = utf8_decode($this->demande_contact->prenom);
							$objet = ($objets[$this->demande_contact->demande]);
							
							
							$this->demande_contact->preciser = $_POST['preciser'];
							$this->demande_contact->nom = $this->ficelle->majNom($_POST['nom']);
							$this->demande_contact->prenom = $this->ficelle->majNom($_POST['prenom']);
							$this->demande_contact->email = $_POST['email'];
							$this->demande_contact->message = $_POST['message'];
							$this->demande_contact->societe = $_POST['societe'];
							$this->demande_contact->telephone = $_POST['telephone'];
							
							
							$infos = '<ul>';
							$infos .= '<li>Type demande : '.$objet.'</li>';
							if($this->demande_contact->demande==5)$infos .= '<li>Preciser :'.utf8_decode($this->demande_contact->preciser).'</li>';
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
							//$this->email->addBCCRecipient('k1@david.equinoa.net');
						
							$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
							$this->email->setHTMLBody($texteMail);
							Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
							
							
							
							$this->demande_contact->demande = '';
							$this->demande_contact->preciser = '';
							$this->demande_contact->nom = '';
							$this->demande_contact->prenom = '';
							$this->demande_contact->email = '';
							$this->demande_contact->message = '';
							$this->demande_contact->societe = '';
							$this->demande_contact->telephone = '';
							
							$this->error_demande = '';
							$this->error_message = '';
							$this->error_nom = '';
							$this->error_prenom = '';
							$this->error_email = '';
							$this->error_captcha = '';

							
							
						}
					}
				}
				
				//////////////////////////
				// FIN TEMPLATE CONTACT //
				//////////////////////////
				
				
				/////////////////////////
				// DEBUT TEMPLATE HOME //
				/////////////////////////
				
				if($this->tree->id_template == 8)
				{
					
					$this->loadCss('default/compteur_home/style');
					
					// Chargement des datas
					$this->projects = $this->loadData('projects');
					$this->projects_status = $this->loadData('projects_status');
					$this->companies = $this->loadData('companies');
					$this->companies_details = $this->loadData('companies_details');
					$this->bids = $this->loadData('bids');
					
					// source
					$this->ficelle->source($_GET['utm_source'],'',$_GET['utm_source2']);
					
					// Heure fin periode funding
					$this->settings->get('Heure fin periode funding','type');
					$this->heureFinFunding = $this->settings->value;
					
					
					$this->ordreProject = 1;
					
					$_SESSION['ordreProject'] = $this->ordreProject;
					
					// Nb projets en funding. 2015-08-22 : ajout du statut 75 en comptage 
					$statutsACompterTotalProjets = $this->tabProjectDisplay;
					$statutsACompterTotalProjets.=',75';
					
					
					// Liste des projets en funding
					$this->lProjetsFunding = $this->projects->selectProjectsByStatus($statutsACompterTotalProjets,' AND p.status = 0 AND p.display = 0',$this->tabOrdreProject[$this->ordreProject],0,10);
					
					
					$this->nbProjects = $this->projects->countSelectProjectsByStatus($statutsACompterTotalProjets,' AND p.status = 0 AND p.display = 0');
					
					
					
					
					// ensemblee des fonds recupérés
					$compteurFonds = $this->transactions->sum('type_transaction = 9','montant_unilend-montant');
					$compteurFonds = number_format(($compteurFonds/100), 0, ',', ' ');
					
					
					
					//$compteurFonds = '100 000 000';
					
					$tabCompteur = str_split($compteurFonds);
					$this->compteur = '';
					$count = count($tabCompteur);
					foreach($tabCompteur as $k => $c)
					{
						// si cest le premier ou si c'est vide
						if($c == '' || $c == ' '){
							$this->compteur .= '
							</div>';
						}
						if($k == 0 || $c == '' || $c == ' '){
							$this->compteur .= '
							<div class="char-group">';
						}
						if($c != '' && $c != ' '){
							$this->compteur .= '
								<span class="counter-char">'.$c.'</span>';
						}
						if($count == $k+1)$this->compteur .= '
							</div>';
						
					}
					
				}
				
				///////////////////////
				// FIN TEMPLATE HOME //
				///////////////////////
				
				
				
				///////////////////////////////
				// TEMPLATE LANDING PAGE //
				///////////////////////////////
				
				// KLE - On bloque l'affichage du header/head/footer sur la landing-page
				// Récup de l'id de page
				
				$this->settings = $this->loadData('settings');
				$this->settings->get('id_template_landing_page','type');
				$this->id_template_landing_page = $this->settings->value;
				
				if($this->tree->id_template == $this->id_template_landing_page)
				{
					$this->autoFireHeader = false;
					$this->autoFireDebug = false;
					$this->autoFireHead = false;
					$this->autoFireFooter = false;
				}
				///////////////////////////////
				// FIN TEMPLATE LANDING PAGE //
				///////////////////////////////
				
				////////////////////////////////////////////
				// TEMPLATE LANDING PAGE DEPOT DE DOSSIER //
				////////////////////////////////////////////
				
				// On bloque l'affichage du header/head/footer sur la landing-page
				// Récup de l'id de page
				
				$this->settings = $this->loadData('settings');
				$this->settings->get('id_template_landing_page_depot_de_dossier','type');
				$this->id_template_landing_page = $this->settings->value;
				
				if($this->tree->id_template == $this->id_template_landing_page)
				{
					$this->autoFireHeader = false;
					$this->autoFireDebug = false;
					$this->autoFireHead = false;
					$this->autoFireFooter = false;
				}
				////////////////////////////////////////////////
				// FIN TEMPLATE LANDING PAGE DEPOT DE DOSSIER //
				////////////////////////////////////////////////
				
				
				////////////////////////////////////////////
				// TEMPLATE LANDING PAGE DEPOT DE DOSSIER l'express //
				////////////////////////////////////////////
				
				// On bloque l'affichage du header/head/footer sur la landing-page
				// Récup de l'id de page
				
				$this->settings = $this->loadData('settings');
				$this->settings->get('id_template_landing_page_depot_de_dossier_lexpress','type');
				$this->id_template_landing_page = $this->settings->value;
				
				if($this->tree->id_template == $this->id_template_landing_page)
				{
					$this->autoFireHeader = false;
					$this->autoFireDebug = false;
					$this->autoFireHead = false;
					$this->autoFireFooter = false;
					
					
					
					$content = file_get_contents('http://lentreprise.lexpress.fr/partenariat/touchvibes/arche.html');
					$content = str_replace('<!-- partner_code_end -->','',$content);
					$content = explode('<!-- partner_code_start -->',$content);
					
					$this->haut = $content[0];
					$this->bas = $content[1];
					
					
				}
				////////////////////////////////////////////////
				// FIN TEMPLATE LANDING PAGE DEPOT DE DOSSIER //
				////////////////////////////////////////////////
				
				
				////////////////////////////////////////////
				// TEMPLATE Landing-page-inscription-preteurs-lexpress //
				////////////////////////////////////////////
				
				// On bloque l'affichage du header/head/footer sur la landing-page
				// Récup de l'id de page
				
				//$this->settings = $this->loadData('settings');
				//$this->settings->get('id_template_landing_page_depot_de_dossier_lexpress','type');
				//$this->id_template_landing_page = $this->settings->value;
				
				$this->id_template_landing_page = 20; // Landing-page-inscription-preteurs-lexpress
				
				if($this->tree->id_template == $this->id_template_landing_page)
				{
					$this->autoFireHeader = false;
					$this->autoFireDebug = false;
					$this->autoFireHead = false;
					$this->autoFireFooter = false;
					
					
					
					$content = file_get_contents('http://votreargent.lexpress.fr/partenaires/unilend/arche.html');
					$content = str_replace('<!-- partner_code_end -->','',$content);
					
					
					$content = explode('<!-- partner_code_start -->',$content);
					
					
					
					$this->haut = $content[0];
					$this->bas = $content[1];
					$content = explode('</main>',$this->bas);
					$this->bas = $content[1];
					
				}
				
				$this->id_template_landing_page = 21; // Landing-page-inscription-preteurs-bienenue-lexpress
				
				if($this->tree->id_template == $this->id_template_landing_page)
				{
					$this->autoFireHeader = false;
					$this->autoFireDebug = false;
					$this->autoFireHead = false;
					$this->autoFireFooter = false;
					
					$content = file_get_contents('http://votreargent.lexpress.fr/partenaires/unilend/arche.html');
					$content = str_replace('<!-- partner_code_end -->','',$content);
					
					
					$content = explode('<!-- partner_code_start -->',$content);

					$this->haut = $content[0];
					$this->bas = $content[1];
					$content = explode('</main>',$this->bas);
					$this->bas = $content[1];
					
				}
				////////////////////////////////////////////////
				// FIN TEMPLATE LANDING PAGE DEPOT DE DOSSIER //
				////////////////////////////////////////////////
				
				
				// Chargemement du tempalte
				if($this->templates->slug == '' || $this->tree->id_template == 7)
				{
					//header("HTTP/1.0 404 Not Found");
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
					$this->setView('../root/404');	
				}				
				elseif($this->tree->status == 0 && !isset($_SESSION['user']))
				{
					//header("HTTP/1.0 404 Not Found");
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
					$this->setView('../root/404');	
				}
				else
				{
					$this->setView('../templates/'.$this->templates->slug,true);
				}
			}
			else
			{
				// On regarde si on a une redirection sur ce slug
				$this->redirections = $this->loadData('redirections');
				
				if($this->redirections->get(array('from_slug'=>$paramSlug,'id_langue'=>$this->language)))
				{
					header('location:'.$this->lurl.'/'.$this->redirections->to_slug,true,301);
					die;	
				}
				else
				{
					//header("HTTP/1.0 404 Not Found");
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
					$this->setView('../root/404');	
				}
			}	
		
	}
	
	function _logout()
	{
		$this->clients->handleLogout();
	}
	
	function _logAdminUser()
	{
		$this->autoFireHeader = false;
		$this->autoFireDebug = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
		$this->users = $this->loadData('users');
		
		if($this->params[0] != '' && $this->params[1] != '')
		{		
			$this->users->handleLoginFront($this->params[0],$this->params[1]);
		}
		else
		{
			$this->users->handleLogoutFront();
		}
	}
	
	function _search()
	{
		//Recuperation des element de traductions
		$this->lng['search'] = $this->ln->selectFront('search',$this->language,$this->App);
		
		// recupération du title et slug pour le Breadcrumbs
		$this->page_title = $this->lng['recherche-corpo']['title'];
		$this->page_slug = "search";

		
		// Vérification recherche
		if(isset($_POST['search']) && $_POST['search'] != $this->lng['header']['recherche'])
		{
			$this->search = $_POST['search'];

			$this->result = $this->tree->search($this->search,'',$this->language);
		}
		
	}
	
	function _notification_payline()
	{
		//echo $_SERVER['REMOTE_ADDR'];
		//die;
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireView = false;
		$this->autoFireFooter = false;
		
		$this->transactions = $this->loadData('transactions');
		$this->backpayline = $this->loadData('backpayline');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->bank_lines = $this->loadData('bank_lines');
		
		// On recup la lib et le reste payline
		require_once($this->path.'protected/payline/include.php');
		
		$array = array();
		
		// GET TOKEN
		if(isset($_POST['token']))
		{
			$array['token'] = $_POST['token'];
		}
		elseif(isset($_GET['token']))
		{
		
			$array['token'] = $_GET['token'];
		}
		else
		{
			//header('location:'.$this->lurl.'/alimentation');
			die;
		}
		
		$payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
		
		
		
		$array['version'] = '3';
		$response = $payline->getWebPaymentDetails($array);
		//echo $array['token'];
		//die;
		
		/*echo '<pre>';
		print_r($response);
		echo '</pre>';
		die;*/
		
		
		if(isset($response))
		{
			// On enregistre le resultat payline
			$this->backpayline->code = $response['result']['code'];
			$this->backpayline->token = $array['token'];
			$this->backpayline->id = $response['transaction']['id'];
			$this->backpayline->date = $response['transaction']['date'];
			$this->backpayline->amount = $response['payment']['amount'];
			$this->backpayline->serialize = serialize($response);
			$this->backpayline->id_backpayline = $this->backpayline->create();
			
			if($response['result']['code'] == '00000')
			{
				if($this->transactions->get($response['order']['ref'],'status = 0 AND etat = 0 AND id_transaction'))
				{
					
					$this->transactions->id_backpayline = $this->backpayline->id_backpayline;
					$this->transactions->montant = $response['payment']['amount'];
					$this->transactions->id_langue = 'fr';
					$this->transactions->date_transaction = date('Y-m-d H:i:s');
					$this->transactions->status = '1';
					$this->transactions->etat = '1';
					$this->transactions->type_paiement = ($response['extendedCard']['type'] == 'VISA'?'0':($response['extendedCard']['type'] == 'MASTERCARD'?'3':''));
					$this->transactions->update();
					
					// On recupere le lender
					$this->lenders_accounts->get($this->transactions->id_client,'id_client_owner');
					$this->lenders_accounts->status = 1;
					$this->lenders_accounts->update();
					
					// On enrgistre la transaction dans le wallet
					$this->wallets_lines->id_lender = $this->lenders_accounts->id_lender_account;
					$this->wallets_lines->type_financial_operation = 30; // alimentation preteur
					$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
					$this->wallets_lines->status = 1;
					$this->wallets_lines->type = 1;
					$this->wallets_lines->amount = $response['payment']['amount'];
					$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
					
					// Transaction physique donc on enregistre aussi dans la bank lines
					$this->bank_lines->id_wallet_line = $this->wallets_lines->id_wallet_line;
					$this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account;
					$this->bank_lines->status = 1;
					$this->bank_lines->amount = $response['payment']['amount'];
					$this->bank_lines->create();
					
					
					////////////////////////////
					// Mail alert transaction //
					////////////////////////////
				
					//$to  = 'unilend@equinoa.fr';
					$to  = 'd.courtier@relance.fr';
				
					// subject
					$subject = '[Alerte] BACK PAYLINE Transaction approved';
					
					// message
					$message = '
					<html>
					<head>
					  <title>[Alerte] BACK PAYLINE Transaction approved</title>
					</head>
					<body>
					  <h3>[Alerte] BACK PAYLINE Transaction approved</h3>
					  <p>Un payement payline accepet&eacute; n\'a pas &eacute;t&eacute; mis &agrave; jour dans la BDD Unilend.</p>
					  <table>
						<tr>
						  <th>Id client : </th><td>'.$this->transactions->id_client.'</td>
						</tr>
						<tr>
						  <th>montant : </th><td>'.($this->transactions->montant/100).'</td>
						</tr>
						<tr>
						  <th>serialize donnees payline : </th><td>'.serialize($response).'</td>
						</tr>
					  </table>
					</body>
					</html>
					';
					
					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					
					// Additional headers
					
					//$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
					$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
					
					// Mail it
					mail($to, $subject, $message, $headers);
				
				}
				else
				{
					////////////////////////////
					// Mail alert transaction //
					////////////////////////////
				
					//$to  = 'unilend@equinoa.fr';
					$to  = 'd.courtier@relance.fr';
				
					// subject
					$subject = '[Alerte] BACK PAYLINE Transaction approved DEJA TRAITE';
					
					// message
					$message = '
					<html>
					<head>
					  <title>[Alerte] BACK PAYLINE Transaction approved DEJA TRAITE</title>
					</head>
					<body>
					  <h3>[Alerte] BACK PAYLINE Transaction approved DEJA TRAITE</h3>
					  <p>Un payement payline accepet&eacute; deacute;j&agrave; &agrave; jour dans la BDD Unilend.</p>
					  <table>
						<tr>
						  <th>Id client : </th><td>'.$this->transactions->id_client.'</td>
						</tr>
						<tr>
						  <th>montant : </th><td>'.($this->transactions->montant/100).'</td>
						</tr>
						<tr>
						  <th>serialize donnees payline : </th><td>'.serialize($response).'</td>
						</tr>
					  </table>
					</body>
					</html>
					';
					
					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					
					// Additional headers
					
					//$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
					$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
					
					// Mail it
					mail($to, $subject, $message, $headers);
				}
			}
		}
	}
	
	function _changeCompte()
	{
		// On check si y a un compte
		if(!$this->clients->checkAccess()){
			header('Location:'.$this->lurl);
			die;
		}
		
		if(isset($this->params[0]) && isset($_SESSION['client']))
		{
			// on redirige sur le compte preteur
			if($this->params[0] == 1)
			{
				$_SESSION['status_pre_emp'] = 1;
				header('Location:'.$this->lurl.'/synthese');
				die;	
			}
			// on redirige sur le compte emprunteur
			else
			{
				$_SESSION['status_pre_emp'] = 2;
				header('Location:'.$this->lurl.'/synthese_emprunteur');
				die;
			}
		}
	}
	

	function _xmlAllProjects()
	{
		$projects = $this->loadData('projects');
		$companies = $this->loadData('companies');		
		$bids = $this->loadData('bids');	
		
		// Somme à emprunter min
		$this->settings->get('Liste deroulante secteurs','type'); // added 19/06/2015
		$this->tabSecteurs = explode(';',$this->settings->value); // added 19/06/2015
		
		
		// 50 : En funding
		// 60 : Fundé
		// 70 : Funding KO
		// 75 : Prêt refusé
		// 80 : Remboursement
		
		$lProjets = $projects->selectProjectsByStatus('50,60,80');
		
		//echo 'la';
		
		/*echo '<pre>';
		print_r($lProjets);
		echo '</pre>';*/
		
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<partenaire>';
		
		foreach($lProjets as $p)
		{
			$companies->get($p['id_company'],'id_company');
			
			$monantRecolt = $bids->sum('id_project = '.$p['id_project'].' AND status = 0','amount');
			$monantRecolt = ($monantRecolt/100);
			
			if($monantRecolt > $p['amount']) $monantRecolt = $p['amount'];
			
			$xml .=	'<projet>';
			$xml .= '<reference_partenaire>045</reference_partenaire>';
			$xml .= '<date_export>'.date('Y-m-d').'</date_export>';
			$xml .= '<reference_projet>'.$p['id_project'].'</reference_projet>';
			$xml .= '<impact_social>NON</impact_social>'; 
			$xml .= '<impact_environnemental>NON</impact_environnemental>';
			$xml .= '<impact_culturel>NON</impact_culturel>'; 
			$xml .= '<impact_eco>OUI</impact_eco>'; 
			$xml .= '<mots_cles_nomenclature_operateur>'.$this->tabSecteurs[$companies->sector-1].'</mots_cles_nomenclature_operateur>'; // added 19/06/2015
			$xml .= '<mode_financement>PRR</mode_financement>';
			$xml .= '<type_porteur_projet>ENT</type_porteur_projet>';
			$xml .= '<qualif_ESS>NON</qualif_ESS>';
			$xml .= '<code_postal>'.$companies->zip.'</code_postal>'; ////////////////////////////////////////
			$xml .= '<ville><![CDATA["'.utf8_encode($companies->city).'"]]></ville>'; 
			$xml .= '<titre><![CDATA["'.$p['title'].'"]]></titre>';
			$xml .= '<description><![CDATA["'.$p['nature_project'].'"]]></description>';
			$xml .= '<url><![CDATA["'.$this->lurl.'/projects/detail/'.$p['slug'].'/?utm_source=TNProjets&utm_medium=Part&utm_campaign=Permanent"]]></url>';
			$xml .= '<url_photo><![CDATA["'.$this->surl.'/var/images/photos_projets/photo_projet_moy_'.$p['photo_projet'].'"]]></url_photo>'; // http://unilend.demo2.equinoa.net/var/images/photos_projets/photo_projet_moy_montage.png
			$xml .= '<date_debut_collecte>'.$p['date_publication'].'</date_debut_collecte>';
			$xml .= '<date_fin_collecte>'.$p['date_retrait'].'</date_fin_collecte>';
			$xml .= '<montant_recherche>'.$p['amount'].'</montant_recherche>';
			$xml .= '<montant_collecte>'.number_format($monantRecolt, 0, ',', '').'</montant_collecte>';
			$xml .= '</projet>';
		}
		$xml .= '</partenaire>';
		//echo $this->spath.'fichiers/045.xml';
		//file_put_contents($this->spath.'fichiers/045.xml',$xml);
		$titre = 'xmlAllProjects';
		header("Content-Type: application/xml; charset=utf-8");
		//header("Content-disposition: attachment; filename=\"".$titre.".xml\"");
		echo $xml;
		die;
	}
	
	function _capital()
	{
		
		$this->autoFireHeader = false;
		$this->autoFireDebug = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
		
		
		
		// Chargement des librairies
		$this->xml2array = $this->loadLib('xml2array');
		
		$xmlstring = file_get_contents('http://www.capital.fr/wrapper-unilend.xml');
		
		$result = $this->xml2array->getArray($xmlstring);
		$content = $result['wrapper']['content'];
		$content = explode('<!--CONTENT_ZONE-->',$content);
		
		
		$this->haut = str_replace(array('<!--TITLE_ZONE_HEAD-->','<!--TITLE_ZONE-->'),array('Financement Participatif  : Prêtez aux entreprises françaises & Recevez des intérêts chaque mois','Financement participatif'),$content[0]);
		$this->bas = str_replace('<!--XITI_ZONE-->','Unilend-accueil',$content[1]);

	}
	
	function _challenges()
	{
		$this->autoFireHeader = false;
		$this->autoFireDebug = false;
		$this->autoFireHead = true;
		$this->autoFireFooter = false;
		
		$this->meta_title = "Financement Participatif  : Prêtez aux entreprises françaises & Recevez des intérêts chaque mois";
		
		$this->haut = file_get_contents('http://www.challenges.fr/partners/header.php');
		$this->bas = file_get_contents('http://www.challenges.fr/partners/footer.php');
	}
	
	function _lexpress()
	{
		$this->autoFireHeader = false;
		$this->autoFireDebug = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->companies = $this->loadData('companies');
		$this->companies_bilans = $this->loadData('companies_bilans');
		$this->companies_details = $this->loadData('companies_details');
		$this->companies_actif_passif = $this->loadData('companies_actif_passif');
		$this->projects_status_history = $this->loadData('projects_status_history');
		$this->projects = $this->loadData('projects');
		
		//traduction 
		$this->lng['landing-page'] = $this->ln->selectFront('landing-page',$this->language,$this->App);
		
		// Somme à emprunter min
		$this->settings->get('Somme à emprunter min','type');
		$this->sommeMin = $this->settings->value;
		
		// Somme à emprunter max
		$this->settings->get('Somme à emprunter max','type');
		$this->sommeMax = $this->settings->value;
		
		// Si on a une session d'ouverte on redirige
		if(isset($_SESSION['client'])){
			header('location:'.$this->lurl);
			die;
		}
		
		// page projet tri
		// 1 : terminé bientot
		// 2 : nouveauté
		//$this->tabOrdreProject[....] <--- dans le bootstrap pour etre accessible partout (page default et ajax)
		
		$this->ordreProject = 1; 
		$this->type = 0;		
		
		// Liste des projets en funding
		$this->lProjetsFunding = $this->projects->selectProjectsByStatus('50,60,80',' AND p.status = 0 AND p.display = 0',$this->tabOrdreProject[$this->ordreProject],0,6);
		
		// Nb projets en funding
		$this->nbProjects = $this->projects->countSelectProjectsByStatus('50,60,80',' AND p.status = 0 AND p.display = 0');
		
		$this->le_id_tree = 282;
		$this->le_slug = $this->tree->getSlug($this->le_id_tree,$this->language);
		
		// Recuperation du contenu de la page
		$contenu = $this->tree_elements->select('id_tree = "'.$this->le_id_tree.'" AND id_langue = "'.$this->language.'"');
		foreach($contenu as $elt)
		{
			$this->elements->get($elt['id_element']);
			$this->content[$this->elements->slug] = $elt['value'];
			$this->complement[$this->elements->slug] = $elt['complement'];
		}
		
		
	}
        
        
        function _lexpress_entreprise()
	{
		$this->autoFireHeader = false;
		$this->autoFireDebug = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->companies = $this->loadData('companies');
		$this->companies_bilans = $this->loadData('companies_bilans');
		$this->companies_details = $this->loadData('companies_details');
		$this->companies_actif_passif = $this->loadData('companies_actif_passif');
		$this->projects_status_history = $this->loadData('projects_status_history');
		$this->projects = $this->loadData('projects');
		
		//traduction 
		$this->lng['landing-page'] = $this->ln->selectFront('landing-page',$this->language,$this->App);
		
		// Somme à emprunter min
		$this->settings->get('Somme à emprunter min','type');
		$this->sommeMin = $this->settings->value;
		
		// Somme à emprunter max
		$this->settings->get('Somme à emprunter max','type');
		$this->sommeMax = $this->settings->value;
		
		// Si on a une session d'ouverte on redirige
		if(isset($_SESSION['client'])){
			header('location:'.$this->lurl);
			die;
		}
		
		// page projet tri
		// 1 : terminé bientot
		// 2 : nouveauté
		//$this->tabOrdreProject[....] <--- dans le bootstrap pour etre accessible partout (page default et ajax)
		
		$this->ordreProject = 1; 
		$this->type = 0;		
		
		// Liste des projets en funding
		$this->lProjetsFunding = $this->projects->selectProjectsByStatus('50,60,80',' AND p.status = 0 AND p.display = 0',$this->tabOrdreProject[$this->ordreProject],0,6);
		
		// Nb projets en funding
		$this->nbProjects = $this->projects->countSelectProjectsByStatus('50,60,80',' AND p.status = 0 AND p.display = 0');
		
		$this->le_id_tree = 282;
		$this->le_slug = $this->tree->getSlug($this->le_id_tree,$this->language);
		
		// Recuperation du contenu de la page
		$contenu = $this->tree_elements->select('id_tree = "'.$this->le_id_tree.'" AND id_langue = "'.$this->language.'"');
		foreach($contenu as $elt)
		{
			$this->elements->get($elt['id_element']);
			$this->content[$this->elements->slug] = $elt['value'];
			$this->complement[$this->elements->slug] = $elt['complement'];
		}
		
		
	}
	
	
	function _figaro()
	{
		$this->autoFireHeader = false;
		$this->autoFireDebug = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
		// Chargement des datas
		$this->projects = $this->loadData('projects');
		$this->clients = $this->loadData('clients');
		$this->clients_adresses = $this->loadData('clients_adresses');
		$this->companies = $this->loadData('companies');
		$this->companies_bilans = $this->loadData('companies_bilans');
		$this->companies_details = $this->loadData('companies_details');
		$this->companies_actif_passif = $this->loadData('companies_actif_passif');
		$this->projects_status_history = $this->loadData('projects_status_history');
		$this->projects = $this->loadData('projects');
		
		//traduction 
		$this->lng['landing-page'] = $this->ln->selectFront('landing-page',$this->language,$this->App);
		
		// Somme à emprunter min
		$this->settings->get('Somme à emprunter min','type');
		$this->sommeMin = $this->settings->value;
		
		// Somme à emprunter max
		$this->settings->get('Somme à emprunter max','type');
		$this->sommeMax = $this->settings->value;
		
		// Si on a une session d'ouverte on redirige
		if(isset($_SESSION['client'])){
			header('location:'.$this->lurl);
			die;
		}
		
		// page projet tri
		// 1 : terminé bientot
		// 2 : nouveauté
		//$this->tabOrdreProject[....] <--- dans le bootstrap pour etre accessible partout (page default et ajax)
		
		$this->ordreProject = 1; 
		$this->type = 0;		
		
		// Liste des projets en funding
		$this->lProjetsFunding = $this->projects->selectProjectsByStatus('50,60,80',' AND p.status = 0 AND p.display = 0',$this->tabOrdreProject[$this->ordreProject],0,6);
		
		// Nb projets en funding
		$this->nbProjects = $this->projects->countSelectProjectsByStatus('50,60,80',' AND p.status = 0 AND p.display = 0');
		
		$this->le_id_tree = 282;
		$this->le_slug = $this->tree->getSlug($this->le_id_tree,$this->language);
		
		// Recuperation du contenu de la page
		$contenu = $this->tree_elements->select('id_tree = "'.$this->le_id_tree.'" AND id_langue = "'.$this->language.'"');
		foreach($contenu as $elt)
		{
			$this->elements->get($elt['id_element']);
			$this->content[$this->elements->slug] = $elt['value'];
			$this->complement[$this->elements->slug] = $elt['complement'];
		}
		
		
	}
	
	// Enregistrement et lecture du pdf cgv
	function _pdf_cgv_preteurs()
	{
		// Si connecté
		if($this->clients->checkAccess()){
			
			// on regarde si c'est bien un preteur
			$this->clients->checkStatusPreEmp($this->clients->status_pre_emp,'preteur',$this->clients->id_client);
			
			// CGU
			$this->settings->get('Lien conditions generales inscription preteur particulier','type');
			$id_tree_cgu = $this->settings->value;
			
			// liste des cgv accpeté
			$listeAccept = $this->acceptations_legal_docs->select('id_client = '.$this->clients->id_client,'added DESC');
			$id_tree_cgu = $listeAccept[0]['id_legal_doc'];
			
			$contenu = $this->tree_elements->select('id_tree = "'.$id_tree_cgu.'" AND id_langue = "'.$this->language.'"');
			foreach($contenu as $elt)
			{
				$this->elements->get($elt['id_element']);
				$this->content[$this->elements->slug] = $elt['value'];
				$this->complement[$this->elements->slug] = $elt['complement'];
			}
			
			// si c'est un ancien cgv de la liste on lance le pdf
			if(in_array($id_tree_cgu,array(92,95,93,254,255))){
				$name = $this->content['pdf-cgu'];

				header("Content-disposition: attachment; filename=".$name);
				header("Content-Type: application/force-download");
				@readfile($this->surl.'/var/fichiers/'.$this->content['pdf-cgu']);
			}
			else
			{
				// Path du dossier
				$pathDossier = $this->path.'protected/pdf/cgv_preteurs/'.$this->clients->id_client;
	
			
				// Si exite pas on créer le dossier
				if(!file_exists($pathDossier)){
					mkdir($pathDossier);
					chmod($pathDossier,0777);
				}
				
				$path 		= $this->path.'protected/pdf/cgv_preteurs/'.$this->clients->id_client.'/';
				$slug 		= $this->clients->hash;
				$urlsite 	= $this->lurl.'/cgv_preteurs/'.$this->clients->hash;
				$name 		= 'cgv_preteurs';
				$vraisNom 	= 'CGV-UNILEND-PRETEUR-'.$this->clients->id_client.'-'.$id_tree_cgu;
				$param 		= $id_tree_cgu;
				$signe 		= '';
				//$entete = $this->lurl.'/pdf/entete/';
				$entete = '';
				$piedpage = $this->lurl.'/pdf/piedpage_cgv/';
				$piedpage = '';
				
				$this->tree->get(array('id_tree' => $id_tree_cgu,'id_langue' => 'fr'));
				
				$dateUpdate = strtotime($this->tree->updated);
				$month = $this->dates->tableauMois['fr'][date('n',$dateUpdate)];
				
				$footer = '&FooterTextLeft="Conditions générales de vente prêteur - Version du '.date('d',$dateUpdate).' '.$month.' '.date('Y',$dateUpdate).'"';
				//$footer = '';
				$paramCovertapi		= '&MarginLeft=25&MarginRight=25&MarginBottom=10';
				
				// On lance la fonction pdf qui fait tout
				$this->Web2Pdf->convert($path,$slug,$urlsite,$name,$vraisNom,$param,$signe,$entete,$piedpage,'',$paramCovertapi);
			}
		}
		die;	
	}
	
	// lecture page du cgv en html
	function _cgv_preteurs()
	{
		// restriction ip
		//if($_SERVER['REMOTE_ADDR'] != '93.26.42.99'){
			//die;
		//}
		
		// DATAS
		$this->pays = $this->loadData('pays_v2');
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		$this->companies = $this->loadData('companies');
		
		// CGU
		$this->settings->get('Lien conditions generales inscription preteur particulier','type');
		$id_tree_cgu = $this->settings->value;

		// Recuperation du contenu de la page
		$contenu = $this->tree_elements->select('id_tree = "'.$id_tree_cgu.'" AND id_langue = "'.$this->language.'"');
		foreach($contenu as $elt)
		{
			$this->elements->get($elt['id_element']);
			$this->content[$this->elements->slug] = $elt['value'];
			$this->complement[$this->elements->slug] = $elt['complement'];
		}
		
		
		// Si connecté ou si on a le hash du client
		if($this->clients->checkAccess() || isset($this->params[0]) && $this->clients->get($this->params[0],'hash')){
			
			$this->clients_adresses->get($this->clients->id_client,'id_client');
			
			// Si c'est le pdf qui appel on retire les truc en trop
			if(isset($this->params[0]) && $this->params[0] != 'morale' && $this->params[0] != 'nosign'){
				// On masque les Head, header et footer originaux plus le debug
				$this->autoFireHeader = false;
				$this->autoFireHead = true;
				$this->autoFireFooter = false;
				$this->autoFireDebug = false;	
			}
			
			
			// Particulier
			if(in_array($this->clients->type,array(1,3))){
			
				// Date naissance
				$naissance = date('d/m/Y',strtotime($this->clients->naissance));

				// Pays fiscal
				if($this->clients_adresses->id_pays_fiscal == 0) $this->clients_adresses->id_pays_fiscal = 1;
				$this->pays->get($this->clients_adresses->id_pays_fiscal,'id_pays');
				$pays_fiscal = $this->pays->fr;
				
				if(isset($this->params[0]) && $this->params[0] == 'nosign')
				{
					$dateAccept = '';
				}
				else{
					// liste des cgv accpeté
					$listeAccept = $this->acceptations_legal_docs->select('id_client = '.$this->clients->id_client,'added DESC');
					$dateAccept = 'Signé électroniquement le '.date('d/m/Y',strtotime($listeAccept[0]['added'])); // date dernier CGV
				}
				$variables = array('[Civilite]','[Prenom]','[Nom]','[date]','[ville_naissance]','[adresse_fiscale]','[date_validation_cgv]');
				
				// Contenu
				$contentVariables = array(
					$this->clients->civilite,
					$this->clients->prenom,
					$this->clients->nom,
					$naissance,
					$this->clients->ville_naissance,
					$this->clients_adresses->adresse_fiscal.', '.$this->clients_adresses->ville_fiscal.', '.$this->clients_adresses->cp_fiscal.', '.$pays_fiscal,
					$dateAccept);
				
				
				$this->mandat_de_recouvrement = str_replace($variables,$contentVariables,$this->content['mandat-de-recouvrement']);
			}
			// Entreprise
			else{
				
				$this->companies->get($this->clients->id_client,'id_client_owner');
				
				// Pays fiscal
				if($this->companies->id_pays == 0) $this->companies->id_pays = 1;
				$this->pays->get($this->companies->id_pays,'id_pays');
				$pays_fiscal = $this->pays->fr;
				
				if(isset($this->params[0]) && $this->params[0] == 'nosign')
				{
					$dateAccept = '';
				}
				else{
					// liste des cgv accpeté
					$listeAccept = $this->acceptations_legal_docs->select('id_client = '.$this->clients->id_client,'added DESC');
					$dateAccept = 'Signé électroniquement le '.date('d/m/Y',strtotime($listeAccept[0]['added'])); // date dernier CGV
				}
				$variables = array('[Civilite]','[Prenom]','[Nom]','[Fonction]','[Raison_sociale]','[adresse_fiscale]','[date_validation_cgv]');
				
				$contentVariables = array(
					$this->clients->civilite,
					$this->clients->prenom,
					$this->clients->nom,
					$this->clients->fonction,
					$this->companies->name,
					$this->companies->adresse1.', '.$this->companies->zip.', '.$this->companies->city.', '.$pays_fiscal,
					$dateAccept);
					
				$this->mandat_de_recouvrement = str_replace($variables,$contentVariables,$this->content['mandat-de-recouvrement-personne-morale']);
			}
		}
		// Si pas connecté
		else{
			
			/*'[Civilité]',
			'[Prénom]',
			'[Nom]',
			'né(e) le [date]',
			' à [ville de naissance]',
			'[adresse, ville de residence fiscale, code postal fiscale, pays fiscal (2e ligne pour l’adresse si besoin……………….]',
			'[date de validation des cgv]'*/
			
			if(isset($this->params[0]) && $this->params[0] == 'morale'){
				$variables = array('[Civilite]','[Prenom]','[Nom]','[Fonction]','[Raison_sociale]','[adresse_fiscale]','[date_validation_cgv]');
				$tabVariables = explode(';',$this->content['contenu-variables-par-defaut-morale']);
				$contentVariables = $tabVariables;
				//print_r($contentVariables);
				//print_r($variables);
				
				$this->mandat_de_recouvrement = str_replace($variables,$contentVariables,$this->content['mandat-de-recouvrement-personne-morale']);
				
			}
			else{
				$variables = array('[Civilite]','[Prenom]','[Nom]','[date]','[ville_naissance]','[adresse_fiscale]','[date_validation_cgv]');
				$tabVariables = explode(';',$this->content['contenu-variables-par-defaut']);
				
				$contentVariables = $tabVariables;
				$this->mandat_de_recouvrement = str_replace($variables,$contentVariables,$this->content['mandat-de-recouvrement']);
			}
			
						
			
		}
		
		
		
	}
	
}