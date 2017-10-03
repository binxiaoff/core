<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class AnnouncementEvent
{
    /**
     * @var int
     *
     * @JMS\SerializedName("codeEvenement")
     * @JMS\Type("int")
     */
    private $code;

    /**
     * @var string
     *
     * @JMS\SerializedName("labelEvenement")
     * @JMS\Type("string")
     */
    private $label;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("dateDecision")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $resolutionDate;

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getlabel()
    {
        return $this->label;
    }

    /**
     * @return \DateTime
     */
    public function getResolutionDate()
    {
        return $this->resolutionDate;
    }
}
