<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class BalanceSheetList
{
    /**
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetListDetail")
     */
    private $myInfo;

    /**
     * @JMS\SerializedName("exception")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\ExceptionResponse")
     */
    private $exception;

    /**
     * @return BalanceSheetListDetail
     */
    public function getMyInfo()
    {
        return $this->myInfo;
    }

    /**
     * @param BalanceSheetListDetail $myInfo
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
