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
            <button type="button" onclick="parent.$.fn.colorbox.close();" class="btn btnDisabled">Annuler</button>
            <?php if (0 == $this->iStep) : ?>
                <input type="submit" class="btn_link" value="Valider"/>
            <?php elseif (1 == $this->iStep) : ?>
                <button onclick="check_status_dossier(<?= \projects_status::REJETE ?>, <?= $this->iProjectId ?>);" class="btn">Rejeter</button>
            <?php else : ?>
                <button onclick="valid_rejete_etape<?= $this->iStep ?>(2, <?= $this->iProjectId ?>);" class="btn">Rejeter</button>
            <?php endif; ?>
        </div>
    </form>
</div>
