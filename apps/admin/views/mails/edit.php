<div id="contenu">
	<ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li><a href="<?=$this->lurl?>/mails" title="Mails">Mails</a> -</li>
        <li>Modifier un email</li>
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
    <form method="post" name="mod_mail" id="mod_mail" enctype="multipart/form-data">
        <input type="hidden" name="lng_encours" id="lng_encours" value="<?=$this->language?>" />
    	<?
		foreach($this->lLangues as $key => $lng)
		{
			// Recuperation des infos du mail
			$this->mails_text->get($this->params[0],'lang = "'.$key.'" AND type');
		?>
        	<div id="langue_<?=$key?>"<?=($key!=$this->language?' style="display:none;"':'')?>>
            	<fieldset>
                    <h1>Modifier le mail <?=$this->mails_text->name?><?=(count($this->lLangues) > 1?' en : '.$lng:'')?></h1>
                    <table class="large">
                        <tr>
                            <th>
                                <label for="mode_<?=$key?>">Mode d'envoi :</label>
                                <select name="mode_<?=$key?>" id="mode_<?=$key?>">
                                    <option value="0"<?=($this->mails_text->mode == 0?' selected="selected"':'')?>>Transactionnel</option>
                                    <option value="1"<?=($this->mails_text->mode == 1?' selected="selected"':'')?>>Marketing</option>
                                </select>
                            </th>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <th><label for="name_<?=$key?>">Nom :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="name_<?=$key?>" id="name_<?=$key?>" value="<?=$this->mails_text->name?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="exp_name_<?=$key?>">Nom d'expéditeur :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="exp_name_<?=$key?>" id="exp_name_<?=$key?>" value="<?=$this->mails_text->exp_name?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="exp_email_<?=$key?>">Adresse d'expéditeur :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="exp_email_<?=$key?>" id="exp_email_<?=$key?>" value="<?=$this->mails_text->exp_email?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="subject_<?=$key?>">Sujet :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="subject_<?=$key?>" id="subject_<?=$key?>" value="<?=$this->mails_text->subject?>" class="input_big" /></td>
                        </tr>
                       <!-- <tr>
                            <th><label for="id_nmp_<?=$key?>">ID Template NMP :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="id_nmp_<?=$key?>" id="id_nmp_<?=$key?>" value="<?=$this->mails_text->id_nmp?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="nmp_unique_<?=$key?>">Numéro unique NMP :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="nmp_unique_<?=$key?>" id="nmp_unique_<?=$key?>" value="<?=$this->mails_text->nmp_unique?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="nmp_secure_<?=$key?>">Phrase sécurisé NMP :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="nmp_secure_<?=$key?>" id="nmp_secure_<?=$key?>" value="<?=$this->mails_text->nmp_secure?>" class="input_big" /></td>
                        </tr>
                        <tr>
                            <th><label for="link_upload_<?=$key?>">Lien pour chargement dans NMP :</label></th>
                        </tr>
                        <tr>
                            <td><input type="text" name="link_upload_<?=$key?>" id="link_upload_<?=$key?>" value="<?=$this->lurl?>/mails/html/<?=$this->mails_text->id_textemail?>" class="input_big" readonly /></td>
                        </tr>-->
                        <tr>
                            <th><label for="content_<?=$key?>">Contenu :</label></th>
                        </tr>
                        <tr>
                            <td><textarea name="content_<?=$key?>" id="content_<?=$key?>" class="textarea_big"><?=htmlentities($this->mails_text->content)?></textarea></td>
                        </tr>
                        <tr>
                            <td>
                            	<input type="hidden" name="id_textemail_<?=$key?>" value="<?=$this->mails_text->id_textemail?>"> 
                                <input type="hidden" name="form_mod_mail" id="form_mod_mail" />
                                <input type="submit" value="Valider" title="Valider" name="send_mail" id="send_mail" class="btn" />
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