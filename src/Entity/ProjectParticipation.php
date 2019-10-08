<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Unilend\Entity\Embeddable\Permission;
use Unilend\Entity\Traits\{BlamableAddedTrait, RoleableTrait, TimestampableTrait};
use Unilend\Service\User\RealUserFinder;

/**
 * @ApiResource
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectParticipation
{
    use RoleableTrait {
        removeRole as private baseRemoveRole;
    }
    use TimestampableTrait;
    use BlamableAddedTrait;

    // Use COMPANY_ prefix to distinguish it from Symfony user's roles
    public const ROLE_PROJECT_ARRANGER         = 'ROLE_PROJECT_ARRANGER'; // The company who arranges a loan syndication.
    public const ROLE_PROJECT_DEPUTY_ARRANGER  = 'ROLE_PROJECT_DEPUTY_ARRANGER';
    public const ROLE_PROJECT_RUN              = 'ROLE_PROJECT_RUN'; // The abbreviation of Responsable Unique de Notation, who gives a note on the borrower.
    public const ROLE_PROJECT_LENDER           = 'ROLE_PROJECT_LENDER';
    public const ROLE_PROJECT_LOAN_OFFICER     = 'ROLE_PROJECT_LOAN_OFFICER';
    public const ROLE_PROJECT_SECURITY_TRUSTEE = 'ROLE_PROJECT_SECURITY_TRUSTEE';

    private const DEFAULT_ROLE = self::ROLE_PROJECT_LENDER;

    private const STATUS_NOT_CONSULTED = 0;
    private const STATUS_CONSULTED     = 10;
    private const STATUS_REFUSED       = 20;

    private const DEFAULT_STATUS = self::STATUS_NOT_CONSULTED;

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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectParticipations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", inversedBy="projectParticipations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $company;

    /**
     * @var ProjectParticipationContact[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationContact", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipationContacts;

    /**
     * @var bool
     *
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    private $currentStatus = self::DEFAULT_STATUS;

    /**
     * @var Permission
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Permission", columnPrefix=false)
     */
    private $permission;

    /**
     * ProjectParticipation constructor.
     */
    public function __construct()
    {
        $this->projectParticipationContacts = new ArrayCollection();
        $this->permission                   = new Permission();
    }

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
     * @return ProjectParticipation
     */
    public function setProject(?Project $project): ProjectParticipation
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
     * @return ProjectParticipation
     */
    public function setCompany(Companies $company): ProjectParticipation
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArranger(): bool
    {
        return in_array(self::ROLE_PROJECT_ARRANGER, $this->getRoles(), true);
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function removeRole(string $role): ProjectParticipation
    {
        $this->baseRemoveRole($role);

        if (0 === count($this->roles)) {
            $this->getProject()->removeProjectParticipation($this);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasBid(): bool
    {
        return 0 > count($this->project->getBids(null, $this->company));
    }

    /**
     * @return bool
     */
    public function isRefused(): bool
    {
        return $this->currentStatus === static::STATUS_REFUSED;
    }

    /**
     * @return bool
     */
    public function isConsulted(): bool
    {
        return $this->currentStatus >= static::STATUS_CONSULTED;
    }

    /**
     * @return ProjectParticipation
     */
    public function setRefused(): ProjectParticipation
    {
        if ($this->hasBid()) {
            throw new DomainException('It is impossible to refuse after making a bid');
        }

        $this->currentStatus = static::STATUS_REFUSED;

        return $this;
    }

    /**
     * @return ProjectParticipation
     */
    public function setConsulted(): ProjectParticipation
    {
        $this->currentStatus = ($this->currentStatus === static::STATUS_NOT_CONSULTED) ? static::STATUS_CONSULTED : $this->currentStatus;

        return $this;
    }

    /**
     * @return ProjectParticipationContact[]|ArrayCollection
     */
    public function getProjectParticipationContacts(): iterable
    {
        return $this->projectParticipationContacts;
    }

    /**
     * @param Clients        $client
     * @param RealUserFinder $realUserFinder
     *
     * @return ProjectParticipationContact
     */
    public function addProjectParticipationContact(Clients $client, RealUserFinder $realUserFinder): ProjectParticipationContact
    {
        $projectParticipationContact = (new ProjectParticipationContact())
            ->setAddedByValue($realUserFinder)
            ->setProjectParticipation($this)
            ->setClient($client)
        ;

        $this->projectParticipationContacts->add($projectParticipationContact);

        return $projectParticipationContact;
    }

    /**
     * @return Permission
     */
    public function getPermission(): Permission
    {
        return $this->permission;
    }
}
