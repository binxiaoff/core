<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class Executive extends Staff
{
    /**
     * @var int
     *
     * @JMS\SerializedName("execId")
     * @JMS\Type("integer")
     */
    private $executiveId;
    /**
     * @var string
     *
     * @JMS\SerializedName("adresse")
     * @JMS\Type("string")
     */
    private $address;

    /**
     * @var string
     *
     * @JMS\SerializedName("codePostal")
     * @JMS\Type("string")
     */
    private $postCode;

    /**
     * @var string
     *
     * @JMS\SerializedName("ville")
     * @JMS\Type("string")
     */
    private $city;

    /**
     * @var string
     *
     * @JMS\SerializedName("pays")
     * @JMS\Type("string")
     */
    private $country;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("dateNaissance")
     * @JMS\Type("DateTime<'d/m/Y'>")
     */
    protected $birthday;

    /**
     * @var string
     *
     * @JMS\SerializedName("mouvement")
     * @JMS\Type("string")
     */
    private $change;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("dateMouvement")
     * @JMS\Type("DateTime<'d/m/Y'>")
     */
    private $changeDate;

    /**
     * @return int
     */
    public function getExecutiveId()
    {
        return $this->executiveId;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getChange()
    {
        return $this->change;
    }

    /**
     * @return \DateTime
     */
    public function getChangeDate()
    {
        return $this->changeDate;
    }
}
