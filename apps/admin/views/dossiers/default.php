<script>
    var nbPages = <?= isset($this->nb_lignes) && $this->nb_lignes > 0 ? ceil($this->iCountProjects / $this->nb_lignes) : 0 ?>;

    $(function () {
        $('[data-toggle="tooltip"]').tooltip();

        $('body').on('click', '[data-project]', function (event) {
            var projectId = $(this).data('project')
            if (projectId && ! $(event.target).is('a') && ! $(event.target).is('img')) {
                $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/' + projectId)
            }
        })

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'
        });

        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'
        });

        $("#reset").click(function () {
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
          source: '<?= $this->url ?>/dossiers/autocompleteCompanyName/',
          minLength: 3,
          delay: 100
        });

        $(".tablesorter").tablesorter({headers: {5: {sorter: 'digit'}, 9: {sorter: false}, 10: {sorter: false}, 11: {sorter: false}}});

        $('#display-pager').html($('#page-active').val() + '/' + nbPages);

        $('#send-dossier').click(function () {
            $('#nb-ligne-pagination').val(0);
            $('#page-active').val(1);
        });

        <?php if (
            false === isset($_SESSION['project_search_page_disclaimer'])
            && (
                in_array($this->userEntity->getIdUserType()->getIdUserType(), [\users_types::TYPE_COMMERCIAL, \users_types::TYPE_RISK])
                || in_array($this->userEntity->getIdUser(), [23, 28])
            )
        ) : ?>
            $.colorbox({html: $('#deprecated-page-disclaimer').html(), overlayClose: false, escKey: false});
            <?php $_SESSION['project_search_page_disclaimer'] = true; ?>
        <?php endif; ?>
    });

    function paginationDossiers(directionPagination) {
        var nbLignePagination = Math.round($('#nb-ligne-pagination').val());
        var pageActive = Math.round($('#page-active').val());
        var totalLignePagination = <?= $this->iCountProjects - $this->nb_lignes ?>;

        switch (directionPagination) {
            case 'first':
                $('#nb-ligne-pagination').val(0);
                $('#page-active').val(1);
                break;
            case 'prev':
                if (nbLignePagination > <?= $this->nb_lignes ?>) {
                    nbLignePagination = nbLignePagination -<?= $this->nb_lignes ?>;
                    $('#page-active').val(pageActive - 1);
                }
                $('#nb-ligne-pagination').val(nbLignePagination);
                break;
            case 'next':
                nbLignePagination = nbLignePagination +<?= $this->nb_lignes ?>;
                if (nbLignePagination <= totalLignePagination) {
                    $('#nb-ligne-pagination').val(nbLignePagination);
                    $('#page-active').val(pageActive + 1);
                }
                break;
            case 'last':
                nbLignePagination = totalLignePagination;
                $('#nb-ligne-pagination').val(nbLignePagination);
                $('#page-active').val(nbPages);
                break;
        }

        $('#search-dossier').submit();
    }
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
<div id="deprecated-page-disclaimer" style="display:none;">
    <div style="padding:10px;">
        <h1>Cette fonctionnalité va bientôt être supprimée</h1>
        <p>Le <a href="<?php $this->lurl ?>/dashboard">flux</a> doit maintenant constituer le point d'entrée vers un dossier.</p>
        <p>S'il manque des fonctionnalités qui ne vous permettent pas de vous passer de cette page de recherche, merci d'en faire part à Oliver afin de trouver une solution.</p>
        <div style="text-align:center; margin-top:30px;">
            <a role="button" class="btn_link" onclick="parent.$.fn.colorbox.close();">Accéder à la recherche</a>
            <a href="<?php $this->lurl ?>/dashboard" class="btn_link">Accéder à mon flux</a>
        </div>
    </div>
</div>
<div id="contenu">

    <div class="row">
        <div class="col-md-6">
            <?php if (isset($this->iCountProjects) && $this->iCountProjects == 0) : ?>
                <h1>Aucun dossier trouvé</h1>
            <?php elseif (isset($this->iCountProjects) && $this->iCountProjects == 1) : ?>
                <h1>1 dossier trouvé</h1>
            <?php elseif (isset($this->iCountProjects) && $this->iCountProjects > 0) : ?>
                <h1><?= $this->ficelle->formatNumber($this->iCountProjects, 0) ?> dossiers trouvés</h1>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->lurl ?>/dossiers/add/create" class="btn-primary pull-right">Créer un dossier</a>
        </div>
    </div>

    <form method="post" name="search-dossier" id="search-dossier" class="form" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers">
        <input type="hidden" name="search-dossier-input" id="search-dossier-input">
        <input type="hidden" name="nb-ligne-pagination" id="nb-ligne-pagination" value="<?= isset($_POST['nb-ligne-pagination']) ? $_POST['nb-ligne-pagination'] : 0 ?>">
        <input type="hidden" name="page-active" id="page-active" value="<?= isset($_POST['page-active']) ? $_POST['page-active'] : 1 ?>">
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
                    <select name="project-need" id="project-need" class="form-control">
                        <option value="0"></option>
                        <?php foreach ($this->needs as $need) : ?>
                            <optgroup label="<?= $need['label'] ?>">
                                <?php foreach ($need['children'] as $needChild) : ?>
                                    <option value="<?= $needChild['id_project_need'] ?>"><?= $needChild['label'] ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value=""></option>
                        <?php foreach ($this->lProjects_status as $s) : ?>
                            <option <?= isset($_POST['status']) && $_POST['status'] == $s['status'] || isset($this->params[0]) && $this->params[0] == $s['status'] ? 'selected' : '' ?> value="<?= $s['status'] ?>">
                                <?= $s['label'] ?>
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
                            <option <?= isset($_POST['duree']) && $_POST['duree'] == $sFundingtime ? 'selected' : '' ?> value="<?= $sFundingtime ?>"><?= $sFundingtime ?> mois</option>
                        <?php endforeach; ?>
                        <option <?= isset($_POST['duree']) && $_POST['duree'] == '1000000' ? 'selected' : '' ?> value="1000000">je ne sais pas</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="commercial">Commercial</label>
                    <select name="commercial" id="commercial" class="form-control input-sm">
                        <option value="0"></option>
                        <?php foreach ($this->aSalesPersons as $aSalesPerson) : ?>
                            <option <?= isset($_POST['commercial']) && $_POST['commercial'] == $aSalesPerson['id_user'] ? 'selected' : '' ?> value="<?= $aSalesPerson['id_user'] ?>"><?= $aSalesPerson['firstname'] ?> <?= $aSalesPerson['name'] ?></option>
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
                            <option <?= isset($_POST['analyste']) && $_POST['analyste'] == $aAnalyst['id_user'] ? 'selected' : '' ?> value="<?= $aAnalyst['id_user'] ?>"><?= $aAnalyst['firstname'] ?> <?= $aAnalyst['name'] ?></option>
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

    <?php if (isset($this->lProjects) && count($this->lProjects) > 0) : ?>
        <table class="tablesorter table table-hover table-striped">
            <thead>
                <tr>
                    <th style="width:4%">ID</th>
                    <th style="width:6%">SIREN</th>
                    <th style="width:22%">Raison sociale</th>
                    <th style="width:9%">Demande</th>
                    <th style="width:8%">Montant</th>
                    <th style="width:8%">Durée</th>
                    <th style="width:12%">Statut</th>
                    <th style="width:12%">Commercial</th>
                    <th style="width:9%">Analyste</th>
                    <th style="width:4%">Presc.</th>
                    <th style="width:4%">Comment.</th>
                    <th style="width:2%"></th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lProjects as $p) : ?>
                    <?php
                        $this->oUserAnalyst->get($p['id_analyste'], 'id_user');
                        $this->oUserSalesPerson->get($p['id_commercial'], 'id_user');
                    ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> data-project="<?= $p['id_project'] ?>">
                        <td><?= $p['id_project'] ?></td>
                        <td><?= $p['siren'] ?></td>
                        <td><?= $p['name'] ?></td>
                        <td><?= $this->dates->formatDate($p['added'], 'd/m/Y') ?></td>
                        <td><?= $this->ficelle->formatNumber($p['amount'], 0) ?> €</td>
                        <td><?= ($p['period'] == 1000000 || $p['period'] == 0) ? 'Je ne sais pas' : $p['period'] . ' mois' ?></td>
                        <td><?= $p['label'] ?></td>
                        <td><?= $this->oUserSalesPerson->firstname ?> <?= $this->oUserSalesPerson->name ?></td>
                        <td><?= $this->oUserAnalyst->firstname ?> <?= $this->oUserAnalyst->name ?></td>
                        <td><?= ($p['id_prescripteur']) ? '<img src="'. $this->surl .'/images/admin/check.png" alt="a prescripteur">' : '' ?></td>
                        <td data-toggle="tooltip" class="tooltip" title="<?= $p['comments'] && $p['comments'] != '' ? $p['comments'] : '' ?>"><?= $p['comments'] && $p['comments'] != '' ? 'oui' : 'non' ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/edit/<?= $p['id_project'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $p['title'] ?>">
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
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first" onclick="paginationDossiers('first');">
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev" onclick="paginationDossiers('prev');">
                        <span id="displayPager"></span>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next" onclick="paginationDossiers('next');">
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last" onclick="paginationDossiers('last');">
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php elseif (isset($_POST['form_search_emprunteur'])) : ?>
        <p>Il n'y a aucun dossier pour cette recherche.</p>
    <?php endif; ?>
</div>
