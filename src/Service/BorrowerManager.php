<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{Companies, Projects, Wallet};
use Unilend\Service\Repayment\ProjectRepaymentTaskManager;

class BorrowerManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var WireTransferOutManager */
    private $wireTransferOutManager;
    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;

    /**
     * @param EntityManagerInterface      $entityManager
     * @param WireTransferOutManager      $wireTransferOutManager
     * @param ProjectRepaymentTaskManager $projectRepaymentTaskManager
     */
    public function __construct(EntityManagerInterface $entityManager, WireTransferOutManager $wireTransferOutManager, ProjectRepaymentTaskManager $projectRepaymentTaskManager)
    {
        $this->entityManager               = $entityManager;
        $this->wireTransferOutManager      = $wireTransferOutManager;
        $this->projectRepaymentTaskManager = $projectRepaymentTaskManager;
    }

    /**
     * @param Companies $company
     *
     * @return string
     */
    public function getCompanyBankTransferLabel(Companies $company): string
    {
        return 'UNILEND' . str_pad($company->getIdCompany(), 6, 0, STR_PAD_LEFT) . 'E' . trim($company->getSiren());
    }

    /**
     * @param Projects $project
     *
     * @return string
     */
    public function getProjectBankTransferLabel(Projects $project): string
    {
        return 'UNILEND' . str_pad($project->getIdProject(), 6, 0, STR_PAD_LEFT) . 'E' . trim($project->getIdCompany()->getSiren());
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

        $company = $this->entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $wallet->getIdClient()]);
        if ($company) {
            $balance = round(bcsub($balance, $this->projectRepaymentTaskManager->getPlannedRepaymentTaskAmountByCompany($company), 4), 2);
        }

        return max($balance, 0);
    }
}
