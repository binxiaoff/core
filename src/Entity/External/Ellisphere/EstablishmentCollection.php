<?php

namespace Unilend\Entity\External\Ellisphere;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("response")
 */
class EstablishmentCollection
{
    /**
     * @var int
     *
     * @JMS\SerializedName("totalHits")
     * @JMS\Type("integer")
     */
    private $totalHits;

    /**
     * @return int
     */
    public function getTotalHits()
    {
        return $this->totalHits;
    }
}
