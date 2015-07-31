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

class produits extends produits_crud
{

	function produits($bdd,$params='')
    {
        parent::produits($bdd,$params);
    }
    
    function get($id,$field='id_produit')
    {
        return parent::get($id,$field);
    }
    
    function update($cs='')
    {
        parent::update($cs);
    }
    
    function delete($id,$field='id_produit')
    {
    	parent::delete($id,$field);
    }
    
    function create($cs='')
    {
        $id = parent::create($cs);
        return $id;
    }
	
	function select($where='',$order='',$start='',$nb='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
		if($order != '')
			$order = ' ORDER BY '.$order;
		$sql = 'SELECT * FROM `produits`'.$where.$order.($nb!='' && $start !=''?' LIMIT '.$start.','.$nb:($nb!=''?' LIMIT '.$nb:''));

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	} 
	
	function counter($where='')
	{
		if($where != '')
			$where = ' WHERE '.$where;
			
		$sql='SELECT count(*) FROM `produits` '.$where;

		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0));
	}
	
	function exist($id,$field='id_produit')
	{
		$sql = 'SELECT * FROM `produits` WHERE '.$field.'="'.$id.'"';
		$result = $this->bdd->query($sql);
		return ($this->bdd->fetch_array($result,0,0)>0);
	}
	
	//******************************************************************************************//
	//**************************************** AJOUTS ******************************************//
	//******************************************************************************************//
	
	// Construction de l'arbo pour un select
	function listChilds($id_parent,$indent,$tableau,$id_langue='fr')
	{
		if($indent != '')
		{
			$indent .= '---';
		}
			
		$sql = 'SELECT * FROM tree WHERE id_parent = '.$id_parent.' AND id_langue = "'.$id_langue.'" ORDER BY ordre ASC ';
		$result = $this->bdd->query($sql);
	
		while($record = $this->bdd->fetch_assoc($result))
		{
			$tableau[]= array('id_tree'=>$record['id_tree'],'title'=>$indent.$record['menu_title'],'id_parent'=>$id_parent,'slug'=>$record['slug']);
			$tableau = $this->listChilds($record['id_tree'],$indent,$tableau,$id_langue);
		}
		
		return $tableau;
	}
	
	// Recuperation de la liste des produits
	function selectProducts($langue='fr',$order='nom_produit')
	{
		if($order != '')
			$order = ' ORDER BY '.$order;
			
		$sql = 'SELECT produits.*, produits_elements.value AS nom_produit 
				FROM `produits` 
				JOIN elements ON (elements.id_template > 0 AND elements.id_template = produits.id_template AND elements.ordre = 3) 
				JOIN produits_elements ON (elements.id_element = produits_elements.id_element AND produits.id_produit = produits_elements.id_produit) 
				WHERE produits_elements.id_langue = "'.$langue.'" '.$order; 
		
		$res = $this->bdd->query($sql);
		$result = array();
		
		while($rec = $this->bdd->fetch_array($res))
		{
			$result[] = $rec;
		}
		
		return $result;
	}
	
	// Control du slug unique pour les produits des elment produits
	function controlSlugProduit($slug,$id_produit,$langue='fr')
	{
		$sql = 'SELECT * FROM produits_elements WHERE complement = "'.$slug.'" AND id_langue = "'.$langue.'"';
		$res = $this->bdd->query($sql);
        
        if($this->bdd->num_rows($res) >= 1 || $slug == "")
        {
			return $slug.'-'.$langue.'-'.$id_produit;
        }
		else
		{
			return $slug;
		}
	}
	
	// Definition des types d'éléments
	public $typesElements = array('Nom Produit','Texte','Textearea','Texteditor','Lien Interne','Lien Externe','Produit','Lien Interne ou Produit','Lien Interne ou Produit ou Lien Externe','Image','Fichier','Fichier Protected','Date','Checkbox');
	
	// Affichage des elements de formulaire en fonction du type d'élément
	function displayFormElement($id_produit=0,$element,$langue='fr')
	{
		// Remize a zero de l'objet
		$this->params['produits_elements']->unsetData();			
		
		// Recuperation de la valeur de l'element pour la page
		$this->params['produits_elements']->get($element['id_element'],'id_produit = '.$id_produit.' AND id_langue = "'.$langue.'" AND id_element');
				
		// Construction des differents elements
		switch($element['type_element'])
		{
			case 'Nom Produit':	
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<td>
						<input class="input_big" type="text" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" value="'.$this->params['produits_elements']->value.'" />
					</td>
				</tr>';				
			break;
			
			case 'Texte':	
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<td>
						<input class="input_big" type="text" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" value="'.$this->params['produits_elements']->value.'" />
					</td>
				</tr>';				
			break;
			
			case 'Textearea':	
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<td>
						<textarea class="textarea_large" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'">'.$this->params['produits_elements']->value.'</textarea>
					</td>
				</tr>';				
			break;
			
			case 'Texteditor':	
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<td>
						<textarea class="textarea_large" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'">'.$this->params['produits_elements']->value.'</textarea>
						<script type="text/javascript">var cked = CKEDITOR.replace(\''.$element['slug'].'_'.$langue.'\');</script>
					</td>
				</tr>';			
			break;
			
			case 'Lien Interne':
				echo '
				<tr>
					<th class="bas">
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
						<select name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" class="select">';
						foreach($this->listChilds(0,'-',array()) as $tree)
						{
							echo '<option value="'.$tree['id_tree'].'"'.($this->params['produits_elements']->value == $tree['id_tree']?' selected="selected"':'').'>'.$tree['title'].'</option>';
						}
						echo '
						</select>	
					</th>
				</tr>';			
			break;
			
			case 'Lien Externe':
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<td>
						<input class="input_big" type="text" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" value="'.$this->params['produits_elements']->value.'" />
					</td>
				</tr>';
			break;
			
			case 'Produit':
				echo '
				<tr>
					<th class="bas">
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
						<select name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" class="select">';
						foreach($this->selectProducts($langue) as $prod)
						{
							echo '<option value="'.$prod['id_produit'].'"'.($this->params['produits_elements']->value == $prod['id_produit']?' selected="selected"':'').'>'.$prod['nom_produit'].'</option>';
						}
						echo '
						</select>	
					</th>
				</tr>';			
			break;
			
			case 'Lien Interne ou Produit':
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<th class="bas">
						<select name="L-'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" class="select">
							<option value="">Lien vers une page du site</option>';
							foreach($this->listChilds(0,'-',array()) as $tree)
							{
								echo '<option value="'.$tree['id_tree'].'"'.(($this->params['produits_elements']->value == $tree['id_tree'] && $this->params['produits_elements']->complement == 'L')?' selected="selected"':'').'>'.$tree['title'].'</option>';
							}
							echo '
						</select>	
						&nbsp;&nbsp;ou&nbsp;&nbsp;
						<select name="P-'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" class="select">
							<option value="">Lien vers un produit</option>';
							foreach($this->selectProducts($langue) as $prod)
							{
								echo '<option value="'.$prod['id_produit'].'"'.(($this->params['produits_elements']->value==$prod['id_produit'] && $this->params['produits_elements']->complement == 'P')?' selected="selected"':'').'>'.$prod['nom_produit'].'</option>';
							}
							echo '
						</select>
					</th>
				</tr>';			
			break;
			
			case 'Lien Interne ou Produit ou Lien Externe':
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<th class="bas">
						<select name="L-'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" class="select">
							<option value="">Lien vers une page</option>';
							foreach($this->listChilds(0,'-',array()) as $tree)
							{
								echo '<option value="'.$tree['id_tree'].'"'.(($this->params['produits_elements']->value == $tree['id_tree'] && $this->params['produits_elements']->complement == 'L')?' selected="selected"':'').'>'.$tree['title'].'</option>';
							}
							echo '
						</select>
						&nbsp;&nbsp;ou&nbsp;&nbsp;	
						<select name="P-'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" class="select">
							<option value="">Lien vers un produit</option>';
							foreach($this->selectProducts($langue) as $prod)
							{
								echo '<option value="'.$prod['id_produit'].'"'.(($this->params['produits_elements']->value==$prod['id_produit'] && $this->params['produits_elements']->complement == 'P')?' selected="selected"':'').'>'.$prod['nom_produit'].'</option>';
							}
							echo '
						</select>
						&nbsp;&nbsp;ou un lien externe :&nbsp;&nbsp;
						<input class="input_large" type="text" name="LX-'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" value="'.($this->params['produits_elements']->complement == 'LX'?$this->params['produits_elements']->value:'').'" />
					</th>
				</tr>';			
			break;
			
			case 'Image':
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<th class="bas">
						<input type="file" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" />
						<input type="hidden" name="'.$element['slug'].'_'.$langue.'-old" id="'.$element['slug'].'_'.$langue.'-old" value="'.$this->params['produits_elements']->value.'" />
						&nbsp;&nbsp;<label for="nom_'.$element['slug'].'_'.$langue.'">Nom du fichier image :</label>
						<input class="input_large" type="text" name="nom_'.$element['slug'].'_'.$langue.'" id="nom_'.$element['slug'].'_'.$langue.'" value="'.$this->params['produits_elements']->complement.'" />
					</th>
				</tr>
				<tr id="deleteImageElement'.$this->params['produits_elements']->id.'">';
					if($this->params['produits_elements']->value != '')
					{
						if(substr(strtolower(strrchr(basename($this->params['produits_elements']->value),'.')),1) == 'swf')
						{
							echo '
							<th class="bas">
								<object type="application/x-shockwave-flash" data="'.$this->params['surl'].'/var/images/'.$this->params['produits_elements']->value.'" width="400" height="180" style="vertical-align:middle;">
									<param name="src" value="'.$this->params['surl'].'/var/images/'.$this->params['produits_elements']->value.'" />
									<param name="movie" value="'.$this->params['surl'].'/var/images/'.$this->params['produits_elements']->value.'" />
									<param name="quality" value="high" />
									<param name="bgcolor" value="#fff" />
									<param name="play" value="true" />
									<param name="loop" value="true" />
									<param name="scale" value="showall" />
									<param name="menu" value="true" />
									<param name="align" value="middle" />
									<param name="wmode" value="transparent" />
									<param name="pluginspage" value="http://www.macromedia.com/go/getflashplayer" />
									<param name="type" value="application/x-shockwave-flash" />
								</object>
								&nbsp;&nbsp; Supprimer le flash&nbsp;&nbsp;
								<a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce flash ?\')){deleteImageElement('.$this->params['produits_elements']->id.',\''.$element['slug'].'_'.$langue.'\');return false;}">
									<img src="'.$this->params['surl'].'/images/admin/delete.png" alt="Supprimer" style="vertical-align:middle;" />
								</a>
							</th>';	
						}
						else
						{
							list($width,$height) = @getimagesize($this->params['spath'].'images/'.$this->params['produits_elements']->value);								
							echo '
							<th class="bas">
								<a href="'.$this->params['surl'].'/var/images/'.$this->params['produits_elements']->value.'" class="thickbox">
									<img src="'.$this->params['surl'].'/var/images/'.$this->params['produits_elements']->value.'" alt="'.$element['name'].'"'.($height > 180?' height="180"':($width > 400?' width="400"':'')).' style="vertical-align:middle;" />
								</a>
								&nbsp;&nbsp; Supprimer l\'image&nbsp;&nbsp;
								<a onclick="if(confirm(\'Etes vous sur de vouloir supprimer cette image ?\')){deleteImageElement('.$this->params['produits_elements']->id.',\''.$element['slug'].'_'.$langue.'\');return false;}">
									<img src="'.$this->params['surl'].'/images/admin/delete.png" alt="Supprimer" style="vertical-align:middle;" />
								</a>
							</th>';	
						}
					}
					else
					{
						echo '
						<td>&nbsp;</td>';
					}
				echo '
				</tr>';
			break;
			
			case 'Fichier':	
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<th colspan="2" class="bas">
						<input type="file" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" />
						<input type="hidden" name="'.$element['slug'].'_'.$langue.'-old" id="'.$element['slug'].'_'.$langue.'-old" value="'.$this->params['produits_elements']->value.'" />	
						&nbsp;&nbsp;<label for="nom_'.$element['slug'].'_'.$langue.'">Nom du fichier :</label>
						<input class="input_large" type="text" name="nom_'.$element['slug'].'_'.$langue.'" id="nom_'.$element['slug'].'_'.$langue.'" value="'.$this->params['produits_elements']->complement.'" />
					</th>
				</tr>
				<tr id="deleteFichierElement'.$this->params['produits_elements']->id.'">';
					if($this->params['produits_elements']->value != '')
					{
						echo '
						<th class="bas">
							<label>Fichier actuel</label> : 
							<a href="'.$this->params['surl'].'/var/fichiers/'.$this->params['produits_elements']->value.'" target="blank">'.$this->params['surl'].'/var/fichiers/'.$this->params['produits_elements']->value.'</a> 
							&nbsp;&nbsp;
							<a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce fichier ?\')){deleteFichierElement('.$this->params['produits_elements']->id.',\''.$element['slug'].'_'.$langue.'\');return false;}">
								<img src="'.$this->params['surl'].'/images/admin/delete.png" alt="Supprimer" />
							</a>
						</th>';	
					}
					else
					{
						echo '
						<td>&nbsp;</td>';
					}
				echo '
				</tr>';
			break;
			
			case 'Fichier Protected':	
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<th class="bas">
						<input type="file" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" />
						<input type="hidden" name="'.$element['slug'].'_'.$langue.'-old" id="'.$element['slug'].'_'.$langue.'-old" value="'.$this->params['produits_elements']->value.'" />	
						&nbsp;&nbsp;<label for="nom_'.$element['slug'].'_'.$langue.'">Nom du fichier :</label>
						<input class="input_large" type="text" name="nom_'.$element['slug'].'_'.$langue.'" id="nom_'.$element['slug'].'_'.$langue.'" value="'.$this->params['produits_elements']->complement.'" />
					</th>
				</tr>
				<tr id="deleteFichierProtectedElement'.$this->params['produits_elements']->id.'">';
					if($this->params['produits_elements']->value != '')
					{
						echo '
						<th class="bas">
							<label>Fichier actuel</label> : 
							<a href="'.$this->params['url'].'/protected/templates/'.$this->params['produits_elements']->value.'" target="blank">'.$this->params['produits_elements']->value.'</a> 
							&nbsp;&nbsp;
							<a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce fichier ?\')){deleteFichierProtectedElement('.$this->params['produits_elements']->id.',\''.$element['slug'].'_'.$langue.'\');return false;}">
								<img src="'.$this->params['surl'].'/images/admin/delete.png" alt="Supprimer" />
							</a>
						</th>';	
					}
					else
					{
						echo '
						<td>&nbsp;</td>';
					}
				echo '
				</tr>';
			break;
			
			case 'Date':
				echo '
				<tr>
					<th>
						<label for="datepik_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<th class="bas">
						<input class="input_dp" type="text" name="'.$element['slug'].'_'.$langue.'" id="datepik_'.$langue.'" value="'.$this->params['produits_elements']->value.'" />
					</th>
				</tr>';				
			break;
			
			case 'Checkbox':
				echo '
				<tr>
					<th class="bas">
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].'</label> : 
						<input type="checkbox" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" value="1"'.($this->params['produits_elements']->value == 1?' checked="checked"':'').' />
					</th>
				</tr>';				
			break;
			
			default:
				echo '
				<tr>
					<th>
						<label for="'.$element['slug'].'_'.$langue.'">'.$element['name'].' :</label>
					</th>
				</tr>
				<tr>
					<td>
						<input class="input_big" type="text" name="'.$element['slug'].'_'.$langue.'" id="'.$element['slug'].'_'.$langue.'" value="'.$this->params['produits_elements']->value.'" />
					</td>
				</tr>';	
			break;
		}	
	}
	
	// Traitement du formulaire des elements en fonction du type d'element
	function handleFormElement($id_produit,$element,$langue='fr')
	{
		// Traitement des differents elements
		switch($element['type_element'])
		{
			case 'Nom Produit':
				$this->params['produits_elements']->id_produit = $id_produit;
				$this->params['produits_elements']->id_element = $element['id_element'];
				$this->params['produits_elements']->id_langue = $langue;
				$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue];
				$this->params['produits_elements']->complement = '';
				$this->params['produits_elements']->status = 1;
				$this->params['produits_elements']->id = $this->params['produits_elements']->create();
				$this->params['produits_elements']->complement = $this->controlSlugProduit($this->bdd->generateSlug($this->params['produits_elements']->value),$this->params['produits_elements']->id_produit,$langue);
				$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue];
				$this->params['produits_elements']->update();
			break;
			
			case 'Image':					
				if(isset($_FILES[$element['slug'].'_'.$langue]) && $_FILES[$element['slug'].'_'.$langue]['name'] != '')
				{
					if($_POST['nom_'.$element['slug'].'_'.$langue] != '')
					{
						$this->nom_fichier = $this->bdd->generateSlug($_POST['nom_'.$element['slug'].'_'.$langue]);
					}
					else
					{
						$this->nom_fichier = '';
					}
					
					$this->params['upload']->setUploadDir($this->params['spath'],'images/');
					
					if($this->params['upload']->doUpload($element['slug'].'_'.$langue,$this->nom_fichier))
					{
						$_POST[$element['slug'].'_'.$langue] = $this->params['upload']->getName();
						$this->params['produits_elements']->id_produit = $id_produit;
						$this->params['produits_elements']->id_element = $element['id_element'];
						$this->params['produits_elements']->id_langue = $langue;
						$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue];
						$this->params['produits_elements']->complement = $_POST['nom_'.$element['slug'].'_'.$langue];
						$this->params['produits_elements']->status = 1;
						$this->params['produits_elements']->create();
					}
					else
					{
						$this->params['produits_elements']->id_produit = $id_produit;
						$this->params['produits_elements']->id_element = $element['id_element'];
						$this->params['produits_elements']->id_langue = $langue;
						$this->params['produits_elements']->value = '';
						$this->params['produits_elements']->complement = '';
						$this->params['produits_elements']->status = 1;
						$this->params['produits_elements']->create();
					}
				}
				else
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue.'-old'];
					$this->params['produits_elements']->complement = $_POST['nom_'.$element['slug'].'_'.$langue];
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();								
				}					
			break;
			
			case 'Fichier':					
				if(isset($_FILES[$element['slug'].'_'.$langue]) && $_FILES[$element['slug'].'_'.$langue]['name'] != '')
				{
					if($_POST['nom_'.$element['slug'].'_'.$langue] != '')
					{
						$this->nom_fichier = $this->bdd->generateSlug($_POST['nom_'.$element['slug'].'_'.$langue]);
					}
					else
					{
						$this->nom_fichier = '';
					}
					
					$this->params['upload']->setUploadDir($this->params['spath'],'fichiers/');
					
					if($this->params['upload']->doUpload($element['slug'].'_'.$langue,$this->nom_fichier))
					{
						$_POST[$element['slug'].'_'.$langue] = $this->params['upload']->getName();
						$this->params['produits_elements']->id_produit = $id_produit;
						$this->params['produits_elements']->id_element = $element['id_element'];
						$this->params['produits_elements']->id_langue = $langue;
						$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue];
						$this->params['produits_elements']->complement = $_POST['nom_'.$element['slug'].'_'.$langue];
						$this->params['produits_elements']->status = 1;
						$this->params['produits_elements']->create();
					}
					else
					{
						$this->params['produits_elements']->id_produit = $id_produit;
						$this->params['produits_elements']->id_element = $element['id_element'];
						$this->params['produits_elements']->id_langue = $langue;
						$this->params['produits_elements']->value = '';
						$this->params['produits_elements']->complement = '';
						$this->params['produits_elements']->status = 1;
						$this->params['produits_elements']->create();
					}
				}
				else
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue.'-old'];
					$this->params['produits_elements']->complement = $_POST['nom_'.$element['slug'].'_'.$langue];
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();								
				}					
			break;
			
			case 'Fichier Protected':					
				if(isset($_FILES[$element['slug'].'_'.$langue]) && $_FILES[$element['slug'].'_'.$langue]['name'] != '')
				{
					if($_POST['nom_'.$element['slug'].'_'.$langue] != '')
					{
						$this->nom_fichier = $this->bdd->generateSlug($_POST['nom_'.$element['slug'].'_'.$langue]);
					}
					else
					{
						$this->nom_fichier = '';
					}
					
					$this->params['upload']->setUploadDir($this->params['path'],'protected/templates/');
					
					if($this->params['upload']->doUpload($element['slug'].'_'.$langue,$this->nom_fichier))
					{
						$_POST[$element['slug'].'_'.$langue] = $this->params['upload']->getName();
						$this->params['produits_elements']->id_produit = $id_produit;
						$this->params['produits_elements']->id_element = $element['id_element'];
						$this->params['produits_elements']->id_langue = $langue;
						$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue];
						$this->params['produits_elements']->complement = $_POST['nom_'.$element['slug'].'_'.$langue];
						$this->params['produits_elements']->status = 1;
						$this->params['produits_elements']->create();
					}
					else
					{
						$this->params['produits_elements']->id_produit = $id_produit;
						$this->params['produits_elements']->id_element = $element['id_element'];
						$this->params['produits_elements']->id_langue = $langue;
						$this->params['produits_elements']->value = '';
						$this->params['produits_elements']->complement = '';
						$this->params['produits_elements']->status = 1;
						$this->params['produits_elements']->create();
					}
				}
				else
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue.'-old'];
					$this->params['produits_elements']->complement = $_POST['nom_'.$element['slug'].'_'.$langue];
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();								
				}					
			break;
			
			case 'Lien Interne ou Produit':					
				if($_POST['P-'.$element['slug'].'_'.$langue] != '')
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = $_POST['P-'.$element['slug'].'_'.$langue];
					$this->params['produits_elements']->complement = 'P';
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();
				}
				elseif($_POST['L-'.$element['slug'].'_'.$langue] != '')
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = $_POST['L-'.$element['slug'].'_'.$langue];
					$this->params['produits_elements']->complement = 'L';
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();
				}
				else
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = '';
					$this->params['produits_elements']->complement = '';
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();
				}					
			break;
			
			case 'Lien Interne ou Produit ou Lien Externe':				
				if($_POST['P-'.$element['slug'].'_'.$langue] != '')
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = $_POST['P-'.$element['slug'].'_'.$langue];
					$this->params['produits_elements']->complement = 'P';
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();
				}
				elseif($_POST['L-'.$element['slug'].'_'.$langue] != '')
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = $_POST['L-'.$element['slug'].'_'.$langue];
					$this->params['produits_elements']->complement = 'L';
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();
				}
				elseif($_POST['LX-'.$element['slug'].'_'.$langue] != '')
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = $_POST['LX-'.$element['slug'].'_'.$langue];
					$this->params['produits_elements']->complement = 'LX';
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();
				}
				else
				{
					$this->params['produits_elements']->id_produit = $id_produit;
					$this->params['produits_elements']->id_element = $element['id_element'];
					$this->params['produits_elements']->id_langue = $langue;
					$this->params['produits_elements']->value = '';
					$this->params['produits_elements']->complement = '';
					$this->params['produits_elements']->status = 1;
					$this->params['produits_elements']->create();
				}									
			break;
			
			default:				
				$this->params['produits_elements']->id_produit = $id_produit;
				$this->params['produits_elements']->id_element = $element['id_element'];
				$this->params['produits_elements']->id_langue = $langue;
				$this->params['produits_elements']->value = $_POST[$element['slug'].'_'.$langue];
				$this->params['produits_elements']->complement = '';
				$this->params['produits_elements']->status = 1;
				$this->params['produits_elements']->create();					
			break;
		}
	}
	
	// Recuperation de la liste des produits (avec prix du details 1)
	function selectListeProduits($id_langue='fr',$nom='',$ref='',$id_brand='',$where='',$order='')
	{
		$sql = '
		SELECT 
			p.id_produit AS id_produit, 
			p.status AS status, 
			p.type AS type, 
			(SELECT pd.reference FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS reference, 
			(SELECT pd.poids FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS poids, 
			(SELECT pd.prix FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS prix, 
			(SELECT pd.prix_promo FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS prix_promo, 
			(SELECT pd.type_detail FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS type_detail, 
			(SELECT pd.detail FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS detail, 
			(SELECT pd.stock FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS stock, 			
			(SELECT t.title FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS categorie, 
			(SELECT t.slug FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS slug_categorie, 			
			(SELECT pi.fichier FROM produits_images pi WHERE pi.id_produit = p.id_produit AND pi.fichier != "" ORDER BY pi.ordre ASC LIMIT 1) AS image, 		
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS nom, 
			(SELECT pe.complement FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS slug, 
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 4) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS desc_courte, 
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 5) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS description,
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 0) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_title, 
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 1) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_description, 
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 2) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_keywords   
		FROM 
			produits p 
		WHERE
			1 = 1'.($where != ''?' AND '.$where:''); 
		
		if($nom != '' && $ref != '')
		{
			$sql .= '	
			AND (SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") LIKE "%'.$nom.'%" 
			OR (SELECT pd.reference FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) LIKE "%'.$ref.'%"';
			
			if($id_brand != '')
			{
				$sql .= ' OR p.id_brand = "%'.$id_brand.'%"';
			}			
		}
		elseif($nom != '' && $ref == '')
		{
			$sql .= '	
			AND (SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") LIKE "%'.$nom.'%" 
			OR (SELECT b.id_brand FROM brands b WHERE b.id_brand = p.id_brand) = "%'.$id_brand.'%"';
			
			if($id_brand != '')
			{
				$sql .= ' OR p.id_brand = "%'.$id_brand.'%"';
			}
		}
		elseif($nom == '' && $ref != '')
		{
			$sql .= ' AND (SELECT pd.reference FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) LIKE "%'.$ref.'%"';
			if($id_brand != '')
			{
				$sql .= ' OR p.id_brand = "%'.$id_brand.'%"';
			}
		}
			
		$sql .= ($order != ''?' ORDER BY '.$order:'');		
		$res = $this->bdd->query($sql);
		$r = array();
		
		while($rec = $this->bdd->fetch_array($res))
		{
			$r[] = $rec;
		}
		
		return $r;
	}
	
	// Recuperation de l'ordre max d'un produit comp pour un produit
	function getMaxOrdreComp($id_produit)
	{
		$sql = 'SELECT ordre FROM produits_crosseling WHERE id_produit = "'.$id_produit.'" ORDER BY ordre DESC LIMIT 1';
		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0)+1);
	}
	
	// Recuperation des infos du  produit
	function getInfosProduit($id_produit,$id_langue='fr')
	{
		$sql = '
		SELECT
			p.id_produit AS id_produit, 
			p.status AS status, 
			p.type AS type, 
			(SELECT pd.reference FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS reference, 
			(SELECT pd.poids FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS poids, 
			(SELECT pd.prix FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS prix, 
			(SELECT pd.prix_promo FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS prix_promo, 
			(SELECT pd.id_detail FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS id_detail, 
			(SELECT pd.type_detail FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS type_detail, 
			(SELECT pd.detail FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS detail, 
			(SELECT pd.stock FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS stock, 			
			(SELECT t.title FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS categorie, 
			(SELECT t.slug FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS slug_categorie, 
			(SELECT pi.fichier FROM produits_images pi WHERE pi.id_produit = p.id_produit AND pi.fichier != "" ORDER BY pi.ordre ASC LIMIT 1) AS image, 		
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS nom, 
			(SELECT pe.complement FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS slug, 
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 4) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS desc_courte, 
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 5) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS description,
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 0) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_title, 
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 1) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_description, 
			(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 2) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_keywords,
			(SELECT pi.fichier FROM produits_images pi WHERE pi.id_produit = p.id_produit AND pi.fichier != "" ORDER BY pi.ordre DESC LIMIT 1) AS imageSmall
		FROM 
			produits p 
		WHERE 
			p.id_produit = "'.$id_produit.'"';
		
		$res = $this->bdd->query($sql);
		$rec = $this->bdd->fetch_array($res);
		return $rec;
	}
	
	// Monter un produit comp
	function moveUp($id_produit,$id_crosseling)
	{
		$position = $this->getPosition($id_produit,$id_crosseling);
		
		$sql = 'UPDATE produits_crosseling SET ordre = ordre + 1 WHERE id_produit = "'.$id_produit.'" AND ordre < '.$position.' ORDER BY ordre DESC LIMIT 1';
		$this->bdd->query($sql);
		
		$sql = 'UPDATE produits_crosseling SET ordre = ordre - 1 WHERE id_produit = "'.$id_produit.'" AND id_crosseling = "'.$id_crosseling.'"';
		$this->bdd->query($sql);
		$this->reordreComp($id_produit);
	}
	
	// Descendre un produit comp
	function moveDown($id_produit,$id_crosseling)
	{
		$position = $this->getPosition($id_produit,$id_crosseling);
		
		$sql = 'UPDATE produits_crosseling SET ordre = ordre - 1 WHERE id_produit = "'.$id_produit.'" AND ordre > '.$position.' ORDER BY ordre ASC LIMIT 1';
		$this->bdd->query($sql);
		
		$sql = 'UPDATE produits_crosseling SET ordre = ordre + 1 WHERE id_produit = "'.$id_produit.'" AND id_crosseling = "'.$id_crosseling.'"';
		$this->bdd->query($sql);
		$this->reordreComp($id_produit);
	}
	
	// Récupération de la position du produit comp
	function getPosition($id_produit,$id_crosseling)
	{
		$sql = 'SELECT ordre FROM produits_crosseling WHERE id_produit = "'.$id_produit.'" AND id_crosseling = "'.$id_crosseling.'"';
		$result = $this->bdd->query($sql);
		
		return (int)($this->bdd->result($result,0,0));
	}
	
	// Reordre des produit comp
	function reordreComp($id_produit)
	{
		$sql = 'SELECT id_crosseling FROM produits_crosseling WHERE id_produit = "'.$id_produit.'" ORDER BY ordre ASC ';
		$result = $this->bdd->query($sql);
		
		$i = 0;
		while($record = $this->bdd->fetch_array($result))
		{
			$sql1 = 'UPDATE produits_crosseling SET ordre = '.$i.' WHERE id_crosseling = "'.$record['id_crosseling'].'"';
			$this->bdd->query($sql1);
			$i++;
		}
	}
	
	// Recuperation de l'ordre max d'une image pour un produit
	function getMaxOrdre($id_produit)
	{
		$sql = 'SELECT ordre FROM produits_images WHERE id_produit = "'.$id_produit.'" ORDER BY ordre DESC LIMIT 1';
		$result = $this->bdd->query($sql);
		return (int)($this->bdd->result($result,0,0)+1);
	}
	
	// Reordre des images
	function reordre($id_produit)
	{
		$sql = 'SELECT id_image FROM produits_images WHERE id_produit = "'.$id_produit.'" ORDER BY ordre ASC ';
		$result = $this->bdd->query($sql);
		
		$i = 1;
		while($record = $this->bdd->fetch_array($result))
		{
			$sql1 = 'UPDATE produits_images SET ordre = '.$i.' WHERE id_image = '.$record['id_image'];
			$this->bdd->query($sql1);
			$i++;
		}
	}
	
	// MAJ Id produit pour les images et produits comp
	function newIDProduit($id_produit,$id_produit_tmp)
	{
		$sql = 'UPDATE produits_images SET id_produit = "'.$id_produit.'" WHERE id_produit = "'.$id_produit_tmp.'"';
		$result = $this->bdd->query($sql);
		
		$sql = 'UPDATE produits_crosseling SET id_produit = "'.$id_produit.'" WHERE id_produit = "'.$id_produit_tmp.'"';
		$result = $this->bdd->query($sql);
	}
	
	// **** FRONT **** //
	
	// On recupere le nombre de vote et la moyenne
	function getMoyenneVotes($id_produit,&$nb_votes)
	{
		$sql = 'SELECT COUNT(*) FROM produits_votes WHERE id_produit = "'.$id_produit.'"';
		$result = $this->bdd->query($sql);
		$nb_votes = (int)($this->bdd->result($result,0,0));
		
		$sql = 'SELECT CEILING(AVG(vote)) AS moy FROM produits_votes WHERE id_produit = "'.$id_produit.'"';
		$result = $this->bdd->query($sql);		
		return (int)($this->bdd->result($result,0,0));
	}
	
	// On regarde si c'est un produit
	function isProduct($slug,$langue='fr',&$id_produit)
	{
		$sql = 'SELECT * FROM produits_elements WHERE complement = "'.$slug.'" AND id_langue = "'.$langue.'"';
		$res = $this->bdd->query($sql);
		$rec = $this->bdd->fetch_array($res);
        
        if($this->bdd->num_rows($res) > 0)
        {
			$id_produit = $rec['id_produit'];
			return true;
        }
		else
		{
			return false;
		}
	}
	
	// Récupération du slug du produit avec la categorie
	function getSlug($id_produit,$id_langue='fr')
	{
		$sql = '
		SELECT
			p.id_produit AS id_produit, 
			(SELECT t.slug FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS slug_categorie, 
			(SELECT pe.complement FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS slug 
		FROM 
			produits p 
		WHERE 
			p.id_produit = "'.$id_produit.'"';
		
		$res = $this->bdd->query($sql);
		$rec = $this->bdd->fetch_array($res);
		
		return $rec['slug'].'/'.$rec['slug_categorie'];
	}
	
	// Recuperation du details d'un produit
	function detailsProduit($id_produit,$id_langue='fr',$status='1')
	{
		$sql = 'SELECT 
					p.id_produit AS id_produit, 
					p.tva AS tva, 
					p.id_brand AS id_brand, 
					p.id_template AS id_template, 
					p.status AS status, 			
					(SELECT t.title FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS categorie, 
					(SELECT t.slug FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS slug_categorie, 			
					(SELECT pi.fichier FROM produits_images pi WHERE pi.id_produit = p.id_produit AND pi.fichier != "" ORDER BY pi.ordre ASC LIMIT 1) AS image, 		
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS nom, 
					(SELECT pe.complement FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS slug, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 4) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS desc_courte, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 5) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS description,
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 0) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_title, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 1) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_description, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 2) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_keywords,
					(SELECT pi.fichier FROM produits_images pi WHERE pi.id_produit = p.id_produit AND pi.fichier != "" ORDER BY pi.ordre DESC LIMIT 1) AS imageSmall
				FROM 
					produits p 
				WHERE 
					p.id_produit = "'.$id_produit.'" 
				AND 
					p.status IN ('.$status.') 
				';

		$resultat = $this->bdd->query($sql);
		$record = $this->bdd->fetch_array($resultat);
		return $record;
	}
	
	// Recuperation des produits complementaires
	function listeProduitsComp($id_produit,$id_langue='fr',$where='',$order='')
	{
		$sql = 'SELECT 
					p.id_produit AS id_produit, 
					p.tva AS tva, 
					p.id_brand AS id_brand, 
					p.id_template AS id_template, 
					p.status AS status, 
					(SELECT pd.reference FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS reference, 
					(SELECT pd.poids FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS poids, 
					(SELECT pd.prix FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS prix, 
					(SELECT pd.prix_promo FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS prix_promo, 
					(SELECT pd.type_detail FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS type_detail, 
					(SELECT pd.detail FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS detail, 
					(SELECT pd.stock FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) AS stock, 			
					(SELECT t.title FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS categorie, 
					(SELECT t.slug FROM tree t JOIN produits_tree pt ON t.id_tree = pt.id_tree WHERE pt.id_produit = p.id_produit AND t.id_langue = "'.$id_langue.'" AND pt.ordre_tree = 1) AS slug_categorie, 			
					(SELECT pi.fichier FROM produits_images pi WHERE pi.id_produit = p.id_produit AND pi.fichier != "" ORDER BY pi.ordre DESC LIMIT 1) AS image, 		
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS nom, 
					(SELECT pe.complement FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS slug, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 4) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS desc_courte, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 5) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS description,
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 0) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_title, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 1) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_description, 
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 2) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$id_langue.'") AS meta_keywords 					
				FROM 
					produits p 
				JOIN 
					produits_crosseling pc 
				ON 
					pc.id_crosseling = p.id_produit 
				WHERE 
					pc.id_produit = "'.$id_produit.'" 
				AND 
					p.status = 1'.($where!=''?' AND '.$where:'').($order!=''?' ORDER BY '.$order:'');

		$resultat = $this->bdd->query($sql);
		$result = array();
		while($record = $this->bdd->fetch_array($resultat))
		{
			$result[] = $record;
		}
		return $result;
	}
	
	// Recuperation des produits d'une gamme
	function listeProduitsGamme($id_categorie,$langue='fr',$where='',$order='')
	{
		$sql = 'SELECT 
					p.id_produit AS id_produit, 
					(SELECT pi.fichier FROM produits_images pi WHERE pi.id_produit = p.id_produit AND pi.fichier != "" ORDER BY pi.ordre DESC LIMIT 1) AS image,
					(SELECT pe.value FROM produits_elements pe JOIN elements e ON (e.id_element = pe.id_element AND e.ordre = 3) WHERE pe.id_produit = p.id_produit AND pe.id_langue = "'.$langue.'") AS nom
				FROM 
					produits p 
				JOIN 
					produits_tree pt 
				ON 
					pt.id_produit = p.id_produit 
				WHERE 
					pt.id_tree = "'.$id_categorie.'" 
				AND 
					(SELECT pd.stock FROM produits_details pd WHERE pd.id_produit = p.id_produit ORDER BY pd.ordre ASC LIMIT 1) >= 0 
				AND 
					p.status = 1'.($where!=''?' AND '.$where:'').($order!=''?' ORDER BY '.$order:'');
		//echo $sql;
		$res = $this->bdd->query($sql);
		
		$r = array();
		
		while($rec = $this->bdd->fetch_array($res))
		{
			$r[] = $rec;
		}
		
		return $r;
	}
}