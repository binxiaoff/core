<script type="text/javascript">
	function afficheNewPassword()
	{
		document.getElementById('newPassword').style.display = 'block';	
		document.getElementById('connexImpossible').style.display = 'none';	
	}
</script>
<div id="popup">
	<a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
	<div id="connexImpossible">
        <h1>Connexion impossible</h1>
        <strong class="red">Vérifiez vos identifiants de connexion !</strong><br /><br />
        Vous pouvez recevoir un nouveau mot de passe en cliquant sur le lien.<br /><br />
        <a onclick="afficheNewPassword();" title="Envoyer un nouveau mot de passe"><strong>Envoyer un nouveau mot de passe</strong></a>
  	</div>
    <div id="newPassword" style="display: none;">
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
                </table>
            </fieldset>
        </form>
    </div>
</div>
<?php unset($_SESSION['msgErreur']); ?>