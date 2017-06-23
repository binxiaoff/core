<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {2: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Liste des templates</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->lurl ?>/templates/add" class="btn-primary pull-right thickbox">Ajouter un template</a>
        </div>
    </div>
    <?php if (count($this->lTemplate) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Nom du template</th>
                    <th>Fichier</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lTemplate as $t) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $t['name'] ?></td>
                        <td>
                            <?php if (file_exists($this->path . 'apps/default/views/templates/' . $t['slug'] . '.php')) : ?>
                                <?= $t['slug'] ?>.php
                            <?php else : ?>
                                Pas de fichier
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/templates/affichage/<?= $t['id_template'] ?>/<?= $t['affichage'] ?>" title="<?= ($t['affichage'] == 0 ? 'Bloquer le template' : 'Debloquer le template') ?>">
                                <img src="<?= $this->surl ?>/images/admin/<?= ($t['affichage'] == 0 ? 'unlock' : 'lock') ?>.png" alt="<?= ($t['affichage'] == 0 ? 'Bloquer le template' : 'Debloquer le template') ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/templates/status/<?= $t['id_template'] ?>/<?= $t['status'] ?>" title="<?= ($t['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                <img src="<?= $this->surl ?>/images/admin/<?= ($t['status'] == 1 ? 'offline' : 'online') ?>.png" alt="<?= ($t['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/templates/edit/<?= $t['id_template'] ?>" class="thickbox">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $t['name'] ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/templates/elements/<?= $t['id_template'] ?>" title="Liste des &eacute;l&eacute;ments du template <?= $t['name'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/database.png" alt="Liste des &eacute;l&eacute;ments du template <?= $t['name'] ?>"/>
                            </a>
                            <a href="<?= $this->lurl ?>/templates/delete/<?= $t['id_template'] ?>" title="Supprimer <?= $t['name'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $t['name'] ?> ?')">
                                <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $t['name'] ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') : ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php else : ?>
        <p>Il n'y a aucun template pour le moment.</p>
    <?php endif; ?>
</div>
