<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <form method="post" name="mod_users" id="mod_users" enctype="multipart/form-data" action="<?= $this->lurl ?>/users/<?= $this->users->id_user ?>" target="_parent" onsubmit="return checkFormModifUser();">
        <h1>Modifier <?= $this->users->firstname ?> <?= $this->users->name ?></h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="firstname">Prénom</label></th>
                    <td><input type="text" name="firstname" id="firstname" value="<?= $this->users->firstname ?>" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="name">Nom</label></th>
                    <td><input type="text" name="name" id="name" value="<?= $this->users->name ?>" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="phone">Téléphone</label></th>
                    <td><input type="text" name="phone" id="phone" value="<?= $this->users->phone ?>" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="mobile">Mobile</label></th>
                    <td><input type="text" name="mobile" id="mobile" value="<?= $this->users->mobile ?>" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="email">Email *</label></th>
                    <td><input type="text" name="email" id="email" value="<?= $this->users->email ?>" autocomplete="off" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="slack">Slack</label></th>
                    <td><input type="text" name="slack" id="slack" value="<?= $this->users->slack ?>" autocomplete="off" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="ip">Restriction IP</label></th>
                    <td><input type="text" name="ip" id="ip" value="<?= $this->users->ip ?>" autocomplete="off" class="input_large" placeholder="192.168.0.1;127.0.0.1-127.0.0.255;8.8.8.8"></td>
                </tr>
                <tr>
                    <th><label for="id_user_type">Droits</label></th>
                    <td>
                        <select name="id_user_type" id="id_user_type" class="select">
                            <option value="0">Choisir</option>
                            <?php foreach ($this->userTypes as $type) : ?>
                                <option value="<?= $type->getIdUserType() ?>" <?= $this->users->id_user_type == $type->getIdUserType() ? 'selected="selected"' : '' ?>><?= $type->getLabel() ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Statut</label></th>
                    <td>
                        <input type="radio" value="<?= \Unilend\Entity\Users::STATUS_ONLINE ?>" id="status1" name="status" class="radio"<?= $this->users->status == \Unilend\Entity\Users::STATUS_ONLINE ? ' checked' : '' ?>>
                        <label for="status1" class="label_radio">&nbsp;En ligne</label>
                        <input type="radio" value="<?= \Unilend\Entity\Users::STATUS_OFFLINE ?>" id="status0" name="status" class="radio"<?= $this->users->status == \Unilend\Entity\Users::STATUS_OFFLINE ? ' checked' : '' ?>>
                        <label for="status0" class="label_radio">&nbsp;Hors ligne</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <a href="<?= $this->lurl ?>/users/generate_new_password/<?= $this->users->id_user ?>" class="btn-default">Générer un nouveau mot de passe</a>
                        <button type="submit" class="btn-primary pull-right">Valider</button>
                        <input type="hidden" name="form_mod_users">
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
