<script type="text/javascript">
  $(function () {
    $(".tablesorter").tablesorter({headers: {7: {sorter: false}}});

      <?php if ($this->nb_lignes != '') : ?>
    $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
      <?php endif; ?>

      <?php if (isset($_SESSION['freeow'])) : ?>
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
    <h1>Liste des partenaires</h1>
    <?php if (count($this->partners) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Ajouté le</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Partner $partner */
            foreach ($this->partners as $partner) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $partner->getName() ?></td>
                    <td><?= $partner->getType()->getName() ?></td>
                    <td><?= $partner->getAdded()->format('m/d/Y') ?></td>
                    <td><a href="/partner/third_party/<?= $partner->getId() ?>">Tiers</a></td>
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
    <?php else : ?>
        <p>Il n'y a aucune partenaire pour le moment.</p>
    <?php endif; ?>
</div>
