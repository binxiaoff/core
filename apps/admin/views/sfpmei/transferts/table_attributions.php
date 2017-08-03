<?php if (count($this->receptions) > 0) : ?>
    <table class="tablesorter table table-hover table-striped operations-attribution">
        <thead>
        <tr>
            <th>ID</th>
            <th>Motif</th>
            <th>Montant</th>
            <th>Attribution</th>
            <th>ID <?= ($this->type === 'preteurs') ? 'client' : 'dossier' ?></th>
            <th>Date</th>
        </tr>
        </thead>
        <tbody>
        <?php
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $reception */
        foreach ($this->receptions as $reception) : ?>
            <tr>
                <td><?= $reception->getIdReception() ?></td>
                <td><?= $reception->getMotif() ?></td>
                <td style="text-align:right"><?= $this->ficelle->formatNumber($reception->getMontant() / 100) ?> €</td>
                <td class="statut_operation_<?= $reception->getIdReception() ?>">
                    <?php if (1 == $reception->getstatusBo() && $reception->getIdUser()): ?>
                        <?= $reception->getIdUser()->getFirstname() . ' ' . $reception->getIdUser()->getName() ?>
                        <br/>
                        <?= $reception->getAssignmentDate()->format('d/m/Y à H:i:s') ?>
                    <?php else: ?>
                        <?= $this->statusOperations[$reception->getstatusBo()] ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($this->type === 'preteurs') : ?>
                        <?= $reception->getIdClient()->getIdClient() ?>
                    <?php else : ?>
                        <a href="/sfpmei/dossier/<?= $reception->getIdProject()->getIdProject() ?>"><?= $reception->getIdProject()->getIdProject() ?></a>
                    <?php endif; ?>
                </td>
                <td><?= $reception->getAdded()->format('d/m/Y') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (count($this->receptions) > $this->pagination) : ?>
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
    Aucune opération
<?php endif; ?>

<script type="text/javascript">
    $(function () {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".operations-attribution").tablesorter({headers: {2: {sorter: 'amount'}, 5: {sorter: 'date'}}});

        <?php if (count($this->receptions) != '') : ?>
        $(".operations-attribution").tablesorterPager({
            container: $("#pagination"),
            positionFixed: false,
            size: <?= $this->pagination ?>});
        <?php endif; ?>

        $(".inline").colorbox({inline: true, width: "50%"});
    });
</script>
