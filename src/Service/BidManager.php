<?php

namespace Unilend\Service;

use Exception;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\{AcceptedBids, Bids};
use Unilend\Repository\{AcceptedBidsRepository, BidsRepository};
use Unilend\Service\User\RealUserFinder;

class BidManager
{
    /** @var BidsRepository */
    private $bidsRepository;
    /** @var AcceptedBidsRepository */
    private $acceptedBidsRepository;
    /** @var RealUserFinder */
    private $realUserFinder;

    /**
     * @param BidsRepository         $bidsRepository
     * @param AcceptedBidsRepository $acceptedBidsRepository
     * @param RealUserFinder         $realUserFinder
     */
    public function __construct(BidsRepository $bidsRepository, AcceptedBidsRepository $acceptedBidsRepository, RealUserFinder $realUserFinder)
    {
        $this->bidsRepository         = $bidsRepository;
        $this->acceptedBidsRepository = $acceptedBidsRepository;
        $this->realUserFinder         = $realUserFinder;
    }

    /**
     * @param Bids       $bid
     * @param Money|null $acceptedMoney
     *
     * @throws Exception
     */
    public function accept(Bids $bid, ?Money $acceptedMoney = null): void
    {
        $bid->setStatus(Bids::STATUS_ACCEPTED);
        $acceptedMoney = $acceptedMoney ?? $bid->getMoney();

        $acceptedBid = new AcceptedBids();
        $acceptedBid
            ->setBid($bid)
            ->setMoney($acceptedMoney)
            ->setAddedByValue($this->realUserFinder)
        ;

        $this->bidsRepository->save($bid);
        $this->acceptedBidsRepository->save($acceptedBid);
    }

    /**
     * @param Bids $bid
     *
     * @throws Exception
     */
    public function reject(Bids $bid): void
    {
        if (Bids::STATUS_PENDING === $bid->getStatus()) {
            $bid->setStatus(Bids::STATUS_REJECTED);
            $this->bidsRepository->save($bid);
        }
    }
}
