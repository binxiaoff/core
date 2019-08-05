<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanySector.
 *
 * @ORM\Table(name="company_sector")
 * @ORM\Entity
 */
class CompanySector
{
    /**
     * @var string
     *
     * @ORM\Column(name="sector", type="string", length=100)
     */
    private $sector;

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
     * @var int
     *
     * @ORM\Column(name="id_company_sector", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCompanySector;

    /**
     * Set sector.
     *
     * @param string $sector
     *
     * @return CompanySector
     */
    public function setSector($sector)
    {
        $this->sector = $sector;

        return $this;
    }

    /**
     * Get sector.
     *
     * @return string
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * Set added.
     *
     * @param \DateTime $added
     *
     * @return CompanySector
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added.
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated.
     *
     * @param \DateTime $updated
     *
     * @return CompanySector
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idCompanySector.
     *
     * @return int
     */
    public function getIdCompanySector()
    {
        return $this->idCompanySector;
    }
}
