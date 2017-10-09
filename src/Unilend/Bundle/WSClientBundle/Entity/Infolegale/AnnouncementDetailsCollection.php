<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("content")
 */
class AnnouncementDetailsCollection
{
    /**
     * @var AnnouncementDetails[]
     *
     * @JMS\SerializedName("annonces")
     * @JMS\XmlList(entry = "annonce")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\AnnouncementDetails>")
     */
    private $announcementDetails;

    /**
     * @return AnnouncementDetails[]
     */
    public function getAnnouncementDetails()
    {
        return $this->announcementDetails;
    }
}
