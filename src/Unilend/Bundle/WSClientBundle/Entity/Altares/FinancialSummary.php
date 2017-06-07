<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class FinancialSummary extends Response
{
    /**
     * @var FinancialSummaryListDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\FinancialSummaryListDetail")
     */
    protected $myInfo;
}
