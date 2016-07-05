<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\MapsService;

class StatisticsManager
{
    /** @var  EntityManager */
    private $entityManager;

    /** @var  IRRManager */
    private $IRRManager;
    /** @var  mapsService */
    private $mapsService;

    public function __construct(EntityManager $entityManager, IRRManager $IRRManager, MapsService $mapsService)
    {
        $this->entityManager = $entityManager;
        $this->IRRManager    = $IRRManager;
        $this->mapsService   = $mapsService;
    }

    public function getAllStatistics()
    {
        $aStatistics = [
            'numberProjects'                 => $this->getNumberOfProjects(),
            'numberLenders'                  => $this->getNumberOfLenders(),
            //TODO replace in code with lendersByType
            'amountBorrowedInMillions'       => bcdiv($this->getAmountBorrowed(), 1000000),
            'amountBorrowed'                 => $this->getAmountBorrowed(),
            'irrUnilend'                     => $this->getUnilendIRR(),
            'avgFundingTime'                 => $this->getAverageFundingTime(),
            'percentageSuccessFullyFunded'   => $this->getPercentageSuccessfullyFinancedProjects(),
            'numberActiveLenders'            => $this->getNumberOfActiveLenders(),
            //TODO replace in code with lendersByType
            'lendersByType'                  => $this->getLendersByType(),
            'lenderCountByRegion'            => $this->getLendersByRegion(),
            'averageNumberLendersPerProject' => $this->getAverageNumberOfLenders(),
            'borrowerCountByRegion'          => $this->getBorrowersByRegion()

        ];

        return $aStatistics;
    }

    public function getNumberOfLenders()
    {
        $iNumberLenders = '';

        //TODO check if value is in cache
        //cache duration 24h

        /** @var \lenders_accounts $lenders */
        $lenders                   = $this->entityManager->getRepository('lenders_accounts');
        $iNumberLendersInCommunity = $lenders->countLenders();
        return $iNumberLendersInCommunity;
    }

    public function getNumberOfProjects()
    {
        $iNumberProjects = '';

        //TODO check if value is in cache
        //cache duration 24h
        /** @var \projects $projects */
        $projects          = $this->entityManager->getRepository('projects');
        $iNumberOfProjects = $projects->countSelectProjectsByStatus(implode(',', \projects_status::$afterRepayment));

        return $iNumberOfProjects;
    }

    public function getAmountBorrowed()
    {
        $iAmountBorrowed = '';

        //TODO check if value is in cache
        //ideally no cache at all


        /** @var \transactions $transactions */
        $transactions    = $this->entityManager->getRepository('transactions');
        $iBorrowedAmount = bcdiv($transactions->sum('type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, 'montant_unilend-montant'), 100);
        return $iBorrowedAmount;
    }

    public function getNumberOfActiveLenders()
    {
        /** @var \lenders_accounts $lenders */
        $lenders                = $this->entityManager->getRepository('lenders_accounts');
        $iNumberOfActiveLenders = $lenders->countLenders(true);

        return $iNumberOfActiveLenders;
    }

    public function getUnilendIRR()
    {
        $aLastUnilendIRR = $this->IRRManager->getLastUnilendIRR();
        return $aLastUnilendIRR['value'];
    }

    public function getPercentageSuccessfullyFinancedProjects()
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

    public function getPercentageSelectedProjects()
    {
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
        $aCountByStatus = $projectStatusHistory->countProjectsHavingHadStatus(array(\projects_status::EN_FUNDING, \projects_status::FUNDE));

        $sPercentageSuccessfullyFunded = bcmul(bcdiv($aCountByStatus[\projects_status::FUNDE], $aCountByStatus[\projects_status::EN_FUNDING], 4), 100);
        return $sPercentageSuccessfullyFunded;
    }

    public function getLendersByType()
    {
        /** @var \lenders_accounts $lenders */
        $lenders   = $this->entityManager->getRepository('lenders_accounts');
        $iLendersPerson      = $lenders->countLendersByClientType(array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER));
        $iLendersLegalEntity = $lenders->countLendersByClientType(array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER));
        $iTotalLenders       = $this->getNumberOfLenders();

        $aLendersCountByType = [
            'person' => array(
                'count'      => $iLendersPerson,
                'percentage' => round(bcdiv($iLendersPerson , $iTotalLenders, 4) * 100, 0)
            ),
            'legalEntity' => array(
                'count' => $iLendersLegalEntity,
                'percentage' => round(bcdiv($iLendersLegalEntity , $iTotalLenders, 4) * 100, 0)
            ),
            'active'    => $this->getNumberOfActiveLenders(),
            'community' => $iTotalLenders
        ];

        return $aLendersCountByType;
    }

    public function getLendersByRegion()
    {
        /** @var \clients $clients */
        $clients = $this->entityManager->getRepository('clients');
        return $clients->countClientsByRegion('lender');
    }

    public function getAverageNumberOfLenders()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        return $projects->getAverageNumberOfLendersForProject();
    }

    public function getAverageProjectAmount()
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        return $projects->getAverageAmount();
    }

    public function getAverageLoanAmount()
    {
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');

    }

    public function getBorrowersByRegion()
    {
        /** @var \clients $clients */
        $clients = $this->entityManager->getRepository('clients');
        return $clients->countClientsByRegion('borrower');
        //TODO compare to array in mapsService and array_fill missing regions
    }
}
