<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

trait BidChecker
{
    use LenderChecker;

    /**
     * @param Bids                    $bid
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isBidEligibleForMaxTotalAmount(Bids $bid, \product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $totalAmount = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->getSumByWalletAndProjectAndStatus($bid->getIdLenderAccount(), $bid->getProject(), Bids::STATUS_BID_PENDING);
        $bidAmount   = bcdiv($bid->getAmount(), 100, 2);
        $totalAmount = bcadd($totalAmount, $bidAmount, 2);

        $maxAmountEligible = $this->getMaxEligibleAmount($product, $productAttributeManager);
        if (null === $maxAmountEligible) {
            return null;
        }

        return bccomp($maxAmountEligible, $totalAmount, 2) >= 0;
    }

    /**
     * @param Bids              $bid
     * @param \product          $product
     * @param EntityManager     $entityManager
     * @param ContractManager   $contractManager
     *
     * @return bool
     */
    public function isAutobidEligibleForMaxTotalAmount(Bids $bid, \product $product, EntityManagerSimulator $entityManager, ContractManager $contractManager)
    {
        $bidMaxAmount = $this->getAutobidMaxEligibleAmount($bid->getIdLenderAccount()->getIdClient(), $product, $entityManager, $contractManager);
        if (null === $bidMaxAmount) {
            return true;
        }

        return bcdiv($bid->getAmount(), 100, 2) <= $bidMaxAmount;
    }
}
