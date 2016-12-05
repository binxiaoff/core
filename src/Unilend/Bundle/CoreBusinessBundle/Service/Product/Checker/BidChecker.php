<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

trait BidChecker
{
    use LenderChecker;

    /**
     * @param \bids                   $bid
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isBidEligibleForMaxTotalAmount(\bids $bid, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $totalAmount = $bid->getBidsEncours($bid->id_project, $bid->id_lender_account)['solde'];
        $bidAmount   = bcdiv($bid->amount, 100, 2);
        $totalAmount = bcadd($totalAmount, $bidAmount, 2);

        $maxAmountEligible = $this->getMaxEligibleAmount($product, $productAttributeManager);
        if (null === $maxAmountEligible) {
            return null;
        }

        return bccomp($maxAmountEligible, $totalAmount, 2) >= 0;
    }

    /**
     * @param \bids             $bid
     * @param \lenders_accounts $lender
     * @param \product          $product
     * @param EntityManager     $entityManager
     * @param ContractManager   $contractManager
     *
     * @return bool
     */
    public function isAutobidEligibleForMaxTotalAmount(\bids $bid, \lenders_accounts $lender, \product $product, EntityManager $entityManager, ContractManager $contractManager)
    {
        $bidMaxAmount = $this->getAutobidMaxEligibleAmount($lender, $product, $entityManager, $contractManager);
        if (null === $bidMaxAmount) {
            return true;
        }

        return bcdiv($bid->amount, 100, 2) <= $bidMaxAmount;
    }
}
