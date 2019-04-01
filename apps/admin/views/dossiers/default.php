<?php

use Unilend\Entity\{Projects, ProjectsStatus};

?>
<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip();

    $('body').on('click', '[data-project]', function (event) {
      var projectId = $(this).data('project')
      if (projectId && !$(event.target).is('a') && !$(event.target).is('img')) {
        $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/' + projectId)
      }
    })

    $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

    $('#datepik_1').datepicker({
      showOn: 'both',
      buttonImageOnly: true,
      changeMonth: true,
      changeYear: true,
      yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
    });

    $('#datepik_2').datepicker({
      showOn: 'both',
      buttonImageOnly: true,
      changeMonth: true,
      changeYear: true,
      yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
    });

    $('#reset').click(function () {
      $("#id").val('');
      $("#siren").val('');
      $("#datepik_1").val('');
      $("#datepik_2").val('');
      $("#raison-sociale").val('');
      $('#montant option[value="0"]').prop('selected', true);
      $('#duree option[value=""]').prop('selected', true);
      $('#status option[value=""]').prop('selected', true);
      $('#analyste option[value="0"]').prop('selected', true);
      $('#commercial option[value="0"]').prop('selected', true);
    });

    $('#raison-sociale').autocomplete({
      source: '<?= $this->lurl ?>/dossiers/autocompleteCompanyName/',
      minLength: 3,
      delay: 100
    });

    jQuery.tablesorter.addParser({
        id: 'frDate',
        type: 'numeric',
        is: function (s) {
            return /^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/.test(s)
        },
        format: function (s) {
            s = s.replace(/(\d{2})[\/](\d{2})[\/](\d{4})/, '$3$2$1')
            return jQuery.tablesorter.formatFloat(s)
        }
    })
    $('.tablesorter').tablesorter({
        headers: {
            3: {sorter: 'frDate'},
            5: {sorter: 'digit'},
            <?php if ($this->isRiskUser && $this->hasRepaymentAccess) : ?>
              12: {sorter: false},
              13: {sorter: false}
            <?php elseif ($this->hasRepaymentAccess) : ?>
              11: {sorter: false},
              12: {sorter: false}
            <?php elseif ($this->isRiskUser) : ?>
              11: {sorter: false}
            <?php else : ?>
              10: {sorter: false}
            <?php endif; ?>
        }
    });

    $('#send-dossier, #reset').on('click', function () {
      $('#page').val(1);
    });
  });

  <?php if (isset($this->resultsCount)) : ?>
      function paginationDossiers(directionPagination) {
        switch (directionPagination) {
          case 'first':
            $('#page').val(1);
            break;
          case 'prev':
            $('#page').val(<?= max(1, $this->page - 1) ?>);
            break;
          case 'next':
            $('#page').val(<?= min(ceil($this->resultsCount / $this->nb_lignes), $this->page + 1) ?>);
            break;
          case 'last':
            $('#page').val(<?= ceil($this->resultsCount / $this->nb_lignes) ?>);
            break;
        }

        $('#search-dossier').submit();
      }
  <?php endif; ?>
</script>
<style>
    #search-dossier {
        margin-bottom: 30px;
    }

    #search-dossier fieldset.primary {
        margin-top: 10px;
    }

    #search-dossier fieldset.secondary {
        margin-bottom: 10px;
        color: #b1adb2;
    }
</style>
<div id="contenu">
    <div class="row">
        <div class="col-md-12">
            <a href="<?= $this->lurl ?>/dossiers/add" class="btn-primary pull-right">Créer un projet</a>
        </div>
    </div>

    <form method="post" name="search-dossier" id="search-dossier" class="form" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers">
        <input type="hidden" name="form_search_dossier">
        <input type="hidden" name="page" id="page" value="<?= $this->page ?>">
        <fieldset class="row primary">
            <div class="col-md-2 col-md-offset-1">
                <div class="form-group">
                    <label for="id">ID</label>
                    <input type="text" name="id" id="id" class="form-control" value="<?= isset($_POST['id']) ? $_POST['id'] : '' ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="siren">SIREN</label><br>
                    <input type="text" name="siren" id="siren" class="form-control" value="<?= isset($_POST['siren']) ? $_POST['siren'] : '' ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="raison-sociale">Raison sociale</label><br>
                    <input type="text" name="raison-sociale" id="raison-sociale" class="form-control" value="<?= isset($_POST['raison-sociale']) ? $_POST['raison-sociale'] : '' ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="project-need">Besoin</label>
                    <select name="projectNeed" id="project-need" class="form-control">
                        <option value="0"></option>
                        <?php foreach ($this->needs as $need) : ?>
                            <optgroup label="<?= $need['label'] ?>">
                                <?php foreach ($need['children'] as $needChild) : ?>
                                    <option<?= isset($_POST['projectNeed']) && $_POST['projectNeed'] == $needChild['id_project_need'] ? ' selected' : '' ?> value="<?= $needChild['id_project_need'] ?>"><?= $needChild['label'] ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="status">Statut</label>
                    <select name="status" id="status" class="form-control">
                        <option value=""></option>
                        <?php /** @var ProjectsStatus $projectStatus */ ?>
                        <?php foreach ($this->projectStatus as $projectStatus) : ?>
                            <option<?= isset($_POST['status']) && $_POST['status'] == $projectStatus->getStatus() || isset($this->params[0]) && $this->params[0] == $projectStatus->getStatus() ? ' selected' : '' ?> value="<?= $projectStatus->getStatus() ?>">
                                <?= $projectStatus->getLabel() ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </fieldset>
        <fieldset class="row secondary">
            <div class="col-md-2 col-md-offset-1">
                <div class="form-group">
                    <label for="datepik_1">Date début</label>
                    <input type="text" name="date1" id="datepik_1" class="form-control input-sm" value="<?= isset($_POST['date1']) ? $_POST['date1'] : '' ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="datepik_2">Date fin</label>
                    <input type="text" name="date2" id="datepik_2" class="form-control input-sm" value="<?= isset($_POST['date2']) ? $_POST['date2'] : '' ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="duree">Durée</label>
                    <select name="duree" id="duree" class="form-control input-sm">
                        <option value=""></option>
                        <?php foreach ($this->fundingTimeValues as $sFundingtime) : ?>
                            <option<?= isset($_POST['duree']) && $_POST['duree'] == $sFundingtime ? ' selected' : '' ?> value="<?= $sFundingtime ?>">
                                <?= $sFundingtime ?> mois
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="commercial">Commercial</label>
                    <select name="commercial" id="commercial" class="form-control input-sm">
                        <option value="0"></option>
                        <?php foreach ($this->aSalesPersons as $aSalesPerson) : ?>
                            <option<?= isset($_POST['commercial']) && $_POST['commercial'] == $aSalesPerson['id_user'] ? ' selected' : '' ?> value="<?= $aSalesPerson['id_user'] ?>">
                                <?= $aSalesPerson['firstname'] ?> <?= $aSalesPerson['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="analyste">Analyste</label>
                    <select name="analyste" id="analyste" class="form-control input-sm">
                        <option value="0"></option>
                        <?php foreach ($this->aAnalysts as $aAnalyst) : ?>
                            <option<?= isset($_POST['analyste']) && $_POST['analyste'] == $aAnalyst['id_user'] ? ' selected' : '' ?> value="<?= $aAnalyst['id_user'] ?>">
                                <?= $aAnalyst['firstname'] ?> <?= $aAnalyst['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </fieldset>

        <div class="btn-controls text-center">
            <button type="submit" id="send-dossier" class="btn-primary" style="margin-right: 5px;">Rechercher</button>
            <button type="button" id="reset" class="btn-default">Réinitialiser</button>
        </div>
    </form>

    <?php if (isset($this->searchResult, $this->resultsCount)) : ?>
        <?php if (0 === $this->resultsCount) : ?>
            <h1>Aucun projet trouvé</h1>
            <p>Il n'y a aucun projet pour cette recherche.</p>
        <?php else : ?>
            <div class="row">
                <div class="col-md-12">
                    <?php if ($this->resultsCount == 1) : ?>
                        <h1>1 projet trouvé</h1>
                    <?php elseif ($this->resultsCount > 0) : ?>
                        <h1><?= $this->ficelle->formatNumber($this->resultsCount, 0) ?> projets trouvés</h1>
                    <?php endif; ?>
                </div>
            </div>
            <table class="tablesorter table table-hover table-striped">
                <thead>
                <tr>
                    <th style="width:4%">ID</th>
                    <th style="width:6%">SIREN</th>
                    <th style="width:11%">Raison sociale</th>
                    <th style="width:9%">Demande</th>
                    <th style="width:8%">Montant</th>
                    <th style="width:8%">Durée</th>
                    <th style="width:12%">Statut</th>
                    <th style="width:12%">Commercial</th>
                    <th style="width:9%">Analyste</th>
                    <?php if ($this->isRiskUser) : ?>
                        <th style="width:4%">Pré-score</th>
                    <?php endif; ?>
                    <th style="width:4%">Comment.</th>
                    <?php if ($this->hasRepaymentAccess) : ?>
                        <th style="width:9%">Remb. auto</th>
                        <th style="width:2%">Remb.</th>
                    <?php endif; ?>
                    <th style="width:2%">Détails</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php /** @var Projects $project */ ?>
                <?php foreach ($this->searchResult as $project) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> data-project="<?= $project->getIdProject() ?>">
                        <td><?= $project->getIdProject() ?></td>
                        <?php if ($project->getIdCompany()->getIdClientOwner()) : ?>
                            <td><a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $project->getIdCompany()->getIdClientOwner()->getIdClient() ?>"><?= $project->getIdCompany()->getSiren() ?></a></td>
                            <td><a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $project->getIdCompany()->getIdClientOwner()->getIdClient() ?>"><?= $project->getIdCompany()->getName() ?></a></td>
                        <?php else : ?>
                            <td><?= $project->getIdCompany()->getSiren() ?></td>
                            <td><?= $project->getIdCompany()->getName() ?></td>
                        <?php endif; ?>
                        <td><?= $project->getAdded()->format('d/m/Y') ?></td>
                        <td><?= $this->ficelle->formatNumber($project->getAmount(), 0) ?> €</td>
                        <td><?= empty($project->getPeriod()) ? '' : $project->getPeriod() . ' mois' ?></td>
                        <td><?= $this->projectStatus[$project->getStatus()]->getLabel() ?></td>
                        <td><?= $project->getIdCommercial() && $project->getIdCommercial()->getIdUser() ? $project->getIdCommercial()->getFirstname() . ' ' . $project->getIdCommercial()->getName() : '' ?></td>
                        <td><?= $project->getIdAnalyste() && $project->getIdAnalyste()->getIdUser() ? $project->getIdAnalyste()->getFirstname() . ' ' . $project->getIdAnalyste()->getName() : '' ?></td>
                        <?php if ($this->isRiskUser) : ?>
                            <?php $notes = $this->projectNotesRepository->findOneBy(['idProject' => $project]); ?>
                            <td><?= $notes && $notes->getPreScoring() ? $notes->getPreScoring() : '' ?></td>
                        <?php endif; ?>
                        <?php if (false === empty($project->getComments())) : ?>
                            <td data-toggle="tooltip" class="tooltip" title="<?= htmlspecialchars($project->getComments()) ?>">oui</td>
                        <?php else : ?>
                            <td>non</td>
                        <?php endif; ?>
                        <?php if ($this->hasRepaymentAccess) : ?>
                            <?php if ($project->getStatus() >= ProjectsStatus::STATUS_REPAYMENT) : ?>
                                <td><?= Projects::AUTO_REPAYMENT_ON === $project->getRembAuto() ? 'oui' : 'non' ?></td>
                                <td align="center">
                                    <a href="<?= $this->lurl ?>/remboursement/projet/<?= $project->getIdProject() ?>">
                                        <img src="<?= $this->surl ?>/images/admin/duplique.png" alt="Remboursement du projet <?= htmlspecialchars($project->getTitle()) ?>">
                                    </a>
                                </td>
                            <?php else : ?>
                                <td></td>
                                <td></td>
                            <?php endif; ?>
                        <?php endif; ?>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/edit/<?= $project->getIdProject() ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= htmlspecialchars($project->getTitle()) ?>">
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            <table>
                <tr>
                    <td id="pager">
                        <?php if ($this->page > 1) : ?>
                            <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first" onclick="paginationDossiers('first');">
                            <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev" onclick="paginationDossiers('prev');">
                        <?php endif; ?>
                        <?php if ($this->page < ceil($this->resultsCount / $this->nb_lignes)) : ?>
                            <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next" onclick="paginationDossiers('next');">
                            <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last" onclick="paginationDossiers('last');">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
