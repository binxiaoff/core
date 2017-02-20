<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Simulator;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRuleDetail;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class VigilanceRuleManager
{
    /**
     * @var EntityManagerSimulator
     */
    private $entityManager;
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * VigilanceRuleManager constructor.
     * @param EntityManagerSimulator $entityManager
     * @param EntityManager          $em
     */
    public function __construct(EntityManagerSimulator $entityManager, EntityManager $em)
    {
        $this->entityManager = $entityManager;
        $this->em            = $em;
    }

    /**
     * @param VigilanceRule $VigilanceRule
     * @return array
     */
    public function getVigilanceRuleConditions(VigilanceRule $VigilanceRule)
    {
        $clientRepository    = $this->em->getRepository('UnilendCoreBusinessBundle:Clients');
        $operationRepository = $this->em->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletRepository    = $this->em->getRepository('UnilendCoreBusinessBundle:Wallet');
        $bankAccountRepository = $this->em->getRepository('UnilendCoreBusinessBundle:BankAccount');

        switch ($VigilanceRule->getLabel()) {
            case 'max_client_age':
                $criteria = [
                    'naissance' => new \DateTime('80 years ago')
                ];
                $operator = [
                    'naissance' => Comparison::LTE
                ];
                $client   = $clientRepository->getClientsBy($criteria, $operator);
                break;
            case 'max_unitary_deposit_amount':

                $criteria  = [
                    'id_type' => $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION]),
                    'amount'  => 7500,
                    'added'   => new \DateTime('1 day ago')
                ];
                $operator  = [
                    'id_type' => Comparison::EQ,
                    'amount'  => Comparison::GTE,
                    'added'   => Comparison::GTE
                ];
                $operation = $operationRepository->getOperationsBy($criteria, $operator);
                break;

            case 'max_sum_deposit_amount_1_w':
                $criteria     = [
                    'id_type' => $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION]),
                    'amount'  => 16000,
                    'added'   => new \DateTime('1 week ago')
                ];
                $operator     = [
                    'id_type' => Comparison::EQ,
                    'amount'  => Comparison::GTE,
                    'added'   => Comparison::GTE
                ];
                $operationSum = $operationRepository->getSumOperationsBy($criteria, $operator);
                break;
            case 'max_sum_deposit_amount_4_w':
                $criteria = [
                    'id_type' => $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION]),
                    'amount'  => 32000,
                    'added'   => new \DateTime('4 weeks ago')
                ];
                $operator = [
                    'id_type' => Comparison::EQ,
                    'amount'  => Comparison::GTE,
                    'added'   => Comparison::GTE
                ];
                $operationSum = $operationRepository->getSumOperationsBy($criteria, $operator);
                break;
            case 'max_sold_without_operation_on_period':
                    $innactiveWallet = $walletRepository->getInactiveLenderWalletOnPeriod(new \DateTime('45 days ago'), 5000);
                    break;
            case 'frequent_rib_modification_on_period':
                $clientsWithFrequentRibModification = $clientRepository->getClientsWithMultipleBankAccountsOnPeriod( new \DateTime('1 year ago'));
                break;
            case 'frequent_deposit_fails':
                return [
                    'entity'     => 'BackPayline',
                    'conditions' => [
                        'code'  => '0000',
                        'added' => new \DateTime('1 day ago')
                    ],
                    'operators'  => [
                        'code'  => Comparison::NEQ,
                        'added' => Comparison::GTE
                    ]
                ];
            case 'legal_entity_max_sum_deposit_amount':
                return [
                    'entity'     => 'Operation',
                    'conditions' => [
                        'id_type' => $this->em->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION]),
                        'amount'  => 15000,
                    ]
                ];
            case 'fiscal_country_risk':
                return [
                    'entity'     => 'ClientsAdresses',
                    'conditions' => [
                        'id_pays_fiscal' => '()'
                    ]
                ];
        }
    }
}
