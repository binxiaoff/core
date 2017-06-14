<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\librairies\CacheKeys;

class IfuManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var CacheItemPoolInterface */
    private $cachePool;

    /**
     * @param EntityManager $entityMananger
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(EntityManager $entityManager, CacheItemPoolInterface $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->cachePool     = $cachePool;
    }

    /**
     * @param int $year
     *
     * @return array
     */
    public function getWallets($year)
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::IFU_WALLETS . $year);
        if (false === $cachedItem->isHit()) {
            $operationTypes = [
                OperationType::LENDER_LOAN,
                OperationType::CAPITAL_REPAYMENT,
                OperationType::GROSS_INTEREST_REPAYMENT
            ];
            $walletRepository     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
            $walletsWithMovements = $walletRepository->getLenderWalletsWithOperationsInYear($operationTypes, $year);
            $cachedItem->set($walletsWithMovements)->expiresAfter(CacheKeys::DAY);
            $this->cachePool->save($cachedItem);

            return $walletsWithMovements;
        } else {
            return $cachedItem->get();
        }
    }
}
