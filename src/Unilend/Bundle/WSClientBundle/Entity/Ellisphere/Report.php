<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Ellisphere;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("report")
 */
class Report
{
    /**
     * @var string
     *
     * @JMS\SerializedName("reference")
     * @JMS\Type("string")
     */
    private $reference;
    /**
     * @var DefaultsModule
     *
     * @JMS\SerializedName("defaultsModule")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Ellisphere\DefaultsModule")
     */
    private $defaults;

    /**
     * @return DefaultsModule
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }
}
