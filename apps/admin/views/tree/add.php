<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/tree" title="Edition">Edition</a> -</li>
        <li><a href="<?=$this->lurl?>/tree" title="Arborescence">Arborescence</a> -</li>
        <li>Ajout d'une page</li>
    </ul>
    <?php 
	if(count($this->lLangues) > 1)
	{
	?>   
        <div id="onglets">
            <?
            foreach($this->lLangues as $key => $lng)
            {			
            ?>
                <a onclick="changeOngletLangue('<?=$key?>');" id="lien_<?=$key?>" title="<?=$lng?>" class="<?=($key==$this->language?'active':'')?>"><?=$lng?></a>
            <?
            }
            ?>    	
        </div>
  	<?php
	}
	?>   
    <form method="post" name="add_tree" id="add_tree" enctype="multipart/form-data">
    	<input type="hidden" name="lng_encours" id="lng_encours" value="<?=$this->dLanguage?>" />
        <input type="hidden" name="id_parent" id="id_parent" value="<?=(isset($this->params[0])?$this->params[0]:'')?>" />
        <?
		foreach($this->lLangues as $key => $lng)
		{
		?>
        	<div id="langue_<?=$key?>"<?=($key!=$this->dLanguage?' style="display:none;"':'')?>>
                <fieldset>
                	<div class="gauche">
                    	<h1>Ajout d'une page</h1>
                        <table class="form">
                            <tr>
                                <th><label for="id_parent_<?=$key?>">Rubrique parente :</label></th>
                                <td>
                                    <select name="id_parent_<?=$key?>" id="id_parent_<?=$key?>" onchange="setNewIdParent(this.value);" class="select">
                                        <option value="0">Choisir une rubrique</option>
                                        <?
                                        foreach($this->lTree as $tree)
                                        {
                                            echo '<option value="'.$tree['id_tree'].'"'.(isset($this->params[0]) && $this->params[0] == $tree['id_tree']?' selected="selected"':'').'>'.$tree['title'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="id_template_<?=$key?>">Template :</label></th>
                                <td>
                                    <select name="id_template_<?=$key?>" id="id_template_<?=$key?>" class="select">
                                        <option value="0">Choisir un template</option>
                                        <?
                                        foreach($this->lTemplate as $template)
                                        {
                                            echo '<option value="'.$template['id_template'].'">'.$template['name'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="title_<?=$key?>">Titre de la page :</label></th>
                                <td><input type="text" name="title_<?=$key?>" id="title_<?=$key?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="menu_title_<?=$key?>">Titre du menu :</label></th>
                                <td><input type="text" name="menu_title_<?=$key?>" id="menu_title_<?=$key?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="slug_<?=$key?>">Lien permanent :</label></th>
                                <td><input type="text" name="slug_<?=$key?>" id="slug_<?=$key?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="img_menu_<?=$key?>">Image menu :</label></th>
                                <td><input type="file" name="img_menu_<?=$key?>" id="img_menu_<?=$key?>" /></td>
                            </tr>
                        </table>
                        <br /><br />
                        <h1>Statuts de la page</h1>
                        <table class="form">
                            <tr>
                                <th><label>Statut de la page :</label></th>
                                <td>
                                    <input type="radio" value="1" id="status1_<?=$key?>" class="radio" name="status_<?=$key?>"<?=($key==$this->dLanguage?' checked="checked"':'')?> />
                                    <label for="status1_<?=$key?>" class="label_radio">En ligne</label>
                                </td>
                                <td>
                                    <input type="radio" value="0" id="status0_<?=$key?>" class="radio" name="status_<?=$key?>"<?=($key!=$this->dLanguage?' checked="checked"':'')?> />
                                    <label for="status0_<?=$key?>" class="label_radio">Hors ligne</label>	
                                </td>
                            </tr>
                            <tr>
                                <th><label>Statut dans la navigation :</label></th>
                                <td>
                                    <input type="radio" value="1" id="status_menu1_<?=$key?>" name="status_menu_<?=$key?>" class="radio" checked="checked" />
                                    <label for="status_menu1_<?=$key?>" class="label_radio">Visible</label>
                                </td>
                                <td>
                                    <input type="radio" value="0" id="status_menu0_<?=$key?>" name="status_menu_<?=$key?>" class="radio" />
                                    <label for="status_menu0_<?=$key?>" class="label_radio">Invisible</label>	
                                </td>
                            </tr>
                            <tr>    
                                <th><label>Visibilit&eacute; de la page :</label></th>
                                <td width="80px">
                                    <input type="radio" value="1" id="prive1_<?=$key?>" name="prive_<?=$key?>" class="radio" />
                                    <label for="prive1_<?=$key?>" class="label_radio">Priv&eacute;e</label>
                                </td>
                                <td width="170px">
                                    <input type="radio" value="0" id="prive0_<?=$key?>" name="prive_<?=$key?>" class="radio" checked="checked" />
                                    <label for="prive0_<?=$key?>" class="label_radio">Publique</label>	
                                </td>
                            </tr>
                        </table>
                  	</div>
                    <div class="droite">
                        <h1>El&eacute;ments de r&eacute;f&eacute;rencement</h1>
                        <table class="form">
                            <tr>
                                <th><label for="meta_title_<?=$key?>">Titre de la page :</label></th>
                                <td><input type="text" name="meta_title_<?=$key?>" id="meta_title_<?=$key?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="meta_description_<?=$key?>">Description :</label></th>
                                <td><textarea name="meta_description_<?=$key?>" id="meta_description_<?=$key?>" class="textarea"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="meta_keywords_<?=$key?>">Mots cl&eacute;s :</label></th>
                                <td><textarea name="meta_keywords_<?=$key?>" id="meta_keywords_<?=$key?>" class="textarea"></textarea></td>
                            </tr>
                            <tr>    
                                <th><label>Indexation de la page :</label></th>
                                <td>
                                    <input type="radio" value="1" id="indexation1_<?=$key?>" name="indexation_<?=$key?>" class="radio" checked="checked" />
                                    <label for="indexation1_<?=$key?>" class="label_radio">Oui</label>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <input type="radio" value="0" id="indexation0_<?=$key?>" name="indexation_<?=$key?>" class="radio" />
                                    <label for="indexation0_<?=$key?>" class="label_radio">Non</label>
                                </td>
                            </tr>
                        </table>
                   	</div>                    
                </fieldset>
       		</div>
        <?	
		}
		?>
        <table class="large">
            <tr>
                <td>
                    <input type="hidden" name="form_add_tree" id="form_add_tree" />
                    <input type="submit" value="Valider l'ajout de la page" name="send_tree" id="send_tree" class="btn" />
                </td>
            </tr>
        </table>        
    </form>
</div>