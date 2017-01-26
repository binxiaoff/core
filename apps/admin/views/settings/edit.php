<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <form method="post" name="edit_settings" id="edit_settings" enctype="multipart/form-data" action="<?= $this->lurl ?>/settings/<?= $this->settings->id_setting ?>" target="_parent">
        <h1>Modifier <?= $this->settings->type ?></h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="type">Type :</label></th>
                    <td><input type="text" name="type" id="type" value="<?= htmlentities($this->settings->type, ENT_QUOTES) ?>" class="input_large"></td>
                </tr>
                <tr>
                    <th><label for="value">Valeur :</label></th>
                    <td><input type="text" name="value" id="value" value="<?= htmlentities($this->settings->value, ENT_QUOTES) ?>" class="input_large"></td>
                </tr>
                <?php if ($this->settings->status != \settings::STATUS_BLOCKED) : ?>
                    <tr>
                        <th><label>Statut :</label></th>
                        <td>
                            <input type="radio" value="1" id="status1" name="status"<?= ($this->settings->status == \settings::STATUS_ACTIVE ? ' checked' : '') ?> class="radio">
                            <label for="status1" class="label_radio">En ligne</label>
                            <input type="radio" value="0" id="status0" name="status"<?= ($this->settings->status == \settings::STATUS_INACTIVE ? ' checked' : '') ?> class="radio">
                            <label for="status0" class="label_radio">Hors ligne</label>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="form_edit_settings" id="form_edit_settings">
                        <input type="submit" value="Valider" title="Valider" name="send_settings" id="send_settings" class="btn">
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
