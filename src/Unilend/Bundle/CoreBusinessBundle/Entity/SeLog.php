<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SeLog
 *
 * @ORM\Table(name="se_log")
 * @ORM\Entity
 */
class SeLog
{
    /**
     * @var string
     *
     * @ORM\Column(name="keyword", type="text", length=16777215, nullable=false)
     */
    private $keyword;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=20, nullable=false)
     */
    private $ip;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_results", type="integer", nullable=false)
     */
    private $nbResults;

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
     * @ORM\Column(name="id_log", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLog;



    /**
     * Set keyword
     *
     * @param string $keyword
     *
     * @return SeLog
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * Get keyword
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return SeLog
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set nbResults
     *
     * @param integer $nbResults
     *
     * @return SeLog
     */
    public function setNbResults($nbResults)
    {
        $this->nbResults = $nbResults;

        return $this;
    }

    /**
     * Get nbResults
     *
     * @return integer
     */
    public function getNbResults()
    {
        return $this->nbResults;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return SeLog
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
     * @return SeLog
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
     * Get idLog
     *
     * @return integer
     */
    public function getIdLog()
    {
        return $this->idLog;
    }
}
