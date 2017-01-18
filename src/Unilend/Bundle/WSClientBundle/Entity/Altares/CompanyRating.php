<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyRating
{
    /**
     * @JMS\SerializedName("dateValeur")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $scoreDate;

    /**
     * @JMS\SerializedName("scoreSectorielVingt")
     * @JMS\Type("integer")
     */
    private $sectoralScore20;

    /**
     * @JMS\SerializedName("scoreSectorielCent")
     * @JMS\Type("integer")
     */
    private $sectoralScore100;

    /**
     * @JMS\SerializedName("scoreVingt")
     * @JMS\Type("integer")
     */
    private $score20;

    /**
     * @JMS\SerializedName("scoreCent")
     * @JMS\Type("integer")
     */
    private $score100;

    /**
     * @return \DateTime
     */
    public function getScoreDate()
    {
        return $this->scoreDate;
    }

    /**
     * @return mixed
     */
    public function getSectoralScore20()
    {
        return $this->sectoralScore20;
    }

    /**
     * @return mixed
     */
    public function getScore20()
    {
        return $this->score20;
    }

    /**
     * @return mixed
     */
    public function getSectoralScore100()
    {
        return $this->sectoralScore100;
    }

    /**
     * @return mixed
     */
    public function getScore100()
    {
        return $this->score100;
    }
}
