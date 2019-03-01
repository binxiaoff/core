<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

trait ProjectCompanyAccessControllable
{
    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $company;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $added;

    /**
     * @return Projects
     */
    public function getProject(): Projects
    {
        return $this->project;
    }

    /**
     * @param Projects $project
     *
     * @return self
     */
    public function setProject(Projects $project): self
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Companies
     */
    public function getCompany(): Companies
    {
        return $this->company;
    }

    /**
     * @param Companies $company
     *
     * @return self
     */
    public function setCompany(Companies $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime $added
     *
     * @return self
     */
    public function setAdded(\DateTime $added): self
    {
        $this->added = $added;

        return $this;
    }
}
