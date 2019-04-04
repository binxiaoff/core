<?php

namespace Unilend\Entity\External\Infolegale;

use JMS\Serializer\Annotation as JMS;

class AnnouncementDetails
{
    /**
     * @var string
     *
     * @JMS\SerializedName("annonceInfo/adID")
     * @JMS\Type("string")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("annonceInfo/dateParution")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $publishedDate;

    /**
     * @var AnnouncementEvent[]
     *
     * @JMS\SerializedName("evenements")
     * @JMS\XmlList(entry = "evenement")
     * @JMS\Type("ArrayCollection<Unilend\Entity\External\Infolegale\AnnouncementEvent>")
     */
    private $announcementEvents;

    /**
     * @var ContentiousParticipant[]
     *
     * @JMS\SerializedName("acteursContentieux")
     * @JMS\XmlList(entry = "acteurContentieux")
     * @JMS\Type("ArrayCollection<Unilend\Entity\External\Infolegale\ContentiousParticipant>")
     */
    private $contentiousParticipants;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getPublishedDate()
    {
        return $this->publishedDate;
    }

    /**
     * @return AnnouncementEvent[]
     */
    public function getAnnouncementEvents()
    {
        return $this->announcementEvents;
    }

    /**
     * @return ContentiousParticipant[]
     */
    public function getContentiousParticipants()
    {
        return $this->contentiousParticipants;
    }
}
