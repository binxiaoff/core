<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\XmlRoot("content")
 */
class DirectorAnnouncementCollection
{
    /**
     * @var DirectorAnnouncement[]
     *
     * @JMS\SerializedName("annonces")
     * @JMS\XmlList(entry = "annonce")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\DirectorAnnouncement>")
     */
    private $announcements;

    /**
     * @return DirectorAnnouncement[]
     */
    public function getAnnouncements()
    {
        return $this->announcements;
    }

}
