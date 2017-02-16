<?php if (empty($this->templateProjects['count'])) : ?>
    <h2>Aucun projet en cours</h2>
<?php else : ?>
    <table class="tablesorter projects">
        <thead>
        <tr>
            <th style="width:50px">ID</th>
            <th>Raison sociale</th>
            <th style="width:70px">Montant</th>
            <th style="width:70px">Durée</th>
            <th style="width:150px">Nom dirigeant</th>
            <th style="width:80px">Téléphone</th>
            <th style="width:110px">Création</th>
            <?php if ($this->templateProjects['assignee']) : ?>
                <th style="width:150px">Commercial</th>
            <?php endif; ?>
            <th style="width:50px">Dernier<br/>mémo</th>
            <th style="width:50px">Priorité</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->templateProjects['projects'] as $status => $statusProjects) : ?>
            <tr class="status-line<?php if (false === in_array($status, $this->collapsedStatus)) : ?> expand<?php endif; ?>">
                <td colspan="10"><span class="sign"></span> <?= $statusProjects['label'] ?> (<?= $statusProjects['count'] ?>)</td>
            </tr>
            <?php $i = 0; ?>
            <?php foreach ($statusProjects['projects'] as $project) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?><?php if (in_array($status, $this->collapsedStatus)) : ?> style="display: none;"<?php endif; ?>>
                    <td data-project="<?= $project['id_project'] ?>"><?= $project['id_project'] ?></td>
                    <td data-project="<?= $project['id_project'] ?>"><?= $project['company_name'] ?></td>
                    <td style="text-align:right"><?= $this->ficelle->formatNumber($project['amount'], 0) ?>&nbsp;€</td>
                    <td><?= $project['duration'] ?> mois</td>
                    <td><?= $project['client_name'] ?></td>
                    <td><a href="tel:<?= $project['client_phone'] ?>"><?= $project['client_phone'] ?></a></td>
                    <td><?= $project['creation']->format('d/m/Y - H\hi') ?></td>
                    <?php if ($this->templateProjects['assignee']) : ?>
                        <td><?= $project['assignee'] ?></td>
                    <?php endif; ?>
                    <?php if (empty($project['memo_content'])) : ?>
                        <td></td>
                    <?php else : ?>
                        <td data-toggle="tooltip" class="tooltip" title="<?= $project['memo_author'] . '<br>' . $project['memo_datetime']->format('d/m/Y - H\hi') . '<hr>' . nl2br(htmlentities($project['memo_content'], ENT_QUOTES)) ?>" style="text-align: center"><img src="<?= $this->surl ?>/images/admin/info.png" alt="Mémo" /></td>
                    <?php endif; ?>
                    <td style="text-align: center"><?= 10 == $project['priority'] ? '' : $project['priority'] ?></td>
                </tr>
                <?php ++$i; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>