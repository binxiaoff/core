<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class EstablishmentIdentityDetail
{
    /**
     * @JMS\SerializedName("telephone")
     * @JMS\Type("string")
     */
    private $phoneNumber;

    /**
     * @param string $phoneNumber
     */
    public function __construct($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }
}
