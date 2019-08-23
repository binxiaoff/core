<script type="text/javascript">
    $(function () {
        $('.tablesorter').tablesorter()

        <?php if (false === empty($this->query->getPaging()) && count($this->result) > $this->query->getPaging()) : ?>
            $('.tablesorter').tablesorterPager({
                container: $('#pager'),
                positionFixed: false,
                size: <?= $this->query->getPaging() ?>
            });
        <?php endif; ?>
    });
</script>
<form method="post" id="formQuery" action="<?= $this->url ?>/queries/export/<?= $this->params[0] ?>" target="_blank">
    <?php foreach ($this->sqlParams as $param) : ?>
        <input type="hidden" name="<?= 'param_' . str_replace('@', '', $param[0]) ?>" value="<?= $_POST[ 'param_' . str_replace('@', '', $param[0]) ] ?>"/>
    <?php endforeach; ?>
</form>
<div id="contenu">
    <h1><?= $this->query->getName() ?></h1>
    <h2><?= count($this->result) ?> résultat<?= count($this->result) > 1 ? 's' : '' ?></h2>
    <div class="btnDroite">
        <a onclick="document.getElementById('formQuery').submit(); return false;" class="btn_link">Export</a>
    </div>
    <?php if (count($this->result) > 0) : ?>
        <table class="tablesorter">
            <?php $i = 1; ?>
            <?php foreach ($this->result as $res) : ?>
                <?php if (1 === $i) : ?>
                    <thead>
                        <tr>
                            <?php foreach ($res as $key => $line) : ?>
                                <th><?= $key ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                <?php endif; ?>
                <tr<?= ($i++ % 2 === 1 ? '' : ' class="odd"') ?>>
                    <?php foreach ($res as $key => $line) : ?>
                        <td><?= $line ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (false === empty($this->query->getPaging()) && count($this->result) > $this->query->getPaging()) : ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->url ?>/images/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->url ?>/images/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
                        <img src="<?= $this->url ?>/images/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->url ?>/images/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->query->getPaging() ?>" selected="selected"><?= $this->query->getPaging() ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php else : ?>
        <p>Il n'y a aucun résultat pour cette requête.</p>
    <?php endif; ?>
</div>
