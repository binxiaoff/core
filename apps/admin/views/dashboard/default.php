<link href="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css" type="text/css" rel="stylesheet">
<script src="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<style>
    @font-face {
        font-family: 'FontAwesome';
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot');
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot?#iefix&v=4.7.0') format('embedded-opentype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff2') format('woff2'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff') format('woff'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.ttf') format('truetype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.svg#fontawesomeregular') format('svg');
        font-weight: normal;
        font-style: normal;
    }
</style>
<script>
    $(function() {
        $('[data-toggle="tooltip"]').tooltip({
            // For line breaks
            content: function() {
                return $(this).attr('title');
            }
        })

        $('body').on('click', '[data-project]', function (event) {
            var projectId = $(this).data('project')
            if (projectId) {
                if (event.shiftKey || event.ctrlKey || event.metaKey) {
                    window.open('<?= $this->lurl ?>/dossiers/edit/' + projectId, '_blank')
                } else {
                    $(this).parent().children().css('background-color', '#2bc9af').css('color', '#fff')
                    $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/' + projectId)
                }
            }
        })

        $('body').on('contextmenu', '[data-project]', function (event) {
            event.preventDefault()
            var projectId = $(this).data('project')
            if (projectId) {
                window.open('<?= $this->lurl ?>/dossiers/edit/' + projectId, '_blank')
            }
        })

        $('body').on('click', '.status-line', function() {
            $(this).toggleClass('expand').nextUntil('.status-line').slideToggle(0)
        })

        $('#sales-projects-selector').change(function () {
            var userId = $(this).val()

            $.ajax({
                method: 'POST',
                url: '<?= $this->lurl ?>/dashboard/salesperson_projects',
                dataType: 'json',
                data: {
                    userId: userId
                }
            }).done(function(response) {
                $('#team-projects-count').text(response.count)
                $('#team-projects').html(response.projects)

                $('[data-toggle="tooltip"]').tooltip({
                    // For line breaks
                    content: function() {
                        return $(this).attr('title');
                    }
                })
            })
        })
        if ($('.projects-to-decline-div') !== undefined) {
            var dt = $('#projects-to-decline-table').DataTable({
                ajax: '/dashboard/projets_a_dechoir',
                language: {
                    url: '/oneui/js/plugins/datatables/localisation/fr_FR.json'
                },
                columnDefs: [
                    {visible: false, targets: [5]}
                ],
                initComplete:function (settings, json) {
                    if (json.error !== null) {
                        $('.dataTables_empty').html(json.error)
                    }
                },
                createdRow: function (row, data, index) {
                    var $row = $(row)
                    $row.find('td:first-child').html(getProjectUrl(data[5], data[0]))
                    var $otherProjectsColumn = $row.find('td:last-child')
                    var projectList = ''
                    $.each(data[4], function (key, projectId) {
                        projectList += getProjectUrl(projectId)
                        if (key < data[4].length - 1 ) {
                            projectList += ', '
                        }
                    })
                    $otherProjectsColumn.html(projectList)
                }
            })
            function getProjectUrl(projectId, projectTitle) {
                var linkText = projectTitle !== undefined ? projectTitle : projectId
                return '<a href="/dossiers/edit/' + projectId + '">' + linkText + '</a>'
            }
            $.fn.dataTable.ext.errMode = 'none';
            dt.on('error.dt', function (e, settings, techNote, message) {
                console.log(message)
            })
        }
    })
</script>
<style>
    table.tablesorter thead tr th {
        font-size: 12px;
    }
    table.tablesorter.projects tbody tr td.partner-logo {
        padding: 1px;
        text-align: center;
        vertical-align: middle;
    }
    table.tablesorter.projects tbody tr td.partner-logo img {
        margin: 0;
        max-height: 20px;
        max-width: 20px;
    }
    .status-line,
    .projects td[data-project] {
        cursor: pointer;
    }
    .projects .status-line td {
        background-color: #288171 !important;
        color: #fff !important;
        font-size: 13px;
    }
    .status-line .sign:after{
        content: '+';
        display: inline-block;
    }
    .status-line.expand .sign:after{
        content: '-';
    }
    .warning {
        background-color: #ffe0f0 !important;
    }
    h1:not(:first-child) {
        margin-top: 20px;
    }
    #impossible-evaluation-projects {
        display: none;
        margin-top: 20px;
    }

</style>
<div id="contenu">
    <?php if (true === $this->showProjectsToDecline) : ?>
        <div class="projects-to-decline-div">
            <h1>Projets à déchoir</h1>
            <table id="projects-to-decline-table" class="table table-bordered table-striped" style="width: 100%">
                <thead>
                <tr>
                    <th>Projet</th>
                    <th>Société</th>
                    <th>Écart DDT</th>
                    <th>Date de funding</th>
                    <th>Projets de la même société</th>
                </tr>
                </thead>
            </table>
        </div>
    <?php endif; ?>

    <?php if (false === empty($this->impossibleEvaluationProjects)) : ?>
        <h1><a href="javascript:$('#impossible-evaluation-projects').slideToggle();">Évaluation impossible</a> (<?= count($this->impossibleEvaluationProjects) ?>)</h1>
        <a href="<?= $this->lurl ?>/dashboard/evaluate_projects" class="btn_link">Ré-évaluer les projets</a>
        <div id="impossible-evaluation-projects">
            <table class="tablesorter projects">
                <thead>
                <tr>
                    <th style="width: 150px">ID</th>
                    <th>SIREN</th>
                    <th style="width: 150px">Montant</th>
                    <th style="width: 150px">Durée</th>
                    <th style="width: 250px">Création</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->impossibleEvaluationProjects as $project) : ?>
                    <?php $i = 0; ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> data-project="<?= $project->getIdProject() ?>">
                        <td><?= $project->getIdProject() ?></td>
                        <td><?php if ($project->getIdCompany()) : ?><?= $project->getIdCompany()->getSiren() ?><?php endif; ?></td>
                        <td style="text-align: right"><?= $this->ficelle->formatNumber($project->getAmount(), 0) ?>&nbsp;€</td>
                        <td><?php if (false === empty($project->getPeriod())) : ?><?= $project->getPeriod() ?> mois<?php endif; ?></td>
                        <td><?= $project->getAdded()->format('d/m/Y - H\hi') ?></td>
                    </tr>
                    <?php ++$i; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (false === empty($this->otherTasksProjects)): ?>
        <h1>Tâches annexes</h1>
        <div class="other-tasks-projects">
            <table id="other-tasks-projects-table" class="tablesorter projects" style="width: 100%">
                <thead>
                <tr>
                    <th style="width:50px;"></th>
                    <th style="width:40px">ID</th>
                    <th>Raison sociale</th>
                    <th style="width:70px">Montant</th>
                    <th style="width:70px">Durée</th>
                    <th style="width:180px">Nom dirigeant</th>
                    <th style="width:80px">Téléphone</th>
                    <th style="width:110px">Création</th>
                    <th style="width:50px">Dernier<br/>mémo</th>
                    <th style="width:50px">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->otherTasksProjects as $taskLabel => $taskProjects) : ?>
                    <?php if (0 < $taskProjects['count']) : ?>
                        <tr class="status-line expand">
                            <td colspan="10"><span class="sign"></span> <?= $taskLabel ?> (<?= $taskProjects['count'] ?>)</td>
                        </tr>
                        <?php $i = 0; ?>
                        <?php foreach ($taskProjects['projects'] as $project) : ?>
                            <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                                <td class="partner-logo">
                                    <?php if (false === empty($project['partner_logo'])) : ?>
                                        <img src="<?= $this->surl ?>/images/admin/partner/<?= $project['partner_logo'] ?>" alt="<?= $project['partner_logo'] ?>">
                                    <?php endif; ?>
                                    <?php if (true === $project['hasMonitoringEvent']) : ?>
                                        <span class="e-change-warning"></span>
                                    <?php endif; ?>
                                </td>
                                <td data-project="<?= $project['id_project'] ?>"><?= $project['id_project'] ?></td>
                                <td data-project="<?= $project['id_project'] ?>"><?= $project['company_name'] ?></td>
                                <td style="text-align:right"><?= $this->ficelle->formatNumber($project['amount'], 0) ?>&nbsp;€</td>
                                <td><?php if (false === empty($project['duration'])) : ?><?= $project['duration'] ?> mois<?php endif; ?></td>
                                <td><?= $project['client_name'] ?></td>
                                <td><a href="tel:0<?= $project['client_phone'] ?>"><?= $project['client_phone'] ?></a></td>
                                <td><?= \DateTime::createFromFormat('Y-m-d H:i:s', $project['creation'])->format('d/m/Y - H\hi') ?></td>
                                <?php if (empty($project['memo_content'])) : ?>
                                    <td></td>
                                <?php else : ?>
                                    <td data-toggle="tooltip" class="tooltip" title="<?= (empty($project['memo_author']) ? '' : $project['memo_author'] . '<br>') . $project['memo_datetime']->format('d/m/Y - H\hi') . '<hr>' . nl2br(htmlentities($project['memo_content'], ENT_QUOTES)) ?>" style="text-align: center"><img src="<?= $this->surl ?>/images/admin/info.png" alt="Mémo" /></td>
                                <?php endif; ?>
                                <td></td>
                            </tr>
                            <?php ++$i; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (isset($this->upcomingProjects)) : ?>
        <h1>À venir</h1>
        <div id="upcoming-projects">
            <?php $this->templateProjects = $this->upcomingProjects; ?>
            <?php $this->fireView($this->template . 'Projects'); ?>
        </div>
    <?php endif; ?>

    <h1>Mes projets (<?= $this->userProjects['count'] ?>)</h1>
    <div id="user-projects">
        <?php $this->templateProjects = $this->userProjects; ?>
        <?php $this->collapsedStatus = \dashboardController::SALES_MY_PROJECTS_COLLAPSED_STATUS; ?>
        <?php $this->fireView($this->template . 'Projects'); ?>
    </div>

    <h1<?php if ('sale' === $this->template) : ?> class="pull-left"<?php endif; ?>>
        Mon équipe (<span id="team-projects-count"><?= $this->teamProjects['count'] ?></span>)
    </h1>
    <?php if ('sale' === $this->template) : ?>
        <select id="sales-projects-selector" class="form-control input-sm pull-left" style="margin: 22px 10px 0; width: 200px;">
            <option value="">Sélectionner</option>
            <?php foreach ($this->salesPeople as $salesperson) : ?>
                <option value="<?= $salesperson['id_user'] ?>"><?= $salesperson['firstname'] ?> <?= $salesperson['name'] ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <div id="team-projects" style="clear: both;">
        <?php $this->templateProjects = $this->teamProjects; ?>
        <?php $this->collapsedStatus = \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::SALES_TEAM; ?>
        <?php $this->fireView($this->template . 'Projects'); ?>
    </div>
</div>
