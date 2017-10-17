<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("content")
 */
class AnnouncementCollection
{
    /**
     * @var Announcement[]
     *
     * @JMS\SerializedName("annonces")
     * @JMS\XmlList(entry = "annonce")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\Announcement>")
     */
    private $announcements;

    /**
     * @return Announcement[]
     */
    public function getAnnouncements()
    {
        return $this->announcements;
    }
}
