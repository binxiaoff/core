<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * Class LoanManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LoanManager
{
    /** @var LoggerInterface */
    private $oLogger;
    /** @var EntityManager  */
    private $oEntityManager;

    public function __construct(EntityManager $oEntityManager)
    {
        $this->oEntityManager = $oEntityManager;
    }
    /**
     * @param LoggerInterface $oLogger
     */
    public function setLogger(LoggerInterface $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function create(\loans $oLoan)
    {
        $aAcceptedBids = $oLoan->getAcceptedBids();
        if (empty($aAcceptedBids)) {
            return false;
        }
        $oLoan->create();

        if (empty($oLoan->id_loan)) {
            return false;
        }
        /** @var \accepted_bids $oAcceptedBid */
        $oAcceptedBid = $this->oEntityManager->getRepository('accepted_bids');
        foreach ($oLoan->getAcceptedBids() as $aAcceptedBid) {
            $oAcceptedBid->unsetData();
            $oAcceptedBid->id_bid  = $aAcceptedBid['bid_id'];
            $oAcceptedBid->id_loan = $oLoan->id_loan;
            $oAcceptedBid->amount  = $aAcceptedBid['amount'] * 100;
            $oAcceptedBid->create();

            if ($oAcceptedBid->id_accepted_bid > 0 && $this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info(
                    'Loan ' . $oLoan->id_loan . ' generated from bid ' . $aAcceptedBid['bid_id'] . ' with amount ' . $aAcceptedBid['amount'],
                    array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oLoan->id_project, 'id_loan' => $oLoan->id_loan, 'id_bid' => $aAcceptedBid['bid_id'])
                );
            }
        }
    }

    /**
     * @param \loans $loan
     * @return \lenders_accounts
     */
    public function getFormerOwner(\loans $loan)
    {
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer = $this->oEntityManager->getRepository('loan_transfer');
        $lastTransfer = $loanTransfer->select('id_loan = ' . $loan->id_loan, 'added DESC', null, 1)[0];
        $loanTransfer->get($lastTransfer['id_transfer']);

        /** @var \lenders_accounts $lender */
        $lender = $this->oEntityManager->getRepository('lenders_accounts');
        $lender->get($loanTransfer->id_lender_origin);

        return $lender;
    }

    /**
     * @param \loans $loan
     * @return \DateTime
     */
    public function getLoanTransferDate(\loans $loan)
    {
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer = $this->oEntityManager->getRepository('loan_transfer');
        $loanTransfer->get($loan->id_transfer);

        $transferDate = new \DateTime($loanTransfer->added);
        return $transferDate;
    }

    public function getFirstOwner(\loans $loan)
    {
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer = $this->oEntityManager->getRepository('loan_transfer');
        $firstTransfer = $loanTransfer->select('id_loan = ' . $loan->id_loan, 'added ASC', null, 1)[0];
        $loanTransfer->get($firstTransfer['id_transfer']);

        /** @var \lenders_accounts $lender */
        $lender = $this->oEntityManager->getRepository('lenders_accounts');
        $lender->get($loanTransfer->id_lender_origin);

        return $lender;
    }

}
