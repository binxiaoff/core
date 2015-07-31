<script type="text/javascript">
	function selectTypeLienMenu(type)
	{
		if(type == "L")
		{
			<?
			foreach($this->lLangues as $key => $lng)
			{
			?>
				document.getElementById('typeLX_<?=$key?>').style.display = 'none';	
				document.getElementById('typeL_<?=$key?>').style.display = 'table-row';
			<?
			}
			?>	
			document.getElementById('complement').value = 'L';
		}
		else if(type == "LX")
		{
			<?
			foreach($this->lLangues as $key => $lng)
			{
			?>
				document.getElementById('typeLX_<?=$key?>').style.display = 'table-row';	
				document.getElementById('typeL_<?=$key?>').style.display = 'none';
			<?
			}
			?>
			document.getElementById('complement').value = 'LX';
		}
		else
		{
			<?
			foreach($this->lLangues as $key => $lng)
			{
			?>
				document.getElementById('typeLX_<?=$key?>').style.display = 'none';	
				document.getElementById('typeL_<?=$key?>').style.display = 'none';
			<?
			}
			?>
			document.getElementById('complement').value = '';
		}
	}
</script>
<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="add_element" id="add_element" enctype="multipart/form-data" action="<?=$this->lurl?>/menus/elements" target="_parent">
    	<input type="hidden" name="id_menu" id="id_menu" value="<?=$this->menus->id_menu?>" />
        <input type="hidden" name="complement" id="complement" value="" />
    	<h1>Ajouter un &eacute;l&eacute;ment au menu <?=$this->menus->nom?></h1>
        <fieldset>
            <table class="formColor" height="350px">
            	<tr>
                    <th><label for="type_element">Type de lien :</label></th>
                    <td>
                        <select name="type_element" id="type_element" onchange="selectTypeLienMenu(this.value);" class="select">
                            <option value="">Sélectionner</option>
							<?
                            foreach($this->typesElements as $tag=>$elt)
                            {
                                echo '<option value="'.$tag.'">'.$elt.'</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Target du lien :</label></th>
                    <td><?=$this->bdd->listEnum('tree_menu','target','target')?></td>
                </tr>
                <?
				foreach($this->lLangues as $key => $lng)
				{
				?>
                    <tr>
                        <th><label for="nom_<?=$key?>">Nom du lien <?=(count($this->lLangues) > 1?'('.$key.')':'')?> :</label></th>
                        <td><input type="text" name="nom_<?=$key?>" id="nom_<?=$key?>" class="input_large" /></td>
                    </tr>            	
                    <tr id="typeLX_<?=$key?>" style="display: none;">
                        <th><label for="value_LX_<?=$key?>">Lien <?=(count($this->lLangues) > 1?'('.$key.')':'')?> :</label></th>
                        <td><input type="text" name="value_LX_<?=$key?>" id="value_LX_<?=$key?>" class="input_large" /></td>
                    </tr>
                    <tr id="typeL_<?=$key?>" style="display:none;">
                        <th><label for="value_L_<?=$key?>">Lien <?=(count($this->lLangues) > 1?'('.$key.')':'')?> :</label></th>
                        <td>
                            <select name="value_L_<?=$key?>" id="value_L_<?=$key?>" class="select">
                                <?
                                foreach($this->tree->listChilds(0,'-',array(),$key) as $tree)
                                {
                                ?>
                                    <option value="<?=$tree['id_tree']?>"><?=$tree['title']?></option>
                                <?
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Statut de l'&eacute;l&eacute;ment <?=(count($this->lLangues) > 1?'('.$key.')':'')?> :</label></th>
                        <td>
                            <input type="radio" value="1" id="status1_<?=$key?>" name="status_<?=$key?>" checked="checked" class="radio" />
                            <label for="status1_<?=$key?>" class="label_radio">En ligne</label>
                            <input type="radio" value="0" id="status0_<?=$key?>" name="status_<?=$key?>" class="radio" />
                            <label for="status0_<?=$key?>" class="label_radio">Hors ligne</label>	
                        </td>
                    </tr>
                <?
				}
				?>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_add_element" id="form_add_element" />
                        <input type="submit" value="Valider" name="send_element" id="send_element" class="btn" />
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>