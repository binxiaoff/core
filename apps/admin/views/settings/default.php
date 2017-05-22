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
        <div class="col-sm-6">
            <h1>Liste des paramètres globaux</h1>
        </div>
        <div class="col-sm-6">
            <a href="<?= $this->lurl ?>/settings/add" class="btn-primary pull-right thickbox">Ajouter un paramètre</a>
        </div>
    </div>
    <?php if (count($this->lSettings) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th style="width: 340px">Type</th>
                    <th style="width: 700px;">Valeur</th>
                    <th style="width: 120px">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lSettings as $s) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $s['type'] ?></td>
                        <td style="overflow: auto; max-width: 700px;"><?= $s['value'] ?></td>
                        <td align="center">
                            <?php if ($s['status'] != \settings::STATUS_BLOCKED) : ?>
                                <a href="<?= $this->lurl ?>/settings/status/<?= $s['id_setting'] ?>/<?= $s['status'] ?>" title="<?= ($s['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>">
                                    <img src="<?= $this->surl ?>/images/admin/<?= ($s['status'] == 1 ? 'offline' : 'online') ?>.png" alt="<?= ($s['status'] == 1 ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>
                                </a>
                                <a href="<?= $this->lurl ?>/settings/edit/<?= $s['id_setting'] ?>" class="thickbox">
                                    <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $s['type'] ?>"/>
                                </a>
                                <a href="<?= $this->lurl ?>/settings/delete/<?= $s['id_setting'] ?>" title="Supprimer <?= $s['type'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $s['type'] ?> ?')">
                                    <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $s['type'] ?>"/>
                                </a>
                            <?php else : ?>
                                <a href="<?= $this->lurl ?>/settings/edit/<?= $s['id_setting'] ?>" class="thickbox">
                                    <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $s['type'] ?>"/>
                                </a>
                            <?php endif; ?>
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
        <p>Il n'y a aucun paramètre pour le moment.</p>
    <?php endif; ?>
</div>
