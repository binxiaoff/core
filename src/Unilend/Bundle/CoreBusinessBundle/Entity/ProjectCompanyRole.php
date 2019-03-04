<?php
declare(strict_types=1);

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\Traits\Roleable;

/**
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectCompanyRole
{
    use Roleable;

    // use FUNCTION_ to distinguish it from Symfony user's roles
    const FUNCTION_ARRANGER = 'FUNCTION_ARRANGER';
    const FUNCTION_AGENT    = 'FUNCTION_AGENT';
    const FUNCTION_LENDER   = 'FUNCTION_LENDER';
    const FUNCTION_RUN      = 'FUNCTION_RUN'; //Responsable Unique de Notation

    const ROLE_DEFAULT = self::FUNCTION_LENDER;

    const ALL_ROLES = [self::FUNCTION_ARRANGER, self::FUNCTION_AGENT, self::FUNCTION_LENDER, self::FUNCTION_RUN];

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects", inversedBy="ProjectCompanyRoles")
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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

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
     * @return ProjectCompanyRole
     */
    public function setProject(Projects $project): ProjectCompanyRole
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
     * @return ProjectCompanyRole
     */
    public function setCompany(Companies $company): ProjectCompanyRole
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
     * @return ProjectCompanyRole
     */
    public function setAdded(\DateTime $added): ProjectCompanyRole
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     *
     * @return ProjectCompanyRole
     */
    public function setUpdated(\DateTime $updated): ProjectCompanyRole
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
