<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <h1>Liste des requêtes</h1>
            </div>
        </div>
        <?php if (count($this->queriesList) > 0) : ?>
            <table class="tablesorter table table-hover table-striped">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Nombre d'exécutions</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->queriesList as $r) : ?>
                    <tr>
                        <td><?= $r['name'] ?></td>
                        <td><?= ($r['executed'] != '0000-00-00 00:00:00') ? \DateTime::createFromFormat('Y-m-d H:i:s', $r['executed'])->format('d/m/Y H:i:s') : 'Jamais' ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/sfpmei/requetes/<?= $r['id_query'] ?>" title="Voir le résultat">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir le résultat"/>
                            </a>
                            <a href="<?= $this->lurl ?>/sfpmei/requetes/export/<?= $r['id_query'] ?>" target="_blank" title="Export Brut">
                                <img src="<?= $this->surl ?>/images/admin/xls.png" alt="Export Brut"/>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($this->queriesList) > $this->pagination) : ?>
                <div id="pagination" class="row">
                    <div class="col-md-12 text-center">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                        <input type="text" class="pagedisplay input_court text-center" title="Page" disabled>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                        <select class="pagesize sr-only" title="Page">
                            <option value="<?= $this->pagination ?>" selected="selected"><?= $this->pagination ?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <p>Il n'y a aucune requête pour le moment.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    $(function () {
        jQuery.tablesorter.addParser({
            id: 'date',
            type: 'numeric',
            is: function (s) {
                return /[0-9]{2}\/[0-9]{2}\/[0-9]{4}/.test(s);
            },
            format: function (s) {
                var match = s.match(/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/)
                if (match !== null && match.length >= 3) {
                    return match[3] + match[2] + match[1];
                }
                return '';
            }
        });

        $('.tablesorter').tablesorter({headers: {1: {sorter: 'date'}, 3: {sorter: false}}});

        <?php if (count($this->queriesList) > $this->pagination) : ?>
        $('.tablesorter').tablesorterPager({
            container: $('#pagination'),
            positionFixed: false,
            size: <?= $this->pagination ?>});
        <?php endif; ?>
    });
</script>
