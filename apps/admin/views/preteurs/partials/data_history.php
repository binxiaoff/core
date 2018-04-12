<style>
    #data-history-table tbody th {
        background-color: #6d1f4f;
        border-left: 1px solid #6d1f4f;
        border-right: 1px solid #6d1f4f;
        color: #fff;
        font-weight: normal;
    }
</style>
<h2>Historique des données personnelles</h2>
<table id="data-history-table" class="table">
    <thead>
    <tr>
        <th>Donnée</th>
        <th>Valeur d'origine</th>
        <th>Nouvelle valeur</th>
        <th>Utilisateur</th>
    </tr>
    </thead>
    <tbody>
        <?php foreach ($this->dataHistory as $timestamp => $timeHistory) : ?>
            <tr>
                <th colspan="4">
                    <?= $timeHistory[0]['date']->format('d/m/Y H\hi') ?>
                </th>
            </tr>
            <?php foreach ($timeHistory as $dataHistory) : ?>
                <tr>
                    <td><?= $dataHistory['name'] ?></td>
                    <td><?= $dataHistory['old'] ?></td>
                    <td><?= $dataHistory['new'] ?></td>
                    <td>
                        <?php if (null !== $dataHistory['user']) : ?>
                            <?= $dataHistory['user']->getFirstname() ?> <?= $dataHistory['user']->getName() ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
</table>
