<script type="text/javascript">
    $(function() {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".tablesorter").tablesorter({headers: {5: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $(".inline").colorbox({inline: true, width: "50%"});

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Liste des mouvements non affectés</h1>
    <table class="tablesorter">
        <thead>
            <tr>
                <th>ID</th>
                <th>Motif</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Date</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->aOperations as $aOperation) : ?>
                <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $aOperation['id_reception'] ?></td>
                    <td><?= $aOperation['motif'] ?></td>
                    <td><?= $this->ficelle->formatNumber($aOperation['montant'] / 100) ?> €</td>
                    <td class="statut_operation_<?= $aOperation['id_reception'] ?>"><?= $this->statusOperations[$aOperation['status_bo']] ?></td>
                    <td><?= date('d/m/Y', strtotime($aOperation['added'])) ?></td>
                    <td align="center">
                        <a class="thickbox ajouter_<?= $aOperation['id_reception'] ?>" href="<?= $this->lurl ?>/transferts/attribution/<?= $aOperation['id_reception'] ?>"><img src="<?= $this->surl ?>/images/admin/edit.png" alt="Attribuer"/></a>
                        <a class="inline" href="#inline_content_<?= $aOperation['id_reception'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="Afficher la ligne de réception"/></a>
                        <div style="display:none;">
                            <div id="inline_content_<?= $aOperation['id_reception'] ?>" style="white-space: nowrap; padding:10px; background:#fff;"><?= $aOperation['ligne'] ?></div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($this->nb_lignes != '') : ?>
        <table>
            <tr>
                <td id="pager">
                    <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                    <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                    <input type="text" class="pagedisplay"/>
                    <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                    <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                    <select class="pagesize">
                        <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                    </select>
                </td>
            </tr>
        </table>
    <?php endif; ?>
</div>
