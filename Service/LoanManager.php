<?php
namespace Unilend\Service;

use Symfony\Bridge\Monolog\Logger;
use Unilend\Service\Simulator\EntityManager;

/**
 * Class LoanManager
 * @package Unilend\Service
 */
class LoanManager
{
    /** @var  Logger */
    private $oLogger;
    /** @var EntityManager  */
    private $oEntityManager;

    public function __construct(EntityManager $oEntityManager)
    {
        $this->oEntityManager = $oEntityManager;
    }
    /**
     * @param Logger $oLogger
     */
    public function setLogger(Logger $oLogger)
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

            if ($oAcceptedBid->id_accepted_bid > 0 && $this->oLogger instanceof Logger) {
                switch ($oLoan->id_type_contract) {
                    case \loans::TYPE_CONTRACT_BDC:
                        $sType = 'BDC';
                        break;
                    case \loans::TYPE_CONTRACT_IFP:
                        $sType = 'IFP';
                        break;
                    default:
                        $sType = 'UNKNOWN';
                        break;
                }
                $this->oLogger->info(
                    'project : ' . $oLoan->id_project . ' : bid (' . $aAcceptedBid['bid_id'] . ') has been transferred to ' . $sType . ' contract loan (' . $oLoan->id_loan . ') with amount ' . $aAcceptedBid['amount'],
                    array(__METHOD__)
                );
            }
        }
    }
}
