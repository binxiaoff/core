<?php

namespace Unilend\Service;

use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Exception;
use Unilend\Entity\Embeddable\{LendingRate, Money};
use Unilend\Entity\{AcceptedBids, Bids, Tranche, Wallet};
use Unilend\Repository\{AcceptedBidsRepository, BidsRepository};

class BidManager
{
    /** @var BidsRepository */
    private $bidsRepository;
    /** @var AcceptedBidsRepository */
    private $acceptedBidsRepository;

    /**
     * @param BidsRepository         $bidsRepository
     * @param AcceptedBidsRepository $acceptedBidsRepository
     */
    public function __construct(BidsRepository $bidsRepository, AcceptedBidsRepository $acceptedBidsRepository)
    {
        $this->bidsRepository         = $bidsRepository;
        $this->acceptedBidsRepository = $acceptedBidsRepository;
    }

    /**
     * @param Wallet      $wallet
     * @param Tranche     $tranche
     * @param Money       $money
     * @param LendingRate $rate
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Bids
     */
    public function bid(Wallet $wallet, Tranche $tranche, Money $money, LendingRate $rate): Bids
    {
        $bidNb = $this->bidsRepository->countBy(['tranche' => $tranche]);
        ++$bidNb;

        $bid = new Bids();
        $bid
            ->setWallet($wallet)
            ->setTranche($tranche)
            ->setMoney($money)
            ->setRate($rate)
            ->setStatus(Bids::STATUS_PENDING)
            ->setOrdre($bidNb)
        ;

        $this->bidsRepository->save($bid);

        return $bid;
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
        if (in_array($bid->getStatus(), [Bids::STATUS_PENDING, Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID])) {
            $bid->setStatus(Bids::STATUS_REJECTED);
            $this->bidsRepository->save($bid);
        }
    }
}
