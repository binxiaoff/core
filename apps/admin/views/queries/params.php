<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="envoi_params" id="envoi_params" enctype="multipart/form-data" action="<?=$this->lurl?>/queries/execute/<?=$this->queries->id_query?>" target="_parent">
        <h1>Paramètres de <?=$this->queries->name?></h1>            
        <fieldset>
            <table class="formColor">
            <?
			foreach($this->sqlParams as $param)
			{
			?>
				<tr>
					<th><label for="param_<?=str_replace('@','',$param[0])?>"><?=str_replace('@','',$param[0])?> :</label></th>
					<td><input type="text" name="param_<?=str_replace('@','',$param[0])?>" id="param_<?=str_replace('@','',$param[0])?>" class="input_large" /></td>
				</tr>
			<?	
			}
			?>
            <tr>
            	<td>&nbsp;</td>
                <th>
                    <input type="hidden" name="form_envoi_params" id="form_envoi_params" />
                    <input type="submit" value="Valider" title="Valider" name="send_params" id="send_params" class="btn" />
                </th>
            </tr>
        </table>
        </fieldset>
    </form>
</div>