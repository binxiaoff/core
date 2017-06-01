<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class ExceptionResponse
{
    /**
     * @JMS\SerializedName("code")
     * @JMS\Type("string")
     */
    private $code;

    /**
     *  @JMS\SerializedName("description")
     * @JMS\Type("string")
     */
    private $description;

    /**
     * @var string
     *
     * @JMS\SerializedName("erreur")
     * @JMS\Type("string")
     */
    private $errorMessage;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}