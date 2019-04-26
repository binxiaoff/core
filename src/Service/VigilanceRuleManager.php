<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{Backpayline, Bids, ClientAtypicalOperation, Clients, ClientVigilanceStatusHistory, Companies, Operation, OperationType, Pays, Users, VigilanceRule, Wallet};

class VigilanceRuleManager
{
    const VIGILANCE_UNITARY_DEPOSIT_AMOUNT                 = 7500;
    const VIGILANCE_SUBSCRIPTION_AGE                       = 80;
    const VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_1_W          = 16000;
    const VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_4_W          = 32000;
    const VIGILANCE_INACTIVE_WALLET_AMOUNT                 = 5000;
    const VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_LEGAL_ENTITY = 15000;
    const VIGILANCE_RIB_CHANGE_FREQUENCY                   = 3;
    const VIGILANCE_DEPOSIT_FOLLOWED_BY_WITHDRAW_AMOUNT    = 5000;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ClientVigilanceStatusManager
     */
    private $clientVigilanceStatusManager;
    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * @param EntityManagerInterface       $entityManager
     * @param ClientVigilanceStatusManager $clientVigilanceStatusManager
     * @param LoggerInterface              $logger
     */
    public function __construct(EntityManagerInterface $entityManager, ClientVigilanceStatusManager $clientVigilanceStatusManager, LoggerInterface $logger)
    {
        $this->entityManager                = $entityManager;
        $this->clientVigilanceStatusManager = $clientVigilanceStatusManager;
        $this->logger                       = $logger;
    }

    /**
     * @param VigilanceRule $vigilanceRule
     */
    public function checkRule(VigilanceRule $vigilanceRule)
    {
        $clientRepository = $this->entityManager->getRepository(Clients::class);

        switch ($vigilanceRule->getLabel()) {
            case 'max_client_age':
                $this->processMaxAgeDetection($clientRepository->getClientByAgeAndSubscriptionDate(new \DateTime(self::VIGILANCE_SUBSCRIPTION_AGE . ' years ago'), new \DateTime('yesterday midnight')), $vigilanceRule);
                break;
            case 'max_unitary_deposit_amount':
                $this->processDepositDetection($clientRepository->getClientsByDepositAmountAndDate((new \DateTime('yesterday midnight')), self::VIGILANCE_UNITARY_DEPOSIT_AMOUNT), $vigilanceRule);
                break;
            case 'max_sum_deposit_amount_1_w':
                $this->processDepositDetection($clientRepository->getClientsByDepositAmountAndDate(new \DateTime('1 week ago 00:00:00'), self::VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_1_W, true), $vigilanceRule, true);
                break;
            case 'max_sum_deposit_amount_4_w':
                $this->processDepositDetection($clientRepository->getClientsByDepositAmountAndDate(new \DateTime('4 weeks ago 00:00:00'), self::VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_4_W, true), $vigilanceRule, true);
                break;
            case 'max_sold_without_operation_on_period':
                $walletRepository = $this->entityManager->getRepository(Wallet::class);
                $this->processInactiveWalletDetection($walletRepository->getInactiveLenderWalletOnPeriod(new \DateTime('90 days ago 00:00:00'), self::VIGILANCE_INACTIVE_WALLET_AMOUNT), $vigilanceRule);
                break;
            case 'max_deposit_withdraw_without_operation':
                $operationRepository = $this->entityManager->getRepository(Operation::class);
                $this->processDepositFollowedByWithdrawDetection($operationRepository->getOperationByTypeAndAmount(OperationType::LENDER_PROVISION, self::VIGILANCE_DEPOSIT_FOLLOWED_BY_WITHDRAW_AMOUNT), $vigilanceRule);
                break;
            case 'frequent_rib_modification_on_period':
                $this->processRibModificationDetection($clientRepository->getClientsWithMultipleBankAccountsOnPeriod(new \DateTime('1 year ago 00:00:00'), self::VIGILANCE_RIB_CHANGE_FREQUENCY), $vigilanceRule);
                break;
            case 'frequent_deposit_fails':
                $backPaylineRepository = $this->entityManager->getRepository(Backpayline::class);
                $this->processDepositFailsDetection($backPaylineRepository->getTransactionsToVerify(new \DateTime('yesterday midnight')), $vigilanceRule);
                break;
            case 'legal_entity_max_sum_deposit_amount':
                $companyRepository = $this->entityManager->getRepository(Companies::class);
                $this->processDepositDetection($companyRepository->getLegalEntitiesByCumulativeDepositAmount(self::VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_LEGAL_ENTITY), $vigilanceRule, true);
                break;
            case 'fiscal_country_risk':
                $this->processClientWithFiscalCountryRisk($clientRepository->getClientsByFiscalCountryStatus(Pays::VIGILANCE_STATUS_MEDIUM_RISK, new \DateTime('yesterday midnight')), $vigilanceRule);
                break;
            case 'fiscal_country_high_risk':
                $this->processClientWithFiscalCountryRisk($clientRepository->getClientsByFiscalCountryStatus(Pays::VIGILANCE_STATUS_HIGH_RISK, new \DateTime('yesterday midnight')), $vigilanceRule);
                break;
        }
    }

    /**
     * @param Clients[]     $clients
     * @param VigilanceRule $vigilanceRule
     */
    private function processMaxAgeDetection(array $clients, VigilanceRule $vigilanceRule)
    {
        foreach ($clients as $client) {
            try {
                $comment           = 'Le client avait ' . $client->getDateOfBirth()->diff($client->getAdded())->y . ' ans à son inscription';
                $atypicalOperation = $this->clientVigilanceStatusManager->addClientAtypicalOperation($vigilanceRule, $client, $client->getDateOfBirth()->diff($client->getAdded())->y, null, $comment);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($client, $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation, $comment);
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not process the detection: ' . $vigilanceRule->getLabel() . ' - id_client = ' . $client->getIdClient() . ' - Error: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
    }

    /**
     * @param array         $clientOperations
     * @param boolean       $checkPendingDuplicate
     * @param VigilanceRule $vigilanceRule
     */
    private function processDepositDetection(array $clientOperations, VigilanceRule $vigilanceRule, $checkPendingDuplicate = false)
    {
        foreach ($clientOperations as $operation) {
            try {
                $client            = $this->entityManager->getRepository(Clients::class)->find($operation['idClient']);
                $comment           = 'Le client a déposé une somme de ' . number_format($operation['depositAmount'], 2, ',', ' ') . ' €';
                $atypicalOperation = $this->clientVigilanceStatusManager->addClientAtypicalOperation($vigilanceRule, $client, $operation['depositAmount'], $operation['operation'], $comment, $checkPendingDuplicate);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($client, $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation, $comment);
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not process the detected operation: ' . $vigilanceRule->getLabel() . ' - id_client = ' . $operation['idClient'] . ' - Error: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $operation['idClient'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
    }

    /**
     * @param Operation[]   $depositOperations
     * @param VigilanceRule $vigilanceRule
     */
    private function processDepositFollowedByWithdrawDetection(array $depositOperations, VigilanceRule $vigilanceRule)
    {
        $operationRepository = $this->entityManager->getRepository(Operation::class);
        $bidRepository       = $this->entityManager->getRepository(Bids::class);
        $walletRepository    = $this->entityManager->getRepository(Wallet::class);

        foreach ($depositOperations as $depositOperation) {
            $wallet             = $walletRepository->findOneBy(['id' => $depositOperation->getWalletCreditor()->getId()]);
            $withdrawOperations = $operationRepository->getWithdrawOperationByWallet($depositOperation->getWalletCreditor(), self::VIGILANCE_DEPOSIT_FOLLOWED_BY_WITHDRAW_AMOUNT, $depositOperation->getAdded());

            foreach ($withdrawOperations as $withdrawOperation) {
                try {
                    if (0 === $bidRepository->countByClientInPeriod($depositOperation->getAdded(), $withdrawOperation->getAdded(), $wallet->getIdClient())) {
                        $comment           = 'le client a déposé ' . number_format($depositOperation->getAmount(), 2, ',', ' ') . ' € suivi d\'un retrait de ' . number_format($withdrawOperation->getAmount(), 2, ',', ' ') . ' € sans bid entre ' .
                            'le ' . $depositOperation->getAdded()->format('d/m/y H:i') . ' et le ' . $withdrawOperation->getAdded()->format('d/m/y H:i');
                        $atypicalOperation = $this->clientVigilanceStatusManager->addClientAtypicalOperation(
                            $vigilanceRule,
                            $wallet->getIdClient(),
                            $depositOperation->getAmount() . '€/' . $withdrawOperation->getAmount() . '€',
                            $depositOperation->getId() . ',' . $withdrawOperation->getId(),
                            $comment,
                            true
                        );
                        $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory(
                            $wallet->getIdClient(),
                            $vigilanceRule->getVigilanceStatus(),
                            Users::USER_ID_CRON,
                            $atypicalOperation,
                            $comment
                        );
                    }
                } catch (\Exception $exception) {
                    $this->logger->error(
                        'Could not process the detection: ' . $vigilanceRule->getLabel() . ' - Error: ' . $exception->getMessage(),
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_wallet' => $wallet->getId(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                    );
                }
            }
        }
    }

    /**
     * @param array         $inactiveWallets
     * @param VigilanceRule $vigilanceRule
     */
    private function processInactiveWalletDetection(array $inactiveWallets, VigilanceRule $vigilanceRule)
    {
        $walletRepository                       = $this->entityManager->getRepository(Wallet::class);
        $bidsRepository                         = $this->entityManager->getRepository(Bids::class);
        $operationRepository                    = $this->entityManager->getRepository(Operation::class);
        $clientAtypicalOperationRepository      = $this->entityManager->getRepository(ClientAtypicalOperation::class);
        $clientVigilanceStatusHistoryRepository = $this->entityManager->getRepository(ClientVigilanceStatusHistory::class);

        foreach ($inactiveWallets as $wallet) {
            try {
                $lenderWallet      = $walletRepository->find($wallet['walletId']);
                $atypicalOperation = $clientAtypicalOperationRepository->findOneBy(['client' => $lenderWallet->getIdClient(), 'rule' => $vigilanceRule], ['added' => 'DESC']);

                if (null !== $atypicalOperation) {
                    if (ClientAtypicalOperation::STATUS_TREATED === $atypicalOperation->getDetectionStatus()) {
                        $vigilanceStatusHistory = $clientVigilanceStatusHistoryRepository->findOneBy(['atypicalOperation' => $atypicalOperation], ['added' => 'DESC']);
                        if (null !== $vigilanceStatusHistory && new \DateTime('1 year ago') < $vigilanceStatusHistory->getAdded()) {
                            continue;
                        }
                    }

                    if (
                        0 === $operationRepository->getWithdrawAndProvisionOperationByDateAndWallet($lenderWallet, $atypicalOperation->getAdded())
                        && 0 === $bidsRepository->getManualBidCountByDateAndWallet($lenderWallet, $atypicalOperation->getAdded())
                    ) {
                        continue;
                    }
                }
                $comment           = 'Le client a un solde inactif d\'un montant de ' . number_format($wallet['availableBalance'], 2, ',',
                        ' ') . ' €. Dernière opération le ' . \DateTime::createFromFormat('Y-m-d H:i:s', $wallet['lastOperationDate'])->format('d/m/Y H:i:s');
                $atypicalOperation = $this->clientVigilanceStatusManager->addClientAtypicalOperation($vigilanceRule, $lenderWallet->getIdClient(), $wallet['availableBalance'], null, $comment, true);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($lenderWallet->getIdClient(), $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation,
                    $comment);
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not process the detection: ' . $vigilanceRule->getLabel() . ' - Error: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_wallet' => $wallet['walletId'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
    }

    /**
     * @param array         $clients
     * @param VigilanceRule $vigilanceRule
     */
    private function processRibModificationDetection(array $clients, VigilanceRule $vigilanceRule)
    {
        foreach ($clients as $clientRow) {
            try {
                $client            = $this->entityManager->getRepository(Clients::class)->find($clientRow['idClient']);
                $comment           = 'Le client a modifié son RIB ' . $clientRow['nbRibChange'] . ' fois sur une année';
                $atypicalOperation = $this->clientVigilanceStatusManager->addClientAtypicalOperation($vigilanceRule, $client, $clientRow['nbRibChange'], null, $comment, true);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($client, $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation, $comment);
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not process the detection: ' . $vigilanceRule->getLabel() . ' - id_client = ' . $clientRow['idClient'] . ' - Error: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $clientRow['idClient'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
    }

    private function processDepositFailsDetection($clientDeposit, VigilanceRule $vigilanceRule)
    {
        foreach ($clientDeposit as $deposit) {
            try {
                $client            = $this->entityManager->getRepository(Clients::class)->find($deposit['id_client']);
                $comment           = 'Le client a effectué ' . $deposit['nb_transactions'] . ' alimentations échouées avec ' . $deposit['nb_cards'] . ' CB différentes';
                $atypicalOperation = $this->clientVigilanceStatusManager->addClientAtypicalOperation($vigilanceRule, $client, 'nombre d\'opérations: ' . $deposit['nb_transactions'] . ' | nombre de CB: ' . $deposit['nb_cards'], $deposit['idTransactionList'], $comment);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($client, $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation, $comment);
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not process the detection: ' . $vigilanceRule->getLabel() . ' - id_client = ' . $deposit['id_client'] . ' - Error: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $deposit['id_client'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
    }

    private function processClientWithFiscalCountryRisk(array $clients, VigilanceRule $vigilanceRule)
    {
        foreach ($clients as $clientRow) {
            try {
                $client            = $this->entityManager->getRepository(Clients::class)->find($clientRow['idClient']);
                $comment           = 'Le client a indiqué ' . trim($clientRow['countryLabel']) . ' comme pays fiscal';
                $atypicalOperation = $this->clientVigilanceStatusManager->addClientAtypicalOperation($vigilanceRule, $client, $clientRow['countryLabel'], null, $comment);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($client, $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation, $comment);
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not process the detection: ' . $vigilanceRule->getLabel() . ' - id_client = ' . $clientRow['idClient'] . ' - Error: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $clientRow['idClient'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
    }
}
