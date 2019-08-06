<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="redirections")
 * @ORM\Entity
 */
class Redirections
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED  = 1;
    /**
     * @var string
     *
     * @ORM\Column(name="to_slug", type="string", length=191)
     */
    private $toSlug;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
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
     * @return string
     */
    public function getToSlug()
    {
        return $this->toSlug;
    }

    /**
     * @param int $type
     *
     * @return Redirections
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $status
     *
     * @return Redirections
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
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
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
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
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
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
     * @return string
     */
    public function getIdLangue()
    {
        return $this->idLangue;
    }

    /**
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
     * @return string
     */
    public function getFromSlug()
    {
        return $this->fromSlug;
    }
}
