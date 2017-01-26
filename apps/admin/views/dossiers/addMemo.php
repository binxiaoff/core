<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1>Ajouter un mémo :</h1>
    <table style="margin-bottom:15px;">
        <tr>
            <td>
                <textarea style="width:500px;height:150px;" name="content" id="content_memo"><?= $this->projects_comments->content ?></textarea>
            </td>
        </tr>
    </table>
    <button id="valider_addMemo" style="float:right" class="btn_link" onclick="addMemo(<?= ($this->type == 'add' ? $this->params['0'] : $this->params['1']) ?>,'<?= $this->type ?>'); parent.$.fn.colorbox.close();"><?= ($this->type == 'add' ? 'Ajouter' : 'Modifier') ?></button>
    <div class="clear"></div>
</div>
