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

    public function _altares()
    {
        ini_set('default_socket_timeout', 60);
        $this->hideDecoration();
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Altares $altares */
        $altares = $this->get('unilend.service.altares');

        $siren = '';
        if (isset($_POST['siren']) && $_POST['siren'] != '') {
            $siren = $_POST['siren'];
        }
        $this->result = [];
        if (isset($_POST['api'])) {
            switch ($_POST['api']) {
                case 1 :
                    $this->result = $altares->getEligibility($siren);
                    break;
                case 2 :
                    $this->result = $altares->getBalanceSheets($siren);
                    break;
                default :
                    $this->result = [];
                    break;
            }
        }
    }

    public function _wsProvider()
    {
        $this->hideDecoration();
        /** @var \ws_external_resource $wsResources */
        $wsResources = $this->loadData('ws_external_resource');

        $this->resources = $wsResources->select();
        $this->result    = [];
        try {
            if (isset($_POST['send'], $_POST['siren'])) {
                if (is_numeric($_POST['resourceId']) && $wsResources->get($_POST['resourceId'])) {
                    $provider = $this->get('unilend.ws_client.' . $wsResources->provider_name . '_manager');
                    $endpoint = $wsResources->resource_name;
                    switch ($wsResources->provider_name) {
                        case 'euler':
                            $countryCode  = (empty($_POST['countryCode'])) ? 'fr' : $_POST['countryCode'];
                            $this->result = $provider->$endpoint($_POST['siren'], $countryCode);
                            break;
                        case 'altares':
                            switch ($wsResources->resource_name) {
                                case 'getFinancialSummary':
                                case 'getBalanceManagementLine':
                                    $this->result = $provider->{$wsResources->resource_name}($_POST['siren'], $_POST['balanceId']);
                                    break;
                                default:
                                    $this->result = $provider->{$wsResources->resource_name}($_POST['siren']);
                                    break;
                            }
                            break;
                        default:
                            $this->result = $provider->{$wsResources->resource_name}($_POST['siren']);
                            break;
                    }
                } else {
                    $this->result = 'Please select a web service to call';
                }
            }

            if ($this->result instanceof \Psr\Http\Message\ResponseInterface) {
                $this->result = $this->result->getBody()->getContents();
            }
        } catch (\Exception $exception) {
            $this->result = 'Error code: ' . $exception->getCode() . '. Error message: ' . $exception->getMessage() . ' at line: ' . $exception->getLine();
        }
    }
}
