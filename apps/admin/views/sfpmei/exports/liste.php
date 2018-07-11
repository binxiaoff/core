<link href="<?= $this->lurl ?>/oneui/css/font-awesome.css" type="text/css" rel="stylesheet">
<style>
    @font-face {
        font-family: 'FontAwesome';
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot');
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot?#iefix&v=4.7.0') format('embedded-opentype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff2') format('woff2'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff') format('woff'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.ttf') format('truetype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.svg#fontawesomeregular') format('svg');
        font-weight: normal;
        font-style: normal;
    }
</style>

<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <h1>Exports</h1>
            </div>
        </div>
        <?php if (count($this->queriesList) > 0) : ?>
            <table class="tablesorter table table-hover table-striped">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th colspan="3">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->queriesList as $query) : ?>
                    <tr>
                        <td><?= $query['name'] ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/sfpmei/exports/html/<?= $query['id_query'] ?>">
                                <span class="fa fa-table"></span>
                            </a>
                        </td>
                        <td>
                            <a href="<?= $this->lurl ?>/sfpmei/exports/xls/<?= $query['id_query'] ?>">
                                <span class="fa fa-file-excel-o"></span>
                            </a>
                        </td>
                        <td>
                            <a href="<?= $this->lurl ?>/sfpmei/exports/csv/<?= $query['id_query'] ?>">
                                <span class="fa fa-file"></span>
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
                            <option value="<?= $this->pagination ?>" selected><?= $this->pagination ?></option>
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
        $('.tablesorter').tablesorter({headers: {1: {sorter: false}}});

        <?php if (count($this->queriesList) > $this->pagination) : ?>
            $('.tablesorter').tablesorterPager({
                container: $('#pagination'),
                positionFixed: false,
                size: <?= $this->pagination ?>});
        <?php endif; ?>
    });
</script>
