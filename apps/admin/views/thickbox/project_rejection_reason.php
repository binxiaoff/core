<div id="popup">
    <h1>Motif de rejet</h1>
    <form id="rejection_reason_form" method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->iProjectId ?>">
        <select name="rejection_reason" id="rejection_reason" class="select" style="width:160px;background-color:#AAACAC;">
            <option value="0">Choisir</option>
            <?php foreach ($this->aRejectionReasons as $aRejectionReason) : ?>
                <option<?= (isset($this->sRejectionReason) && $this->sRejectionReason == $aRejectionReason['label'] ? ' selected' : '') ?> value="<?= $aRejectionReason['id_rejection'] ?>"><?= $aRejectionReason['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <br/><br/>
        <div class="right">
            <button onclick="parent.$.fn.colorbox.close();" class="btn btnDisabled">Annuler</button>
            <input type="submit" class="btn_link" value="Valider"/>
        </div>
    </form>
</div>
