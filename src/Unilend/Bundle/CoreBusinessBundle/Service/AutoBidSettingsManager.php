<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Autobid, Clients, ClientSettingType, ClientsHistoryActions, Notifications, ProjectPeriod, Wallet, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\Product\{
    Contract\ContractManager, ProductManager
};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class AutoBidSettingsManager
{
    const AUTOBID_TERMS_OF_SALES = 53;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var ClientSettingsManager */
    private $clientSettingsManager;
    /** @var ClientManager */
    private $clientManager;
    /** @var NotificationManager */
    private $notificationManager;
    /** @var LenderManager */
    private $lenderManager;
    /** @var ProductManager */
    private $productManager;
    /** @var ContractManager */
    private $contractManager;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var MailerManager */
    private $mailerManager;

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManager          $entityManager
     * @param ClientSettingsManager  $clientSettingsManager
     * @param ClientManager          $clientManager
     * @param NotificationManager    $notificationManager
     * @param LenderManager          $lenderManager
     * @param ProductManager         $productManager
     * @param ContractManager        $contractManager
     * @param TermsOfSaleManager     $termsOfSaleManager
     * @param LoggerInterface        $logger
     * @param MailerManager          $mailerManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        ClientSettingsManager $clientSettingsManager,
        ClientManager $clientManager,
        NotificationManager $notificationManager,
        LenderManager $lenderManager,
        ProductManager $productManager,
        ContractManager $contractManager,
        TermsOfSaleManager $termsOfSaleManager,
        LoggerInterface $logger,
        MailerManager $mailerManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->clientSettingsManager  = $clientSettingsManager;
        $this->clientManager          = $clientManager;
        $this->notificationManager    = $notificationManager;
        $this->lenderManager          = $lenderManager;
        $this->productManager         = $productManager;
        $this->contractManager        = $contractManager;
        $this->termsOfSaleManager     = $termsOfSaleManager;
        $this->logger                 = $logger;
        $this->mailerManager          = $mailerManager;
    }

    /**
     * @param Clients $client
     * @param bool    $notifyLender
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function on(Clients $client, bool $notifyLender): void
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        if ($this->isQualified($client) && $this->lenderManager->canBid($client)) {
            $this->clientSettingsManager->saveClientSetting($client, ClientSettingType::TYPE_AUTOBID_SWITCH, \client_settings::AUTO_BID_ON);

            if ($notifyLender) {
                $notification = $this->notificationManager->createNotification(Notifications::TYPE_AUTOBID_FIRST_ACTIVATION, $client->getIdClient());
                $this->mailerManager->sendFirstAutoBidActivation($notification);
            }
        }
    }

    /**
     * @param Clients $client
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function off(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        $this->clientSettingsManager->saveClientSetting($client, ClientSettingType::TYPE_AUTOBID_SWITCH, \client_settings::AUTO_BID_OFF);
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function isQualified(Clients $client): bool
    {
        if (false === $client->isLender()) {
            return false;
        }

        $autobidGlobalSetting = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy(['type' => 'Auto-bid global switch']);

        if (null === $autobidGlobalSetting) {
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

        if (
            $autobidGlobalSetting->getValue() && $this->termsOfSaleManager->isAcceptedVersion($client, self::AUTOBID_TERMS_OF_SALES)
            || $this->clientManager->isBetaTester($client)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Clients       $client
     * @param string        $evaluation
     * @param ProjectPeriod $autobidPeriodId
     * @param string        $rate
     * @param int           $amount
     *
     * @throws \Exception
     */
    public function saveSetting(Clients $client, string $evaluation, ProjectPeriod $autobidPeriodId, string $rate, int $amount): void
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $settingsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');
        $autobidRepository  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        $bidsRepository     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $amountSetting = $settingsRepository->findOneBy(['type' => 'Pret min']);
        if (null === $amountSetting) {
            throw new \Exception('Error while calling ' . __METHOD__ . ' Autobid minimum amount setting not found');
        }
        $amountMin = (int) $amountSetting->getValue();

        if ($amount < $amountMin) {
            throw new \Exception('Error while calling ' . __METHOD__ . ' Amount must be >= ' . $amountMin . ': ' . $amount . ' given');
        }

        if (false === $this->isRateValid($rate)) {
            throw new \Exception('Error while calling ' . __METHOD__ . ' Interest rate is not valid : ' . $rate);
        }

        /** @var Autobid[] $autobidEntities */
        $autobidEntities = $autobidRepository->findBy(['evaluation' => $evaluation, 'idPeriod' => $autobidPeriodId, 'status' => [Autobid::STATUS_ACTIVE, Autobid::STATUS_INACTIVE], 'idLender' => $wallet]);

        if (empty($autobidEntities)) {
            $this->createSetting($wallet, $evaluation, $autobidPeriodId, $rate, $amount);
        } else {
            $autoBidActiveEntity = array_shift($autobidEntities);

            if ($bidsRepository->findOneBy(['idAutobid' => $autoBidActiveEntity])) {
                $autoBidActiveEntity->setStatus(Autobid::STATUS_ARCHIVED);
                $this->entityManager->flush($autoBidActiveEntity);

                $this->createSetting($wallet, $evaluation, $autobidPeriodId, $rate, $amount);
            } else {
                $autoBidActiveEntity
                    ->setRateMin($rate)
                    ->setAmount($amount);
                $this->entityManager->flush($autoBidActiveEntity);
            }

            // It shouldn't have more than one autobid settings for each category, but if we have, archive them all.
            if (false === empty($autobidEntities)) {
                foreach ($autobidEntities as $autobidEntity) {
                    $autobidEntity->setStatus(Autobid::STATUS_ARCHIVED);
                    $this->entityManager->flush($autobidEntity);
                }
            }
        }
    }

    /**
     * @param Wallet        $wallet
     * @param string        $evaluation
     * @param ProjectPeriod $autobidPeriodId
     * @param string        $rate
     * @param int           $amount
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createSetting(Wallet $wallet, string $evaluation, ProjectPeriod $autobidPeriodId, string $rate, int $amount): void
    {
        $autobid = new Autobid();
        $autobid
            ->setIdLender($wallet)
            ->setStatus(Autobid::STATUS_ACTIVE)
            ->setEvaluation($evaluation)
            ->setIdPeriod($autobidPeriodId)
            ->setRateMin($rate)
            ->setAmount($amount);

        $this->entityManager->persist($autobid);
        $this->entityManager->flush($autobid);
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
        $autobidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        $wallet            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $bIsNovice         = true;

        if ($this->hasAutoBidActivationHistory($client) && null !== $autobidRepository->findOneBy(['idLender' => $wallet])) {
            if (null !== $autobidRepository->findOneBy(['idLender' => $wallet, 'status' => Autobid::STATUS_INACTIVE])) {
                $bIsNovice = false;
            } else {
                $autobidSettings = $autobidRepository->findBy(['idLender' => $wallet, 'status' => Autobid::STATUS_ACTIVE]);

                if (count($autobidSettings)) {
                    $firstAutobidSetting = array_shift($autobidSettings);
                    $fRate               = $firstAutobidSetting->getRateMin();
                    $iAmount             = $firstAutobidSetting->getAmount();

                    foreach ($autobidSettings as $autobidSetting) {
                        if ($fRate !== $autobidSetting->getRateMin() || $iAmount !== $autobidSetting->getAmount()) {
                            $bIsNovice = false;
                            break;
                        }
                    }
                }
            }
        }

        return $bIsNovice;
    }

    /**
     * @param Clients $client
     * @param string  $rate
     * @param int     $amount
     *
     * @throws \Exception
     */
    public function saveNoviceSetting(Clients $client, string $rate, int $amount): void
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        $projectPeriodRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectPeriod');
        /** @var \projects $projectData */
        $projectData     = $this->entityManagerSimulator->getRepository('projects');
        $riskEvaluations = $projectData->getAvailableRisks();

        $this->entityManager->getConnection()->beginTransaction();

        try {
            foreach ($projectPeriodRepository->findBy(['status' => \project_period::STATUS_ACTIVE]) as $projectPeriod) {
                foreach ($riskEvaluations as $riskEvaluation) {
                    $this->saveSetting($client, $riskEvaluation, $projectPeriod, $rate, $amount);
                    $this->activateDeactivateSetting($client, $riskEvaluation, $projectPeriod->getIdPeriod(), Autobid::STATUS_ACTIVE);
                }
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollback();

            $this->logger->error(
                'Could not save autobid novice settings for client ' . $client->getIdClient() . ', using rate=' . $rate . ' and amount=' . $amount . '. Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
            throw $exception;
        }
    }

    /**
     * @param string $evaluation
     * @param int    $duration
     *
     * @return int
     */
    public function predictAmount(string $evaluation, int $duration): int
    {
        try {
            return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid')->getSumAmount($evaluation, $duration);
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not calculate average amount of project from autobid settings. Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'evaluation' => $evaluation, 'duration' => $duration, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            return 0;
        }
    }

    /**
     * @param Clients           $client
     * @param string            $evaluation
     * @param int|ProjectPeriod $autoBidPeriodId
     * @param int               $newStatus
     *
     * @throws \Exception
     */
    public function activateDeactivateSetting(Clients $client, string $evaluation, $autoBidPeriodId, int $newStatus): void
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $autobidEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid')
            ->findOneBy(['idLender' => $wallet, 'status' => [Autobid::STATUS_ACTIVE, Autobid::STATUS_INACTIVE], 'evaluation' => $evaluation, 'idPeriod' => $autoBidPeriodId]);

        if (null !== $autobidEntity && in_array($newStatus, [Autobid::STATUS_ACTIVE, Autobid::STATUS_INACTIVE])) {
            $autobidEntity->setStatus($newStatus);
            $this->entityManager->flush($autobidEntity);
        }
    }


    /**
     * @param int $clientId
     *
     * @return array
     */
    public function getLastDateOnOff($clientId): array
    {
        $autoBidHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsHistoryActions')
            ->findBy(['idClient' => $clientId, 'nomForm' => ClientsHistoryActions::AUTOBID_SWITCH], ['added' => 'DESC'], 2);
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
    public function getValidationDate(Clients $client): \DateTime
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $autobidRepository  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        $lastValidationDate = $autobidRepository->getLastValidationDate($wallet);

        return new \DateTime($lastValidationDate);
    }

    /**
     * @param Clients $client
     *
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isOn(Clients $client): bool
    {
        if (false === $client->isLender()) {
            return false;
        }

        return (bool) $this->clientSettingsManager->getSetting($client, ClientSettingType::TYPE_AUTOBID_SWITCH);
    }

    /**
     * @param Clients $client
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getActivationTime(Clients $client): \DateTime
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        /** @var \client_settings $oClientSettings */
        $oClientSettings = $this->entityManagerSimulator->getRepository('client_settings');

        if ($oClientSettings->get($client->getIdClient(), 'id_type = ' . ClientSettingType::TYPE_AUTOBID_SWITCH . ' AND id_client')) {
            $oActivationTime = new \DateTime($oClientSettings->added);
        } else {
            $oActivationTime = new \DateTime();
        }

        return $oActivationTime;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     * @throws \Exception
     */
    public function hasAutoBidActivationHistory(Clients $client): bool
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
    public function getRateRange(?string $evaluation = null, ?int $periodId = null)
    {
        $projectRateSettingsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRateSettings');

        if ($evaluation === null || $periodId === null) {
            $projectMinMaxRate = $projectRateSettingsRepository->getGlobalMinMaxRate();
        } else {
            /** @var \project_rate_settings $projectRateSettings */
            $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');
            $projectRates        = $projectRateSettings->getSettings($evaluation, $periodId);
            $projectMinMaxRate   = array_shift($projectRates);

            if (empty($projectMinMaxRate)) {
                $projectMinMaxRate = $projectRateSettingsRepository->getGlobalMinMaxRate();
            }
        }

        return $projectMinMaxRate;
    }

    /**
     * Check if a autobid settings rate is valid (don't use it for a bid on a particular project. in this case, use
     * getProjectRateRange() of bid manager)
     *
     * @param string      $rate
     * @param string|null $evaluation
     * @param int|null    $periodId
     *
     * @return bool
     */
    public function isRateValid(string $rate, ?string $evaluation = null, ?int $periodId = null): bool
    {
        $projectRate = $this->getRateRange($evaluation, $periodId);

        if (bccomp($rate, $projectRate['rate_min'], 1) >= 0 && bccomp($rate, $projectRate['rate_max'], 1) <= 0) {
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
    public function getAmount(Clients $client): ?int
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $autoBidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        $autobidSetting    = $autoBidRepository->findOneBy(['idLender' => $wallet, 'status' => Autobid::STATUS_ACTIVE]);
        $amount            = null;

        if (null !== $autobidSetting) {
            $amount = $autobidSetting->getAmount();
        }

        return $amount;
    }

    /**
     * @param Clients $client
     *
     * @return int|null
     * @throws \Exception
     */
    public function getMaxAmountPossible(Clients $client): ?int
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
     * @param Clients    $client
     * @param array|null $projectRates optional, for optimize the performance.
     *
     * @return array
     * @throws \Exception
     */
    public function getBadAutoBidSettings(Clients $client, ?array $projectRates = null): array
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $autoBidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');

        if ($projectRates == null) {
            /** @var \project_rate_settings $projectRateSettings */
            $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');
            $projectRates        = $projectRateSettings->getSettings();
        }

        $projectMaxRate = [];
        foreach ($projectRates as $rate) {
            $projectMaxRate[$rate['id_period']][$rate['evaluation']] = $rate['rate_max'];
        }

        $autoBidSettings = $autoBidRepository->findBy(['idLender' => $wallet, 'status' => Autobid::STATUS_ACTIVE]);
        $badSettings     = [];
        foreach ($autoBidSettings as $setting) {
            if (false === isset($projectMaxRate[$setting->getIdPeriod()->getIdPeriod()][$setting->getEvaluation()])) {
                continue;
            }
            if (bccomp($setting->getRateMin(), $projectMaxRate[$setting->getIdPeriod()->getIdPeriod()][$setting->getEvaluation()], 1) > 0) {
                $badSettings[] = [
                    'period_min'       => $setting->getIdPeriod()->getMin(),
                    'period_max'       => $setting->getIdPeriod()->getMax(),
                    'evaluation'       => $setting->getEvaluation(),
                    'rate_min_autobid' => $setting->getRateMin(),
                    'rate_max_project' => $projectMaxRate[$setting->getIdPeriod()->getIdPeriod()][$setting->getEvaluation()],
                ];
            }
        }

        return $badSettings;
    }

    /**
     * @param Wallet $lenderWallet
     *
     * @return bool
     */
    public function isFirstAutobidActivation(Wallet $lenderWallet): bool
    {
        $autobidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');

        return null === $autobidRepository->findOneBy(['idLender' => $lenderWallet]);
    }
}
