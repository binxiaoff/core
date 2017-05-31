<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Altares;

use JMS\Serializer\Annotation as JMS;

class CompanyBalanceSheet
{
    /**
     * @var string
     *
     * @JMS\SerializedName("bilanId")
     * @JMS\Type("string")
     */
    private $balanceSheetId;

    /**
     * @var \DateTime
     *
     * @JMS\SerializedName("dateClotureN")
     * @JMS\Type("DateTime<'Y-m-d'>")
     */
    private $closeDate;

    /**
     * @var int
     *
     * @JMS\SerializedName("dureeN")
     * @JMS\Type("integer")
     */
    private $duration;

    /**
     * @var int
     *
     * @JMS\SerializedName("nbPoste")
     * @JMS\Type("integer")
     */
    private $postNumber;

    /**
     * @var BalancePost[]
     *
     * @JMS\SerializedName("posteList")
     * @JMS\Type("array<Unilend\Bundle\WSClientBundle\Entity\Altares\BalancePost>")
     */
    private $postList;

    /**
     * @return string
     */
    public function getBalanceSheetId()
    {
        return $this->balanceSheetId;
    }

    /**
     * @return \DateTime
     */
    public function getCloseDate()
    {
        return $this->closeDate->setTime(0, 0, 0);
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @return int
     */
    public function getPostNumber()
    {
        return $this->postNumber;
    }

    /**
     * @return BalancePost[]
     */
    public function getPostList()
    {
        return $this->postList;
    }
}
