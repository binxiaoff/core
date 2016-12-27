<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Redirections
 *
 * @ORM\Table(name="redirections")
 * @ORM\Entity
 */
class Redirections
{
    /**
     * @var string
     *
     * @ORM\Column(name="to_slug", type="string", length=191, nullable=false)
     */
    private $toSlug;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

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
     * @var string
     *
     * @ORM\Column(name="id_langue", type="string", length=5)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idLangue;

    /**
     * @var string
     *
     * @ORM\Column(name="from_slug", type="string", length=191)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $fromSlug;



    /**
     * Set toSlug
     *
     * @param string $toSlug
     *
     * @return Redirections
     */
    public function setToSlug($toSlug)
    {
        $this->toSlug = $toSlug;

        return $this;
    }

    /**
     * Get toSlug
     *
     * @return string
     */
    public function getToSlug()
    {
        return $this->toSlug;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Redirections
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Redirections
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Redirections
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
     * @return Redirections
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
     * Set idLangue
     *
     * @param string $idLangue
     *
     * @return Redirections
     */
    public function setIdLangue($idLangue)
    {
        $this->idLangue = $idLangue;

        return $this;
    }

    /**
     * Get idLangue
     *
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }

    /**
     * Set fromSlug
     *
     * @param string $fromSlug
     *
     * @return Redirections
     */
    public function setFromSlug($fromSlug)
    {
        $this->fromSlug = $fromSlug;

        return $this;
    }

    /**
     * Get fromSlug
     *
     * @return string
     */
    public function getFromSlug()
    {
        return $this->fromSlug;
    }
}
