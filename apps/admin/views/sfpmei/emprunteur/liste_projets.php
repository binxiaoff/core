<div class="row">
    <div class="col-md-12">
        <?php if (count($this->projects) > 0) : ?>
            <table class="tablesorter table table-hover table-striped project-list">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Projet</th>
                    <th>statut</th>
                    <th>Montant</th>
                    <th>PDF</th>
                    <th>Factures</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->projects as $iIndex => $project) : ?>
                    <?php $this->projects_status->get($project['status'], 'status'); ?>
                    <tr<?= (++$iIndex % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $project['id_project'] ?></td>
                        <td><?= $project['title'] ?></td>
                        <td><?= $this->projects_status->label ?></td>
                        <td class="right"><?= $this->ficelle->formatNumber($project['amount'], 0) ?>&nbsp;€</td>
                        <td>
                            <?php if ($this->projects_pouvoir->get($project['id_project'], 'id_project')) : ?>
                                <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $this->projects_pouvoir->name ?>">POUVOIR</a>
                            <?php elseif ($project['status'] > \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::STATUS_FUNDED) : ?>
                                <a href="/emprunteurs/link_ligthbox/pouvoir/<?= $project['id_project'] ?>" class="thickbox cboxElement">POUVOIR</a>
                            <?php endif; ?>
                            &nbsp;&nbsp;
                            <?php if ($this->clients_mandats->get($this->clients->id_client, 'id_project = ' . $project['id_project'] . ' AND status = ' . \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED . ' AND id_client')) : ?>
                                <a href="<?= $this->lurl ?>/protected/mandats/<?= $this->clients_mandats->name ?>">MANDAT</a>
                            <?php elseif ($project['status'] > \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::STATUS_FUNDED) : ?>
                                <a href="/emprunteurs/link_ligthbox/mandat/<?= $project['id_project'] ?>" class="thickbox cboxElement">MANDAT</a>
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/sfpmei/emprunteur/<?= $this->clients->id_client ?>/factures/<?= $project['id_project'] ?>" class="thickbox cboxElement" target="_blank">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Factures"/>
                            </a>
                        </td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/sfpmei/projet/<?= $project['id_project'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Détails"/>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($this->projects) > $this->pagination) : ?>
                <div id="pagination" class="row">
                    <div class="col-md-12 text-center">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                        <input type="text" class="pagedisplay input_court text-center" title="Page" disabled>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                        <select class="pagesize sr-only" title="Page">
                            <option value="<?= $this->pagination ?>" selected><?= $this->pagination ?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <p>Aucun projet</p>
        <?php endif; ?>
    </div>
</div>

<script>
    $(function () {
        $('.project-list').tablesorter({
            headers: {
                3: {sorter: 'amount'}
            }
        });

        <?php if (count($this->projects) > $this->pagination) : ?>
        $('.project-list').tablesorterPager({
            container: $('#pagination'),
            positionFixed: false,
            size: <?= $this->pagination ?>});
        <?php endif; ?>
    })
</script>
