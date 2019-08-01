<div id="popup" style="background-color:#FFF;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->url ?>/images/delete.png" alt="Fermer"/></a>
    <form method="post" name="add_traduction" id="add_traduction" enctype="multipart/form-data" action="<?= $this->url ?>/traductions" target="_parent">
        <h1>Ajouter une traduction</h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="section">Section :</label></th>
                    <td>
                        <input type="text" name="section" id="section" value="<?= (isset($this->params[0]) && $this->params[0] != '' ? $this->params[0] : '') ?>" class="input_large"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="nom">Nom :</label></th>
                    <td><input type="text" name="name" id="name" class="input_large"/></td>
                </tr>
                <tr>
                    <th><label for="translation">Texte <?= (count($this->lLangues) > 1 ? '(' . $this->dLanguage . ')' : '') ?>:</label></th>
                    <td><textarea name="translation" id="translation" class="textarea"></textarea></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="id_langue" id="id_langue" value="<?= $this->dLanguage ?>"/>
                        <input type="hidden" name="form_add_traduction" id="form_add_traduction"/>
                        <button type="submit" class="btn-primary">Valider</button>
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
