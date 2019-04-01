<?php

use Unilend\Entity\Projects;

?>
<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1>Recherche projets</h1>
            </div>
        </div>

        <form method="post" action="<?= $this->lurl ?>/sfpmei/projets" role="search">
            <div class="form-group row">
                <div class="col-md-4">
                    <label for="id">ID</label>
                    <input id="id" name="id" type="text" class="form-control">
                </div>
                <div class="col-md-4">
                    <label for="siren">SIREN</label>
                    <input id="siren" name="siren" type="text" class="form-control">
                </div>
                <div class="col-md-4">
                    <label for="company">Raison sociale</label>
                    <input id="company" name="company" type="text" class="form-control">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-12">
                    <button type="submit" class="btn-primary col-md-2 pull-right">Chercher</button>
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

    <?php if (isset($this->projects)) : ?>
        <div class="container-fluid">
            <?php if (count($this->projects) > 0) : ?>
                <div class="row">
                    <div class="col-md-12">
                        <h2><?= count($this->projects) ?> projet<?= count($this->projects) > 1 ? 's' : '' ?></h2>
                    </div>
                </div>
                <table class="tablesorter table table-hover table-striped">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>SIREN</th>
                        <th>Raison sociale</th>
                        <th>Date demande</th>
                        <th>Montant</th>
                        <th>Durée</th>
                        <th>Statut</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php /** @var Projects $project */ ?>
                    <?php foreach ($this->projects as $project) : ?>
                        <tr>
                            <td><a href="<?= $this->lurl ?>/sfpmei/projet/<?= $project->getIdProject() ?>"><?= $project->getIdProject() ?></a></td>
                            <td><a href="<?= $this->lurl ?>/sfpmei/emprunteur/<?= $project->getIdCompany()->getIdClientOwner()->getIdClient() ?>"><?= $project->getIdCompany()->getSiren() ?></a></td>
                            <td><a href="<?= $this->lurl ?>/sfpmei/emprunteur/<?= $project->getIdCompany()->getIdClientOwner()->getIdClient() ?>"><?= $project->getIdCompany()->getName() ?></a></td>
                            <td><?= $project->getAdded()->format('d/m/Y') ?></td>
                            <td><?= empty($project->getAmount()) ? '' : $this->ficelle->formatNumber($project->getAmount(), 0) . ' €' ?></td>
                            <td><?= empty($project->getPeriod()) ? '' : $project->getPeriod() . ' mois' ?></td>
                            <td><?= $this->projectStatusRepository->findOneBy(['status' => $project->getStatus()])->getLabel() ?></td>
                            <td align="center">
                                <a href="<?= $this->lurl ?>/sfpmei/projet/<?= $project->getIdProject() ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir la fiche du projet">
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($this->projects) > $this->pagination) : ?>
                    <div id="pagination" class="row">
                        <div class="col-md-12 text-center">
                            <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                            <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                            <input type="text" class="pagedisplay input_court text-center" title="Page" disabled>
                            <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                            <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                            <select class="pagesize sr-only" title="Page">
                                <option value="<?= $this->pagination ?>" selected="selected"><?= $this->pagination ?></option>
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
        jQuery.tablesorter.addParser({
            id: 'amount',
            type: 'numeric',
            is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            },
            format: function (s) {
                return s.replace(/,/g, '').replace('€', '').replace(' ', '');
            }
        });

        $('.tablesorter').tablesorter({headers: {4: {sorter: 'amount'}, 7: {sorter: false}}});

        <?php if (count($this->projects) > $this->pagination) : ?>
            $('.tablesorter').tablesorterPager({container: $('#pagination'), positionFixed: false, size: <?= $this->pagination ?>});
        <?php endif; ?>

        $('#company').autocomplete({
            source: '<?= $this->lurl ?>/sfpmei/autocompleteCompanyName/',
            minLength: 3,
            delay: 100
        });
    });
</script>
