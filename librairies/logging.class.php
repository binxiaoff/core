<?php
// ********************************************************************************* //
// ************************************* LOGGING *********************************** //
// ********************************************************************************* //
//                                                                                   //
//  Version : 2.0.0                       											 //
//  Date : 31/05/2010                                                                //
//  Coupable : TR																	 //
//  Last release note : Classe de logging basique			     					 //
//                                                                                   //
// ********************************************************************************* //

class logging
{
	

	function logging($params)
	{
		$this->bdd = $params[0];
	}

	// Ajout d'un log, on peut passer un type et une information sur le user, autormatiquement la session, le contexte serveur et l'ip sont loggues
	function addLog($event,$type='',$user='')
	{
		$event = $this->bdd->escape_string($event);
		$type = $this->bdd->escape_string($type);
		$user = $this->bdd->escape_string($user);
		$sql = 'INSERT INTO logging(event,type,user,date,session,server,ip) VALUES("'.$event.'","'.$type.'","'.$user.'",NOW(),"'.addslashes(serialize($_SESSION)).'","'.addslashes(serialize($_SERVER)).'","'.$_SERVER['REMOTE_ADDR'].'")';

		$this->bdd->query($sql);
	}
	
	// Suppression d'un log unique
	function deleteLog($id_log)
	{
		$sql = 'DELETE FROM logging where id_logging="'.$id_logging.'"';
		$this->bdd->queyr($sql);
	}
	
	// Vider les logs (dans le temps, ou par type)
	function emptyLogs($from='',$to='',$type='%')
	{
		$sql = 'DELETE FROM logging WHERE 1';
		if($from != '')
			$sql .= ' AND date > "'.$from.'"';
		if($to != '')
			$sql .= ' AND date < "'.$to.'"';
		if($type != '')
			$sql .= ' AND type LIKE "'.$type.'"';
		$this->bdd->query($sql);
	}
	
	// Lister les logs (dans le temps, ou part type, ou les deux, ca pete...)
	function listLogs($from='',$to='',$type='%')
	{
		if($to=='')
			$to = date('Y-m-d');
		if($from=='')
			$from = date('Y-m-d',strtotime("-1 day"));
		$sql = 'SELECT * FROM logging WHERE type  LIKE "'.$type.'" AND date > "'.$from.'" AND date < "'.$to.'"';
		$result = $this->bdd->query($sql);
		$results = array();
		while($record = $this->bdd->fetch_array($result))
		{
			$results[] = $record;	
		}
		return $results;
	}
}

?>