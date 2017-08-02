<div class="col-md-12">
    <?php if (count($this->lenderOperations) > 0) : ?>
        <table class="tablesorter table table-hover table-striped lender-operations">
            <thead>
            <tr>
                <th width="15%">Type d'opération</th>
                <th width="25%">Projet</th>
                <th width="5%">Date</th>
                <th width="45%">Montant de l'opération</th>
                <th width="10%">Solde</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->lenderOperations as $operation) : ?>
                <tr>
                    <td><?= $this->translator->trans('lender-operations_operation-label-' . $operation['label'])?></td>
                    <td><a href=""><?= $operation['title'] ?></td>
                    <td><?= \DateTime::createFromFormat('Y-m-d', $operation['date'])->format('d/m/Y') ?></td>
                    <td>
                        <strong><?= $this->ficelle->formatNumber($operation['amount']) ?> €</strong>
                        <?php if (false == empty($operation['detail'])) : ?>
                            <em>
                                <?php foreach ($operation['detail']['items'] as $detail) : ?>
                                    <br><?= $detail['label'] ?> : <?= $this->ficelle->formatNumber($detail['value']) ?>&nbsp;€
                                <?php endforeach; ?>
                            </em>
                        <?php endif; ?>
                    </td>
                    <td><?= $this->ficelle->formatNumber($operation['available_balance']) ?> €</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($this->lenderOperations) > $this->pagination) : ?>
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
</div>

<script>
    $(function () {
        $('.lender-operations').tablesorter({headers: {2: {sorter: 'date'}, 3: {sorter: 'amount'}, 4: {sorter: 'amount'}}});

        <?php if (count($this->lenderOperations) > $this->pagination) : ?>
            $('.lender-operations').tablesorterPager({container: $('#pagination'), positionFixed: false, size: <?= $this->pagination ?>});
        <?php endif; ?>
    })
</script>
