<script>
    $(function() {
        $('.tablesorter').tablesorter({headers:{5: {sorter: false}}});

        <?php  if ($this->nb_lignes != '') : ?>
            $('.tablesorter').tablesorterPager({container: $('#pager'), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
  });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Recherche société</h1>
    <div class="btnDroite"><a href="<?= $this->url ?>/company/add" class="btn-primary">Créer une société</a></div>
    <form method="post" enctype="multipart/form-data" action="<?= $this->lurl ?>/company">
        <div class="form-group">
            <label for="siren">SIREN</label>
            <input type="text" name="siren" id="siren" class="form-control input_large">
        </div>
        <button type="submit" class="btn-primary">Rechercher</button>
    </form>
    <br>
    <br>
    <?php if (false === empty($this->companies)) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Raison sociale</th>
                <th>Adresse</th>
                <th>Code postale</th>
                <th>Ville</th>
                <th>Téléphone</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php
            foreach ($this->companies as $details) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $details['company']->getName() ?></td>
                    <td><?= null !== $details['address'] ? $details['address']->getAddress() : '' ?></td>
                    <td><?= null !== $details['address'] ? $details['address']->getZip() : '' ?></td>
                    <td><?= null !== $details['address'] ? $details['address']->getCity() : '' ?></td>
                    <td><?= $details['company']->getPhone() ?></td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/company/edit/<?= $details['company']->getIdCompany() ?>">
                            <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $details['company']->getName() ?>">
                        </a>
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
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                        <input type="text" class="pagedisplay">
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
