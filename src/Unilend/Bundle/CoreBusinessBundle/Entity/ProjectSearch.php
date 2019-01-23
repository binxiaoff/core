<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectSearch
 *
 * @ORM\Table(name="project_search")
 * @ORM\Entity(readOnly=true)
 */
class ProjectSearch
{
    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     * @ORM\Id
     */
    private $idProject;

    /**
     * @var CompanySector
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanySector")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sector", referencedColumnName="id_company_sector", nullable=true)
     * })
     */
    private $sector;

    /**
     * @var float
     *
     * @ORM\Column(name="sortDate", type="decimal", precision=14, scale=4, nullable=true)
     */
    private $sortDate;

    /**
     * @var float
     *
     * @ORM\Column(name="avgRate", type="decimal", precision=39, scale=5, nullable=false)
     */
    private $avgRate;

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
     * Get sector
     *
     * @return CompanySector|null
     */
    public function getSector(): ?CompanySector
    {
        return $this->sector;
    }

    /**
     * Get sortDate
     *
     * @return float|null
     */
    public function getSortDate(): ?float
    {
        return $this->sortDate;
    }

    /**
     * Get avgRate
     *
     * @return float
     */
    public function getAvgRate(): float
    {
        return $this->avgRate;
    }
}
