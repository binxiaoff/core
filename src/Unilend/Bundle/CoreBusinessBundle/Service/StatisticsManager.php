<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class StatisticsManager
{
    /** @var  EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function getNumberOfLenders()
    {
        $iNumberLenders = '';

        //TODO check if value is in cache, of not call calculate function
        //cache duration 24h

        return $this->calculateSizeOfLenderCommunity();
    }

    public function getNumberOfProjects()
    {
        $iNumberProjects = '';

        //TODO check if value is in cache, of not call calculate function
        //cache duration 24h

        return $this->calculateNumberOfProjects();
    }

    public function getAmountBorrowed()
    {
        $iAmountBorrowed = '';

        //TODO check if value is in cache, of not call calculate function
        //ideally no cache at all

        return $this->calculateBorrowedAmount();

    }

    private function calculateNumberOfActiveLenders()
    {
        /** @var \lenders_accounts $lenders */
        $lenders                = $this->entityManager->getRepository('lenders_accounts');
        $iNumberOfActiveLenders = $lenders->countLenders(true);

        return $iNumberOfActiveLenders;
    }

    private function calculateSizeOfLenderCommunity()
    {
        /** @var \lenders_accounts $lenders */
        $lenders                   = $this->entityManager->getRepository('lenders_accounts');
        $iNumberLendersInCommunity = $lenders->countLenders();

        return $iNumberLendersInCommunity;
    }

    private function calculateNumberOfProjects()
    {
        /** @var \projects $projects */
        $projects          = $this->entityManager->getRepository('projects');
        $iNumberOfProjects = $projects->countSelectProjectsByStatus(implode(',', \projects_status::$afterRepayment));

        return $iNumberOfProjects;
    }

    private function calculateBorrowedAmount()
    {
        /** @var \transactions $transactions */
        $transactions    = $this->entityManager->getRepository('transactions');
        $iBorrowedAmount = bcdiv($transactions->sum('type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, 'montant_unilend-montant'), 100);

        return $iBorrowedAmount;
    }

    public function getLastUnilendIRR()
    {
        /** @var \unilend_stats $unilendStats */
        $unilendStats = $this->entityManager->getRepository('unilend_stats');
        $aUnilendStats = $unilendStats->select('type_stat = "IRR"', 'added DESC', null, '1');
        return array_shift($aUnilendStats);
    }

    public function getAmountSuccessfullyFinancedProjects()
    {
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
        $aCountByStatus = $projectStatusHistory->countProjectsHavingHadStatus(array(\projects_status::EN_FUNDING, \projects_status::FUNDE));

        $sPercentageSuccessfullyFunded = bcmul(bcdiv($aCountByStatus[\projects_status::FUNDE], $aCountByStatus[\projects_status::EN_FUNDING], 4), 100);
        return $sPercentageSuccessfullyFunded;
    }

    public function getAverageFundingTime()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        return $projects->getAverageFundingTime();
    }
}
