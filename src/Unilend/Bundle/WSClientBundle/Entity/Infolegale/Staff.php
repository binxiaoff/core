<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class Staff
{
    /**
     * @var string
     *
     * @JMS\SerializedName("civilite")
     * @JMS\Type("string")
     */
    protected $title;

    /**
     * @var string
     *
     * @JMS\SerializedName("nom")
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var string
     *
     * @JMS\SerializedName("prenom")
     * @JMS\Type("string")
     */
    protected $firstName;

    /**
     * @var Position
     *
     * @JMS\SerializedName("fonction")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Infolegale\Position")
     */
    protected $position;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("dateNaissance")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    protected $birthday;

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
     * @return Position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }
}
