<script>
    $(function(){
        $.tablesorter.addParser({
            id: 'dataAttr',
            is: function(s) {
                return false;
            },
            format: function(s, table, cell) {
                return $(cell).attr('data-sort-value');
            },
            type: 'numeric'
        });

        $('.tablesorter').tablesorter({
            headers : {
                0 : { sorter: 'dataAttr' },
                2 : { sorter: false }
            }
        });
    })
</script>

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
                    <tr>
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
        <table class="table table-striped table-hover tablesorter">
            <thead>
            <tr>
                <th style="width: 12%">Date</th >
                <th colspan="2">Fichier</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td data-sort-value="201701">Janvier 2017</td>
                <td>reporting_mensuel_sfpmei_20170231.xlsx</td>
                <td class="text-right"><a href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_20170331.xlsx" class="btn-primary btn-sm">Télécharger</a></td>
            </tr>
            <tr>
                <td data-sort-value="201702">Fevrier 2017</td>
                <td>reporting_mensuel_sfpmei_20170631.xlsx</td>
                <td class="text-right"><a href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_20170331.xlsx" class="btn-primary btn-sm">Télécharger</a></td>
            </tr>
            <tr>
                <td data-sort-value="201601">Janvier 2016</td>
                <td>reporting_mensuel_sfpmei_20170331.xlsx</td>
                <td class="text-right"><a href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_20170331.xlsx" class="btn-primary btn-sm">Télécharger</a></td>
            </tr>
            <tr>
                <td data-sort-value="201602">Fevrier 2016</td>
                <td>reporting_mensuel_sfpmei_20170431.xlsx</td>
                <td class="text-right"><a href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_20170331.xlsx" class="btn-primary btn-sm">Télécharger</a></td>
            </tr>
            <tr>
                <td data-sort-value="201604">Avril 2016</td>
                <td>reporting_mensuel_sfpmei_20170331.xlsx</td>
                <td class="text-right"><a href="/stats/reporting_sfpmei/file/reporting_mensuel_sfpmei_20170331.xlsx" class="btn-primary btn-sm">Télécharger</a></td>
            </tr>
            </tbody>
        </table>
    <?php endif ?>
</div>
