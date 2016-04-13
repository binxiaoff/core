<?php
namespace Unilend\Service;

use Unilend\core\Loader;
use Unilend\librairies\ULogger;

/**
 * Class LoanManager
 * @package Unilend\Service
 */
class LoanManager extends Service
{
    /** @var  ULogger */
    private $oLogger;

    /**
     * @param ULogger $oLogger
     */
    public function setLogger(ULogger $oLogger)
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
        $oAcceptedBid = $this->loadData('accepted_bids');
        foreach ($oLoan->getAcceptedBids() as $aAcceptedBid) {
            $oAcceptedBid->unsetData();
            $oAcceptedBid->id_bid  = $aAcceptedBid['bid_id'];
            $oAcceptedBid->id_loan = $oLoan->id_loan;
            $oAcceptedBid->amount  = $aAcceptedBid['amount'] * 100;
            $oAcceptedBid->create();

            if ($oAcceptedBid->id_accepted_bid > 0 && $this->oLogger instanceof ULogger) {
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
                $this->oLogger->addRecord(
                    ULogger::INFO,
                    'project : ' . $oLoan->id_project . ' : bid (' . $aAcceptedBid['bid_id'] . ') has been transferred to ' . $sType . ' contract loan (' . $oLoan->id_loan . ') with amount ' . $aAcceptedBid['amount']
                );
            }
        }
    }
}
