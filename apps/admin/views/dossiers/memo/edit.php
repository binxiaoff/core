<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1><label for="content_memo"><?= $this->type === 'add' ? 'Ajouter' : 'Modifier' ?> un mémo</label></h1>
    <table style="margin-bottom:15px;">
        <tr>
            <td>
                <textarea name="content" id="content_memo" autofocus style="width:500px; height:150px;" class="select"><?= $this->content ?></textarea>
            </td>
        </tr>
        <tr>
            <td>
                <label><input type="radio" name="public_memo" value="0"<?php if (false === $this->public) : ?> checked<?php endif; ?>> Privé</label>
                <label><input type="radio" name="public_memo" value="1"<?php if ($this->public) : ?> checked<?php endif; ?>> Public</label>
            </td>
        </tr>
    </table>
    <?php if ($this->type === 'add') : ?>
        <button style="float:right" class="btn_link" onclick="editMemo(<?= $this->params['0'] ?>)">Ajouter</button>
    <?php else : ?>
        <button style="float:right" class="btn_link" onclick="editMemo(<?= $this->params['0'] ?>, <?= $this->params['1'] ?>)">Modifier</button>
    <?php endif; ?>
    <div class="clear"></div>
</div>
