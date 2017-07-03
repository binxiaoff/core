<div id="popup" class="suspensive-conditions-popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <form method="post" id="suspensive-conditions-form" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>">
        <h1>Valider avec conditions suspensives de mise en ligne</h1>
        <p><em>Notez ici les conditions suspensives de mise en ligne. Ces conditions devront être vérifiées manuellement avant passage du projet en statut "Prép Funding".</em></p>
        <p><strong>La note de crédit doit également être complétée et sauvegardée avant.</strong></p>
        <fieldset>
            <textarea name="comment" id="comment" style="width:600px; height:270px;"></textarea>
            <div class="pull-right" style="margin-top:10px;">
                <a href="javascript:parent.$.fn.colorbox.close()" class="btn-default">Annuler</a>
                <button type="submit" class="btn-primary">Valider</button>
            </div>
        </fieldset>
    </form>
</div>
<script>
    $(function() {
        $('#suspensive-conditions-form').submit(function(event) {
            event.preventDefault()
            if (! CKEDITOR.instances['comment'].getData()) {
                alert('Vous devez obligatoirement saisir un mémo')
            } else {
                if ($('#comment').val() === '') {
                    $('#comment').val(CKEDITOR.instances['comment'].getData())
                }
                valid_rejete_etape7(4, <?= $this->projects->id_project ?>)
            }
        })

        $(document).on('cbox_complete', function () {
            if (CKEDITOR.instances['comment']) {
                CKEDITOR.instances['comment'].destroy(true)
            }
            CKEDITOR.replace('comment', {
                height: 170,
                width: 845,
                toolbar: 'Basic',
                removePlugins: 'elementspath',
                resize_enabled: false
            })
            setTimeout(function() {
                CKEDITOR.instances['comment'].focus()
                $(document).off('cbox_complete')
            }, 300)
        })
    })
</script>
