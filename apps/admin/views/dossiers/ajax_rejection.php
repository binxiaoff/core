<div id="popup" class="rejection-popup">
    <h1>Rejeter le projet</h1>
    <form id="rejection-reason-form" method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->projectId ?>">
        <div class="form-group row">
            <label for="rejection_reason" class="col-md-3 col-form-label">Motif</label>
            <div class="col-md-9">
                <select name="rejection_reason" id="rejection_reason" class="select form-control">
                    <option value=""></option>
                    <?php foreach ($this->rejectionReasons as $rejectionReason) : ?>
                        <option value="<?= $rejectionReason->getIdRejection() ?>"><?= $rejectionReason->getLabel() ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php if (1 == $this->step) : ?>
            <div class="form-group row">
                <label for="rejection-comment" class="col-md-3 col-form-label">Commentaire (mémo)</label>
                <div class="col-md-9">
                    <textarea id="rejection-comment" name="comment" class="textarea form-control"></textarea>
                </div>
            </div>
            <fieldset class="form-group">
                <div class="row">
                    <label class="col-form-label col-md-3 pt-0">Visibilité</label>
                    <div class="col-md-9">
                        <div class="form-check">
                            <input type="radio" id="rejection-private" name="rejection_privacy" value="0" class="form-check-input" checked>
                            <label for="rejection-private" class="form-check-label">Privé</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" id="rejection-public" name="rejection_privacy" value="1" class="form-check-input">
                            <label for="rejection-public" class="form-check-label">Public</label>
                        </div>
                    </div>
                </div>
            </fieldset>
        <?php endif; ?>
        <?php if (1 <= $this->step) : ?>
            <fieldset class="form-group">
                <div class="row">
                    <label class="col-form-label col-md-3 pt-0">Envoyer l'email de rejet</label>
                    <div class="col-md-9">
                        <div class="form-check">
                            <input type="radio" id="send-email-yes" name="send_email" value="1" class="form-check-input" checked>
                            <label for="send-email-yes" class="form-check-label">Oui</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" id="send-email-no" name="send_email" value="0" class="form-check-input">
                            <label for="send-email-no" class="form-check-label">Non</label>
                        </div>
                    </div>
                </div>
            </fieldset>
        <?php endif; ?>
        <div class="form-group row text-right">
            <button type="button" onclick="parent.$.fn.colorbox.close();" class="btn-default">Annuler</button>
            <?php if (0 == $this->step) : ?>
                <button type="submit" class="btn-primary">Valider</button>
            <?php else : ?>
                <button type="submit" class="btn-primary">Rejeter</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    <?php if (1 == $this->step) : ?>
    $(document).on('cbox_complete', function () {
        if (CKEDITOR.instances.hasOwnProperty('rejection-comment')) {
            var editor = CKEDITOR.instances['rejection-comment']
            editor.destroy(true)
        }

        CKEDITOR.replace('rejection-comment', {
            width: '500px',
            toolbar: 'Basic',
            removePlugins: 'elementspath',
            resize_enabled: false,
            startupFocus: true
        })

        CKEDITOR.on('instanceReady', function () {
            $.colorbox.resize()
        })
    })
    <?php endif; ?>

    $('#rejection-reason-form').on('submit', function (e) {
        e.preventDefault()

        var reason = $('#rejection_reason').val()

        if ('' === reason) {
            alert('Veuillez renseigner le motif de rejet')
            return
        }

        <?php if (1 == $this->step) : ?>
            var comment = CKEDITOR.instances['rejection-comment'].getData()

            if ('' === comment) {
                alert('Veuillez saisir un commentaire')
                return
            }

            var privacy = $('[name=rejection_privacy]:checked').val()

            if ('0' !== privacy && '1' !== privacy) {
                alert('Veuillez renseigner la visibilité du mémo')
                return
            }

            check_status_dossier(<?= \projects_status::COMMERCIAL_REJECTION ?>, <?= $this->projectId ?>)
        <?php elseif (0 < $this->step) : ?>
            valid_rejete_etape<?= $this->step ?>(2, <?= $this->projectId ?>)
        <?php endif; ?>
    });
</script>
