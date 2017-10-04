<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class DirectorAnnouncement
{
    const PEJORATIVE_EVENT_CODE = [
        5110, 5111, 5120, 5121, 5125, 5126, 5130, 5131, 5132, 5210, 5211, 5212, 5213, 5220, 5221, 5222,
        5223, 5224, 5225, 5226, 5227, 5228, 5229, 5230, 5231, 5232, 5233, 5234, 5235, 5236, 5237, 5299,
        5300, 5310, 5320, 5321, 5325, 5330, 5340, 5345, 5350, 5360, 5370, 5380, 5381, 5382, 5390, 5391,
        5392, 5393, 5399, 5410, 5420, 5421, 5430, 5431, 5440, 5450, 5510, 5520, 5530, 5540, 5550, 5551,
        5910, 5911, 5912, 5913, 5914, 5915, 5916, 5917, 7130, 8440, 6451, 6452, 6450, 6320, 6321
    ];

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
     * @var string
     *
     * @JMS\SerializedName("codeEvenement")
     * @JMS\Type("string")
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
     * @return string
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
        return $this->publishedDate->setTime(0, 0, 0);
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
