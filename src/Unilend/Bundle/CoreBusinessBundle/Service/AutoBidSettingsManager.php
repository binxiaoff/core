<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
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
    ) {
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
     * @param \lenders_accounts $oLenderAccount
     */
    public function on(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');

        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');

        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner) && $this->isQualified($oLenderAccount)
            && $this->oLenderManager->canBid($oLenderAccount)
        ) {
            $this->oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH, \client_settings::AUTO_BID_ON);

            if ($oAutoBid->counter('id_lender = ' . $oLenderAccount->id_lender_account) == 0) {
                $this->oNotificationManager->create(
                    \notifications::TYPE_AUTOBID_FIRST_ACTIVATION,
                    \clients_gestion_type_notif::TYPE_AUTOBID_FIRST_ACTIVATION,
                    $oClient->id_client,
                    'sendFirstAutoBidActivation'
                );
            }
        }
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     */
    public function off(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');

        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner)) {
            $this->oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH, \client_settings::AUTO_BID_OFF);
        }
    }

    /**
     * @param \lenders_accounts $lenderAccount
     *
     * @return bool
     */
    public function isQualified(\lenders_accounts $lenderAccount)
    {
        if (empty($lenderAccount->id_lender_account) || empty($lenderAccount->id_client_owner)) {
            return false;
        }
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        /** @var \clients $client */
        $client = $this->entityManagerSimulator->getRepository('clients');

        if (false === $settings->get('Auto-bid global switch', 'type')) {
            return false;
        }

        if (false === $client->get($lenderAccount->id_client_owner)) {
            return false;
        }

        foreach ($this->productManager->getAvailableProducts(true) as $product) {
            $autobidContracts = $this->productManager->getAutobidEligibleContracts($product);
            foreach ($autobidContracts as $contract) {
                if (true === $this->contractManager->isLenderEligible($lenderAccount, $contract)) {
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
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param float  $fRate
     * @param int    $iAmount
     *
     * @return bool
     */
    public function saveSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount)
    {
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

        $aAutoBids = $oAutoBid->select('evaluation = "' . $sEvaluation . '" AND id_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender = ' . $iLenderId);

        if (empty($aAutoBids)) {
            $this->createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
        } else {
            $aAutoBidActive = array_shift($aAutoBids);

            if ($oBid->exist($aAutoBidActive['id_autobid'], 'id_autobid')) {
                $oAutoBid->get($aAutoBidActive['id_autobid']);
                $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                $oAutoBid->update();
                $this->createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
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
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param float  $fRate
     * @param int    $iAmount
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
     * @param \lenders_accounts $oLendersAccount
     *
     * @return bool
     */
    public function isNovice(\lenders_accounts $oLendersAccount)
    {
        /** @var \autobid $oAutobid */
        $oAutobid  = $this->entityManagerSimulator->getRepository('autobid');
        $bIsNovice = true;

        if ($this->hasAutoBidActivationHistory($oLendersAccount) && $oAutobid->counter('id_lender = ' . $oLendersAccount->id_lender_account) > 0) {
            if ($oAutobid->exist($oLendersAccount->id_lender_account . '" AND status = "' . \autobid::STATUS_INACTIVE, 'id_lender')) {
                $bIsNovice = false;
            } else {
                $aAutobids = $oAutobid->getSettings($oLendersAccount->id_lender_account);
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
     * @param int   $iLenderId
     * @param float $fRate
     * @param int   $iAmount
     */
    public function saveNoviceSetting($iLenderId, $fRate, $iAmount)
    {
        /** @var \project_period $oAutoBidPeriods */
        $oAutoBidPeriods = $this->entityManagerSimulator->getRepository('project_period');
        /** @var \projects $oProject */
        $oProject        = $this->entityManagerSimulator->getRepository('projects');
        $aAutoBidPeriods = $oAutoBidPeriods->select('status = ' . \project_period::STATUS_ACTIVE);
        $aRiskValues     = $oProject->getAvailableRisks();

        foreach ($aAutoBidPeriods as $aPeriod) {
            foreach ($aRiskValues as $sEvaluation) {
                $this->saveSetting($iLenderId, $sEvaluation, $aPeriod['id_period'], $fRate, $iAmount);
                $this->activateDeactivateSetting($iLenderId, $sEvaluation, $aPeriod['id_period'], \autobid::STATUS_ACTIVE);
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
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param int    $iNewStatus
     */
    public function activateDeactivateSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $iNewStatus)
    {
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');
        $oAutoBid->get(
            $iLenderId,
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
        $autoBidHistory        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsHistoryActions')->getLastAutoBidOnOffActions($clientId);
        $dates                 = [];

        foreach ($autoBidHistory as $historyAction) {
            $action                           = unserialize($historyAction['serialize']);
            $dates[$action['autobid_switch']] = $historyAction['added'];
        }
        return $dates;
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return \DateTime
     */
    public function getValidationDate(\lenders_accounts $oLenderAccount)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');
        return new \DateTime($oAutoBid->getValidationDate($oLenderAccount->id_lender_account));
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function isOn(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');
        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner)) {
            return (bool)$this->oClientSettingsManager->getSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH);
        }

        return false;
    }

    /**
     * @param \clients $oClient
     *
     * @return \DateTime|null
     */
    public function getActivationTime(\clients $oClient)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = $this->entityManagerSimulator->getRepository('client_settings');

        if ($oClientSettings->get($oClient->id_client, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oActivationTime = new \DateTime($oClientSettings->added);
        } else {
            $oActivationTime = new \DateTime();
        }
        return $oActivationTime;
    }

    /**
     * @param \lenders_accounts $oLendersAccount
     *
     * @return bool
     */
    public function hasAutoBidActivationHistory(\lenders_accounts $oLendersAccount)
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsHistoryActions')->countAutobidActivationHistory($oLendersAccount->id_client_owner) > 0;
    }

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

    public function getAmount(\lenders_accounts $lender)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');
        $settings = $oAutoBid->getSettings($lender->id_lender_account, null, null, \autobid::STATUS_ACTIVE, [], 1);

        $amount = null;

        if (true === isset($settings[0]['amount'])) {
            $amount = $settings[0]['amount'];
        }

        return $amount;
    }

    public function getMaxAmountPossible(\lenders_accounts $lender)
    {
        $maxAmount = 0;

        foreach ($this->productManager->getAvailableProducts(true) as $product) {
            $currentMaxAmount = $this->productManager->getAutobidMaxEligibleAmount($lender, $product);
            if (null === $currentMaxAmount) {
                return null;
            }
            $maxAmount = max($maxAmount, $currentMaxAmount);
        }

        return $maxAmount;
    }
}
