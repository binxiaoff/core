<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<form method="post" name="edit_brands" id="edit_brands" enctype="multipart/form-data" action="<?=$this->lurl?>/brands/<?=$this->brands->id_brand?>" target="_parent">
        <h1>Modifier <?=$this->brands->name?></h1>            
        <fieldset>
            <table class="formColor">
            	<tr>
                    <th><label for="name">Name :</label></th>
                    <td><input type="text" name="name" id="name" value="<?=$this->brands->name?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="image">Image :</label></th>
                    <td>
                    	<input type="file" name="image" id="image" />
                        <input type="hidden" name="image-old" id="image-old" value="<?=$this->brands->image?>"/>
                  	</td>
                </tr>
                <tr>
                    <th><label>Statut de la marque :</label></th>
                    <td>
                        <input type="radio" value="1" id="status1" name="status" <?=($this->brands->status == 1?'checked="checked"':'')?> class="radio" />
                        <label for="status1" class="label_radio">En ligne</label>
                        <input type="radio" value="0" id="status0" name="status" <?=($this->brands->status == 0?'checked="checked"':'')?> class="radio" />
                        <label for="status0" class="label_radio">Hors ligne</label>	
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                	<th>
                        <input type="hidden" name="form_edit_brands" id="form_edit_brands" />
                        <input type="submit" value="Valider" title="Valider" name="send_brands" id="send_brands" class="btn" />
                    </th>
                </tr>
        	</table>
        </fieldset>
    </form>
</div>