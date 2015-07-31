<?php

class elgoog {

	var $minchars = 1;
	var $exclude = array('de','et','sa','son','je');
	
	function elgoog($params)
	{
		$this->bdd = $params[0];	
	}
	
	//Supprime les accents d'une chaine
	function remove_accents($s) {
		$str = utf8_encode(str_replace('®','',html_entity_decode($s)));

		return ($str);
	}

	//Vérifie la validité d'un mot en fonction de sa taille et de sa présence ou non dans la liste d'exclusion
	function is_valid_word($word) {
		if(strlen($word) < $this->minchars)
		{
			return false;	
		}
		if(in_array($word,$this->exclude))
		{
			return false;	
		}
		
		return true;
	}

	//Remplace les caractères non valides d'une chaine par des espaces
	function clean_expression($s) {
		$ok = 'abcdefghijklmnopqrstuvwxyz0123456789ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ';
		$len = strlen($s);
		for ($i = 0; $i < $len; $i++)
			if (strpos($ok, $s[$i]) === false) $s[$i] = ' ';
		return $s;
	}

	//Transforme une chaine en une liste de mots
	function towords($s) {
		$s = ereg_replace('<[a-zA-Z][^>]*>', '', $s); //suppression des tags --> Enlever les commentaires si la chaine à indexer contient des tags HTML
		$s = strtolower($this->remove_accents($s));
		$s = $this->clean_expression($s);
		$a = explode(' ', $s);
		$a = array_filter($a,array($this,'is_valid_word'));
		$a = array_unique($a);
		sort($a);
		return $a;
	}
	
	function index()
	{
		//On commence par vider la base !
		$sql = 'TRUNCATE se_matches';	
		$this->bdd->query($sql);
		$sql = 'TRUNCATE se_words';	
		$this->bdd->query($sql);
		$sql = 'SELECT value FROM settings where type="Templates indexes"';
		$result = $this->bdd->query($sql);
		$templates = $this->bdd->result($result,0);
		
		$sql = 'SELECT id_tree, id_langue, value FROM tree_elements where status=1 and id_tree in (select id_tree from tree where id_template in ('.$templates.'))';
		echo $sql;
		$result = $this->bdd->query($sql);
		while($record = $this->bdd->fetch_array($result))
		{
			$words = $this->towords($record['value']);
			foreach($words as $word) 
			{
				$sql = 'SELECT id_word FROM se_words WHERE word = "'.$word.'" and id_langue="'.$record['id_langue'].'"';
				$r = $this->bdd->query($sql);
				if ($r && $this->bdd->num_rows($r) > 0) 
				{
					$word_id = $this->bdd->result($r, 0, 'id_word');
				}
				else 
				{
			  		$sql = 'INSERT INTO se_words (word,id_langue) VALUES ("'.$word.'","'.$record['id_langue'].'")';
			  		$this->bdd->query($sql);
			  		$word_id = $this->bdd->insert_id();
				}
				
				$sql = 'INSERT INTO se_matches (id_object, id_word, object_type) VALUES ('.$record['id_tree'].', '.$word_id.',1)';
				$this->bdd->query($sql);
			}			
		}
		
		$sql = 'SELECT id_produit, id_langue, value FROM produits_elements where status=1 and id_produit not in (select id_produit from produits where id_brand = 2) and id_element <> 40';
		$result = $this->bdd->query($sql);
		while($record = $this->bdd->fetch_array($result))
		{
			$words = $this->towords($record['value']);
			foreach($words as $word) 
			{
				$sql = 'SELECT id_word FROM se_words WHERE word = "'.$word.'" and id_langue="'.$record['id_langue'].'"';
				$r = $this->bdd->query($sql);
				if ($r && $this->bdd->num_rows($r) > 0) 
				{
					$word_id = $this->bdd->result($r, 0, 'id_word');
				}
				else 
				{
			  		$sql = 'INSERT INTO se_words (word,id_langue) VALUES ("'.$word.'","'.$record['id_langue'].'")';
			  		$this->bdd->query($sql);
			  		$word_id = $this->bdd->insert_id();
				}
				
				$sql = 'INSERT INTO se_matches (id_object, id_word, object_type) VALUES ('.$record['id_produit'].', '.$word_id.',0)';
				$this->bdd->query($sql);
			}			
		}
	}
	
	function search($phrase,$langue)
	{
		$words = $this->towords(trim($phrase));
		$swords = implode("', '", $words);
		
		$sql = "SELECT id_word FROM se_words WHERE word IN ('$swords') AND id_langue = '".$langue."'";
		$r = $this->bdd->query($sql);
		
		if ($this->bdd->num_rows($r) != count($words)) return array();
		$ids = array();
		
		while ($l = mysql_fetch_object($r)) $ids[] = $l->id_word;
		
		$sIds = implode(', ', $ids);
		$len = count($ids);
		
		$sql = "SELECT id_object, object_type FROM (SELECT count(id_object) AS cnt, id_object, object_type from se_matches WHERE id_word IN ($sIds) GROUP BY id_object, object_type) AS found_results WHERE cnt = $len";
		$r = $this->bdd->query($sql);
		
		
		$aArticles = array();
		if ($r && $this->bdd->num_rows($r) > 0) {
			while ($l = mysql_fetch_object($r)) 
			{
				$retours[$l->object_type][] = $l->id_object;
			}
		}
		return $retours;
	}	
}