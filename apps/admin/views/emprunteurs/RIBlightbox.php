<div id="popup">
    <?php if ($this->alreadySent > 0): ?>
        <p>Attention, un prélèvement est déjà lancé pour le <?= date('d/m/Y', strtotime($this->sentEcheance)) ?>.</p>
        <p>Ce changement de RIB sera effectif à partir du prélèvement prévu pour le <?= date('d/m/Y', strtotime($this->nextEcheance)) ?>.</p>
        <p>Valider tout de même ?</p>
    <?php else: ?>
        <p>Confirmer le changement de RIB pour ce projet?</p>
        <p>Prise en compte pour l'échéance du <?= date('d/m/Y', strtotime($this->nextEcheance)) ?></p>
    <?php endif; ?>
    <center>
        <button onclick="document.getElementById('edit_emprunteur').submit()" class='btn' >Valider</button>
        <button onclick="parent.$.fn.colorbox.close();" class='btn' style="margin-left:15px;"  >Refuser</button>
    </center>
</div>
