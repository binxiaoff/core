<form method="post" name="mod_traduction" id="mod_traduction" enctype="multipart/form-data" action="<?= $this->lurl ?>/traductions">
    <input type="hidden" name="section" id="section" value="<?= $this->params[1] ?>"/>
    <input type="hidden" name="nom" id="nom" value="<?= $this->params[0] ?>"/>
    <table class="lng">
        <tr>
            <td>
                <img src="<?= $this->surl ?>/images/admin/langues/fr.png" alt="fr"/>
            </td>
            <td>
                <textarea class="textarea_lng" style="background-image: url('<?= $this->surl ?>/images/admin/langues/flag_fr.png'); background-position:center; background-repeat:no-repeat;" name="translation" id="translation"><?= $this->lTranslations ?></textarea>
            </td>
        <tr>
        <tr>
            <th colspan="2">
                <input type="hidden" name="form_mod_traduction" id="form_mod_traduction" value="0"/>
                <input type="submit" value="Modifier" name="send_traduction" id="send_traduction" class="btn"/>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <input type="submit" value="Supprimer" name="del_traduction" id="del_traduction" class="btnRouge" onclick="if(confirm('Êtes vous certain ?')){ document.getElementById('form_mod_traduction').value = 1; } else { return false; }"/>
            </th>
        </tr>
    </table>
</form>
