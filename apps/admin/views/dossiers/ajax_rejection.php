<div id="popup">
    <h1>Motif de rejet</h1>
    <select id="rejection_reason" class="select">
        <option></option>
        <?php foreach ($this->aRejectionReasons as $aRejectionReason) : ?>
            <option value="<?= $aRejectionReason['id_rejection'] ?>"><?= $aRejectionReason['label'] ?></option>
        <?php endforeach; ?>
    </select>
    <br/><br/>
    <div class="right">
        <button onclick="parent.$.fn.colorbox.close();" class="btn btnDisabled">Annuler</button>
        <button onclick="valid_rejete_etape<?= $this->iStep ?>(2, <?= $this->iProjectId ?>);" class="btn">Rejeter</button>
    </div>
</div>
