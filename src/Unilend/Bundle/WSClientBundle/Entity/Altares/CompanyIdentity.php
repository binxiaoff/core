<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyIdentity
{
    /**
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentityDetail")
     */
    private $myInfo;

    /**
     * @JMS\SerializedName("exception")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\ExceptionResponse")
     */
    private $exception;

    /**
     * @return CompanyIdentityDetail
     */
    public function getMyInfo()
    {
        return $this->myInfo;
    }

    /**
     * @param CompanyIdentityDetail $myInfo
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
