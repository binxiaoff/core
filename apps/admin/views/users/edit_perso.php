<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="mod_users" id="mod_users" enctype="multipart/form-data" action="<?= $this->lurl ?>/users/edit_perso_user/<?= $this->users->id_user ?>" target="_parent" onsubmit="return checkFormModifUser();">
        <h1>Modifier <?= $this->users->firstname ?> <?= $this->users->name ?></h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="firstname">Pr&eacute;nom :</label></th>
                    <td><input type="text" name="firstname" id="firstname" value="<?= $this->users->firstname ?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="name">Nom :</label></th>
                    <td><input type="text" name="name" id="name" value="<?= $this->users->name ?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="phone">T&eacute;l&eacute;phone :</label></th>
                    <td><input type="text" name="phone" id="phone" value="<?= $this->users->phone ?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="mobile">Mobile :</label></th>
                    <td><input type="text" name="mobile" id="mobile" value="<?= $this->users->mobile ?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="email">Email* :</label></th>
                    <td><input type="text" name="email" id="email" value="<?= $this->users->email ?>" autocomplete="off" class="input_large" /></td>
                </tr>
                <tr style="margin-top:10px; margin-bottom:10px;">
                    <th><label for="email">Mot de passe :</label></th>
                    <td><a href="<?= $this->lurl ?>/users/edit_password/" class="btn_link">Générer un nouveau mot de passe</a></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="form_mod_users" id="form_mod_users" />
                        <button type="submit" class="btn-primary">Valider</button>
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
