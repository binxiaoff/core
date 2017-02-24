<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientAtypicalOperation;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;

class VigilanceRuleManager
{
    const VIGILANCE_UNITARY_DEPOSIT_AMOUNT                 = 7500;
    const VIGILANCE_SUBSCRIPTION_AGE                       = 80;
    const VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_1_W          = 16000;
    const VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_4_W          = 32000;
    const VIGILANCE_INACTIVE_WALLET_AMOUNT                 = 5000;
    const VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_LEGAL_ENTITY = 15000;
    const VIGILANCE_RIB_CHANGE_FREQUENCY                   = 2;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ClientVigilanceStatusManager
     */
    private $clientVigilanceStatusManager;
    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * VigilanceRuleManager constructor.
     * @param EntityManager                $em
     * @param ClientVigilanceStatusManager $clientVigilanceStatusManager
     * @param LoggerInterface              $logger
     */
    public function __construct(EntityManager $em, ClientVigilanceStatusManager $clientVigilanceStatusManager, LoggerInterface $logger)
    {
        $this->em                           = $em;
        $this->clientVigilanceStatusManager = $clientVigilanceStatusManager;
        $this->logger                       = $logger;
    }

    /**
     * @param VigilanceRule $vigilanceRule
     * @return array
     */
    public function getVigilanceRuleConditions(VigilanceRule $vigilanceRule)
    {
        $clientRepository      = $this->em->getRepository('UnilendCoreBusinessBundle:Clients');
        $walletRepository      = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet');
        $backPaylineRepository = $this->em->getRepository('UnilendCoreBusinessBundle:Backpayline');
        $companyRepository     = $this->em->getRepository('UnilendCoreBusinessBundle:Companies');

        switch ($vigilanceRule->getLabel()) {
            case 'max_client_age':
                $criteria = [
                    'naissance' => '(added - INTERVAL ' . self::VIGILANCE_SUBSCRIPTION_AGE . ' YEAR)'
                ];
                $operator = [
                    'naissance' => Comparison::LTE
                ];
                $this->processMaxAgeDetection($clientRepository->getClientsBy($criteria, $operator), $vigilanceRule);
                break;
            case 'max_unitary_deposit_amount':
                $this->processDepositDetection($clientRepository->getClientsByDepositAmountAndDate(new \DateTime('1 day ago'), self::VIGILANCE_UNITARY_DEPOSIT_AMOUNT), $vigilanceRule);
                break;
            case 'max_sum_deposit_amount_1_w':
                $this->processDepositDetection($clientRepository->getClientsByDepositAmountAndDate(new \DateTime('1 week ago'), self::VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_1_W, true), $vigilanceRule);
                break;
            case 'max_sum_deposit_amount_4_w':
                $this->processDepositDetection($clientRepository->getClientsByDepositAmountAndDate(new \DateTime('4 weeks ago'), self::VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_4_W, true), $vigilanceRule);
                break;
            case 'max_sold_without_operation_on_period':
                $this->processInactiveWalletDetection($walletRepository->getInactiveLenderWalletOnPeriod(new \DateTime('45 days ago'), self::VIGILANCE_INACTIVE_WALLET_AMOUNT), $vigilanceRule);
                break;
            case 'frequent_rib_modification_on_period':
                $clientRepository->getClientsWithMultipleBankAccountsOnPeriod(new \DateTime('1 year ago'), self::VIGILANCE_RIB_CHANGE_FREQUENCY);
                break;
            case 'frequent_deposit_fails':
                $backPaylineRepository->getTransactionsToVerify(new \DateTime('1 day ago'));
                break;
            case 'legal_entity_max_sum_deposit_amount':
                $companyRepository->getLegalEntitiesByCumulativeDepositAmount(self::VIGILANCE_CUMULATIVE_DEPOSIT_AMOUNT_LEGAL_ENTITY);
                break;
            case 'fiscal_country_risk':
                $clientRepository->getClientsByFiscalCountryStatus(PaysV2::VIGILANCE_STATUS_MEDIUM_RISK);
                break;
            case 'fiscal_country_high_risk':
                $clientRepository->getClientsByFiscalCountryStatus(PaysV2::VIGILANCE_STATUS_HIGH_RISK);
                break;
        }
    }

    /**
     * @param VigilanceRule $vigilanceRule
     * @param Clients       $client
     * @param null|string   $atypicalValue
     * @param null|string   $operationLog
     * @return ClientAtypicalOperation
     */
    private function addClientAtypicalOperation(VigilanceRule $vigilanceRule, Clients $client, $atypicalValue = null, $operationLog = null)
    {
        $atypicalOperation = new ClientAtypicalOperation();
        $atypicalOperation->setClient($client)
            ->setRule($vigilanceRule)
            ->setStatus(ClientAtypicalOperation::STATUS_PENDING)
            ->setAtypicalValue($atypicalValue)
            ->setOperationLog($operationLog)
            ->setIdUser(Users::USER_ID_CRON);
        $this->em->persist($atypicalOperation);
        $this->em->flush();

        return $atypicalOperation;
    }

    /**
     * @param array         $clientOperations
     * @param VigilanceRule $vigilanceRule
     */
    private function processDepositDetection(array $clientOperations, VigilanceRule $vigilanceRule)
    {
        foreach ($clientOperations as $operation) {
            try {
                $client            = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->find($operation['idClient']);
                $atypicalOperation = $this->addClientAtypicalOperation($vigilanceRule, $client, $operation['depositAmount'], $operation['operation']);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($client, $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation, 'Le client a déposé une somme de ' . number_format($operation['depositAmount'], 2, ',', ' ') . ' €');
            } catch (\Exception $exception) {
                $this->logger->error('Could not process the detected operation: ' . $vigilanceRule->getName() . ' - id_client = ' . $operation['idClient'] .
                    ' - Error: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $operation['idClient']]);
            }
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
                $atypicalOperation = $this->addClientAtypicalOperation($vigilanceRule, $client, $client->getNaissance()->diff($client->getAdded())->y);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($client, $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation, 'Le client avait ' . $client->getNaissance()->diff($client->getAdded())->y . ' ans à son inscription');
            } catch (\Exception $exception) {
                $this->logger->error('Could not process the detection: ' . $vigilanceRule->getName() . ' - id_client = ' . $client->getIdClient() .
                    ' - Error: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]);
            }
        }
    }

    /**
     * @param array         $inactiveWallets
     * @param VigilanceRule $vigilanceRule
     */
    private function processInactiveWalletDetection(array $inactiveWallets, VigilanceRule $vigilanceRule)
    {
        foreach ($inactiveWallets as $wallet) {
            try {
                $client            = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->find($wallet['id_client']);
                $atypicalOperation = $this->addClientAtypicalOperation($vigilanceRule, $client, $wallet['available_balance']);
                $this->clientVigilanceStatusManager->upgradeClientVigilanceStatusHistory($client, $vigilanceRule->getVigilanceStatus(), Users::USER_ID_CRON, $atypicalOperation, 'Le client a un solde inactif d\'un montant de ' . number_format($wallet['available_balance'], 2, ',', ' ') . ' €');
            } catch (\Exception $exception) {
                $this->logger->error('Could not process the detection: ' . $vigilanceRule->getName() . ' - id_client = ' . $wallet['id_client'] .
                    ' - Error: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $wallet['id_client']]);
            }
        }
    }
}
