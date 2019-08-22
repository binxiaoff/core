<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->url ?>/images/delete.png" alt="Fermer" /></a>
    <form method="post" name="add_requete" id="add_requete" enctype="multipart/form-data" action="<?= $this->url ?>/queries" target="_parent">
        <h1>Ajouter une requête</h1>
        <fieldset>
            <table class="formColor">
            <tr>
                <th><label for="name">Nom</label></th>
                <td><input type="text" name="name" id="name" class="input_large" /></td>
            </tr>
            <tr>
                <th><label for="paging">Lignes par page</label></td>
                <td><input type="text" name="paging" id="paging" class="input_court" /></th>
            </tr>
            <tr>
                <th><label for="sql">SQL</label></th>
                <td><textarea name="sql" id="sql" class="textarea"></textarea></td>
            </tr>
            <tr>
                <th colspan="2">
                    <input type="hidden" name="form_add_requete" id="form_add_requete" />
                    <button type="submit" class="btn-primary">Valider</button>
                </th>
            </tr>
        </table>
        </fieldset>
    </form>
</div>
