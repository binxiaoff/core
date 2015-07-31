<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and 
// associated documentation files (the "Software"), to deal in the Software without restriction, 
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, 
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies 
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but 
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. 
// In no event shall the authors or copyright holders equinoa be liable for any claim, 
// damages or other liability, whether in an action of contract, tort or otherwise, arising from, 
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising 
// or otherwise to promote the sale, use or other dealings in this Software without 
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//                                                                                   
// **************************************************************************************************** //



class bdd {
 
	var $config = array();
	var $option = array(); 

	var $connect_id; // Identifiant de connexion MySQL
	var $requete; // Contient la requête au format SQL
	var $ressource; // Contient la ressource si succès ou FALSE
		
	var $num_rows;
	var $affected_rows;
	
	//public $log_error = '';
	//public $log_debug = '';
	
	function bdd($bdd_config, $bdd_option, $auto_connect = true)
	{
		$this->config = $bdd_config;
		$this->option = $bdd_option;
		
		if($auto_connect == true)
		{
			$this->connect();
		}
	}
	
	function error($msg = null)
	{
		$this->log_error[] = '['.mysql_errno().'] '.mysql_error().' - '.$msg;
		 
		if($this->option['DISPLAY_ERREUR'] == true)
		{
			$cle = key($this->log_error);
			
			$_SESSION['error'][] = '<p style="color:#ff0000;"><b>ERREUR !</b> '.$this->log_error[$cle].'</p>';
			next($this->log_error);	
		}
	}
	
	function debug($function,$time='')
	{	
		$this->log_debug[] = $function.' '.$time;

		if($this->option['DEBUG_DISPLAY'] != false)
		{
    		$_SESSION['debug'][] = array('requete'=>$function,'time'=>$time);
		}
    }
	
    function connect($select_base = true, $host = null, $user = null, $password = null)
	{
		if($host == null) { $host = $this->config['HOST']; }
		if($user == null) { $user = $this->config['USER']; }
		if($password == null) { $password = $this->config['PASSWORD']; }

		$this->connect_id = mysql_connect($host,$user,$password);
		
		mysql_query("SET NAMES 'utf8'");

        if(!$this->connect_id)
		{

        	$this->error(); 
			
			return false;
        }
        else
		{
        	//$function = 'connect()';	
        	//$this->debug($function);
			
			if($select_base == true) { $this->select_base(); }
			
			return $this->connect_id;	
        }
    }
    
    function select_base($bdd = null, $connect_id = null)
	{
	
		if($bdd == null) { $bdd = $this->config['BDD']; }
		if($connect_id == null) { $connect_id = $this->connect_id; }
		
    	if(!mysql_select_db($bdd,$connect_id))
		{
			$this->error(); 
			
			return false;
    	}
    	else
		{
    		//$function = 'select_base()';
        	//$this->debug($function);
    	}
    }
    
    function close($connect_id = null)
	{
		if($connect_id == null) { $connect_id = $this->connect_id; }
	
    	if(!@mysql_close($connect_id))
		{
			$msg = 'Erreur lors de la fermeture de la connexion !';
    		$this->error($msg);
			
			return false;
    	}
    	else
		{
    		//$function = 'close()';
			//$this->debug($function);
    	}
    }
    
    function query($requete, $connect_id = null) {
	
		if($connect_id == null) { $connect_id = $this->connect_id; }
		$time = '';
		
    	$this->requete = $requete;

		if($this->option['BDD_PANIC'] == true) { $start = microtime(true); }
		
    	$this->ressource = @mysql_query($requete, $connect_id);
		
		if($this->option['BDD_PANIC'] == true && $_SERVER['HTTP_HOST']=='fr.nuxe.com') 
		{ 
			$stop = microtime(true); 
			$time = ($stop - $start); 
			
			if(($stop-$start)>$this->option['BDD_PANIC_SEUIL'])
				mail($this->option['BDD_PANIC_MAIL'],'['.$this->config['NOM'].'] Slow query : '.$time,$requete."\r\n\r\nSESSION:\r\n".serialize($_SESSION)."\r\n\r\nSERVER:\r\n".serialize($_SERVER));
		}
		
    	if(!$this->ressource) {
		
			$msg = 'Erreur lors de l\'execution de la requete "<i>'.$this->requete.'</i>"';
    		$this->error($msg);
			return false;
    	}
    	else {
		
        	$this->debug($this->requete,$time);
        	
        	return $this->ressource;
    	}
    }
	
    function fetch_array($ressource = null)
	{	
    	if($ressource == null) { $ressource = $this->ressource; }

    	$array = @mysql_fetch_array($ressource);
    	
    	if(!is_resource($ressource)) {
		
    		$this->error('L\'argument passé à la fonction fetch_array() n\'est pas une ressource !');
    	}
    	elseif(!$array) {
    		
			//$function = 'fetch_array()';
        	//$this->debug($function);
    	}
		else {
		
			return $array;
		}
    }
	
	function affected_rows($connect_id = null)
	{	
    	if($connect_id == null) { $connect_id = $this->connect_id;	}

    	$this->affected_rows = @mysql_affected_rows($connect_id);

    	if(!is_resource($connect_id)) {
		
			$this->error('L\'argument passé à la fonction affected_rows() n\'est pas une ressource valide !');
    	}
		elseif ($this->affected_rows == -1) {
		
			$this->error();
		}
    	else {
		
    		//$function = 'affected_rows()';
        	//$this->debug($function);
        	
        	return $this->affected_rows;
    	}
    }
	
    function num_rows($ressource = null)
	{	
    	if($ressource == null) { $ressource = $this->ressource; }

    	$this->num_rows = @mysql_num_rows($ressource);
    	
		if(!is_resource($ressource)) {
		
			$this->error('L\'argument passé à la fonction num_rows() n\'est pas une ressource valide !');
    	}
		else {
		
			//$function = 'num_rows()';
        	//$this->debug($function);
        	
        	return $this->num_rows;
    	}
    }
    
	function escape_string($arg, $connect_id = null)
	{		
		if($connect_id == null) { $connect_id = $this->connect_id; }
		
		$magic_quotes_config = get_magic_quotes_gpc();

		if($magic_quotes_config == 1) { $arg = stripslashes($arg); }
   		
		$arg = @mysql_real_escape_string($arg, $connect_id);

		return $arg;
	}
	
    function fetch_assoc($ressource = null)
	{	
    	if($ressource == null) { $ressource = $this->ressource; }

    	$array = @mysql_fetch_assoc($ressource);
    	
    	if(!is_resource($ressource)) {
		
    		$this->error('L\'argument passé à la fonction fetch_assoc() n\'est pas une ressource !');
    	}
    	elseif(!$array) {
    		
			//$function = 'fetch_assoc()';
        	//$this->debug($function);
    	}
		else {
		
			return $array;
		}
    }
    
    function insert_id($connect_id = null)
	{	
    	if($connect_id == null) { $connect_id = $this->connect_id; }
    	
		$last_insert_id = @mysql_insert_id($connect_id);
		
    	if(!$last_insert_id) {
		
    		$this->error(); 
    	}
		elseif($last_insert_id == 0) {
		
    		$this->error('Aucun ID généré lors de la dernière requête !'); 
    	}
    	else {
		
    		//$function = 'insert_id()';
        	//$this->debug($function);
			
        	return $last_insert_id;
    	}
    }
	
	function result($ressource = null, $row = null, $field = null)
	{	
		if($ressource == null) { $ressource = $this->ressource; }
		if($row == null) { $row = 0; }
    	if($field == null) { $field = 0; }
		
		$champ = @mysql_result($ressource, $row, $field);
		
    	if($champ===false) {
		
    		$this->error(); 
    	}
    	else {
		
    		//$function = 'result()';
        	//$this->debug($function);
			
        	return $champ;
    	}
    }
			
	function controlSlug($table, $slug, $id_name, $id_value)
    {
        $sql = 'SELECT slug FROM '.$table.' WHERE slug = "'.$slug.'" AND '.$id_name.' != "'.$id_value.'"';
        $res = $this->query($sql);
        
        if($this->num_rows($res) == 1 || $slug == "")
        {
			if($table == 'tree' && $id_value == 1 && $slug == '')
			{
				$slug = '';
			}
			else
			{
				$slug = $slug.'-'.$id_value;
			}
        }
        
        $sql = 'UPDATE '.$table.' SET slug = "'.$slug.'" WHERE '.$id_name.' = "'.$id_value.'"';
        $this->query($sql);
    }
	
	/*function controlSlugNew($table, $slug, $id_name, $id_value)
    {
        $sql = 'SELECT slug FROM '.$table.' WHERE slug = "'.$slug.'" AND '.$id_name.' = "'.$id_value.'"';
        $res = $this->query($sql);
        
        if($this->num_rows($res) >= 1 || $slug == "")
        {
			if($table == 'tree' && $id_value == 1 && $slug == '')
			{
				$slug = '';
			}
			else
			{
				$slug = $slug.'-'.$id_value;
			}
			
			$sql = 'UPDATE '.$table.' SET slug = "'.$slug.'" WHERE '.$id_name.' = "'.$id_value.'"';
	        $this->query($sql);
			
			return true;
        }
		else
			return false;
    }*/
	
	function controlSlugMulti($table, $slug, $id_value, $list_field_value, $id_langue)
    {
		foreach($list_field_value as $champ => $valeur)
		{
			$list.=' '.$champ.' != "'.$valeur.'" ';
			if(next($list_field_value))
				$list.= ' OR ';
		}
		
		$sql = 'SELECT * FROM '.$table.' WHERE slug = "'.$slug.'" AND ('.$list.') ';
		
        $res = $this->query($sql);
        
        if($this->num_rows($res) >= 1)
        {
            $slug = $slug.'-'.$id_value;
			
			if($id_langue!='')
				$slug.= '-'.$id_langue;
			
			foreach($list_field_value as $champ => $valeur)
			{
				$list2.=' AND '.$champ.' = "'.$valeur.'" ';
			}
			
			$sql = 'UPDATE '.$table.' SET slug = "'.$slug.'" WHERE 1=1 '.$list2.' ';
        	$this->query($sql);
			
			$this->controlSlugMulti($table, $slug, $id_value, $list_field_value, $id_langue);
        }
    }
	
	function controlSlugMultiLn($table, $slug, $id_value, $list_field_value, $id_langue)
    {
		$sql = 'SELECT * FROM '.$table.' WHERE slug = "'.$slug.'" AND id_langue = "'.$id_langue.'" ';
		
        $res = $this->query($sql);
        
        if($this->num_rows($res) > 1)
        {
            $new_slug = $slug;
			
			if($id_langue!='')
				$new_slug.= '-'.$id_langue;
			
			$new_slug.= '-'.$id_value;
			
			foreach($list_field_value as $champ => $valeur)
			{
				$list2.=' AND '.$champ.' = "'.$valeur.'" ';
			}
			
			$sql = 'UPDATE '.$table.' SET slug = "'.$new_slug.'" WHERE 1=1 '.$list2.' ';
        	$this->query($sql);
			
			$this->controlSlugMultiLn($table, $new_slug, $id_value, $list_field_value, $id_langue);
        }
    }
	
	function generateSlug($string)
	{
		$string = strip_tags(utf8_decode($string));
		$string = strtr($string, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ','AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
		$string = strtolower( $string ); // lower-case the string
		$string = preg_replace( '/[ ]/', '-', $string ); // replace special characters by score
		$string = preg_replace( '/[^a-z0-9-.]/', '', $string ); // replace all non-alphanumeric characters by void
		$string = preg_replace( '/[-]{2,}/', '-', $string ); // replace multi '-' by once
		$string = preg_replace( '/[-]{1,}$/', '', $string ); // replace end '-' by void
		return $string;
	}
	
	function listEnum($nom_table,$nom_enum, $nom_champ, $selected = null )
	{
		$sql = 'SHOW COLUMNS FROM '.$nom_table.' LIKE "'.$nom_enum.'" ' ;
		$resultat = $this->query($sql) ; //or die("show columns from $nom_table like '$nom_enum' ".mysql_error());
        
		$result = array();
        while($result = $this->fetch_array($resultat))
		{
			if(preg_match('!enum(.+)!', $result['Type']))
			{
				$enum2 = preg_replace ('!^enum\((.+)\)$!', '$1', $result['Type']);
	
				$enum1 = str_replace ("'", "", $enum2);
				$enum = explode (',', $enum1);
					
				$selecteur = '<select name="'. $nom_champ.'" id="'. $nom_champ.'" class="select">';
					
				foreach ($enum as $valeur){
						
					if($selected==$valeur) $selecteur .= ' <option selected value="' . $valeur . '">' . $valeur . '</option>';
					else $selecteur .= ' <option value="' . $valeur . '">' . $valeur . '</option>';
				}
					
				$selecteur .= '</select>';
			}
		}
			
		return $selecteur ;
	} 
	
	
	function getEnum($nom_table,$nom_enum)
	{
		$sql = 'SHOW COLUMNS FROM '.$nom_table.' LIKE "'.$nom_enum.'" ';
		$resultat = $this->query($sql);
		$data =  mysql_fetch_assoc($resultat);
		
		$new_enum2 = preg_replace('!^enum\((.+)\)$!','$1',$data['Type']).",'".$valeur."'";
		$new_enum1 = str_replace ("'", "", $new_enum2);	
		$new_enum = explode (',', $new_enum1);
		
		return $new_enum;
	}
	
	function majEnum($nom_table,$nom_enum,$valeur)
	{
		$sql = 'SHOW COLUMNS FROM '.$nom_table.' LIKE "'.$nom_enum.'" ' ;
		$resultat = $this->query($sql);
		$data =  mysql_fetch_assoc($resultat);
		
		$new_enum2 = preg_replace('!^enum\((.+)\)$!','$1',$data['Type']).",'".$valeur."'";
		$new_enum1 = str_replace ("'", "", $new_enum2);	
		$new_enum = explode (',', $new_enum1);
		$enum_tab = array();
		foreach($new_enum as $enum)
		{
			if($enum != '')
			{
				$enum_tab[] = $enum;
			}
		}
		$new_enum = implode('\',\'',$enum_tab);
		
		$sql = 'ALTER TABLE `'.$nom_table.'` CHANGE `'.$nom_enum.'` `'.$nom_enum.'` ENUM(\''.$new_enum.'\') NULL DEFAULT NULL';
		$resultat = $this->query($sql);
	}
	
	function deleteEnum($nom_table,$nom_enum,$valeur)
	{
		$sql = 'SHOW COLUMNS FROM '.$nom_table.' LIKE "'.$nom_enum.'" ';
		$resultat = $this->query($sql);
		$data =  mysql_fetch_assoc($resultat);
		
		$new_enum2 = preg_replace('!^enum\((.+)\)$!','$1',$data['Type']).",'".$valeur."'";
		$new_enum1 = str_replace ("'", "", $new_enum2);	
		$new_enum = explode (',', $new_enum1);
		$enum_tab = array();
		foreach($new_enum as $enum)
		{
			if($enum != $valeur)
			{
				$enum_tab[] = $enum;
			}
		}
		$new_enum = implode('\',\'',$enum_tab);
		
		$sql = 'ALTER TABLE `'.$nom_table.'` CHANGE `'.$nom_enum.'` `'.$nom_enum.'` ENUM(\''.$new_enum.'\') NULL DEFAULT NULL';
		$resultat = $this->query($sql);
	}
	
	function run($req)
	{
		$resultat = $this->query($req);
        $result = array();
		
        while($record = $this->fetch_array($resultat))
        {
            $result[] = $record;
        }
        return $result;		
	}
} 

?>