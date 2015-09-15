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
class clients_adresses_crud
{
	
	public $id_adresse;
	public $id_client;
	public $defaut;
	public $type;
	public $nom_adresse;
	public $civilite;
	public $nom;
	public $prenom;
	public $societe;
	public $adresse1;
	public $adresse2;
	public $adresse3;
	public $cp;
	public $ville;
	public $id_pays;
	public $telephone;
	public $mobile;
	public $commentaire;
	public $meme_adresse_fiscal;
	public $adresse_fiscal;
	public $ville_fiscal;
	public $cp_fiscal;
	public $id_pays_fiscal;
	public $status;
	public $added;
	public $updated;

	
	function clients_adresses($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_adresse = '';
		$this->id_client = '';
		$this->defaut = '';
		$this->type = '';
		$this->nom_adresse = '';
		$this->civilite = '';
		$this->nom = '';
		$this->prenom = '';
		$this->societe = '';
		$this->adresse1 = '';
		$this->adresse2 = '';
		$this->adresse3 = '';
		$this->cp = '';
		$this->ville = '';
		$this->id_pays = '';
		$this->telephone = '';
		$this->mobile = '';
		$this->commentaire = '';
		$this->meme_adresse_fiscal = '';
		$this->adresse_fiscal = '';
		$this->ville_fiscal = '';
		$this->cp_fiscal = '';
		$this->id_pays_fiscal = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
	
	function get($id,$field='id_adresse')
	{
		$sql = 'SELECT * FROM  `clients_adresses` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_adresse = $record['id_adresse'];
			$this->id_client = $record['id_client'];
			$this->defaut = $record['defaut'];
			$this->type = $record['type'];
			$this->nom_adresse = $record['nom_adresse'];
			$this->civilite = $record['civilite'];
			$this->nom = $record['nom'];
			$this->prenom = $record['prenom'];
			$this->societe = $record['societe'];
			$this->adresse1 = $record['adresse1'];
			$this->adresse2 = $record['adresse2'];
			$this->adresse3 = $record['adresse3'];
			$this->cp = $record['cp'];
			$this->ville = $record['ville'];
			$this->id_pays = $record['id_pays'];
			$this->telephone = $record['telephone'];
			$this->mobile = $record['mobile'];
			$this->commentaire = $record['commentaire'];
			$this->meme_adresse_fiscal = $record['meme_adresse_fiscal'];
			$this->adresse_fiscal = $record['adresse_fiscal'];
			$this->ville_fiscal = $record['ville_fiscal'];
			$this->cp_fiscal = $record['cp_fiscal'];
			$this->id_pays_fiscal = $record['id_pays_fiscal'];
			$this->status = $record['status'];
			$this->added = $record['added'];
			$this->updated = $record['updated'];

			return true;
		}
		else
		{
			$this->unsetData();
			return false;
		}
	}
	
	function update($cs='')
	{
		$this->id_adresse = $this->bdd->escape_string($this->id_adresse);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->defaut = $this->bdd->escape_string($this->defaut);
		$this->type = $this->bdd->escape_string($this->type);
		$this->nom_adresse = $this->bdd->escape_string($this->nom_adresse);
		$this->civilite = $this->bdd->escape_string($this->civilite);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->societe = $this->bdd->escape_string($this->societe);
		$this->adresse1 = $this->bdd->escape_string($this->adresse1);
		$this->adresse2 = $this->bdd->escape_string($this->adresse2);
		$this->adresse3 = $this->bdd->escape_string($this->adresse3);
		$this->cp = $this->bdd->escape_string($this->cp);
		$this->ville = $this->bdd->escape_string($this->ville);
		$this->id_pays = $this->bdd->escape_string($this->id_pays);
		$this->telephone = $this->bdd->escape_string($this->telephone);
		$this->mobile = $this->bdd->escape_string($this->mobile);
		$this->commentaire = $this->bdd->escape_string($this->commentaire);
		$this->meme_adresse_fiscal = $this->bdd->escape_string($this->meme_adresse_fiscal);
		$this->adresse_fiscal = $this->bdd->escape_string($this->adresse_fiscal);
		$this->ville_fiscal = $this->bdd->escape_string($this->ville_fiscal);
		$this->cp_fiscal = $this->bdd->escape_string($this->cp_fiscal);
		$this->id_pays_fiscal = $this->bdd->escape_string($this->id_pays_fiscal);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'UPDATE `clients_adresses` SET `id_client`="'.$this->id_client.'",`defaut`="'.$this->defaut.'",`type`="'.$this->type.'",`nom_adresse`="'.$this->nom_adresse.'",`civilite`="'.$this->civilite.'",`nom`="'.$this->nom.'",`prenom`="'.$this->prenom.'",`societe`="'.$this->societe.'",`adresse1`="'.$this->adresse1.'",`adresse2`="'.$this->adresse2.'",`adresse3`="'.$this->adresse3.'",`cp`="'.$this->cp.'",`ville`="'.$this->ville.'",`id_pays`="'.$this->id_pays.'",`telephone`="'.$this->telephone.'",`mobile`="'.$this->mobile.'",`commentaire`="'.$this->commentaire.'",`meme_adresse_fiscal`="'.$this->meme_adresse_fiscal.'",`adresse_fiscal`="'.$this->adresse_fiscal.'",`ville_fiscal`="'.$this->ville_fiscal.'",`cp_fiscal`="'.$this->cp_fiscal.'",`id_pays_fiscal`="'.$this->id_pays_fiscal.'",`status`="'.$this->status.'",`added`="'.$this->added.'",`updated`=NOW() WHERE id_adresse="'.$this->id_adresse.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_adresse,'id_adresse');
	}
	
	function delete($id,$field='id_adresse')
	{
		if($id=='')
			$id = $this->id_adresse;
		$sql = 'DELETE FROM `clients_adresses` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_adresse = $this->bdd->escape_string($this->id_adresse);
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->defaut = $this->bdd->escape_string($this->defaut);
		$this->type = $this->bdd->escape_string($this->type);
		$this->nom_adresse = $this->bdd->escape_string($this->nom_adresse);
		$this->civilite = $this->bdd->escape_string($this->civilite);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->societe = $this->bdd->escape_string($this->societe);
		$this->adresse1 = $this->bdd->escape_string($this->adresse1);
		$this->adresse2 = $this->bdd->escape_string($this->adresse2);
		$this->adresse3 = $this->bdd->escape_string($this->adresse3);
		$this->cp = $this->bdd->escape_string($this->cp);
		$this->ville = $this->bdd->escape_string($this->ville);
		$this->id_pays = $this->bdd->escape_string($this->id_pays);
		$this->telephone = $this->bdd->escape_string($this->telephone);
		$this->mobile = $this->bdd->escape_string($this->mobile);
		$this->commentaire = $this->bdd->escape_string($this->commentaire);
		$this->meme_adresse_fiscal = $this->bdd->escape_string($this->meme_adresse_fiscal);
		$this->adresse_fiscal = $this->bdd->escape_string($this->adresse_fiscal);
		$this->ville_fiscal = $this->bdd->escape_string($this->ville_fiscal);
		$this->cp_fiscal = $this->bdd->escape_string($this->cp_fiscal);
		$this->id_pays_fiscal = $this->bdd->escape_string($this->id_pays_fiscal);
		$this->status = $this->bdd->escape_string($this->status);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);

		
		$sql = 'INSERT INTO `clients_adresses`(`id_client`,`defaut`,`type`,`nom_adresse`,`civilite`,`nom`,`prenom`,`societe`,`adresse1`,`adresse2`,`adresse3`,`cp`,`ville`,`id_pays`,`telephone`,`mobile`,`commentaire`,`meme_adresse_fiscal`,`adresse_fiscal`,`ville_fiscal`,`cp_fiscal`,`id_pays_fiscal`,`status`,`added`,`updated`) VALUES("'.$this->id_client.'","'.$this->defaut.'","'.$this->type.'","'.$this->nom_adresse.'","'.$this->civilite.'","'.$this->nom.'","'.$this->prenom.'","'.$this->societe.'","'.$this->adresse1.'","'.$this->adresse2.'","'.$this->adresse3.'","'.$this->cp.'","'.$this->ville.'","'.$this->id_pays.'","'.$this->telephone.'","'.$this->mobile.'","'.$this->commentaire.'","'.$this->meme_adresse_fiscal.'","'.$this->adresse_fiscal.'","'.$this->ville_fiscal.'","'.$this->cp_fiscal.'","'.$this->id_pays_fiscal.'","'.$this->status.'",NOW(),NOW())';
		$this->bdd->query($sql);
		
		$this->id_adresse = $this->bdd->insert_id();
		
		if($cs=='')
		{
	
		}
		else
		{
		
		}
		
		$this->get($this->id_adresse,'id_adresse');
		
		return $this->id_adresse;
	}
	
	function unsetData()
	{
		$this->id_adresse = '';
		$this->id_client = '';
		$this->defaut = '';
		$this->type = '';
		$this->nom_adresse = '';
		$this->civilite = '';
		$this->nom = '';
		$this->prenom = '';
		$this->societe = '';
		$this->adresse1 = '';
		$this->adresse2 = '';
		$this->adresse3 = '';
		$this->cp = '';
		$this->ville = '';
		$this->id_pays = '';
		$this->telephone = '';
		$this->mobile = '';
		$this->commentaire = '';
		$this->meme_adresse_fiscal = '';
		$this->adresse_fiscal = '';
		$this->ville_fiscal = '';
		$this->cp_fiscal = '';
		$this->id_pays_fiscal = '';
		$this->status = '';
		$this->added = '';
		$this->updated = '';

	}
}