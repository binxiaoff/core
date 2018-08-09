<div id="contenu">
    <h1>Liste des templates</h1>
    <?php if (count($this->lTemplate) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Nom du template</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lTemplate as $t) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $t['name'] ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/templates/elements/<?= $t['id_template'] ?>" title="Liste des éléments du template <?= $t['name'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Liste des éléments du template <?= $t['name'] ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Il n'y a aucun template pour le moment.</p>
    <?php endif; ?>
</div>
