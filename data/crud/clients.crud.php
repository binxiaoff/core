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
class clients_crud
{
	
	public $id_client;
	public $hash;
	public $id_langue;
	public $id_partenaire;
	public $id_partenaire_subcode;
	public $id_facebook;
	public $id_linkedin;
	public $id_viadeo;
	public $id_twitter;
	public $civilite;
	public $nom;
	public $nom_usage;
	public $prenom;
	public $slug;
	public $fonction;
	public $naissance;
	public $id_pays_naissance;
	public $ville_naissance;
	public $id_nationalite;
	public $telephone;
	public $mobile;
	public $email;
	public $password;
	public $secrete_question;
	public $secrete_reponse;
	public $type;
	public $status_depot_dossier;
	public $etape_inscription_preteur;
	public $status_inscription_preteur;
	public $status_pre_emp;
	public $status_transition;
	public $cni_passeport;
	public $signature;
	public $source;
	public $source2;
	public $source3;
	public $slug_origine;
	public $origine;
	public $optin1;
	public $optin2;
	public $status;
	public $history;
	public $added;
	public $updated;
	public $lastlogin;

	
	function clients($bdd,$params='')
	{
		$this->bdd = $bdd;
		if($params=='')
			$params = array();
		$this->params = $params;
		$this->id_client = '';
		$this->hash = '';
		$this->id_langue = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->id_facebook = '';
		$this->id_linkedin = '';
		$this->id_viadeo = '';
		$this->id_twitter = '';
		$this->civilite = '';
		$this->nom = '';
		$this->nom_usage = '';
		$this->prenom = '';
		$this->slug = '';
		$this->fonction = '';
		$this->naissance = '';
		$this->id_pays_naissance = '';
		$this->ville_naissance = '';
		$this->id_nationalite = '';
		$this->telephone = '';
		$this->mobile = '';
		$this->email = '';
		$this->password = '';
		$this->secrete_question = '';
		$this->secrete_reponse = '';
		$this->type = '';
		$this->status_depot_dossier = '';
		$this->etape_inscription_preteur = '';
		$this->status_inscription_preteur = '';
		$this->status_pre_emp = '';
		$this->status_transition = '';
		$this->cni_passeport = '';
		$this->signature = '';
		$this->source = '';
		$this->source2 = '';
		$this->source3 = '';
		$this->slug_origine = '';
		$this->origine = '';
		$this->optin1 = '';
		$this->optin2 = '';
		$this->status = '';
		$this->history = '';
		$this->added = '';
		$this->updated = '';
		$this->lastlogin = '';

	}
	
	function get($id,$field='id_client')
	{
		$sql = 'SELECT * FROM  `clients` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		
		if($this->bdd->num_rows()==1)
		{
			$record = $this->bdd->fetch_array($result);
		
				$this->id_client = $record['id_client'];
			$this->hash = $record['hash'];
			$this->id_langue = $record['id_langue'];
			$this->id_partenaire = $record['id_partenaire'];
			$this->id_partenaire_subcode = $record['id_partenaire_subcode'];
			$this->id_facebook = $record['id_facebook'];
			$this->id_linkedin = $record['id_linkedin'];
			$this->id_viadeo = $record['id_viadeo'];
			$this->id_twitter = $record['id_twitter'];
			$this->civilite = $record['civilite'];
			$this->nom = $record['nom'];
			$this->nom_usage = $record['nom_usage'];
			$this->prenom = $record['prenom'];
			$this->slug = $record['slug'];
			$this->fonction = $record['fonction'];
			$this->naissance = $record['naissance'];
			$this->id_pays_naissance = $record['id_pays_naissance'];
			$this->ville_naissance = $record['ville_naissance'];
			$this->id_nationalite = $record['id_nationalite'];
			$this->telephone = $record['telephone'];
			$this->mobile = $record['mobile'];
			$this->email = $record['email'];
			$this->password = $record['password'];
			$this->secrete_question = $record['secrete_question'];
			$this->secrete_reponse = $record['secrete_reponse'];
			$this->type = $record['type'];
			$this->status_depot_dossier = $record['status_depot_dossier'];
			$this->etape_inscription_preteur = $record['etape_inscription_preteur'];
			$this->status_inscription_preteur = $record['status_inscription_preteur'];
			$this->status_pre_emp = $record['status_pre_emp'];
			$this->status_transition = $record['status_transition'];
			$this->cni_passeport = $record['cni_passeport'];
			$this->signature = $record['signature'];
			$this->source = $record['source'];
			$this->source2 = $record['source2'];
			$this->source3 = $record['source3'];
			$this->slug_origine = $record['slug_origine'];
			$this->origine = $record['origine'];
			$this->optin1 = $record['optin1'];
			$this->optin2 = $record['optin2'];
			$this->status = $record['status'];
			$this->history = $record['history'];
			$this->added = $record['added'];
			$this->updated = $record['updated'];
			$this->lastlogin = $record['lastlogin'];

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
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->hash = $this->bdd->escape_string($this->hash);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->id_facebook = $this->bdd->escape_string($this->id_facebook);
		$this->id_linkedin = $this->bdd->escape_string($this->id_linkedin);
		$this->id_viadeo = $this->bdd->escape_string($this->id_viadeo);
		$this->id_twitter = $this->bdd->escape_string($this->id_twitter);
		$this->civilite = $this->bdd->escape_string($this->civilite);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->nom_usage = $this->bdd->escape_string($this->nom_usage);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->fonction = $this->bdd->escape_string($this->fonction);
		$this->naissance = $this->bdd->escape_string($this->naissance);
		$this->id_pays_naissance = $this->bdd->escape_string($this->id_pays_naissance);
		$this->ville_naissance = $this->bdd->escape_string($this->ville_naissance);
		$this->id_nationalite = $this->bdd->escape_string($this->id_nationalite);
		$this->telephone = $this->bdd->escape_string($this->telephone);
		$this->mobile = $this->bdd->escape_string($this->mobile);
		$this->email = $this->bdd->escape_string($this->email);
		$this->password = $this->bdd->escape_string($this->password);
		$this->secrete_question = $this->bdd->escape_string($this->secrete_question);
		$this->secrete_reponse = $this->bdd->escape_string($this->secrete_reponse);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status_depot_dossier = $this->bdd->escape_string($this->status_depot_dossier);
		$this->etape_inscription_preteur = $this->bdd->escape_string($this->etape_inscription_preteur);
		$this->status_inscription_preteur = $this->bdd->escape_string($this->status_inscription_preteur);
		$this->status_pre_emp = $this->bdd->escape_string($this->status_pre_emp);
		$this->status_transition = $this->bdd->escape_string($this->status_transition);
		$this->cni_passeport = $this->bdd->escape_string($this->cni_passeport);
		$this->signature = $this->bdd->escape_string($this->signature);
		$this->source = $this->bdd->escape_string($this->source);
		$this->source2 = $this->bdd->escape_string($this->source2);
		$this->source3 = $this->bdd->escape_string($this->source3);
		$this->slug_origine = $this->bdd->escape_string($this->slug_origine);
		$this->origine = $this->bdd->escape_string($this->origine);
		$this->optin1 = $this->bdd->escape_string($this->optin1);
		$this->optin2 = $this->bdd->escape_string($this->optin2);
		$this->status = $this->bdd->escape_string($this->status);
		$this->history = $this->bdd->escape_string($this->history);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->lastlogin = $this->bdd->escape_string($this->lastlogin);

		
		$sql = 'UPDATE `clients` SET `hash`="'.$this->hash.'",`id_langue`="'.$this->id_langue.'",`id_partenaire`="'.$this->id_partenaire.'",`id_partenaire_subcode`="'.$this->id_partenaire_subcode.'",`id_facebook`="'.$this->id_facebook.'",`id_linkedin`="'.$this->id_linkedin.'",`id_viadeo`="'.$this->id_viadeo.'",`id_twitter`="'.$this->id_twitter.'",`civilite`="'.$this->civilite.'",`nom`="'.$this->nom.'",`nom_usage`="'.$this->nom_usage.'",`prenom`="'.$this->prenom.'",`slug`="'.$this->slug.'",`fonction`="'.$this->fonction.'",`naissance`="'.$this->naissance.'",`id_pays_naissance`="'.$this->id_pays_naissance.'",`ville_naissance`="'.$this->ville_naissance.'",`id_nationalite`="'.$this->id_nationalite.'",`telephone`="'.$this->telephone.'",`mobile`="'.$this->mobile.'",`email`="'.$this->email.'",`password`="'.$this->password.'",`secrete_question`="'.$this->secrete_question.'",`secrete_reponse`="'.$this->secrete_reponse.'",`type`="'.$this->type.'",`status_depot_dossier`="'.$this->status_depot_dossier.'",`etape_inscription_preteur`="'.$this->etape_inscription_preteur.'",`status_inscription_preteur`="'.$this->status_inscription_preteur.'",`status_pre_emp`="'.$this->status_pre_emp.'",`status_transition`="'.$this->status_transition.'",`cni_passeport`="'.$this->cni_passeport.'",`signature`="'.$this->signature.'",`source`="'.$this->source.'",`source2`="'.$this->source2.'",`source3`="'.$this->source3.'",`slug_origine`="'.$this->slug_origine.'",`origine`="'.$this->origine.'",`optin1`="'.$this->optin1.'",`optin2`="'.$this->optin2.'",`status`="'.$this->status.'",`history`="'.$this->history.'",`added`="'.$this->added.'",`updated`=NOW(),`lastlogin`="'.$this->lastlogin.'" WHERE id_client="'.$this->id_client.'"';
		$this->bdd->query($sql);
		
		if($cs=='')
		{
	$this->bdd->controlSlug('clients',$this->slug,'id_client',$this->id_client);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('clients',$this->slug,$this->id_client,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_client,'id_client');
	}
	
	function delete($id,$field='id_client')
	{
		if($id=='')
			$id = $this->id_client;
		$sql = 'DELETE FROM `clients` WHERE '.$field.'="'.$id.'"';
		$this->bdd->query($sql);
	}
	
	function create($cs='')
	{
		$this->id_client = $this->bdd->escape_string($this->id_client);
		$this->hash = $this->bdd->escape_string($this->hash);
		$this->id_langue = $this->bdd->escape_string($this->id_langue);
		$this->id_partenaire = $this->bdd->escape_string($this->id_partenaire);
		$this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
		$this->id_facebook = $this->bdd->escape_string($this->id_facebook);
		$this->id_linkedin = $this->bdd->escape_string($this->id_linkedin);
		$this->id_viadeo = $this->bdd->escape_string($this->id_viadeo);
		$this->id_twitter = $this->bdd->escape_string($this->id_twitter);
		$this->civilite = $this->bdd->escape_string($this->civilite);
		$this->nom = $this->bdd->escape_string($this->nom);
		$this->nom_usage = $this->bdd->escape_string($this->nom_usage);
		$this->prenom = $this->bdd->escape_string($this->prenom);
		$this->slug = $this->bdd->escape_string($this->slug);
		$this->fonction = $this->bdd->escape_string($this->fonction);
		$this->naissance = $this->bdd->escape_string($this->naissance);
		$this->id_pays_naissance = $this->bdd->escape_string($this->id_pays_naissance);
		$this->ville_naissance = $this->bdd->escape_string($this->ville_naissance);
		$this->id_nationalite = $this->bdd->escape_string($this->id_nationalite);
		$this->telephone = $this->bdd->escape_string($this->telephone);
		$this->mobile = $this->bdd->escape_string($this->mobile);
		$this->email = $this->bdd->escape_string($this->email);
		$this->password = $this->bdd->escape_string($this->password);
		$this->secrete_question = $this->bdd->escape_string($this->secrete_question);
		$this->secrete_reponse = $this->bdd->escape_string($this->secrete_reponse);
		$this->type = $this->bdd->escape_string($this->type);
		$this->status_depot_dossier = $this->bdd->escape_string($this->status_depot_dossier);
		$this->etape_inscription_preteur = $this->bdd->escape_string($this->etape_inscription_preteur);
		$this->status_inscription_preteur = $this->bdd->escape_string($this->status_inscription_preteur);
		$this->status_pre_emp = $this->bdd->escape_string($this->status_pre_emp);
		$this->status_transition = $this->bdd->escape_string($this->status_transition);
		$this->cni_passeport = $this->bdd->escape_string($this->cni_passeport);
		$this->signature = $this->bdd->escape_string($this->signature);
		$this->source = $this->bdd->escape_string($this->source);
		$this->source2 = $this->bdd->escape_string($this->source2);
		$this->source3 = $this->bdd->escape_string($this->source3);
		$this->slug_origine = $this->bdd->escape_string($this->slug_origine);
		$this->origine = $this->bdd->escape_string($this->origine);
		$this->optin1 = $this->bdd->escape_string($this->optin1);
		$this->optin2 = $this->bdd->escape_string($this->optin2);
		$this->status = $this->bdd->escape_string($this->status);
		$this->history = $this->bdd->escape_string($this->history);
		$this->added = $this->bdd->escape_string($this->added);
		$this->updated = $this->bdd->escape_string($this->updated);
		$this->lastlogin = $this->bdd->escape_string($this->lastlogin);

		
		$sql = 'INSERT INTO `clients`(`hash`,`id_langue`,`id_partenaire`,`id_partenaire_subcode`,`id_facebook`,`id_linkedin`,`id_viadeo`,`id_twitter`,`civilite`,`nom`,`nom_usage`,`prenom`,`slug`,`fonction`,`naissance`,`id_pays_naissance`,`ville_naissance`,`id_nationalite`,`telephone`,`mobile`,`email`,`password`,`secrete_question`,`secrete_reponse`,`type`,`status_depot_dossier`,`etape_inscription_preteur`,`status_inscription_preteur`,`status_pre_emp`,`status_transition`,`cni_passeport`,`signature`,`source`,`source2`,`source3`,`slug_origine`,`origine`,`optin1`,`optin2`,`status`,`history`,`added`,`updated`,`lastlogin`) VALUES(md5(UUID()),"'.$this->id_langue.'","'.$this->id_partenaire.'","'.$this->id_partenaire_subcode.'","'.$this->id_facebook.'","'.$this->id_linkedin.'","'.$this->id_viadeo.'","'.$this->id_twitter.'","'.$this->civilite.'","'.$this->nom.'","'.$this->nom_usage.'","'.$this->prenom.'","'.$this->slug.'","'.$this->fonction.'","'.$this->naissance.'","'.$this->id_pays_naissance.'","'.$this->ville_naissance.'","'.$this->id_nationalite.'","'.$this->telephone.'","'.$this->mobile.'","'.$this->email.'","'.$this->password.'","'.$this->secrete_question.'","'.$this->secrete_reponse.'","'.$this->type.'","'.$this->status_depot_dossier.'","'.$this->etape_inscription_preteur.'","'.$this->status_inscription_preteur.'","'.$this->status_pre_emp.'","'.$this->status_transition.'","'.$this->cni_passeport.'","'.$this->signature.'","'.$this->source.'","'.$this->source2.'","'.$this->source3.'","'.$this->slug_origine.'","'.$this->origine.'","'.$this->optin1.'","'.$this->optin2.'","'.$this->status.'","'.$this->history.'",NOW(),NOW(),"'.$this->lastlogin.'")';
		$this->bdd->query($sql);
		
		$this->id_client = $this->bdd->insert_id();
		
		if($cs=='')
		{
	$this->bdd->controlSlug('clients',$this->slug,'id_client',$this->id_client);
		}
		else
		{
	$this->bdd->controlSlugMultiLn('clients',$this->slug,$this->id_client,$list_field_value,$this->id_langue);	
		}
		
		$this->get($this->id_client,'id_client');
		
		return $this->id_client;
	}
	
	function unsetData()
	{
		$this->id_client = '';
		$this->hash = '';
		$this->id_langue = '';
		$this->id_partenaire = '';
		$this->id_partenaire_subcode = '';
		$this->id_facebook = '';
		$this->id_linkedin = '';
		$this->id_viadeo = '';
		$this->id_twitter = '';
		$this->civilite = '';
		$this->nom = '';
		$this->nom_usage = '';
		$this->prenom = '';
		$this->slug = '';
		$this->fonction = '';
		$this->naissance = '';
		$this->id_pays_naissance = '';
		$this->ville_naissance = '';
		$this->id_nationalite = '';
		$this->telephone = '';
		$this->mobile = '';
		$this->email = '';
		$this->password = '';
		$this->secrete_question = '';
		$this->secrete_reponse = '';
		$this->type = '';
		$this->status_depot_dossier = '';
		$this->etape_inscription_preteur = '';
		$this->status_inscription_preteur = '';
		$this->status_pre_emp = '';
		$this->status_transition = '';
		$this->cni_passeport = '';
		$this->signature = '';
		$this->source = '';
		$this->source2 = '';
		$this->source3 = '';
		$this->slug_origine = '';
		$this->origine = '';
		$this->optin1 = '';
		$this->optin2 = '';
		$this->status = '';
		$this->history = '';
		$this->added = '';
		$this->updated = '';
		$this->lastlogin = '';

	}
}