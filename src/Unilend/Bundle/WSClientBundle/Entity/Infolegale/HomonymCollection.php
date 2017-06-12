<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("content")
 */
class HomonymCollection
{

    /**
     * @var Homonym[]
     *
     * @JMS\SerializedName("homonymes")
     * @JMS\XmlList(entry = "homonyme")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\Homonym>")
     */
    private $homonyms;

    /**
     * @return Homonym[]
     */
    public function getHomonyms()
    {
        return $this->homonyms;
    }

}
