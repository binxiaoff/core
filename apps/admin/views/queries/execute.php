<script type="text/javascript">
    $(function () {
        $(".tablesorter").tablesorter();

        <?php if (false === empty($this->queries->paging)): ?>
            $(".tablesorter").tablesorterPager({
                container: $("#pager"),
                positionFixed: false,
                size: <?= $this->queries->paging ?>
            });
        <?php endif; ?>
    });
</script>
<form method="post" id="formQuery" action="<?= $this->lurl ?>/queries/excel/<?= $this->params[0] ?>" target="_blank">
    <?php foreach ($this->sqlParams as $param): ?>
        <input type="hidden" name="<?= 'param_' . str_replace('@', '', $param[0]) ?>" value="<?= $_POST[ 'param_' . str_replace('@', '', $param[0]) ] ?>"/>
    <?php endforeach; ?>
</form>
<form method="post" id="formQueryBrute" action="<?= $this->lurl ?>/queries/export/<?= $this->params[0] ?>" target="_blank">
    <?php foreach ($this->sqlParams as $param): ?>
        <input type="hidden" name="<?= 'param_' . str_replace('@', '', $param[0]) ?>" value="<?= $_POST[ 'param_' . str_replace('@', '', $param[0]) ] ?>"/>
    <?php endforeach; ?>
</form>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/queries" title="Stats">Stats</a> -</li>
        <li><a href="<?= $this->lurl ?>/queries" title="Stats">Requêtes</a> -</li>
        <li><?= $this->queries->name ?></li>
    </ul>
    <h1><?= $this->queries->name ?></h1>

    <div class="btnDroite">
        <a onclick="document.getElementById('formQuery').submit();return false;" class="btn_link">Exporter vers
            Excel</a>
        <a onclick="document.getElementById('formQueryBrute').submit();return false;" class="btn_link">Export brut</a>
    </div>

    <?php if (count($this->result) > 0): ?>
        <table class="tablesorter">
            <?php $i = 1; ?>
            <?php foreach ($this->result as $res): ?>
                <?php if ($i == 1): ?>
                    <thead>
                        <tr>
                            <?php foreach ($res as $key => $line): ?>
                                <th><?= $key ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                <?php endif; ?>
                <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?>>
                    <?php foreach ($res as $key => $line): ?>
                        <td><?= $line ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (false === empty($this->queries->paging)): ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->queries->paging ?>" selected="selected"><?= $this->queries->paging ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <p>Il n'y a aucun résultat pour cette requête.</p>
    <?php endif; ?>
</div>
