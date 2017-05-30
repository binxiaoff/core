<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1><label for="content_memo"><?= $this->type === 'add' ? 'Ajouter' : 'Modifier' ?> un mémo</label></h1>
    <div class="row">
        <textarea name="content" id="content_memo" style="width:600px; height:230px;" class="textarea memo"><?= $this->content ?></textarea>
    </div>
    <div class="row">
        <label><input type="radio" name="public_memo" value="0"<?php if (false === $this->public) : ?> checked<?php endif; ?>> Privé</label>
        <label><input type="radio" name="public_memo" value="1"<?php if ($this->public) : ?> checked<?php endif; ?>> Public</label>
    </div>
    <div class="row" style="margin-top: 15px">
        <?php if ($this->type === 'add') : ?>
            <button style="float:right" class="btn_link" onclick="editMemo(<?= $this->params['0'] ?>)">Ajouter</button>
        <?php else : ?>
            <button style="float:right" class="btn_link" onclick="editMemo(<?= $this->params['0'] ?>, <?= $this->params['1'] ?>)">Modifier</button>
        <?php endif; ?>
    </div>
    <div class="clear"></div>
</div>
<script>
    $(function() {
        $(document).on('cbox_complete', function () {
            if (CKEDITOR.instances['content_memo']) {
                CKEDITOR.instances['content_memo'].destroy(true)
            }
            CKEDITOR.replace('content_memo', {
                height: 170,
                width: 610,
                toolbar: 'Basic',
                removePlugins: 'elementspath',
                resize_enabled: false
            })
            setTimeout(function() {
                CKEDITOR.instances['content_memo'].focus()
                $(document).off('cbox_complete')
            }, 150)
        })
    })
</script>
