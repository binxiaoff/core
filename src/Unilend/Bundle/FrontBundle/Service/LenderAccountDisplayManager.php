<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class LenderAccountDisplayManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var  LocationManager */
    private $locationManager;
    /** @var TranslatorInterface  */
    private $translator;
    /** @var CacheItemPoolInterface */
    private $cachePool;
    /** @var CIPManager */
    private $cipManager;
    /** @var array|null */
    private $cipIndicators = false;
    /** @var  ProductManager */
    private $productManager;

    public function __construct(
        EntityManager $entityManager,
        LocationManager $locationManager,
        TranslatorInterface $translator,
        CacheItemPoolInterface $cachePool,
        CIPManager $cipManager,
        ProductManager $productManager
    ) {
        $this->entityManager   = $entityManager;
        $this->locationManager = $locationManager;
        $this->translator      = $translator;
        $this->cachePool       = $cachePool;
        $this->cipManager      = $cipManager;
        $this->productManager  = $productManager;
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

        return [
            'myLoanOnProject'   => $loans->getBidsValid($projectId, $lenderAccount->id_lender_account),
            'myAverageLoanRate' => round($loans->getAvgLoansPreteur($projectId, $lenderAccount->id_lender_account), 2)
        ];
    }

    /**
     * get a ready to use formatted array of lender loans allocation by company sector
     * @param $lenderId
     * @return array
     */
    public function getLenderLoansAllocationByCompanySector($lenderId)
    {
        $dataForTreeMap = [];
        $data           = $this->getLoansAllocationByCompanySector($lenderId);

        foreach ($data as $row) {
            $dataForTreeMap[] = [
                'name'                => $this->translator->trans('company-sector_sector-' . $row['sector']),
                'projectsCount'       => (int) $row['count'],
                'averageRate'         => round($row['average_rate'], 2),
                'loanedAmount'        => round($row['loaned_amount'], 2),
                'loanSharePercentage' => round($row['loaned_amount'] * 100 / array_sum(array_column($data, 'loaned_amount')), 2),
                'svgIconId'           => '#category-sm-' . $row['sector']
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
                'insee'               => $row['insee_region_code'],
                'name'                => $regions[$row['insee_region_code']],
                'projectsCount'       => (int) $row['count'],
                'averageRate'         => round($row['average_rate'], 2),
                'loanedAmount'        => round($row['loaned_amount'], 2),
                'loanSharePercentage' => round($row['loaned_amount'] * 100 / array_sum(array_column($data, 'loaned_amount')), 0),
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
                $cachedItem->set($projectsCountByRegion)->expiresAfter(CacheKeys::DAY);
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
                $cachedItem->set($projectsCountByCategory)->expiresAfter(CacheKeys::DAY);
                $this->cachePool->save($cachedItem);
            } catch (\Exception $exception) {
                return [];
            }
            return $projectsCountByCategory;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * @param \projects $project
     * @param \lenders_accounts $lender
     *
     * @return bool
     */
    public function isProjectAdvisedForLender(\projects $project, \lenders_accounts $lender)
    {
        if (false === $this->productManager->getLenderEligibility($lender, $project)) {
            return false;
        }

        if (false === $this->cipIndicators) {
            $this->cipIndicators = $this->cipManager->getIndicators($lender);
        }

        if (null === $this->cipIndicators) {
            return false;
        }

        $durationLimit = $this->cipIndicators[CIPManager::INDICATOR_PROJECT_DURATION];

        return (
            null === $durationLimit
            || $project->period <= $durationLimit
        );
    }
}
