<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use Unilend\Bundle\StoreBundle\Document\WsCall;

class simulationController extends bootstrap
{
    /** @var array */
    const RISK_WEBSERVICES = [
        [
            'provider' => 'Altares',
            'format'   => 'json',
            'services' => [
                [
                    'label'  => 'getDerniersBilans',
                    'name'   => 'get_balance_sheet_altares',
                    'method' => 'getBalanceSheets',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'text',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'parameter[balanceCount]',
                            'label'     => 'Nombre de bilans',
                            'type'      => 'int',
                            'mandatory' => false
                        ]
                    ]
                ],
                [
                    'label'  => 'getIdentiteAltaN3Entreprise',
                    'name'   => 'get_company_identity_altares',
                    'method' => 'getCompanyIdentity',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getIdentiteAltaN3Etablissement',
                    'name'   => 'get_establishment_identity_altares',
                    'method' => 'getEstablishmentIdentity',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getScore',
                    'name'   => 'get_score_altares',
                    'method' => 'getScore',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getSoldeIntermediaireGestion',
                    'name'   => 'get_balance_management_line_altares',
                    'method' => 'getBalanceManagementLine',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'parameter[balanceId]',
                            'label'     => 'ID bilan',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getSyntheseFinanciere',
                    'name'   => 'get_financial_summary_altares',
                    'method' => 'getFinancialSummary',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'parameter[balanceId]',
                            'label'     => 'ID bilan',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ]
            ]
        ],
        [
            'provider' => 'Codinf',
            'format'   => 'xml',
            'services' => [
                [
                    'label'  => 'get_list_v2',
                    'name'   => 'get_incident_list_codinf',
                    'method' => 'getIncidentList',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'parameter[startDate]',
                            'label'     => 'Date de début',
                            'type'      => 'date',
                            'mandatory' => false
                        ],
                        [
                            'name'      => 'parameter[endDate]',
                            'label'     => 'Date de fin',
                            'type'      => 'date',
                            'mandatory' => false
                        ],
                        [
                            'name'      => 'parameter[includeRegularized]',
                            'label'     => 'Régularisations',
                            'type'      => 'bool',
                            'mandatory' => false,
                            'default'   => false
                        ]
                    ]
                ]
            ]
        ],
        [
            'provider' => 'Ellisphere',
            'format'   => 'xml',
            'services' => [
                [
                    'label'  => 'svcOnlineOrder',
                    'name'   => 'get_online_order_ellisphere',
                    'method' => 'getReport',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'svcSearch',
                    'name'   => 'search_ellisphere',
                    'method' => 'searchBySiren',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ]
            ]
        ],
        [
            'provider' => 'Euler Hermes',
            'format'   => 'json',
            'services' => [
                [
                    'label'  => 'trafficLight',
                    'name'   => 'get_traffic_light_euler',
                    'method' => 'getTrafficLight',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'parameter[country]',
                            'label'     => 'Pays',
                            'type'      => 'string',
                            'mandatory' => false,
                            'default'   => 'FR'
                        ]
                    ]
                ],
                [
                    'label'  => 'grade',
                    'name'   => 'get_grade_euler',
                    'method' => 'getGrade',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'parameter[country]',
                            'label'     => 'Pays',
                            'type'      => 'string',
                            'mandatory' => false,
                            'default'   => 'FR'
                        ]
                    ]
                ]
            ]
        ],
        [
            'provider' => 'Infogreffe',
            'format'   => 'xml',
            'services' => [
                [
                    'label'  => 'getProduitsWebServicesXML',
                    'name'   => 'get_indebtedness_infogreffe',
                    'method' => 'getIndebtedness',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ]
            ]
        ],
        [
            'provider' => 'Infolegale',
            'format'   => 'xml',
            'services' => [
                [
                    'label'  => 'getExecutives',
                    'name'   => 'get_executives_infolegale',
                    'method' => 'getExecutives',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getHomonymes',
                    'name'   => 'get_homonyms_infolegale',
                    'method' => 'getHomonyms',
                    'fields' => [
                        [
                            'name'      => 'parameter[execId]',
                            'label'     => 'ID dirigeant',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getIdentity',
                    'name'   => 'get_identity_infolegale',
                    'method' => 'getIdentity',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getListAnnonceLegale',
                    'name'   => 'get_legal_notice_infolegale',
                    'method' => 'getListAnnonceLegale',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getListAnnonceLegaleDirigeant',
                    'name'   => 'get_announcements__director_infolegale',
                    'method' => 'getDirectorAnnouncements',
                    'fields' => [
                        [
                            'name'      => 'parameter[execId]',
                            'label'     => 'ID dirigeant',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getMandats',
                    'name'   => 'get_mandates_infolegale',
                    'method' => 'getMandates',
                    'fields' => [
                        [
                            'name'      => 'parameter[execId]',
                            'label'     => 'ID dirigeant',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getScore',
                    'name'   => 'get_score_infolegale',
                    'method' => 'getScore',
                    'fields' => [
                        [
                            'name'      => 'parameter[siren]',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ]
            ]
        ]
    ];

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_SIMULATOR);
    }

    public function _default()
    {
        header('Location: ' . $this->lurl);
        die;
    }

    public function _webservices_risque()
    {
        if (empty($_POST)) {
            $this->render(null, ['resources' => self::RISK_WEBSERVICES]);
        }

        header('Content-Type: application/json');

        if (
            false === isset($_POST['service'])
            || false === isset($_POST['request_type'])
            || false === isset($_POST['parameter'])
            || false === is_array($_POST['parameter'])
            || empty($_POST['parameter'])
        ) {
            echo json_encode([
                'success' => false,
                'error'   => ['Paramètres manquants']
            ]);
            exit;
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager        = $this->get('doctrine.orm.entity_manager');
        $wsResourceRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WsExternalResource');
        $wsResource           = $wsResourceRepository->findOneBy(['label' => $_POST['service']]);

        if (null === $wsResource) {
            echo json_encode([
                'success' => false,
                'error'   => ['Webservice inconnu']
            ]);
            exit;
        }

        $date   = null;
        $method = null;
        $format = 'xml';
        foreach (self::RISK_WEBSERVICES as $provider) {
            foreach ($provider['services'] as $service) {
                if ($service['name'] === $_POST['service']) {
                    $method = $service['method'];
                    $format = $provider['format'];
                    break;
                }
            }
        }

        if (null === $method) {
            echo json_encode([
                'success' => false,
                'error'   => ['Impossible de trouver la méthode à appeler']
            ]);
            exit;
        }

        try {
            switch ($_POST['request_type']) {
                case 'normal':
                    $response = $this->getNormalWsCall($wsResource, $method);
                    break;
                case 'ws':
                    $response = $this->getDirectWsCall($wsResource, $method);
                    break;
                case 'cache':
                    $response = $this->getCacheWsCall($wsResource, (int) $_POST['cache_duration']);
                    break;
                default:
                    echo json_encode([
                        'success' => false,
                        'error'   => ['Type de requête inconnu']
                    ]);
                    exit;
            }

            if ($response instanceof WsCall) {
                $date     = $response->getAdded()->format(\DateTime::RFC3339);
                $response = $response->getResponse();

                if ('xml' === $format) {
                    $response = htmlentities($response);
                }

                $response = print_r($response, true);
            } elseif (false === $response || null === $response) {
                $response = 'Aucune donnée';
                $format   = 'string';
            }

            echo json_encode([
                'success' => true,
                'data'    => [
                    'response' => $response,
                    'format'   => $format,
                    'date'     => $date
                ]
            ]);
            exit;
        } catch (\Exception $exception) {
            echo json_encode([
                'success' => true,
                'data'    => [
                    'response' => $exception->getMessage(),
                    'format'   => 'string'
                ]
            ]);
            exit;
        }
    }

    /**
     * @param WsExternalResource $wsResource
     * @param string             $method
     *
     * @return false|WsCall
     */
    private function getNormalWsCall(WsExternalResource $wsResource, $method)
    {
        $providerService = $this->get('unilend.service.ws_client.' . $wsResource->getProviderName() . '_manager');
        $response        = call_user_func_array([$providerService, $method], $this->getWsCallParameters());

        if (null === $response) {
            return false;
        }

        return $this->getCacheWsCall($wsResource);
    }

    /**
     * @param WsExternalResource $wsResource
     * @param string             $method
     *
     * @return false|WsCall
     */
    public function getDirectWsCall(WsExternalResource $wsResource, $method)
    {
        $providerService = $this->get('unilend.service.ws_client.' . $wsResource->getProviderName() . '_manager');
        $providerService->setReadFromCache(false);

        $response = call_user_func_array([$providerService, $method], $this->getWsCallParameters());

        if (null === $response) {
            return false;
        }

        return $this->getCacheWsCall($wsResource);
    }

    /**
     * @return array
     */
    private function getWsCallParameters()
    {
        $parameters = [];
        foreach ($_POST['parameter'] as $name => $parameter) {
            if (empty($parameter)) {
                break;
            }

            switch ($name) {
                case 'startDate':
                case 'endDate':
                    $parameter = \DateTime::createFromFormat('d/m/Y', $parameter);
                    break;
                case 'includeRegularized':
                    $parameter = 'true' === $parameter;
                    break;
            }

            $parameters[] = $parameter;
        }

        return $parameters;
    }

    /**
     * @param WsExternalResource $wsResource
     * @param null|int           $cacheValidity
     *
     * @return false|WsCall
     */
    private function getCacheWsCall(WsExternalResource $wsResource, $cacheValidity = null)
    {
        if (null === $cacheValidity) {
            $cacheValidity = $wsResource->getValidityDays();
        }

        $siren      = null;
        $parameters = [];
        if (isset($_POST['parameter']['execId'])) {
            $parameters['execId'] = $_POST['parameter']['execId'];
        } elseif (isset($_POST['parameter']['siren'])) {
            $siren = $_POST['parameter']['siren'];
        }

        /** @var \Unilend\Bundle\WSClientBundle\Service\CallHistoryManager $wsCallHistory */
        $wsCallHistory  = $this->get('unilend.service.ws_client.call_history_handler');
        $wsCallResponse = $wsCallHistory->fetchLatestDataFromMongo(
            $siren,
            $parameters,
            $wsResource->getProviderName(),
            $wsResource->getResourceName(),
            $wsCallHistory->getDateTimeFromPassedDays($cacheValidity)
        );

        return $wsCallResponse;
    }
}
