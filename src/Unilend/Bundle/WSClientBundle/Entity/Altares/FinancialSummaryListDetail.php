<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class FinancialSummaryListDetail
{
    /**
     * @JMS\SerializedName("syntheseFinanciereList")
     * @JMS\Type("array<Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummaryDetail>")
     */
    private $financialSummaryList;

    /**
     * @JMS\SerializedName("SIGList")
     * @JMS\Type("array<Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummaryDetail>")
     */
    private $balanceManagementLine;

    /**
     * @return FinancialSummaryDetail[]
     */
    public function getFinancialSummaryList()
    {
        return $this->financialSummaryList;
    }

    /**
     * @param array $financialSummaryList
     */
    public function setFinancialSummaryList(array $financialSummaryList)
    {
        $this->financialSummaryList = $financialSummaryList;
    }

    /**
     * @return FinancialSummaryDetail[]
     */
    public function getBalanceManagementLine()
    {
        return $this->balanceManagementLine;
    }

    /**
     * @param array $balanceManagementLine
     */
    public function setBalanceManagementLine($balanceManagementLine)
    {
        $this->balanceManagementLine = $balanceManagementLine;
    }
}
