<div id="popup">
    <h1>Motif de rejet</h1>
    <form id="rejection-reason-form" method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->iProjectId ?>">
        <select name="rejection_reason" id="rejection_reason" class="select">
            <option value=""></option>
            <?php foreach ($this->aRejectionReasons as $aRejectionReason) : ?>
                <option value="<?= $aRejectionReason['id_rejection'] ?>"><?= $aRejectionReason['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <?php if (1 == $this->iStep) : ?>
            <textarea id="rejection-comment" name="comment" class="textarea" title="Commentaire" style="width: 500px"></textarea><br>
            <label><input type="radio" name="rejection_public" value="0" checked> Privé </label>
            <label><input type="radio" name="rejection_public" value="1"> Public</label>
            <br><br>
        <?php endif; ?>
        <div class="right">
            <button type="button" onclick="parent.$.fn.colorbox.close();" class="btn-default">Annuler</button>
            <?php if (0 == $this->iStep) : ?>
                <button type="submit" class="btn-primary">Valider</button>
            <?php else : ?>
                <button type="submit" class="btn-primary">Rejeter</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    <?php if (1 == $this->iStep) : ?>
        CKEDITOR.replace('rejection-comment', {
            width: '500px',
            toolbar: 'Basic',
            removePlugins: 'elementspath',
            resize_enabled: false
        })

        CKEDITOR.on('instanceReady', function () {
            $.colorbox.resize()
        })
    <?php endif; ?>

    $('#rejection-reason-form').on('submit', function(e) {
        e.preventDefault()

        var reason = $('#rejection_reason').val()

        if ('' === reason) {
            alert('Veuillez renseigner le motif de rejet')
            return
        }

        <?php if (1 == $this->iStep) : ?>
            var privacy = $('[name=rejection_public]:checked').val()
            var comment = CKEDITOR.instances['rejection-comment'].getData()

            if ('' === comment || '0' !== privacy && '1' !== privacy) {
                alert('Vous devez saisir tous les champs')
                return
            }

            check_status_dossier(<?= \projects_status::COMMERCIAL_REJECTION ?>, <?= $this->iProjectId ?>)
        <?php elseif (0 < $this->iStep) : ?>
            valid_rejete_etape<?= $this->iStep ?>(2, <?= $this->iProjectId ?>)
        <?php endif; ?>
    });
</script>
