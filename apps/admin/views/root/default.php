<div id="contenu">

    <!-- START Highcharts -->

    <style>
        .controls > div {
            display: table-cell;
            padding: 30px;
            margin-bottom: 15px;
            background: #efefef;
        }
        .controls label {
            height: 20px;
            margin-bottom: 15px
        }
        .controls label span {
            display: inline-block;
            padding: 3px 15px;
            color: #fff;
        }
        .green {
            background: green;
        }
        .orange {
            background: orange;
        }
        .red {
            background: red;
        }

        .chart-wrapper {
            padding: 10px 20px 20px;
            background: #efefef;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/4.2.7/highcharts.js"></script>
    <script>
        $(function(){
            var _chart = new Highcharts.chart('highcharts-container', {
                title: {
                    text: ''
                },
                yAxis: {
                    title: {
                        text: 'Calls'
                    }
                },
                xAxis: {
                    type: 'datetime',
                    tickInterval: 1,
                    labels: {
                        rotation: 45,
                        formatter: function() {
                            if ($('.controls-period input:checked').val() === 'day') {
                                return Highcharts.dateFormat("%H:%M", this.value);
                            } else {
                                return Highcharts.dateFormat("%e %b", this.value);
                            }
                        }
                    }
                },
                series: [
                    {
                        name: 'Valid',
                        data: [],
                        color: 'green',
                        showInLegend: false
                    },
                    {
                        name: 'Warning',
                        data: [],
                        color: 'orange',
                        showInLegend: false
                    },
                    {
                        name: 'Error',
                        data: [],
                        color: 'red',
                        showInLegend: false
                    }
                ],
                credits: {
                    enabled: false
                }
            });

            var _chartData = {
                day: {
                    startDate: '01/01/2013 08:00',
                    status: {
                        valid: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 6
                            },
                            {
                                date: '01/01/2013 09:00',
                                volume: 7
                            },
                            {
                                date: '01/01/2013 10:00',
                                volume: 5
                            }
                        ],
                        warning: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 2
                            },
                            {
                                date: '01/01/2013 09:00',
                                volume: 1
                            },
                            {
                                date: '01/01/2013 10:00',
                                volume: 2
                            }
                        ],
                        error: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 2
                            },
                            {
                                date: '01/01/2013 09:00',
                                volume: 4
                            },
                            {
                                date: '01/01/2013 10:00',
                                volume: 1
                            }
                        ]
                    }
                },
                week: {
                    startDate: '01/01/2013 08:00',
                    status: {
                        valid: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 6
                            },
                            {
                                date: '01/02/2013 09:00',
                                volume: 7
                            },
                            {
                                date: '01/03/2013 10:00',
                                volume: 5
                            },
                            {
                                date: '01/04/2013 08:00',
                                volume: 8
                            },
                            {
                                date: '01/05/2013 09:00',
                                volume: 12
                            },
                            {
                                date: '01/06/2013 10:00',
                                volume: 20
                            },
                            {
                                date: '01/07/2013 10:00',
                                volume: 20
                            }
                        ],
                        warning: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 2
                            },
                            {
                                date: '01/02/2013 09:00',
                                volume: 1
                            },
                            {
                                date: '01/03/2013 10:00',
                                volume: 3
                            },
                            {
                                date: '01/04/2013 08:00',
                                volume: 4
                            },
                            {
                                date: '01/05/2013 09:00',
                                volume: 2
                            },
                            {
                                date: '01/06/2013 10:00',
                                volume: 3
                            },
                            {
                                date: '01/07/2013 10:00',
                                volume: 5
                            }
                        ],
                        error: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 1
                            },
                            {
                                date: '01/03/2013 09:00',
                                volume: 1
                            },
                            {
                                date: '01/03/2013 10:00',
                                volume: 0
                            },
                            {
                                date: '01/04/2013 08:00',
                                volume: 1
                            },
                            {
                                date: '01/05/2013 09:00',
                                volume: 3
                            },
                            {
                                date: '01/06/2013 10:00',
                                volume: 2
                            },
                            {
                                date: '01/07/2013 10:00',
                                volume: 1
                            }
                        ]
                    }
                },
                month: {
                    startDate: '01/01/2013 08:00',
                    status: {
                        valid: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 6
                            },
                            {
                                date: '01/02/2013 09:00',
                                volume: 7
                            },
                            {
                                date: '01/03/2013 10:00',
                                volume: 5
                            },
                            {
                                date: '01/04/2013 08:00',
                                volume: 8
                            },
                            {
                                date: '01/05/2013 09:00',
                                volume: 12
                            },
                            {
                                date: '01/06/2013 10:00',
                                volume: 20
                            },
                            {
                                date: '01/07/2013 10:00',
                                volume: 20
                            },
                            {
                                date: '01/08/2013 08:00',
                                volume: 6
                            },
                            {
                                date: '01/09/2013 09:00',
                                volume: 7
                            },
                            {
                                date: '01/10/2013 10:00',
                                volume: 5
                            },
                            {
                                date: '01/11/2013 08:00',
                                volume: 8
                            },
                            {
                                date: '01/12/2013 09:00',
                                volume: 12
                            },
                            {
                                date: '01/13/2013 10:00',
                                volume: 20
                            },
                            {
                                date: '01/14/2013 10:00',
                                volume: 20
                            }
                        ],
                        warning: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 2
                            },
                            {
                                date: '01/02/2013 09:00',
                                volume: 1
                            },
                            {
                                date: '01/03/2013 10:00',
                                volume: 3
                            },
                            {
                                date: '01/04/2013 08:00',
                                volume: 4
                            },
                            {
                                date: '01/05/2013 09:00',
                                volume: 2
                            },
                            {
                                date: '01/06/2013 10:00',
                                volume: 3
                            },
                            {
                                date: '01/07/2013 10:00',
                                volume: 5
                            },
                            {
                                date: '01/08/2013 08:00',
                                volume: 2
                            },
                            {
                                date: '01/09/2013 09:00',
                                volume: 1
                            },
                            {
                                date: '01/10/2013 10:00',
                                volume: 3
                            },
                            {
                                date: '01/11/2013 08:00',
                                volume: 4
                            },
                            {
                                date: '01/12/2013 09:00',
                                volume: 2
                            },
                            {
                                date: '01/13/2013 10:00',
                                volume: 3
                            },
                            {
                                date: '01/14/2013 10:00',
                                volume: 5
                            }
                        ],
                        error: [
                            {
                                date: '01/01/2013 08:00',
                                volume: 1
                            },
                            {
                                date: '01/03/2013 09:00',
                                volume: 1
                            },
                            {
                                date: '01/03/2013 10:00',
                                volume: 0
                            },
                            {
                                date: '01/04/2013 08:00',
                                volume: 1
                            },
                            {
                                date: '01/05/2013 09:00',
                                volume: 3
                            },
                            {
                                date: '01/06/2013 10:00',
                                volume: 2
                            },
                            {
                                date: '01/07/2013 10:00',
                                volume: 1
                            },
                            {
                                date: '01/08/2013 08:00',
                                volume: 1
                            },
                            {
                                date: '01/09/2013 09:00',
                                volume: 1
                            },
                            {
                                date: '01/10/2013 10:00',
                                volume: 0
                            },
                            {
                                date: '01/11/2013 08:00',
                                volume: 1
                            },
                            {
                                date: '01/12/2013 09:00',
                                volume: 3
                            },
                            {
                                date: '01/13/2013 10:00',
                                volume: 2
                            },
                            {
                                date: '01/14/2013 10:00',
                                volume: 1
                            }
                        ]
                    }
                }
            }

            var _chartPopulateSeries = function(period, status) {
                var ser = _chartData[period]['status'][status]
                var items = new Array();
                for (var i = 0; i < ser.length; i++) {
                    var time = Date.parse(ser[i].date)
                    var vol  = ser[i].volume
                    var item = [time, vol]
                    items[i] = item
                }
                return items
            }

            var _chartToggleSeries = function (action, period, status) {
                var series = []
                if (status === 'valid') {
                    series = _chart.series[0]
                }
                if (status === 'warning') {
                    series = _chart.series[1]
                }
                if (status === 'error') {
                    series = _chart.series[2]
                }

                if (action === 'show') {
                    series.setData(_chartPopulateSeries(period, status));
                } else {
                    series.setData('');
                }
            }

            // Show daily series by default
            _chartToggleSeries('show', 'day', 'valid')
            _chartToggleSeries('show', 'day', 'warning')
            _chartToggleSeries('show', 'day', 'error')


            $('.controls-period input').change(function(){
                var period = $(this).val()
                var $statuses = $('.controls-status input:checked')

                $statuses.each(function(){
                    var status = $(this).val()
                    _chartToggleSeries('show', period, status)
                })
            })

            $('.controls-status input').change(function(){
                var status = $(this).val()
                var period = $('.controls-period input:checked').val()
                var series = []
                if (status === 'valid') {
                    series = _chart.series[0]
                }
                if (status === 'warning') {
                    series = _chart.series[1]
                }
                if (status === 'error') {
                    series = _chart.series[2]
                }
                if (series.data.length > 0) {
                    _chartToggleSeries('hide', period, status)
                } else {
                    _chartToggleSeries('show', period, status)
                }
            })

        })
    </script>

    <div class="row">
        <div class="col-md-6">
            <h1>Web Services</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="chart-wrapper">
                <h2 class="text-center">WS Call History</h2>
                <div id="highcharts-container"></div>
            </div>
        </div>
        <div class="col-md-4 controls">
            <div class="controls-period">
                <label>
                    <input type="radio" name="period" value="day" checked> Today
                </label>
                <label>
                    <input type="radio" name="period" value="week"> Last Week
                </label>
                <label>
                    <input type="radio" name="period" value="month"> Last Month
                </label>
            </div>
            <div class="controls-status">
                <label>
                    <input type="checkbox" name="period" value="valid" checked> <span class="green">Valid</span>
                </label>
                <label>
                    <input type="checkbox" name="period" value="warning" checked> <span class="orange">Warning</span>
                </label>
                <label>
                    <input type="checkbox" name="period" value="error" checked> <span class="red">Error</span>
                </label>
            </div>
        </div>
    </div>

    <!-- END Highcharts -->




    <div style="float: left; width: 320px;">
        <h1>Dossiers en cours</h1>
        <?php if (count($this->lStatus) > 0) : ?>
            <table class="tablesorter">
                <thead>
                <tr>
                    <th align="center">Statut</th>
                    <th align="center">Résultats</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lStatus as $s) : ?>
                    <?php $nbProjects = $this->projects->countSelectProjectsByStatus([$s['status']]); ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><a href="<?= $this->lurl ?>/dossiers/<?= $s['status'] ?>"><?= $s['label'] ?></a></td>
                        <td><?= $nbProjects ?></td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Il n'y a aucun statut pour le moment.</p>
        <?php endif; ?>
    </div>
    <div style="float: right; width: 775px;">
        <h1><?= count($this->lProjectsNok) ?> incidents de remboursement :</h1>
        <?php if (count($this->lProjectsNok) > 0) : ?>
            <table class="tablesorter">
                <thead>
                <tr>
                    <th>Référence</th>
                    <th>Titre</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lProjectsNok as $p) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $p['id_project'] ?></td>
                        <td><?= $p['title'] ?></td>
                        <td><?= $p['amount'] ?></td>
                        <td><?= $this->projects_status->getLabel($p['status']) ?></td>
                        <td align="center">
                            <a href="<?= $this->lurl ?>/dossiers/edit/<?= $p['id_project'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir le dossier" title="Voir le dossier"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Il n'y a aucune incidence de remboursement pour le moment.</p>
        <?php endif; ?>
    </div>
    <div style="clear: both;"></div>
</div>

