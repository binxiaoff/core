<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Euler;

use JMS\Serializer\Annotation as JMS;

class ExternalId
{
    /**
     * @JMS\SerializedName("ExternalId")
     * @JMS\Type("string")
     */
    private $externalId;

    /**
     * @JMS\SerializedName("ExternalService")
     * @JMS\Type("string")
     */
    private $externalService;

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @return string
     */
    public function getExternalService(): string
    {
        return $this->externalService;
    }
}
