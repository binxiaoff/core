<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Ellisphere;

use JMS\Serializer\Annotation as JMS;

class SocialSecurityPrivilege
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
