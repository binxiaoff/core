<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares\RiskDataMonitoring;

use JMS\Serializer\Annotation as JMS;

class EventDetail
{
    /**
     * @JMS\SerializedName("dateEvenement")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $eventDate;

    /**
     * @JMS\SerializedName("nomEvenementCode")
     * @JMS\Type("string")
     */
    private $eventCode;

    /**
     * @JMS\SerializedName("nomEvenementLabel")
     * @JMS\Type("string")
     */
    private $label;

    /**
     * @return mixed
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * @return mixed
     */
    public function getEventCode()
    {
        return $this->eventCode;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $eventDate
     */
    public function setEventDate($eventDate): void
    {
        $this->eventDate = $eventDate;
    }

    /**
     * @param mixed $eventCode
     */
    public function setEventCode($eventCode): void
    {
        $this->eventCode = $eventCode;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label): void
    {
        $this->label = $label;
    }


}
