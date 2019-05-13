<?php

namespace Unilend\Service\Product\Checker;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\Bids;
use Unilend\Service\Product\Contract\ContractManager;

trait BidChecker
{
    use ClientChecker;
    use LenderChecker;

    /**
     * @param Bids                   $bid
     * @param ContractManager        $contractManager
     * @param EntityManagerInterface $entityManager
     *
     * @return bool
     */
    private function isEligibleForMaxTotalAmount(Bids $bid, ContractManager $contractManager, EntityManagerInterface $entityManager)
    {
        $isAutobid = false;

        if ($bid->getAutobid()) {
            $isAutobid = true;
        }

        $amountRestForBid = $this->getAmountLenderCanStillBid($bid->getWallet()->getIdClient(), $bid->getTranche(), $contractManager, $entityManager, $isAutobid);

        if (null === $amountRestForBid) {
            return true;
        }

        return bcdiv($bid->getAmount(), 100, 2) <= $amountRestForBid;
    }
}
