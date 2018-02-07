<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares\RiskDataMonitoring;

use JMS\Serializer\Annotation as JMS;

class Notification
{
    /**
     * @JMS\SerializedName("dateCreation")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $creationDate;

    /**
     * @JMS\SerializedName("etatIhm")
     * @JMS\Type("string")
     */
    private $status;

    /**
     * @JMS\SerializedName("id")
     * @JMS\Type("integer")
     */
    private $id;

    /**
     * @JMS\SerializedName("siren")
     * @JMS\Type("string")
     */
    private $siren;

    /**
     * @JMS\SerializedName("evenementList")
     * @JMS\Type("array<Unilend\Bundle\WSClientBundle\Entity\Altares\RiskDataMonitoring\EventDetail>")
     */
    private $eventList;

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * @return mixed
     */
    public function getEventList()
    {
        return $this->eventList;
    }

    /**
     * @param mixed $creationDate
     */
    public function setCreationDate($creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @param mixed $siren
     */
    public function setSiren($siren): void
    {
        $this->siren = $siren;
    }

    /**
     * @param mixed $eventList
     */
    public function setEventList($eventList): void
    {
        $this->eventList = $eventList;
    }
}
