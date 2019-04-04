<?php

namespace Unilend\Entity\External\Altares;

use JMS\Serializer\Annotation as JMS;

class Response
{
    protected $myInfo;

    /**
     * @JMS\SerializedName("exception")
     * @JMS\Type("Unilend\Entity\External\Altares\ExceptionResponse")
     */
    protected $exception;

    public function getMyInfo()
    {
        return $this->myInfo;
    }

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
