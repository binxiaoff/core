<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BorrowingMotive
 *
 * @ORM\Table(name="borrowing_motive")
 * @ORM\Entity
 */
class BorrowingMotive
{
    const ID_MOTIVE_PURCHASE_MATERIAL   = 1;
    const ID_MOTIVE_ACQUISITION_MERGER  = 2;
    const ID_MOTIVE_DEVELOPMENT         = 3;
    const ID_MOTIVE_REAL_ESTATE         = 4;
    const ID_MOTIVE_WORK                = 5;
    const ID_MOTIVE_CASH_FLOW           = 6;
    const ID_MOTIVE_OTHER               = 7;
    const ID_MOTIVE_FRANCHISER_CREATION = 8;
    const ID_MOTIVE_SHARE_BUYBACK       = 9;

    /**
     * @var string
     *
     * @ORM\Column(name="motive", type="string", length=100, nullable=false)
     */
    private $motive;

    /**
     * @var integer
     *
     * @ORM\Column(name="rank", type="integer", nullable=false)
     */
    private $rank;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_motive", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idMotive;

    /**
     * Set motive
     *
     * @param string $motive
     *
     * @return BorrowingMotive
     */
    public function setMotive($motive)
    {
        $this->motive = $motive;

        return $this;
    }

    /**
     * Get motive
     *
     * @return string
     */
    public function getMotive()
    {
        return $this->motive;
    }

    /**
     * Set rank
     *
     * @param string $rank
     *
     * @return BorrowingMotive
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return string
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return BorrowingMotive
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return BorrowingMotive
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idMotive
     *
     * @return integer
     */
    public function getIdMotive()
    {
        return $this->idMotive;
    }
}
