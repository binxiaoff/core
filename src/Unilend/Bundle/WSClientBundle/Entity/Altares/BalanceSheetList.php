<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class BalanceSheetList extends Response
{
    /**
     * @var BalanceSheetListDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetListDetail")
     */
    protected $myInfo;
}
