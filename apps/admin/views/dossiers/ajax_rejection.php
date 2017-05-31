<div id="popup">
    <h1>Motif de rejet</h1>
    <form id="rejection_reason_form" method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->iProjectId ?>">
        <select name="rejection_reason" id="rejection_reason" class="select">
            <option></option>
            <?php foreach ($this->aRejectionReasons as $aRejectionReason) : ?>
                <option value="<?= $aRejectionReason['id_rejection'] ?>"><?= $aRejectionReason['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <br/><br/>
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
    $('#rejection_reason_form').submit(function(e) {
        <?php if (1 == $this->iStep) : ?>
            e.preventDefault();
            check_status_dossier(<?= \projects_status::COMMERCIAL_REJECTION ?>, <?= $this->iProjectId ?>);
        <?php elseif (0 < $this->iStep) : ?>
            e.preventDefault();
            valid_rejete_etape<?= $this->iStep ?>(2, <?= $this->iProjectId ?>);
        <?php endif; ?>
    });
</script>
