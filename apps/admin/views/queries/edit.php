<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->url ?>/images/delete.png" alt="Fermer" /></a>
    <form method="post" name="edit_requete" id="edit_requete" enctype="multipart/form-data" action="<?= $this->url ?>/queries/<?= $this->query->getIdQuery() ?>" target="_parent">
        <h1>Modifier <?= $this->query->getName() ?></h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="name">Nom</label></th>
                    <td><input type="text" name="name" id="name" value="<?= $this->query->getName() ?>" class="input_large" /></td>
                </tr>
                <tr>
                    <th><label for="paging">Lignes par page</label></td>
                    <td><input type="text" name="paging" id="paging" value="<?= $this->query->getPaging() ?>" class="input_court" /></th>
                </tr>
                <tr>
                    <th><label for="sql">SQL</label></th>
                    <td><textarea name="sql" id="sql" class="textarea"><?= $this->query->getQuery() ?></textarea></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="form_edit_requete" id="form_edit_requete" />
                        <button type="submit" class="btn-primary">Valider</button>
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
