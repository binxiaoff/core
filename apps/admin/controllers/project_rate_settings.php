<?php

use Doctrine\ORM\EntityManager;
use Unilend\Entity\{
    ClientSettingType, Wallet, WalletType, Zones
};
use Unilend\Bundle\CoreBusinessBundle\Service\{
    AutoBidSettingsManager, LenderManager
};

class project_rate_settingsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

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

        /** @var \client_settings $clientSettings */
        $clientSettings = $this->loadData('client_settings');
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->loadData('project_rate_settings');
        $projectRates        = $projectRateSettings->getSettings();
        /** @var LenderManager $lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');
        /** @var AutoBidSettingsManager $autoBidSettingsManager */
        $autoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        /** @var EntityManager $entityManager */
        $entityManager    = $this->get('doctrine.orm.entity_manager');
        $walletRepository = $entityManager->getRepository(Wallet::class);

        $offset            = 0;
        $limit             = 100;
        $clientSettingType = $entityManager->getRepository(ClientSettingType::class)->findOneBy(['label' => ClientSettingType::LABEL_AUTOBID_SWICTH]);

        while ($autoLendActiveClients = $clientSettings->select('id_type=' . $clientSettingType->getIdType() . ' AND value = ' . client_settings::AUTO_BID_ON, '', $offset, $limit)) {
            $offset += $limit;
            foreach ($autoLendActiveClients as $autoLendClient) {
                /** @var Wallet $wallet */
                $wallet = $walletRepository->getWalletByType($autoLendClient['id_client'], WalletType::LENDER);
                if (
                    null === $wallet
                    || false === $lenderManager->canBid($wallet->getIdClient())
                    || $autoBidSettingsManager->isNovice($wallet->getIdClient())
                ) {
                    continue;
                }

                $badSettings = $autoBidSettingsManager->getBadAutoBidSettings($wallet->getIdClient(), $projectRates);

                if (false === empty($badSettings)) {
                    $keywords = [
                        'firstName'            => $wallet->getIdClient()->getPrenom(),
                        'autolendSettingsLink' => $this->furl . '/profile/autolend#parametrage',
                        'lenderPattern'        => $wallet->getWireTransferPattern()
                    ];

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('bad-autolend-settings', $keywords);

                    try {
                        $message->setTo($wallet->getIdClient()->getEmail());
                        $mailer = $this->get('mailer');
                        $mailer->send($message);
                    } catch (\Exception $exception) {
                        $this->get('logger')->warning(
                            'Could not send email: bad-autolend-settings - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $wallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }
    }
}
