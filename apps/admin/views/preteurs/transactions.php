<?php if (count($this->lenderOperations) > 0) : ?>
    <table class="tablesorter transac">
        <thead>
            <tr>
                <th width="15%">Type d'operation</th>
                <th width="5%">Id loan</th>
                <th width="20%">Projet</th>
                <th width="5%">Date</th>
                <th width="45%">Montant de l'opération</th>
                <th width="10%">Solde</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->lenderOperations as $operation) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $this->translator->trans('lender-operations_operation-label-' . $operation['label'])?></td>
                    <td><?= $operation['id_loan'] ?></td>
                    <td><?= $operation['title'] ?></td>
                    <td><?= date('d/m/Y', strtotime($operation['date'])) ?></td>
                    <td>
                        <?= number_format($operation['amount'], 2, ',', ' ') ?> €
                        <?php if (false == empty($operation['detail'])) : ?>
                            <br><i>(
                            <?php foreach ($operation['detail']['items'] as $detail) : ?>
                                <?= $detail['label'] ?> : <?= number_format($detail['value'], 2, ',', ' ') ?>
                            <?php endforeach; ?>
                                )</i>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($operation['available_balance'], 2, ',', ' ') ?> €</td>
                </tr>
                <?php $i++; ?>
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
<?php endif; ?>
<script>
    $(".transac").tablesorter({headers: {}});
</script>
