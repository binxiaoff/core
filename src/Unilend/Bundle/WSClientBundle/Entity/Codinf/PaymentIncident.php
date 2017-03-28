<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Codinf;

use JMS\Serializer\Annotation as JMS;

class PaymentIncident
{
    /**
     * @JMS\SerializedName("numero")
     * @JMS\Type("string")
     */
    private $number;

    /**
     * @JMS\Type("DateTime<'Y-m-d H:i:s'>")
     */
    private $date;

    /**
     * @JMS\Type("string")
     */
    private $type;

    /**
     * @JMS\Type("string")
     */
    private $status;

    /**
     * PaymentIncident constructor.
     * @param $number
     * @param $date
     * @param $type
     * @param $status
     */
    public function __construct($number, $date, $type, $status)
    {
        $this->number       = $number;
        $this->date         = $date;
        $this->type         = $type;
        $this->status       = $status;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }
}
