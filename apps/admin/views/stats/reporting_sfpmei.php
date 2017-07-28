<div id="contenu">
    <h1>Reporting Mensuel SFPMEI</h1>
    <?php if (0 < count($this->reportingList)) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Annee</th>
                <th>Mois</th>
                <th>Lien de téléchargement</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->reportingList as $year => $reportings) : ?>
                <?php foreach ($reportings as $index => $reporting) : ?>
                    <tr <?= ($index % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $year ?></td>
                        <td><?= $reporting['month'] ?></td>
                        <td><a title="Télécharger" href="<?= $reporting['link'] ?>"><?= $reporting['name'] ?></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        Aucun fichier trouvé.
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Annee</th>
                <th>Mois</th>
                <th>Lien de téléchargement</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>2017</td>
                <td>mars</td>
                <td><a title="Télécharger" href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_20170331.xlsx">reporting_mensuel_sfpmei_20170331.xlsx</a></td>
            </tr>
            <tr class="odd">
                <td>2017</td>
                <td>avril</td>
                <td><a title="Télécharger" href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_20170405.xlsx">reporting_mensuel_sfpmei_20170405.xlsx</a></td>
            </tr>
            <tr class="odd">
                <td>2017</td>
                <td>juin</td>
                <td><a title="Télécharger" href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_comp_20170630.xlsx">reporting_mensuel_sfpmei_comp_20170630.xlsx</a></td>
            </tr>
            <tr>
                <td>2017</td>
                <td>juillet</td>
                <td><a title="Télécharger" href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_20170727.xlsx">reporting_mensuel_sfpmei_20170727.xlsx</a></td>
            </tr>
            </tbody>
        </table>
    <?php endif ?>
</div>
