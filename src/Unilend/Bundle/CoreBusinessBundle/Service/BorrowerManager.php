<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, Projects, Wallet
};
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectRepaymentTaskManager;

class BorrowerManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager, WireTransferOutManager $wireTransferOutManager, ProjectRepaymentTaskManager $projectRepaymentTaskManager)
    {
        $this->entityManager               = $entityManager;
        $this->wireTransferOutManager      = $wireTransferOutManager;
        $this->projectRepaymentTaskManager = $projectRepaymentTaskManager;
    }

    /**
     * @param Projects|Companies $entity
     *
     * @return string
     */
    public function getBorrowerBankTransferLabel($entity)
    {
        if ($entity instanceof Projects) {
            $identity = $entity->getIdProject();
            $siren    = $entity->getIdCompany()->getSiren();
        } elseif ($entity instanceof Companies) {
            $identity = $entity->getIdCompany();
            $siren    = $entity->getSiren();
        } else {
            return '';
        }

        return 'UNILEND' . str_pad($identity, 6, 0, STR_PAD_LEFT) . 'E' . trim($siren);
    }

    /**
     * @param Wallet $wallet
     *
     * @return float
     */
    public function getRestOfFundsToRelease(Wallet $wallet): float
    {
        $balance = $wallet->getAvailableBalance();
        $balance = round(bcsub($balance, $this->wireTransferOutManager->getCommittedAmount($wallet), 4), 2);

        $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $wallet->getIdClient()]);
        if ($company) {
            $balance = round(bcsub($balance, $this->projectRepaymentTaskManager->getPlannedRepaymentTaskAmountByCompany($company), 4), 2);
        }
        return $balance;
    }
}
