<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

?>
<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1>Recherche prêteurs</h1>
            </div>
        </div>

        <form method="post" action="<?= $this->lurl ?>/sfpmei/preteurs" role="search">
            <div class="form-group row">
                <div class="col-md-3">
                    <label for="id">ID</label>
                    <input id="id" name="id" type="text" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="text" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="lastname">Nom</label>
                    <input id="lastname" name="lastname" type="text" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="company">Raison sociale</label>
                    <input id="company" name="company" type="text" class="form-control">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-12">
                    <button type="submit" class="btn col-md-2 pull-right">Chercher</button>
                </div>
            </div>
        </form>
    </div>

    <?php if (false === empty($_SESSION['error_search'])) : ?>
        <div class="attention">
            <?= implode('<br>', $_SESSION['error_search']) ?>
            <?php unset($_SESSION['error_search']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($this->lenders)) : ?>
        <div class="container-fluid">
            <?php if (count($this->lenders) > 0) : ?>
                <div class="row">
                    <div class="col-md-12">
                        <h2><?= count($this->lenders) ?> prêteur<?= count($this->lenders) > 1 ? 's' : '' ?> trouvé<?= count($this->lenders) > 1 ? 's' : '' ?></h2>
                    </div>
                </div>
                <table class="tablesorter table table-hover table-striped">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom / Raison sociale</th>
                        <th>Nom d'usage</th>
                        <th>Prénom / Dirigeant</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($this->lenders as $lender) : ?>
                        <tr class="<?= ($i++ % 2 == 1 ? '' : 'odd') ?>">
                            <td><?= $lender['id_client'] ?></td>
                            <td><?= $lender['nom_ou_societe'] ?></td>
                            <td><?= $lender['nom_usage'] ?></td>
                            <td><?= $lender['prenom_ou_dirigeant'] ?></td>
                            <td><?= $lender['email'] ?></td>
                            <td><?= $lender['telephone'] ?></td>
                            <td><?= $lender['status'] == Clients::STATUS_ONLINE ? 'En ligne' : 'Hors ligne' ?></td>
                            <td align="center">
                                <a href="<?= $this->lurl ?>/preteur/<?= $lender['id_client'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir la fiche du prêteur">
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($this->lenders) > $this->pagination) : ?>
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
                <strong>Aucun résultat trouvé pour cette recherche</strong>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    $(function () {
        $('.tablesorter').tablesorter({headers: {7: {sorter: false}}});

        <?php if (count($this->lenders) > $this->pagination) : ?>
            $('.tablesorter').tablesorterPager({container: $('#pagination'), positionFixed: false, size: <?= $this->pagination ?>});
        <?php endif; ?>
    });
</script>
