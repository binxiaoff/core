<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;

trait BidChecker
{
    use ClientChecker;
    use LenderChecker;

    /**
     * @param Bids            $bid
     * @param ContractManager $contractManager
     * @param EntityManager   $entityManager
     *
     * @return bool
     */
    private function isEligibleForMaxTotalAmount(Bids $bid, ContractManager $contractManager, $entityManager)
    {
        $isAutobid = false;

        if ($bid->getAutobid()) {
            $isAutobid = true;
        }

        $amountRestForBid = $this->getAmountLenderCanStillBid($bid->getIdLenderAccount()->getIdClient(), $bid->getProject(), $contractManager, $entityManager, $isAutobid);

        if (null === $amountRestForBid) {
            return true;
        }

        return bcdiv($bid->getAmount(), 100, 2) <= $amountRestForBid;
    }
}
