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
    <?php if ($this->partner) : ?>
    <?php $thirdParties = $this->partner->getPartnerThirdParties() ?>
        <h1>Liste des tiers</h1>
        <div class="btnDroite">
            <input type="text" name="siren" id="search-siren" class="input_moy" placeholder="SIREN" pattern="[0-9]{9}" required>
            <a href="#" id="third-party-add-link" class="btn_link">Ajouter un tier</a>
        </div>
        <?php if (count($thirdParties) > 0) : ?>
            <table class="tablesorter">
                <thead>
                <tr>
                    <th>Raison sociale</th>
                    <th>Type</th>
                    <th>Ajouté le</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\PartnerThirdParty $thirdParty */
                foreach ($thirdParties as $thirdParty) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $thirdParty->getIdCompany()->getName() ?></td>
                        <td><?= $this->translator->trans('partner_third-party-type-' . $thirdParty->getIdType()->getLabel()) ?></td>
                        <td><?= $thirdParty->getAdded()->format('m/d/Y') ?></td>
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
            <p>Il n'y a aucune tier pour le moment.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
  $(function () {
    $('#third-party-add-link').click(function(event) {
      event.preventDefault()
      var siren = $('#search-siren').val();
      if (siren.length == 0) {
        alert('Merci de saisir le SIREN');
        return;
      }
      $.colorbox({href: '/partner/third_party_add_thickbox/<?= $this->partner->getId() ?>/' + $('#search-siren').val()})
    })
  })
</script>

