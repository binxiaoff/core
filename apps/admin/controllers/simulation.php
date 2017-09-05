<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use \Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use \Doctrine\ORM\EntityRepository;

class simulationController extends bootstrap
{
    /** @var array */
    const RISK_WEBSERVICES = [
        [
            'provider' => 'Altares',
            'services' => [
                [
                    'label'  => 'getDerniersBilans',
                    'name'   => 'get_balance_sheet_altares',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'text',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'balancheCount',
                            'label'     => 'Nombre de bilans',
                            'type'      => 'int',
                            'mandatory' => false
                        ]
                    ]
                ],
                [
                    'label'  => 'getIdentiteAltaN3Entreprise',
                    'name'   => 'get_company_identity_altares',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getIdentiteAltaN3Etablissement',
                    'name'   => 'get_establishment_identity_altares',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'   => 'getScore',
                    'name'    => 'get_score_altares',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getSoldeIntermediaireGestion',
                    'name'   => 'get_balance_management_line_altares',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'balanceId',
                            'label'     => 'ID bilan',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getSyntheseFinanciere',
                    'name'   => 'get_financial_summary_altares',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'balanceId',
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
            'services' => [
                [
                    'label'  => 'get_list_v2',
                    'name'   => 'get_incident_list_codinf',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'startDate',
                            'label'     => 'Date de début',
                            'type'      => 'date',
                            'mandatory' => false
                        ],
                        [
                            'name'      => 'endDate',
                            'label'     => 'Date de fin',
                            'type'      => 'date',
                            'mandatory' => false
                        ],
                        [
                            'name'      => 'includeRegularized',
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
            'services' => [
                [
                    'label'  => 'svcOnlineOrder',
                    'name'   => 'get_online_order_ellisphere',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'svcSearch',
                    'name'   => 'search_ellisphere',
                    'fields' => [
                        [
                            'name'      => 'siren',
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
            'services' => [
                [
                    'label'  => 'trafficLight/',
                    'name'   => 'get_traffic_light_euler/',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'country',
                            'label'     => 'Pays',
                            'type'      => 'string',
                            'mandatory' => false,
                            'default'   => 'FR'
                        ]
                    ]
                ],
                [
                    'label'  => 'transactor/',
                    'name'   => 'search_company_euler/',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'country',
                            'label'     => 'Pays',
                            'type'      => 'string',
                            'mandatory' => false,
                            'default'   => 'FR'
                        ]
                    ]
                ],
                [
                    'label'  => 'transactor/grade/',
                    'name'   => 'get_grade_euler/grade/',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'country',
                            'label'     => 'Pays',
                            'type'      => 'string',
                            'mandatory' => false,
                            'default'   => 'FR'
                        ]
                    ]
                ],
                [
                    'label'  => 'transactor/startmonitoring/',
                    'name'   => 'start_euler_grade_monitoring/startmonitoring/',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'country',
                            'label'     => 'Pays',
                            'type'      => 'string',
                            'mandatory' => false,
                            'default'   => 'FR'
                        ]
                    ]
                ],
                [
                    'label'  => 'transactor/stopmonitoring/',
                    'name'   => 'end_euler_grade_monitoring/stopmonitoring/',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ],
                        [
                            'name'      => 'country',
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
            'services' => [
                [
                    'label'  => 'getProduitsWebServicesXML',
                    'name'   => 'get_indebtedness_infogreffe',
                    'fields' => [
                        [
                            'name'      => 'siren',
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
            'services' => [
                [
                    'label'  => 'getExecutives',
                    'name'   => 'get_executives_infolegale',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getHomonymes',
                    'name'   => 'get_homonyms_infolegale',
                    'fields' => [
                        [
                            'name'      => 'executiveId',
                            'label'     => 'ID dirigeant',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getIdentity',
                    'name'   => 'get_identity_infolegale',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getListAnnonceLegale',
                    'name'   => 'get_legal_notice_infolegale',
                    'fields' => [
                        [
                            'name'      => 'executiveId',
                            'label'     => 'ID dirigeant',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getListAnnonceLegaleDirigeant',
                    'name'   => 'get_announcements__director_infolegale',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getMandats',
                    'name'   => 'get_mandates_infolegale',
                    'fields' => [
                        [
                            'name'      => 'executiveId',
                            'label'     => 'ID dirigeant',
                            'type'      => 'int',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'getScore',
                    'name'   => 'get_score_infolegale',
                    'fields' => [
                        [
                            'name'      => 'siren',
                            'label'     => 'SIREN',
                            'type'      => 'siren',
                            'mandatory' => true
                        ]
                    ]
                ],
                [
                    'label'  => 'searchCompany',
                    'name'   => 'search_company_infolegale',
                    'fields' => [
                        [
                            'name'      => 'siren',
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
        $this->render(null, ['resources' => self::RISK_WEBSERVICES]);

        $this->hideDecoration();
        /** @var EntityRepository $wsResourceRepository */
        $wsResourceRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:WsExternalResource');
        $this->resources      = $wsResourceRepository->findBy([], ['providerName' => 'ASC', 'resourceName' => 'ASC']);
        $this->result         = false;

        try {
            if (isset($_POST['siren'])) {
                /** @var WsExternalResource $wsResource */
                $wsResource = $wsResourceRepository->findOneBy(['label' => $_POST['resource_label']]);

                if (null !== $wsResource) {
                    $provider = $this->get('unilend.service.ws_client.' . $wsResource->getProviderName() . '_manager');
                    $endpoint = $this->methods[$wsResource->getLabel()];

                    switch ($wsResource->getProviderName()) {
                        case 'euler':
                            $countryCode  = (empty($_POST['countryCode'])) ? 'fr' : $_POST['countryCode'];
                            $this->result = $provider->{$endpoint}($_POST['siren'], $countryCode);
                            break;
                        case 'altares':
                            switch ($endpoint) {
                                case 'getFinancialSummary':
                                case 'getBalanceManagementLine':
                                    $this->result = $provider->{$endpoint}($_POST['siren'], $_POST['balanceId']);
                                    break;
                                default:
                                    $this->result = $provider->{$endpoint}($_POST['siren']);
                                    break;
                            }
                            break;
                        default:
                            $this->result = $provider->$endpoint($_POST['siren']);
                            break;
                    }
                } else {
                    $this->result = 'Veuillez sélectionner un webservice';
                }
            }
        } catch (\Exception $exception) {
            $this->result = 'Error code: ' . $exception->getCode() . '. Error message: ' . $exception->getMessage() . ' in file: ' . $exception->getFile() . ' at line: ' . $exception->getLine();
        }
    }

    public function _storedData()
    {
        $this->hideDecoration();
        /** @var \Unilend\Bundle\WSClientBundle\Service\CallHistoryManager $wsCallHistory */
        $wsCallHistory = $this->get('unilend.service.ws_client.call_history_handler');
        /** @var EntityRepository $wsResourceRepository */
        $wsResourceRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:WsExternalResource');
        $this->resources      = $wsResourceRepository->findBy([], ['providerName' => 'ASC', 'resourceName' => 'ASC']);
        $this->result         = false;
        $this->result         = [];

        try {
            if (isset($_POST['siren'])) {
                /** @var WsExternalResource $wsResource */
                $wsResource = $wsResourceRepository->findOneBy(['label' => $_POST['resource_label']]);

                if (false === empty($_POST['siren']) && null !== $wsResource) {
                    $days         = empty($_POST['nbDaysAgo']) ? 3 : $_POST['nbDaysAgo'];
                    $date         = (new \DateTime())->sub(new \DateInterval('P' . $days . 'D'));
                    $this->result = $wsCallHistory->fetchLatestDataFromMongo($_POST['siren'], [], $wsResource->getProviderName(), $wsResource->getResourceName(), $date);
                } else {
                    $this->result = 'Please give a siren and a valid resource from the drop down list';
                }
            }
        } catch (\Exception $exception) {
            $this->result = 'Error code: ' . $exception->getCode() . ' Error message: ' . $exception->getMessage() . ' in file: ' . $exception->getFile() . ' at line: ' . $exception->getLine();
        }
    }
}
