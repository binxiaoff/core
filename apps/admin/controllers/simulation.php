<?php

class simulationController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
    }

    public function _default()
    {
        header('Location: ' . $this->lurl);
    }

    public function _wsProvider()
    {
        $this->hideDecoration();
        /** @var \ws_external_resource $wsResources */
        $wsResources = $this->loadData('ws_external_resource');

        $methods = [
            'get_incident_list_codinf'            => 'getIncidentList',
            'get_score_altares'                   => 'getScore',
            'get_company_identity_altares'        => 'getCompanyIdentity',
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

        $this->resources = $wsResources->select();
        $this->result    = [];
        try {
            if (isset($_POST['send'], $_POST['siren'])) {
                if ($wsResources->get($_POST['resource_label'], 'label')) {
                    $provider = $this->get('unilend.service.ws_client.' . $wsResources->provider_name . '_manager');
                    $endpoint = $methods[$wsResources->label];

                    switch ($wsResources->provider_name) {
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
                    $this->result = 'Please select a web service to call';
                }
            }
        } catch (\Exception $exception) {
            $this->result = 'Error code: ' . $exception->getCode() . '. Error message: ' . $exception->getMessage() . ' at line: ' . $exception->getLine();
        }
    }
}
