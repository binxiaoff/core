<div id="contenu">
    <div style="float: left; width: 350px;">
        <h1>Dossiers en cours</h1>
        <?php if (count($this->lStatus) > 0) : ?>
            <table class="tablesorter">
                <thead>
                    <tr>
                        <th align="center">Statut</th>
                        <th align="center">Résultats</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($this->lStatus as $s) : ?>
                        <?php $nbProjects = $this->projects->countSelectProjectsByStatus($s['status']); ?>
                        <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                            <td><a href="<?= $this->lurl ?>/dossiers/<?= $s['status'] ?>"><?= $s['label'] ?></a></td>
                            <td><?= $nbProjects ?></td>
                        </tr>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Il n'y a aucun statut pour le moment.</p>
        <?php endif; ?>
    </div>
    <div style="float: right; width: 790px;">
        <h1><?= count($this->lProjectsNok) ?> incidents de remboursement :</h1>
        <?php if (count($this->lProjectsNok) > 0) : ?>
            <table class="tablesorter">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Titre</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($this->lProjectsNok as $p) : ?>
                        <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                            <td><?= $p['id_project'] ?></td>
                            <td><?= $p['title_bo'] ?></td>
                            <td><?= $p['amount'] ?></td>
                            <td><?= $this->projects_status->getLabel($p['status']) ?></td>
                            <td align="center">
                                <a href="<?= $this->lurl ?>/dossiers/edit/<?= $p['id_project'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir le dossier" title="Voir le dossier"/>
                                </a>
                            </td>
                        </tr>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Il n'y a aucune incidence de remboursement pour le moment.</p>
        <?php endif; ?>
    </div>
    <div style="clear: both;"></div>
</div>
