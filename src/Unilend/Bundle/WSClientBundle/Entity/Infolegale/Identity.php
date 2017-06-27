<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infolegale;

use JMS\Serializer\Annotation as JMS;

class Identity
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
     * @var Staff[]
     *
     * @JMS\SerializedName("dirigeants")
     * @JMS\XmlList(entry = "dirigeant")
     * @JMS\Type("ArrayCollection<Unilend\Bundle\WSClientBundle\Entity\Infolegale\Staff>")
     */
    private $directors;

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
     * @return Staff[]
     */
    public function getDirectors()
    {
        return $this->directors;
    }
}
