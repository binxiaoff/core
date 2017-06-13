<?php

use \Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;
use \Doctrine\ORM\EntityRepository;

class simulationController extends bootstrap
{
    /**
     * @var array
     */
    private $methods = [
        'get_incident_list_codinf'               => 'getIncidentList',
        'get_score_altares'                      => 'getScore',
        'get_company_identity_altares'           => 'getCompanyIdentity',
        'get_establishment_identity_altares'     => 'getEstablishmentIdentity',
        'get_balance_sheet_altares'              => 'getBalanceSheets',
        'get_financial_summary_altares'          => 'getFinancialSummary',
        'get_balance_management_line_altares'    => 'getBalanceManagementLine',
        'get_score_infolegale'                   => 'getScore',
        'search_company_infolegale'              => 'searchCompany',
        'get_identity_infolegale'                => 'getIdentity',
        'get_legal_notice_infolegale'            => 'getListAnnonceLegale',
        'get_indebtedness_infogreffe'            => 'getIndebtedness',
        'search_company_euler'                   => 'searchCompany',
        'get_grade_euler'                        => 'getGrade',
        'get_traffic_light_euler'                => 'getTrafficLight',
        'get_executives_infolegale'              => 'getExecutives',
        'get_mandates_infolegale'                => 'getMandates',
        'get_homonyms_infolegale'                => 'getHomonyms',
        'get_announcements__director_infolegale' => 'getDirectorAnnouncements',
    ];

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('simulator');
    }

    public function _default()
    {
        header('Location: ' . $this->lurl);
        die;
    }

    public function _wsProvider()
    {
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
                    $this->result = $wsCallHistory->fetchLatestDataFromMongo($_POST['siren'], $wsResource->getProviderName(), $wsResource->getResourceName(), $date);
                } else {
                    $this->result = 'Please give a siren and a valid resource from the drop down list';
                }
            }
        } catch (\Exception $exception) {
            $this->result = 'Error code: ' . $exception->getCode() . ' Error message: ' . $exception->getMessage() . ' in file: ' . $exception->getFile() . ' at line: ' . $exception->getLine();
        }
    }
}
