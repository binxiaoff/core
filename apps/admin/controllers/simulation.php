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
                    'name'   => 'get_announcements_list_infolegale',
                    'method' => 'getAnnouncementsList',
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
                    'label'  => 'getAnnonceLegale',
                    'name'   => 'get_announcements_details_infolegale',
                    'method' => 'getAnnouncementsDetails',
                    'fields' => [
                        [
                            'name'      => 'parameter[announcementsId]',
                            'label'     => 'ID annonces',
                            'type'      => 'string',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getListAnnonceLegaleDirigeant',
                    'name'   => 'get_announcements_director_infolegale',
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

        $this->menu_admin = 'emprunteurs';
        $this->users->checkAccess(Zones::ZONE_LABEL_SIMULATOR);
        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        header('Location: ' . $this->lurl);
        die;
    }

    public function _webservices_risque()
    {
        if (false === $this->request->isXmlHttpRequest()) {
            $this->render(null, ['resources' => self::RISK_WEBSERVICES]);
        }

        header('Content-Type: application/json');

        if (
            null === $this->request->request->filter('service', FILTER_SANITIZE_STRING)
            || null === $this->request->request->filter('request_type')
            || null === $this->request->request->get('parameter')
            || false === is_array($this->request->request->get('parameter'))
            || empty($this->request->request->get('parameter'))
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
        $service              = $this->request->request->filter('service', FILTER_SANITIZE_STRING);
        $wsResource           = $wsResourceRepository->findOneBy(['label' => $service]);

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
            foreach ($provider['services'] as $providerService) {
                if ($providerService['name'] === $service) {
                    $method = $providerService['method'];
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
            switch ($this->request->request->filter('request_type', FILTER_SANITIZE_STRING)) {
                case 'normal':
                    $response = $this->getNormalWsCall($wsResource, $method, $this->request->request->get('parameter'));
                    break;
                case 'ws':
                    $response = $this->getDirectWsCall($wsResource, $method, $this->request->request->get('parameter'));
                    break;
                case 'cache':
                    $response = $this->getCacheWsCall($wsResource, $this->request->request->getInt('cache_duration'));
                    break;
                default:
                    echo json_encode([
                        'success' => false,
                        'error'   => ['Type de requête inconnu']
                    ]);
                    exit;
            }

            if ($response instanceof WsCall) {
                $date     = $response->getAdded()->format('d/m/Y H:i:s');
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
     * @param array              $parameters
     *
     * @return false|WsCall
     */
    private function getNormalWsCall(WsExternalResource $wsResource, $method, array $parameters)
    {
        $providerService = $this->get('unilend.service.ws_client.' . $wsResource->getProviderName() . '_manager');
        $response        = call_user_func_array([$providerService, $method], $this->getWsCallParameters($parameters));

        if (null === $response) {
            return false;
        }

        return $this->getCacheWsCall($wsResource);
    }

    /**
     * @param WsExternalResource $wsResource
     * @param string             $method
     * @param array              $parameters
     *
     * @return false|WsCall
     */
    public function getDirectWsCall(WsExternalResource $wsResource, $method, array $parameters)
    {
        $providerService = $this->get('unilend.service.ws_client.' . $wsResource->getProviderName() . '_manager');
        $providerService->setReadFromCache(false);

        $response = call_user_func_array([$providerService, $method], $this->getWsCallParameters($parameters));

        if (null === $response) {
            return false;
        }

        return $this->getCacheWsCall($wsResource);
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    private function getWsCallParameters(array $parameters)
    {
        $callParameters = [];
        foreach ($parameters as $name => $parameter) {
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
                case 'announcementsId':
                    $parameter = explode(',', $parameter);
                    break;
            }

            $callParameters[] = $parameter;
        }

        return $callParameters;
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
