<?php
class tnmp {

	function tnmp($params)
	{
		$this->nmp = $params[0];
		$this->nmp_desabo = $params[1];
		$this->env = $params[2];
	}
	
	function constructionVariablesServeur($tab)
	{
		$result = array();
		
		foreach($tab as $key=>$value)
		{
			$result['[EMV DYN]'.$key.'[EMV /DYN]'] = $value;
		}
		
		return $result;
	}
	
	function sendMailNMP($tabFiler,$varMail,$nmp_secure,$id_nmp,$nmp_unique,$mode)
	{
		if($this->env == 'demo')
		{
			// pas d'enregistrement nmp de minuit a 6h du matin pour la demo
			$debut = mktime (0,0,0, date("m"),date("d"),date("Y"));
			$fin = mktime (6,0,0, date("m"),date("d"),date("Y"));
			
			if(time() >= $debut && time() <= $fin)
			{
				//echo 'stop';
				die;	
			}
		}
		
		// Recuperation des variables
		foreach($tabFiler as $key=>$value)
		{
			${$key} = $value;
		}
		
		// On envoi NMP qui si il est pas désabo
		if(!$this->nmp_desabo->get($email_nmp,'email') || $mode == 0)
		{
			// Initialisation du contenu		
			$contentPush = array();
			
			// Initialisation des variables dynamiques
			$varDyn = array();
				
			foreach($varMail as $key=>$value)
			{
				$varDyn['entry'][] = array('key'=>$key,'value'=>$value);	
			}
			
			$varDyn['entry'][] = array('key'=>'miroir','value'=>$this->lurl.'/miroir/'.$id_filermails.'/'.md5($id_textemail));
			$varDyn['entry'][] = array('key'=>'desabo','value'=>$this->lurl.'/removeNMP/'.$desabo.'/'.$id_filermails.'/'.$email_nmp);
			
			// Initialisation des parametres		
			$arg0['arg0'] = array('content'=>$contentPush,'dyn'=> $varDyn,'email'=>$email_nmp,'encrypt'=>$nmp_secure,'notificationId'=>$id_nmp,'random'=>$nmp_unique,'senddate'=>date('Y-m-d'),'synchrotype'=>'NOTHING','uidkey'=>'EMAIL');
			
			// Preparation du queue
			$this->nmp->serialize_content = serialize($arg0);
			$this->nmp->date = date('Y-m-d');
			$this->nmp->mailto = $email_nmp;
			$this->nmp->status = 0;
			$this->nmp->create();
		}
	}
	
	function processQueue()
	{
		
		// Connection au serveur
		$location = 'http://api.notificationmessaging.com/NMSOAP/NotificationService?wsdl';
		$client = new SoapClient($location);
		
		
		
		// Recuperation de la file d'attente
		$lQueue = $this->nmp->select('status = 0','added ASC', '', 100);
		
		// Traitement
		foreach($lQueue as $q)
		{
			// Recuperation des donnees
			$this->nmp->get($q['id_nmp'],'id_nmp');
			
			if($this->nmp->status == 0)
			{
			
				// Envoi du message		
				try
				{
					$respo = $client->sendObject(unserialize($this->nmp->serialize_content));
					
					/*print_r(unserialize($this->nmp->serialize_content));
					echo '<br>---------------<bR>';
					print_r($respo);*/
					
					$this->nmp->reponse = serialize($respo);
					$this->nmp->status = 1;
					$this->nmp->date_sent .= "-".date("Y-m-d H:i:s");
				}
				catch(Exception $e )
				{
					$this->nmp->erreur = serialize($e);
					$this->nmp->date_sent .= "-".date("Y-m-d H:i:s");
					$this->nmp->status = 2;
				}
				
				// MAJ
				$this->nmp->update();
			}						
		}
	}
}
?>