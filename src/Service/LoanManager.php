<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{AcceptationsLegalDocs, AcceptedBids, Clients, Embeddable\LendingRate, LoanTransfer, Loans, UnderlyingContract, UnderlyingContractAttributeType};
use Unilend\Service\Product\Contract\ContractAttributeManager;

class LoanManager
{
    /** @var LoggerInterface */
    private $logger;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ContractAttributeManager */
    private $contractAttributeManager;

    /**
     * @param EntityManagerInterface   $entityManager
     * @param ContractAttributeManager $contractAttributeManager
     */
    public function __construct(EntityManagerInterface $entityManager, ContractAttributeManager $contractAttributeManager)
    {
        $this->entityManager            = $entityManager;
        $this->contractAttributeManager = $contractAttributeManager;
    }

    /**
     * @required
     *
     * @param LoggerInterface|null $logger
     */
    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param array              $acceptedBids
     * @param UnderlyingContract $contract
     */
    public function create(array $acceptedBids, UnderlyingContract $contract): void
    {
        $loanAmount = 0;
        $interests  = 0;
        /** @var AcceptedBids $acceptedBid */
        foreach ($acceptedBids as $acceptedBid) {
            $interests = round(bcadd($interests, bcmul($acceptedBid->getBid()->getRate()->getMargin(), $acceptedBid->getAmount(), 4), 4), 2);
            $loanAmount += $acceptedBid->getAmount();
        }

        if (UnderlyingContract::CONTRACT_IFP === $contract->getLabel()) {
            $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
            if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
                throw new \UnexpectedValueException('The IFP contract max amount is not set');
            }
            $IfpLoanAmountMax = $contractAttrVars[0];

            if (bccomp(round(bcdiv($loanAmount, 100, 4), 2), $IfpLoanAmountMax, 2) > 0) {
                throw new InvalidArgumentException(sprintf(
                    'Sum of bids for client %s exceeds maximum IFP amount.',
                    $acceptedBids[0]->getIdBid()->getWallet()->getIdClient()->getIdClient()
                ));
            }

            //todo: check also if this is the only one loan to build for IFP (We can only have one IFP loan per project)
        }

        $currentAcceptedTermsOfSale = $this->entityManager
            ->getRepository(AcceptationsLegalDocs::class)
            ->findOneBy(['idClient' => $acceptedBids[0]->getIdBid()->getWallet()->getIdClient()], ['added' => 'DESC'])
        ;

        $lendingRate = (new LendingRate())
            ->setIndexType(LendingRate::INDEX_FIXED)
            ->setMargin(round(bcdiv($interests, $loanAmount, 4), 1))
        ;

        $loan = new Loans();
        $loan
            ->setLender($acceptedBids[0]->getIdBid()->getLender())
            ->setTranche($acceptedBids[0]->getIdBid()->getProject())
            ->setAmount($loanAmount)
            ->setRate($lendingRate)
            ->setStatus(Loans::STATUS_ACCEPTED)
            ->setUnderlyingContract($contract)
            ->setAcceptationLegalDoc($currentAcceptedTermsOfSale)
        ;

        $this->entityManager->persist($loan);
        $this->entityManager->flush($loan);

        /** @var AcceptedBids $acceptedBid */
        foreach ($acceptedBids as $acceptedBid) {
            $acceptedBid->setLoan($loan);
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
            $loan = $this->entityManager->getRepository(Loans::class)->find($loan->id_loan);
        }

        $loanTransfer = $loan->getTransfer();
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
            $loan = $this->entityManager->getRepository(Loans::class)->find($loan->id_loan);
        }

        $firstTransfer = $this->entityManager->getRepository(LoanTransfer::class)->findOneBy(['idLoan' => $loan], ['added' => 'ASC']);

        if ($firstTransfer) {
            return $firstTransfer->getIdTransfer()->getClientOrigin();
        }

        return null;
    }
}
