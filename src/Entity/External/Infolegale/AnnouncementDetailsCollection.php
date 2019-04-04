<?php

namespace Unilend\Entity\External\Infolegale;

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
     * @JMS\Type("ArrayCollection<Unilend\Entity\External\Infolegale\AnnouncementDetails>")
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
