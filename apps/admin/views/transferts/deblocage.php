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
            <th>RIB</th>
            <th>Kbis</th>
            <th>Pouvoir</th>
            <th>Mandat</th>
            <th>Déblocage</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->aProjects as $aProject) : ?>
            <tr>
                <form method="post" id="statut_pouvoir" name="deblocage" onsubmit="return confirm('Voulez-vous vraiment débloquer les fonds pour le projet <?= $aProject['id_project'] ?> ?');">
                    <td><?= $aProject['id_project'] ?></td>
                    <td><?= $aProject['title'] ?></td>
                    <td><?= number_format($aProject['amount'], 0, '', '.') . '&nbsp€' ?></td>
                    <td><?= isset($aProject['bic']) ? $aProject['bic'] : '' ?></td>
                    <td><?= isset($aProject['iban']) ? $aProject['iban'] : '' ?></td>
                    <td>
                        <?php if (isset($aProject['rib']) && $aProject['rib'] != '') : ?>
                            <a href="<?= $this->url ?>/attachment/download/id/<?= $aProject['id_rib'] ?>/file/<?= urlencode($aProject['rib']) ?>">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="RIB"/>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($aProject['kbis']) && $aProject['kbis'] != '') : ?>
                            <a href="<?= $this->url ?>/attachment/download/id/<?= $aProject['id_kbis'] ?>/file/<?= urlencode($aProject['kbis']) ?>">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="KBIS"/>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($aProject['url_pdf']) && $aProject['url_pdf'] != '') : ?>
                            <div><a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $aProject['url_pdf'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="POUVOIR"/></a></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($aProject['mandat']) && $aProject['mandat'] != '') : ?>
                            <a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $aProject['mandat'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="MANDAT"/></a></td>
                        <?php endif; ?>
                    </td>
                    <td>
                    <?php if (isset($aProject['status_remb']) && 0 == $aProject['status_remb'] && $aProject['status_mandat'] == \clients_mandats::STATUS_SIGNED && $aProject['authority_status'] == 1) : ?>
                        <input type="hidden" name="status_remb" value="<?= $aProject['status_remb'] ?>"/>
                        <input type="hidden" name="statut_pouvoir" class="btn" value="1" />
                        <input type="submit" class="btn" value="Débloquer les fonds" />
                    <?php endif; ?>
                    <input type="hidden" name="id_project" value="<?= $aProject['id_project'] ?>"/>
                    </td>
                </form>
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
