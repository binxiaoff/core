<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {8: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <h1>Résultats de la recherche prêteurs <?= (count($this->lPreteurs) > 0 ? '(' . count($this->lPreteurs) . ')' : '') ?></h1>
    <?php if (count($this->lPreteurs) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nom / Raison sociale</th>
                <th>Nom d'usage</th>
                <th>Prénom / Dirigeant</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Status</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->lPreteurs as $client) : ?>
                <tr class="<?= ($i++ % 2 == 1 ? '' : 'odd') ?> ">
                    <td><?= $client['id_client'] ?></td>
                    <td><?= $client['nom_ou_societe'] ?></td>
                    <td><?= $client['nom_usage'] ?></td>
                    <td><?= $client['prenom_ou_dirigeant'] ?></td>
                    <td><?= $client['email'] ?></td>
                    <td><?= $client['telephone'] ?></td>
                    <td><?= $client['status'] == \Unilend\Bundle\CoreBusinessBundle\Entity\Clients::STATUS_ONLINE ? 'en ligne' : 'hors ligne' ?></td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $client['id_client'] ?>">
                            <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $client['nom_ou_societe'] . ' ' . $client['prenom_ou_dirigeant'] ?>"/>
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
    <?php else : ?>
        <p>Il n'y a aucun prêteur pour cette recherche.</p>
    <?php endif; ?>
</div>
