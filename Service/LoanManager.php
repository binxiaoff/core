<?php
namespace Unilend\Service;

use Unilend\librairies\ULogger;

/**
 * Class LoanManager
 * @package Unilend\Service
 */
class LoanManager
{
    /** @var \bids */
    private $oBid;
    /** @var  \loans */
    private $oLoan;
    /** @var  \accepted_bids */
    private $oAcceptedBid;
    /** @var  ULogger */
    private $oLogger;

    public function __construct()
    {
        $this->oBid         = Loader::loadData('bids');
        $this->oLoan        = Loader::loadData('loans');
        $this->oAcceptedBid = Loader::loadData('accepted_bids');
    }

    /**
     * @param ULogger $oLogger
     */
    public function setLogger(ULogger $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function create($iLenderId, $iProjectId, $fAmount, $fRate, $iContractType, $aAcceptedBids)
    {
        if (empty($aAcceptedBids)) {
            return false;
        }

        $this->oLoan->unsetData();
        $this->oLoan->id_lender        = $iLenderId;
        $this->oLoan->id_project       = $iProjectId;
        $this->oLoan->amount           = $fAmount * 100;
        $this->oLoan->rate             = $fRate;
        $this->oLoan->id_type_contract = $iContractType;
        $this->oLoan->create();

        if (empty($this->oLoan->id_loan)) {
            return false;
        }

        foreach ($aAcceptedBids as $aAcceptedBid) {
            $this->oAcceptedBid->unsetData();
            $this->oAcceptedBid->id_bid  = $aAcceptedBid['id_bid'];
            $this->oAcceptedBid->id_loan = $this->oLoan->id_loan;
            $this->oAcceptedBid->amount  = $aAcceptedBid['amount'] * 100;
            $this->oAcceptedBid->create();

            if ($this->oAcceptedBid->id > 0 && $this->oLogger instanceof ULogger) {
                switch ($iContractType) {
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
                    'project : ' . $iProjectId . ' : bid (' . $aAcceptedBid['id_bid'] . ') has been transferred to ' . $sType . ' contract loan (' . $this->oLoan->id_loan . ') with amount ' . $aAcceptedBid['amount']
                );
            }
        }
    }

}