<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Embeddable\{Money, NullableMoney, Permission};
use Unilend\Entity\Traits\{BlamableAddedTrait, RoleableTrait, TimestampableTrait};
use Unilend\Service\User\RealUserFinder;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {"normalization_context": {"groups": "projectParticipation:list"}},
 *         "post"
 *     }
 * )
 * @ApiFilter("Unilend\Filter\ArrayFilter", properties={"roles"})
 * @ApiFilter("Unilend\Filter\CountFilter", properties={"project.projectOffers"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter", properties={"project.currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter", properties={"project.currentStatus.status"})
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectParticipationRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectParticipation
{
    use RoleableTrait {
        removeRole as private baseRemoveRole;
    }
    use TimestampableTrait;
    use BlamableAddedTrait;

    public const DUTY_PROJECT_PARTICIPATION_ARRANGER         = 'DUTY_PROJECT_PARTICIPATION_ARRANGER'; // The company who arranges a loan syndication.
    public const DUTY_PROJECT_PARTICIPATION_DEPUTY_ARRANGER  = 'DUTY_PROJECT_PARTICIPATION_DEPUTY_ARRANGER';
    public const DUTY_PROJECT_PARTICIPATION_RUN              = 'DUTY_PROJECT_PARTICIPATION_RUN'; // Responsable Unique de Notation, who gives a note on the borrower.
    public const DUTY_PROJECT_PARTICIPATION_PARTICIPANT      = 'DUTY_PROJECT_PARTICIPATION_PARTICIPANT';
    public const DUTY_PROJECT_PARTICIPATION_LOAN_OFFICER     = 'DUTY_PROJECT_PARTICIPATION_LOAN_OFFICER';
    public const DUTY_PROJECT_PARTICIPATION_SECURITY_TRUSTEE = 'DUTY_PROJECT_PARTICIPATION_SECURITY_TRUSTEE';

    private const STATUS_NOT_CONSULTED = 0;
    private const STATUS_CONSULTED     = 10;
    private const STATUS_UNINTERESTED  = 20;

    private const DEFAULT_STATUS = self::STATUS_NOT_CONSULTED;

    private const DUTY_GROUP_PROJECT_PARTICIPATION_ORGANIZER = [
        self::DUTY_PROJECT_PARTICIPATION_ARRANGER,
        self::DUTY_PROJECT_PARTICIPATION_DEPUTY_ARRANGER,
        self::DUTY_PROJECT_PARTICIPATION_RUN,
        self::DUTY_PROJECT_PARTICIPATION_LOAN_OFFICER,
        self::DUTY_PROJECT_PARTICIPATION_SECURITY_TRUSTEE,
    ];

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Groups({"projectParticipation:list"})
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectParticipations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     *
     * @Groups({"projectParticipation:list"})
     */
    private $project;

    /**
     * @var Companies
     *
     * @Groups({"project:list"})
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", inversedBy="projectParticipations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"projectParticipation:list"})
     */
    private $company;

    /**
     * @var ProjectParticipationContact[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationContact", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"projectParticipation:list"})
     */
    private $projectParticipationContacts;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     *
     * @Groups({"projectParticipation:list"})
     */
    private $currentStatus = self::DEFAULT_STATUS;

    /**
     * @var Permission
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Permission", columnPrefix=false)
     */
    private $permission;

    /**
     * @var ProjectParticipationFee
     *
     * @ORM\OneToOne(targetEntity="ProjectParticipationFee", inversedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"projectParticipation:list"})
     */
    private $projectParticipationFee;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney", columnPrefix="invitation_")
     *
     * @Groups({"projectParticipation:list"})
     */
    private $invitationMoney;

    /**
     * @param Clients $addedBy
     * @param Money   $invitationAmount
     *
     * @throws Exception
     */
    public function __construct(Clients $addedBy, Money $invitationAmount = null)
    {
        $this->projectParticipationContacts = new ArrayCollection();
        $this->permission                   = new Permission();
        $this->added                        = new DateTimeImmutable();
        $this->addedBy                      = $addedBy;
        $this->invitationMoney              = $invitationAmount ?? new NullableMoney();
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
        return in_array(self::DUTY_PROJECT_PARTICIPATION_ARRANGER, $this->getRoles(), true);
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
     * @Groups({"project:list"})
     *
     * @return bool
     */
    public function hasOffer(): bool
    {
        return 0 < count($this->project->getProjectOffers(null, $this->company));
    }

    /**
     * @return bool
     */
    public function hasValidatedOffer(): bool
    {
        return 0 < count($this->project->getTrancheOffers([TrancheOffer::STATUS_ACCEPTED], $this->company));
    }

    /**
     * @Groups({"project:list"})
     *
     * @return bool
     */
    public function isNotInterested(): bool
    {
        return $this->currentStatus === static::STATUS_UNINTERESTED && !$this->hasOffer();
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
    public function setUninterested(): ProjectParticipation
    {
        if ($this->hasOffer()) {
            throw new DomainException('It is impossible to refuse after making an offer');
        }

        $this->currentStatus = static::STATUS_UNINTERESTED;

        return $this;
    }

    /**
     * @return ProjectParticipation
     */
    public function setConsulted(): ProjectParticipation
    {
        $this->currentStatus = ($this->currentStatus === static::STATUS_NOT_CONSULTED) ?
            static::STATUS_CONSULTED : $this->currentStatus;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOrganizer(): bool
    {
        return 0 < count(array_intersect($this->getRoles(), self::DUTY_GROUP_PROJECT_PARTICIPATION_ORGANIZER));
    }

    /**
     * @return bool
     */
    public function isParticipant(): bool
    {
        return false === $this->isOrganizer();
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

    /**
     * @return ProjectParticipationFee
     */
    public function getProjectParticipationFee(): ?ProjectParticipationFee
    {
        return $this->projectParticipationFee;
    }

    /**
     * @return Money
     */
    public function getInvitationMoney(): Money
    {
        return $this->invitationMoney;
    }

    /**
     * @param Money $invitationMoney
     */
    public function setInvitationMoney(Money $invitationMoney): void
    {
        $this->invitationMoney = $invitationMoney;
    }
}
