<?php

namespace Unilend\Entity\External\Altares;

use JMS\Serializer\Annotation as JMS;

class EstablishmentIdentity extends Response
{
    /**
     * @var EstablishmentIdentityDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Entity\External\Altares\EstablishmentIdentityDetail")
     */
    protected $myInfo;
}
