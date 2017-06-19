<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("content")
 */
class ExecutiveCollection
{
    /**
     * @var Executive[]
     *
     * @JMS\SerializedName("dirigeants")
     * @JMS\XmlList(entry = "dirigeant")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\Executive>")
     */
    private $executives;

    /**
     * @return Executive[]
     */
    public function getExecutives()
    {
        return $this->executives;
    }
}
