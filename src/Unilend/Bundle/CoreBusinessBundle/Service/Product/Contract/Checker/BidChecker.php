<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker;


use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

trait BidChecker
{
    use LenderChecker;

    public function isAutobidEligible(\lenders_accounts $lender, \bids $bid, \product $product, EntityManager $entityManager, ContractAttributeManager $contractAttributeManager)
    {
        $bidMaxAmount = $this->getMaxEligibleAutobidAmount($lender, $product, $entityManager, $contractAttributeManager);
        return bcdiv($bid->amount, 100) <= $bidMaxAmount;
    }
}