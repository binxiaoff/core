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
                    $(this).parent().children().css('background-color', '#b20066').css('color', '#fff')
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
        background-color: #6d1f4f !important;
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
    <h1>Mes projets (<?= $this->userProjects['count'] ?>)</h1>
    <div id="user-projects">
        <?php $this->templateProjects = $this->userProjects; ?>
        <?php $this->fireView($this->template . 'Projects'); ?>
    </div>
    <h1>
        Mon équipe (<span id="team-projects-count"><?= $this->teamProjects['count'] ?></span>)
        <?php if ('sale' === $this->template) : ?>
            <select id="sales-projects-selector" style="margin-left: 10px">
                <option value=""></option>
                <?php foreach ($this->salesPeople as $salesperson) : ?>
                    <option value="<?= $salesperson['id_user'] ?>"><?= $salesperson['firstname'] ?> <?= $salesperson['name'] ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </h1>
    <div id="team-projects">
        <?php $this->templateProjects = $this->teamProjects; ?>
        <?php $this->fireView($this->template . 'Projects'); ?>
    </div>
    <?php if (isset($this->upcomingProjects)) : ?>
        <h1>À venir</h1>
        <div id="upcoming-projects">
            <?php $this->templateProjects = $this->upcomingProjects; ?>
            <?php $this->fireView($this->template . 'Projects'); ?>
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
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?> data-project="<?= $project['id_project'] ?>">
                        <td><?= $project['id_project'] ?></td>
                        <td><?= $project['siren'] ?></td>
                        <td style="text-align: right"><?= $this->ficelle->formatNumber($project['amount'], 0) ?>&nbsp;€</td>
                        <td><?php if (false === empty($project['duration'])) : ?><?= $project['duration'] ?> mois<?php endif; ?></td>
                        <td><?= $project['creation']->format('d/m/Y - H\hi') ?></td>
                    </tr>
                    <?php ++$i; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
