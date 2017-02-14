<div id="contenu">
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/templates/addBloc/<?= $this->templates->id_template ?>" class="btn_link thickbox">Ajouter un bloc</a>
    </div>
    <?php foreach ($this->lPositions as $p) : ?>
        <?php $this->lBlocs = $this->blocs_templates->select('id_template = "' . $this->templates->id_template . '" AND position = "' . $p . '"', 'ordre ASC'); ?>
        <?php if (count($this->lBlocs) > 0) : ?>
            <h1>Liste des blocs du template pour la position <?= $p ?></h1>
            <table class="tablesorter">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Slug</th>
                        <th>Position</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($this->lBlocs as $b) : ?>
                        <?php $this->blocs->get($b['id_bloc']); ?>
                        <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                            <td><?= $this->blocs->name ?></td>
                            <td><?= $this->blocs->slug ?></td>
                            <td align="center">
                                <?php if (count($this->lBlocs) > 1) : ?>
                                    <?php if ($b['ordre'] > 0) : ?>
                                        <a href="<?= $this->lurl ?>/templates/elements/<?= $this->templates->id_template ?>/upBloc/<?= $p ?>/<?= $b['id_bloc'] ?>" title="Remonter"><img src="<?= $this->surl ?>/images/admin/up.png" alt="Remonter"/></a>
                                    <?php endif; ?>

                                    <?php if ($b['ordre'] < (count($this->lBlocs) - 1)) : ?>
                                        <a href="<?= $this->lurl ?>/templates/elements/<?= $this->templates->id_template ?>/downBloc/<?= $p ?>/<?= $b['id_bloc'] ?>" title="Descendre"><img src="<?= $this->surl ?>/images/admin/down.png" alt="Descendre"/></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td align="center">
                                <a href="<?= $this->lurl ?>/templates/elements/<?= $this->templates->id_template ?>/statusBloc/<?= $b['id'] ?>/<?= $b['status'] ?>" title="<?= ($b['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                    <img src="<?= $this->surl ?>/images/admin/<?= ($b['status'] == 1 ? 'offline' : 'online') ?>.png" alt="<?= ($b['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>
                                </a>
                                <a href="<?= $this->lurl ?>/templates/elements/<?= $this->templates->id_template ?>/deleteBloc/<?= $b['id'] ?>" title="Supprimer <?= $this->blocs->name ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $this->blocs->name ?>?')">
                                    <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $this->blocs->name ?>"/>
                                </a>
                            </td>
                        </tr>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br/>
        <?php endif; ?>
    <?php endforeach; ?>
    <br/>
    <h1>Liste des éléments du template <?= $this->templates->name ?></h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/templates/addElement/<?= $this->templates->id_template ?>" class="btn_link thickbox">Ajouter un élément</a>
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
                                    <a href="<?= $this->lurl ?>/templates/elements/<?= $this->templates->id_template ?>/up/<?= $e['id_element'] ?>" title="Remonter">
                                        <img src="<?= $this->surl ?>/images/admin/up.png" alt="Remonter"/>
                                    </a>
                                <?php endif; ?>
                                <?php if ($e['ordre'] < (count($this->lElements) - 1)) : ?>
                                    <a href="<?= $this->lurl ?>/templates/elements/<?= $this->templates->id_template ?>/down/<?= $e['id_element'] ?>" title="Descendre">
                                        <img src="<?= $this->surl ?>/images/admin/down.png" alt="Descendre"/>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/templates/elements/<?= $this->templates->id_template ?>/status/<?= $e['id_element'] ?>/<?= $e['status'] ?>" title="<?= ($e['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                <img src="<?= $this->surl ?>/images/admin/<?= ($e['status'] == 1 ? 'offline' : 'online') ?>.png" alt="<?= ($e['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/templates/editElement/<?= $e['id_element'] ?>/<?= $this->templates->id_template ?>" class="thickbox">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $e['name'] ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/templates/elements/<?= $this->templates->id_template ?>/delete/<?= $e['id_element'] ?>" title="Supprimer <?= $e['name'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $e['name'] ?> ?')">
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
