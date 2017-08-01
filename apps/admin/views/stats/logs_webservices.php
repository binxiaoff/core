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
        $(function () {
            var _chart = new Highcharts.chart('highcharts-container', {
                chart: {
                    type: 'column'
                },
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
                    tickInterval: 3600 * 1000,
                    labels: {
                        rotation: 45,
                        formatter: function () {
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

            <?php echo 'var _chartData = ' . $this->chartData ?>;

            var _chartPopulateSeries = function (period, status, custom) {
                var ser = _chartData[period][status]
                var items = new Array();
                var d = new Date()
                var offset = d.getTimezoneOffset();
                for (var i = 0; i < ser.length; i++) {
                    var time = Date.parse(ser[i].date) - offset * 60 * 1000
                    if (custom === 'all') {
                        var vol = ser[i].totalVolume
                    } else {
                        var vol = ser[i].clientVolume
                    }
                    items[i] = [time, vol]
                }
                return items
            }

            var _chartToggleSeries = function (action, period, status, custom) {
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
                    series.setData(_chartPopulateSeries(period, status, custom));
                } else {
                    series.setData('');
                }
            }

            // Show daily series by default
            _chartToggleSeries('show', 'day', 'valid', 'all')
            _chartToggleSeries('show', 'day', 'warning', 'all')
            _chartToggleSeries('show', 'day', 'error', 'all')


            $('.controls-period input').change(function () {
                var period = $(this).val()
                var $statuses = $('.controls-status input:checked')
                var custom = $('.controls-custom input:checked').val()

                $statuses.each(function () {
                    var status = $(this).val()
                    _chartToggleSeries('show', period, status, custom)
                })
            })

            $('.controls-status input').change(function () {
                var status = $(this).val()
                var period = $('.controls-period input:checked').val()
                var custom = $('.controls-custom input:checked').val()
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
                    _chartToggleSeries('hide', period, status, custom)
                } else {
                    _chartToggleSeries('show', period, status, custom)
                }
            })

            $('.controls-custom input').change(function () {
                var custom = $(this).val()
                var $statuses = $('.controls-status input:checked')
                var period = $('.controls-period input:checked').val()

                $statuses.each(function () {
                    var status = $(this).val()
                    var customOpposite
                    if (custom === 'all') {
                        customOpposite = 'without-monitoring'
                    } else {
                        customOpposite = 'all'
                    }
                    _chartToggleSeries('hide', period, status, customOpposite)
                    _chartToggleSeries('show', period, status, custom)
                })
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
                    <input type="radio" name="period" value="day" checked> Last 24h
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
            <div class="controls-custom" style="display: block;">
                <label style="margin-right: 10px;">
                    <input type="radio" name="custom" value="all" checked> All logs
                </label>
                <label>
                    <input type="radio" name="custom" value="without-monitoring"> Without monitoring logs
                </label>
            </div>
        </div>
    </div>
    <!-- END Highcharts -->
</div>
