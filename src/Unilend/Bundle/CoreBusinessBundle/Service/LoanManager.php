<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AcceptedBids, Clients, Loans, UnderlyingContract, UnderlyingContractAttributeType
};
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractAttributeManager;

/**
 * Class LoanManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LoanManager
{
    /** @var LoggerInterface */
    private $logger;
    /** @var EntityManager */
    private $entityManager;
    /** @var ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(EntityManager $entityManager, ContractAttributeManager $contractAttributeManager)
    {
        $this->entityManager            = $entityManager;
        $this->contractAttributeManager = $contractAttributeManager;

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
     * @param UnderlyingContract $contract
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(array $acceptedBids, UnderlyingContract $contract)
    {
        $loanAmount = 0;
        $interests  = 0;

        foreach ($acceptedBids as $acceptedBid) {
            $interests  = round(bcadd($interests, bcmul($acceptedBid->getIdBid()->getRate(), $acceptedBid->getAmount(), 4), 4), 2);
            $loanAmount += $acceptedBid->getAmount();
        }

        if (UnderlyingContract::CONTRACT_IFP === $contract->getLabel()) {
            $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
            if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
                throw new \UnexpectedValueException('The IFP contract max amount is not set');
            } else {
                $IfpLoanAmountMax = $contractAttrVars[0];
            }

            if (bccomp(round(bcdiv($loanAmount, 100, 4), 2), $IfpLoanAmountMax, 2) > 0) {
                throw new \InvalidArgumentException('Sum of bids for client ' . $acceptedBids[0]->getIdBid()->getIdLenderAccount()->getIdClient()->getIdClient() . ' exceeds maximum IFP amount.');
            }

            //todo: check also if this is the only one loan to build for IFP (We can only have one IFP loan per project)
        }

        $rate = round(bcdiv($interests, $loanAmount, 4), 1);

        $loan = new Loans();
        $loan
            ->setIdLender($acceptedBids[0]->getIdBid()->getIdLenderAccount())
            ->setProject($acceptedBids[0]->getIdBid()->getProject())
            ->setAmount($loanAmount)
            ->setRate($rate)
            ->setStatus(Loans::STATUS_ACCEPTED)
            ->setIdTypeContract($contract);

        $this->entityManager->persist($loan);
        $this->entityManager->flush($loan);

        /** @var AcceptedBids $acceptedBid */
        foreach ($acceptedBids as $acceptedBid) {
            $acceptedBid->setIdLoan($loan);
            $this->entityManager->flush($acceptedBid);
        }
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
