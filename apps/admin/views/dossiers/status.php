<style type="text/css">
    .status-chart {
        border: 1px solid #b20066;
        border-radius: 10px;
        display: inline-block;
        height: 600px;
        width: 100%;
    }
    .status-chart.half {
        width: 573px;
    }
    #second-status-chart {
        float: right;
    }
    .search-box {
        border: 1px #b20066 solid;
        border-radius: 10px;
        margin: 10px auto;
        padding: 15px;
    }
    .search-box > legend {
        font-size: 14px;
        font-weight: bold;
        padding: 0 5px;
    }
    .search-box > table {
        width: 100%;
    }
    .search-box > table td {
        width: 33%;
    }
    .search-box > table td:first-child {
        width: 34%;
    }
</style>
<div id="contenu">
    <form method="post" action="<?= $this->lurl ?>/dossiers/status">
        <fieldset class="search-box">
            <legend>Recherche</legend>
            <table>
                <thead>
                    <tr>
                        <th><label for="status">Statut</label></th>
                        <th><label for="first-range-start">Période</label></th>
                        <th><label for="second-range-start">Période à comparer (optionnel)</label></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td rowspan="2">
                            <select id="status" name="status" class="select">
                                <option></option>
                                <?php foreach ($this->statuses as $status) : ?>
                                    <option value="<?= $status['id_project_status'] ?>"<?= isset($this->baseStatus) && $this->baseStatus == $status['id_project_status'] ? 'selected="selected"' : '' ?>><?= $status['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" id="first-range-start" name="first-range-start" placeholder="Date de début" value="<?= isset($this->firstRangeStart) ? $this->firstRangeStart->format('d/m/Y') : '' ?>" class="input_dp" />
                        </td>
                        <td>
                            <input type="text" id="second-range-start" name="second-range-start" placeholder="Date de début" value="<?= isset($this->secondRangeStart) ? $this->secondRangeStart->format('d/m/Y') : '' ?>" class="input_dp" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="text" id="first-range-end" name="first-range-end" placeholder="Date de fin" value="<?= isset($this->firstRangeEnd) ? $this->firstRangeEnd->format('d/m/Y') : '' ?>" class="input_dp" />
                        </td>
                        <td>
                            <input type="text" id="second-range-end" name="second-range-end" placeholder="Date de fin" value="<?= isset($this->secondRangeEnd) ? $this->secondRangeEnd->format('d/m/Y') : '' ?>" class="input_dp" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div style="text-align: right;"><button type="submit" class="btn">Valider</button></div>
        </fieldset>
    </form>
    <?php if (isset($this->history)) : ?>
        <span id="status-chart" class="status-chart<?php if (isset($this->compareHistory)) : ?> half<?php endif; ?>"><?= $this->firstRangeStart->format('d/m/Y') ?></span>
        <?php if (isset($this->compareHistory)) : ?>
            <span id="second-status-chart" class="status-chart half"></span>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($this->params[0]) && false === isset($this->history)) : ?>
        <div class="attention" style="background-color:#F2F258">
            Les critères de recherche n'ont retourné aucun résultat.
        </div>
    <?php endif; ?>
</div>
<script type="text/javascript">
    $(function() {
        $(".input_dp").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '2013:<?= date('Y') ?>'
        });

        <?php if (isset($this->history)) : ?>
            var nodes = new vis.DataSet([
                {id: 'level1', label: '<?= $this->history['label'] ?> : <?= $this->history['count'] ?>', level: 1},
                <?php foreach ($this->history['children'] as $childStatus => $child) : ?>
                    <?php if (0 == $childStatus) : ?>
                        {id: 'level2-<?= $childStatus ?>', label: 'Pas de changement : <?= $child['count'] ?>', level: 2, group: 'disabled'},
                    <?php else : ?>
                        {id: 'level2-<?= $childStatus ?>', label: '<?= $child['label'] ?> : <?= $child['count'] ?>\n\nMoyenne : <?= $child['avg_days'] ?> jours\nDate max : <?= $this->dates->formatDate($child['max_date'], 'd/m/Y') ?>', level: 2},
                        <?php if (count($child['children']) > 1 || false === isset($child['children'][0])) : ?>
                            <?php foreach ($child['children'] as $subChildStatus => $subChild) : ?>
                                <?php if (0 == $subChildStatus) : ?>
                                    {id: 'level3-<?= $childStatus ?>-<?= $subChildStatus ?>', label: 'Pas de changement : <?= $subChild['count'] ?>', level: 3, group: 'disabled'},
                                <?php else : ?>
                                    {id: 'level3-<?= $childStatus ?>-<?= $subChildStatus ?>', label: '<?= $subChild['label'] ?> : <?= $subChild['count'] ?>\n\nMoyenne : <?= $subChild['avg_days'] ?> jours\nDate max : <?= $this->dates->formatDate($subChild['max_date'], 'd/m/Y') ?>', level: 3},
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            ]);

            var edges = new vis.DataSet([
                <?php foreach ($this->history['children'] as $childStatus => $child) : ?>
                    <?php if (0 == $childStatus) : ?>
                        {from: 'level1', to: 'level2-<?= $childStatus ?>', label: '<?= round($child['count'] / $this->history['count'] * 100, 1) ?> %', color: '#aaa'},
                    <?php else : ?>
                        {from: 'level1', to: 'level2-<?= $childStatus ?>', label: '<?= round($child['count'] / $this->history['count'] * 100, 1) ?> %'},
                        <?php foreach ($child['children'] as $subChildStatus => $subChild) : ?>
                            <?php if (0 == $subChildStatus) : ?>
                                {from: 'level2-<?= $childStatus ?>', to: 'level3-<?= $childStatus ?>-<?= $subChildStatus ?>', label: '<?= round($subChild['count'] / $child['count'] * 100, 1) ?> %', color: '#aaa'},
                            <?php else : ?>
                                {from: 'level2-<?= $childStatus ?>', to: 'level3-<?= $childStatus ?>-<?= $subChildStatus ?>', label: '<?= round($subChild['count'] / $child['count'] * 100, 1) ?> %'},
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            ]);

            var container = document.getElementById('status-chart');
            var data = {
                nodes: nodes,
                edges: edges
            };
            var options = {
                layout: {
                    hierarchical: {
                        direction: 'LR',
                        levelSeparation: 275,
                        nodeSpacing: 125
                    }
                },
                interaction: {
                    dragNodes: false,
                    navigationButtons: true,
                    keyboard: {
                        bindToWindow: true
                    }
                },
                physics: {
                    enabled: false
                },
                nodes: {
                    borderWidth: 6,
                    borderWidthSelected: 6,
                    color: {
                        background: '#b20066',
                        border: '#b20066',
                        highlight: {
                            background: '#8e0252',
                            border: '#8e0252'
                        }
                    },
                    font: {
                        align: 'left',
                        color: '#fff',
                        size: 16
                    },
                    labelHighlightBold: false,
                    shape: 'box',
                    shapeProperties: {
                        borderRadius: 0
                    }
                },
                edges: {
                    arrows: 'to',
                    font: {
                        background: '#fff',
                        size: 15
                    },
                    width: 2
                },
                groups: {
                    disabled: {
                        color: {
                            background: '#aaa',
                            border: '#aaa',
                            highlight: {
                                background: '#999',
                                border: '#999'
                            }
                        }
                    }
                }
            };
            var statusTree = new vis.Network(container, data, options);

            <?php if (isset($this->compareHistory)) : ?>
                nodes = new vis.DataSet([
                    {id: 'level1', label: '<?= $this->compareHistory['label'] ?> : <?= $this->compareHistory['count'] ?>', level: 1},
                    <?php foreach ($this->compareHistory['children'] as $childStatus => $child) : ?>
                        <?php if (0 == $childStatus) : ?>
                            {id: 'level2-<?= $childStatus ?>', label: 'Pas de changement : <?= $child['count'] ?>', level: 2, group: 'disabled'},
                        <?php else : ?>
                            {id: 'level2-<?= $childStatus ?>', label: '<?= $child['label'] ?> : <?= $child['count'] ?>\n\nMoyenne : <?= $child['avg_days'] ?> jours\nDate max : <?= $this->dates->formatDate($child['max_date'], 'd/m/Y') ?>', level: 2},
                            <?php if (count($child['children']) > 1 || false === isset($child['children'][0])) : ?>
                                <?php foreach ($child['children'] as $subChildStatus => $subChild) : ?>
                                    <?php if (0 == $subChildStatus) : ?>
                                        {id: 'level3-<?= $childStatus ?>-<?= $subChildStatus ?>', label: 'Pas de changement : <?= $subChild['count'] ?>', level: 3, group: 'disabled'},
                                    <?php else : ?>
                                        {id: 'level3-<?= $childStatus ?>-<?= $subChildStatus ?>', label: '<?= $subChild['label'] ?> : <?= $subChild['count'] ?>\n\nMoyenne : <?= $subChild['avg_days'] ?> jours\nDate max : <?= $this->dates->formatDate($subChild['max_date'], 'd/m/Y') ?>', level: 3},
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                ]);

                edges = new vis.DataSet([
                    <?php foreach ($this->compareHistory['children'] as $childStatus => $child) : ?>
                        <?php if (0 == $childStatus) : ?>
                            {from: 'level1', to: 'level2-<?= $childStatus ?>', label: '<?= round($child['count'] / $this->compareHistory['count'] * 100, 1) ?> %', color: '#aaa'},
                        <?php else : ?>
                            {from: 'level1', to: 'level2-<?= $childStatus ?>', label: '<?= round($child['count'] / $this->compareHistory['count'] * 100, 1) ?> %'},
                            <?php foreach ($child['children'] as $subChildStatus => $subChild) : ?>
                                <?php if (0 == $subChildStatus) : ?>
                                    {from: 'level2-<?= $childStatus ?>', to: 'level3-<?= $childStatus ?>-<?= $subChildStatus ?>', label: '<?= round($subChild['count'] / $child['count'] * 100, 1) ?> %', color: '#aaa'},
                                <?php else : ?>
                                    {from: 'level2-<?= $childStatus ?>', to: 'level3-<?= $childStatus ?>-<?= $subChildStatus ?>', label: '<?= round($subChild['count'] / $child['count'] * 100, 1) ?> %'},
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                ]);

                container = document.getElementById('second-status-chart');
                data = {
                    nodes: nodes,
                    edges: edges
                };
                var secondStatusTree = new vis.Network(container, data, options);
            <?php endif; ?>
        <?php endif; ?>
    });
</script>
