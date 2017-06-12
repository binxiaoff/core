<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class Executive
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
     * @JMS\SerializedName("civilite")
     * @JMS\Type("string")
     */
    private $title;

    /**
     * @var string
     *
     * @JMS\SerializedName("nom")
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @var string
     *
     * @JMS\SerializedName("prenom")
     * @JMS\Type("string")
     */
    private $firstName;

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
    private $birthday;

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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
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
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
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
