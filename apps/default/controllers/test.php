<?php

class testController extends bootstrap
{
	var $Command;
	
	function testController($command,$config,$app)
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
	
	function _ip(){
		
		echo $_SERVER['REMOTE_ADDR'];
		die;	
	}
	
	function _default()
	{
		
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;

		// Chargement des librairies
		$this->remb = $this->loadLib('remb');
		$this->echeanciers = $this->loadData('echeanciers');
		
		if(isset($_POST['send']))
		{
			$capital = $_POST['capital'];
			$nbecheances = $_POST['nbecheances'];
			$taux = $_POST['taux'];
			$commission = $_POST['commission'];
			$tva = $_POST['tva'];
		}
		else
		{
			$capital = 100000;
			$nbecheances = 36;
			$taux = 0.05;
			$commission = 0.01;
			$tva = 0.196;
		}
		
		$tabl = $this->remb->echeancier($capital,$nbecheances,$taux,$commission,$tva);
		
		$this->donneesEcheances = $tabl[1];
		$this->echeancier = $tabl[2];
		
		
		$this->echeancierold2 = $this->remb->echeancierold2($capital,$nbecheances,$taux,$commission,$tva);
		
		$this->echeancierold = $this->remb->echeancierold($capital,$nbecheances,$taux,$commission,$tva);
		
		
		
	}
	
	function _altares()
	{
		ini_set('default_socket_timeout', 60);
		
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
		// $ip_client
		$this->settings->get('IP autorisée','type');
		$ip_client = $this->settings->value;
		
		// on interdit les ip differentes du client et de equinoa
		if($_SERVER['REMOTE_ADDR'] != $ip_client && $_SERVER['REMOTE_ADDR'] != '93.26.42.99')
		{
			header("location:".$this->lurl);
			die;
		}
		
		
		// on interdit les ip differentes du client et de equinoa
		/*if($_SERVER['REMOTE_ADDR'] != '86.217.44.230' && $_SERVER['REMOTE_ADDR'] != '93.26.42.99' && $_SERVER['REMOTE_ADDR'] != '90.61.151.251' && $_SERVER['REMOTE_ADDR'] != '82.120.2.73')
		{
			header("location:".$this->lurl);
			die;
		}*/
		

		$url = 'http://iws-sffpme.edgeteam.fr/services/MozaikEligibilityObject?wsdl';
			 //'http://iws-sffpme.edgeteam.fr/services/MozaikEligibilityObject?wsdl'

		// Creation objet Soap 
		$client = new SoapClient($url);
		//print_r($client->__getFunctions());
		var_dump($client->__getFunctions());
		//print_r($client->__getTypes());
		// Appel WS
		
		if(isset($_POST['siren']) && $_POST['siren'] != '')
		{
			$siren = $_POST['siren'];
		}
		else
		{
			
		}

		$result = $client->__soapCall("getEligibility",array(
			array(
				"identification"=>"U2012008557|45c8586a626ddabd233951066138d0efa7f4eb9d",
				"refClient"=>"unilend",
				"siren"=>"$siren"
			)
		)); 
		
		?>
        <form action="" method="post">
            <table>
                <tr>
                    <td><label>siren : </label></td>
                    <td><input type="text" name="siren" value="<?=(isset($_POST['siren'])?$_POST['siren']:'')?>"/></td>
                </tr>
                <tr>
                <td><label>valider : </label></td>
                <td><input type="submit" name="send" value="Valider"/></td>
            </tr>
            </table>
        </form>
        <?
		//$test = $result->return->myInfo->bilans[0]->bilanRetraiteInfo;
		/*$montant1 = $test->posteActifList[1]->montant;
		$montant2 = $test->posteActifList[2]->montant;
		$montant3 = $test->posteActifList[3]->montant;
		echo $montant1.'<br>';
		echo $montant2.'<br>';
		echo $montant3.'<br>';
		echo $montant = $montant1+$montant2+$montant3;*/
		/*echo '<br>---------------------<br>';
		echo 'SIREN<br>';
		echo '394030183 = OUI<br>';
		echo '414724633 = Pas de bilan<br>';
		echo '267606929 = Pas de RCS';
		echo '<br>---------------------<br>';*/
		
		
		echo '<pre>';		
		print_r($result->return);
		echo '</pre>';
	}
	
	function _altares2()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
		// On recup la lib et le reste payline
		require_once($this->path.'public/default/nusoap/lib/nusoap.php');
		
		$wsdl="http://iws-sffpme.edgeteam.fr/services/MozaikEligibilityObject?wsdl";
		$client=new soapclient($wsdl, 'wsdl');
		var_dump($client->__getFunctions());
		
		/*$param=array(
				"identification"=>"U2012008557|45c8586a626ddabd233951066138d0efa7f4eb9d",
				"refClient"=>"unilend",
				"siren"=>"394030183");
		echo $client->call('add', $param);*/	
	}
	
	function _test_altares_error()
	{
		 // Login altares
        $this->settings->get('Altares login', 'type');
        $login = $this->settings->value;
        // Mdp altares
        $this->settings->get('Altares mot de passe', 'type');
        $mdp = $this->settings->value; // mdp en sha1
        // Url wsdl
        $this->settings->get('Altares wsdl', 'type');
        $this->wsdl = $this->settings->value;
        // Identification
        $this->identification = $login . '|' . $mdp;
		
		
		$siren = '483209383';
		
	
		 // Web Service Altares
		$result = '';
		try
		{
			$result = $result = $this->ficelle->ws($this->identification, $siren);
			
		}
		catch (Exception $e)
		{
			mail('d.courtier@equinoa.com','[ALERTE] ERREUR ALTARES 2','Date '.date('Y-m-d H:i:s').''.$e->getMessage());
			error_log("[".date('Y-m-d H:i:s')."] ".$e->getMessage(), 3, $this->path.'/log/error_altares.txt');
		}
		
		
		// Verif si erreur
		$exception = $result->exception;
		
		if($result->exception != false){

			$erreur = 'Siren fourni : '.$siren.' | '.$result->exception->code.' | '.$result->exception->description.' | '.$result->exception->erreur;
			mail('d.courtier@equinoa.com','[ALERTE] ERREUR ALTARES 1','Date '.date('Y-m-d H:i:s').''.$erreur);
			error_log("[".date('Y-m-d H:i:s')."] ".$erreur."\n", 3, $this->path.'/log/error_altares.txt');
		}
		
		
		die;
	}
	
	function _payline()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		
		// On recup la lib et le reste payline
		require_once($this->path.'protected/payline/include.php');
		
		if(isset($_POST['send_payment']))
		{
			$array = array();
			$payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
			$payline->returnURL = RETURN_URL;
			$payline->cancelURL = CANCEL_URL;
			$payline->notificationURL = NOTIFICATION_URL;
			
			// PAYMENT
			$array['payment']['amount'] = $_POST['amount'];
			$array['payment']['currency'] = $_POST['currency'];
			$array['payment']['action'] = PAYMENT_ACTION;
			$array['payment']['mode'] = PAYMENT_MODE;
			
			// ORDER
			$array['order']['ref'] = $_POST['ref'];
			$array['order']['amount'] = $_POST['amount'];
			$array['order']['currency'] = $_POST['currency'];
			
			// CONTRACT NUMBERS
			$array['payment']['contractNumber'] = CONTRACT_NUMBER;
			$contracts = explode(";",CONTRACT_NUMBER_LIST);
			$array['contracts'] = $contracts;
			$secondContracts = explode(";",SECOND_CONTRACT_NUMBER_LIST);
			$array['secondContracts'] = $secondContracts;
			
			// EXECUTE
			//$result = $payline->do_webpayment($array);
			$result = $payline->doWebPayment($array);
			
			// RESPONSE
			if(isset($_POST['debug'])){
				
				echo '<H3>REQUEST</H3>';
				print_a($array, 0, true);
				echo '<H3>RESPONSE</H3>';
				print_a($result, 0, true);
				
			}
			else{
				if(isset($result) && $result['result']['code'] == '00000'){
					header("location:".$result['redirectURL']);
					exit();
				}
				elseif(isset($result)) {
				echo 'ERROR : '.$result['result']['code']. ' '.$result['result']['longMessage'].' <BR/>';
				}
			}	
			
		}
		
		?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
        <html>
        <head>
        <meta name="generator" content="HTML Tidy, see www.w3.org">
        <title></title>
        </head>
        <body>
        <form action="http://unilend.dev2.equinoa.net/test/payline" method="post" class="payline-form">
        <fieldset>
        <h4>Do Web Payment minimal informations</h4>
        
        <div class="row"><label for="ref">Order reference</label> <input
        type="text" name="ref" id="ref" value="<?php echo 'PHP-'.time()?>"> <span class=
        "help">(required)</span></div>
        
        <div class="row"><label for="amount">Amount</label> <input type=
        "text" name="amount" id="amount" value="33300"> <span class=
        "help">(required)</span></div>
        
        <div class="row"><label for="currency">Currency</label> <select
        name="currency" id="currency">
        <option value="978">EURO (EUR)</option>
        <option value="840">US DOLLAR (USD)</option>
        <option value="756">FRANC SUISSE (CHF)</option>
        <option value="826">STERLING (GBP)</option>
        <option value="124">CANADIAN DOLLAR (CAD)</option>
        </select></div>
        
        <div class="row"><label for="debug">MODE DEBUG</label> <input type=
        "checkbox" name="debug" id="debug" value="YES"></div>
        </fieldset>
        <input type="hidden" name="send_payment">
        <input type="submit" name="submit" class="submit" value=
        "Start payment process"></form>
        </body>
        </html>
        <?
		
	}
	
	function _project1()
	{
		
	}
	
	function _project2()
	{
		
	}
	
	function _project3()
	{
		
	}
	
	function _mouvement()
	{
		
	}
	
	function _profile()
	{
		
	}
	function _payment()
	{
		mail('d.courtier@equinoa.com','test notification payline','ici la notification');
	}
	function _test()
	{
		$val ='a:9:{s:6:"result";a:3:{s:4:"code";s:5:"02304";s:12:"shortMessage";s:19:"Invalid Transaction";s:11:"longMessage";s:35:"No transaction found for this token";}s:11:"transaction";a:8:{s:2:"id";s:0:"";s:4:"date";s:0:"";s:12:"isDuplicated";N;s:15:"isPossibleFraud";N;s:11:"fraudResult";N;s:11:"explanation";N;s:12:"threeDSecure";s:1:"N";s:5:"score";N;}s:7:"payment";a:6:{s:6:"amount";N;s:8:"currency";s:3:"978";s:6:"action";s:3:"101";s:4:"mode";s:3:"CPT";s:14:"contractNumber";s:0:"";s:18:"differedActionDate";s:0:"";}s:13:"authorization";a:2:{s:6:"number";s:0:"";s:4:"date";s:0:"";}s:15:"privateDataList";a:0:{}s:22:"authentication3DSecure";a:6:{s:2:"md";N;s:3:"xid";N;s:3:"eci";N;s:4:"cavv";N;s:13:"cavvAlgorithm";N;s:10:"vadsResult";N;}s:4:"card";a:0:{}s:5:"order";a:6:{s:3:"ref";s:3:"125";s:6:"origin";s:0:"";s:7:"country";s:0:"";s:6:"amount";s:5:"10000";s:8:"currency";s:3:"978";s:4:"date";s:8:"17/10/13";}s:5:"media";N;}';
		$tabl = unserialize($val);
		echo 'ici<br>';
		echo '<pre>';
		print_r($tabl);
		echo '</pre>';
	}
	
	function _pdf()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		$this->Web2Pdf->convert($this->lurl.'/test/mandat','mandat');	
	}
	
	function _mandat()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
	}
	
	function _testimg()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;

	}
	
	function _txt()
	{
		$url = $this->surl."/var/fichiers/SFPMEI_Virements_SEPA_Recus.txt";

		$handle = @fopen($url,"r"); //lecture du fichier
		if ($handle) {
			$i = 0;
			while (($ligne = fgets($handle)) !== false) {
				
				
				echo $i.' - '.$ligne.'<br>';
				

				$codeEnregi = substr($ligne,0,2);
				$codeBanque = substr($ligne,2,5);
				$codeOpBNPP = substr($ligne,7,4);
				$codeGuichet = substr($ligne,11,5);
				$codeDevises = substr($ligne,16,3);
				$nbDecimales = substr($ligne,19,1);
				$zoneReserv1 = substr($ligne,20,1);
				$numCompte = substr($ligne,21,11);
				$codeOpInterbancaire = substr($ligne,32,2);
				$dateEcriture = substr($ligne,34,6);
				$codeMotifRejet = substr($ligne,40,2);
				$dateValeur = substr($ligne,42,6);
				$libelleOpe = substr($ligne,48,31);
				$zoneReserv2 = substr($ligne,79,2);
				$numEcriture = substr($ligne,81,7);
				$codeExoneration = substr($ligne,88,1);
				$zoneReserv3 = substr($ligne,89,1);
				$montant = substr($ligne,90,14);
				$refOp = substr($ligne,104,16);
				
				
				echo ' ------------- <br>';
				echo '1 codeEnregi :'.$codeEnregi.'<br>';
				echo '2 codeBanque :'.$codeBanque.'<br>';
				echo '3 codeOpBNPP :'.$codeOpBNPP.'<br>';
				echo '4 codeGuichet :'.$codeGuichet.'<br>';
				echo '5 codeDevises :'.$codeDevises.'<br>';
				echo '6 nbDecimales :'.$nbDecimales.'<br>';
				echo '7 zoneReser :'.$zoneReser.'<br>';
				echo '8 numCompte :'.$numCompte.'<br>';
				echo '9 codeOpInterbancaire :'.$codeOpInterbancaire.'<br>';
				echo '10 dateEcriture :'.$dateEcriture.'<br>';
				echo '11 codeMotifRejet :'.$codeMotifRejet.'<br>';
				echo '12 dateValeur :'.$dateValeur.'<br>';
				echo '13 libelleOpe :'.$libelleOpe.'<br>';
				echo '14 zoneReserv2 :'.$zoneReserv2.'<br>';
				echo '15 numEcriture :'.$numEcriture.'<br>';
				echo '16 codeExoneration :'.$codeExoneration.'<br>';
				echo '17 zoneReserv3 :'.$zoneReserv3.'<br>';
				echo '18 montant :'.$montant.'<br>';
				echo '19 refOp :'.$refOp.'<br>';
				echo ' ------------- <br>';
				
				$i++;
			}
			if (!feof($handle)) {
				echo "Erreur: fgets() a échoué\n";
			}
			fclose($handle);
		}

	}
	
	function _testmail()
	{
		echo 'test mail';
		//************************************//
		//*** ENVOI DU MAIL GENERATION MDP ***//
		//************************************//

		// Recuperation du modele de mail
		$this->mails_text->get('unilend-test','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		
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
		'prenom' => 'damién',
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
		$this->email->addRecipient(trim('d.courtier@equinoa.com'));
		//$this->email->addBCCRecipient($this->clients->email);
		
		$this->email->setSubject(stripslashes($sujetMail));
		$this->email->setHTMLBody(stripslashes($texteMail));
		Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.courtier@equinoa.com',$tabFiler);
		
		// Injection du mail NMP dans la queue
		$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
		
		// fin mail	
	}
	
	
	function _pagetestFunde()
	{
		// Si le solde a été atteint on envoie un mail a l'emprunteur
		if($_SERVER['REMOTE_ADDR'] == '93.26.42.99')
		{

			// Chargement des datas
			$this->projects = $this->loadData('projects');
			$this->bids = $this->loadData('bids');
			$this->companies = $this->loadData('companies');
			
			// Heure fin periode funding
			$this->settings->get('Heure fin periode funding','type');
			$this->heureFinFunding = $this->settings->value;
			
			$this->projects->get($this->params[0],'id_project');
			
			$this->companies->get($this->projects->id_company,'id_company');
			
			// la sum des encheres
			$soldeBid = $this->bids->getSoldeBid($this->projects->id_project);
	
			// Reste a payer
			$montantEmprunt = $this->projects->amount;
		
			echo '$soldeBid : '.$soldeBid.'<br>';
			echo '$montantEmprunt : '.$montantEmprunt.'<br>';
		
			if($soldeBid >= $montantEmprunt)
			{
				echo 'ok';
				if($this->projects->status_solde == 0)
				{
					echo 'aaa';
					// on met a jour le statut du solde pour eviter d'avoir a renvoyer le mail a chaque fois
					$this->projects->status_solde = 1;
					$this->projects->update();
					
					// intervalle aujourd'hui et retrait
					$inter = $this->dates->intervalDates(date('Y-m-d H:i:s'),$this->projects->date_retrait.' '.$this->heureFinFunding);
					if($inter['mois']>0) $dateRest = $inter['mois'].' mois';
					elseif( $inter['jours']>0)$dateRest = $inter['jours'].' jours';
					elseif( $inter['heures']>0)$dateRest = $inter['heures'].' heures';
					elseif( $inter['minutes']>0)$dateRest = $inter['minutes'].' minutes';
					
					
					//**************************************//
					//*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
					//**************************************//
					
					// Recuperation du modele de mail
					$this->mails_text->get('emprunteur-dossier-funde','lang = "'.$this->language.'" AND type');
					
					// emprunteur
					$e = $this->loadData('clients');
					$e->get($this->companies->id_client_owner,'id_client');
					
					// taux moyen
					//$taux_moyen = $this->bids->getAVG($this->projects->id_project,'rate');
					
					// moyenne pondéré
					$montantHaut = 0;
					$montantBas = 0;
					foreach($this->bids->select('id_project = '.$this->projects->id_project.' AND status = 0' ) as $b)
					{
						$montantHaut += ($b['rate']*($b['amount']/100));
						$montantBas += ($b['amount']/100);
					}
					$taux_moyen = ($montantHaut/$montantBas);
					
					$taux_moyen = number_format($taux_moyen, 2, ',', ' ');
					
					// Variables du mailing
					$surl = $this->surl;
					$url = $this->lurl;
					$projet = $this->projects->title;
					$link_mandat = $this->surl.'/images/default/mandat.jpg';
					$link_pouvoir = $this->lurl.'/pdf/pouvoir/'.$e->hash.'/'.$this->projects->id_project;
					
					
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
					'prenom_e' => utf8_decode($e->prenom),
					'taux_moyen' => $taux_moyen,
					'link_compte_emprunteur' => $this->lurl.'/projects/detail/'.$this->projects->slug,
					'temps_restant' => $dateRest,
					'link_mandat' => $link_mandat,
					'link_pouvoir' => $link_pouvoir,
					'projet' => $projet,
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
					//$this->email->addRecipient(trim('courtier.damien@gmail.com'));
					//$this->email->addBCCRecipient($this->clients->email);
					
					$this->email->setSubject(stripslashes($sujetMail));
					$this->email->setHTMLBody(stripslashes($texteMail));
					Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.courtier@equinoa.com',$tabFiler);
								
					// Injection du mail NMP dans la queue
					$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
				
				
				
				
					//*********************************************//
					//*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//
					//*********************************************//
					
					//$this->settings->get('Adresse notifications','type');
					//$destinataire = $this->settings->value;
					$destinataire = 'd.courtier@equinoa.com';
					
					$this->nbPeteurs = $this->bids->counter('id_project = '.$this->projects->id_project);
					
					// Recuperation du modele de mail
					$this->mails_text->get('notification-projet-funde-a-100','lang = "'.$this->language.'" AND type');
					
					// Variables du mailing
					$surl = $this->surl;
					$url = $this->lurl;
					$id_projet = $this->projects->id_project;
					$title_projet = $this->projects->title;
					$nbPeteurs = $this->nbPeteurs;
					$tx = $taux_moyen;
					$periode = $dateRest;
					
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
					//$this->email->addBCCRecipient('');
				
					$this->email->setSubject('=?UTF-8?B?'.base64_encode($sujetMail).'?=');
					$this->email->setHTMLBody($texteMail);
					Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
					// fin mail
				
				}
			}
			
		}
	}
	
	function _testbids()
	{
		// Si le solde a été atteint on envoie un mail a l'emprunteur
		if($_SERVER['REMOTE_ADDR'] == '93.26.42.99')
		{

			// Chargement des datas
			$this->bids = $this->loadData('bids');
			$this->lenders_accounts = $this->loadData('lenders_accounts');
			$this->wallets_lines = $this->loadData('wallets_lines');
			$this->transactions = $this->loadData('transactions');
			$this->notifications = $this->loadData('notifications');
			
			// Heure fin periode funding
			$this->settings->get('Heure fin periode funding','type');
			$this->heureFinFunding = $this->settings->value;
			
			// On recup le projet
			$projects = $this->loadData('projects');
			$projects->get(55,'id_project');
			
			// on recup la companie
			$companies = $this->loadData('companies');
			$companies->get($projects->id_company,'id_company');
			
			$inter = $this->dates->intervalDates(date('Y-m-d h:i:s'),$projects->date_retrait.' '.$this->heureFinFunding.':00'); 			
			if($inter['mois']>0) $tempsRest = $inter['mois'].' mois';
			elseif($inter['jours']>0) $tempsRest = $inter['jours'].' jours';
			elseif($inter['heures']>0 && $inter['minutes'] >= 120) $tempsRest = $inter['heures'].' heures';
			elseif($inter['minutes']>0 && $inter['minutes']< 120) $tempsRest = $inter['minutes'].' min';
			
			$this->lEnchere = $this->bids->select('id_project = 55','rate ASC,added ASC');
			$leSoldeE = 0;
			foreach($this->lEnchere as $k => $e)
			{
				// on parcour les encheres jusqu'au montant de l'emprunt
				if($leSoldeE < 20000)
				{
					// le montant preteur (x100)
					$amount = $e['amount'];
					
					// le solde total des encheres
					$leSoldeE += ($e['amount']/100);
					
				}
				else
				{
					echo '<br>------------<br>';
					echo 'lender : '.$e['id_lender_account'].'<br>';
					echo 'montant preteur : '.($e['amount']/100).'<br>';
					echo '$leSoldeE : '.$leSoldeE.'<br>';
					echo 'tx preteur : '.($e['rate']).'<br>';
					
					
					
					
					// On recup lenders_accounts
					$this->lenders_accounts->get($e['id_lender_account'],'id_lender_account');
										
					// On enregistre la transaction
					$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
					$this->transactions->montant = $e['amount'];
					$this->transactions->id_langue = 'fr';
					$this->transactions->date_transaction = date('Y-m-d H:i:s');
					$this->transactions->status = '1';
					$this->transactions->etat = '1';
					$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
					$this->transactions->type_transaction = 2; 
					$this->transactions->transaction = 2; // transaction virtuelle
					//$this->transactions->id_transaction = $this->transactions->create();
					
					// on enregistre la transaction dans son wallet
					$this->wallets_lines->id_lender = $e['id_lender_account'];
					$this->wallets_lines->type_financial_operation = 20;
					$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
					$this->wallets_lines->status = 1;
					$this->wallets_lines->type = 2;
					$this->wallets_lines->amount = $e['amount'];
					//$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
					
					// On recupere le bid
					$this->bids->get($e['id_bid'],'id_bid');
					$this->bids->status = 2;
					//$this->bids->update();
					
					$this->notifications->type = 1; // rejet
					$this->notifications->id_lender = $e['id_lender_account'];
					$this->notifications->id_project = $e['id_project'];
					$this->notifications->amount = $e['amount'];
					$this->notifications->id_bid = $e['id_bid'];
					//$this->notifications->create();
					
					
					// mail enchere ko //
					//mail('d.courtier@equinoa.com','bid ko','bid : '.$e['id_bid'].' montant : '.$e['amount']/100);
							
					//****************************//
					//*** ENVOI DU MAIL BID KO ***//
					//****************************//
					
					
					// On recup le client
					$preteur = $this->loadData('clients');
					$preteur->get($this->lenders_accounts->id_client_owner,'id_client');
					
					echo $preteur->prenom;
					if($preteur->prenom == 'David')
					{
					
					// Recuperation du modele de mail
					$this->mails_text->get('preteur-bid-ko','lang = "'.$this->language.'" AND type');
					
					// Variables du mailing
					$surl = $this->surl;
					$url = $this->lurl;
					$prenom = $preteur->prenom;
					$projet = $projects->title;
					$montant = number_format($e['amount']/100, 2, ',', ' ');
					$taux = number_format($e['rate'], 2, ',', ' ');
					$entreprise = $companies->name;
					$date = $this->dates->formatDate($e['added'],'d/m/Y');
					$heure = $this->dates->formatDate($e['added'],'H\hi').'';
					
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
					'prenom_p' => $prenom,
					'valeur_bid' => $montant,
					'taux_bid' => $taux,
					'nom_entreprise' => $entreprise,
					'projet-p' => $this->lurl.'/projects/detail/'.$projects->slug,
					'date_bid' => $date,
					'heure_bid' => $heure,
					'fin_chrono' => $tempsRest,
					'projet-bid' => $this->lurl.'/projects/detail/'.$projects->slug,
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
					//$this->email->addRecipient(trim('courtier.damien@gmail.com'));
					//$this->email->addBCCRecipient($this->clients->email);
					
					$this->email->setSubject(stripslashes($sujetMail));
					$this->email->setHTMLBody(stripslashes($texteMail));
					Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.courtier@equinoa.com',$tabFiler);
								
					// Injection du mail NMP dans la queue
					$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
					
					// fin mail enchere ko //
					}
					
					
				}
			}
			
			
		}
		
	}
	
	
	
	function _testUnMail()
	{
		// Si le solde a été atteint on envoie un mail a l'emprunteur
		if($_SERVER['REMOTE_ADDR'] == '93.26.42.99')
		{
			echo 'laaa';
			// Chargement des datas
			$loans = $this->loadData('loans');
			$transactions = $this->loadData('transactions');
			$lenders = $this->loadData('lenders_accounts');
			$clients = $this->loadData('clients');
			$wallets_lines = $this->loadData('wallets_lines');
			$companies = $this->loadData('companies');
			$projects = $this->loadData('projects');
		
			$nb_loans = $loans->getNbPreteurs(55);
		
			$i=0;
			foreach($loans->select('id_project = 55') as $l)
			{
				if($i > 0)
				{
					break;	
				}
				$i++;
				// On recup le projet
				$projects->get($l['id_project'],'id_project');
				// On recup l'entreprise
				$companies->get($projects->id_company,'id_company');
				
				// On recup lender
				$lenders->get($l['id_lender'],'id_lender_account');
				// on recup les infos du lender
				$clients->get($lenders->id_client_owner,'id_client');
				
				// On change le satut des loans du projet refusé
				$loans->get($l['id_loan'],'id_loan');
				$loans->status = 1;
				//$loans->update();
				
				// On redonne l'argent aux preteurs
				
				
									
				// On enregistre la transaction
				$transactions->id_client = $lenders->id_client_owner;
				$transactions->montant = $l['amount'];
				$transactions->id_langue = 'fr';
				$transactions->date_transaction = date('Y-m-d H:i:s');
				$transactions->status = '1';
				$transactions->etat = '1';
				$transactions->ip_client = $_SERVER['REMOTE_ADDR'];
				$transactions->type_transaction = 2; 
				$transactions->transaction = 2; // transaction virtuelle
				//$transactions->id_transaction = $transactions->create();
				
				// on enregistre la transaction dans son wallet
				$wallets_lines->id_lender = $l['id_lender_account'];
				$wallets_lines->type_financial_operation = 20;
				$wallets_lines->id_transaction = $transactions->id_transaction;
				$wallets_lines->status = 1;
				$wallets_lines->type = 2;
				$wallets_lines->amount = $l['amount'];
				//$wallets_lines->id_wallet_line = $wallets_lines->create();
		
				
				
				//**************************************//
				//*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
				//**************************************//
				
				// Recuperation du modele de mail
				$this->mails_text->get('preteur-pret-refuse','lang = "'.$this->language.'" AND type');
				

				// FB
				$this->settings->get('Facebook','type');
				$lien_fb = $this->settings->value;
				
				// Twitter
				$this->settings->get('Twitter','type');
				$lien_tw = $this->settings->value;
				
	
				// Variables du mailing
				$varMail = array(
				'surl' => $this->surl,
				'url' => $this->lurl,
				'prenom_p' => $clients->prenom,
				'valeur_bid' => number_format($l['amount']/100, 0, ',', ' '),
				'nom_entreprise' => $companies->name,
				'nb_preteurMoinsUn' => ($nb_loans-1),
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
				//$this->email->addRecipient(trim('d.courtier@equinoa.com'));
				//$this->email->addBCCRecipient($this->clients->email);
				
				$this->email->setSubject(stripslashes($sujetMail));
				$this->email->setHTMLBody(stripslashes($texteMail));
				Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.courtier@equinoa.com',$tabFiler);
				print_r($tabFiler);			
				// Injection du mail NMP dans la queue
				$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
			
				
			}
					
					
		}
	}
	
	function _testWalletTrans()
	{
		// Si le solde a été atteint on envoie un mail a l'emprunteur
		if($_SERVER['REMOTE_ADDR'] == '93.26.42.99')
		{
			
			// Chargement des datas
			
			
			$transactions = $this->loadData('transactions');
			$wallets_lines = $this->loadData('wallets_lines');
			
			$transac = $transactions->select('status = 1 AND etat = 1');
			
			foreach($transac as $t)
			{
				$nb = $wallets_lines->counter('id_transaction = '.$t['id_transaction']);
				if($nb > 1)
				{
				echo 'id_transaction '.$t['id_transaction'].' = '.$nb.'<br>';
				}
			}
			
		}
	}
	
	// Permet de creer des prelevement en prenant les echeances emprunteurs (derniere mise a jour fonction le 17/06/2014)
	function _createPrelevements()
	{
		// que chez nous
		if($_SERVER['REMOTE_ADDR'] == '93.26.42.99')
		{
			// renseigner le id projet 
			$id_project = 3384;
			
			$echeanciers = $this->loadData('echeanciers');
			$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
			$companies = $this->loadData('companies');
			$projects = $this->loadData('projects');
			$clients = $this->loadData('clients');
			$prelevements = $this->loadData('prelevements');
			
			$jo = $this->loadLib('jours_ouvres');
			
			// On recup le projet
			$projects->get($id_project,'id_project');
			// On recup les echeances de remb emprunteur
			//$echea = $echeanciers->getSumRembEmpruntByMonths($projects->id_project);
			$echea = $echeanciers_emprunteur->select('id_project = '.$projects->id_project);
			// On recup la companie
			$companies->get($projects->id_company,'id_company');
			// On recup le client
			$clients->get($companies->id_client_owner,'id_client');
			
			// Motif mandat emprunteur
			$p = substr($clients->prenom,0,1);
			$nom = $clients->nom;
			$id_project = str_pad($projects->id_project,6,0,STR_PAD_LEFT);
			$motif = strtoupper('UNILEND'.$id_project.'E'.$p.$nom);
			
			foreach($echea as $key => $e)
			{
				$dateEcheEmp = strtotime($e['date_echeance_emprunteur']);
				$result = mktime(0,0,0, date("m",$dateEcheEmp), date("d",$dateEcheEmp)-15, date("Y",$dateEcheEmp));
				$dateExec = date('Y-m-d',$result);

				// montant emprunteur a remb
				$montant = $echeanciers->getMontantRembEmprunteur($e['montant'],$e['commission'],$e['tva']);
				
				// on enregistre le prelevement recurent a effectuer chaque mois
				$prelevements->id_client = $clients->id_client;
				$prelevements->id_project = $projects->id_project;
				$prelevements->motif = $motif;
				$prelevements->montant = $montant;
				$prelevements->bic = $companies->bic; // bic
				$prelevements->iban = $companies->iban;
				$prelevements->type_prelevement = 1; // recurrent
				$prelevements->type = 2; //emprunteur
				$prelevements->num_prelevement = $e['ordre'];
				$prelevements->date_execution_demande_prelevement = $dateExec;
				$prelevements->date_echeance_emprunteur = $e['date_echeance_emprunteur'];
				$prelevements->create();
			}
		
		
		}
		die;
	}
	
	
	
	function _testremb()
	{
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$echeanciers = $this->loadData('echeanciers');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$bank_unilend = $this->loadData('bank_unilend');
		$clients = $this->loadData('clients');
		$lenders_accounts = $this->loadData('lenders_accounts');
		
		// id projet
		$id_project = 7;
		// echeanches preteurs
		$lEcheances = $echeanciers->select('id_project = '.$id_project.' AND status_emprunteur = 1 AND ordre = 1');
		// echeance emprunteur
		$lEcheances_emprunteur = $echeanciers_emprunteur->select('id_project = '.$id_project.' AND status_emprunteur = 1 AND ordre = 1');
		
		// emprunteur
		$tvaEmprunteur = 0;
		$commissionEmprunteur = 0;
		
		// preteur
		$montant = 0;
		$capital = 0;
		$interets = 0;
		$prelevements_obligatoires = 0;
		$retenues_source = 0;
		$csg = 0;
		$prelevements_sociaux = 0;
		$contributions_additionnelles = 0;
		$prelevements_solidarite = 0;
		$crds = 0;
		$tva = 0;
		$commission = 0;
		
		// On parcourt les echeances emprunteur
		foreach($lEcheances_emprunteur as $e)
		{
			$tvaEmprunteur += ($e['tva']/100);
			$commissionEmprunteur += ($e['commission']/100);
		}
		
		// On partcourt les echeances preteur
		foreach($lEcheances as $e)
		{
			$montant += ($e['montant']/100);
			$capital += ($e['capital']/100);
			$interets += ($e['interets']/100);
			$prelevements_obligatoires += $e['prelevements_obligatoires'];
			$retenues_source += $e['retenues_source'];
			$csg += $e['csg'];
			$prelevements_sociaux += $e['prelevements_sociaux'];
			$contributions_additionnelles += $e['contributions_additionnelles'];
			$prelevements_solidarite += $e['prelevements_solidarite'];
			$crds += $e['crds'];
			
			// partie pour l'etat
			$TotalEtat += $e['prelevements_obligatoires'] +  $e['retenues_source'] +  $e['csg'] + $e['prelevements_sociaux'] + $e['contributions_additionnelles'] + $e['prelevements_solidarite'] + $e['crds'];
			
			
			$tva += ($e['tva']/100);
			$commission += ($e['commission']/100);
			
		}

		
		// partie a retirer de bank unilend
		$rembTotal = $montant - $prelevements_obligatoires - $retenues_source - $csg - $prelevements_sociaux - $contributions_additionnelles - $prelevements_solidarite - $crds;
		
		// montant remb par l'emprunteur
		$rembEmprunteur = $bank_unilend->sumMontant('id_project = 7 AND type = 1 AND id_unilend = 32 ');
		// Montant part Unilend
		$tauxUnilendSurProjetEmprunteur = $bank_unilend->sumMontant('id_project = 7 AND type = 0');
		
		?>
        <h1 style="font-family:Arial, Helvetica, sans-serif">Remboursement du premier mois pour 727</h1>
        
        <br><br>
        <table border="1" style="font-family:Arial, Helvetica, sans-serif;font-size: 12px;">
            <tr>
            	<th colspan="2">Part Unilend</th>
            </tr><tr>
            	<td>Montant unilend 3% + tva :</td><td align="right"><?=number_format($tauxUnilendSurProjetEmprunteur/100, 2, ',', ' ')?> €</td>
            </tr><tr>
            	<td colspan="2">&nbsp;</td>
            </tr><tr>
            	<th colspan="2">Remb Emprunteur</th>
            </tr><tr>
                <td>Montant remb par l'emprunteur :</td><td align="right"><?=number_format($rembEmprunteur/100, 2, ',', ' ')?> €</td>
            </tr><tr>
                <td>Partie commission emprunteur :</td><td align="right"><?=number_format($commissionEmprunteur, 2, ',', ' ')?> €</td>
            </tr><tr>
                <td>Partie TVA emprunteur :</td><td align="right"><?=number_format($tvaEmprunteur, 2, ',', ' ')?> €</td>
            </tr><tr>
           		<td colspan="2">&nbsp;</td>
            </tr><tr>
            	<th colspan="2">Remb preteurs</th>
            </tr><tr>
                <td>Partie pour les preteurs brute :</td><td align="right"><?=number_format($montant, 2, ',', ' ')?> €</td>
            </tr><tr>
            	<td>Partie pour l'etat :</td><td align="right"><?=number_format($TotalEtat, 2, ',', ' ')?> €</td>
            </tr><tr>
                <td>Partie pour les preteurs net :</td><td align="right"><?=number_format($rembTotal, 2, ',', ' ')?> €</td>
            </tr>
        </table>
        
        <br><br>
        
        <h2 style="font-family:Arial, Helvetica, sans-serif;">Remb preteurs :</h2>
		<table border="1" style="font-family:Arial, Helvetica, sans-serif;font-size: 12px;">
            <tr>
            	<th>Client</th>
                <th>Type (morale/physique)</th>
                <th>Exonéré (oui/non)</th>
                <th>Remb brute</th>
                <th>Prlv obligatoires</th>
                <th>Retenues source</th>
                <th>Csg</th>
                <th>Prlv sociaux</th>
                <th>Contri additionnelles</th>
                <th>Prlv solidarite</th>
                <th>Crds</th>
                <th>Etat</th>
                <th>Remb net</th>
        	</tr>
            
        <?
		
		foreach($lEcheances as $e)
		{
			// lender
			$lenders_accounts->get($e['id_lender'],'id_lender_account');
			// client
			$clients->get($lenders_accounts->id_client_owner,'id_client');
			
			// remb net
			$remb = ($e['montant']/100) - $e['prelevements_obligatoires'] -  $e['retenues_source'] -  $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
			
			// partie pour l'etat
			$etat =  ($e['montant']/100) - $remb;
		
			
			?>
            
            <tr>
            	<td><?=$clients->id_client?> <?=$clients->prenom?> <?=$clients->nom?></td>
                <td><?=($clients->type == 1?'Physique':'<span style="color:blue;">Morale</span>')?></td>
                <td><?=($lenders_accounts->exonere == 1?'<span style="color:red;">Oui</span>':'Non')?></td>
                <td align="right"><?=($e['interets']/100)?></td>
                <td align="right" bgcolor="#D0DEEA"><?=$e['prelevements_obligatoires']?></td>
                <td align="right" bgcolor="#D0DEEA"><?=$e['retenues_source']?></td>
                <td align="right" bgcolor="#D0DEEA"><?=$e['csg']?></td>
                <td align="right" bgcolor="#D0DEEA"><?=$e['prelevements_sociaux']?></td>
                <td align="right" bgcolor="#D0DEEA"><?=$e['contributions_additionnelles']?></td>
                <td align="right" bgcolor="#D0DEEA"><?=$e['prelevements_solidarite']?></td>
                <td align="right" bgcolor="#D0DEEA"><?=$e['crds']?></td>
                <td align="right"><?=$etat?></td>
                <td align="right" bgcolor="#82BAEA"><?=$remb?></td>
            </tr>
            <?
			
		}
		?>
        <tr>
            <th>Total</th>
            <th>&nbsp;</th>
            <th>&nbsp;</td>
            <th><?=$montant?></th>
            <th><?=$prelevements_obligatoires?></td>
            <th><?=$retenues_source?></td>
            <th><?=$csg?></td>
            <th><?=$prelevements_sociaux?></td>
            <th><?=$contributions_additionnelles?></td>
            <th><?=$prelevements_solidarite?></td>
            <th><?=$crds?></td>
            <th><?=$TotalEtat?></th>
            <th><?=$rembTotal?></th>
        </tr>
        
        
        </table>
        <?
		
		//echo 'total a remb : '.$remb.'<br>';
		//echo 'montant : '.$montant;
	}
	
	function _ibanbic()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$lenders_accounts = $this->loadData('lenders_accounts');
		foreach($lenders_accounts->select() as $p)
		{
			$lenders_accounts->get($p['id_lender_account'],'id_lender_account');
			$lenders_accounts->iban = str_replace(' ','',$p['iban']);
			$lenders_accounts->bic = str_replace(' ','',$p['bic']);
			//$lenders_accounts->update();
		}
	}
	
	
	function _checkDouble()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$w = $this->loadData('wallets_lines');
		$l = $this->loadData('lenders_accounts');
		
		$lesL = $l->select();
		
		foreach($lesL as $leL)
		{
			$lesW = $w->select('id_lender = '.$leL['id_lender_account']);
			
			
			$i = 0;
			foreach($lesW as $leW)
			{
				//if($i==0)echo '<br><br>LE LENDER : '.$leL['id_lender_account'].'<br>';
				$i++;
				$added = substr($leW['added'],0,16);
				
				$lesDoubles = $w->select('id_wallet_line = '.$leW['id_wallet_line'].' AND LEFT(added,16) = "'.$added.'"');
				
				
				
				$a = 0;
				foreach($lesDoubles as $leDouble)
				{
					if($a>1)echo $leDouble['id_wallet_line'].'<br>';
					$a++;
					
					
				}
				
				
			}
			
			
		}
	}
	
	function _notifCheckDoubles()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$n = $this->loadData('notifications');
		
		$lesN = $n->select('type = 1');
		
		
		foreach($lesN as $leN)
		{
			$lsDoubles = $n->select('id_bid = '.$leN['id_bid']);
			if(count($lsDoubles)>1)
			{

				echo $leN['id_bid'].'<br>';
				
			}
			
		}
	}
	
	function _CheckEnCours()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$p = $this->loadData('projects');
		$b = $this->loadData('bids');
		
		$lesP = $p->selectProjectsByStatus('60,70,75,80');

		
		foreach($lesP as $leP)
		{
			echo '------ PROJET '.$leP['id_project'].' ------<br>';
			$lesBids = $b->select('id_project = '.$leP['id_project'].' AND status = 0');
			
			foreach($lesBids as $bid)
			{
				echo $bid['id_bid'].'<br>';
			}
			echo '------ FIN ------<br><br>';
		}
	}
	
	
	function _testJourOuvre()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		//$jo = $this->loadLib('jours_ouvres');
		
		$p = $this->loadData('prelevements');
		
		//$today = date('Y-m-d').' 00:00:00';
		//$result = $jo->getNbJourNonOuvre(strtotime($today),11,'1');	
		//echo $today.'<br>';
		//echo $result;
		
		
		$lesP = $p->select('type = 2 AND status = 0');
		foreach($lesP as $leP)
		{	
		
		
			$dateEcheEmp = strtotime($leP['date_echeance_emprunteur']);
			$result = mktime(0,0,0, date("m",$dateEcheEmp), date("d",$dateEcheEmp)-11, date("Y",$dateEcheEmp));
			$dateExec = date('Y-m-d',$result);
			
			
			echo 'echeance Emprunteur : '.$leP['date_echeance_emprunteur'].'<br>';
			echo 'echeance Execution '.$leP['num_prelevement'].' : '.$dateExec.'<br><br>';
			
			$p->get($leP['id_prelevement'],'id_prelevement');
			$p->date_execution_demande_prelevement = $dateExec;
			$p->update();
		
			
		}
	
	}
	
	function _majuscules()
	{
		
		
		$c = $this->loadData('clients');
		
		$lesC = $c->select('status = 1');
		
		foreach($lesC as $leC)
		{
			
			$nom = $this->ficelle->majNom($leC['nom']);
			$nom_usage = $this->ficelle->majNom($leC['nom_usage']);
			$prenom = $this->ficelle->majNom($leC['prenom']);
			
			echo $nom.' - '.$nom_usage.' - '.$prenom.'<br>';
			
			$c->get($leC['id_client'],'id_client');
			$c->nom = $nom;
			$c->nom_usage = $nom_usage;
			$c->prenom = $prenom;
			$c->update();
		}
		
	}
	
	function _majuscules2()
	{
		
		
		$c = $this->loadData('companies');
		
		$lesC = $c->select('name <> ""');
		
		foreach($lesC as $leC)
		{
			
			//$iban = strtoupper ($leC['iban']);
			//$bic = strtoupper($leC['bic']);
			$nom_dirigeant = $this->ficelle->majNom($leC['nom_dirigeant']);
			$prenom_dirigeant = $this->ficelle->majNom($leC['prenom_dirigeant']);
			
			echo $leC['name'].' - '.$iban.' - '.$bic.' - '.$nom_dirigeant.' - '.$prenom_dirigeant.'<br>';
			
			$c->get($leC['id_company'],'id_company');
			//$c->iban = $iban;
			//$c->bic = $bic;
			$c->nom_dirigeant = $nom_dirigeant;
			$c->prenom_dirigeant = $prenom_dirigeant;
			$c->update();
		}
		
	}
	
	function _majuscules3()
	{
		
		
		$c = $this->loadData('lenders_accounts');
		
		$lesC = $c->select();
		
		foreach($lesC as $leC)
		{
			
			$iban = strtoupper ($leC['iban']);
			$bic = strtoupper($leC['bic']);
			
			echo $iban.' - '.$bic.'<br>';
			
			$c->get($leC['id_lender_account'],'id_lender_account');
			$c->iban = $iban;
			$c->bic = $bic;
			$c->update();
		}
		
	}
	
	function _phpInfo()
	{
		//ini_set('memory_limit', '1024M');
		//ini_set('max_execution_time', 300);
		
		phpinfo();
		die;
	}
	
	// met a jour le montant des prelevements
	function _miseAjourMontantPrelevement()
	{
		$p = $this->loadData('prelevements');
		$echeE = $this->loadData('echeanciers_emprunteur');
		
		$lesP = $p->select();
		
		foreach($lesP as $leP)
		{
			
			$echeE->get($leP['id_project'],'ordre = '.$leP['num_prelevement'].' AND id_project');
			$montant = $echeE->montant+$echeE->commission+$echeE->tva;
			//$date_echeance_emprunteur = date('Y-m-d',strtotime($echeE->date_echeance_emprunteur));
			
			$p->get($leP['id_prelevement'],'id_prelevement');
			if($p->status == 0)$p->montant = $montant;
			//if($p->status == 0)$p->date_echeance_emprunteur = $date_echeance_emprunteur;
			$p->update();

		}
	}
	
	function _updateDatePrelevementEcheances()
	{
		die;
		$p = $this->loadData('prelevements');
		$lesP = $p->select('date_echeance_emprunteur >= "2014-05-28"','date_echeance_emprunteur ASC');
		foreach($lesP as $leP)
		{
			if($p->get($leP['id_prelevement'],'status = 0 AND id_prelevement'))
			{
				$dateEcheEmp = strtotime($p->date_echeance_emprunteur);
				$result = mktime(0,0,0, date("m",$dateEcheEmp), date("d",$dateEcheEmp)-15, date("Y",$dateEcheEmp));
				$dateExec = date('Y-m-d',$result);
				
				$p->date_execution_demande_prelevement = $dateExec;
				
				echo $leP['id_prelevement'].' - '.$p->date_execution_demande_prelevement.' - '.$p->date_echeance_emprunteur.'<br>';
				//$p->update();
			}
		}
		die;
	}
	
	function _updateEcheance()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$echeanciers = $this->loadData('echeanciers');
		
		// EQ-Retenue à la source
		$this->settings->get('EQ-Retenue à la source','type');
		$retenues_source = $this->settings->value;
		
		$lEche = $echeanciers->select('id_loan IN(415,417)');
		
		foreach($lEche as $l)
		{
			$montant_prelevements_obligatoires = 0;
			$montant_contributions_additionnelles = 0;
			$montant_crds = 0;
			$montant_csg = 0;
			$montant_prelevements_solidarite = 0;
			$montant_prelevements_sociaux = 0;
			$montant_retenues_source = round($retenues_source*($l['interets']/100),2);
			
			$echeanciers->get($l['id_echeancier'],'id_echeancier');
			
			$echeanciers->prelevements_obligatoires = $montant_prelevements_obligatoires;
			$echeanciers->contributions_additionnelles = $montant_contributions_additionnelles;
			$echeanciers->crds = $montant_crds;
			$echeanciers->csg = $montant_csg;
			$echeanciers->prelevements_solidarite = $montant_prelevements_solidarite;
			$echeanciers->prelevements_sociaux = $montant_prelevements_sociaux;
			$echeanciers->retenues_source = $montant_retenues_source;
			$echeanciers->update();

			
		}
			
	}
	
	
	function _checkTrop()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$transactions = $this->loadData('transactions');
		
		$lesRejets = $transactions->select('id_bid_remb <> 0');
		/*echo '<pre>';
		print_r($lesRejets);
		echo '</pre>';*/
		
		foreach($lesRejets as $r)
		{
			
			$lsDoubles = $transactions->select('id_bid_remb = '.$r['id_bid_remb']);
			if(count($lsDoubles)>1)
			{

				echo 'id_client : '.$r['id_client'].' bid '.$r['id_bid_remb'].'<br>';
				
			}
			
		}
		
	}
	
	
	function _lesbids()
	{
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$bids = $this->loadData('bids');
		$lenders_accounts = $this->loadData('lenders_accounts');
		
		
		$this->lEnchere = $bids->select('id_project = 699','rate ASC,added ASC');
		$leSoldeE = 0;
		$montantEmprunt = 56000;
		foreach($this->lEnchere as $k => $e)
		{
			$lenders_accounts->get($e['id_lender_account'],'id_lender_account');
			// on parcour les encheres jusqu'au montant de l'emprunt
			if($leSoldeE < $montantEmprunt)
			{
				// le solde total des encheres
				$leSoldeE += ($e['amount']/100);
				
				echo '<br>------------<br>';
				echo 'lender : '.$e['id_lender_account'].'<br>';
				echo 'id client : '.$lenders_accounts->id_client_owner.'<br>';
				echo 'id_bid : '.$e['id_bid'].'<br>';
				echo 'montant preteur : '.($e['amount']/100).'<br>';
				echo '$leSoldeE : '.$leSoldeE.'<br>';
				echo 'tx preteur : '.($e['rate']).'<br>';
				
				if($leSoldeE > $montantEmprunt)
				{
					echo '<p style="color:red;">-------------------------ICI-------------------------------</p>';
				
				}
				
			}
			else
			{
				echo '<br>------------<br>';
				echo 'lender : '.$e['id_lender_account'].'<br>';
				echo 'id client : '.$lenders_accounts->id_client_owner.'<br>';
				echo 'id_bid : '.$e['id_bid'].'<br>';
				echo 'montant preteur : '.($e['amount']/100).'<br>';
				echo '$leSoldeE : '.$leSoldeE.'<br>';
				echo 'tx preteur : '.($e['rate']).'<br>';
			}
		}
		
	}
	
	
	function _untest()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$echeanciers = $this->loadData('echeanciers');
		$echeanciersEmp = $this->loadData('echeanciers_emprunteur');
		
		$eche = $echeanciers->getSumRembEmpruntByMonths('19','','0');
		
		//$eche = $echeanciersEmp->select('status_emprunteur = 0 AND id_project = 19','ordre ASC');
		
		
		foreach($eche as $e)
		{
			// on récup le montant que l'emprunteur doit rembourser
			$montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant'],$e['commission'],$e['tva']);
			
			echo $e['ordre'].'montant mois : '.$montantDuMois.'<br>';
		}
		
		
	}
	
	
	function _histocli()
	{
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$clients_history_actions = $this->loadData('clients_history_actions');
		
			
	}
	
	function _updatebicIban()
	{
		$p = $this->loadData('prelevements');
		$projects = $this->loadData('projects');
		$companies = $this->loadData('companies');
		
		$lesP = $p->select();
		
		foreach($lesP as $leP)
		{
			$projects->get($leP['id_project'],'id_project');
			$companies->get($projects->id_company,'id_company');
			
			$companies->bic.'<br>';
			$p->get($leP['id_prelevement'],'id_prelevement');
			$p->bic = $companies->bic;
			$p->iban = $companies->iban;
			$p->update();
		}
	}
	
	
	function _mail1()
	{
		die;
		// Chargement des datas
		$e = $this->loadData('clients');
		$loan = $this->loadData('loans');
		$project = $this->loadData('projects');
		$companie = $this->loadData('companies');
		$echeancier = $this->loadData('echeanciers');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$bids = $this->loadData('bids');
		
		//**************************************//
		//*** ENVOI DU MAIL FUNDE EMPRUNTEUR TERMINE ***//
		//**************************************//
		
		// On recup le projet
		$project->get('2784','id_project');
		
		// On recup la companie
		$companie->get($project->id_company,'id_company');
		
		// Recuperation du modele de mail
		$this->mails_text->get('emprunteur-dossier-funde-et-termine','lang = "'.$this->language.'" AND type');
		
		// emprunteur
		$e->get($companie->id_client_owner,'id_client');
		
		// taux moyen
		//$taux_moyen = $loan->getAvgLoans($project->id_project,'rate');
		
		$montantHaut = 0;
		$montantBas = 0;
		foreach($loan->select('id_project = '.$project->id_project) as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}
		$taux_moyen = ($montantHaut/$montantBas);
		
		
		
		//$mensualite = $echeancier->getSumRembEmpruntByMonths($project->id_project);
		//$mensualite = $mensualite[1]['montant'];
		
		$echeanciers_emprunteur->get($project->id_project,'ordre = 1 AND id_project');
		$mensualite = $echeanciers_emprunteur->montant+$echeanciers_emprunteur->commission+$echeanciers_emprunteur->tva;
		$mensualite = ($mensualite/100);

		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		$projet = $project->title;
		$link_mandat = $this->lurl.'/pdf/mandat/'.$e->hash.'/'.$project->id_project;
		$link_pouvoir = $this->lurl.'/pdf/pouvoir/'.$e->hash.'/'.$project->id_project;
		
		
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
		'prenom_e' => $e->prenom,
		'nom_e' => $companie->name,
		'mensualite' => number_format($mensualite, 2, ',', ' '),
		'montant' => number_format($project->amount, 0, ',', ' '),
		'taux_moyen' => number_format($taux_moyen, 2, ',', ' '),
		'link_compte_emprunteur' => $this->lurl.'/projects/detail/'.$project->id_project,
		'link_mandat' => $link_mandat,
		'link_pouvoir' => $link_pouvoir,
		'projet' => $projet,
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
			Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$e->email,$tabFiler);			
			// Injection du mail NMP dans la queue
			$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
		}
		else // non nmp
		{
			$this->email->addRecipient(trim($e->email));
			Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
		}
		
		
		
		
		
		
		//////////////////////////////////////////
		
		
		////////////////////////////////////
		// NOTIFICATION PROJET FUNDE 100% //
		////////////////////////////////////
		
		
	
		// destinataire
		$this->settings->get('Adresse notification projet funde a 100','type');
		$destinataire = $this->settings->value;
		//$destinataire = 'd.courtier@equinoa.com';
		
		// Solde des encheres du project
		$montant_collect = $bids->getSoldeBid($this->projects->id_project);
		
		// si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
		if(($montant_collect/100) >= $project->amount) $montant_collect = $project->amount;
		
		$this->nbPeteurs = $loan->getNbPreteurs($project->id_project);
		
		
		
		// Recuperation du modele de mail
		$this->mails_text->get('notification-projet-funde-a-100','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		$id_projet = $project->id_project;
		$title_projet = $project->title;
		$nbPeteurs = $this->nbPeteurs;
		$tx = $taux_moyen;
		$montant_pret = $project->amount;
		$montant = $montant_collect;
		$periode =  $project->period;
		
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
		$this->email->addRecipient(trim($destinataire));
		
	
		$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
		$this->email->setHTMLBody($texteMail);
		Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
		// fin mail
		
		
		
		
		
		
		
		
	}
	
	
	function _mail2()
	{
		die;

		$loans = $this->loadData('loans');
		$preteur = $this->loadData('clients');
		$companies = $this->loadData('companies');
		$echeanciers = $this->loadData('echeanciers');
		$this->projects = $this->loadData('projects');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		
		// on parcourt les bids ok du projet et on envoie les mail
		$lLoans = $loans->select('id_project = 2784');		
		$i=0;
		foreach($lLoans as $l)
		{
			$i++;
			// On recup le projet
			$this->projects->get($l['id_project'],'id_project');
			// le lender
			$this->lenders_accounts->get($l['id_lender'],'id_lender_account');
			// On recup le client
			$preteur->get($this->lenders_accounts->id_client_owner,'id_client');
			// on recup la companie de l'emprunteur
			$companies->get($this->projects->id_company,'id_company');
			
			
			// Bid OK
			
			// Motif virement
			$p = substr($this->ficelle->stripAccents(utf8_decode(trim($preteur->prenom))),0,1);
			$nom = $this->ficelle->stripAccents(utf8_decode(trim($preteur->nom)));
			$id_client = str_pad($preteur->id_client,6,0,STR_PAD_LEFT);
			$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
			
			//*********************************//
			//*** ENVOI DU MAIL BID OK 100% ***//
			//*********************************//
			
			// on recup la premiere echeance
			//$lecheancier = $echeanciers->getPremiereEcheancePreteur($l['id_project'],$l['id_lender']);
			$lecheancier = $echeanciers->getPremiereEcheancePreteurByLoans($l['id_project'],$l['id_lender'],$l['id_loan']);
			
			
			// Recuperation du modele de mail
			$this->mails_text->get('preteur-bid-ok','lang = "'.$this->language.'" AND type');
			
			// Variables du mailing
			$surl = $this->surl;
			$url = $this->lurl;
			$prenom = $preteur->prenom;
			$projet = $this->projects->title;
			$montant_pret = number_format($l['amount']/100, 2, ',', ' ');
			$taux = number_format($l['rate'], 2, ',', ' ');
			$entreprise = $companies->name;
			$date = $this->dates->formatDate($l['added'],'d/m/Y');
			$heure = $this->dates->formatDate($l['added'],'H');
			$duree = $this->projects->period;
			
			$timeAdd = strtotime($lecheancier['date_echeance']);
			$month = $this->dates->tableauMois['fr'][date('n',$timeAdd)];
			
			// Variables du mailing
			$varMail = array(
			'surl' => $this->surl,
			'url' => $this->lurl,
			'prenom_p' => $preteur->prenom,
			'valeur_bid' => number_format($l['amount']/100, 2, ',', ' '),
			'taux_bid' => number_format($l['rate'], 2, ',', ' '),
			'nom_entreprise' => $companies->name,
			'nbre_echeance' => $this->projects->period,
			'mensualite_p' => number_format($lecheancier['montant']/100, 2, ',', ' '),
			'date_debut' => date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd),
			'compte-p' => $this->lurl,
			'projet-p' => $this->lurl.'/projects/detail/'.$this->projects->slug,
			'motif_virement' => $motif,
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
				Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$preteur->email,$tabFiler);
							
				// Injection du mail NMP dans la queue
				$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
			}
			else // non nmp
			{
				$this->email->addRecipient(trim($preteur->email));
				Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
			}
			
		}	
		die;
	}
	
	function _checkdateMois()
	{
		// On definit le nombre de mois et de jours apres la date de fin pour commencer le remboursement
		$this->settings->get('Nombre de mois apres financement pour remboursement','type');
		$nb_mois = $this->settings->value;
		$this->settings->get('Nombre de jours apres financement pour remboursement','type');
		$nb_jours = $this->settings->value;
		
		$nbjourstemp = 0;
		for($k=1;$k<=5;$k++)
		{
		
			
			$nbjourstemp = mktime (0,0,0,date("m")+$k ,1,date("Y"));
			$nbjoursMois += date('t',$nbjourstemp).'<br>';
			
			echo $nbjoursMois.'<br>';
			
			// Date d'echeance preteur
			$dateEcheance = $this->dates->dateAddMoisJours('2014-01-31 16:00:04',0,$nb_jours+$nbjoursMois);
			$dateEcheance = date('Y-m-d h:i',$dateEcheance).':00';
			
			// Date d'echeance emprunteur
			$dateEcheance_emprunteur = $this->dates->dateAddMoisJours('2014-01-31 16:00:04',0,$nbjoursMois);
			$dateEcheance_emprunteurMois = date('m',$dateEcheance_emprunteur);
			$dateEcheance_emprunteurJours = date('d',$dateEcheance_emprunteur);
			$dateEcheance_emprunteurAnnee = date('Y',$dateEcheance_emprunteur);
			$dateEcheance_emprunteur = date('Y-m-d h:i',$dateEcheance_emprunteur).':00';
			
			echo '2014-01-31 16:00:04<br>';
			echo 'emprunteur : '.$dateEcheance_emprunteur.'<br>';	
			echo 'preteur : '.$dateEcheance.'<br>';
			echo '----------------<br>';
			
			
		}
		
	}
	
	function _test_date()
	{
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$inter = $this->dates->intervalDates('2014-02-03 10:30:00','2014-02-03 16:00:00');
		print_r($inter);
		 			
		if($inter['mois']>0) $tempsRest = $inter['mois'].' mois';
		elseif($inter['jours']>0) $tempsRest = $inter['jours'].' jours';
		elseif($inter['heures']>0 && $inter['minutes'] >= 120) $tempsRest = $inter['heures'].' heures';
		elseif($inter['minutes']>0 && $inter['minutes'] < 120) $tempsRest = $inter['minutes'].' min';
		else $tempsRest = $inter['secondes'].' secondes';
		
		echo '<br><br>';
		echo 'il reste : '.$tempsRest;
	}
	
	function _sendMailContrat()
	{
		
		
		$loans = $this->loadData('loans');
		$preteur = $this->loadData('clients');
		$lender = $this->loadData('lenders_accounts');
		$leProject = $this->loadData('projects');
		$laCompanie = $this->loadData('companies');
		$echeanciers = $this->loadData('echeanciers');
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		$lLoans = $loans->select('id_project = 407');
		
		foreach($lLoans as $l)
		{
			// lender
			$lender->get($l['id_lender'],'id_lender_account');
			// preteur (client)
			$preteur->get($lender->id_client_owner,'id_client');
			
			
			
			//mail('d.courtier@equinoa.com','test unilend '.$l['id_lender'],'le contrat '.$l['id_lender'].' : '.$link_contrat);
			
			
			//******************************//
			//*** ENVOI DU MAIL CONTRAT ***//
			//******************************//
			
			// Recuperation du modele de mail
			$this->mails_text->get('preteur-contrat','lang = "'.$this->language.'" AND type');
			
			// on recup la premiere echeance
			//$lecheancier = $echeanciers->getPremiereEcheancePreteur($l['id_project'],$l['id_lender']);
			$lecheancier = $echeanciers->getPremiereEcheancePreteurByLoans($l['id_project'],$l['id_lender'],$l['id_loan']);
			
			
			
			$leProject->get($l['id_project'],'id_project');
			$laCompanie->get($leProject->id_company,'id_company');
		
			// Variables du mailing
			$surl = $this->surl;
			$url = $this->furl;
			$prenom = $preteur->prenom;
			$projet = $this->projects->title;
			$montant_pret = number_format($l['amount']/100, 2, ',', ' ');
			$taux = number_format($l['rate'], 2, ',', ' ');
			$entreprise = $laCompanie->name;
			$date = $this->dates->formatDate($l['added'],'d/m/Y');
			$heure = $this->dates->formatDate($l['added'],'H');
			$duree = $this->projects->period;
			$link_contrat = $this->furl.'/pdf/contrat/'.$preteur->hash.'/'.$l['id_loan'];
			
			// Variables du mailing
			$varMail = array(
			'surl' => $surl,
			'url' => $url,
			'prenom_p' => $prenom,
			'valeur_bid' => $montant_pret,
			'taux_bid' => $taux,
			'nom_entreprise' => $entreprise,
			'nbre_echeance' => $duree,
			'mensualite_p' => number_format($lecheancier['montant']/100, 2, ',', ' '),
			'date_debut' => date('d/m/Y',strtotime($lecheancier['date_echeance'])),
			'compte-p' => $this->furl,
			'projet-p' => $this->furl.'/projects/detail/'.$this->projects->slug,
			'link_contrat' => $link_contrat,
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
			//$this->email->addRecipient(trim('d.courtier@equinoa.com'));
			//$this->email->addBCCRecipient($this->clients->email);
			
			$this->email->setSubject(stripslashes($sujetMail));
			$this->email->setHTMLBody(stripslashes($texteMail));
			Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($preteur->email),$tabFiler);
			//Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim('d.courtier@equinoa.com'),$tabFiler);
						
			// Injection du mail NMP dans la queue
			$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);

			// fin mail
			//break;

		}	
	}
	
		
	
	function _updateEcheancesEmprunteurByProject()
	{
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		if(isset($this->params['0']))
		{
			$this->updateEcheancesEmprunteur($this->params['0']);
			echo 'ok';
		}
		else
		{
			echo 'mettre l\'id_project';	
		}
	}
	
	// fonction create echeances emprunteur
	function updateEcheancesEmprunteur($id_project)
	{
		// chargement des datas
		$loans = $this->loadData('loans');
		$projects = $this->loadData('projects');
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$echeanciers = $this->loadData('echeanciers');
		$prelevements = $this->loadData('prelevements');
		
		// Chargement des librairies
		$remb = $this->loadLib('remb');
		
		
		$this->settings->get('Commission remboursement','type');
		$com = $this->settings->value;
		
		// On definit le nombre de mois et de jours apres la date de fin pour commencer le remboursement
		$this->settings->get('Nombre de mois apres financement pour remboursement','type');
		$nb_mois = $this->settings->value;
		$this->settings->get('Nombre de jours apres financement pour remboursement','type');
		$nb_jours = $this->settings->value;
		
		// tva (0.196)
		$this->settings->get('TVA','type');
		$tva = $this->settings->value;
		
		$projects->get($id_project,'id_project');
		
		
		$montantHaut = 0;
		$montantBas = 0;
		foreach($loans->select('id_project = '.$projects->id_project) as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}
		$tauxMoyen = ($montantHaut/$montantBas);
		
		
		$capital = $projects->amount;
		$nbecheances = $projects->period;
		$taux = ($tauxMoyen/100);
		$commission = $com;
		$tva = $tva;
		
		$tabl = $remb->echeancier($capital,$nbecheances,$taux,$commission,$tva);
		
		$donneesEcheances = $tabl[1];
		//$lEcheanciers = $tabl[2];
		
		$lEcheanciers = $echeanciers->getSumRembEmpruntByMonths($projects->id_project,'','0');
		
		
		$nbjoursMois = 0;
		foreach($lEcheanciers as $k => $e)
		{
			if($prelevements->get($projects->id_project,'num_prelevement = '.$e['ordre'].' AND status = 0 AND id_project'))
			{
				$echeanciers_emprunteur->get($projects->id_project,'ordre = '.$e['ordre'].' AND id_project');
				
				$echeanciers_emprunteur->id_project = $projects->id_project;
				$echeanciers_emprunteur->montant = $e['montant']*100;
				$echeanciers_emprunteur->capital = $e['capital']*100;
				$echeanciers_emprunteur->interets = $e['interets']*100;
				
				$echeanciers_emprunteur->update();
			}
		}
		
		//echo 'ok';
	}
	
	
	function _testMailAlert()
	{
		$this->projects = $this->loadData('projects');
			
		$this->projects->get('469','id_project');
		$status = 40;
		
		// Si statut a funder, en funding ou fundé
		if(in_array($status,array(40,50,60)))
		{
			
			//mail('courtier.damine@gmail.com','alert change statut 1','statut : '.$_POST['status'].' projet : '.$this->projects->id_project );
			/////////////////////////////////////
			// Partie check données manquantes //
			/////////////////////////////////////
			
			$companies = $this->loadData('companies');
			$clients = $this->loadData('clients');
			$clients_adresses = $this->loadData('clients_adresses');
			
			
			// on recup la companie
			$companies->get($this->projects->id_company,'id_company');
			// et l'emprunteur
			$clients->get($companies->id_client_owner,'id_client');
			// son adresse
			$clients_adresses->get($companies->id_client_owner,'id_client');
			
			
			$mess = '<ul>';
			
			if($this->projects->title == '')$mess .= '<li>Titre projet</li>';
			if($this->projects->title_bo == '')$mess .= '<li>Titre projet BO</li>';
			if($this->projects->period == '0')$mess .= '<li>Periode projet</li>';
			if($this->projects->amount == '0')$mess .= '<li>Montant projet</li>';
			
			if($companies->name == '')$mess .= '<li>Nom entreprise</li>';
			if($companies->forme == '')$mess .= '<li>Forme juridique</li>';
			if($companies->siren == '')$mess .= '<li>SIREN entreprise</li>';
			if($companies->iban == '')$mess .= '<li>IBAN entreprise</li>';
			if($companies->bic == '')$mess .= '<li>BIC entreprise</li>';
			if($companies->rcs == '')$mess .= '<li>RCS entreprise</li>';
			if($companies->tribunal_com == '')$mess .= '<li>Tribunal de commerce entreprise</li>';
			if($companies->capital == '0')$mess .= '<li>Capital entreprise</li>';
			if($companies->date_creation == '0000-00-00')$mess .= '<li>Date creation entreprise</li>';
			if($companies->sector == 0)$mess .= '<li>Secteur entreprise</li>';
			
			if($clients->nom == '')$mess .= '<li>Nom emprunteur</li>';
			if($clients->prenom == '')$mess .= '<li>Prenom emprunteur</li>';
			if($clients->fonction == '')$mess .= '<li>Fonction emprunteur</li>';
			if($clients->telephone == '')$mess .= '<li>Telephone emprunteur</li>';
			if($clients->email == '')$mess .= '<li>Email emprunteur</li>';
			
			if($clients_adresses->adresse1 == '')$mess .= '<li>Adresse emprunteur</li>';
			if($clients_adresses->cp == '')$mess .= '<li>CP emprunteur</li>';
			if($clients_adresses->ville == '')$mess .= '<li>Ville emprunteur</li>';
			
			$mess .= '</ul>';
			
			
			if(strlen($mess) > 4)
			{
				
				//mail('courtier.damine@gmail.com','alert change statut 2','statut : '.$_POST['status'].' projet : '.$this->projects->id_project .' strlen : '.strlen($mess).' mess : '.$mess);
				
				$to  = 'courtier.damien@gmail.com , d.courtier@equinoa.com';
				//$to  .= 'nicolas.lesur@unilend.fr';
				//$to  = 'courtier.damien@gmail.com' . ', ';
				//$to  .= 'd.courtier@equinoa.com';
			
				// subject
				$subject = '[Rappel] Donnees projet manquantes';
				
				// message
				$message = '
				<html>
				<head>
				  <title>[Rappel] Donnees projet manquantes</title>
				</head>
				<body>
					<p>Un projet qui vient d\'etre publie ne dispose pas de toutes les donnees necessaires</p>
					<p>Listes des informations manquantes sur le projet '.$this->projects->id_project.' : </p>
					'.$mess.'
				</body>
				</html>
				';
				
				// To send HTML mail, the Content-type header must be set
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				
				// Additional headers
				
				$headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
				//$headers .= 'From: Unilend <courtier.damien@gmail.com>' . "\r\n";
				
				// Mail it
				mail($to, $subject, $message, $headers);
			}
		}	
	}
	
	function _releve_compte()
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
		
		
		// Argent bloqué/débloqué pour offre de prêt
		$this->argentBloque = $this->transactions->sumByMonthByPreteur($this->clients->id_client,'2',$month,$year);
		
		//$this->argentBloque = $this->agentPrete + $this->argentBloque;
		
		if($this->agentPrete > 0)$this->agentPrete = '-'.$this->agentPrete;
		
		// Argent remboursé
		$this->argentRemb = $this->transactions->sumByMonthByPreteur($this->clients->id_client,'5',$month,$year);
		
		$lRemb = $this->transactions->select('MONTH(date_transaction) = '.$month.' AND YEAR(date_transaction) = '.$year.' AND etat = 1 AND status = 1 AND type_transaction = 5 AND id_client = '.$this->clients->id_client);

		$this->interets = 0;
		$this->retenuesFiscales = 0;
		foreach($lRemb as $r)
		{
			$this->echeanciers->get($r['id_echeancier'],'id_echeancier');
			
			// Intérêts bruts reçus
			$this->interets += $this->echeanciers->interets;
			
			// Retenues fiscales
			$this->retenuesFiscales += $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;
		}
		
		$this->interets = ($this->interets/100);
		
		
		$this->lTrans = $this->transactions->select('type_transaction IN (1,2,3,4,5,7,8) AND status = 1 AND etat = 1 AND id_client = '.$this->clients->id_client.' AND LEFT(date_transaction,7) = "'.$year.'-'.$month.'"','date_transaction ASC');
			

	}
	
	
	function _miseAjourRemb()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients = $this->loadData('clients');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->transactions = $this->loadData('transactions');
		$this->notifications = $this->loadData('notifications');
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->bank_unilend = $this->loadData('bank_unilend');
		
		
		$lNomRemb = $this->echeanciers->select('id_project = 449 AND ordre = 1 AND status = 0');
		
		$this->projects->get(449,'id_project');
		
		foreach($lNomRemb as $k => $e)
		{
			
			$a = $k+1;
			echo $a.' - '.$e['id_echeancier'].'<br>';
			
			// on fait la somme de tout
			$montant += ($e['montant']/100);
			$capital += ($e['capital']/100);
			$interets += ($e['interets']/100);
			$commission += ($e['commission']/100);
			$tva += ($e['tva']/100);
			$prelevements_obligatoires += $e['prelevements_obligatoires'];
			$retenues_source += $e['retenues_source'];
			$csg += $e['csg'];
			$prelevements_sociaux += $e['prelevements_sociaux'];
			$contributions_additionnelles += $e['contributions_additionnelles'];
			$prelevements_solidarite += $e['prelevements_solidarite'];
			$crds += $e['crds'];
			
			// Remb net preteur
			$rembNet = ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
			
			// Partie pour l'etat sur un remb preteur
			$etat = $e['prelevements_obligatoires'] + $e['retenues_source'] + $e['csg'] + $e['prelevements_sociaux'] + $e['contributions_additionnelles'] + $e['prelevements_solidarite'] + $e['crds'];
			
			//echo 'Preteur '.$e['id_lender'].' remb net : '.$rembNet.' €<br>';
			
			// Partie on enregistre les mouvements
			
			// On regarde si on a pas deja
			if($this->transactions->get($e['id_echeancier'],'id_echeancier')==false)
			{
			
				// On recup lenders_accounts
				$this->lenders_accounts->get($e['id_lender'],'id_lender_account');
				// On recup le client
				$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
				
				// echeance preteur
				$this->echeanciers->get($e['id_echeancier'],'id_echeancier');
				$this->echeanciers->status = 1; // remboursé
				$this->echeanciers->date_echeance_reel = '2014-03-26 15:23:00';
				$this->echeanciers->update();
									
				// On enregistre la transaction
				$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
				$this->transactions->montant = ($rembNet*100);
				$this->transactions->id_echeancier = $e['id_echeancier']; // id de l'echeance remb
				$this->transactions->id_langue = 'fr';
				$this->transactions->date_transaction = '2014-03-26 15:23:00';
				$this->transactions->status = '1';
				$this->transactions->etat = '1';
				$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
				$this->transactions->type_transaction = 5; // remb enchere
				$this->transactions->transaction = 2; // transaction virtuelle
				$this->transactions->id_transaction = $this->transactions->create();
				
				// on enregistre la transaction dans son wallet
				$this->wallets_lines->id_lender = $e['id_lender'];
				$this->wallets_lines->type_financial_operation = 40;
				$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
				$this->wallets_lines->status = 1; // non utilisé
				$this->wallets_lines->type = 2; // transaction virtuelle
				$this->wallets_lines->amount = ($rembNet*100);
				$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
				
				// On enregistre la notification pour le preteur
				$this->notifications->type = 2; // remb
				$this->notifications->id_lender = $this->lenders_accounts->id_lender_account;
				$this->notifications->id_project = $this->projects->id_project;
				$this->notifications->amount = ($rembNet*100);
				$this->notifications->create();
				
				//*******************************************//
				//*** ENVOI DU MAIL REMBOURSEMENT PRETEUR ***//
				//*******************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('preteur-remboursement','lang = "'.$this->language.'" AND type');
				$this->companies->get($this->projects->id_company,'id_company');
				
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->furl;
				
				// euro avec ou sans "s"
				if($rembNet >= 2)$euros = ' euros';
				else $euros = ' euro';
				$rembNetEmail = number_format($rembNet, 2, ',', ' ').$euros;
				
				
				if($this->transactions->getSolde($this->clients->id_client)>=2)$euros = ' euros';
				else $euros = ' euro';
				$solde = number_format($this->transactions->getSolde($this->clients->id_client), 2, ',', ' ').$euros;
	
				// Variables du mailing
				$varMail = array(
				'surl' => $surl,
				'url' => $url,
				'prenom_p' => $this->clients->prenom,
				'mensualite_p' => $rembNetEmail,
				'nom_entreprise' => $this->companies->name,
				'solde_p' => $solde,
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
				//$this->email->addRecipient(trim($this->clients->email));
				//$this->email->addBCCRecipient($this->clients->email);
				
				$this->email->setSubject(stripslashes($sujetMail));
				$this->email->setHTMLBody(stripslashes($texteMail));
				Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->clients->email,$tabFiler);
							
				// Injection du mail NMP dans la queue
				$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);

				
			}

			
		}// fin boucle
		
		// partie a retirer de bank unilend
		$rembNetTotal = $montant - $prelevements_obligatoires - $retenues_source - $csg -$prelevements_sociaux - $contributions_additionnelles - $prelevements_solidarite - $crds;
		
		// partie pour l'etat
		$TotalEtat = $prelevements_obligatoires + $retenues_source + $csg + $prelevements_sociaux + $contributions_additionnelles + $prelevements_solidarite + $crds;
		
		// On evite de créer une ligne qui sert a rien
		if($rembNetTotal != 0)
		{
			
			// On enregistre la transaction
			$this->transactions->montant = 0;
			$this->transactions->id_echeancier = 0; // on reinitialise
			$this->transactions->id_client = 0; // on reinitialise
			$this->transactions->montant_unilend = '-'.$rembNetTotal*100;
			$this->transactions->montant_etat = $TotalEtat*100;
			$this->transactions->id_echeancier_emprunteur = $RembEmpr['id_echeancier_emprunteur']; // id de l'echeance emprunteur
			$this->transactions->id_langue = 'fr';
			$this->transactions->date_transaction = '2014-03-26 15:23:00';
			$this->transactions->status = '1';
			$this->transactions->etat = '1';
			$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
			$this->transactions->type_transaction = 10; // remb unilend pour les preteurs
			$this->transactions->transaction = 2; // transaction virtuelle
			
			$this->transactions->id_transaction = $this->transactions->create();
			
			
			// bank_unilend (on retire l'argent redistribué)
			$this->bank_unilend->id_transaction = $this->transactions->id_transaction;
			$this->bank_unilend->id_project = $this->projects->id_project; 
			$this->bank_unilend->montant = '-'.$rembNetTotal*100;
			$this->bank_unilend->etat = $TotalEtat*100;
			$this->bank_unilend->type = 2; // remb unilend
			$this->bank_unilend->id_echeance_emprunteur = $RembEmpr['id_echeancier_emprunteur'];
			$this->bank_unilend->status = 1;
			$this->bank_unilend->create();
			
			
			
		}
		/*echo '---------------------<br>';
		echo 'etat : '.$TotalEtat.'<br>';
		echo 'total a remb : '.$rembNetTotal.'<br>';
		echo 'montant : '.$montant.'<br>';
		echo '---------------------<br>';*/
		
	}
	
	
	function _testresultUnilend()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients = $this->loadData('clients');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->transactions = $this->loadData('transactions');
		$this->notifications = $this->loadData('notifications');
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->bank_unilend = $this->loadData('bank_unilend');
		
		
		$lNomRemb = $this->echeanciers->select('id_project = 449 AND ordre = 1');
		
		$this->projects->get(449,'id_project');
		
		foreach($lNomRemb as $k => $e)
		{
			
			//$a = $k+1;
			//echo $a.' - '.$e['id_echeancier'].'<br>';
			
			// on fait la somme de tout
			$montant += ($e['montant']/100);
			$capital += ($e['capital']/100);
			$interets += ($e['interets']/100);
			$commission += ($e['commission']/100);
			$tva += ($e['tva']/100);
			$prelevements_obligatoires += $e['prelevements_obligatoires'];
			$retenues_source += $e['retenues_source'];
			$csg += $e['csg'];
			$prelevements_sociaux += $e['prelevements_sociaux'];
			$contributions_additionnelles += $e['contributions_additionnelles'];
			$prelevements_solidarite += $e['prelevements_solidarite'];
			$crds += $e['crds'];
			
			// Remb net preteur
			$rembNet = ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
			
			// Partie pour l'etat sur un remb preteur
			$etat = $e['prelevements_obligatoires'] + $e['retenues_source'] + $e['csg'] + $e['prelevements_sociaux'] + $e['contributions_additionnelles'] + $e['prelevements_solidarite'] + $e['crds'];
			
		}
		
		// partie a retirer de bank unilend
		$rembNetTotal = $montant - $prelevements_obligatoires - $retenues_source - $csg -$prelevements_sociaux - $contributions_additionnelles - $prelevements_solidarite - $crds;
		
		// partie pour l'etat
		$TotalEtat = $prelevements_obligatoires + $retenues_source + $csg + $prelevements_sociaux + $contributions_additionnelles + $prelevements_solidarite + $crds;
		
		echo '---------------------<br>';
		echo 'etat : '.$TotalEtat.'<br>';
		echo 'total a remb : '.$rembNetTotal.'<br>';
		echo 'montant : '.$montant.'<br>';
		echo '---------------------<br>';
	}
	
	
	function _testLecturePayline()
	{
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireView = false;
		$this->autoFireFooter = false;
		
		// On recup la lib et le reste payline
		require_once($this->path.'protected/payline/include.php');
		
		
		$array = array();
		$payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
		$array['token'] = '1vjdCzMJZ1C1w5B571811397894930522';
		$array['version'] = '3';
		$response = $payline->getWebPaymentDetails($array);
		
		echo '<pre>';
		print_r($response);
		echo '</pre>';
	}
	
	
	function _ListeLecturePayline()
	{
		die;
		
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireView = false;
		$this->autoFireFooter = false;
		
		// On recup la lib et le reste payline
		require_once($this->path.'protected/payline/include.php');
		
		$this->transactions = $this->loadData('transactions');
		
		$trans = $this->transactions->select('type_transaction IN(1,3) AND status = 0 AND etat = 0 AND serialize_payline != ""','added ASC','800','100');
		
		
		foreach($trans as $t)
		{
			$leserial = unserialize($t['serialize_payline']);
			
			//print_r($leserial['token']);
			
			$array = array();
			$payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
			$array['token'] = $leserial['token'];
			$array['version'] = '3';
			$response = $payline->getWebPaymentDetails($array);
			
			if($response['result']['code'] == '00000')
			{
			
			echo $t['id_transaction'].'<br>';
			
			echo '-----------------------<br>';
			}
			
		}
		echo 'fin';
		
	}
	
	
	function _testmailbidkoapres()
	{
		die;
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		// Recuperation du modele de mail
		$this->mails_text->get('preteur-bid-ko-apres-fin-de-periode-projet','lang = "'.$this->language.'" AND type');
		

		// Variables du mailing
		$varMail = array(
		'surl' =>  $this->surl,
		'url' => $this->lurl,
		'prenom_p' => 'David',
		'valeur_bid' => number_format(1500, 2, ',', ' '),
		'taux_bid' => number_format(8.6, 2, ',', ' '),
		'nom_entreprise' => 'nom entreprise test',
		'projet-p' => $this->lurl.'/projects/detail/lenumprojet',
		'date_bid' => $this->dates->formatDate('2014-04-08 8:30:00','d/m/Y'),
		'heure_bid' => $this->dates->formatDate('2014-04-08 8:30:00','H\hi'),
		'fin_chrono' => '2 jours',
		'projet-bid' => $this->lurl.'/projects/detail/lenumprojet',
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
		
		Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.nandji@equinoa.com',$tabFiler);
		// Injection du mail NMP dans la queue
		$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
		//********************************//
		//*** FIN ENVOI DU MAIL BID KO ***//
		//********************************//	
	}
	
	function _mailkonopgoout()
	{
		$bids = $this->loadData('bids');
		$mails_filer = $this->loadData('mails_filer');
		$lenders_accounts = $this->loadData('lenders_accounts');
		$clients = $this->loadData('clients');
		
		$lBids = $bids->select('status = 2 AND status_email_bid_ko = 1 AND updated >= "2014-04-08 08:00:00" ','updated ASC');
		$a = 0;
		echo 'mis a jour a partir du 2014-04-08 08:00:00<br><br>';
		?>
        <table border="1">
        <tr>
        	<td>Id bid</td>
            <td>email</td>
            <td>date de degel</td>
        </tr>
        <?
		foreach($lBids as $b)
		{
			//11
			
			
			$lenders_accounts->get($b['id_lender_account'],'id_lender_account');
			$clients->get($lenders_accounts->id_client_owner,'id_client');
			
			$d = strtotime($b['updated']);
			$datedebut = mktime (date("H",$d),date("i",$d)-5,date("s",$d),date("m",$d),date("d",$d),date("Y",$d));
			$datedebut = date('Y-m-d H:i:s',$datedebut);
			
			$datefin = mktime (date("H",$d),date("i",$d)+5,date("s",$d),date("m",$d),date("d",$d),date("Y",$d));
			$datefin = date('Y-m-d H:i:s',$datefin);
			
			$results = $mails_filer->counter('email_nmp = "'.$clients->email.'" AND id_textemail = 11 AND added BETWEEN "'.$datedebut.'" AND "'.$datefin.'"');
			
			if($results == 0)
			{
			echo '<tr><td>'.$b['id_bid'].'</td><td>'.$clients->email.'</td><td>'.$b['updated'].'</td></tr>';
			
			$a++;
			}

		}
		echo '</table>';
		
		echo '<br><br>'.$a;
		die;
	}
	
	function _recuplemec()
	{
		$mails_filer = $this->loadData('mails_filer');
		$echeanciers = $this->loadData('echeanciers');
		$lenders_accounts = $this->loadData('lenders_accounts');
		$clients = $this->loadData('clients');
		
		$lEche = $echeanciers->select('id_project = 407 AND ordre = 2');
		
		foreach($lEche as $b)
		{
			
			$d = strtotime($b['date_echeance_reel']);
			$datedebut = mktime (date("H",$d),date("i",$d)-1,date("s",$d),date("m",$d),date("d",$d),date("Y",$d));
			$datedebut = date('Y-m-d H:i:s',$datedebut);
			
			$datefin = mktime (date("H",$d),date("i",$d)+1,date("s",$d),date("m",$d),date("d",$d),date("Y",$d));
			$datefin = date('Y-m-d H:i:s',$datefin);
			
			$lenders_accounts->get($b['id_lender'],'id_lender_account');
			$clients->get($lenders_accounts->id_client_owner,'id_client');	
			
			
			$results = $mails_filer->counter('email_nmp = "'.$clients->email.'" AND id_textemail = 8 AND added BETWEEN "'.$datedebut.'" AND "'.$datefin.'"');
			
			echo $b['id_echeancier'].' - '.$clients->email.' - '.$b['date_echeance_reel'].' - '.$results.'<br>';
		}
		die;
	}
	
	function _testAccept()
	{
		$accept = $this->loadData('acceptations_legal_docs');
		$clients = $this->loadData('clients');
		$bids = $this->loadData('bids');
		$receptions = $this->loadData('receptions');
		$lenders_accounts = $this->loadData('lenders_accounts');
		
		$lClients = $clients->select('status = 1');
		$i=0;
		$a=0;
		$nbVirements = 0;
		$nbBids = 0;
		foreach($lClients as $c)
		{
			// accepté
			if($accept->counter('id_client = '.$c['id_client']) > 0)
			{
				$i++;
			}
			// non accepté
			else
			{
				$a++;
				echo $c['id_client'].'<br>';
				$lenders_accounts->get($c['id_client'],'id_client_owner');
				if($bids->counter('id_lender_account = '.$lenders_accounts->id_lender_account) > 0)
				{
					$nbBids++;
					$lesclientsBids[$c['id_client']]['id_client'] = $c['id_client'];
					$lesclientsBids[$c['id_client']]['id_lender'] = $lenders_accounts->id_lender_account;
				}
				if($receptions->counter('type = 2 AND id_client = '.$c['id_client']) > 0)
				{
					$nbVirements++;
					$lesclientsVirements[$c['id_client']] = $c['id_client'];
				}
				
				if($lenders_accounts->type_transfert == 2)
				{
					$lesclientslender[$c['id_client']] = $c['id_client'];
				}
				
			}
		}
		
		//echo 'nombres ok : '.$i.'<br>';
		//echo 'nombres ko : '.$a;
		echo '-------------------<br>';
		echo 'nb bids : '.$nbBids.'<br>';
		echo 'nb virements : '.$nbVirements.'<br>';
		echo '---------Virements----------<br>';
		echo '<pre>';
		print_r($lesclientsVirements);
		echo '</pre>';
		echo '---------Bids----------<br>';
		echo '<pre>';
		print_r($lesclientsBids);
		echo '</pre>';
		echo '---------alim cb lender ----------<br>';
		echo '<pre>';
		print_r($lesclientslender);
		echo '</pre>';
		die;
	}
	
	function _checkOfflineetbid()
	{
		
		$clients = $this->loadData('clients');
		$bids = $this->loadData('bids');
		$loans = $this->loadData('loans');
		$lenders_accounts = $this->loadData('lenders_accounts');
		
		$lclients = $clients->select('status = 0 AND status_pre_emp IN(1,3)');
		$i = 0;
		foreach($lclients as $c)
		{
			$lenders_accounts->get($c['id_client'],'id_client_owner');
			
			$nb = $bids->counter('id_lender_account = '.$lenders_accounts->id_lender_account);
			if($nb > 0)
			{
				echo 'id client : '.$c['id_client'].' - id lender : '.$lenders_accounts->id_lender_account.' - nb bids : '.$nb;
				
				$nbLoans = $loans->counter('id_lender = '.$lenders_accounts->id_lender_account);
				
				if($nbLoans > 0)
				{
					echo ' - nb loans : '.$nbLoans	.'<br>';
				}
				else
				{
					echo '<br>';	
				}
				$i++;
			}
		}
		die;
	}
	
	function _checkOffline()
	{
		$clients = $this->loadData('clients');
		$users_history = $this->loadData('users_history');
		
		$histo = $users_history->select('id_form = 1','added ASC');
		foreach($histo as $h)
		{
			$table = unserialize($h['serialize']);	
			/*echo 'id_client : '.$table['id_client'];
			echo ' status : '.$table['status'].'<br>';
			echo '--------- <br>';*/
			
			if($clients->get($table['id_client'],'status = 0 AND id_client'))
			$latabl[$table['id_client']][] = $table['status'];
		}
		echo count($latabl);
			echo '<pre>';
			print_r($latabl);
			echo '</pre>';
			
			
		die;
	}
	
	function _test_retraitUnilend()
	{
		// chargement des datas
		$virements = $this->loadData('virements');
		$bank_unilend = $this->loadData('bank_unilend');
		$transactions = $this->loadData('transactions');
		
		// 3%+tva  + les retraits Unilend
		$comProjet = $bank_unilend->sumMontant('status IN(0,3) AND type IN(0,3) AND retrait_fiscale = 0 AND LEFT(updated,7) < "2014-06"');
		// com sur remb
		$comRemb = $bank_unilend->sumMontant('status = 1 AND type IN(1,2) AND LEFT(updated,7) < "2014-06"');
		
		$etatRemb = $bank_unilend->sumMontantEtat('status = 1 AND type IN(2) AND LEFT(updated,7) < "2014-06"');
		
		
		$fiscaleProjet = $bank_unilend->sumMontant('status IN(0,3) AND type IN(0,3) AND retrait_fiscale = 1 AND LEFT(updated,7) < "2014-06"');
		
		// On prend la com projet + la com sur les remb et on retire la partie pour l'etat
		$total = $comRemb+$comProjet-$etatRemb;
		echo ($total/100);
		
		echo '<br>'.$fiscaleProjet;
		//echo '<br>'.$etatRemb;
		
		die;
	}
	
	function _testmailER()
	{
		die;
		// MAIL FACTURE REMBOURSEMENT EMPRUNTEUR //
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;					
							
		// Chargement des datas
		$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
		$projects = $this->loadData('projects');
		$companies = $this->loadData('companies');
		$emprunteur = $this->loadData('clients');
		$projects_status_history = $this->loadData('projects_status_history'); 
		
		// On recup les infos de l'emprunteur
		$projects->get(1124,'id_project');											
		$companies->get($projects->id_company,'id_company');
		$emprunteur->get($companies->id_client_owner,'id_client');
		
		$dateRemb = $projects_status_history->select('id_project = '.$projects->id_project.' AND id_project_status = 8');
		//print_r($projects->id_project);
		
		$dateRemb =  date('d/m/Y',strtotime($dateRemb[0]['added']));
		
		$link = $this->furl.'/pdf/facture_ER/'.$emprunteur->hash.'/129/5';
		
		//********************************//
		//*** ENVOI DU MAIL FACTURE ER ***//
		//********************************//
		
		// Recuperation du modele de mail
		$this->mails_text->get('facture-emprunteur-remboursement','lang = "'.$this->language.'" AND type');
		

		// Variables du mailing
		$varMail = array(
		'surl' =>  $this->surl,
		'url' => $this->furl,
		'prenom' => $emprunteur->prenom,
		'pret' => number_format($projects->amount, 2, ',', ' '),
		'entreprise' => stripslashes(trim($companies->name)),
		'projet-title' => $projects->title,
		'compte-p' => $this->furl,
		'projet-p' => $this->furl.'/projects/detail/'.$projects->slug,
		'link_facture' => $link,
		'datedelafacture' => $dateRemb,
		'mois' => strtolower($this->dates->tableauMois['fr'][date('n')]),
		'annee' => date('Y'),
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
		//$this->email->addBCCRecipient('nicolas.lesur@unilend.fr');
		//$this->email->addBCCRecipient('d.nandji@equinoa.com');
		
		$this->email->setSubject(stripslashes($sujetMail));
		$this->email->setHTMLBody(stripslashes($texteMail));
		//Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($companies->email_facture),$tabFiler);
					
		// Injection du mail NMP dans la queue
		$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
		
		//////////////////////////////////////////////
		
		die;
	}
	
	// test universign (le 16/06/2014)
	function _testUniversign()
	{
		die;
		include($this->path.'protected/xmlrpc-3.0.0.beta/lib/xmlrpc.inc');
				
		//used variables
		//$uni_url = '';
		$uni_url = "https://t.raymond@equinoa.com:g5rtohav@ws.universign.eu/sign/rpc/"; // address of the universign server with basic authentication
		$uni_id = '3c00a520-f244-31e3-b201-04c9748b4469'; // a collection id
		
		//create the request
		$c = new xmlrpc_client($uni_url);
		$f = new xmlrpcmsg('requester.getDocumentsByTransactionId', array(new xmlrpcval($uni_id, "string")));
		
		//Send request and analyse response
		$r = &$c->send($f);
		
		echo '<pre>';
		print_r($r);
		echo '</pre>';
		
		if (!$r->faultCode()) {
		   //if the request succeeded
		   $doc['name'] = $r->value()->arrayMem(0)->structMem('name')->scalarVal();
		   $doc['content'] = $r->value()->arrayMem(0)->structMem('content')->scalarVal(); 
		}
		die;
	}
	
	// permet de générer le mail facture financement qui est envoyé lors du changement de staut remboursment (mis a jour le 17/06/2014)
	function _email_facture_EF()
	{
		
		die;
		// Renseigner l'id projet
		$id_project = 5841;
		//$dateStatutRemb = date('d/m/Y');
		$dateStatutRemb = '05/02/2015';
		
		//********************************//
		//*** ENVOI DU MAIL FACTURE EF ***//
		//********************************//
		
		// Recuperation du modele de mail
		$this->mails_text->get('facture-emprunteur','lang = "'.$this->language.'" AND type');
		
		$leProject = $this->loadData('projects');
		$lemprunteur = $this->loadData('clients');
		$laCompanie = $this->loadData('companies');
		
		$leProject->get($id_project,'id_project');
		$laCompanie->get($leProject->id_company,'id_company');
		$lemprunteur->get($laCompanie->id_client_owner,'id_client');
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		// Variables du mailing
		$varMail = array(
		'surl' =>  $this->surl,
		'url' => $this->furl,
		'prenom' => $lemprunteur->prenom,
		'entreprise' => $laCompanie->name,
		'pret' => number_format($leProject->amount, 2, ',', ' '),
		'projet-title' => $leProject->title,
		'compte-p' => $this->furl,
		'projet-p' => $this->furl.'/projects/detail/'.$leProject->slug,
		'link_facture' => $this->furl.'/pdf/facture_EF/'.$lemprunteur->hash.'/'.$leProject->id_project.'/',
		'datedelafacture' => $dateStatutRemb,
		'mois' => strtolower($this->dates->tableauMois['fr'][date('n')]),
		'annee' => date('Y'),
		'lien_fb' => $lien_fb,
		'lien_tw' => $lien_tw);	
		
		// Construction du tableau avec les balises EMV
		$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
		
		// Attribution des données aux variables
		$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);				
		$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
		$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
//die;
		// Envoi du mail
		$this->email = $this->loadLib('email',array());
		$this->email->setFrom($this->mails_text->exp_email,$exp_name);
		if($this->Config['env'] == 'prod')
		{
			$this->email->addBCCRecipient('nicolas.lesur@unilend.fr');
			$this->email->addBCCRecipient('d.nandji@equinoa.com');
			
			//$this->email->addBCCRecipient('d.courtier@equinoa.com');
			//$this->email->addBCCRecipient('courtier.damien@gmail.com');
		}
		
		$this->email->setSubject(stripslashes($sujetMail));
		$this->email->setHTMLBody(stripslashes($texteMail));
		
		Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($laCompanie->email_facture),$tabFiler);	
		
		//Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim('d.courtier@relance.fr'),$tabFiler);
		
		// Injection du mail NMP dans la queue
		$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
		
		die;
	}
	
	//  genere les mails de confirmation preteur pour un bid d'un projet - mis en place le 17/06/2014
	function _lescontratsPreteurs()
	{
		die;
		$this->loans = $this->loadData('loans');
		$this->projects = $this->loadData('projects');
		
		$preteur = $this->loadData('clients');
		$lender = $this->loadData('lenders_accounts');
		$leProject = $this->loadData('projects');
		$laCompanie = $this->loadData('companies');
		$echeanciers = $this->loadData('echeanciers');
		
		// On renseigne le projet
		$id_project = 1009;
		
		$this->projects->get($id_project,'id_project');
		
		$lLoans = $this->loans->select('id_project = '.$this->projects->id_project);
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		foreach($lLoans as $l)
		{
			// lender
			$lender->get($l['id_lender'],'id_lender_account');
			// preteur (client)
			$preteur->get($lender->id_client_owner,'id_client');
			
			//mail('d.courtier@equinoa.com','test unilend '.$l['id_lender'],'le contrat '.$l['id_lender'].' : '.$link_contrat);
			
			
			//******************************//
			//*** ENVOI DU MAIL CONTRAT ***//
			//******************************//
			
			// Recuperation du modele de mail
			$this->mails_text->get('preteur-contrat','lang = "'.$this->language.'" AND type');
			
			// on recup la premiere echeance
			//$lecheancier = $echeanciers->getPremiereEcheancePreteur($l['id_project'],$l['id_lender']);
			$lecheancier = $echeanciers->getPremiereEcheancePreteurByLoans($l['id_project'],$l['id_lender'],$l['id_loan']);
			
			$leProject->get($l['id_project'],'id_project');
			$laCompanie->get($leProject->id_company,'id_company');
		
			// Variables du mailing
			$surl = $this->surl;
			$url = $this->furl;
			$prenom = $preteur->prenom;
			$projet = $this->projects->title;
			$montant_pret = number_format($l['amount']/100, 2, ',', ' ');
			$taux = number_format($l['rate'], 2, ',', ' ');
			$entreprise = $laCompanie->name;
			$date = $this->dates->formatDate($l['added'],'d/m/Y');
			$heure = $this->dates->formatDate($l['added'],'H');
			$duree = $this->projects->period;
			$link_contrat = $this->furl.'/pdf/contrat/'.$preteur->hash.'/'.$l['id_loan'];
			
			// Variables du mailing
			$varMail = array(
			'surl' => $surl,
			'url' => $url,
			'prenom_p' => $prenom,
			'valeur_bid' => $montant_pret,
			'taux_bid' => $taux,
			'nom_entreprise' => $entreprise,
			'nbre_echeance' => $duree,
			'mensualite_p' => number_format($lecheancier['montant']/100, 2, ',', ' '),
			'date_debut' => date('d/m/Y',strtotime($lecheancier['date_echeance'])),
			'compte-p' => $this->furl,
			'projet-p' => $this->furl.'/projects/detail/'.$this->projects->slug,
			'link_contrat' => $link_contrat,
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
			//$this->email->addRecipient(trim('d.courtier@equinoa.com'));
			//$this->email->addBCCRecipient($this->clients->email);
			
			$this->email->setSubject(stripslashes($sujetMail));
			$this->email->setHTMLBody(stripslashes($texteMail));
			Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($preteur->email),$tabFiler);
			
						
			// Injection du mail NMP dans la queue
			$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);

			// fin mail

		}	
		die;
	}

	function _testMailnmp()
	{
		//************************************//
		//*** ENVOI DU MAIL GENERATION MDP ***//
		//************************************//

		// Recuperation du modele de mail
		$this->mails_text->get('email-test','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->furl;
		$login = $this->clients->email;
		$mdp = $pass;
		
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
		'login' => 'test@equinoa.fr',
		'prenom_p' => 'damien',
		'mdp' => 'Mot de passe : 123456789',
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
		Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'d.courtier@relance.fr',$tabFiler);
		
		// Injection du mail NMP dans la queue
		$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
		// fin mail
		die;
	}
	
	
	function _testmailbidkio()
	{
		$this->bids = $this->loadData('bids');
		
		$lBidsKO = $this->bids->select('status = 2 AND status_email_bid_ko = 0 AND updated < "2014-07-12"');
		echo count($lBidsKO);
		die;	
	}
	
	// email bid accepter executé au moment du changement de statut du remboursement (mis en place 18/07/2014) <---- regule bid accepté a utiliser
	function _test_maildamien()
	{
		die;
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		$echeanciers = $this->loadData('echeanciers');
		
		$this->notifications = $this->loadData('notifications');
		$this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
		$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
		
		$this->projects->get('5418','id_project'); // <------------------- id projet a mettre ici
		die;
		$lLoans = $this->loans->select('id_project = '.$this->projects->id_project);
			
		/*echo '<pre>';
		print_r($lLoans);
		echo '</pre>';
		die;	*/					
		$preteur = $this->loadData('clients');
		$lender = $this->loadData('lenders_accounts');
		$leProject = $this->loadData('projects');
		$laCompanie = $this->loadData('companies');
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		foreach($lLoans as $l)
		{

			// lender
			$lender->get($l['id_lender'],'id_lender_account');
			// preteur (client)
			$preteur->get($lender->id_client_owner,'id_client');
			
			$this->notifications->type = 4; // accepté
			$this->notifications->id_lender = $l['id_lender'];
			$this->notifications->id_project = $l['id_project'];
			$this->notifications->amount = $l['amount'];
			$this->notifications->id_bid = $l['id_bid'];
			$this->notifications->id_notification = $this->notifications->create();
			
			//////// GESTION ALERTES //////////
			$this->clients_gestion_mails_notif->id_client = $lender->id_client_owner;
			$this->clients_gestion_mails_notif->id_notif = 4; // offre acceptée
			$this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
			$this->clients_gestion_mails_notif->id_transaction = 0;
			$this->clients_gestion_mails_notif->date_notif = date('Y-m-d H:i:s');
			$this->clients_gestion_mails_notif->id_loan = $this->loans->id_loan;
			$this->clients_gestion_mails_notif->create();
			//////// FIN GESTION ALERTES //////////
			
			if($this->clients_gestion_notifications->getNotif($lender->id_client_owner,4,'immediatement') == true){
				
				//////// GESTION ALERTES //////////
				$this->clients_gestion_mails_notif->get($l['id_loan'],'id_client = '.$lender->id_client_owner.' AND id_loan');
				$this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
				$this->clients_gestion_mails_notif->update();
				//////// FIN GESTION ALERTES //////////
			
				// Motif virement
				$p = substr($this->ficelle->stripAccents(utf8_decode($preteur->prenom)),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode($preteur->nom));
				$id_client = str_pad($preteur->id_client,6,0,STR_PAD_LEFT);
				$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				
				//******************************//
				//*** ENVOI DU MAIL CONTRAT ***//
				//******************************//
				
				// Recuperation du modele de mail
				$this->mails_text->get('preteur-contrat','lang = "'.$this->language.'" AND type');
				
				$lecheancier = $echeanciers->getPremiereEcheancePreteurByLoans($l['id_project'],$l['id_lender'],$l['id_loan']);
				
				$leProject->get($l['id_project'],'id_project');
				$laCompanie->get($leProject->id_company,'id_company');
			
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->furl;
				$prenom = $preteur->prenom;
				$projet = $this->projects->title;
				$montant_pret = number_format($l['amount']/100, 2, ',', ' ');
				$taux = number_format($l['rate'], 2, ',', ' ');
				$entreprise = $laCompanie->name;
				$date = $this->dates->formatDate($l['added'],'d/m/Y');
				$heure = $this->dates->formatDate($l['added'],'H');
				$duree = $this->projects->period;
				$link_contrat = $this->furl.'/pdf/contrat/'.$preteur->hash.'/'.$l['id_loan'];
				
				// Variables du mailing
				$varMail = array(
				'surl' => $surl,
				'url' => $url,
				'prenom_p' => $prenom,
				'valeur_bid' => $montant_pret,
				'taux_bid' => $taux,
				'nom_entreprise' => $entreprise,
				'nbre_echeance' => $duree,
				'mensualite_p' => number_format($lecheancier['montant']/100, 2, ',', ' '),
				'date_debut' => date('d/m/Y',strtotime($lecheancier['date_echeance'])),
				'compte-p' => $this->furl,
				'projet-p' => $this->furl.'/projects/detail/'.$this->projects->slug,
				'link_contrat' => $link_contrat,
				'motif_virement' => $motif,
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
					Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($preteur->email),$tabFiler);			
					// Injection du mail NMP dans la queue
					$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
				}
				else // non nmp
				{
					$this->email->addRecipient(trim($preteur->email));
					Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
				}
				// fin mail
			}
		}
	die;		
	}
	
	function _testaaalimentation()
	{
		die;
		//******************************//
		//*** ENVOI DU MAIL preteur-alimentation ***//
		//******************************//

		// Recuperation du modele de mail
		$this->mails_text->get('preteur-alimentation','lang = "'.$this->language.'" AND type');
		
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		// Variables du mailing
		$varMail = array(
		'surl' => $this->surl,
		'url' => $this->lurl,
		'prenom_p' => 'Bàaliôu test email equinoa',
		'fonds_depot' => '15 000',
		'solde_p' => '150 000',
		'motif_virement' => 'BOOULOULILOU',
		'lien_fb' => $lien_fb,
		'lien_tw' => $lien_tw);	
		
		// Construction du tableau avec les balises EMV
		$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
		
		// Attribution des données aux variables
		$sujetMail = strtr($this->mails_text->subject,$tabVars);				
		$texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
		$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
		
		// Envoi du mail
		$this->email = $this->loadLib('email',array());
		$this->email->setFrom($this->mails_text->exp_email,$exp_name);
		
		$this->email->setSubject(stripslashes($sujetMail));
		$this->email->setHTMLBody(stripslashes($texteMail));
		
		if($this->Config['env'] == 'prod') // nmp
		{
			Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,'courtier.damien@gmail.com',$tabFiler);
						
			// Injection du mail NMP dans la queue
			$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
		}
		else // non nmp
		{
			$this->email->addRecipient(trim('courtier.damien@gmail.com'));
			Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
		}
		// fin mail	
		die;
	}
	
	function _statutClient()
	{
		// etape 1
		//UPDATE `unilend`.`clients` SET `etape_inscription_preteur` = '3' WHERE `clients`.`status` = 1 AND `clients`.`status_pre_emp` = 1;
		
		
		// etape 2
		$this->clients = $this->loadData('clients');
		$this->clients_status_history = $this->loadData('clients_status_history');
		$lClients = $this->clients->select('status = 1 AND etape_inscription_preteur = 3 AND status_pre_emp = 1');	
		
		echo count($lClients);
		foreach($lClients as $f){
			//echo $f['id_client'].'<br>';
			if($this->clients_status_history->counter('id_client = '.$f['id_client']) == 0){
			//echo $f['id_client'].'<br>';
			$this->clients_status_history->addStatus(1,'60',$f['id_client']);
			}
		}

		die;
	}
	
	// remboursement preteurs partie remb preteurs
	function _correctionRembPreteur()
	{
		die;
		$debut = time().'<br>';
		
		$this->echeanciers = $this->loadData('echeanciers');
		$this->transactions = $this->loadData('transactions');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients = $this->loadData('clients');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->notifications = $this->loadData('notifications');
		$this->companies = $this->loadData('companies');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		
		
		$id_project = 1124;
		$ordre = 1;
		
		$this->projects->get($id_project,'id_project');
		$this->companies->get($this->projects->id_company,'id_company');
		
		$lEcheances = $this->echeanciers->select('id_project = '.$id_project.' AND status_emprunteur = 1 AND ordre = '.$ordre.' AND status = 1');
										
		foreach($lEcheances as $e)
		{
			// Remb net preteur
			$rembNet = ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
			
			// Partie pour l'etat sur un remb preteur
			$etat = $e['prelevements_obligatoires'] + $e['retenues_source'] + $e['csg'] + $e['prelevements_sociaux'] + $e['contributions_additionnelles'] + $e['prelevements_solidarite'] + $e['crds'];
			
			$debutcreate = microtime();
			if($this->transactions->get($e['id_echeancier'],'id_echeancier')==false)
			{
				
				// On recup lenders_accounts
				$this->lenders_accounts->get($e['id_lender'],'id_lender_account');
				// On recup le client
				$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
				
				// echeance preteur
				$this->echeanciers->get($e['id_echeancier'],'id_echeancier');
				$this->echeanciers->status = 1; // remboursé
				$this->echeanciers->date_echeance_reel = date('Y-m-d H:i:s');
				$this->echeanciers->update();
									
				// On enregistre la transaction
				$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
				$this->transactions->montant = ($rembNet*100);
				$this->transactions->id_echeancier = $e['id_echeancier']; // id de l'echeance remb
				$this->transactions->id_langue = 'fr';
				$this->transactions->date_transaction = date('Y-m-d H:i:s');
				$this->transactions->status = '1';
				$this->transactions->etat = '1';
				$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
				$this->transactions->type_transaction = 5; // remb enchere
				$this->transactions->transaction = 2; // transaction virtuelle
				$this->transactions->id_transaction = $this->transactions->create();
				
				// on enregistre la transaction dans son wallet
				$this->wallets_lines->id_lender = $e['id_lender'];
				$this->wallets_lines->type_financial_operation = 40;
				$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
				$this->wallets_lines->status = 1; // non utilisé
				$this->wallets_lines->type = 2; // transaction virtuelle
				$this->wallets_lines->amount = ($rembNet*100);
				$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
				
				// On enregistre la notification pour le preteur
				$this->notifications->type = 2; // remb
				$this->notifications->id_lender = $this->lenders_accounts->id_lender_account;
				$this->notifications->id_project = $this->projects->id_project;
				$this->notifications->amount = ($rembNet*100);
				$this->notifications->create();
				
				// Motif virement
				$p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
				$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
				$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				
				
				//*******************************************//
				//*** ENVOI DU MAIL REMBOURSEMENT PRETEUR ***//
				//*******************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('preteur-remboursement','lang = "'.$this->language.'" AND type');
				
				
				$nbpret = $this->loans->counter('id_lender = '.$e['id_lender'].' AND id_project = '.$e['id_project']);
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->furl;
				
				// euro avec ou sans "s"
				if($rembNet >= 2)$euros = ' euros';
				else $euros = ' euro';
				$rembNetEmail = number_format($rembNet, 2, ',', ' ').$euros;
				
				if($this->transactions->getSolde($this->clients->id_client)>=2)$euros = ' euros';
				else $euros = ' euro';
				$solde = number_format($this->transactions->getSolde($this->clients->id_client), 2, ',', ' ').$euros;
	
				$timeAdd = strtotime($dateDernierStatut);
				$month = $this->dates->tableauMois['fr'][date('n',$tiAmeAdd)];
				
				// Variables du mailing
				$varMail = array(
				'surl' => $surl,
				'url' => $url,
				'prenom_p' => $this->clients->prenom,
				'mensualite_p' => $rembNetEmail,
				'mensualite_avantfisca' => ($e['montant']/100),
				'nom_entreprise' => $this->companies->name,
				'date_bid_accepte' => date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd),
				'nbre_prets' => $nbpret,
				'solde_p' => $solde,
				'motif_virement' => $motif,
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
					//$this->email->addRecipient(trim($this->clients->email));
					//Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
				}
				// fin mail pour preteur //
				
			}
			
		}
		$fin = time();
		
		
		echo '<br>----------------------<br>';
		echo 'total : '.($fin-$debut);
		echo '<br>----------------------<br>';
		
		die;	
	}
	
	// remboursement preteurs partie emprunteur et unilend
	function _partie2delacorrection()
	{
		
		$this->echeanciers = $this->loadData('echeanciers');
		$this->transactions = $this->loadData('transactions');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients = $this->loadData('clients');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->notifications = $this->loadData('notifications');
		$this->companies = $this->loadData('companies');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		$this->bank_unilend = $this->loadData('bank_unilend');
		
		
		$id_project = 1124;
		$ordre = 4;
		
		$this->projects->get($id_project,'id_project');
		$this->companies->get($this->projects->id_company,'id_company');
		
		/*$lEcheances = $this->echeanciers->select('id_project = '.$id_project.' AND status_emprunteur = 1 AND ordre = '.$ordre.' AND status = 1');
		
		// On déclare les variables
		$montant = 0;
		$capital = 0;
		$interets = 0;
		$commission = 0;
		$tva = 0;
		$prelevements_obligatoires = 0;
		$retenues_source = 0;
		$csg = 0;
		$prelevements_sociaux = 0;
		$contributions_additionnelles = 0;
		$prelevements_solidarite = 0;
		$crds = 0;
		
		$rembNet = 0;
		$etat = 0;
		
		$rembNetTotal = 0;
		$TotalEtat = 0;
										
		foreach($lEcheances as $e)
		{
			// on fait la somme de tout
			$montant += ($e['montant']/100);
			$capital += ($e['capital']/100);
			$interets += ($e['interets']/100);
			$commission += ($e['commission']/100);
			$tva += ($e['tva']/100);
			$prelevements_obligatoires += $e['prelevements_obligatoires'];
			$retenues_source += $e['retenues_source'];
			$csg += $e['csg'];
			$prelevements_sociaux += $e['prelevements_sociaux'];
			$contributions_additionnelles += $e['contributions_additionnelles'];
			$prelevements_solidarite += $e['prelevements_solidarite'];
			$crds += $e['crds'];
		}
		
		// partie a retirer de bank unilend
		$rembNetTotal = $montant - $prelevements_obligatoires - $retenues_source - $csg -$prelevements_sociaux - $contributions_additionnelles - $prelevements_solidarite - $crds;
		
		// partie pour l'etat
		$TotalEtat = $prelevements_obligatoires + $retenues_source + $csg + $prelevements_sociaux + $contributions_additionnelles + $prelevements_solidarite + $crds;*/
		
		// On evite de créer une ligne qui sert a rien
		if($rembNetTotal != 0 || 5 == 5)
		{
			// On enregistre la transaction
			/*$this->transactions->montant = 0;
			$this->transactions->id_echeancier = 0; // on reinitialise
			$this->transactions->id_client = 0; // on reinitialise
			$this->transactions->montant_unilend = '-'.$rembNetTotal*100;
			$this->transactions->montant_etat = $TotalEtat*100;
			$this->transactions->id_echeancier_emprunteur = '2557'; // id de l'echeance emprunteur
			$this->transactions->id_langue = 'fr';
			$this->transactions->date_transaction = date('Y-m-d H:i:s');
			$this->transactions->status = '1';
			$this->transactions->etat = '1';
			$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
			$this->transactions->type_transaction = 10; // remb unilend pour les preteurs
			$this->transactions->transaction = 2; // transaction virtuelle
			//$this->transactions->id_transaction = $this->transactions->create();
			
			
			// bank_unilend (on retire l'argent redistribué)
			$this->bank_unilend->id_transaction = $this->transactions->id_transaction;
			$this->bank_unilend->id_project = $this->projects->id_project; 
			$this->bank_unilend->montant = '-'.$rembNetTotal*100;
			$this->bank_unilend->etat = $TotalEtat*100;
			$this->bank_unilend->type = 2; // remb unilend
			$this->bank_unilend->id_echeance_emprunteur = '2557';
			$this->bank_unilend->status = 1;*/
			//$this->bank_unilend->create();
			
			// MAIL FACTURE REMBOURSEMENT EMPRUNTEUR //
			
			
			// Chargement des datas
			$echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
			$projects = $this->loadData('projects');
			$companies = $this->loadData('companies');
			$emprunteur = $this->loadData('clients');
			$projects_status_history = $this->loadData('projects_status_history'); 
			
			// On recup les infos de l'emprunteur
			$projects->get($this->projects->id_project,'id_project');											
			$companies->get($projects->id_company,'id_company');
			$emprunteur->get($companies->id_client_owner,'id_client');
			
			$link = $this->furl.'/pdf/facture_ER/'.$emprunteur->hash.'/'.$projects->id_project.'/'.$ordre;
			
			$dateRemb = $projects_status_history->select('id_project = '.$projects->id_project.' AND id_project_status = 8');

			$timeAdd = strtotime($dateRemb[0]['added']);
			$month = $this->dates->tableauMois['fr'][date('n',$tiAmeAdd)];
			
			$dateRemb =  date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd);
			
			
			//********************************//
			//*** ENVOI DU MAIL FACTURE ER ***//
			//********************************//
			
			// Recuperation du modele de mail
			$this->mails_text->get('facture-emprunteur-remboursement','lang = "'.$this->language.'" AND type');
			

			// Variables du mailing
			$varMail = array(
			'surl' =>  $this->surl,
			'url' => $this->furl,
			'prenom' => $emprunteur->prenom,
			'pret' => number_format($projects->amount, 2, ',', ' '),
			'entreprise' => stripslashes(trim($companies->name)),
			'projet-title' => $projects->title,
			'compte-p' => $this->furl,
			'projet-p' => $this->furl.'/projects/detail/'.$projects->slug,
			'link_facture' => $link,
			'datedelafacture' => $dateRemb,
			'mois' => strtolower($this->dates->tableauMois['fr'][date('n')]),
			'annee' => date('Y'),
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
			if($this->Config['env'] == 'prod')
			{
				//$this->email->addBCCRecipient('nicolas.lesur@unilend.fr');
				//$this->email->addBCCRecipient('d.nandji@equinoa.com');
			}
			
			$this->email->setSubject(stripslashes($sujetMail));
			$this->email->setHTMLBody(stripslashes($texteMail));
			
			if($this->Config['env'] == 'prod') // nmp
			{
				Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,trim($companies->email_facture),$tabFiler);
				// Injection du mail NMP dans la queue
				$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
			}
			else // non nmp
			{
				//$this->email->addRecipient(trim($companies->email_facture));
				//Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
			}
			
			
			//$lesRembEmprun = $this->bank_unilend->select('type = 1 AND status = 0 AND id_project = '.$this->projects->id_project);
			// On parcourt les remb non reversé aux preteurs dans bank unilend et on met a jour le satut pour dire que c'est remb
			/*foreach($lesRembEmprun as $r)
			{
				$this->bank_unilend->get($r['id_unilend'],'id_unilend');
				$this->bank_unilend->status = 1;
				$this->bank_unilend->update();
			}*/
		
		}
		
		die;
	}
	
	
	// remboursement preteurs partie remb preteurs
	function _testRembPreteur()
	{
		
		ini_set('max_execution_time', 300); //300 seconds = 5 minutes
		$debut = time();
		
		$this->echeanciers = $this->loadData('echeanciers');
		$this->transactions = $this->loadData('transactions');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients = $this->loadData('clients');
		$this->wallets_lines = $this->loadData('wallets_lines');
		$this->notifications = $this->loadData('notifications');
		$this->companies = $this->loadData('companies');
		$this->projects = $this->loadData('projects');
		$this->loans = $this->loadData('loans');
		
		
		$id_project = 1124;
		$ordre = 4;
		
		
		
		$this->projects->get($id_project,'id_project');
		$this->companies->get($this->projects->id_company,'id_company');
		
		
		$lEcheances = $this->echeanciers->select('id_project = '.$id_project.' AND status_emprunteur = 1 AND ordre = '.$ordre.' AND status = 1');
		
		//$lEcheances = $this->echeanciers->selectEcheances_a_remb('id_project = '.$id_project.' AND status_emprunteur = 1 AND ordre = '.$ordre.' AND status = 0');
		//echo count($lEcheances);
		
		$rembNet = 0;
		$etat = 0;
		foreach($lEcheances as $e)
		{
			// Remb net preteur
			$rembNet += ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
			
			// Partie pour l'etat sur un remb preteur
			$etat += $e['prelevements_obligatoires'] + $e['retenues_source'] + $e['csg'] + $e['prelevements_sociaux'] + $e['contributions_additionnelles'] + $e['prelevements_solidarite'] + $e['crds'];
		}
		
		echo 'remb net : '.$rembNet.'<br>'; 
		echo 'etat : '.$etat;
		
		die;
			
		foreach($lEcheances as $e)
		{
			// Remb net preteur
			$rembNet = ($e['montant']/100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
			
			// Partie pour l'etat sur un remb preteur
			$etat = $e['prelevements_obligatoires'] + $e['retenues_source'] + $e['csg'] + $e['prelevements_sociaux'] + $e['contributions_additionnelles'] + $e['prelevements_solidarite'] + $e['crds'];
			
			/*echo '<pre>';
			print_r($lEcheances2[$a]);
			echo '</pre>';
			
			echo 'remb net : '.$rembNet.'<br>';
			echo 'etat : '.$etat.'<br>';
			echo '----------------<br>';*/
			
			$e['rembNet'] = $rembNet;
			$e['etat'] = $etat;
			
			//$rembNet = $e['rembNet'];
			//$etat = $e['etat'];
			
			//$rembNetTotal += $e['rembNet'];
			//$TotalEtat += $e['etat'];
			
			//$debutcreate = microtime();
			if($this->transactions->get($e['id_echeancier'],'id_echeancier')==false)
			{
				
				// On recup lenders_accounts
				$this->lenders_accounts->get($e['id_lender'],'id_lender_account');
				// On recup le client
				$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
				
				// echeance preteur
				$this->echeanciers->get($e['id_echeancier'],'id_echeancier');
				$this->echeanciers->status = 1; // remboursé
				$this->echeanciers->date_echeance_reel = date('Y-m-d H:i:s');
				//$this->echeanciers->update();
									
				// On enregistre la transaction
				$this->transactions->id_client = $this->lenders_accounts->id_client_owner;
				$this->transactions->montant = ($e['rembNet']*100);
				$this->transactions->id_echeancier = $e['id_echeancier']; // id de l'echeance remb
				$this->transactions->id_langue = 'fr';
				$this->transactions->date_transaction = date('Y-m-d H:i:s');
				$this->transactions->status = '1';
				$this->transactions->etat = '1';
				$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
				$this->transactions->type_transaction = 5; // remb enchere
				$this->transactions->transaction = 2; // transaction virtuelle
				//$this->transactions->id_transaction = $this->transactions->create();
				
				// on enregistre la transaction dans son wallet
				$this->wallets_lines->id_lender = $e['id_lender'];
				$this->wallets_lines->type_financial_operation = 40;
				$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
				$this->wallets_lines->status = 1; // non utilisé
				$this->wallets_lines->type = 2; // transaction virtuelle
				$this->wallets_lines->amount = ($e['rembNet']*100);
				//$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
				
				// On enregistre la notification pour le preteur
				$this->notifications->type = 2; // remb
				$this->notifications->id_lender = $this->lenders_accounts->id_lender_account;
				$this->notifications->id_project = $this->projects->id_project;
				$this->notifications->amount = ($e['rembNet']*100);
				//$this->notifications->create();
				
				// Motif virement
				$p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
				$id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
				$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				
				//*******************************************//
				//*** ENVOI DU MAIL REMBOURSEMENT PRETEUR ***//
				//*******************************************//
	
				// Recuperation du modele de mail
				$this->mails_text->get('preteur-remboursement','lang = "'.$this->language.'" AND type');
				
				$nbpret = $this->loans->counter('id_lender = '.$e['id_lender'].' AND id_project = '.$e['id_project']);
				
				// Variables du mailing
				$surl = $this->surl;
				$url = $this->furl;
				
				// euro avec ou sans "s"
				if($rembNet >= 2)$euros = ' euros';
				else $euros = ' euro';
				$rembNetEmail = number_format($e['rembNet'], 2, ',', ' ').$euros;
				
				if($this->transactions->getSolde($this->clients->id_client)>=2)$euros = ' euros';
				else $euros = ' euro';
				$solde = number_format($this->transactions->getSolde($this->clients->id_client), 2, ',', ' ').$euros;
	
				$timeAdd = strtotime($dateDernierStatut);
				$month = $this->dates->tableauMois['fr'][date('n',$tiAmeAdd)];
				
				// Variables du mailing
				$varMail = array(
				'surl' => $surl,
				'url' => $url,
				'prenom_p' => $this->clients->prenom,
				'mensualite_p' => $rembNetEmail,
				'mensualite_avantfisca' => ($e['montant']/100),
				'nom_entreprise' => $this->companies->name,
				'date_bid_accepte' => date('d',$timeAdd).' '.$month.' '.date('Y',$timeAdd),
				'nbre_prets' => $nbpret,
				'solde_p' => $solde,
				'motif_virement' => $motif,
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
					//Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$this->clients->email,$tabFiler);			
					// Injection du mail NMP dans la queue
					//$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
				}
				else // non nmp
				{
					//$this->email->addRecipient(trim($this->clients->email));
					//Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
				}
				// fin mail pour preteur //
				

			}
			
			echo  $a.' - '.$motif.'<br>';
			$a++;
			//if($a == 500)break;
			//else $a++;
			
		}
		$fin = time();
		
		echo 'test 5';
		echo '<br>----------------------<br>';
		echo 'total : '.($fin-$debut);
		echo '<br>----------------------<br>';
		//echo $tempsMail;
		die;	
	}
	
	function _version2test(){
		$this->clients = $this->loadData('clients');
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		$list = $this->acceptations_legal_docs->select('id_acceptation BETWEEN 2278 AND 2347','id_acceptation asc');
		
		echo '<table border="1">
		
		<tr>
		<th>id_acceptation</th>
		<th>id client</th>
		<th>added</th>
		</tr>
		';
		foreach($list as $l){
			
			
			$this->clients->get($l['id_client'],'id_client');
			echo '
			<tr>
				<td>'.$l['id_acceptation'].'</td>
				<td>'.$l['id_client'].'</td>
				<td>'.$this->clients->added.'</td>
			</tr>';
		}
		echo '</table>';
		die;
	}
	
	function _cherchelesPreteursSansCGV(){
		
		$this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
		$this->clients = $this->loadData('clients');
		$this->transactions = $this->loadData('transactions');
		$this->bids = $this->loadData('bids');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->clients_history = $this->loadData('clients_history');
		
		$liste = $this->clients->selectPreteursByStatus('60','c.added >= "2014-08-01 00:00:00"','id_client ASC');
		echo count($liste).'<br>----------------<br>';
		$i = 0;
		echo '<table border="1">';
		echo '
		<tr>
			<th>id client</th>
			<th>type</th>
			<th>solde</th>
			<th>nbBis</th>
			<th>nbBis OK</th>
			<th>creation compte</th>
			<th>Date creation compte</th>
			<th>added</th>
		</tr>';
		foreach($liste as $c){
			if($this->acceptations_legal_docs->counter('id_client = '.$c['id_client']) <= 0){
				
				$this->lenders_accounts->get($c['id_client'],'id_client_owner');
				
				$solde = $this->transactions->getSolde($c['id_client']);
				$bids = $this->bids->counter('id_lender_account = '.$this->lenders_accounts->id_lender_account);
				$bidsValid = $this->bids->counter('id_lender_account = '.$this->lenders_accounts->id_lender_account.' AND status = 2');
				$CreationcompteTemp = $this->clients_history->select('id_client = '.$c['id_client'].' AND status = 2');
				$Creationcompte = count($CreationcompteTemp);

				
					echo '<tr>';
					echo '
					<td>'.$c['id_client'].'</td>
					<td>'.$c['type'].'</td>
					<td>'.$solde.'</td>
					<td>'.$bids.'</td>
					<td>'.$bidsValid.'</td>
					<td>'.$Creationcompte.'</td>
					<td>'.$CreationcompteTemp[0]['added'].'</td>
					<td>'.$c['added'].'</td>';
					echo '</tr>';
				
					//$this->acceptations_legal_docs->id_legal_doc = 92;
					//$this->acceptations_legal_docs->id_client = $c['id_client'];
					//$this->acceptations_legal_docs->added = $c['added'];
					//$this->acceptations_legal_docs->create();
				
				$i++;
				
			}
		}
		echo '</table>';
		
		echo '<br>--------------------<br>';
		echo "Total : ".$i;
		die;	
	}
	
	
	function _testMailBidcheck(){
		die;
		
		$this->projects = $this->loadData('projects'); 
		$this->bids = $this->loadData('bids'); 
		$this->projects->get(2444,'id_project');
		
		$tab_date_retrait = explode(' ',$this->projects->date_retrait_full);
		$tab_date_retrait = explode(':',$tab_date_retrait[1]);
		$heure_retrait = $tab_date_retrait[0].':'.$tab_date_retrait[1];
		
		// On recup le temps restant
		$inter = $this->dates->intervalDates(date('Y-m-d H:i:s'),$this->projects->date_retrait.' '.$heure_retrait.':00'); 
		
		$dateRetrait = $this->projects->date_retrait.' '.$heure_retrait.':00';
		
		
				
		if($inter['mois']>0) $tempsRest = $inter['mois'].' mois';
		elseif($inter['jours']>0) $tempsRest = $inter['jours'].' jours';
		elseif($inter['heures']>0 && $inter['minutes'] >= 120) $tempsRest = $inter['heures'].' heures';
		elseif($inter['minutes']>0 && $inter['minutes']< 120) $tempsRest = $inter['minutes'].' min';
		else $tempsRest = $inter['secondes'].' secondes';
		
		
		// Taux moyen pondéré
		$montantHaut = 0;
		$montantBas = 0;
		foreach($this->bids->select('id_project = '.$this->projects->id_project.' AND status = 0' ) as $b)
		{
			$montantHaut += ($b['rate']*($b['amount']/100));
			$montantBas += ($b['amount']/100);
		}
		$taux_moyen = ($montantHaut/$montantBas);
		$taux_moyen = number_format($taux_moyen, 2, ',', ' ');
		
		
		
		//*********************************************//
		//*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//
		//*********************************************//
		
		$this->settings->get('Adresse notification projet funde a 100','type');
		//$destinataire = $this->settings->value;
		$destinataire = 'd.courtier@equinoa.com';
		
		// Nombre de preteurs
		$nbPeteurs = $this->bids->getNbPreteurs($this->projects->id_project);
		
		// Recuperation du modele de mail
		$this->mails_text->get('notification-projet-funde-a-100','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		$id_projet =$this->projects->id_project;
		$title_projet = utf8_decode($this->projects->title);
		$nbPeteurs = $nbPeteurs;
		$tx = $taux_moyen;
		$periode = $tempsRest;
		
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
		
		//echo $texteMail;
		// Envoi du mail
		$this->email = $this->loadLib('email',array());
		$this->email->setFrom($this->mails_text->exp_email,$exp_name);
		$this->email->addRecipient(trim($destinataire));
	
		$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
		$this->email->setHTMLBody($texteMail);
		Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
		// fin mail
		die;
	}
	
	
	function _testRecup(){
		
		$clients = $this->loadData('clients');
		
		
		echo $motif = 'CERTENAIS BENOIT               <br>NPYCERTENAIS BENOIT<br>IPYILLE-ET-VILAINE RENNES FR1978-02-05<br>LCC001277BCERTENAIS<br>RCNNOTPROVIDED<br>';
		
		
		// On cherche une suite de chiffres
		preg_match_all('#[0-9]+#',$motif,$extract);
		$nombre = (int)$extract[0][0]; // on retourne un int pour retirer les zeros devant
		
		// si existe en bdd
		if($clients->get($nombre,'id_client')){
			// on créer le motif qu'on devrait avoir
			$p = substr($this->ficelle->stripAccents(utf8_decode(trim($clients->prenom))),0,1);
			$nom = $this->ficelle->stripAccents(utf8_decode(trim($clients->nom)));
			$id_client = str_pad($clients->id_client,6,0,STR_PAD_LEFT);
			echo $returnMotif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
			
			$mystring = str_replace(' ','',$motif); // retire les espaces au cas ou le motif soit mal ecrit
			$findme   = str_replace(' ','',$returnMotif);
			$pos = strpos($mystring, $findme);
			
			// on laisse en manuel
			if ($pos === false) {
				echo 'Recu';
			}	
			else{
				echo 'auto';	
			}
		}
		die;
	}
	
	function _ontestRecupFichier()
	{
		//die;
		// Lien
		$lien = $this->path.'protected/sftp/reception/UNILEND-00040631007-20141016.txt';
		$lrecus = $this->recus2array($lien);	
		echo '<pre>';
		print_r($lrecus);
		echo '</pre>';
		die;
	}
	
	// transforme le fichier txt format truc en tableau
	function recus2array($file)
	{
		
		$tablemontant = array(
		'{' => 0,
		'A' => 1,
		'B' => 2,
		'C' => 3,
		'D' => 4,
		'E' => 5,
		'F' => 6,
		'G' => 7,
		'H' => 8,
		'I' => 9,
		'}' => 0,
		'J' => 1,
		'K' => 2,
		'L' => 3,
		'M' => 4,
		'N' => 5,
		'O' => 6,
		'P' => 7,
		'Q' => 8,
		'R' => 9);
		
		
		
		$url = $file;
		
		$array = array();
		$handle = @fopen($url,"r"); //lecture du fichier
		if($handle)
		{
			$i = 0;
			while(($ligne = fgets($handle)) !== false)
			{
				
				if(strpos($ligne,'CANTONNEMENT') == true || strpos($ligne,'DECANTON') == true || strpos($ligne,'REGULARISATION DIGITAL') == true || strpos($ligne,'00374 REGULARISATION DIGITAL') == true || strpos($ligne,'REGULARISATION') == true || strpos($ligne,'régularisation') == true || strpos($ligne,'00374 régularisation') == true)
				{
					$codeEnregi = substr($ligne,0,2);
					if($codeEnregi == 04)$i++;
					echo $i.' '.$ligne.'<br>';
					$tabRestrcition[$i] = $i;
				}
				else
				{
					
					
					$codeEnregi = substr($ligne,0,2);
					
					if($codeEnregi == 04)
					{
						$i++;
						echo $i.'GOOOD '.$ligne.'<br>';
						$laligne = 1;
						
						$array[$i]['codeEnregi'] = substr($ligne,0,2);
						$array[$i]['codeBanque'] = substr($ligne,2,5);
						$array[$i]['codeOpBNPP'] = substr($ligne,7,4);
						$array[$i]['codeGuichet'] = substr($ligne,11,5);
						$array[$i]['codeDevises'] = substr($ligne,16,3);
						$array[$i]['nbDecimales'] = substr($ligne,19,1);
						$array[$i]['zoneReserv1'] = substr($ligne,20,1);
						$array[$i]['numCompte'] = substr($ligne,21,11);
						$array[$i]['codeOpInterbancaire'] = substr($ligne,32,2);
						$array[$i]['dateEcriture'] = substr($ligne,34,6);
						$array[$i]['codeMotifRejet'] = substr($ligne,40,2);
						$array[$i]['dateValeur'] = substr($ligne,42,6);
						$array[$i]['libelleOpe1'] = substr($ligne,48,31);
						$array[$i]['zoneReserv2'] = substr($ligne,79,2);
						$array[$i]['numEcriture'] = substr($ligne,81,7);
						$array[$i]['codeExoneration'] = substr($ligne,88,1);
						$array[$i]['zoneReserv3'] = substr($ligne,89,1);
						
						$array[$i]['refOp'] = substr($ligne,104,16);
						$array[$i]['ligne1'] = $ligne;
						
						
						// on recup le champ montant
						$montant = substr($ligne,90,14);
						// on retire les zeros du debut et le dernier caractere
						$Debutmontant = ltrim(substr($montant,0,13),'0');
						// On recup le dernier caractere
						$dernier = substr($montant,-1,1);
						$array[$i]['montant'] = $Debutmontant.$tablemontant[$dernier];
						$motif .= $r['libelleOpe'.$i].'<br>';
					}
					
					if($codeEnregi == 05)
					{
						$laligne += 1;
						//$array[$i]['ligne'.$laligne] = $ligne;
						$array[$i]['libelleOpe'.$laligne] = trim(substr($ligne,45));
						
						
					}
				
				}
				
					
				
			}
			if (!feof($handle)) {
				return "Erreur: fgets() a échoué\n";
			}
			fclose($handle);
			
			
			foreach($tabRestrcition as $r){ unset($array[$r]); }
			
			return $array;
		}
	}
	
	
	function _miseajourclientws()
	{
		
		die;
		$this->clients = $this->loadData('clients'); 
		
		$liste = $this->clients->select('origine = 1');	
		foreach($liste as $l){
		
		$this->clients->get($l['id_client'],'id_client');
		$this->clients->secrete_reponse = md5($this->clients->secrete_reponse);
		$this->clients->update();
			
			
		}
		
		die;
	}
	
	function _updatedPrelevementObligatoires(){
		die;
		$this->echeanciers = $this->loadData('echeanciers'); 
		$this->transactions = $this->loadData('transactions'); 
		$this->wallets_lines = $this->loadData('wallets_lines'); 
		
		// EQ-Acompte d'impôt sur le revenu
		$this->settings->get("EQ-Acompte d'impôt sur le revenu",'type');
		$prelevements_obligatoires = $this->settings->value;

		$lecheances = $this->echeanciers->requeteGetecheancePrelevement();
		$nb = count($lecheances);
		
		
		echo '<br>Nb echeances : '.$nb;
		echo '<br>Taux prelevements obligatoires : '.$prelevements_obligatoires.'<br>';
		
		//********************//
		//*** Envoi du CSV ***//
		//********************//
		//$titre = $this->bdd->generateSlug($this->queries->name);
		//$titre = 'prelevements_'.date('Ymd');
		//header("Content-type: application/vnd.ms-excel"); 
		//header("Content-disposition: attachment; filename=\"".$titre.".xls\"");
		
		?>
        
        
        
        <table border="1">
        <tr>
        	<th>Id client</th>
        	<th>Id lender</th>
            <th>Solde</th>
            <th>id projet</th>
            <th>id echeancier</th>
            <th>date echeance</th>
            <th>date echeance reel</th>
            <th>statut remb</th>
            <th>interets</th>
            <th>prelevements obligatoires avant</th>
            <th>prelevements obligatoires apres</th>
        </tr>
        <?
		$total = 0;
		
		
		
		foreach($lecheances as $e){
			$interets = ($e['interets']/100);
			$newPrelevements = round($interets*$prelevements_obligatoires,2);
			
			
			// update echeance (prelevement)
			// echeance preteur
			//$this->echeanciers->get($e['id_echeancier'],'id_echeancier');
			//$this->echeanciers->prelevements_obligatoires = $newPrelevements;
			//$this->echeanciers->update();
			
			// creation d'une transaction - le prelèvement(regule type : 14)
			
			// On enregistre la transaction
			$this->transactions->id_client = $e['id_client'];
			$this->transactions->montant = '-'.($newPrelevements*100);
			$this->transactions->id_echeancier = $e['id_echeancier']; // id de l'echeance remb
			$this->transactions->id_langue = 'fr';
			$this->transactions->date_transaction = '2014-12-01 14:00:00';
			$this->transactions->status = '1';
			$this->transactions->etat = '1';
			$this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
			$this->transactions->type_transaction = 5; // remb enchere
			$this->transactions->transaction = 2; // transaction virtuelle
			//$this->transactions->display = 1;
			$this->transactions->serialize_paniers = 'Regule prelevements obligatoires';
			//$this->transactions->id_transaction = $this->transactions->create();
			
			
			// creation d'une ligne wallet - le prelèvement
			
			// on enregistre la transaction dans son wallet
			$this->wallets_lines->id_lender = $e['id_lender_account'];
			$this->wallets_lines->type_financial_operation = 40;
			$this->wallets_lines->id_transaction = $this->transactions->id_transaction;
			$this->wallets_lines->status = 1; // non utilisé
			$this->wallets_lines->type = 2; // transaction virtuelle
			$this->wallets_lines->amount = '-'.($newPrelevements*100);
			//$this->wallets_lines->id_wallet_line = $this->wallets_lines->create();
			
			
			
			
			$total += $newPrelevements;
			$totalbyprojects[$e['id_project']] += $newPrelevements;
			$solde = $this->transactions->getSolde($e['id_client'])
			
			?>
            <tr>
                <td><?=$e['id_client']?></td>
                <td><?=$e['id_lender_account']?></td>
                <td <?=($solde < $newPrelevements?'style="color:red"':'')?>><?=$solde?></td>
                <td><?=$e['id_project']?></td>
                <td><?=$e['id_echeancier']?></td>
                <td><?=$e['date_echeance']?></td>
                <td><?=$e['date_echeance_reel']?></td>
                <td><?=$e['status']?></td>
                <td><?=$interets?></td>
                <td><?=$e['prelevements_obligatoires']?></td>
                <td><?=$newPrelevements?></td>
            </tr>
            <?
			
		}
		?>
        </table>
        <?
		
		echo 'total pour etat en plus : '.$total.' euros';
		echo '<br> par projet : <br>';
		
		echo '<pre>';
		print_r($totalbyprojects);
		echo '</pre>';
		
		die;
	}
	
	
	function _miseAjourdesexonere()
	{
		die;
		$this->lenders_accounts = $this->loadData('lenders_accounts'); 
		$this->echeanciers = $this->loadData('echeanciers'); 
		
		// EQ-Acompte d'impôt sur le revenu
		$this->settings->get("EQ-Acompte d'impôt sur le revenu",'type');
		$prelevements_obligatoires = $this->settings->value;
		
		//$lexo = $this->lenders_accounts->select('exonere = 1 AND debut_exoneration != "0000-00-00"','debut_exoneration ASC');
		$lexo = $this->lenders_accounts->select('id_client_owner = 133');
		print_r($lexo);
		die;
		echo count($lexo);
		/*echo '<pre>';
		print_r($lexo);
		echo '</pre>';*/
		echo '<br>'.$prelevements_obligatoires;
		?>
		<table border="1">
        <tr>
        	<th>id client</th>
        	<th>id lender</th>
            <th>debut exo</th>
            <th>fin exo</th>
        </tr>
        <?
		foreach($lexo as $exo){
			
			?>
            <tr>
                <td><?=$exo['id_client_owner']?></td>
                <td><?=$exo['id_lender_account']?></td>
                <td><?=$exo['debut_exoneration']?></td>
                <td><?=$exo['fin_exoneration']?></td>
            </tr>
            <?
			
			$debut_exo = $exo['debut_exoneration'];
			$fin_exo = $exo['fin_exoneration'];
			//$this->echeanciers->update_prelevements_obligatoires($exo['id_lender_account'],0,$prelevements_obligatoires);
			//$this->echeanciers->update_prelevements_obligatoires($exo['id_lender_account'],1,'',$debut_exo,$fin_exo);
		}
		echo '</table>';
		die;
	}
	
	function _testcapital()
	{
		// Chargement des librairies
		$this->xml2array = $this->loadLib('xml2array');
		
		$xmlstring = file_get_contents('http://www.capital.fr/wrapper-unilend.xml');
		
		
		$result = $this->xml2array->getArray($xmlstring);
		$content = $result['wrapper']['content'];
		$content = explode('<!--CONTENT_ZONE-->',$content);
		
		$haut = str_replace('<!--TITLE_ZONE_HEAD-->','Capital',$content[0]);
		$bas = $content[1];
		
		$style = '
			<style type="text/css">
			#LeftColumn{width:100%;}
			#pageContent_sideBar{display:none;}
			</style>
		';
		
		
		

		echo $haut;
		echo $style;
		?>
       <?php /*?> <script type="text/javascript" src="http://www.unilend.fr/scripts/default/jquery/jquery-1.10.2.min.js"></script><?php */?>

        <div class="capitalici"></div>

       	<script type="text/javascript">
		
		$( document ).ready(function() {
			$.post( "<?=$this->lurl?>/projets-a-financer", function( data ){
				$(".capitalici").html(data);
				$('ul, ol').css('padding-left','0px');
				
			});
		});
		</script>
       
        <?php /*?><iframe scrolling="auto" src="<?=$this->lurl?>/projets-a-financer" width="100%" height="1000px;"></iframe><?php */?>
		<?
		echo $bas;
		
		
		
		die;
	}
	
	
	function _capital()
	{

		// Chargement des librairies
		$this->xml2array = $this->loadLib('xml2array');
		
		$xmlstring = file_get_contents('http://www.capital.fr/wrapper-unilend.xml');
		
		
		$result = $this->xml2array->getArray($xmlstring);
		$content = $result['wrapper']['content'];
		$content = explode('<!--CONTENT_ZONE-->',$content);
		
		$haut = str_replace('<!--TITLE_ZONE_HEAD-->','Capital',$content[0]);
		$bas = $content[1];
		
		$style = '
			<style type="text/css">
			#LeftColumn{width:100%;}
			#pageContent_sideBar{display:none;}
			</style>
		';
		
		echo $haut;
		echo $style;
		?><iframe scrolling="auto" src="<?=$this->lurl?>/projets-a-financer" width="100%" height="1200px"></iframe><?
		echo $bas;
		
		
		
		die;
	}
	
	// 21/04/2015
	function _email_redressement()
	{
		
		$this->loans = $this->loadData('loans'); 
		$this->clients = $this->loadData('clients'); 
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->companies = $this->loadData('companies');
		$this->projects = $this->loadData('projects');
		$this->echeanciers = $this->loadData('echeanciers');   
		
		
		$id_project = 1009;
		//$lLoans = $this->loans->select('id_project = '.$id_project);
		$lLoans = $this->loans->select('id_project = '.$id_project.' AND id_lender = 113'); // test
		
		//$lLoans = $this->loans->select('id_project = '.$id_project.' AND id_lender = 1586'); // test (id client 2015)
		//echo 'NB : '.count($lLoans);
		
		foreach($lLoans as $loan){
		
			$this->lenders_accounts->get($loan['id_lender'],'id_lender_account');
			$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
			
			$this->projects->get($loan['id_project'],'id_project');
			$this->companies->get($this->projects->id_company,'id_company');
			
			$sumRemb = $this->echeanciers->getSumRemb($loan['id_lender'].' AND id_loan = '.$loan['id_loan'],'montant');
			$fiscal = $this->echeanciers->getSumRevenuesFiscalesRemb($loan['id_lender'].' AND id_loan = '.$loan['id_loan']);
			
			$sumRemb = ($sumRemb-$fiscal);
			
			//$email = $this->clients->email;
			$destinataire = 'courtier.damien@gmail.com'; // test
			
			
			//*********************************************//
			//*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//
			//*********************************************//
			

			// Recuperation du modele de mail
			$this->mails_text->get('redressement-judiciaire','lang = "'.$this->language.'" AND type');
			
			// Variables du mailing
			$surl = $this->surl;
			$url = $this->lurl;
			
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
			'prenom_p' => $this->clients->prenom,
			'valeur_bid' => number_format(($loan['amount']/100), 2, ',', ' '),
			'nom_entreprise' => $this->companies->name,
			'montant_rembourse' => number_format(($sumRemb), 2, ',', ' '),
			'lien_fb' => $lien_fb,
			'lien_tw' => $lien_tw);	
			
			
			// Construction du tableau avec les balises EMV
			$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
			
			// Attribution des données aux variables
			$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);				
			echo $texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
			$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
			
			// Envoi du mail
			/*$this->email = $this->loadLib('email',array());
			$this->email->setFrom($this->mails_text->exp_email,$exp_name);
			$this->email->setSubject(stripslashes($sujetMail));
			$this->email->setHTMLBody(stripslashes($texteMail));
			
			if($this->Config['env'] == 'prod') // nmp
			{
				Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$destinataire,$tabFiler);			
				// Injection du mail NMP dans la queue
				$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
			}
			else // non nmp
			{

				//$this->email->addRecipient(trim('testunilend@outlook.fr')); 	// 2015
				//$this->email->addRecipient(trim('manupd@gmail.com'));			// 3167
				//$this->email->addRecipient(trim('david.nandji@gmail.com'));
				$this->email->addRecipient(trim($destinataire));
				Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
			}*/
			
			die; // <-------------- a retier
		}
		die;
	}
	
	function _email_redressement_old()
	{
		die;
		$this->loans = $this->loadData('loans'); 
		$this->clients = $this->loadData('clients'); 
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		$this->companies = $this->loadData('companies');
		$this->projects = $this->loadData('projects');
		$this->echeanciers = $this->loadData('echeanciers');   
		
		
		$id_project = 1456;
		$lLoans = $this->loans->select('id_project = '.$id_project);
		//$lLoans = $this->loans->select('id_project = '.$id_project.' AND id_lender = 4155'); // test
		
		
		
		foreach($lLoans as $loan){
		
			$this->lenders_accounts->get($loan['id_lender'],'id_lender_account');
			$this->clients->get($this->lenders_accounts->id_client_owner,'id_client');
			
			$this->projects->get($loan['id_project'],'id_project');
			$this->companies->get($this->projects->id_company,'id_company');
			
			$sumRemb = $this->echeanciers->getSumRemb($loan['id_lender'].' AND id_loan = '.$loan['id_loan'],'montant');
			$fiscal = $this->echeanciers->getSumRevenuesFiscalesRemb($loan['id_lender'].' AND id_loan = '.$loan['id_loan']);
			
			$sumRemb = ($sumRemb-$fiscal);
			
			//$email = $this->clients->email;
			$email = 'd.courtier@equinoa.fr'; // test
			
			//*********************************************//
			//*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//
			//*********************************************//
			

			// Recuperation du modele de mail
			$this->mails_text->get('redressement-judiciaire','lang = "'.$this->language.'" AND type');
			
			// Variables du mailing
			$surl = $this->surl;
			$url = $this->lurl;
			
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
			'prenom_p' => $this->clients->prenom,
			'valeur_bid' => number_format(($loan['amount']/100), 2, ',', ' '),
			'nom_entreprise' => $this->companies->name,
			'montant_rembourse' => number_format(($sumRemb), 2, ',', ' '),
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
			
			//$this->email->addRecipient(trim('d.courtier@equinoa.com'));
			//$this->email->addBCCRecipient($this->clients->email);
			
			$this->email->setSubject(stripslashes($sujetMail));
			$this->email->setHTMLBody(stripslashes($texteMail));
			Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$email,$tabFiler);
			
			// Injection du mail NMP dans la queue
			$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
		}
		die;
	}
	
	function _test_email_date()
	{
		$destinataire = 'd.courtier@equinoa.com';
		
		$this->projects = $this->loadData('projects');
		$this->projects->get('3823','id_project');
					
		// Recuperation du modele de mail
		$this->mails_text->get('notification-depot-de-dossier','lang = "'.$this->language.'" AND type');
		
		// Variables du mailing
		$surl = $this->surl;
		$url = $this->lurl;
		$nom_societe = utf8_decode('damiéen');
		$montant_pret = $this->projects->amount;
		$lien = $this->aurl.'/emprunteurs/edit/'.$this->clients->id_client;
		
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
		$this->email->addRecipient(trim($destinataire));
		
	
		$this->email->setSubject('=?UTF-8?B?'.base64_encode(html_entity_decode($sujetMail)).'?=');
		$this->email->setHTMLBody($texteMail);
		Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);
		die;
	}
	
	function _ordrepays()
	{
		die;
		$this->pays = $this->loadData('pays_v2');
		
		$lpays = $this->pays->select('','fr asc');
		
		
		unset($lpays['60']);
		
		$i = 2;
		foreach($lpays as $p)
		{
			echo $i.' - '.$p['fr'].'<br>';
			$this->pays->get($p['id_pays'],'id_pays');
			$this->pays->ordre = $i;
			$this->pays->update();
			$i++;	
		}
		
		die;
	}
	
	function _mail_nouveau_projet()
	{
		$this->mails_text->get('confirmation-bid','lang = "'.$this->language.'" AND type');
					
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		// Variables du mailing
		$varMail = array(
		'surl' => $this->surl,
		'url' => $this->furl,
		'prenom_p' => 'Damien',
		'nom_entreprise' => 'Apple',
		'projet-p' => $this->furl.'/projects/detail/toto',
		'montant' => number_format(100000, 2, ',', ' '),
		'duree' => '36',
		'motif_virement' => '000012DCOURTIER',
		'gestion_alertes' => $this->lurl.'/profile',
		'lien_fb' => $lien_fb,
		'lien_tw' => $lien_tw);		
		// Construction du tableau avec les balises EMV
		$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
		
		// Attribution des données aux variables
		$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);				
		echo $texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
		$exp_name = strtr(utf8_decode($this->mails_text->exp_name),$tabVars);
		
		/*// Envoi du mail
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
		}*/
		die;	
	}
	
	
	function _chargeData(){
		$this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
		$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
		$this->clients_gestion_type_notif = $this->loadData('clients_gestion_type_notif');
		die;
	}
	
	function _updateMotifTablePrelevement(){
		
		$this->clients = $this->loadData('clients');
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->prelevements = $this->loadData('prelevements');
		
		if(isset($this->params[0]) && $this->projects->get($this->params[0],'id_project')){
			
			$this->companies->get($this->projects->id_company,'id_company');
			$this->clients->get($this->companies->id_client_owner,'id_client');
			
			$prenom = $this->clients->prenom;
			$nom = $this->clients->nom;
			$id_project = $this->projects->id_project;
			$motif = $this->ficelle->motif_mandat($prenom,$nom,$id_project);
			
			$lprelevements = $this->prelevements->select('id_project = '.$this->projects->id_project.' AND status = 0');
			
			foreach($lprelevements as $prelev){
				$this->prelevements->get($prelev['id_prelevement'],'id_prelevement');	
				$this->prelevements->motif = $motif;
				$this->prelevements->update();
			}
		}
		die;
	}
	
	// pour faire une correction sur les notif mail
	function _miseajournotificationprojects()
	{
		die;
		$this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
		$this->notifications = $this->loadData('notifications');
		
		
		
		// les projets a mettre un id project
		$projectsagarder = $this->clients_gestion_mails_notif->select('id_project = 0 AND LEFT(updated,10) = "2015-02-04" AND id_notif = 1');
		
		foreach($projectsagarder as $pro){
			$this->notifications->get($pro['id_notification'],'id_notification');
			
			
			$this->clients_gestion_mails_notif->get($pro['id_clients_gestion_mails_notif'],'id_clients_gestion_mails_notif');
			$this->clients_gestion_mails_notif->id_project = $this->notifications->id_project;
			//$this->clients_gestion_mails_notif->update();
		}
		
		
		
		die;	
	}
	
	// fonction synhtese nouveaux projets
	// $type = quotidienne,hebdomadaire,mensuelle
	function _nouveaux_projets_synthese()
	{
		die;
		$type = 'quotidienne';

		$this->clients = $this->loadData('clients');
		$this->notifications = $this->loadData('notifications');
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		
		$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
		$this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
		
		$array_mail_nouveaux_projects = $this->clients_gestion_mails_notif->select('id_client = 12 and id_notif = 1');
			
			echo '<pre>';
			print_r($array_mail_nouveaux_projects);
			echo '</pre>';
			
			
		
		// on regarde si on a bien quelque chose
		if($array_mail_nouveaux_projects != false){
		
			// FB
			$this->settings->get('Facebook','type');
			$lien_fb = $this->settings->value;
			
			// Twitter
			$this->settings->get('Twitter','type');
			$lien_tw = $this->settings->value;
			
			
			
			// on recup les notifs par preteur
			foreach($array_mail_nouveaux_projects as $key => $mails_notif){
				
				
				
				$id_client = $mails_notif['id_client'];
				
				// On check dans la gestion des alertes immediatement ou pas
				if($this->clients_gestion_notifications->getNotif($id_client,1,$type) == true){
					
					
					//////// MAIL ////////
					
					
					// On recup les infos du preteur
					$this->clients->get($id_client,'id_client');
					
					// Motif virement
					$p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))),0,1);
					$nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
					$le_id_client = str_pad($this->clients->id_client,6,0,STR_PAD_LEFT);
					$motif = mb_strtoupper($le_id_client.$p.$nom,'UTF-8');
					
					$mails_notif = $this->clients_gestion_mails_notif->select('id_client = 12 and id_notif = 1');
					
					if(count($mails_notif) > 1 || $type != 'quotidienne'){
						
						//////// MAIL avec plusieurs projets ////////
						
						if($type == 'quotidienne') 
							$this->mails_text->get('nouveaux-projets-du-jour','lang = "'.$this->language.'" AND type');
						else 
							$this->mails_text->get('nouveaux-projets-de-la-semaine','lang = "'.$this->language.'" AND type');
						
						$liste_projets = '';
						foreach($mails_notif as $n){
							
							$this->notifications->get($n['id_notification'],'id_notification');
							$this->projects->get($this->notifications->id_project,'id_project');
							$this->companies->get($this->projects->id_company,'id_company');
							
							//////// GESTION ALERTES //////////
							$this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'],'id_clients_gestion_mails_notif');
							if($type == 'quotidienne') 		$this->clients_gestion_mails_notif->quotidienne 	= 1;
							elseif($type == 'hebdomadaire') $this->clients_gestion_mails_notif->hebdomadaire 	= 1;
							elseif($type == 'mensuelle') 	$this->clients_gestion_mails_notif->mensuelle 		= 1;
							//$this->clients_gestion_mails_notif->update();
							//////// FIN GESTION ALERTES //////////
							
							
							$liste_projets .= '
								<tr style="color:#b20066;">
									<td  style="font-family:Arial;font-size:14px;height: 25px;"><a style="color:#b20066;text-decoration:none;font-family:Arial;" href="'.$this->lurl.'/projects/detail/'.$this->projects->slug.'">'.$this->projects->title.'</a></td>
									<td align="right" style="font-family:Arial;font-size:14px;">'.number_format($this->projects->amount, 0, ',', ' ').' &euro;</td>
									<td align="right" style="font-family:Arial;font-size:14px;">'.$this->projects->period.' mois</td>
								</tr>';
						}
						
								
							
						
						// Variables du mailing
						$varMail = array(
						'surl' => $this->surl,
						'url' => $this->furl,
						'prenom_p' => $this->clients->prenom,
						'liste_projets' => ($liste_projets),
						'projet-p' => $this->lurl.'/projets-a-financer',
						'motif_virement' => $motif,
						'gestion_alertes' => $this->lurl.'/profile',
						'lien_fb' => $lien_fb,
						'lien_tw' => $lien_tw);		
						// Construction du tableau avec les balises EMV
						$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
						
						// Attribution des données aux variables
						$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);				
						echo $texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
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
							$this->email->addRecipient($this->clients->email);
							Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
						}
					}
					else{
						////// EMAIL avec un seul projet ////////
						
						foreach($mails_notif as $n){
							$mail = $n;
							break;
						}
						
						$this->notifications->get($mail['id_notification'],'id_notification');
						$this->projects->get($this->notifications->id_project,'id_project');
						$this->companies->get($this->projects->id_company,'id_company');
						
						//////// GESTION ALERTES //////////
						$this->clients_gestion_mails_notif->get($mail['id_clients_gestion_mails_notif'],'id_clients_gestion_mails_notif');
						if($type == 'quotidienne') 		$this->clients_gestion_mails_notif->quotidienne 	= 1;
						elseif($type == 'hebdomadaire') $this->clients_gestion_mails_notif->hebdomadaire 	= 1;
						elseif($type == 'mensuelle') 	$this->clients_gestion_mails_notif->mensuelle 		= 1;
						$this->clients_gestion_mails_notif->update();
						//////// FIN GESTION ALERTES //////////
						
						
						$this->mails_text->get('nouveau-projet','lang = "'.$this->language.'" AND type');

						
						// Variables du mailing
						$varMail = array(
						'surl' => $this->surl,
						'url' => $this->furl,
						'prenom_p' => $this->clients->prenom,
						'nom_entreprise' => $this->companies->name,
						'projet-p' => $this->furl.'/projets-a-financer',
						'montant' => number_format($this->projects->amount, 0, ',', ' '),
						'duree' => $this->projects->period,
						'motif_virement' => $motif,
						'gestion_alertes' => $this->lurl.'/profile',
						'lien_fb' => $lien_fb,
						'lien_tw' => $lien_tw);		
						// Construction du tableau avec les balises EMV
						$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
						
						// Attribution des données aux variables
						$sujetMail = strtr(utf8_decode($this->mails_text->subject),$tabVars);				
						echo $texteMail = strtr(utf8_decode($this->mails_text->content),$tabVars);
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
					}
					////// FIN MAIL ///////
					
					
				}
			}
		}
		
		die;
	}
	
	function _creategestionAlertNotifPreteur()
	{
		
		$clients = $this->loadData('clients');
		$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
		$clients_gestion_type_notif = $this->loadData('clients_gestion_type_notif');
		
		$lTypeNotifs = $clients_gestion_type_notif->select();
	
		
		//$lPreteurs = $clients->selectPreteursByStatus(60);
		//$lPreteurs = $clients->selectPreteursByStatus(60,"c.id_client IN (2015,1,12)"); // mode test <------------------
		//$lPreteurs = $clients->selectPreteursByStatus(60,"c.id_client IN (12)"); // mode test <------------------
		
		
		
		// on check tous les preteurs
		foreach($lPreteurs as $preteur){
			
			if($this->clients_gestion_notifications->counter('id_client = '.$preteur['id_client']) == 0){
echo $preteur['id_client'].'<br>------- <br>';
				
				/*foreach($lTypeNotifs as $n){
					$this->clients_gestion_notifications->id_client = $preteur['id_client'];
					$this->clients_gestion_notifications->id_notif = $n['id_client_gestion_type_notif'];
					$id_notif = $n['id_client_gestion_type_notif'];
					// immediatement
					if(in_array($id_notif,array(3,6,7,8)))
						$this->clients_gestion_notifications->immediatement = 1;
					else 
						$this->clients_gestion_notifications->immediatement = 0;
					// quotidienne
					if(in_array($id_notif,array(1,2,4,5)))
						$this->clients_gestion_notifications->quotidienne	= 1;
					else
						$this->clients_gestion_notifications->quotidienne	= 0;
					// hebdomadaire
					if(in_array($id_notif,array(1,4)))
						$this->clients_gestion_notifications->hebdomadaire	= 1;
					else
						$this->clients_gestion_notifications->hebdomadaire	= 0;
					// mensuelle
					$this->clients_gestion_notifications->mensuelle			= 0;
					$this->clients_gestion_notifications->create();
				}*/
					
			}
		}
		die;
	}
	
	function _listmailNMPerror()
	{
		//	 27/02/15 12h32
		//	 01/02/15 
		
		$clients = $this->loadData('clients');
		$mails_text = $this->loadData('mails_text');
		
		$sql = "SELECT 
				n.serialize_content,
				n.id_nmp,
				n.mailto,
				n.added
				FROM nmp n 
				WHERE n.status = 2 
				AND n.added > '2015-02-27 12:30:00' AND n.added < '2015-03-28 13:00:00'";
		
		$resultat = $this->bdd->query($sql);
		
		
		while($record = $this->bdd->fetch_array($resultat))
		{
			$id_nmp = $record[1];
			
			$tab = unserialize($record[0]);
			
			
			
			$notificationId = $tab['arg0']['notificationId'];
			
			if($mails_text->get($notificationId,'id_nmp') && $notificationId != ''){
				
				
				//$array[$mails_text->type][$id_nmp] = serialize(array('email' =>$record[2],'added' => $record[3]));
				
				$array[$mails_text->type][$id_nmp]['email'] = $record[2];
				$array[$mails_text->type][$id_nmp]['added'] = $record[3];
				
				$arrayTemplates[$notificationId] = $mails_text->type;

			}
			
			
		}
			// Nombre de templates
			$nb_arrayTemplates = count($arrayTemplates);
			
			
			header("Content-Type: application/vnd.ms-excel");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("content-disposition: attachment;filename=".$this->bdd->generateSlug('error_emails').".xls");
			
			
			echo '<table border="0" style="font-family: arial;font-size: 12px;">';
			echo '<tr><th>idTemplateNMP</th><th>type</th><th>NB</th></tr>';
			foreach($arrayTemplates as $k => $t){
				?>
				<tr>
					<td><?=$k?></td>
                    <td><?=$t?></td>
                    <td><?=count($array[$t])?></td>
				</tr>
				<?
			}
			echo '</table>';

			
			
			?>
			<table border="1" style="font-family: arial;font-size: 12px;">
			<?
			$templateTemp = '';
            foreach($array as $template => $mails){
				foreach($mails as $key => $lemail){
					
				

			
					if($template != $templateTemp)
					{
						
						?>
                        <tr>
							<td colspan="2">&nbsp;</td>
							
						</tr>
						<tr>
							<th colspan="2"><?=$template?></th>
							
						</tr>
						<tr>
							<td><?=$lemail['email']?></td>
							<td><?=$lemail['added']?></td>
						</tr>
						<?
					}
					else{
						?>
						<tr>
							<td><?=$lemail['email']?></td>
							<td><?=$lemail['added']?></td>
						</tr>
						<?
					}
					$templateTemp = $template;
				}	
            }
            ?>
            </table>
            <?
			
			
		
		die;
	}
	
	function _mise_ajour_id_loan_notification()
	{
		die;
		$this->clients = $this->loadData('clients');
		$this->notifications = $this->loadData('notifications');
		$this->projects = $this->loadData('projects');
		$this->companies = $this->loadData('companies');
		$this->bids = $this->loadData('bids');
		$this->loans = $this->loadData('loans');
		
		$this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
		$this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
		
		$liste = $this->clients_gestion_mails_notif->select('id_notif = 4 AND id_loan = 0');
		//echo 'nb : '.count($liste);
		/*echo '<pre>';
		print_r($liste);
		echo '</pre>';*/
		
		foreach($liste as $l){
			$this->notifications->get($l['id_notification'],'id_notification');
			if($this->loans->get($this->notifications->id_bid,'id_bid')){
			
			$this->clients_gestion_mails_notif->get($l['id_clients_gestion_mails_notif'],'id_clients_gestion_mails_notif');
			$this->clients_gestion_mails_notif->id_loan = $this->loans->id_loan;
			echo $this->clients_gestion_mails_notif->id_loan.'<br>';
			//$this->clients_gestion_mails_notif->update();
			}
			else{
				echo 'error bid :'.$this->notifications->id_bid.' - '.$l['id_clients_gestion_mails_notif'].'<br>';	
			}
			
		}
		
		die;	
	}
	function _testreceptionMotif()
	{
		
		$clients = $this->loadData('clients');
		$receptions = $this->loadData('receptions');
		
		$receptions->get(6697,'id_reception');
		
		echo $motif = $receptions->motif;
		echo '<br>';
		// On cherche une suite de chiffres
		preg_match_all('#[0-9]+#',$motif,$extract);
		echo '<pre>';
		print_r($extract);
		echo '</pre>';
		$nombre = (int)$extract[0][0]; // on retourne un int pour retirer les zeros devant
		
		$auto = false;
		foreach($extract[0] as $nombre){
			// si existe en bdd
			if($clients->get($nombre,'id_client')){
				// on créer le motif qu'on devrait avoir
				$p = substr($this->ficelle->stripAccents(utf8_decode(trim($clients->prenom))),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode(trim($clients->nom)));
				$id_client = str_pad($clients->id_client,6,0,STR_PAD_LEFT);
				$returnMotif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				echo $returnMotif.'<br>';
				
				$mystring = str_replace(' ','',$motif); // retire les espaces au cas ou le motif soit mal ecrit
				$findme   = str_replace(' ','',$returnMotif);
				$pos = strpos($mystring, $findme);
				
				// on laisse en manuel
				if ($pos === false) {
					$auto = false;
				}
				// Automatique (on attribue le virement au preteur)
				else{
					$auto = true;
					break;
				}
			}
		}
		
		
		if($auto == false){
			$reponse = 'Recu';
		}
		else{
			$reponse = 'Auto';
		}
			
			
		
		echo $reponse;
		die;
	}
	
	function _contentemail()
	{
	   die;
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		$this->companies = $this->loadData('companies');
		$this->clients = $this->loadData('clients');
		$this->loans = $this->loadData('loans');
		$this->projects = $this->loadData('projects');
		$this->echeanciers = $this->loadData('echeanciers');
		$this->lenders_accounts = $this->loadData('lenders_accounts');
		
		// FB
		$this->settings->get('Facebook', 'type');
		$lien_fb = $this->settings->value;

		// Twitter
		$this->settings->get('Twitter', 'type');
		$lien_tw = $this->settings->value;
		
		$this->projects->get(3013,'id_project');
		$this->companies->get($this->projects->id_company, 'id_company');
		
		$sql = "
			SELECT n.mailto,c.* 
			FROM nmp n 
			LEFT JOIN clients c ON n.mailto = c.email
			WHERE n.serialize_content LIKE '%1539774%' AND n.added LIKE '%2015-04-15%' group by n.mailto";
		
		$resultat = $this->bdd->query($sql);
		while($record = $this->bdd->fetch_array($resultat))
		{
			$this->lenders_accounts->get($record['id_client'],'id_client_owner');
			
			// Motif virement
			$p = substr($this->ficelle->stripAccents(utf8_decode(trim($record['prenom']))), 0, 1);
			$nom = $this->ficelle->stripAccents(utf8_decode(trim($record['nom'])));
			$id_client = str_pad($record['id_client'], 6, 0, STR_PAD_LEFT);
			$motif = mb_strtoupper($id_client . $p . $nom, 'UTF-8');
			
			$lLoans = $this->loans->select('id_lender = '.$this->lenders_accounts->id_lender_account.' AND id_project = '.$this->projects->id_project);
			
			foreach($lLoans as $l){
				////////////////////////////////////////////
				// on recup la somme deja remb du preteur //
				////////////////////////////////////////////
				$lEchea = $this->echeanciers->select('id_loan = ' .$l['id_loan'] . ' AND id_project = ' . $this->projects->id_project . ' AND status = 1');
				$rembNet = 0;
				foreach ($lEchea as $e) {
					// on fait la somme de tout
					$rembNet += ($e['montant'] / 100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
				}
				////////////////////////////////////////////
				
				$this->mails_text->get('preteur-erreur-remboursement-2', 'lang = "' . $this->language . '" AND type');
			
				// Variables du mailing
				$varMail = array(
					'surl' => $this->surl,
					'url' => $this->furl,
					'prenom_p' => ($record['prenom']),
					'valeur_bid' => number_format(($l['amount']/100), 0,',',' '),
					'nom_entreprise' => ($this->companies->name),
					'montant_rembourse' =>  number_format(($rembNet), 2,',',' '),
					'motif_virement' => $motif,
					'lien_fb' => $lien_fb,
					'lien_tw' => $lien_tw);
			
				// Construction du tableau avec les balises EMV
				$tabVars = $this->tnmp->constructionVariablesServeur($varMail);
			
				// Attribution des données aux variables
				$sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
				$texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
				$exp_name = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);
				
				
				// Envoi du mail
				/*$this->email = $this->loadLib('email',array());
				$this->email->setFrom($this->mails_text->exp_email,$exp_name);
				$this->email->setSubject(stripslashes($sujetMail));
				$this->email->setHTMLBody(stripslashes($texteMail));
				
				if($this->Config['env'] == 'prod') // nmp
				{
					Mailer::sendNMP($this->email,$this->mails_filer,$this->mails_text->id_textemail,$record['email'],$tabFiler);			
					// Injection du mail NMP dans la queue
					$this->tnmp->sendMailNMP($tabFiler,$varMail,$this->mails_text->nmp_secure,$this->mails_text->id_nmp,$this->mails_text->nmp_unique,$this->mails_text->mode);
				}
				else // non nmp
				{
					//$this->email->addRecipient(trim('d.courtier@relance.fr'));
					//Mailer::send($this->email,$this->mails_filer,$this->mails_text->id_textemail);	
				}*/
				
			}
			
		}// fin while
		echo 'ok';
			die;
			
			
	}
	
	function _deletedesprospects()
	{
		die;
		$sql = 'SELECT p.id_prospect, p.email as email_prospects, c.id_client, c.email FROM prospects p LEFT JOIN clients c on p.email = c.email WHERE c.id_client IS NOT NULL AND c.status = 1';
		
		$resultat = $this->bdd->query($sql);
		$i=0;
		while($record = $this->bdd->fetch_array($resultat))
		{
			$sql = 'DELETE FROM `prospects` WHERE id_prospect = "'.$record['id_prospect'].'"';
			$this->bdd->query($sql);
			$i++;
		}
		die;
	}
	
	function _reguleDoublonsMailsNotif()
	{
		die;
		
		$clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
		
		$sql = 'SELECT count(*) as nb, LEFT(added,16) as ladate, id_client FROM clients_gestion_mails_notif WHERE status_check_quotidienne IN(1,0) AND LEFT(added,10) = "2015-04-23" AND id_notif = 1  GROUP BY id_client ORDER BY count(*) DESC ';
		
		$resultat = $this->bdd->query($sql);
		$i=0;
		while($record = $this->bdd->fetch_array($resultat))
		{
			
			if($record['nb'] > 1){
			
				$liste = $clients_gestion_mails_notif->select('id_client = '.$record['id_client'].' AND id_notif = 1 AND LEFT(added,10) = "2015-04-23"','added ASC');
				
				unset($liste[0]);
				
				
				
				foreach($liste as $l){
				
					//$sql = 'DELETE FROM `clients_gestion_mails_notif` WHERE id_clients_gestion_mails_notif = "'.$l['id_clients_gestion_mails_notif'].'"';
					//$this->bdd->query($sql);
				
				}
				
				$i++;
				
			}
		}
		echo 'nb : '.$i;
		die;
	}
	
	function _reguleloan(){
		
		//REMETTRE LE DIE
		die;
		
		// Chargement des datas
		$loans = $this->loadData('loans');
		$transactions = $this->loadData('transactions');
		$lenders = $this->loadData('lenders_accounts');
		$clients = $this->loadData('clients');
		$wallets_lines = $this->loadData('wallets_lines');
		$companies = $this->loadData('companies');
		$projects = $this->loadData('projects');
	
	
		// FB
		$this->settings->get('Facebook','type');
		$lien_fb = $this->settings->value;
		
		// Twitter
		$this->settings->get('Twitter','type');
		$lien_tw = $this->settings->value;
		
		
		$id_project = 9155;
	
	
		$sql='SELECT count(DISTINCT id_lender) FROM `loans` WHERE id_project = '.$id_project;
		$result = $this->bdd->query($sql);
		$nb_loans = (int)($this->bdd->result($result,0,0));
	
		//$nb_loans = $loans->getNbPreteurs($id_project);
		
		$lesloans = $loans->select('id_project = '.$id_project.' AND status = 0','',0,100);
		
		
		
		$i=0;
		foreach($lesloans as $l)
		{
			
			// On regarde si on a pas deja un remb pour ce bid
			
			if($transactions->get($l['id_loan'],'id_loan_remb')==false)
			{
				echo $l['id_loan'].'<br>';
				// On recup le projet
				$projects->get($l['id_project'],'id_project');
				// On recup l'entreprise
				$companies->get($projects->id_company,'id_company');
				
				// On recup lender
				$lenders->get($l['id_lender'],'id_lender_account');
				// on recup les infos du lender
				$clients->get($lenders->id_client_owner,'id_client');
				
				// On change le satut des loans du projet refusé
				$loans->get($l['id_loan'],'id_loan');
				$loans->status = 1;
				$loans->update();
				
				// On redonne l'argent aux preteurs
				
									
				// On enregistre la transaction
				$transactions->id_client = $lenders->id_client_owner;
				$transactions->montant = $l['amount'];
				$transactions->id_langue = 'fr';
				$transactions->id_loan_remb = $l['id_loan'];
				$transactions->date_transaction = date('Y-m-d H:i:s');
				$transactions->status = '1';
				$transactions->etat = '1';
				$transactions->ip_client = $_SERVER['REMOTE_ADDR'];
				$transactions->type_transaction = 2; 
				$transactions->transaction = 2; // transaction virtuelle
				$transactions->id_transaction = $transactions->create();
				
				
				// on enregistre la transaction dans son wallet
				$wallets_lines->id_lender = $l['id_lender'];
				$wallets_lines->type_financial_operation = 20;
				$wallets_lines->id_transaction = $transactions->id_transaction;
				$wallets_lines->status = 1;
				$wallets_lines->type = 2;
				$wallets_lines->amount = $l['amount'];
				$wallets_lines->id_wallet_line = $wallets_lines->create();
				
				// Motif virement
				$p = substr($this->ficelle->stripAccents(utf8_decode($clients->prenom)),0,1);
				$nom = $this->ficelle->stripAccents(utf8_decode($clients->nom));
				$id_client = str_pad($clients->id_client,6,0,STR_PAD_LEFT);
				$motif = mb_strtoupper($id_client.$p.$nom,'UTF-8');
				
				//**************************************//
				//*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
				//**************************************//
				
				// Recuperation du modele de mail
				$this->mails_text->get('preteur-pret-refuse','lang = "'.$this->language.'" AND type');

	
				// Variables du mailing
				$varMail = array(
				'surl' => $this->surl,
				'url' => $this->furl,
				'prenom_p' => $clients->prenom,
				'valeur_bid' => number_format($l['amount']/100, 0, ',', ' '),
				'nom_entreprise' => $companies->name,
				'nb_preteurMoinsUn' => ($nb_loans-1),
				'motif_virement' => $motif,
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
				$i++;
				
			}
			
		}
		echo 'nb : '.$i;
		die;
	}
	
	
	function delete_echeanciers($id_project)
	{
		die;
		$projects_status = $this->loadData('projects_status');
		
		// On recupere le statut
		$projects_status->getLastStatut($id_project);
		
		echo $projects_status->status;
		die;
	}
	
	
	
        
        function _test_unitaire_alerte_altares()
        {
            //init test
            $this->projects = $this->loadData('projects');
            $this->projects->get(11100,'id_project');
            //fin init test
            
            
            //on envoi un MAIL ALERTE
            // subject
            $subject = '[Alerte] Webservice Altares sans reponse';

            // message
            $message = '
                <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <title>Webservice Altares sans r&eacute;ponse</title>
                </head>
                <style>
                    table {width: 100%;}
                    th {height: 50px;}
                </style>

                <body >
                    <table border="0" width="450" style="margin:auto;">
                        <tr>
                                <td colspan="2" ><img src="'.$this->surl.'/images/default/emails/logo.png" alt="logo" /></td>
                        </tr>
                        <tr>
                                <td colspan="2">Le Webservice Altares ne semble pas r&eacute;pondre</td>
                        </tr>

                        <tr>
                            <td colspan="2">Projet touch&eacute; :</td>
                        </tr>
                    </table>

                    <br />
                    Id Projet : '.$this->projects->id_project.'<br />
                    Nom : '.$this->projects->title.'
                    


                </body>
                </html>
                ';


            // To send HTML mail, the Content-type header must be set
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // Additional headers
            //$headers .= 'To: Damien <d.courtier@equinoa.com>, Kelly <kelly@example.com>' . "\r\n";
            //$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
            $headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";

            // multiple recipients
            //$to  = 'aidan@example.com' . ', '; // note the comma
            //$to .= 'wez@example.com';
            $to = 'k1@david.equinoa.net';


            // Recupération du destinataire
            $this->settings->get('Adresse alerte altares erreur', 'type');
            $to = $this->settings->value;

            // Mail it
            mail($to, $subject, $message, $headers);

            // FIN ENVOI MAIL ALERTE
        }
		
		function _updatelender1()
		{
			die;
			
			// datas
			$this->clients = $this->loadData('clients');
			$this->clients_adresses = $this->loadData('clients_adresses');
			$this->echeanciers = $this->loadData('echeanciers');
			$this->lenders_imposition_history = $this->loadData('lenders_imposition_history');
			
			// EQ-Acompte d'impôt sur le revenu
			$this->settings->get("EQ-Acompte d'impôt sur le revenu",'type');
			$prelevements_obligatoires = $this->settings->value;
			
			// EQ-Contribution additionnelle au Prélèvement Social
			$this->settings->get('EQ-Contribution additionnelle au Prélèvement Social','type');
			$contributions_additionnelles = $this->settings->value;
			
			// EQ-CRDS
			$this->settings->get('EQ-CRDS','type');
			$crds = $this->settings->value;
			
			// EQ-CSG
			$this->settings->get('EQ-CSG','type');
			$csg = $this->settings->value;
			
			// EQ-Prélèvement de Solidarité
			$this->settings->get('EQ-Prélèvement de Solidarité','type');
			$prelevements_solidarite = $this->settings->value;
			
			// EQ-Prélèvement social
			$this->settings->get('EQ-Prélèvement social','type');
			$prelevements_sociaux = $this->settings->value;
			
			// EQ-Retenue à la source
			$this->settings->get('EQ-Retenue à la source','type');
			$retenues_source = $this->settings->value;
			
			$id_client = 1;
			$id_lender = 1;
			
			$this->clients->get($id_client,'id_client');
			$this->clients_adresses->get($id_client,'id_client');
			
			$this->etranger = 0;
			// fr/resident etranger
			if($this->clients->id_nationalite <= 1 && $this->clients_adresses->id_pays_fiscal > 1){
				$this->etranger = 1;
			}
			// no fr/resident etranger
			elseif($this->clients->id_nationalite > 1 && $this->clients_adresses->id_pays_fiscal > 1){
				$this->etranger = 2;
			}
			echo $this->etranger;
			
			
			$tabImpo = array(
			'prelevements_obligatoires' => $prelevements_obligatoires,
			'contributions_additionnelles' => $contributions_additionnelles,
			'crds' => $crds,
			'csg' => $csg,
			'prelevements_solidarite' => $prelevements_solidarite,
			'prelevements_sociaux' => $prelevements_sociaux,
			'retenues_source' => $retenues_source);
			
			print_r($tabImpo);
			die;
			
			//$this->echeanciers->update_imposition_etranger($id_lender,$this->etranger,$tabImpo,0);	
		}
	

}
	
	