<?php

namespace Unilend\Entity\External\Altares;

use JMS\Serializer\Annotation as JMS;

class BalanceSheetListDetail
{
    /**
     * @var CompanyBalanceSheet[]
     *
     * @JMS\SerializedName("bilans")
     * @JMS\Type("array<Unilend\Entity\External\Altares\CompanyBalanceSheet>")
     */
    private $balanceSheets;

    /**
     * @return CompanyBalanceSheet[]
     */
    public function getBalanceSheets()
    {
        return $this->balanceSheets;
    }

    /**
     * @return int
     */
    public function getBalanceSheetsCount()
    {
        return count($this->balanceSheets);
    }

    /**
     * CompanyBalanceSheet
     */
    public function getLastBalanceSheet()
    {
        $lastBalanceDate  = null;
        $lastBalanceSheet = null;

        foreach ($this->balanceSheets as $balanceSheet) {
            if (null === $lastBalanceDate) {
                $lastBalanceDate  = $balanceSheet->getCloseDate();
                $lastBalanceSheet = $balanceSheet;
            }

            if ($lastBalanceDate < $balanceSheet->getCloseDate()) {
                $lastBalanceDate  = $balanceSheet->getCloseDate();
                $lastBalanceSheet = $balanceSheet;
            }
        }

        return $lastBalanceSheet;
    }
}
