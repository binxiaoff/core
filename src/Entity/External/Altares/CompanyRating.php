<?php

namespace Unilend\Entity\External\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyRating extends Response
{
    /**
     * @var CompanyRatingDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Entity\External\Altares\CompanyRatingDetail")
     */
    protected $myInfo;
}
