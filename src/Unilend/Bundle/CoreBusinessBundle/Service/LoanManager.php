<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\AcceptedBids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class LoanManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LoanManager
{
    /** @var LoggerInterface */
    private $logger;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManagerSimulator $entityManagerSimulator, EntityManager $entityManager)
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;

    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array              $acceptedBids
     * @param float              $loanAmount
     * @param float              $rate
     * @param UnderlyingContract $contract
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(array $acceptedBids, float $loanAmount, float $rate, UnderlyingContract $contract)
    {
        $loan = new Loans();
        $loan
            ->setIdLender($acceptedBids[0]->getIdBid()->getIdLenderAccount())
            ->setProject($acceptedBids[0]->getIdBid()->getProject())
            ->setAmount($loanAmount)
            ->setRate($rate)
            ->setStatus(Loans::STATUS_ACCEPTED)
            ->setIdTypeContract($contract);

        $this->entityManager->persist($loan);

        /** @var AcceptedBids $acceptedBid */
        foreach ($acceptedBids as $acceptedBid) {
            $acceptedBid->setIdLoan($loan);
        }

        $this->entityManager->flush();
    }

    /**
     * @param \loans|Loans $loan
     *
     * @return Clients|null
     */
    public function getFormerOwner($loan)
    {
        if ($loan instanceof \loans) {
            $loan = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($loan->id_loan);
        }

        $loanTransfer = $loan->getIdTransfer();
        if ($loanTransfer) {
            return $loanTransfer->getIdTransfer()->getClientOrigin();
        }

        return null;
    }

    /**
     * @param \loans|Loans $loan
     *
     * @return Clients
     */
    public function getFirstOwner($loan)
    {
        if ($loan instanceof \loans) {
            $loan = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($loan->id_loan);
        }

        $firstTransfer = $this->entityManager->getRepository('UnilendCoreBusinessBundle:LoanTransfer')->findOneBy(['idLoan' => $loan], ['added' => 'ASC']);

        if ($firstTransfer) {
            return $firstTransfer->getIdTransfer()->getClientOrigin();
        }

        return null;
    }
}
