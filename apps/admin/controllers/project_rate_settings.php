<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

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
        $this->autoFireView = false;

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

    public function _warn_confirmation_box()
    {
        $this->hideDecoration();
    }

    public function _warn_lender_autolend_settings()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var client_settings $clientSettings */
        $clientSettings = $this->loadData('client_settings');
        /** @var client_setting_type $clientSettingType */
        $clientSettingType = $this->loadData('client_setting_type');
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->loadData('project_rate_settings');
        /** @var settings $settings */
        $settings = $this->loadData('settings');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager $lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $autoBidSettingsManager */
        $autoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $projectRates = $projectRateSettings->getSettings();

        $settings->get('Facebook', 'type');
        $facebook = $settings->value;

        $settings->get('Twitter', 'type');
        $twitter = $settings->value;

        $clientSettingType->get('autobid_switch', 'label');
        $offset = 0;
        $limit  = 100;
        while ($autoLendActiveClients = $clientSettings->select('id_type=' . $clientSettingType->id_type . ' AND value = ' . client_settings::AUTO_BID_ON, '', $offset, $limit)) {
            $offset += $limit;
            foreach ($autoLendActiveClients as $autoLendClient) {
                /** @var Wallet $wallet */
                $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($autoLendClient['id_client'], WalletType::LENDER);
                if (
                    null !== $wallet
                    || false === $lenderManager->canBid($wallet->getIdClient())
                    || $autoBidSettingsManager->isNovice($wallet->getIdClient())
                ) {
                    continue;
                }

                $badSettings = $autoBidSettingsManager->getBadAutoBidSettings($wallet->getIdClient(), $projectRates);
                if (false === empty($badSettings)) {
                    $varMail = [
                        'first_name'     => $wallet->getIdClient()->getPrenom(),
                        'autobid_link'   => $this->furl . '/profile/autolend#parametrage',
                        'lien_tw'        => $facebook,
                        'lien_fb'        => $twitter,
                        'surl'           => $this->surl,
                        'url'            => $this->furl,
                        'motif_virement' => $wallet->getWireTransferPattern(),
                        'annee'          => date('Y')
                    ];
                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-bad-autolend-settings', $varMail);
                    $message->setTo($wallet->getIdClient()->getEmail());
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }
            }
        }
    }
}