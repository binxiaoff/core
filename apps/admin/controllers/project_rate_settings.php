<?php

class project_rate_settingsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->users->checkAccess('admin');
        $this->menu_admin = 'configuration';
    }

    public function _default()
    {
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->loadData('project_rate_settings');
        $this->groupedRate   = [];
        $rateTable           = $projectRateSettings->getSettings();

        if (false === empty($rateTable)) {
            foreach ($rateTable as $rate) {
                $this->groupedRate[$rate['id_period']][$rate['evaluation']] = $rate;
            }
        }
    }

    public function _save()
    {
        $this->hideDecoration();
        $response = ['result' => 'KO', 'message' => ''];
        if (isset($this->params[0], $this->params[1], $_POST['rate_min'], $_POST['rate_max'])) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRateSettingsManager $projectRateSettingsManager */
            $projectRateSettingsManager = $this->get('unilend.service.project_rate_settings_manager');
            try {
                $projectRateSettingsManager->saveSetting($this->params[0], $this->params[1], $_POST['rate_min'], $_POST['rate_max']);
                $response['result'] = 'OK';

            } catch (Exception $exception) {
                $response = ['result' => 'KO', 'message' => $exception->getMessage()];
            }
        } else {
            $response = ['result' => 'KO', 'message' => 'missing parameters'];
        }

        echo json_encode($response);
    }

    public function _warn_lender_autoLend_settings()
    {
        $this->hideDecoration();
        /** @var client_settings $clientSettings */
        $clientSettings = $this->loadData('client_settings');
        /** @var client_setting_type $clientSettingType */
        $clientSettingType = $this->loadData('client_setting_type');
        /** @var lenders_accounts $lender */
        $lender = $this->loadData('lenders_accounts');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager $lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');

        $clientSettingType->get('autobid_switch', 'label');
        $offset = 0;
        $limit  = 100;
        while ($autoLendActiveClients = $clientSettings->select('id_type=' . $clientSettingType->id_type . ' AND value = ' . client_settings::AUTO_BID_ON, '', $offset, $limit)) {
            $offset += $limit;
            foreach ($autoLendActiveClients as $client) {
                if (false === $lender->get($client['id_client'], 'id_client_owner')) {
                    continue;
                }
                $badSettings = $lenderManager->getBadAutoBidSettings($lender);
                if (false === empty($badSettings)) {
                    //todo:send mail
                }
            }
        }
    }
}