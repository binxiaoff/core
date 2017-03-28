<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infogreffe;

use JMS\Serializer\Annotation as JMS;

class Subscription
{
    /**
     * @var array
     * @JMS\SerializedName("@attributes")
     * @JMS\Type("array<string, boolean>")
     */
    private $valid;

    /**
     * @JMS\SerializedName("date_inscription")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $subscriptionDate;

    /**
     * @JMS\SerializedName("numero_inscription")
     * @JMS\Type("integer")
     */
    private $subscritpionNumber;

    /**
     * @JMS\SerializedName("montant_creance")
     * @JMS\Type("float")
     */
    private $amount;

    /**
     * @return boolean
     */
    public function getValid()
    {
        return $this->valid['valide'];
    }

    /**
     * @return mixed
     */
    public function getSubscriptionDate()
    {
        return $this->subscriptionDate;
    }

    /**
     * @return mixed
     */
    public function getSubscritpionNumber()
    {
        return $this->subscritpionNumber;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }
}