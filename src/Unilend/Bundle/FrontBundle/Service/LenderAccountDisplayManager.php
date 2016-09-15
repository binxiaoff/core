<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class LenderAccountDisplayManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var  LocationManager */
    private $locationManager;
    /** @var  TranslationManager */
    private $translationManager;
    /** @var MemcacheCachePool */
    private $cachePool;

    public function __construct(EntityManager $entityManager, LocationManager $locationManager, TranslationManager $translationManager, MemcacheCachePool $cachePool)
    {
        $this->entityManager      = $entityManager;
        $this->locationManager    = $locationManager;
        $this->translationManager = $translationManager;
        $this->cachePool          = $cachePool;
    }

    public function getActivityForProject(\lenders_accounts $lenderAccount, $projectId, $projectStatus)
    {
        $lenderActivity = [
            'bids' => $this->getBidsForProject($projectId, $lenderAccount)
        ];

        if ($projectStatus >= \projects_status::FUNDE) {
            $lenderActivity['loans'] = $this->getLoansForProject($projectId, $lenderAccount);
        }

        return $lenderActivity;
    }

    public function getBidsForProject($projectId, \lenders_accounts $lenderAccount)
    {
        /** @var \bids $bids */
        $bids       = $this->entityManager->getRepository('bids');
        $lenderBids = $bids->select('id_lender_account = ' . $lenderAccount->id_lender_account . ' AND id_project = ' . $projectId);

        return [
            'inprogress' => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_PENDING;
            }),
            'rejected'   => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_REJECTED;
            }),
            'accepted'   => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_ACCEPTED;
            }),
            'autobid'    => [
                'inprogress' => array_filter($lenderBids, function ($bid) {
                    return $bid['id_autobid'] > 0 && $bid['status'] == \bids::STATUS_BID_PENDING;
                }),
                'rejected'   => array_filter($lenderBids, function ($bid) {
                    return $bid['id_autobid'] > 0 && $bid['status'] == \bids::STATUS_BID_REJECTED;
                }),
                'accepted'   => array_filter($lenderBids, function ($bid) {
                    return $bid['id_autobid'] > 0 && $bid['status'] == \bids::STATUS_BID_ACCEPTED;
                }),
                'count'      => count(array_filter($lenderBids, function ($bid) {
                    return $bid['id_autobid'] > 0;
                }))
            ],
            'count'      => count($lenderBids)
        ];
    }

    public function getLoansForProject($projectId, \lenders_accounts $lenderAccount)
    {
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->entityManager->getRepository('echeanciers');

        $loanInfo                          = [];
        $loanInfo['amountAlreadyPaidBack'] = $repaymentSchedule->getRepaidAmount(['id_lender' => $lenderAccount->id_lender_account, 'id_project' => $projectId]);
        $loanInfo['remainingToBeRepaid']   = $repaymentSchedule->getOwedAmount(['id_lender' => $lenderAccount->id_lender_account, 'id_project' => $projectId]);
        $loanInfo['remainingMonths']       = $repaymentSchedule->counterPeriodRestantes($lenderAccount->id_lender_account, $projectId);
        $loanInfo['myLoanOnProject']       = $loans->getBidsValid($projectId, $lenderAccount->id_lender_account);
        $loanInfo['myAverageLoanRate']     = round($loans->getAvgLoansPreteur($projectId, $lenderAccount->id_lender_account), 2);
        $loanInfo['percentageRecovered']   = $loanInfo['myLoanOnProject']['solde'] > 0 ? $loanInfo['amountAlreadyPaidBack'] / $loanInfo['myLoanOnProject']['solde'] * 100 : 0;

        return $loanInfo;
    }

    /**
     * get a ready to use formatted array of lender loans allocation by company sector
     * @param $lenderId
     * @return array
     */
    public function getLenderLoansAllocationByCompanySector($lenderId)
    {
        $translations   = $this->translationManager->getTranslatedCompanySectorList();
        $dataForTreeMap = [];
        $data           = $this->getLoansAllocationByCompanySector($lenderId);

        foreach ($data as $category => $row) {
            $dataForTreeMap[] = [
                'name'                => $translations[$category],
                'projectsCount'       => (int) $row['count'],
                'averageRate'         => round($row['average_rate'], 2),
                'loanedAmount'        => round($row['loaned_amount'], 2),
                'loanSharePercentage' => round($row['loaned_amount'] * 100 / array_sum(array_column($data, 'loaned_amount')), 2),
                'svgIconId'           => '#category-sm-' . $category
            ];
        }

        return $dataForTreeMap;
    }

    /**
     * get a ready to use formatted array of lender loans allocation by company region
     * @param $lenderId
     * @return array
     */
    public function getLenderLoansAllocationByRegion($lenderId)
    {
        $regions        = $this->locationManager->getFrenchRegions();
        $dataForTreeMap = [];
        $data           = $this->getLoansAllocationByCompanyRegion($lenderId);

        foreach ($data as $row) {
            if (0 == $row['insee_region_code']) {
                continue;
            }
            $dataForTreeMap[] = [
                'name'                => $regions[$row['insee_region_code']],
                'projectsCount'       => (int) $row['count'],
                'averageRate'         => round($row['average_rate'], 2),
                'loanedAmount'        => round($row['loaned_amount'], 2),
                'loanSharePercentage' => round($row['loaned_amount'] * 100 / array_sum(array_column($data, 'loaned_amount')), 2),
            ];
        }

        return $dataForTreeMap;
    }

    /**
     * get lender loans allocation by company region
     * @param int $lenderId
     * @return array
     */
    private function getLoansAllocationByCompanyRegion($lenderId)
    {
        $cachedItem = $this->cachePool->getItem(__FUNCTION__ . $lenderId);

        if (false === $cachedItem->isHit()) {
            /** @var \lenders_accounts $lendersAccounts */
            $lendersAccounts = $this->entityManager->getRepository('lenders_accounts');
            try {
                $projectsCountByRegion = $lendersAccounts->countProjectsForLenderByRegion($lenderId);
                $cachedItem->set($projectsCountByRegion)->expiresAfter(86400);
                $this->cachePool->save($cachedItem);
            } catch (\Exception $exception) {
                return [];
            }
            return $projectsCountByRegion;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * get lender loans allocation by company sector
     * @param int $lenderId
     * @return array
     */
    private function getLoansAllocationByCompanySector($lenderId)
    {
        $cachedItem = $this->cachePool->getItem(__FUNCTION__ . $lenderId);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManager->getRepository('projects');
            try {
                $projectsCountByCategory = $projects->getLoanDetailsAllocation($lenderId);
                $cachedItem->set($projectsCountByCategory)->expiresAfter(86400);
                $this->cachePool->save($cachedItem);
            } catch (\Exception $exception) {
                return [];
            }
            return $projectsCountByCategory;
        } else {
            return $cachedItem->get();
        }
    }

}
