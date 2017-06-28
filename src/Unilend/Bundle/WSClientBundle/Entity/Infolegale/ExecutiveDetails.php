<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class ExecutiveDetails
{
    /**
     * @var string
     *
     * @JMS\SerializedName("siren")
     * @JMS\Type("string")
     */
    private $siren;

    /**
     * @var string
     *
     * @JMS\SerializedName("raisonSociale")
     * @JMS\Type("string")
     */
    private $companyName;

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
     * @var Position
     *
     * @JMS\SerializedName("fonction")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Infolegale\Position")
     */
    private $position;

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
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $changeDate;

    /**
     * @return string
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
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
     * @return Position
     */
    public function getPosition()
    {
        return $this->position;
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
