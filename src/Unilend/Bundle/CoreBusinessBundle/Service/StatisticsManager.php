<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class StatisticsManager
{
    /** @var  EntityManager */
    private $entityManager;
    /** @var  IRRManager */
    private $IRRManager;
    /** @var MemcacheCachePool */
    private $cachePool;
    /** @var LocationManager */
    private $locationManager;
    /** @var  TranslationManager */
    private $translationManager;

    public function __construct(EntityManager $entityManager, IRRManager $IRRManager,MemcacheCachePool $cachePool, LocationManager $locationManager, TranslationManager $translationManager)
    {
        $this->entityManager      = $entityManager;
        $this->IRRManager         = $IRRManager;
        $this->cachePool          = $cachePool;
        $this->locationManager    = $locationManager;
        $this->translationManager = $translationManager;
    }

    /**
     * @param string $name
     */
    public function getStatistic($name)
    {
        $function  = 'get' . ucfirst($name);
        return call_user_func([$this, $function]);
    }

    public function getNumberOfLendersInCommunity()
    {
        $cachedItem = $this->cachePool->getItem('numberOfLendersInCommunity');

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lenders */
            $lenders = $this->entityManager->getRepository('lenders_accounts');
            /** @var int $numberOfLendersInCommunity */
            $numberOfLendersInCommunity = $lenders->countLenders();
            $cachedItem->set($numberOfLendersInCommunity)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $numberOfLendersInCommunity;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNumberOfActiveLenders()
    {
        $cachedItem = $this->cachePool->getItem('numberOfActiveLenders');

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lenders */
            $lenders = $this->entityManager->getRepository('lenders_accounts');
            /** @var int $numberOfActiveLenders */
            $numberOfActiveLenders = $lenders->countLenders(true);
            $cachedItem->set($numberOfActiveLenders)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $numberOfActiveLenders;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNumberOfFinancedProjects()
    {
        $cachedItem = $this->cachePool->getItem('numberOfFinancedProjects');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $numberOfProjects */
            $numberOfProjects = $projects->countSelectProjectsByStatus(implode(',', \projects_status::$afterRepayment));
            $cachedItem->set($numberOfProjects)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $numberOfProjects;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAmountBorrowed()
    {
        $cachedItem = $this->cachePool->getItem('amountBorrowed');

        if (false === $cachedItem->isHit()) {
            /** @var \transactions $transactions */
            $transactions    = $this->entityManager->getRepository('transactions');
            /** @var int $amountBorrowed */
            $amountBorrowed = bcdiv($transactions->sum('type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, 'montant_unilend-montant'), 100);
            $cachedItem->set($amountBorrowed)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $amountBorrowed;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAmountBorrowedInMillions()
    {
        return bcdiv($this->getAmountBorrowed(), 1000000, 0);
    }




    public function getUnilendIRR()
    {
        $cachedItem = $this->cachePool->getItem('unilendIRR');

        if (false === $cachedItem->isHit()) {
            /** @var array $lastUnilendIRR */
            $lastUnilendIRR = $this->IRRManager->getLastUnilendIRR();
            $cachedItem->set($lastUnilendIRR['value'])->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $lastUnilendIRR['value'];
        } else {
            return $cachedItem->get();
        }
    }

    public function getPercentageSuccessfullyFinancedProjects()
    {
        $cachedItem = $this->cachePool->getItem('percentageSuccessfullyFinancedProjects');

        if (false === $cachedItem->isHit()) {
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
            /** @var array $countByStatus */
            $countByStatus = $projectStatusHistory->countProjectsHavingHadStatus([\projects_status::EN_FUNDING, \projects_status::FUNDE]);
            /** @var string $percentageSuccessfullyFunded */
            $percentageSuccessfullyFunded = bcmul(bcdiv($countByStatus[\projects_status::FUNDE], $countByStatus[\projects_status::EN_FUNDING], 4), 100);
            $cachedItem->set($percentageSuccessfullyFunded)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $percentageSuccessfullyFunded;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageFundingTime()
    {
        $cachedItem = $this->cachePool->getItem('averageFundingTime');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var array $averageFundingTime */
            $averageFundingTime = $projects->getAverageFundingTime();

            $cachedItem->set($averageFundingTime)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $averageFundingTime;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageInterestRateForLenders()
    {
        $cachedItem = $this->cachePool->getItem('averageInterestRateForLenders');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $averageRate */
            $averageRate = $projects->getGlobalAverageRateOfFundedProjects(PHP_INT_MAX);
            $cachedItem->set($averageRate)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $averageRate;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageNumberOfLenders()
    {
        $cachedItem = $this->cachePool->getItem('averageNumberOfLenders');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $averageNumberOfLenders */
            $averageNumberOfLenders = $projects->getAverageNumberOfLendersForProject();
            $cachedItem->set($averageNumberOfLenders)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $averageNumberOfLenders;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageProjectAmount()
    {
        $cachedItem = $this->cachePool->getItem('averageProjectAmount');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            /** @var int $averageProjectAmount */
            $averageProjectAmount = $projects->getAverageAmount();
            $cachedItem->set($averageProjectAmount)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $averageProjectAmount;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageLoanAmount()
    {
        $cachedItem = $this->cachePool->getItem('averageLoanAmount');

        if (false === $cachedItem->isHit()) {
            /** @var \loans $loans */
            $loans = $this->entityManager->getRepository('loans');
            /** @var int $averageLoanAmount */
            $averageLoanAmount = $loans->getAverageLoanAmount();
            $cachedItem->set($averageLoanAmount)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $averageLoanAmount;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNumberOfProjectRequests()
    {
        $cachedItem = $this->cachePool->getItem('numberOfProjectRequests');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');;
            /** @var int $numberOfProjectRequests */
            $numberOfProjectRequests = $projects->getNumberOfUniqueProjectRequests();
            $cachedItem->set($numberOfProjectRequests)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $numberOfProjectRequests;
        } else {
            return $cachedItem->get();
        }
    }

    public function getPercentageOfAcceptedProjects()
    {
        $cachedItem = $this->cachePool->getItem('percentageOfAcceptedProjects');

        if (false === $cachedItem->isHit()) {
            $numberOfRequests = $this->getNumberOfProjectRequests();
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
            /** @var array $countByStatus */
            $countByStatus = $projectStatusHistory->countProjectsHavingHadStatus([\projects_status::EN_FUNDING]);
            /** @var string $percentageOfAcceptedProjects */
            $percentageOfAcceptedProjects = bcdiv($countByStatus[\projects_status::EN_FUNDING], $numberOfRequests, 2);
            $cachedItem->set($percentageOfAcceptedProjects)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $percentageOfAcceptedProjects;
        } else {
            return $cachedItem->get();
        }
    }

    public function getAverageLenderIRR()
    {
        $cachedItem = $this->cachePool->getItem('averageLenderIRR');

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_account_stats $lendersAccountsStats */
            $lendersAccountsStats = $this->entityManager->getRepository('lenders_accounts_stats');
            $averageLenderIRR = $lendersAccountsStats->getAverageIRRofAllLenders();
            $cachedItem->set($averageLenderIRR)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $averageLenderIRR;
        } else {
            return $cachedItem->get();
        }
    }

    public function getLendersByType()
    {
        $cachedItem = $this->cachePool->getItem('lendersByType');

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lenders */
            $lenders = $this->entityManager->getRepository('lenders_accounts');
            /** @var int $lendersPerson */
            $lendersPerson = $lenders->countLendersByClientType([\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]);
            /** @var int $lendersLegalEntity */
            $lendersLegalEntity = $lenders->countLendersByClientType([\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER]);
            /** @var int $totalLenders */
            $totalLenders = $lenders->countLenders();

            $lendersByType = [
                'person' => [
                    'count'      => $lendersPerson,
                    'percentage' => round(bcdiv($lendersPerson , $totalLenders, 4) * 100, 0)
                ],
                'legalEntity' => [
                    'count' => $lendersLegalEntity,
                    'percentage' => round(bcdiv($lendersLegalEntity , $totalLenders, 4) * 100, 0)
                ]
            ];

            $cachedItem->set($lendersByType)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $lendersByType;
        } else {
            return $cachedItem->get();
        }
    }

    public function getLendersByRegion()
    {
        $cachedItem = $this->cachePool->getItem('lendersByRegion');

        if (false === $cachedItem->isHit()) {
            $lendersByRegion = $this->locationManager->getLendersByRegion();
            $cachedItem->set($lendersByRegion)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $lendersByRegion;
        } else {
            return $cachedItem->get();
        }
    }

    public function getBorrowersByRegion()
    {
        $cachedItem = $this->cachePool->getItem('projectsByRegion');

        if (false === $cachedItem->isHit()) {
            $projectsByRegion = $this->locationManager->getProjectsByRegion();
            $cachedItem->set($projectsByRegion)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $projectsByRegion;
        } else {
            return $cachedItem->get();
        }
    }

    public function getTotalRepaidCapital()
    {
        $cachedItem = $this->cachePool->getItem('totalRepaidCapital');

        if (false === $cachedItem->isHit()) {
            /** @var \echeanciers $paymentSchedule */
            $paymentSchedule = $this->entityManager->getRepository('echeanciers');
            $repaidCapital = $paymentSchedule->getTotalRepaidCapital();
            $cachedItem->set($repaidCapital)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $repaidCapital;
        } else {
            return $cachedItem->get();
        }
    }

    public function getTotalRepaidInterests()
    {
        $cachedItem = $this->cachePool->getItem('totalRepaidInterests');

        if (false === $cachedItem->isHit()) {
            /** @var \echeanciers $paymentSchedule */
            $paymentSchedule = $this->entityManager->getRepository('echeanciers');
            $repaidInterests = $paymentSchedule->getTotalRepaidInterests();
            $cachedItem->set($repaidInterests)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $repaidInterests;
        } else {
            return $cachedItem->get();
        }
    }

    public function getProjectCountByCategory()
    {
        $cachedItem = $this->cachePool->getItem('projectCountByCategory');

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            $projectsCountByCategory = $projects->countProjectsByCategory();
            $cachedItem->set($projectsCountByCategory)->expiresAfter(86400);
            $this->cachePool->save($cachedItem);

            return $projectsCountByCategory;
        } else {
            return $cachedItem->get();
        }
    }

    //TODO @Mesbah : use it for your lender tree map
    /**
     * @param $countByCategory
     * @return array
     */
    public function getDataForCategoryTreeMap($countByCategory)
    {
        $translations = $this->translationManager->getTranslatedCompanySectorList();
        $dataForTreeMap = [];

        foreach ($countByCategory as $category => $count) {
            if (isset($translations[$category])) {
                $dataForTreeMap[] = [
                    'name'      => $translations[$category],
                    'value'     => (int) $count,
                    'svgIconId' => '#category-sm-' . $category
                ];
            }
        }

        return $dataForTreeMap;
    }

    public function getProjectCountForCategoryTreeMap()
    {
        $countByCategory = $this->getProjectCountByCategory();
        return $this->getDataForCategoryTreeMap($countByCategory);
    }

}
