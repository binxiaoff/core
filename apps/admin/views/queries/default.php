<script type="text/javascript">
    $(function() {
        $('.tablesorter').tablesorter({headers: {3: {sorter: false}}})

        <?php if (count($this->queries) > $this->maxTableRows) : ?>
            $('.tablesorter').tablesorterPager({
                container: $('#pager'),
                positionFixed: false,
                size: <?= $this->maxTableRows ?>
            });
        <?php endif; ?>
    });
</script>

<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Liste des requêtes</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->url ?>/queries/add" class="btn-primary pull-right thickbox">Ajouter une requête</a>
        </div>
    </div>
    <?php if (count($this->queries) > 0) : ?>
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
                <?php /** @var \Unilend\Entity\Queries $query */ ?>
                <?php foreach ($this->queries as $query) : ?>
                    <tr<?= ($i % 2 === 1 ? '' : ' class="odd"') ?>>
                        <td><?= $query->getName() ?></td>
                        <td><?= $query->getExecuted() ? $query->getExecuted()->format('d/m/Y H:i:s') : 'Jamais' ?></td>
                        <td><?= $query->getExecutions() ?></td>
                        <td align="center">
                            <?php if (strrchr($query->getQuery(), '@')) : ?>
                                <a href="<?= $this->url ?>/queries/params/<?= $query->getIdQuery() ?>" class="thickbox">
                                    <img src="<?= $this->url ?>/images/modif.png" alt="Renseigner les paramètres"/>
                                </a>
                                <a href="<?= $this->url ?>/queries/params/<?= $query->getIdQuery() ?>/export" class="thickbox">
                                    <img src="<?= $this->url ?>/images/xls.png" alt="Export Brut"/>
                                </a>
                            <?php else : ?>
                                <a href="<?= $this->url ?>/queries/execute/<?= $query->getIdQuery() ?>" title="Voir le résultat">
                                    <img src="<?= $this->url ?>/images/modif.png" alt="Voir le résultat"/>
                                </a>
                                <a href="<?= $this->url ?>/queries/export/<?= $query->getIdQuery() ?>" target="_blank" title="Export Brut">
                                    <img src="<?= $this->url ?>/images/xls.png" alt="Export Brut"/>
                                </a>

                            <?php endif; ?>
                            <a href="<?= $this->url ?>/queries/edit/<?= $query->getIdQuery() ?>" class="thickbox">
                                <img src="<?= $this->url ?>/images/edit.png" alt="Modifier <?= $query->getName() ?>"/>
                            </a>
                            <a href="<?= $this->url ?>/queries/delete/<?= $query->getIdQuery() ?>" title="Supprimer <?= $query->getName() ?>" onclick="return confirm('Etes vous sur de vouloir supprimer <?= $query->getName() ?> ?')">
                                <img src="<?= $this->url ?>/images/delete.png" alt="Supprimer <?= $query->getName() ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($this->queries) > $this->maxTableRows) : ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->url ?>/images/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->url ?>/images/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
                        <img src="<?= $this->url ?>/images/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->url ?>/images/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->maxTableRows ?>" selected="selected"><?= $this->maxTableRows ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php else : ?>
        <p>Il n'y a aucune requête pour le moment.</p>
    <?php endif; ?>
</div>
