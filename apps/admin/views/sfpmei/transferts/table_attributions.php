<?php use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions; ?>
<?php if (count($this->receptions) > 0) : ?>
    <table class="tablesorter table table-hover table-striped operations-attribution">
        <thead>
        <tr>
            <th>ID</th>
            <th>Motif</th>
            <th>Montant</th>
            <?php if (in_array($this->type, ['preteurs', 'emprunteurs'])) : ?>
                <th>Attribution</th>
                <th>ID <?= ($this->type === 'preteurs') ? 'client' : 'projet' ?></th>
            <?php elseif ('non_attribues' === $this->type) : ?>
                <th>Code</th>
            <?php endif; ?>
            <th>Date</th>
        </tr>
        </thead>
        <tbody>
            <?php /** @var Receptions $reception */ ?>
            <?php foreach ($this->receptions as $reception) : ?>
                <?php $isNegativeAmount = Receptions::TYPE_DIRECT_DEBIT == $reception->getType() && Receptions::DIRECT_DEBIT_STATUS_REJECTED == $reception->getStatusPrelevement(); ?>
                <tr<?= $isNegativeAmount ? ' class="danger"' : '' ?>>
                    <td class="text-center"><?= $reception->getIdReception() ?></td>
                    <td><?= $reception->getMotif() ?></td>
                    <td class="text-right"><?= $isNegativeAmount ? '- ' : '' ?><?= $this->ficelle->formatNumber($reception->getMontant() / 100) ?> €</td>
                    <?php if (in_array($this->type, ['preteurs', 'emprunteurs'])) : ?>
                        <td class="statut_operation_<?= $reception->getIdReception() ?>">
                            <?php if (Receptions::STATUS_ASSIGNED_MANUAL == $reception->getstatusBo() && $reception->getIdUser()): ?>
                                <?= $reception->getIdUser()->getFirstname() . ' ' . $reception->getIdUser()->getName() ?><br>
                                <?= $reception->getAssignmentDate()->format('d/m/Y H:i:s') ?>
                            <?php else: ?>
                                <?= $this->statusOperations[$reception->getstatusBo()] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($this->type === 'preteurs') : ?>
                                <a href="<?= $this->lurl ?>/sfpmei/preteur/<?= $reception->getIdClient()->getIdClient() ?>"><?= $reception->getIdClient()->getIdClient() ?></a>
                            <?php else : ?>
                                <a href="<?= $this->lurl ?>/sfpmei/projet/<?= $reception->getIdProject()->getIdProject() ?>"><?= $reception->getIdProject()->getIdProject() ?></a>
                            <?php endif; ?>
                        </td>
                    <?php elseif ('non_attribues' === $this->type) : ?>
                        <td class="text-center"><?= substr($reception->getLigne(), 32, 2) ?></td>
                    <?php endif; ?>
                    <td class="text-center"><?= $reception->getAdded()->format('d/m/Y') ?></td>
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

<script>
    $(function () {
        jQuery.tablesorter.addParser({
            id: 'amount',
            type: 'numeric',
            is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            },
            format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(/ /g, ''));
            }
        })

        jQuery.tablesorter.addParser({
            id: 'frDate',
            type: 'numeric',
            is: function (s) {
                return /^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/.test(s)
            },
            format: function (s) {
                s = s.replace(/(\d{2})[\/](\d{2})[\/](\d{4})/, '$3$2$1')
                return jQuery.tablesorter.formatFloat(s)
            }
        })

        <?php if (in_array($this->type, ['preteurs', 'emprunteurs'])) : ?>
            $('.operations-attribution').tablesorter({headers: {2: {sorter: 'amount'}, 5: {sorter: 'frDate'}}})
        <?php else : ?>
            $('.operations-attribution').tablesorter({headers: {2: {sorter: 'amount'}, 3: {sorter: 'frDate'}}})
        <?php endif; ?>

        <?php if (count($this->receptions) > $this->pagination) : ?>
            $('.operations-attribution').tablesorterPager({
                container: $('#pagination'),
                positionFixed: false,
                size: <?= $this->pagination ?>
            })
        <?php endif; ?>
    })
</script>
