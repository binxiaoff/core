<script>
    $(function() {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $(".inline").colorbox({inline: true, width: "50%"});
    });
</script>
<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Opérations emprunteurs</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->lurl ?>/transferts/emprunteurs/csv" class="btn-primary pull-right">Export CSV</a>
        </div>
    </div>
    <table class="tablesorter table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width:50px">ID</th>
                <th>Motif</th>
                <th style="width:150px">Montant</th>
                <th style="width:150px">Attribution</th>
                <th style="width:100px">ID projet</th>
                <th style="width:100px">Date</th>
                <th style="width:100px">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        <?php use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions; ?>
            <?php /** @var Receptions $reception */ ?>
            <?php foreach ($this->receptions as $reception) : ?>
                <?php $isNegativeAmount = Receptions::TYPE_DIRECT_DEBIT == $reception->getType() && Receptions::DIRECT_DEBIT_STATUS_REJECTED == $reception->getStatusPrelevement(); ?>
                <tr<?= $isNegativeAmount ? ' class="danger"' : '' ?>>
                    <td><?= $reception->getIdReception() ?></td>
                    <td><?= $reception->getMotif() ?></td>
                    <td class="text-right"><?= $isNegativeAmount ? '- ' : '' ?><?= $this->ficelle->formatNumber($reception->getMontant() / 100) ?> €</td>
                    <td class="statut_operation_<?= $reception->getIdReception() ?>">
                        <?php if (Receptions::STATUS_ASSIGNED_MANUAL == $reception->getStatusBo() && null !== $reception->getIdUser()) : ?>
                            <?= $reception->getIdUser()->getFirstname() . ' ' . $reception->getIdUser()->getName() ?><br>
                            <?= $reception->getAssignmentDate()->format('d/m/Y H:i:s') ?>
                        <?php else : ?>
                            <?= $this->statusOperations[$reception->getStatusBo()] ?>
                        <?php endif; ?>
                    </td>
                    <td class="num_project_<?= $reception->getIdReception() ?>"><a href="<?= $this->lurl ?>/dossiers/edit/<?= $reception->getIdProject()->getIdProject() ?>"><?= $reception->getIdProject()->getIdProject() ?></a></td>
                    <td><?= $reception->getAdded()->format('d/m/Y') ?></td>
                    <td align="center">
                        <a class="inline" href="#inline-content-<?= $reception->getIdReception() ?>" title="Ligne de réception">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Afficher l'opération">
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($this->nb_lignes != '') : ?>
        <table>
            <tr>
                <td id="pager">
                    <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                    <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                    <input type="text" class="pagedisplay">
                    <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                    <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                    <select class="pagesize">
                        <option value="<?= $this->nb_lignes ?>" selected><?= $this->nb_lignes ?></option>
                    </select>
                </td>
            </tr>
        </table>
    <?php endif; ?>
    <div class="hidden">
        <?php foreach ($this->receptions as $reception) : ?>
            <div id="inline-content-<?= $reception->getIdReception() ?>" style="white-space: nowrap; padding: 10px; background:#fff;"><?= $reception->getLigne() ?></div>
        <?php endforeach; ?>
    </div>
</div>
