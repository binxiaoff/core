<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class DirectorAnnouncement
{
    /**
     * @var string
     *
     * @JMS\SerializedName("adID")
     * @JMS\Type("string")
     */
    private $id;

    /**
     * @var string
     *
     * @JMS\SerializedName("source")
     * @JMS\Type("string")
     */
    private $source;

    /**
     * @var string
     *
     * @JMS\SerializedName("origine")
     * @JMS\Type("string")
     */
    private $origine;

    /**
     * @var string
     *
     * @JMS\SerializedName("categorie")
     * @JMS\Type("string")
     */
    private $category;

    /**
     * @var int
     *
     * @JMS\SerializedName("codeEvenement")
     * @JMS\Type("int")
     */
    private $eventCode;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("dateParution")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $publishedDate;

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
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getOrigine()
    {
        return $this->origine;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return int
     */
    public function getEventCode()
    {
        return $this->eventCode;
    }

    /**
     * @return \DateTime
     */
    public function getPublishedDate()
    {
        return $this->publishedDate;
    }

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
}
