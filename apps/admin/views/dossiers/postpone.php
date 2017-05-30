<div id="popup" class="postpone-popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <form method="post" id="postpone_form" action="<?= $this->lurl ?>/dossiers/postpone/<?= $this->projects->id_project ?>">
        <h1>Reporter un dossier</h1>
        <fieldset>
            <label for="postpone_comment">Mémo&nbsp;*</label>
            <textarea name="comment" id="postpone_comment" class="textarea memo" style="height:250px;"></textarea>
            <p style="text-align:right; margin:15px 0 0 0;">
                <a href="javascript:parent.$.fn.colorbox.close()" class="btn-default">Annuler</a>
                <button type="submit" class="btn-primary">Valider</button>
            </p>
        </fieldset>
    </form>
</div>
<script>
    $(function() {
        $('#postpone_form').submit(function(event) {
            if (! CKEDITOR.instances['postpone_comment'].getData()) {
                event.preventDefault()
                alert('Vous devez obligatoirement saisir un mémo')
            }
        })
        $(document).on('cbox_complete', function () {
            if (CKEDITOR.instances['postpone_comment']) {
                CKEDITOR.instances['postpone_comment'].destroy(true)
            }
            CKEDITOR.replace('postpone_comment', {
                height: 180,
                width: 570,
                toolbar: 'Basic',
                removePlugins: 'elementspath',
                resize_enabled: false
            })
            setTimeout(function() {
                CKEDITOR.instances['postpone_comment'].focus()
                $(document).off('cbox_complete')
            }, 300)
        })
    })
</script>
