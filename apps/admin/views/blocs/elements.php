<div id="contenu">
    <h1>Liste des éléments du bloc <?= $this->blocs->name ?></h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/blocs/addElement/<?= $this->blocs->id_bloc ?>" class="btn_link thickbox">Ajouter un élément</a>
    </div>
    <?php if (count($this->lElements) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Slug</th>
                    <th>Type</th>
                    <th>Position</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lElements as $e) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $e['name'] ?></td>
                        <td><?= $e['slug'] ?></td>
                        <td><?= $e['type_element'] ?></td>
                        <td align="center">
                            <?php if (count($this->lElements) > 1) : ?>
                                <?php if ($e['ordre'] > 0) : ?>
                                    <a href="<?= $this->lurl ?>/blocs/elements/<?= $this->blocs->id_bloc ?>/up/<?= $e['id_element'] ?>" title="Remonter">
                                        <img src="<?= $this->surl ?>/images/admin/up.png" alt="Remonter"/>
                                    </a>
                                <?php endif; ?>
                                <?php if ($e['ordre'] < (count($this->lElements) - 1)) : ?>
                                    <a href="<?= $this->lurl ?>/blocs/elements/<?= $this->blocs->id_bloc ?>/down/<?= $e['id_element'] ?>" title="Descendre">
                                        <img src="<?= $this->surl ?>/images/admin/down.png" alt="Descendre"/>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/blocs/elements/<?= $this->blocs->id_bloc ?>/status/<?= $e['id_element'] ?>/<?= $e['status'] ?>" title="<?= ($e['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                <img src="<?= $this->surl ?>/images/admin/<?= ($e['status'] == 1 ? 'offline' : 'online') ?>.png" alt="<?= ($e['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/blocs/editElement/<?= $e['id_element'] ?>/<?= $this->blocs->id_bloc ?>" class="thickbox">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $e['name'] ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/blocs/elements/<?= $this->blocs->id_bloc ?>/delete/<?= $e['id_element'] ?>" title="Supprimer <?= $e['name'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $e['name'] ?> ?')">
                                <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $e['name'] ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Il n'y a aucun élément pour le moment.</p>
    <?php endif; ?>
</div>
