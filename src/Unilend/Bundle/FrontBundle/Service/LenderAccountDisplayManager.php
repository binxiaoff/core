<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\librairies\CacheKeys;

class LenderAccountDisplayManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
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
    /** @var EntityManager */
    private $entityManager;

    /**
     * LenderAccountDisplayManager constructor.
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param LocationManager        $locationManager
     * @param TranslatorInterface    $translator
     * @param CacheItemPoolInterface $cachePool
     * @param CIPManager             $cipManager
     * @param ProductManager         $productManager
     * @param EntityManager          $entityManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        LocationManager $locationManager,
        TranslatorInterface $translator,
        CacheItemPoolInterface $cachePool,
        CIPManager $cipManager,
        ProductManager $productManager,
        EntityManager $entityManager
    ) {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->locationManager        = $locationManager;
        $this->translator             = $translator;
        $this->cachePool              = $cachePool;
        $this->cipManager             = $cipManager;
        $this->productManager         = $productManager;
        $this->entityManager          = $entityManager;
    }

    /**
     * @param int     $projectId
     * @param Clients $client
     *
     * @return array
     * @throws \Exception
     */
    public function getBidsForProject($projectId, Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        /** @var \bids $bids */
        $bids       = $this->entityManagerSimulator->getRepository('bids');
        $lenderBids = $bids->select('id_lender_account = ' . $wallet->getId() . ' AND id_project = ' . $projectId);

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

    /**
     * @param int     $projectId
     * @param Clients $client
     *
     * @return array
     * @throws \Exception
     */
    public function getLoansForProject($projectId, Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        /** @var \loans $loans */
        $loans = $this->entityManagerSimulator->getRepository('loans');

        return [
            'myLoanOnProject'   => $loans->getBidsValid($projectId, $wallet->getId()),
            'myAverageLoanRate' => round($loans->getAvgLoansPreteur($projectId, $wallet->getId()), 2)
        ];
    }

    /**
     * get a ready to use formatted array of lender loans allocation by company sector
     * @param Clients $client
     *
     * @return array
     * @throws \Exception
     */
    public function getLenderLoansAllocationByCompanySector(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $dataForTreeMap = [];
        $data           = $this->getLoansAllocationByCompanySector($wallet->getId());

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
     * @param Clients $client
     *
     * @return array
     * @throws \Exception
     */
    public function getLenderLoansAllocationByRegion(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $regions        = $this->locationManager->getFrenchRegions();
        $dataForTreeMap = [];
        $data           = $this->getLoansAllocationByCompanyRegion($wallet->getId());

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
     *
     * @return array
     * @throws \Exception
     */
    private function getLoansAllocationByCompanyRegion($lenderId)
    {
        $cachedItem = $this->cachePool->getItem(__FUNCTION__ . $lenderId);

        if (false === $cachedItem->isHit()) {
            $loanRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
            try {
                $projectsCountByRegion = $loanRepository->countProjectsForLenderByRegion($lenderId);
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
     *
     * @return array
     */
    private function getLoansAllocationByCompanySector($lenderId)
    {
        $cachedItem = $this->cachePool->getItem(__FUNCTION__ . $lenderId);

        if (false === $cachedItem->isHit()) {
            /** @var \projects $projects */
            $projects = $this->entityManagerSimulator->getRepository('projects');
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
     * @param Clients $clients
     *
     * @return bool
     */
    public function isProjectAdvisedForLender(\projects $project, Clients $clients)
    {
        if (false === $this->productManager->getLenderEligibility($clients, $project)) {
            return false;
        }

        if (false === $this->cipIndicators) {
            $this->cipIndicators = $this->cipManager->getIndicators($clients);
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
