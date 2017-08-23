<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <form method="post" name="add_users" id="add_users" enctype="multipart/form-data" action="<?= $this->lurl ?>/users" target="_parent" onsubmit="return checkFormAjoutUser();">
        <h1>Ajouter un utilisateur</h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="firstname">Prénom</label></th>
                    <td><input type="text" name="firstname" id="firstname" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="name">Nom</label></th>
                    <td><input type="text" name="name" id="name" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="phone">Téléphone</label></th>
                    <td><input type="text" name="phone" id="phone" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="mobile">Mobile</label></th>
                    <td><input type="text" name="mobile" id="mobile" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="email">Email *</label></th>
                    <td><input type="text" name="email" id="email" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="slack">Slack</label></th>
                    <td><input type="text" name="slack" id="slack" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="ip">IP</label></th>
                    <td><input type="text" name="ip" id="ip" value="<?= $this->users->ip ?>" autocomplete="off" class="input_large" placeholder="192.168.0.1;127.0.0.1-127.0.0.255;8.8.8.8"></td>
                </tr>
                <tr>
                    <th><label for="id_user_type">Droits</label></th>
                    <td>
                        <select name="id_user_type" id="id_user_type" class="select">
                            <option value="0">Choisir</option>
                            <?php foreach ($this->lUsersTypes as $type) : ?>
                                <option value="<?= $type['id_user_type'] ?>"><?= $type['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Statut</label></th>
                    <td>
                        <input type="radio" value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\Users::STATUS_ONLINE ?>" id="status1" name="status" checked class="radio">
                        <label for="status1" class="label_radio">&nbsp;En ligne</label>
                        <input type="radio" value="<?= \Unilend\Bundle\CoreBusinessBundle\Entity\Users::STATUS_OFFLINE ?>" id="status0" name="status" class="radio">
                        <label for="status0" class="label_radio">&nbsp;Hors ligne</label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type="submit" class="btn-primary pull-right">Valider</button>
                        <input type="hidden" name="form_add_users" id="form_add_users">
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
