<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {3: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <h1>Liste des requêtes</h1>
    <?php if (\users_types::TYPE_ADMIN == $_SESSION['user']['id_user_type']) : ?>
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/queries/add" class="btn_link thickbox">Ajouter une requête</a>
        </div>
    <?php endif; ?>
    <?php if (count($this->lRequetes) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Dernière exécution</th>
                    <th>Nombre d'exécutions</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lRequetes as $r) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $r['name'] ?></td>
                        <td><?= ($r['executed'] != '0000-00-00 00:00:00' ? $this->dates->formatDate($r['executed'], 'd/m/Y H:i:s') : 'Jamais') ?></td>
                        <td><?= $r['executions'] ?></td>
                        <td align="center">
                            <?php if (strrchr($r['sql'], '@')) : ?>
                                <a href="<?= $this->lurl ?>/queries/params/<?= $r['id_query'] ?>" class="thickbox">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Renseigner les paramètres"/>
                                </a>
                                <a href="<?= $this->lurl ?>/queries/params/<?= $r['id_query'] ?>/export" class="thickbox">
                                    <img src="<?= $this->surl ?>/images/admin/xls.png" alt="Export Brut"/>
                                </a>
                            <?php else : ?>
                                <a href="<?= $this->lurl ?>/queries/execute/<?= $r['id_query'] ?>" title="Voir le résultat">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir le résultat"/>
                                </a>
                                <a href="<?= $this->lurl ?>/queries/export/<?= $r['id_query'] ?>" target="_blank" title="Export Brut">
                                    <img src="<?= $this->surl ?>/images/admin/xls.png" alt="Export Brut"/>
                                </a>

                            <?php endif; ?>
                            <?php if (\users_types::TYPE_ADMIN == $_SESSION['user']['id_user_type']) : ?>
                                <a href="<?= $this->lurl ?>/queries/edit/<?= $r['id_query'] ?>" class="thickbox">
                                    <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $r['name'] ?>"/>
                                </a>
                                <a href="<?= $this->lurl ?>/queries/delete/<?= $r['id_query'] ?>" title="Supprimer <?= $r['name'] ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $r['name'] ?> ?')">
                                    <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer <?= $r['name'] ?>"/>
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
        <p>Il n'y a aucune requête pour le moment.</p>
    <?php endif; ?>
</div>
