<?php

namespace Unilend\Entity\External\Ellisphere;

use JMS\Serializer\Annotation as JMS;

class TreasuryTaxPrivilege
{
    /**
     * @var int
     *
     * @JMS\SerializedName("count")
     * @JMS\Type("integer")
     * @JMS\XmlAttribute
     */
    private $count;

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }
}
