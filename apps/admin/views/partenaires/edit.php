<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <form method="post" name="edit_part" id="edit_part" enctype="multipart/form-data" action="<?= $this->lurl ?>/partenaires/<?= $this->partenaires->id_partenaire ?>" target="_parent">
        <h1>Modifier <?= $this->partenaires->nom ?></h1>
        <fieldset>
            <table class="formColor">
                <tr>
                    <th><label for="nom">Nom :</label></th>
                    <td>
                        <input type="text" name="nom" id="nom" value="<?= $this->partenaires->nom ?>" class="input_large"/>
                    </td>
                </tr>
                <tr>
                    <th><label for="id_type">Type :</label></th>
                    <td>
                        <select name="id_type" id="id_type" class="select">
                            <?php foreach ($this->lTypes as $t) : ?>
                                <option value="<?= $t['id_type'] ?>"<?= ($t['id_type'] == $this->partenaires->id_type ? ' selected="selected"' : '') ?>><?= $t['nom'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Statut de la campagne :</label></th>
                    <td>
                        <input type="radio" value="1" id="status1" name="status"<?= ($this->partenaires->status == 1 ? ' checked="checked"' : '') ?> class="radio"/>
                        <label for="status1" class="label_radio">En ligne</label>
                        <input type="radio" value="0" id="status0" name="status"<?= ($this->partenaires->status == 0 ? ' checked="checked"' : '') ?> class="radio"/>
                        <label for="status0" class="label_radio">Hors ligne</label>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <th>
                        <input type="hidden" name="form_edit_part" id="form_edit_part"/>
                        <button type="submit" class="btn-primary">Valider</button>
                    </th>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
