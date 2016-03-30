<style type="text/css">
    #status-chart {
        height: 650px;
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
                                <?php foreach ($this->aStatuses as $aStatus) : ?>
                                    <option value="<?= $aStatus['id_project_status'] ?>"<?= isset($this->iBaseStatus) && $this->iBaseStatus == $aStatus['id_project_status'] ? 'selected="selected"' : '' ?>><?= $aStatus['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" id="first-range-start" name="first-range-start" placeholder="Date de début" value="<?= isset($this->oFirstRangeStart) ? $this->oFirstRangeStart->format('d/m/Y') : '' ?>" class="input_dp" />
                        </td>
                        <td>
                            <input type="text" id="second-range-start" name="second-range-start" placeholder="Date de début" value="<?= isset($this->oSecondRangeStart) ? $this->oSecondRangeStart->format('d/m/Y') : '' ?>" class="input_dp" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="text" id="first-range-end" name="first-range-end" placeholder="Date de fin" value="<?= isset($this->oFirstRangeEnd) ? $this->oFirstRangeEnd->format('d/m/Y') : '' ?>" class="input_dp" />
                        </td>
                        <td>
                            <input type="text" id="second-range-end" name="second-range-end" placeholder="Date de fin" value="<?= isset($this->oSecondRangeEnd) ? $this->oSecondRangeEnd->format('d/m/Y') : '' ?>" class="input_dp" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div style="text-align: right;"><button type="submit" class="btn">Valider</button></div>
        </fieldset>
    </form>
    <?php if (isset($this->iBaseStatus)) : ?>
        <div id="status-chart"></div>
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

        var nodes = new vis.DataSet([
            {id: 'level1', label: '<?= $this->aHistory['label'] ?> : <?= $this->aHistory['count'] ?>', level: 1},
            <?php foreach ($this->aHistory['children'] as $iChildStatus => $aChild) : ?>
                <?php if (0 == $iChildStatus) : ?>
                    {id: 'level2-<?= $iChildStatus ?>', label: 'Pas de changement : <?= $aChild['count'] ?>', level: 2, group: 'disabled'},
                <?php else : ?>
                    {id: 'level2-<?= $iChildStatus ?>', label: '<?= $aChild['label'] ?> : <?= $aChild['count'] ?>\n\nMoyenne : <?= $aChild['avg_days'] ?>\nMin : <?= $aChild['min_days'] ?>\nMax : <?= $aChild['max_days'] ?>', level: 2},
                    <?php foreach ($aChild['children'] as $iSubChildStatus => $aSubChild) : ?>
                        <?php if (0 == $iSubChildStatus) : ?>
                            {id: 'level3-<?= $iChildStatus ?>-<?= $iSubChildStatus ?>', label: 'Pas de changement : <?= $aSubChild['count'] ?>', level: 3, group: 'disabled'},
                        <?php else : ?>
                            {id: 'level3-<?= $iChildStatus ?>-<?= $iSubChildStatus ?>', label: '<?= $aSubChild['label'] ?> : <?= $aSubChild['count'] ?>\n\nMoyenne : <?= $aSubChild['avg_days'] ?>\nMin : <?= $aSubChild['min_days'] ?>\nMax : <?= $aSubChild['max_days'] ?>', level: 3},
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        ]);

        var edges = new vis.DataSet([
            <?php foreach ($this->aHistory['children'] as $iChildStatus => $aChild) : ?>
                <?php if (0 == $iChildStatus) : ?>
                    {from: 'level1', to: 'level2-<?= $iChildStatus ?>', label: '<?= round($aChild['count'] / $this->aHistory['count'] * 100, 1) ?> %', color: '#aaa'},
                <?php else : ?>
                    {from: 'level1', to: 'level2-<?= $iChildStatus ?>', label: '<?= round($aChild['count'] / $this->aHistory['count'] * 100, 1) ?> %'},
                    <?php foreach ($aChild['children'] as $iSubChildStatus => $aSubChild) : ?>
                        <?php if (0 == $iSubChildStatus) : ?>
                            {from: 'level2-<?= $iChildStatus ?>', to: 'level3-<?= $iChildStatus ?>-<?= $iSubChildStatus ?>', label: '<?= round($aSubChild['count'] / $aChild['count'] * 100, 1) ?> %', color: '#aaa'},
                        <?php else : ?>
                            {from: 'level2-<?= $iChildStatus ?>', to: 'level3-<?= $iChildStatus ?>-<?= $iSubChildStatus ?>', label: '<?= round($aSubChild['count'] / $aChild['count'] * 100, 1) ?> %'},
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
                    levelSeparation: 300,
                    nodeSpacing: 125
                }
            },
            interaction: {
                dragNodes: false,
                tooltipDelay: 0
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
        statusTree.on('click', function(e) {console.log(e);});
    });
</script>