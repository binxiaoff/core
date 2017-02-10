<?php if (empty($this->templateProjects['count'])) : ?>
    <h2>Aucun projet en cours</h2>
<?php else : ?>
    <table class="tablesorter projects">
        <thead>
        <tr>
            <th style="width:55px">ID</th>
            <th>Raison sociale</th>
            <th style="width:75px">Montant</th>
            <th style="width:75px">Durée</th>
            <th style="width:175px">Nom dirigeant</th>
            <th style="width:90px">Téléphone</th>
            <th style="width:120px">Création</th>
            <th style="width:120px">Passage à l'analyse</th>
            <th style="width:65px">Dernier<br/>mémo</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->templateProjects['projects'] as $statusProjects) : ?>
            <tr class="status-line expand">
                <td colspan="9"><span class="sign"></span> <?= $statusProjects['label'] ?> (<?= $statusProjects['count'] ?>)</td>
            </tr>
            <?php $i = 0; ?>
            <?php foreach ($statusProjects['projects'] as $project) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> data-project="<?= $project['id_project'] ?>">
                    <td><?= $project['id_project'] ?></td>
                    <td><?= $project['company_name'] ?></td>
                    <td style="text-align:right"><?= $this->ficelle->formatNumber($project['amount'], 0) ?>&nbsp;€</td>
                    <td><?= $project['duration'] ?> mois</td>
                    <td><?= $project['client_name'] ?></td>
                    <td><?= $project['client_phone'] ?></td>
                    <td><?= $project['creation']->format('d/m/Y - H\hi') ?></td>
                    <td data-toggle="tooltip" class="tooltip<?php if ($project['risk_status_duration'] > 48) : ?> warning<?php endif; ?>" title="<?= $project['risk_status_datetime']->format('d/m/Y - H\hi') ?>"><?= $this->ficelle->formatNumber($project['risk_status_duration'], 0) ?> heures</td>
                    <?php if (empty($project['memo'])) : ?>
                        <td></td>
                    <?php else : ?>
                        <td data-toggle="tooltip" class="tooltip" title="<?= nl2br(htmlentities($project['memo'], ENT_QUOTES)) ?>" style="text-align: center"><img src="<?= $this->surl ?>/images/admin/info.png" alt="Mémo" /></td>
                    <?php endif; ?>
                </tr>
                <?php ++$i; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
