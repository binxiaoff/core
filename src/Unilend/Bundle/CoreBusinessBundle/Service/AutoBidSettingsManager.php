<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsHistoryActions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class AutoBidSettingsManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class AutoBidSettingsManager
{
    const CGV_AUTOBID = 53;

    /** @var ClientSettingsManager */
    private $oClientSettingsManager;

    /** @var ClientManager */
    private $oClientManager;

    /** @var NotificationManager */
    private $oNotificationManager;

    /** @var LenderManager */
    private $oLenderManager;

    /** @var  ProductManager */
    private $productManager;

    /** @var  ContractManager */
    private $contractManager;

    /** @var  EntityManagerSimulator */
    private $entityManagerSimulator;

    /** @var  EntityManager */
    private $entityManager;

    /**
     * AutoBidSettingsManager constructor.
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManager          $entityManager
     * @param ClientSettingsManager  $oClientSettingsManager
     * @param ClientManager          $oClientManager
     * @param NotificationManager    $oNotificationManager
     * @param LenderManager          $oLenderManager
     * @param ProductManager         $productManager
     * @param ContractManager        $contractManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        ClientSettingsManager $oClientSettingsManager,
        ClientManager $oClientManager,
        NotificationManager $oNotificationManager,
        LenderManager $oLenderManager,
        ProductManager $productManager,
        ContractManager $contractManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->oClientSettingsManager = $oClientSettingsManager;
        $this->oClientManager         = $oClientManager;
        $this->oNotificationManager   = $oNotificationManager;
        $this->oLenderManager         = $oLenderManager;
        $this->productManager         = $productManager;
        $this->contractManager        = $contractManager;
    }

    /**
     * @param Clients $client
     *
     * @throws \Exception
     */
    public function on(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');

        if (false === empty($client) && $this->isQualified($client)
            && $this->oLenderManager->canBid($client)
        ) {
            $this->oClientSettingsManager->saveClientSetting($client, \client_setting_type::TYPE_AUTO_BID_SWITCH, \client_settings::AUTO_BID_ON);

            if (0 == $oAutoBid->counter('id_lender = ' . $wallet->getId())) {
                $this->oNotificationManager->create(
                    Notifications::TYPE_AUTOBID_FIRST_ACTIVATION,
                    \clients_gestion_type_notif::TYPE_AUTOBID_FIRST_ACTIVATION,
                    $client->getIdClient(),
                    'sendFirstAutoBidActivation'
                );
            }
        }
    }

    /**
     * @param Clients $client
     *
     * @throws \Exception
     */
    public function off(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        $this->oClientSettingsManager->saveClientSetting($client, \client_setting_type::TYPE_AUTO_BID_SWITCH, \client_settings::AUTO_BID_OFF);
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function isQualified(Clients $client)
    {
        if (false === $client->isLender()) {
            return false;
        }
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');

        if (false === $settings->get('Auto-bid global switch', 'type')) {
            return false;
        }

        foreach ($this->productManager->getAvailableProducts(true) as $product) {
            $autobidContracts = $this->productManager->getAutobidEligibleContracts($product);
            foreach ($autobidContracts as $contract) {
                if (true === $this->contractManager->isClientEligible($client, $contract)) {
                    return true;
                }
            }
        }

        if ($settings->value && $this->oClientManager->isAcceptedCGV($client, self::CGV_AUTOBID) || $this->oClientManager->isBetaTester($client)) {
            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     * @param string  $sEvaluation
     * @param int     $iAutoBidPeriodId
     * @param float   $fRate
     * @param int     $iAmount
     *
     * @return bool
     * @throws \Exception
     */
    public function saveSetting(Clients $client, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        /** @var \settings $oSettings */
        $oSettings = $this->entityManagerSimulator->getRepository('settings');
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');
        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');

        $oSettings->get('Pret min', 'type');
        $iAmountMin = (int)$oSettings->value;

        if ($iAmount < $iAmountMin) {
            return false;
        }


        if (false === $this->isRateValid($fRate)) {
            return false;
        }

        $aAutoBids = $oAutoBid->select('evaluation = "' . $sEvaluation . '" AND id_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender = ' . $wallet->getId());

        if (empty($aAutoBids)) {
            $this->createSetting($wallet->getId(), $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
        } else {
            $aAutoBidActive = array_shift($aAutoBids);

            if ($oBid->exist($aAutoBidActive['id_autobid'], 'id_autobid')) {
                $oAutoBid->get($aAutoBidActive['id_autobid']);
                $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                $oAutoBid->update();
                $this->createSetting($wallet->getId(), $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
            } else {
                $oAutoBid->get($aAutoBidActive['id_autobid']);
                $oAutoBid->rate_min = $fRate;
                $oAutoBid->amount   = $iAmount;
                $oAutoBid->update();
            }

            // It shouldn't have more than one autobid settings for each category, but if we have, archive them all.
            if (false === empty($aAutoBids)) {
                foreach ($aAutoBids as $aBid) {
                    $oAutoBid->get($aBid['id_autobid']);
                    $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                    $oAutoBid->update();
                }
            }
        }

        return true;
    }

    /**
     * @param int     $iLenderId
     * @param string  $sEvaluation
     * @param int     $iAutoBidPeriodId
     * @param float   $fRate
     * @param int     $iAmount
     *
     * @return bool
     */
    private function createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');

        $oAutoBid->id_lender  = $iLenderId;
        $oAutoBid->status     = \autobid::STATUS_ACTIVE;
        $oAutoBid->evaluation = $sEvaluation;
        $oAutoBid->id_period  = $iAutoBidPeriodId;
        $oAutoBid->rate_min   = $fRate;
        $oAutoBid->amount     = $iAmount;
        $oAutoBid->create();
    }

    /**
     * @param Clients $client
     *
     * @return bool
     * @throws \Exception
     */
    public function isNovice(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        /** @var \autobid $oAutobid */
        $oAutobid  = $this->entityManagerSimulator->getRepository('autobid');
        $bIsNovice = true;

        if ($this->hasAutoBidActivationHistory($client) && $oAutobid->counter('id_lender = ' . $wallet->getId()) > 0) {
            if ($oAutobid->exist($wallet->getId() . '" AND status = "' . \autobid::STATUS_INACTIVE, 'id_lender')) {
                $bIsNovice = false;
            } else {
                $aAutobids = $oAutobid->getSettings($wallet->getId());
                $fRate     = $aAutobids[0]['rate_min'];
                $iAmount   = $aAutobids[0]['amount'];

                foreach ($aAutobids as $aAutobid) {
                    if ($fRate !== $aAutobid['rate_min'] || $iAmount !== $aAutobid['amount']) {
                        $bIsNovice = false;
                        break;
                    }
                }
            }
        }

        return $bIsNovice;
    }

    /**
     * @param Clients $client
     * @param float   $fRate
     * @param int     $iAmount
     *
     * @throws \Exception
     */
    public function saveNoviceSetting(Clients $client, $fRate, $iAmount)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        /** @var \project_period $oAutoBidPeriods */
        $oAutoBidPeriods = $this->entityManagerSimulator->getRepository('project_period');
        /** @var \projects $oProject */
        $oProject        = $this->entityManagerSimulator->getRepository('projects');
        $aAutoBidPeriods = $oAutoBidPeriods->select('status = ' . \project_period::STATUS_ACTIVE);
        $aRiskValues     = $oProject->getAvailableRisks();

        foreach ($aAutoBidPeriods as $aPeriod) {
            foreach ($aRiskValues as $sEvaluation) {
                $this->saveSetting($client, $sEvaluation, $aPeriod['id_period'], $fRate, $iAmount);
                $this->activateDeactivateSetting($client, $sEvaluation, $aPeriod['id_period'], \autobid::STATUS_ACTIVE);
            }
        }
    }

    /**
     * @param string $sEvaluation
     * @param int    $iDuration
     *
     * @return int
     */
    public function predictAmount($sEvaluation, $iDuration)
    {
        return $this->entityManagerSimulator->getRepository('autobid')->sumAmount($sEvaluation, $iDuration);
    }

    /**
     * @param Clients $client
     * @param string  $sEvaluation
     * @param int     $iAutoBidPeriodId
     * @param int     $iNewStatus
     *
     * @throws \Exception
     */
    public function activateDeactivateSetting(Clients $client, $sEvaluation, $iAutoBidPeriodId, $iNewStatus)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');
        $oAutoBid->get(
            $wallet->getId(),
            'status != ' . \autobid::STATUS_ARCHIVED . ' AND evaluation = "' . $sEvaluation . '" AND id_period = '
            . $iAutoBidPeriodId . ' AND id_lender'
        );

        if (in_array($iNewStatus, array(\autobid::STATUS_ACTIVE, \autobid::STATUS_INACTIVE))) {
            $oAutoBid->status = $iNewStatus;
            $oAutoBid->update();
        }
    }


    /**
     * @param int $clientId
     *
     * @return array
     */
    public function getLastDateOnOff($clientId)
    {
        $autoBidHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsHistoryActions')
            ->findBy(['idClient' => $clientId, 'nomForm'  => ClientsHistoryActions::AUTOBID_SWITCH], ['added' => 'DESC'], 2);
        $dates          = [];

        foreach ($autoBidHistory as $historyAction) {
            $action                           = unserialize($historyAction->getSerialize());
            $dates[$action['autobid_switch']] = $historyAction->getAdded();
        }
        return $dates;
    }

    /**
     * @param Clients $client
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getValidationDate(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');

        return new \DateTime($oAutoBid->getValidationDate($wallet->getId()));
    }

    /**
     * @param Clients $client
     *
     * @return bool
     * @throws \Exception
     */
    public function isOn(Clients $client)
    {
        if (false === $client->isLender()) {
            return false;
        }

        return (bool) $this->oClientSettingsManager->getSetting($client, \client_setting_type::TYPE_AUTO_BID_SWITCH);
    }

    /**
     * @param Clients $client
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getActivationTime(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        /** @var \client_settings $oClientSettings */
        $oClientSettings = $this->entityManagerSimulator->getRepository('client_settings');

        if ($oClientSettings->get($client->getIdClient(), 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oActivationTime = new \DateTime($oClientSettings->added);
        } else {
            $oActivationTime = new \DateTime();
        }
        return $oActivationTime;
    }

    /**
     * @param Wallet $wallet
     *
     * @return bool
     * @throws \Exception
     */
    public function hasAutoBidActivationHistory(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsHistoryActions')->countAutobidActivationHistory($client->getIdClient()) > 0;
    }

    /**
     * @param string|null $evaluation
     * @param int|null    $periodId
     *
     * @return mixed
     */
    public function getRateRange($evaluation = null, $periodId = null)
    {
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');

        if ($evaluation === null || $periodId === null) {
            $projectMinMaxRate = $projectRateSettings->getGlobalMinMaxRate();
        } else {
            $projectRates = $projectRateSettings->getSettings($evaluation, $periodId);
            $projectMinMaxRate = array_shift($projectRates);
            if (empty($projectMinMaxRate)) {
                $projectMinMaxRate = $projectRateSettings->getGlobalMinMaxRate();
            }
        }

        return $projectMinMaxRate;
    }

    /**
     * Check if a autobid settings rate is valid (don't use it for a bid on a particular project. in this case, use getProjectRateRange() of bid manager)
     *
     * @param      $fRate
     * @param null $evaluation
     * @param null $periodId
     *
     * @return bool
     */
    public function isRateValid($fRate, $evaluation = null, $periodId = null)
    {
        $projectRate = $this->getRateRange($evaluation, $periodId);

        if (bccomp($fRate, $projectRate['rate_min'], 1) >= 0 && bccomp($fRate, $projectRate['rate_max'], 1) <= 0) {
            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     *
     * @return int|null
     * @throws \Exception
     */
    public function getAmount(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');
        $settings = $oAutoBid->getSettings($wallet->getId(), null, null, [\autobid::STATUS_ACTIVE], [], 1);
        $amount   = null;

        if (true === isset($settings[0]['amount'])) {
            $amount = $settings[0]['amount'];
        }

        return $amount;
    }

    /**
     * @param Clients $client
     *
     * @return int|null
     * @throws \Exception
     */
    public function getMaxAmountPossible(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        $maxAmount = 0;

        foreach ($this->productManager->getAvailableProducts(true) as $product) {
            $currentMaxAmount = $this->productManager->getMaxEligibleAmount($client, $product, true);
            if (null === $currentMaxAmount) {
                return null;
            }
            $maxAmount = max($maxAmount, $currentMaxAmount);
        }

        return $maxAmount;
    }

    /**
     * @param Clients $client
     * @param null    $projectRates optional, for optimize the performance.
     *
     * @return array
     * @throws \Exception
     */
    public function getBadAutoBidSettings(Clients $client, $projectRates = null)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        /** @var \autobid $autoBid */
        $autoBid = $this->entityManagerSimulator->getRepository('autobid');

        if ($projectRates == null) {
            /** @var \project_rate_settings $projectRateSettings */
            $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');
            $projectRates        = $projectRateSettings->getSettings();
        }

        $projectMaxRate = [];
        foreach ($projectRates as $rate) {
            $projectMaxRate[$rate['id_period']][$rate['evaluation']] = $rate['rate_max'];
        }

        $autoBidSettings = $autoBid->getSettings($wallet->getId());
        $badSettings     = [];
        foreach ($autoBidSettings as $setting) {
            if (false === isset($projectMaxRate[$setting['id_period']][$setting['evaluation']])) {
                continue;
            }
            if (bccomp($setting['rate_min'], $projectMaxRate[$setting['id_period']][$setting['evaluation']], 1) > 0) {
                $badSettings[] = [
                    'period_min'       => $setting['period_min'],
                    'period_max'       => $setting['period_max'],
                    'evaluation'       => $setting['evaluation'],
                    'rate_min_autobid' => $setting['rate_min'],
                    'rate_max_project' => $projectMaxRate[$setting['id_period']][$setting['evaluation']],
                ];
            }
        }

        return $badSettings;
    }
}
