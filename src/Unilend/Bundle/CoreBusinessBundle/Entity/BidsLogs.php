<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BidsLogs
 *
 * @ORM\Table(name="bids_logs", indexes={@ORM\Index(name="id_project", columns={"id_project"})})
 * @ORM\Entity
 */
class BidsLogs
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="debut", type="datetime", nullable=false)
     */
    private $debut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fin", type="datetime", nullable=false)
     */
    private $fin;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_bids_encours", type="integer", nullable=false)
     */
    private $nbBidsEncours;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_bids_ko", type="integer", nullable=false)
     */
    private $nbBidsKo;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_bids_ko", type="integer", nullable=false)
     */
    private $totalBidsKo;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_bids", type="integer", nullable=false)
     */
    private $totalBids;

    /**
     * @var float
     *
     * @ORM\Column(name="rate_max", type="float", precision=2, scale=1, nullable=false)
     */
    private $rateMax;

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
     * @ORM\Column(name="id_bid_log", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBidLog;



    /**
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return BidsLogs
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return integer
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set debut
     *
     * @param \DateTime $debut
     *
     * @return BidsLogs
     */
    public function setDebut($debut)
    {
        $this->debut = $debut;

        return $this;
    }

    /**
     * Get debut
     *
     * @return \DateTime
     */
    public function getDebut()
    {
        return $this->debut;
    }

    /**
     * Set fin
     *
     * @param \DateTime $fin
     *
     * @return BidsLogs
     */
    public function setFin($fin)
    {
        $this->fin = $fin;

        return $this;
    }

    /**
     * Get fin
     *
     * @return \DateTime
     */
    public function getFin()
    {
        return $this->fin;
    }

    /**
     * Set nbBidsEncours
     *
     * @param integer $nbBidsEncours
     *
     * @return BidsLogs
     */
    public function setNbBidsEncours($nbBidsEncours)
    {
        $this->nbBidsEncours = $nbBidsEncours;

        return $this;
    }

    /**
     * Get nbBidsEncours
     *
     * @return integer
     */
    public function getNbBidsEncours()
    {
        return $this->nbBidsEncours;
    }

    /**
     * Set nbBidsKo
     *
     * @param integer $nbBidsKo
     *
     * @return BidsLogs
     */
    public function setNbBidsKo($nbBidsKo)
    {
        $this->nbBidsKo = $nbBidsKo;

        return $this;
    }

    /**
     * Get nbBidsKo
     *
     * @return integer
     */
    public function getNbBidsKo()
    {
        return $this->nbBidsKo;
    }

    /**
     * Set totalBidsKo
     *
     * @param integer $totalBidsKo
     *
     * @return BidsLogs
     */
    public function setTotalBidsKo($totalBidsKo)
    {
        $this->totalBidsKo = $totalBidsKo;

        return $this;
    }

    /**
     * Get totalBidsKo
     *
     * @return integer
     */
    public function getTotalBidsKo()
    {
        return $this->totalBidsKo;
    }

    /**
     * Set totalBids
     *
     * @param integer $totalBids
     *
     * @return BidsLogs
     */
    public function setTotalBids($totalBids)
    {
        $this->totalBids = $totalBids;

        return $this;
    }

    /**
     * Get totalBids
     *
     * @return integer
     */
    public function getTotalBids()
    {
        return $this->totalBids;
    }

    /**
     * Set rateMax
     *
     * @param float $rateMax
     *
     * @return BidsLogs
     */
    public function setRateMax($rateMax)
    {
        $this->rateMax = $rateMax;

        return $this;
    }

    /**
     * Get rateMax
     *
     * @return float
     */
    public function getRateMax()
    {
        return $this->rateMax;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return BidsLogs
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
     * @return BidsLogs
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
     * Get idBidLog
     *
     * @return integer
     */
    public function getIdBidLog()
    {
        return $this->idBidLog;
    }
}
