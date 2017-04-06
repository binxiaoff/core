<?php

class simulationController extends bootstrap
{
    /**
     * @var array
     */
    private $methods = [
        'get_incident_list_codinf'            => 'getIncidentList',
        'get_score_altares'                   => 'getScore',
        'get_company_identity_altares'        => 'getCompanyIdentity',
        'get_establishment_identity_altares'  => 'getEstablishmentIdentity',
        'get_balance_sheet_altares'           => 'getBalanceSheets',
        'get_financial_summary_altares'       => 'getFinancialSummary',
        'get_balance_management_line_altares' => 'getBalanceManagementLine',
        'get_score_infolegale'                => 'getScore',
        'search_company_infolegale'           => 'searchCompany',
        'get_identity_infolegale'             => 'getIdentity',
        'get_legal_notice_infolegale'         => 'getListAnnonceLegale',
        'get_indebtedness_infogreffe'         => 'getIndebtedness',
        'search_company_euler'                => 'searchCompany',
        'get_grade_euler'                     => 'getGrade',
        'get_traffic_light_euler'             => 'getTrafficLight'
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
        /** @var \ws_external_resource $wsResource */
        $wsResource      = $this->loadData('ws_external_resource');
        $this->resources = $wsResource->select('', 'provider_name ASC, resource_name ASC');
        $this->result    = [];

        try {
            if (isset($_POST['send'], $_POST['siren'])) {
                if ($wsResource->get($_POST['resource_label'], 'label')) {
                    $provider = $this->get('unilend.service.ws_client.' . $wsResource->provider_name . '_manager');
                    $endpoint = $this->methods[$wsResource->label];

                    switch ($wsResource->provider_name) {
                        case 'euler':
                            $countryCode  = (empty($_POST['countryCode'])) ? 'fr' : $_POST['countryCode'];
                            $this->result = $provider->$endpoint($_POST['siren'], $countryCode);
                            break;
                        case 'altares':
                            switch ($endpoint) {
                                case 'getFinancialSummary':
                                case 'getBalanceManagementLine':
                                    $this->result = $provider->$endpoint($_POST['siren'], $_POST['balanceId']);
                                    break;
                                default:
                                    $this->result = $provider->$endpoint($_POST['siren']);
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

    /**
     *
     */
    public function _storedData()
    {
        $this->hideDecoration();
        /** @var \Unilend\Bundle\WSClientBundle\Service\CallHistoryManager $wsCallHistory */
        $wsCallHistory = $this->get('unilend.service.ws_client.call_history_handler');
        /** @var \ws_external_resource $wsResource */
        $wsResource      = $this->loadData('ws_external_resource');
        $this->resources = $wsResource->select();
        $this->result    = [];

        try {
            if (isset($_POST['send'], $_POST['siren'])) {
                if (false === empty($_POST['siren']) && false !== $wsResource->get($_POST['resource_label'], 'label')) {
                    if (true === empty($_POST['nbDaysAgo'])) {
                        $days = 3;
                    } else {
                        $days = $_POST['nbDaysAgo'];
                    }
                    $date = (new \DateTime())->sub(new \DateInterval('P' . $days . 'D'));
                    $this->result = $wsCallHistory->fetchLatestDataFromMongo($_POST['siren'], $wsResource->provider_name, $wsResource->resource_name, $date);
                } else {
                    $this->result = 'Please give a siren and a valid resource from the drop down list';
                }
            }
        } catch (Exception $exception) {
            $this->result = 'Error code: ' . $exception->getCode() . ' Error message: ' . $exception->getMessage() . ' in file: ' . $exception->getFile() . ' at line: ' . $exception->getLine();
        }
    }
}
