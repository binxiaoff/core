<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\{RoleableTrait, TimestampableTrait};

/**
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectParticipant
{
    use RoleableTrait {
        removeRole as private baseRemoveRole;
    }

    use TimestampableTrait;

    // Use COMPANY_ prefix to distinguish it from Symfony user's roles
    public const ROLE_PROJECT_ARRANGER         = 'ROLE_PROJECT_ARRANGER'; // The company who arranges a loan syndication.
    public const ROLE_PROJECT_DEPUTY_ARRANGER  = 'ROLE_PROJECT_DEPUTY_ARRANGER';
    public const ROLE_PROJECT_RUN              = 'ROLE_PROJECT_RUN'; // The abbreviation of Responsable Unique de Notation, who gives a note on the borrower.
    public const ROLE_PROJECT_LENDER           = 'ROLE_PROJECT_LENDER';
    public const ROLE_PROJECT_LOAN_OFFICER     = 'ROLE_PROJECT_LOAN_OFFICER';
    public const ROLE_PROJECT_SECURITY_TRUSTEE = 'ROLE_PROJECT_SECURITY_TRUSTEE';

    public const ROLE_DEFAULT = self::ROLE_PROJECT_LENDER;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectParticipants")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", inversedBy="projectParticipants")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $company;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project|null $project
     *
     * @return ProjectParticipant
     */
    public function setProject(?Project $project): ProjectParticipant
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
     * @return ProjectParticipant
     */
    public function setCompany(Companies $company): ProjectParticipant
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArranger(): bool
    {
        return in_array(self::ROLE_PROJECT_ARRANGER, $this->getRoles());
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function removeRole(string $role)
    {
        $this->baseRemoveRole($role);

        if (0 === count($this->roles)) {
            $this->getProject()->removeProjectParticipant($this);
        }

        return $this;
    }
}
