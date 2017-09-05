<style>
    .block {
        margin-bottom: 30px;
    }
    .block-header {
        padding-right: 15px;
        background: #b20066;
    }
    .block-header .table, .block-content .table {
        margin: 0;
    }
    .block-content {
        height: 450px;
        border-bottom: 1px solid #ddd;
        overflow-y: scroll;
        overflow-x: hidden;
    }
    .table-events {
        color: #4a4a4a;
    }
    .table-events tbody > tr > td {
        vertical-align: middle!important;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .e-date, .e-id, .e-action {
         width: 8%;
    }
    .e-siren {
        width: 12%;
    }
    .e-change {
        width: 32%;
    }
    .e-statut, .e-raison {
        width: 16%;
    }
    .e-action {
        text-align: right;
    }
    .e-action .btn-default {
        padding: 0;
        width: 22px;
    }
    .details {
        list-style: none;
        padding: 0; margin: 0;
    }
    .details td {
        color: #777;
    }
    .details td.label {
        color: #bdbdbd;
        font-size: 10px;
        text-transform: uppercase;
    }
    .details td.label:after {
        content: ': '
    }
    .details td.label, .details td.value {
        padding-right: 10px;
    }
    .positive .details td.value:nth-child(2) {
        color: #00a453;
    }
    .negative .details td.value:nth-child(2) {
        color: #a30a09;
    }
</style>

<script>
    $(function(){
        var $events = $('.table-events')

        $.tablesorter.addParser({
            id: 'PositiveOrNegativeChange',
            is: function(s) {
                return false;
            },
            format: function(s, table, cell, cellIndex) {
                return $(cell).attr('data-change');
            },
            type: 'numeric'
        });
        $events.tablesorter({
            headers: {
                6: {sorter: false},
                5: {sorter: 'PositiveOrNegativeChange'}
            }
        });
        $('.table-events-header th').click(function() {
            if ($(this).is('.header')) {
                var $th = $(this),
                    thIndex = $th.index(),
                    sortDirection = 1, // headerSortUp
                    sorting

                if (!$th.is('.sort-active')) {
                    $th.siblings().removeClass('sort-active')
                    $th.addClass('sort-active headerSortUp')
                }

                if ($th.is('.sort-active')) {
                    if ($th.is('.headerSortDown')) {
                        $th.removeClass('headerSortDown').addClass('headerSortUp')
                        sortDirection = 1
                    } else {
                        $th.removeClass('headerSortUp').addClass('headerSortDown')
                        sortDirection = 0
                    }
                }

                sorting = [[thIndex,sortDirection]]
                $events.trigger("sorton",[sorting]);
            }
        })
    })
</script>

<div id="contenu">

    <div class="row">
        <div class="col-md-6">
            <h1>Monitoring</h1>
        </div>
    </div>

    <section id="monitoring-events">
        <article class="block">
            <div class="block-header">
                <table class="table table-events-header tablesorter">
                    <thead>
                    <tr>
                        <th class="e-date header">Date</th>
                        <th class="e-id header">ID</th>
                        <th class="e-siren header">Siren</th>
                        <th class="e-raison header">Raison Sociale</th>
                        <th class="e-statut header">Statut</th>
                        <th class="e-change header">Changement</th>
                        <th class="e-action">&nbsp;</th>
                    </tr>
                    </thead>
                </table>
            </div>
            <div class="block-content">
                <table class="table table-striped table-events tablesorter">
                    <thead style="display: none">
                    <tr>
                        <th class="e-date">Date</th>
                        <th class="e-id">ID</th>
                        <th class="e-siren">Siren</th>
                        <th class="e-raison">Raison Sociale</th>
                        <th class="e-statut">Statut</th>
                        <th class="e-change">Changement</th>
                        <th class="e-action">&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->events as $project) : ?>
                        <tr>
                            <td class="e-date"><?= $this->dateFormatter->format(strtotime($project['added'])) ?></td>
                            <td class="e-id">
                                <?= $project['id_project'] ?>
                            </td>
                            <td class="e-siren">
                                <?= $project['siren'] ?>
                            </td>
                            <td class="e-raison">
                                <?= $project['name'] ?>
                            </td>
                            <td class="e-statut">
                                <?= $project['label'] ?>
                            </td>
                            <td class="e-change negative" data-change="0">
                                <table class="details">
                                    <tr>
                                        <td class="label"><?= $project['type'] ?></td>
                                        <td class="value"><?= $project['value'] < $project['previous_value'] ? '↓' : '↑' ?> <?= $project['value'] ?></td>
                                        <td class="label">Précédent</td>
                                        <td class="value"> <?= $project['previous_value'] ?></td>
                                    </tr>
                                </table>
                            </td>
                            <td class="e-action">
                                <a href="/dossiers/edit/<?= $project['id_project'] ?>" class="btn-default"><span>></span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</div>
