<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class EstablishmentIdentity
{
    /**
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\EstablishmentIdentityDetail")
     */
    private $myInfo;

    /**
     * @JMS\SerializedName("exception")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\ExceptionResponse")
     */
    private $exception;

    /**
     * @return EstablishmentIdentityDetail
     */
    public function getMyInfo()
    {
        return $this->myInfo;
    }

    /**
     * @param EstablishmentIdentityDetail $myInfo
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
