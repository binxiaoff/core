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
            <h1>Liste des blocs</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->url ?>/blocs/add" class="btn-primary pull-right thickbox">Ajouter un bloc</a>
        </div>
    </div>
    <?php if (count($this->lBlocs) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Nom du bloc</th>
                    <th>Fichier</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lBlocs as $b) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $b['name'] ?></td>
                        <td>
                            <?php if (file_exists($this->path . 'apps/default/views/blocs/' . $b['slug'] . '.php')) : ?>
                                <?= $b['slug'] ?>.php
                            <?php else : ?>
                                Pas de fichier
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <a href="<?= $this->url ?>/blocs/status/<?= $b['id_bloc'] ?>/<?= $b['status'] ?>" title="<?= ($b['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                <img src="<?= $this->url ?>/images/<?= ($b['status'] == 1 ? 'offline' : 'online') ?>.png" alt="<?= ($b['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>
                            </a>
                            <a href="<?= $this->url ?>/blocs/edit/<?= $b['id_bloc'] ?>" class="thickbox">
                                <img src="<?= $this->url ?>/images/edit.png" alt="Modifier <?= $b['name'] ?>"/>
                            </a>
                            <a href="<?= $this->url ?>/blocs/elements/<?= $b['id_bloc'] ?>" title="Liste des éléments du bloc <?= $b['name'] ?>">
                                <img src="<?= $this->url ?>/images/database.png" alt="Liste des éléments du bloc <?= $b['name'] ?>"/>
                            </a>
                            <a href="<?= $this->url ?>/blocs/modifier/<?= $b['id_bloc'] ?>" title="Edition des éléments du bloc <?= $b['name'] ?>">
                                <img src="<?= $this->url ?>/images/modif.png" alt="Edition des éléments du bloc <?= $b['name'] ?>"/>
                            </a>
                            <a href="<?= $this->url ?>/blocs/delete/<?= $b['id_bloc'] ?>" title="Supprimer <?= $b['name'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $b['name'] ?> ?')">
                                <img src="<?= $this->url ?>/images/delete.png" alt="Supprimer <?= $b['name'] ?>"/>
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
                        <img src="<?= $this->url ?>/images/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->url ?>/images/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
                        <img src="<?= $this->url ?>/images/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->url ?>/images/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php else : ?>
        <p>Il n'y a aucun bloc pour le moment.</p>
    <?php endif; ?>
</div>
