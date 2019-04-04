<?php

namespace Unilend\Entity\External\Altares;

use JMS\Serializer\Annotation as JMS;

class BalanceSheetList extends Response
{
    /**
     * @var BalanceSheetListDetail
     *
     * @JMS\SerializedName("myInfo")
     * @JMS\Type("Unilend\Entity\External\Altares\BalanceSheetListDetail")
     */
    protected $myInfo;
}
