<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyRating extends Response
{
    /**
     * @var CompanyRatingDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRatingDetail")
     */
    protected $myInfo;
}
