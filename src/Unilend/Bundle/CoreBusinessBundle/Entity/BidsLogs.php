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
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
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
     * @var int
     *
     * @ORM\Column(name="nb_bids_encours", type="integer", nullable=false)
     */
    private $nbBidsEncours;

    /**
     * @var int
     *
     * @ORM\Column(name="nb_bids_ko", type="integer", nullable=false)
     */
    private $nbBidsKo;

    /**
     * @var int
     *
     * @ORM\Column(name="total_bids_ko", type="integer", nullable=false)
     */
    private $totalBidsKo;

    /**
     * @var int
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
     * @var int
     *
     * @ORM\Column(name="id_bid_log", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBidLog;



    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return BidsLogs
     */
    public function setIdProject(Projects $idProject): BidsLogs
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getIdProject(): Projects
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
    public function setDebut(\DateTime $debut): BidsLogs
    {
        $this->debut = $debut;

        return $this;
    }

    /**
     * Get debut
     *
     * @return \DateTime
     */
    public function getDebut(): \DateTime
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
    public function setFin(\DateTime $fin): BidsLogs
    {
        $this->fin = $fin;

        return $this;
    }

    /**
     * Get fin
     *
     * @return \DateTime
     */
    public function getFin(): \DateTime
    {
        return $this->fin;
    }

    /**
     * Set nbBidsEncours
     *
     * @param int $nbBidsEncours
     *
     * @return BidsLogs
     */
    public function setNbBidsEncours(int $nbBidsEncours): BidsLogs
    {
        $this->nbBidsEncours = $nbBidsEncours;

        return $this;
    }

    /**
     * Get nbBidsEncours
     *
     * @return int
     */
    public function getNbBidsEncours(): int
    {
        return $this->nbBidsEncours;
    }

    /**
     * Set nbBidsKo
     *
     * @param int $nbBidsKo
     *
     * @return BidsLogs
     */
    public function setNbBidsKo(int $nbBidsKo): BidsLogs
    {
        $this->nbBidsKo = $nbBidsKo;

        return $this;
    }

    /**
     * Get nbBidsKo
     *
     * @return int
     */
    public function getNbBidsKo(): int
    {
        return $this->nbBidsKo;
    }

    /**
     * Set totalBidsKo
     *
     * @param int $totalBidsKo
     *
     * @return BidsLogs
     */
    public function setTotalBidsKo(int $totalBidsKo): BidsLogs
    {
        $this->totalBidsKo = $totalBidsKo;

        return $this;
    }

    /**
     * Get totalBidsKo
     *
     * @return int
     */
    public function getTotalBidsKo(): int
    {
        return $this->totalBidsKo;
    }

    /**
     * Set totalBids
     *
     * @param int $totalBids
     *
     * @return BidsLogs
     */
    public function setTotalBids(int $totalBids): BidsLogs
    {
        $this->totalBids = $totalBids;

        return $this;
    }

    /**
     * Get totalBids
     *
     * @return int
     */
    public function getTotalBids(): int
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
    public function setRateMax(float $rateMax): BidsLogs
    {
        $this->rateMax = $rateMax;

        return $this;
    }

    /**
     * Get rateMax
     *
     * @return float
     */
    public function getRateMax(): float
    {
        return $this->rateMax;
    }

    /**
     * Get idBidLog
     *
     * @return int
     */
    public function getIdBidLog(): int
    {
        return $this->idBidLog;
    }
}
