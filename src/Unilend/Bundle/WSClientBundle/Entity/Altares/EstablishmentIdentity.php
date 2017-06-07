<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class EstablishmentIdentity extends Response
{
    /**
     * @var EstablishmentIdentityDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\EstablishmentIdentityDetail")
     */
    protected $myInfo;
}
