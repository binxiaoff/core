<?php

namespace Unilend\Entity\External\Infolegale;

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
     * @JMS\Type("ArrayCollection<Unilend\Entity\External\Infolegale\Mandate>")
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
