<form method="post" name="mod_traduction" id="mod_traduction" enctype="multipart/form-data" action="<?= $this->lurl ?>/traductions">
    <input type="hidden" name="form_mod_traduction" id="form_mod_traduction" value="0">
    <input type="hidden" name="section" id="section" value="<?= $this->section ?>">
    <input type="hidden" name="nom" id="nom" value="<?= $this->nom ?>">
    <div>
        <textarea name="translation" id="translation" class="textarea_lng" title="Traduction" style="margin-top: 20px;"><?= $this->translation ?></textarea>
    </div>
    <div class="btnDroite">
        <input type="submit" value="Supprimer" name="del_traduction" id="del_traduction" class="btn-default" onclick="if (confirm('Êtes vous certain ?')){ document.getElementById('form_mod_traduction').value = 'delete'; } else { return false; }">
        <input type="submit" value="Modifier" name="send_traduction" id="send_traduction" class="btn-primary">
    </div>
</form>
