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
        return [
            'numberProjects'                 => 260,
            'numberProjectRequest'           => 1587952,
            'numberLenders'                  => 32930,
            'amountBorrowedInMillions'       => 19,
            'amountBorrowed'                 => 19516150,
            'irrUnilend'                     => 4.73,
            'avgFundingTime'                 => [
                'days'     => 11,
                'hours'    => 22,
                'minutes'  => 40,
                'unixtime' => 2864000,
            ],
            'percentageSuccessFullyFunded'   => 96,
            'numberActiveLenders'            => 10271,
            'lendersByType'                  => [
                'person'      => [
                    'count'      => 31393,
                    'percentage' => 95
                ],
                'legalEntity' => [
                    'count'      => 624,
                    'percentage' => 2
                ],
                'active'      => 10271,
                'community'   => 32930
            ],
            'lenderCountByRegion'            => [
                0  => [
                    'region'     => 'Auvergne-Rhone-Alpes',
                    'count'      => 2857,
                    'percentage' => 10
                ],
                1  => [
                    'region'     => 'Bourgogne-Franche-Comté',
                    'count'      => 2709,
                    'percentage' => 10
                ],
                2  => [
                    'region'     => 'Bretagne',
                    'count'      => 1896,
                    'percentage' => 7
                ],
                3  => [
                    'region'     => 'Centre',
                    'count'      => 2522,
                    'percentage' => 9
                ],
                4  => [
                    'region'     => 'Champane-Ardenne-Lorraine-Alsace',
                    'count'      => 3195,
                    'percentage' => 11
                ],
                5  => [
                    'region'     => 'Corse',
                    'count'      => 471,
                    'percentage' => 2
                ],
                6  => [
                    'region'     => 'Ile-de-France',
                    'count'      => 430,
                    'percentage' => 2
                ],
                7  => [
                    'region'     => 'Midi-Pyrénées-Languedoc-Roussillon',
                    'count'      => 4632,
                    'percentage' => 17
                ],
                8  => [
                    'region'     => 'Nord-Pas-de-Calais-Picardie',
                    'count'      => 1043,
                    'percentage' => 4
                ],
                9  => [
                    'region'     => 'Normandie',
                    'count'      => 1709,
                    'percentage' => 6
                ],
                10 => [
                    'region'     => 'not-in-France',
                    'count'      => 197,
                    'percentage' => 1
                ],
                11 => [
                    'region'     => 'Pays-de-la-Loire',
                    'count'      => 1200,
                    'percentage' => 4
                ],
                12 => [
                    'region'     => 'Poitou-Charentes-Limousin-Aquitaine',
                    'count'      => 4456,
                    'percentage' => 16
                ],
                13 => [
                    'region'     => 'Provence-Alpes-Côte-dAzur',
                    'count'      => 718,
                    'percentage' => 3
                ]
            ],
            'averageNumberLendersPerProject' => 449,
            'borrowerCountByRegion'          => [
                0 => [
                    'region'     => 'Auvergne-Rhone-Alpes',
                    'count'      => 3,
                    'percentage' => 23
                ],
                1 => [
                    'region'     => 'Bourgogne-Franche-Comté',
                    'count'      => 3,
                    'percentage' => 23
                ],
                2 => [
                    'region'     => 'Centre',
                    'count'      => 1,
                    'percentage' => 8
                ],
                3 => [
                    'region'     => 'Ile-de-France',
                    'count'      => 1,
                    'percentage' => 8
                ],
                4 => [
                    'region'     => 'Midi-Pyrénées-Languedoc-Roussillon',
                    'count'      => 2,
                    'percentage' => 15
                ],
                5 => [
                    'region'     => 'not-in-France',
                    'count'      => 1,
                    'percentage' => 8
                ],
                6 => [
                    'region'     => 'Poitou-Charentes-Limousin-Aquitaine',
                    'count'      => 1,
                    'percentage' => 8
                ],
                7 => [
                    'region'     => 'Provence-Alpes-Côte-dAzur',
                    'count'      => 1,
                    'percentage' => 8
                ]
            ]
        ];

        // @todo hidden because of performance issue
        $borrowedAmount = $this->getAmountBorrowed();
        $aStatistics    = [
            'numberProjects'                 => $this->getNumberOfFinancedProjects(),
            'numberProjectRequest'           => 1587952,
            'numberLenders'                  => $this->getNumberOfLenders(),
            //TODO replace in code with lendersByType
            'amountBorrowedInMillions'       => bcdiv($borrowedAmount, 1000000),
            'amountBorrowed'                 => $borrowedAmount,
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

    public function getNumberOfFinancedProjects()
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
