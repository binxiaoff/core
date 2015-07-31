<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<?
	if($_SESSION['newPassword'] == 'OK')
	{
	?>
    	<h1>Nouveau mot de passe</h1>
    	<strong>Votre nouveau mot de passe a été envoyé !</strong>
    <?
	}
	else
	{
	?>
    	<form method="post" name="new_password" id="new_password" enctype="multipart/form-data" action="<?=$this->lurl?>/login" target="_parent">
            <h1>Nouveau mot de passe</h1>            
            <fieldset>
                <table class="recupPassword">
                    <tr>
                        <td><label for="email">Adresse Email</label></td>
                        <td><input type="text" name="email" id="email" class="input_recupPassword" /></td>
                        <td>
                            <input type="hidden" name="form_new_password" id="form_new_password" />
                            <input type="submit" value="Envoyer" title="Envoyer" name="send_new_password" id="send_new_password" class="btn" />
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="3">
                        	<?
							if($_SESSION['newPassword'] == 'NOK')
							{
							?>
								<strong class="red">Adresse e-mail inconnue !</strong>
							<?
							}
							?>
                        </td>
                   	</tr>
                </table>
            </fieldset>
        </form>        
    <?
	}
	?>
</div>
<?php 
	unset($_SESSION['msgErreur']);
	unset($_SESSION['newPassword']);
?>