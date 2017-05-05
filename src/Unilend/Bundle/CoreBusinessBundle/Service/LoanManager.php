<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class LoanManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LoanManager
{
    /** @var LoggerInterface */
    private $logger;
    /** @var EntityManagerSimulator  */
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
     * @param \loans $loan
     *
     * @return bool
     */
    public function create(\loans $loan)
    {
        $acceptedBids = $loan->getAcceptedBids();
        if (empty($acceptedBids)) {
            return false;
        }
        $loan->create();

        if (empty($loan->id_loan)) {
            return false;
        }
        /** @var \accepted_bids $acceptedBid */
        $acceptedBid = $this->entityManagerSimulator->getRepository('accepted_bids');
        foreach ($acceptedBids  as $aAcceptedBid) {
            $acceptedBid->unsetData();
            $acceptedBid->id_bid  = $aAcceptedBid['bid_id'];
            $acceptedBid->id_loan = $loan->id_loan;
            $acceptedBid->amount  = $aAcceptedBid['amount'] * 100;
            $acceptedBid->create();

            if ($acceptedBid->id_accepted_bid > 0 && $this->logger instanceof LoggerInterface) {
                $this->logger->info(
                    'Loan ' . $loan->id_loan . ' generated from bid ' . $aAcceptedBid['bid_id'] . ' with amount ' . $aAcceptedBid['amount'],
                    array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $loan->id_project, 'id_loan' => $loan->id_loan, 'id_bid' => $aAcceptedBid['bid_id'])
                );
            }
        }

        return true;
    }

    /**
     * @param \loans $loan
     *
     * @return Clients
     */
    public function getFormerOwner(\loans $loan)
    {
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer = $this->entityManagerSimulator->getRepository('loan_transfer');
        $loanTransfer->get($loan->id_transfer);

        /** @var \transfer $transfer */
        $transfer = $this->entityManagerSimulator->getRepository('transfer');
        $transfer->get($loanTransfer->id_transfer);

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($transfer->id_client_origin);
    }

    /**
     * @param \loans $loan
     *
     * @return \DateTime
     */
    public function getLoanTransferDate(\loans $loan)
    {
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer = $this->entityManagerSimulator->getRepository('loan_transfer');
        $loanTransfer->get($loan->id_transfer);

        $transferDate = new \DateTime($loanTransfer->added);
        return $transferDate;
    }

    /**
     * @param \loans $loan
     *
     * @return Clients
     */
    public function getFirstOwner(\loans $loan)
    {
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer  = $this->entityManagerSimulator->getRepository('loan_transfer');
        $firstTransfer = $loanTransfer->select('id_loan = ' . $loan->id_loan, 'added ASC', null, 1)[0];
        $loanTransfer->get($firstTransfer['id_loan_transfer']);

        /** @var \transfer $transfer */
        $transfer = $this->entityManagerSimulator->getRepository('transfer');
        $transfer->get($loanTransfer->id_transfer);

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($transfer->id_client_origin);
    }

}
