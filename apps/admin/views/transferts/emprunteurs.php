<script type="text/javascript">
    function annulerAttribution(id_project, id_reception) {
        if (confirm('Voulez vous vraiment annuler le prélèvement ?')) {
            var val = {
                id_project: id_project,
                id_reception: id_reception
            };

            $.post(add_url + '/transferts/annuler_attribution_projet', val).done(function(data) {
                if (data != 'nok') {
                    $(".statut_operation_" + id_reception).parent('tr').fadeOut();
                }
            });
        }
    }

    function rejeterPrelevement(id_project, id_reception) {
        if (confirm('Voulez vous vraiment rejeter le prélèvement attribué au projet ' + id_project + ' ?')) {
            var val = {
                id_project: id_project,
                id_reception: id_reception
            };

            $.post(add_url + '/transferts/rejeter_prelevement_projet', val).done(function(data) {
                if (data != 'nok') {
                    $(".statut_operation_" + id_reception).parent('tr').fadeOut();
                }
            });
        }
    }

    $(function() {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

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
        <li><a href="<?= $this->lurl ?>/transferts">Dépot de fonds</a> -</li>
        <li>Opérations emprunteurs</li>
    </ul>
    <h1>Opérations emprunteurs</h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/transferts/emprunteurs/csv" class="btn_link">Export CSV</a>
    </div>
    <table class="tablesorter">
        <thead>
            <tr>
                <th style="width:50px">ID</th>
                <th>Motif</th>
                <th style="width:150px">Montant</th>
                <th style="width:150px">Attribution</th>
                <th style="width:100px">ID client</th>
                <th style="width:100px">Date</th>
                <th style="width:100px">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->aOperations as $aOperation): ?>
                <tr<?= ($i++ % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $aOperation['id_reception'] ?></td>
                    <td><?= $aOperation['motif'] ?></td>
                    <td style="text-align:right"><?= $this->ficelle->formatNumber($aOperation['montant'] / 100) ?> €</td>
                    <td class="statut_operation_<?= $aOperation['id_reception'] ?>">
                        <?php if (1 == $aOperation['status_bo'] && isset($this->aUsers[$aOperation['id_user']])): ?>
                            <?= $this->aUsers[$aOperation['id_user']]['firstname'] . ' ' . $this->aUsers[$aOperation['id_user']]['name'] ?><br/>
                            <?= date('d/m/Y à H:i:s', strtotime($aOperation['assignment_date'])) ?>
                        <?php else: ?>
                            <?= $this->statusOperations[$aOperation['status_bo']] ?>
                        <?php endif; ?>
                    </td>
                    <td class="num_project_<?= $aOperation['id_reception'] ?>"><a href="/dossiers/edit/<?= $aOperation['id_project'] ?>"><?= $aOperation['id_project'] ?></a></td>
                    <td><?= date('d/m/Y', strtotime($aOperation['added'])) ?></td>
                    <td align="center">
                        <?php if (in_array($aOperation['status_bo'], array(1, 2))): ?>
                            <img class="rejete_<?= $aOperation['id_reception'] ?>" style="cursor:pointer;" onclick="rejeterPrelevement(<?= $aOperation['id_project'] ?>, <?= $aOperation['id_reception'] ?>)" src="<?= $this->surl ?>/images/admin/edit.png" alt="Rejeter"/>
                            <img class="annuler_<?= $aOperation['id_reception'] ?>" style="cursor:pointer;" onclick="annulerAttribution(<?= $aOperation['id_project'] ?>, <?= $aOperation['id_reception'] ?>)" src="<?= $this->surl ?>/images/admin/delete.png" alt="Annuler"/>
                        <?php endif; ?>
                        <a class="inline" href="#inline_content_<?= $aOperation['id_reception'] ?>">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Afficher la ligne de réception"/>
                        </a>
                        <div style="display:none;">
                            <div id="inline_content_<?= $aOperation['id_reception'] ?>" style="white-space: nowrap; padding:10px; background:#fff;"><?= $aOperation['ligne'] ?></div>
                        </div>
                    </td>
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
