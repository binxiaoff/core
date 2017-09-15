<div id="contenu">
    <div class="container-fluid">
        <h1><?= $this->queries->name ?></h1>
        <?php if (count($this->result) > 0) : ?>
            <table class="tablesorter  table table-hover table-striped">
                <?php $i = 1; ?>
                <?php foreach ($this->result as $res) : ?>
                    <?php if ($i++ == 1) : ?>
                    <thead>
                    <tr>
                        <?php foreach ($res as $key => $line) : ?>
                            <th><?= $key ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php endif; ?>
                    <tr>
                        <?php foreach ($res as $key => $line) : ?>
                            <td><?= $line ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (false === empty($this->queries->paging)) : ?>
                <table>
                    <tr>
                        <td id="pager">
                            <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                            <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                            <input type="text" class="pagedisplay">
                            <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                            <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                            <select class="pagesize">
                                <option value="<?= $this->queries->paging ?>" selected><?= $this->queries->paging ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>
        <?php else : ?>
            <p>Il n'y a aucun résultat pour cette requête.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    $(function () {
        $('.tablesorter').tablesorter();

        <?php if (false === empty($this->queries->paging)) : ?>
            $('.tablesorter').tablesorterPager({
                container: $("#pager"),
                positionFixed: false,
                size: <?= $this->queries->paging ?>
            });
        <?php endif; ?>
    });
</script>
