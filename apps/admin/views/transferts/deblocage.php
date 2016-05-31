<script type="text/javascript">
    $(function() {
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
                <td><?= $aProject['id_project'] ?></td>
                <td><?= $aProject['title'] ?></td>
                <td><?= $this->ficelle->formatNumber($aProject['amount']) . '&nbsp€' ?></td>
                <td><?= isset($aProject['bic']) ? $aProject['bic'] : '' ?></td>
                <td><?= isset($aProject['iban']) ? $aProject['iban'] : '' ?></td>
                <td>
                    <?php if (false === empty($aProject['rib'])) : ?>
                        <a href="<?= $this->url ?>/attachment/download/id/<?= $aProject['id_rib'] ?>/file/<?= urlencode($aProject['rib']) ?>">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="RIB"/>
                        </a>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (false === empty($aProject['kbis'])) : ?>
                        <a href="<?= $this->url ?>/attachment/download/id/<?= $aProject['id_kbis'] ?>/file/<?= urlencode($aProject['kbis']) ?>">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="KBIS"/>
                        </a>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (false === empty($aProject['url_pdf'])) : ?>
                        <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $aProject['url_pdf'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="POUVOIR"/></a>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (false === empty($aProject['mandat'])) : ?>
                        <a href="<?= $this->lurl ?>/protected/mandat_preteur/<?= $aProject['mandat'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="MANDAT"/></a></td>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" name="deblocage" onsubmit="return confirm('Voulez-vous vraiment débloquer les fonds pour le projet <?= $aProject['id_project'] ?> ?');">
                        <?php if (
                            isset($aProject['status_remb'], $aProject['status_mandat'], $aProject['authority_status'])
                            && $aProject['status_remb'] == \projects_pouvoir::STATUS_PENDING_VALIDATION
                            && $aProject['status_mandat'] == \clients_mandats::STATUS_SIGNED
                            && $aProject['authority_status'] == \projects_pouvoir::STATUS_SIGNED
                        ) : ?>
                            <input type="submit" name="validateProxy" class="btn" value="Débloquer les fonds" />
                            <input type="hidden" name="id_project" value="<?= $aProject['id_project'] ?>"/>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
