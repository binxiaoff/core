<div id="contenu">
    <h1>Gestion des règles de vigilance</h1>
    <?php if (count($this->vigilanceRuleList) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>ID Règle</th>
                <th>Nom</th>
                <th>Label</th>
                <th>Statut de vigilance</th>
<!--                <th>Modifier</th>-->
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->vigilanceRuleList as $rule) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <?php
                    switch ($rule['status']) {
                        case \vigilance_status::STATUS_INTERMEDIATE:
                            $status = 'Surveillance intermediare';
                            break;
                        case \vigilance_status::STATUS_REINFORCED:
                            $status = 'Surveillance renforcée';
                            break;
                        case \vigilance_status::STATUS_AGREEMENT_REQUIRED:
                            $status = 'Accord SFPMEI requis';
                            break;
                    }
                    ?>
                    <td><?= $rule['id_rule'] ?></td>
                    <td><?= $rule['name'] ?></td>
                    <td><?= $rule['label'] ?></td>
                    <td><?= $status ?></td>
<!--                    <td>-->
<!--                        <a href="/vigilance_rules/editRule/--><?//= $rule['id_rule'] ?><!--" title="Consulter">-->
<!--                            <img src="--><?//= $this->surl ?><!--/images/admin/modif.png" alt="Consulter" />-->
<!--                        </a>-->
<!--                    </td>-->
                </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Il n'y a aucune règle pour le moment.</p>
    <?php endif; ?>
<!--    <label>Ajouter une nouvelle règle</label>-->
<!--    <input type="button" id="add_rule">-->
    <h1>Gestion des détails des règles de vigilance</h1>
    <?php if (count($this->vigilanceRuleDetailList) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Action</th>
                <th>Période</th>
                <th>Nombre</th>
                <th>Type du nombre</th>
                <th>Opérateur de comparaison</th>
                <th>Type de client</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->vigilanceRuleDetailList as $ruleDetail) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $ruleDetail['action'] ?></td>
                    <td><?= $ruleDetail['period'] ?></td>
                    <td><?= $ruleDetail['number'] ?></td>
                    <td><?= $ruleDetail['number_type'] ?></td>
                    <td><?= $ruleDetail['operator'] ?></td>
                    <td><?= $ruleDetail['client_type'] ?></td>
<!--                    <td>-->
<!--                        <a href="/vigilance_rules/editRuleDetail/--><?//= $ruleDetail['id_rule_detail'] ?><!--" title="Consulter">-->
<!--                            <img src="--><?//= $this->surl ?><!--/images/admin/modif.png" alt="Consulter" />-->
<!--                        </a>-->
<!--                    </td>-->
                </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Il n'y a aucun détail de règle pour le moment.</p>
    <?php endif; ?>
<!--    <label>Ajouter un nouveau détail de règle</label>-->
<!--    <input type="button" id="add_rule_detail">-->
</div>
