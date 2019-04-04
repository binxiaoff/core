<?php

namespace Unilend\Entity\External\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyIdentity extends Response
{
    /**
     * @var CompanyIdentityDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Entity\External\Altares\CompanyIdentityDetail")
     */
    protected $myInfo;
}
