<?php

namespace Unilend\Entity\External\Infolegale;

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
     * @JMS\Type("ArrayCollection<Unilend\Entity\External\Infolegale\Executive>")
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
