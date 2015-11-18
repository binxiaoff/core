<?php if (count($this->lTrans) > 0) { ?>
    <table class="tablesorter transac">
        <thead>
        <tr>
            <th>Type d'approvisionnement</th>
            <th>Date de l'opération</th>
            <th>Montant de l'opération</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        foreach ($this->lTrans as $t) {
            if ($t['type_transaction'] == 5) {
                $this->echeanciers->get($t['id_echeancier'], 'id_echeancier');
                $this->projects->get($this->echeanciers->id_project, 'id_project');
                $this->companies->get($this->projects->id_company, 'id_company');
            }
            ?>
            <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                <td><?= $this->lesStatuts[$t['type_transaction']] . ($t['type_transaction'] == 5 ? ' - ' . $this->companies->name : '') ?></td>
                <td><?= $this->dates->formatDate($t['date_transaction'], 'd-m-Y') ?></td>
                <td><?= $this->ficelle->formatNumber($t['montant'] / 100) ?> €</td>
            </tr>
            <?php
            $i++;
        }
        ?>
        </tbody>
    </table>
    <?php if ($this->nb_lignes != '') { ?>
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
    <?php } ?>
<?php } ?>
<script>
    $(".transac").tablesorter({headers: {}});
</script>
