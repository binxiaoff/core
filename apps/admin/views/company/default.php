<script>
  $(function() {
    $(".tablesorter").tablesorter({headers:{7: {sorter: false}}});

      <?php  if ($this->nb_lignes != '') : ?>
    $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
      <?php endif; ?>
  });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Recherche sociétés</h1>
    <div class="btnDroite"><a href="<?= $this->url ?>/company/add" class="btn_link">Créer une société</a></div>
    <form method="post" name="search_company" id="search_company" enctype="multipart/form-data" action="company">
        <label for="siren">SIREN <input type="text" name="siren" id="siren" class="input_large"></label>
        <input type="submit" value="Valider" class="btn">
    </form>
    <br>
    <br>
    <?php if (false === empty($this->companies)) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>ID</th>
                <th>Raison sociale</th>
                <th>Adresse</th>
                <th>Code postale</th>
                <th>Ville</th>
                <th>Téléphone</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies $company */
            foreach ($this->companies as $company) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $company->getIdCompany() ?></td>
                    <td><?= $company->getName() ?></td>
                    <td><?= $company->getAdresse1() ?></td>
                    <td><?= $company->getZip() ?></td>
                    <td><?= $company->getCity() ?></td>
                    <td><?= $company->getPhone() ?></td>
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
</div>
