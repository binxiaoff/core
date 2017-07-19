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
            var _chartData
            <?php // echo 'var _chartData = ' . $this->chartData ?>
            var _chartData = {
                "day": {
                    "valid": [
                        {
                        "date": "2017-07-18 08:00", 
                        "volume": 0,
                        "volumeC": 0
                    }, {"date": "2017-07-18 09:00",
                        "volume": 0,
                        "volumeC": 0
                    }, {"date": "2017-07-18 10:00", "volume": 0}, {
                        "date": "2017-07-18 11:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 12:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 13:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 14:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 15:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 16:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 17:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 18:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 19:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 20:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 21:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 22:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 23:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 00:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 01:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 02:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 03:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 04:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 05:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 06:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 07:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 08:00", "volume": 0, "volumeC": 1}],
                    "warning": [{"date": "2017-07-18 08:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 09:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 10:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 11:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 12:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 13:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 14:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 15:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 16:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 17:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 18:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 19:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 20:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 21:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 22:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 23:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 00:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 01:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 02:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 03:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 04:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 05:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 06:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 07:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 08:00", "volume": 0, "volumeC": 1}],
                    "error": [{"date": "2017-07-18 08:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 09:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 10:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 11:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 12:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 13:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 14:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 15:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 16:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 17:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 18:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 19:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 20:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 21:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18 22:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-18 23:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 00:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 01:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 02:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 03:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 04:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 05:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 06:00", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19 07:00",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-19 08:00", "volume": 0, "volumeC": 1}]
                },
                "week": {
                    "valid": [{"date": "2017-07-12", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-13",
                        "volume": 2, "volumeC": 1
                    }, {"date": "2017-07-14", "volume": 0, "volumeC": 1}, {"date": "2017-07-15", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-16",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-17", "volume": 0, "volumeC": 1}, {"date": "2017-07-18", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19",
                        "volume": 0, "volumeC": 1
                    }],
                    "warning": [{"date": "2017-07-12", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-13",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-14", "volume": 0, "volumeC": 1}, {"date": "2017-07-15", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-16",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-17", "volume": 0, "volumeC": 1}, {"date": "2017-07-18", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19",
                        "volume": 0, "volumeC": 1
                    }],
                    "error": [{"date": "2017-07-12", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-13",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-14", "volume": 0, "volumeC": 1}, {"date": "2017-07-15", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-16",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-17", "volume": 0, "volumeC": 1}, {"date": "2017-07-18", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-19",
                        "volume": 0, "volumeC": 1
                    }]
                },
                "month": {
                    "valid": [{"date": "2017-06-19", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-20",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-21", "volume": 0, "volumeC": 1}, {"date": "2017-06-22", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-23",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-24", "volume": 0, "volumeC": 1}, {"date": "2017-06-25", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-26",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-27", "volume": 0, "volumeC": 1}, {"date": "2017-06-28", "volume": 1, "volumeC": 0}, {
                        "date": "2017-06-29",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-30", "volume": 0, "volumeC": 1}, {"date": "2017-07-01", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-02",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-03", "volume": 0, "volumeC": 1}, {"date": "2017-07-04", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-05",
                        "volume": 10, "volumeC": 1
                    }, {"date": "2017-07-06", "volume": 0, "volumeC": 1}, {"date": "2017-07-07", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-08",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-09", "volume": 0, "volumeC": 1}, {"date": "2017-07-10", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-11",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-12", "volume": 0, "volumeC": 1}, {"date": "2017-07-13", "volume": 2, "volumeC": 1}, {
                        "date": "2017-07-14",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-15", "volume": 0, "volumeC": 1}, {"date": "2017-07-16", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-17",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18", "volume": 0, "volumeC": 1}, {"date": "2017-07-19", "volume": 0, "volumeC": 1}],
                    "warning": [{"date": "2017-06-19", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-20",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-21", "volume": 0, "volumeC": 1}, {"date": "2017-06-22", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-23",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-24", "volume": 0, "volumeC": 1}, {"date": "2017-06-25", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-26",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-27", "volume": 0, "volumeC": 1}, {"date": "2017-06-28", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-29",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-30", "volume": 0, "volumeC": 1}, {"date": "2017-07-01", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-02",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-03", "volume": 0, "volumeC": 1}, {"date": "2017-07-04", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-05",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-06", "volume": 0, "volumeC": 1}, {"date": "2017-07-07", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-08",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-09", "volume": 0, "volumeC": 1}, {"date": "2017-07-10", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-11",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-12", "volume": 0, "volumeC": 1}, {"date": "2017-07-13", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-14",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-15", "volume": 0, "volumeC": 1}, {"date": "2017-07-16", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-17",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18", "volume": 0, "volumeC": 1}, {"date": "2017-07-19", "volume": 0, "volumeC": 1}],
                    "error": [{"date": "2017-06-19", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-20",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-21", "volume": 0, "volumeC": 1}, {"date": "2017-06-22", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-23",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-24", "volume": 0, "volumeC": 1}, {"date": "2017-06-25", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-26",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-27", "volume": 0, "volumeC": 1}, {"date": "2017-06-28", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-29",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-30", "volume": 0, "volumeC": 1}, {"date": "2017-07-01", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-02",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-03", "volume": 0, "volumeC": 1}, {"date": "2017-07-04", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-05",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-06", "volume": 0, "volumeC": 1}, {"date": "2017-07-07", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-08",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-09", "volume": 0, "volumeC": 1}, {"date": "2017-07-10", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-11",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-12", "volume": 0, "volumeC": 1}, {"date": "2017-07-13", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-14",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-15", "volume": 0, "volumeC": 1}, {"date": "2017-07-16", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-17",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18", "volume": 0, "volumeC": 1}, {"date": "2017-07-19", "volume": 0, "volumeC": 1}],
                    "": [{"date": "2017-06-19", "volume": 20, "volumeC": 10}, {
                        "date": "2017-06-20",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-21", "volume": 0, "volumeC": 1}, {"date": "2017-06-22", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-23",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-24", "volume": 0, "volumeC": 1}, {"date": "2017-06-25", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-26",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-27", "volume": 0, "volumeC": 1}, {"date": "2017-06-28", "volume": 0, "volumeC": 1}, {
                        "date": "2017-06-29",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-06-30", "volume": 0, "volumeC": 1}, {"date": "2017-07-01", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-02",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-03", "volume": 0, "volumeC": 1}, {"date": "2017-07-04", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-05",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-06", "volume": 0, "volumeC": 1}, {"date": "2017-07-07", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-08",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-09", "volume": 0, "volumeC": 1}, {"date": "2017-07-10", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-11",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-12", "volume": 0, "volumeC": 1}, {"date": "2017-07-13", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-14",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-15", "volume": 0, "volumeC": 1}, {"date": "2017-07-16", "volume": 0, "volumeC": 1}, {
                        "date": "2017-07-17",
                        "volume": 0, "volumeC": 1
                    }, {"date": "2017-07-18", "volume": 0, "volumeC": 1}, {"date": "2017-07-19", "volume": 0, "volumeC": 1}]
                }
            }
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

            var _chartPopulateSeries = function (period, status, custom) {
                var ser = _chartData[period][status]
                var items = new Array();
                var d = new Date()
                var offset = d.getTimezoneOffset();
                for (var i = 0; i < ser.length; i++) {
                    var time = Date.parse(ser[i].date) - offset * 60 * 1000
                    // console.log(Highcharts.dateFormat("%H:%M", time))
                    if (custom === 'all') {
                        var vol = ser[i].volume
                    } else {
                        var vol = ser[i].volumeC
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
                        customOpposite = 'local-only'
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
                    <input type="radio" name="custom" value="all" checked> All
                </label>
                <label>
                    <input type="radio" name="custom" value="local-only"> Local only
                </label>
            </div>
        </div>
    </div>
    <!-- END Highcharts -->
</div>
