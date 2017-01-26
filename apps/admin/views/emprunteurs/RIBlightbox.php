<div id="popup">
    <?php if (count($this->aProjects) == 1) : ?>
        <p><strong>Confirmer le changement de RIB pour <?= $this->aProjects[0]['title'] ?>&nbsp;:</strong></p>
    <?php else : ?>
        <p><strong>Confirmer le changement de RIB pour les projets suivants&nbsp;:</strong></p>
        <p>
            <ul>
            <?php foreach ($this->aProjects as $aProject) : ?>
                <li><?= $aProject['title'] ?> (<?= $aProject['id_project'] ?>)</li>
            <?php endforeach; ?>
            </ul>
        </p>
    <?php endif; ?>
    <p>Le nouveau mandat va être envoyé par mail.</p>
    <p>La prise en compte des modifications aura lieu à sa signature.</p>
    <center>
        <button onclick="document.getElementById('edit_emprunteur').submit()" class='btn'>Valider</button>
        <button onclick="parent.$.fn.colorbox.close();" class='btn' style="margin-left:15px;">Refuser</button>
    </center>
</div>
