<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyIdentity extends Response
{
    /**
     * @var CompanyIdentityDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyIdentityDetail")
     */
    protected $myInfo;
}
