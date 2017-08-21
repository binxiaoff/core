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
        <table class="table table-striped table-hover tablesorter">
            <thead>
            <tr>
                <th style="width: 12%">Date</th >
                <th colspan="2">Fichier</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->reportingList as $sortDate => $reporting) : ?>
                <tr>
                    <td data-sort-value="<?= $sortDate ?>"><?= $reporting['displayDate'] ?></td>
                    <td><?= $reporting['name'] ?></td>
                    <td class="text-right"><a href="<?= $reporting['link'] ?>" class="btn-primary btn-sm">Télécharger</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        Aucun fichier trouvé.
    <?php endif ?>
</div>
