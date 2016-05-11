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

        <?php if ($this->nb_lignes != ''): ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $(".inline").colorbox({inline: true, width: "50%"});

        <?php if (isset($_SESSION['freeow'])): ?>
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
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/transferts">Déblocage</a> -</li>
        <li>Déblocage de fonds</li>
    </ul>
    <h1>Liste des fonds non débloqués à contrôler</h1>
    <table class="tablesorter">
        <thead>
        <tr>
            <th>ID du dossier</th>
            <th>Nom du projet</th>
            <th>Montant</th>
            <th>BIC</th>
            <th>Iban</th>
            <th>Kbis</th>
            <th>Pouvoir</th>
            <th>Mandat</th>
            <th>Déblocage</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->aProjects as $aProject) : ?>
            <tr>
                <td><?= $aProject['id_project'] ?></td>
                <td><?= $aProject['title'] ?></td>
                <td><?= number_format($aProject['amount'], 0, '', '.') . '&nbsp€' ?></td>
                <td><?= isset($aProject['bic']) ? $aProject['bic'] : '' ?></td>
                <td><?= isset($aProject['iban']) ? $aProject['iban'] : '' ?></td>
                <td>
                    <?php if (isset($aProject['kbis']) && $aProject['kbis'] != '') : ?>
                        <div><a href="<?= $this->lurl . $aProject['kbis'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="POUVOIR"/></a></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (isset($aProject['pouvoir']) && $aProject['pouvoir'] != '') : ?>
                        <div><a href="<?= $this->lurl . $aProject['pouvoir'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="POUVOIR"/></a></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (isset($aProject['mandat']) && $aProject['mandat'] != '') : ?>
                        <a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $aProject['mandat'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="MANDAT"/></a></td>
                    <?php endif; ?>
                </td>
                <td>Menu déblocage</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($this->nb_lignes != ''): ?>
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
