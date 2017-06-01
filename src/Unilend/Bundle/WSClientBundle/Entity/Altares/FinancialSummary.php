<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class FinancialSummary
{
    /**
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummaryListDetail")
     */
    private $myInfo;

    /**
     * @JMS\SerializedName("exception")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\ExceptionResponse")
     */
    private $exception;

    /**
     * @return FinancialSummaryListDetail
     */
    public function getMyInfo()
    {
        return $this->myInfo;
    }

    /**
     * @param FinancialSummaryDetail $myInfo
     */
    public function setMyInfo($myInfo)
    {
        $this->myInfo = $myInfo;
    }

    /**
     * @return ExceptionResponse
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param ExceptionResponse $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }
}
