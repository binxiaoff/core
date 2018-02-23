<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <form method="post" name="add_settings" id="add_settings" enctype="multipart/form-data" action="<?= $this->lurl ?>/settings" target="_parent">
        <h1>Ajouter un paramètre</h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="type">Type :</label></th>
                    <td><input type="text" name="type" id="type" class="input_large"/></td>
                </tr>
                <tr>
                    <th><label for="value">Valeur :</label></th>
                    <td><input type="text" name="value" id="value" class="input_large"/></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="form_add_settings" id="form_add_settings"/>
                        <button type="submit" class="btn-primary">Valider</button>
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
