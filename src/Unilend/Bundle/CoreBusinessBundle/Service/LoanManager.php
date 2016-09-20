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
}
