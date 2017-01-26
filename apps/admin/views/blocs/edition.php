<div id="contenu">
    <h1>Edition du bloc <?= $this->blocs->name ?></h1>
    <form method="post" name="edition_bloc" id="edition_bloc" enctype="multipart/form-data">
        <fieldset>
            <table class="large">
                <tr>
                    <td><textarea name="filecontent" id="filecontent" class="textarea_big"><?= $this->edit ?></textarea></td>
                </tr>
                <tr>
                    <td>
                        <input type="hidden" name="form_edition_bloc" id="form_edition_bloc" />
                        <input type="submit" value="Valider" name="edit_bloc" id="edit_bloc" class="btn" />
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
