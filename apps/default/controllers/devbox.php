<?php

class devboxController extends bootstrap
{
	var $Command;
	
	function devboxController($command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
		
		// on interdit les autres ip sur le contoleur test sauf pour la page default
		if($command->Function != 'altares' && $_SERVER['REMOTE_ADDR'] != '93.26.42.99')
		{
			header("location:".$this->lurl);
			die;
		}
		
		
		
	}
	
	function _maj_indexation_rejet_loan()
	{
		$this->indexage_vos_operations = $this->loadData('indexage_vos_operations');
		$L_ligne_a_traiter = $this->indexage_vos_operations->select('id_projet = 0 AND type_transaction = 2 AND date_operation > "2015-01-01 00:00:00"');
		print_r(count($L_ligne_a_traiter));
		//die;
		print_r("<br />");
		die;
		foreach( $L_ligne_a_traiter as $ligne)
		{
			$title = $id_project = "";
			$sql = 'SELECT l.id_project, 
						   (SELECT p.title FROM projects p WHERE p.id_project = l.id_project) as title
					FROM transactions t
						LEFT JOIN loans l ON (l.id_loan = t.id_loan_remb)
					WHERE t.id_transaction = '.$ligne['id_transaction'].'
					LIMIT 1'
					;
		
			$resultat = $this->bdd->query($sql);
			$result = array();
			while($record = $this->bdd->fetch_array($resultat))
			{
				$result[] = $record;
			}
			
			$title = $result[0]['title'];
			$id_project = $result[0]['id_project'];
			
			print_r(utf8_decode($title).' - '.$id_project);
			print_r('<br />');
			
			$this->indexage_vos_operations_temp = $this->loadData('indexage_vos_operations');
			$this->indexage_vos_operations_temp->get($ligne['id'], 'id');
			if($this->indexage_vos_operations_temp->id_projet == 0 && $this->indexage_vos_operations_temp->libelle_projet == "")
			{
				$this->indexage_vos_operations_temp->id_projet = $id_project;
				$this->indexage_vos_operations_temp->libelle_projet = $title;
				$this->indexage_vos_operations_temp->update();
				
			}
			else
			{
				echo "erreur, id_indexation : ".$ligne['id'];
				print_r('<br />');
			}
		}
		
		die;
	}
        
        
        
        // lors de la MEP, remb auto on a desactivé l'envoi des mails pour tester avant et donc ici on renvoi les mails qui étaient sensé pârtir
        function _envoi_mail_facture_emprunteur()
        {
            $projects_remb= $this->loadData('projects_remb');
            
            // Une seule echeance emprunteur à la fois
            $lProjetsAremb = $projects_remb->select('status = 1 AND LEFT(date_remb_preteurs,10) = "' . date('Y-m-d') . '" AND id_project IN (1183, 818, 3823)', '', 0, 3);
            
                        
            // si remb auto preteur autorisé
            if ($lProjetsAremb != false)
            {
                foreach ($lProjetsAremb as $r)
                {
                    if($r['id_project'] == 1183)
                    {
                        
                    }
                    elseif($r['id_project'] == 818)
                    {
                        
                    }
                    elseif($r['id_project'] == 3823)
                    {
                        
                    }
                    
                    // Chargement des datas
                    $emprunteur = $this->loadData('clients');
                    $projects = $this->loadData('projects');
                    $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
                    $companies = $this->loadData('companies');
                    $projects_status_history = $this->loadData('projects_status_history');

                    // On recup les infos de l'emprunteur
                    $projects->get($r['id_project'], 'id_project');
                    $companies->get($projects->id_company, 'id_company');
                    $emprunteur->get($companies->id_client_owner, 'id_client');
                    $echeanciers_emprunteur->get('id_project = "' . $r['id_project'] . '" AND ordre = "' . $r['ordre'] . '"');
                    
                    
                    // Date du dernier statut
                    $dernierStatut = $projects_status_history->select('id_project = ' . $r['id_project'], 'added DESC', 0, 1);
                    $dateDernierStatut = $dernierStatut[0]['added'];

                    // Format date
                    $timeAdd = strtotime($dateDernierStatut);
                    $day = date('d', $timeAdd);
                    $month = $this->dates->tableauMois['fr'][date('n', $timeAdd)];
                    $year = date('Y', $timeAdd);
                    
                        
                    /////////////// MAIL FACTURE REMBOURSEMENT EMPRUNTEUR ///////////////////
                    //********************************//
                    //*** ENVOI DU MAIL FACTURE ER ***//
                    //********************************//
                    // Recuperation du modele de mail
                    $this->mails_text->get('facture-emprunteur-remboursement', 'lang = "' . $this->language . '" AND type');


                    // FB
                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;


                    // Twitter
                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    // Variables du mailing
                    $varMail = array(
                        'surl' => $this->surl,
                        'url' => $this->furl,
                        'prenom' => $emprunteur->prenom,
                        'pret' => number_format($projects->amount, 2, ',', ' '),
                        'entreprise' => stripslashes(trim($companies->name)),
                        'projet-title' => $projects->title,
                        'compte-p' => $this->furl,
                        'projet-p' => $this->furl . '/projects/detail/' . $projects->slug,
                        'link_facture' => $this->furl . '/pdf/facture_ER/' . $emprunteur->hash . '/' . $r['id_project'] . '/' . $r['ordre'],
                        'datedelafacture' => $day . ' ' . $month . ' ' . $year,
                        'mois' => strtolower($this->dates->tableauMois['fr'][date('n')]),
                        'annee' => date('Y'),
                        'lien_fb' => $lien_fb,
                        'lien_tw' => $lien_tw);

                    // Construction du tableau avec les balises EMV
                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);
                    // Attribution des données aux variables
                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);
                    // Envoi du mail
                    $this->email = $this->loadLib('email', array());
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                    if ($this->Config['env'] == 'prod')
                    {
                        //$this->email->addBCCRecipient('nicolas.lesur@unilend.fr');
                        //$this->email->addBCCRecipient('d.nandji@equinoa.com');
                    }
                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($this->Config['env'] == 'prod') // nmp
                    {
                        
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, trim($companies->email_facture), $tabFiler);
                        //Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, trim("k.levezier@equinoa.com"), $tabFiler);
                        // Injection du mail NMP dans la queue
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    }
                    else // non nmp
                    {
                        $this->email->addRecipient(trim($companies->email_facture));
                        //Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                       
                }
            }
            
        }
}