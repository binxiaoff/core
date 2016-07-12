<?php


namespace Unilend\Bundle\FrontBundle\Service;


class HighchartsService
{

    public function getFinancialProjectDataCharts($accountData)
    {
        $charts = [
            'income'             => $this->getIncomeStatementChart($accountData['balanceSheets']),
            'balanceSheetAssets' => $this->getBalanceSheetAssetsChart($accountData['totalYearlyAssets']),
            'balanceSheetDebts'  => $this->getBalanceSheetDebtsChart($accountData['totalYearlyDebts'])
        ];

        return $charts;
    }

    public function getFinancialProjectDataForTables($accountData)
    {
        $tables = [
            'income'  => $this->formatIncomeStatementDataForTable($accountData['balanceSheets']),
            'balance' => $this->formatBalanceSheetDataForTable($accountData['totalYearlyAssets'], $accountData['totalYearlyDebts'])
        ];

        return $tables;
    }

    /**
     * @param array $bids
     */
    public function formatBidsForTable($bids, $replaceIdByOrder = false)
    {
        $schema = [
            ['name' => 'id', 'type' => 'int', 'label' => 'No'],
            ['name' => 'rate', 'type' => 'float', 'label' => 'Taux d\'intérêt'], //TODO TRAD
            ['name' => 'amount', 'type' => 'int', 'label' => 'Montant'],
            ['name' => 'status', 'type' => 'int', 'label' => 'Statut'],
            ['name' => 'view', 'type' => 'string', 'label' => 'View']
        ];

        $data = [];

        $i = 1;
        foreach ($bids as $key => $bid) {
            if ($replaceIdByOrder) {
                $data[] = [$i, $bid['rate'], $bid['amount'] / 100, $bid['status']];
                $i += 1;
            } else {
                $data[] = [$bid['id_bid'], $bid['rate'], $bid['amount'] / 100, $bid['status'], ''];
            }
        }

        $offers = [
            'schema' => $schema,
            'data'   => $data
        ];

        return $offers;
    }

    public function formatBidsForOverview($bids)
    {

    }

    public function getBidsChartSetting($bidsByRate, $limitRate)
    {
        $dataBeforeLimit = [];
        $dataAfterLimit  = [];

        foreach ($bidsByRate as $bid) {
            if ($bid['rate'] <= $limitRate) {
                $dataBeforeLimit[] = $bid['nb_bids'];
            } else {
                $dataAfterLimit[] = $bid['nb_bids'];
            }
        }

        $bidsChartSetting = [
            'type'        => 'projectOffers',
            'background'  => 'none',
            'highcharts'  => [
                'chart' => [
                    'type'                => 'areaspline',
                    'plotBackgroundColor' => [
                        'linearGradient' => ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 1],
                        'stops'          => [
                            [0, 'rgba(95, 196, 208, .2)'],
                            [1, 'rgba(95, 196, 208, 0)']
                        ]
                    ]
                ]
            ],
            'title'       => [
                'margin' => 20,
                'text'   => "{% trans with {'%NumberPendingBids%' => NumberPendingBids } %} project-detail_all-bids-chart-title {% endtrans %}",
                'align'  => 'left',
                'style'  => [
                    'fontSize' => '16px'
                ]
            ],
            'subtitle'    => [
                'margin'  => 10,
                'useHTML' => true,
                'text'    => '<span class="chart-key-icon" style="background: white; border: solid 3px #5FC5D1; width: 12px; height: 12px; line-height: 12px;"></span> {% trans %} project-detail_all-bids-chart-best-rate-legend {% endtrans %}',
                'align'   => 'right',
                'style'   => [
                    'color'    => '#5FC5D1',
                    'fontSize' => '14px'
                ]
            ],
            'xAxis'       => [
                'categories' => [
                    '4%',
                    '4,5%',
                    '5%',
                    '5,5%',
                    '6%',
                    '6,5%',
                    '7%',
                    '7,5%',
                    '8%',
                    '8,5%',
                    '9%',
                    '9,5%',
                    '10%'
                ],
                'plotBands'  => [
                    'color'     => '#999999',
                    'dashStyle' => 'ShortDot',
                    'value'     => 8,
                    'width'     => 1
                ],
                'plotLines'  => [
                    'color'     => '#999999',
                    'dashStyle' => 'ShortDot',
                    'value'     => 8,
                    'width'     => 1
                ]
            ],
            'yAxis'       => [
                'title' => [
                    'text' => '{% trans %} project-detail_all-bids-chart-y-axis-legend {% endtrans %}'
                ]
            ],
            'plotOptions' => [
                'areaspline' => [
                    'dataLabels' => [
                        'enabled' => false,
                        'states'  => [
                            'hover' => [
                                'enabled' => true
                            ]
                        ]
                    ]
                ]
            ],
            'series'      => [
                'name'         => 'Offres en cours',
                'color'        => '#5FC5D1',
                'fillOpacity'  => 0.25,
                'showInLegend' => false,
                'data'         => [
                    $dataBeforeLimit,
                    [
                        'y'      => 823,
                        'marker' => [
                            'fillColor' => 'white',
                            'lineColor' => '#5FC5D1',
                            'lineWidth' => 3,
                            'enabled'   => true,
                            'radius'    => 7
                        ]
                    ],
                    $dataAfterLimit
                ]
            ],
            'exportUrl'   => ''
        ];

        return $bidsChartSetting;
    }

    public function getIncomeStatementChart($balanceSheets)
    {
        $categories = [2013, 2014, 2015];
        $data       = [
            ['name' => "Chiffre d'affaires", 'data' => [842020, 508340, 732245]],
            ['name' => "Résultats bruts d'exposition", 'data' => [42020, 18340, 2245]],
            ['name' => "Résultats d'exploitation", 'data' => [32020, 12340, 1245]],
            ['name' => "Investissement", 'data' => [1020, 8340, 2245]]
        ];

        $incomeStatementChart = [
            'type'       => 'projectOwnerIncome',
            'background' => 'none',
            'highcharts' => [
                'chart'  => [
                    'type'                => 'spline',
                    'plotBackgroundColor' => [
                        'linearGradient' => ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 1],
                        'stops'          => [
                            [0, 'rgba(95, 196, 208, .1)'],
                            [1, 'rgba(95, 196, 208, 0)']
                        ]
                    ]
                ],
                'title'  => ['text' => ''],
                'xAxis'  => ['categories' => $categories],
                'yAxis'  => [
                    'title' => ['text' => 'Montant (en €)'],
                    'units' => '€'
                ],
                'series' => $data
            ],
            'exportUrl'  => ''
        ];

        return $incomeStatementChart;
    }


    public function formatIncomeStatementDataForTable($balanceSheets)
    {
        $schema = [
            ['name' => 'name', 'type' => 'string', 'label' => 'Name'],
            ['name' => 'year-2015', 'type' => 'float', 'label' => '2015'],
            ['name' => 'diff-2015', 'type' => 'float', 'label' => 'false'],
            ['name' => 'year-2014', 'type' => 'float', 'label' => '2014'],
            ['name' => 'diff-2014', 'type' => 'float', 'label' => 'false'],
            ['name' => 'year-2013', 'type' => 'float', 'label' => '2013'],
            ['name' => 'diff-2013', 'type' => 'float', 'label' => 'false']

        ];
        $data   = [
            ["Chiffre d'affaires", 842020, 5.2, 508340, 3.1, 732245, ''],
            ["Résultats bruts d'exposition", 42020, 12, 18340, 8.8, 2245, ''],
            ["Résultats d'exploitation", 32020, 1, 12340, 3, 1245, ''],
            ["Investissement", 1020, 10, 8340, 0, 2245, '']
        ];

        $incomeTableData = [
            'schema'    => json_encode($schema),
            'data'      => json_encode($data),
            'exportUrl' => ''
        ];

        return $incomeTableData;
    }

    public function getBalanceSheetAssetsChart($totalYearlyAssets)
    {
        $categories =  [2013, 2014, 2015];

        $dataSeries = [
            ['name' => 'Immobilisations corporelles','data' => [842020, 508340, 732245]],
            ['name' => 'Immobilisations incorporelles','data' => [42050, 18380, 2215]],
            ['name' => 'Immobilisations financières','data' => [32020, 12340, 1245]],
            ['name' => 'Stocks','data' => [1020, 8340, 3745]],
            ['name' => 'Créances','data' => [840020, 500340, 730245]],
            ['name' => 'Disponibilités','data' => [41020, 18040, 2045]],
            ['name' => 'Valeur mobilières de placement','data' => [30020, 12040, 1205]],
            ['name' => 'Stocks','data' => [1220, 8200, 4745]]
        ];

        $balanceSheetAssets = [
            'type'       => 'projectOwnerBalanceActive',
            'background' => 'none',
            'highcharts' => [
                'chart'  => [
                    'type'                => 'spline',
                    'plotBackgroundColor' => [
                        'linearGradient' => ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 1],
                        'stops'          => [
                            [0, 'rgba(95, 196, 208, .1)'],
                            [1, 'rgba(95, 196, 208, 0)']
                        ]
                    ]
                ],
                'title'  => ['text' => ''],
                'xAxis'  => ['categories' => $categories],
                'yAxis'  => [
                    'title' => ['text' => 'Montant (en €)'],
                    'units' => '€'
                ],
                'legend' => ['symbolRadius' => 10],
                'series' => $dataSeries
            ],
            'exportUrl'  => ''
        ];

        return $balanceSheetAssets;
    }

    public function getBalanceSheetDebtsChart($totalYearlyDebts)
    {
        $categories = [2013, 2014, 2015];

        $dataSeries = [
            ['name' => 'Capitaux propres', 'data' => [642020, 208340, 132245]],
            ['name' => 'Provisions pour risques & charges','data' => [32020, 19340, 3245]],
            ['name' => 'Amortissement sur immobilisations','data' => [12020, 16340, 1335]],
            ['name' => 'Dettes financières','data' => [2020, 6340, 8745]],
            ['name' => 'Dettes fournissers','data' => [542020, 208340, 532245]],
            ['name' => 'Autres dettes','data' => [22020, 16340, 1245]]
        ];

        $balanceSheetDebts = [
            'type'       => 'projectOwnerBalancePassive',
            'background' => 'none',
            'highcharts' => [
                'chart'  => [
                    'type'                => 'spline',
                    'plotBackgroundColor' => [
                        'linearGradient'  => array('x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 1),
                        'stops'           => [
                            [0, 'rgba(95, 196, 208, .1)'],
                            [1, 'rgba(95, 196, 208, 0)']
                        ]
                    ]
                ],
                'title'  => ['text' => ''],
                'xAxis'  => ['categories' => $categories],
                'yAxis'  => [
                    'title' => ['text' => 'Montant (en €)'],
                    'units' => '€'
                ],
                'legend' => ['symbolRadius' => 10],
                'series' => $dataSeries
            ],
            'exportUrl'  => ''
        ];

        return $balanceSheetDebts;
    }

    public function formatBalanceSheetDataForTable($totalYearlyAssets, $totalYearlyDebts)
    {
        $schema = [
            ['name'   => 'type','values' => ['title', '', 'total'],'label'  => false],
            ['name'  => 'name','type'  => 'string','label' => 'Name'],
            ['name'  => 'year-2015','type'  => 'float','label' => '2015'],
            ['name'  => 'diff-2015','type'  => 'float','label' => false],
            ['name'  => 'year-2014','type'  => 'float','label' => '2014'],
            ['name'  => 'diff-2014','type'  => 'float','label' => false],
            ['name'  => 'year-2013','type'  => 'float','label' => '2013']
        ];

        $dataSeries = [
            ['title', 'Actif', '', '', ''],
            ['', 'Immobilisations corporelles', 842020, 5.2, 508340, 4.3, 732245, ''],
            ['', 'Immobilisations incorporelles', 42020, 6.2, 18340, 5.0, 2245, ''],
            ['', 'Immobilisations financières', 32020, 8, 12340, -4.3, 1245, ''],
            ['', 'Stocks', 1020, 9.2, 8340, -21.2, 3745, ''],
            ['', 'Créances (clients & autres)', 842020, 3, 508340, -2.2, 732245, ''],
            ['', 'Disponibilités', 42020, 9.2, 18340, 7.4, 2245, ''],
            ['', 'Valeur mobilières de placement', 32020, -4.2, 12340, 6.8, 1245, ''],
            ['total', 'Total actif', 4807975, 11, 4237129, 10, 4137892, ''],
            ['title', 'Passif', '', '', '', '', '', ''],
            ['', 'Capitaux propres', 842020, 5, 508340, 3.34, 732245, ''],
            ['', 'Provisions pour risques & charges', 42020, 3, 18340, -9.2, 2245, ''],
            ['', 'Amortissement sur immobilisations', 32020, -6, 12340, 9.9, 1245, ''],
            ['', 'Dettes financières', 1020, 4, 8340, 6.6, 3745, ''],
            ['', 'Dettes fournissers', 842020, -22, 508340, -15.67, 732245, ''],
            ['', 'Autres dettes', 42020, 5, 18340, 2, 2245, ''],
            ['total', 'Total passif', 4207975, 0, 4137129, 0.2, 4037892, '']
        ];

        $balanceSheetData = [
            'schema'    => $schema,
            'data'      => $dataSeries,
            'exportUrl' => ''
        ];

        return $balanceSheetData;
    }

}
