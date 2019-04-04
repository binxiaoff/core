<?php

namespace Unilend\Entity\External\Altares;

use JMS\Serializer\Annotation as JMS;

class FinancialSummary extends Response
{
    /**
     * @var FinancialSummaryListDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Entity\External\Altares\FinancialSummaryListDetail")
     */
    protected $myInfo;
}
