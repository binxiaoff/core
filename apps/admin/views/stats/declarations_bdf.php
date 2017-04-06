<div id="contenu">
    <h1>Fichiers des déclarations mensuelles Banque De France</h1>
    <?php if (0 < count($this->declarationList)) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Moi de centralisation</th>
                <th>Date de création du fichier</th>
                <th>Lien de téléchargement</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->declarationList as $year => $declarations) : ?>
                <td colspan="3" style="text-align: center"><?= $year ?></td>
                <?php foreach ($declarations as $index => $declarationDetails) : ?>
                    <tr <?= ($index % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $declarationDetails['declarationDate'] ?></td>
                        <td><?= $declarationDetails['creationDate'] ?></td>
                        <td><a title="Télécharger" href="<?= $declarationDetails['link'] ?>"><?= $declarationDetails['fileName'] ?></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        Aucune déclaration trouvée.
    <?php endif ?>
</div>
