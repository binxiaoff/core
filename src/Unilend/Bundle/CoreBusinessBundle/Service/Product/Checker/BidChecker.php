<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;

trait BidChecker
{
    use ClientChecker;
    use LenderChecker;

    /**
     * @param Bids            $bid
     * @param Product         $product
     * @param ContractManager $contractManager
     *
     * @return bool
     */
    public function isEligibleForMaxTotalAmount(Bids $bid, Product $product, ContractManager $contractManager)
    {
        $isAutobid = false;

        if ($bid->getAutobid()) {
            $isAutobid = true;
        }

        $bidMaxAmount = $this->getMaxEligibleAmount($bid->getIdLenderAccount()->getIdClient(), $product, $contractManager, $isAutobid);

        if (null === $bidMaxAmount) {
            return true;
        }

        return bcdiv($bid->getAmount(), 100, 2) <= $bidMaxAmount;
    }
}
