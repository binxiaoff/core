<h2>Suivi des enchères en cours</h2>
<?php if (count($this->lBids) > 0) : ?>
    <table class="tablesorter bidsEncours">
        <thead>
            <tr>
                <th>ID bid</th>
                <th>Projet</th>
                <th>Date</th>
                <th>Montant enchère (€)</th>
                <th>Taux</th>
                <th>Nbre de mois</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->lBids as $e) : ?>
                <?php $this->projects->get($e['id_project'], 'id_project'); ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td align="center"><?= $e['id_bid'] ?></td>
                    <td><?= $this->projects->title_bo ?></td>
                    <td><?= date('d/m/Y', strtotime($e['added'])) ?></td>
                    <td align="center"><?= number_format($e['amount'] / 100, 2, '.', ' ') ?></td>
                    <td align="center"><?= number_format($e['rate'], 2, '.', ' ') ?> %</td>
                    <td align="center"><?= $this->projects->period ?></td>
                    <td align="center">
                        <img style="cursor:pointer;" onclick="deleteBid(<?= $e['id_bid'] ?>);" src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer"/>
                    </td>
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
  $(".bidsEncours").tablesorter({headers: {6: {sorter: false}}});
</script>
