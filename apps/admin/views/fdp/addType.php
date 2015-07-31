<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/produits" title="Boutique">Boutique</a> -</li>
        <li><a href="<?=$this->lurl?>/fdp/types" title="Types de FDP">Types de FDP</a> -</li>
        <li>Ajouter un type de frais de port</li>
    </ul>
    <h1>Ajouter un type de frais de port</h1>
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
    <form method="post" name="add_type" id="add_type" enctype="multipart/form-data">
        <?
		foreach($this->lLangues as $key => $lng)
		{
		?>
        	<div id="langue_<?=$key?>"<?=($key!=$this->language?' style="display:none;"':'')?>>
            	<fieldset>
                    <table class="large">
                        <tr>
                            <th><label for="nom_<?=$key?>">Nom :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="nom_<?=$key?>" id="nom_<?=$key?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="affichage_<?=$key?>">Affichage :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="affichage_<?=$key?>" id="affichage_<?=$key?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="description_<?=$key?>">Description :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="description_<?=$key?>" id="description_<?=$key?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="delais_min_<?=$key?>">Delais Min. :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="delais_min_<?=$key?>" id="delais_min_<?=$key?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="delais_max_<?=$key?>">Delais Max. :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="delais_max_<?=$key?>" id="delais_max_<?=$key?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="url_suivi_<?=$key?>">URL de suivi :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="url_suivi_<?=$key?>" id="url_suivi_<?=$key?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <td>
                                <input type="hidden" name="form_add_type" id="form_add_type" />
                                <input type="submit" value="Valider" title="Valider" name="send_type" id="send_type" class="btn" />
                            </td>
                        </tr>
                    </table>
              	</fieldset>
  			</div>
        <?	
		}
		?>
    </form>
</div>