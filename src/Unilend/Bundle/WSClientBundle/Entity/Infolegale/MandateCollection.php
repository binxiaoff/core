<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("content")
 */
class MandateCollection
{
    /**
     * @var Mandate[]
     *
     * @JMS\SerializedName("mandats")
     * @JMS\XmlList(entry = "mandat")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\Mandate>")
     */
    private $mandates;

    /**
     * @return Mandate[]
     */
    public function getMandates()
    {
        return $this->mandates;
    }
}
