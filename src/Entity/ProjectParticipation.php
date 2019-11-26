<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Unilend\Entity\Embeddable\{Money, NullableMoney, Permission};
use Unilend\Entity\Traits\{BlamableAddedTrait, RoleableTrait, TimestampableTrait};
use Unilend\Service\User\RealUserFinder;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {"normalization_context": {"groups": "projectParticipation:list"}},
 *         "post": {"denormalization_context": {"groups": "projectParticipation:create"}}
 *     },
 *     itemOperations={
 *         "get": {"normalization_context": {"groups": "projectParticipation:view"}},
 *         "delete": {"security": "is_granted('edit', object.getProject())"},
 *         "patch": {"security": "is_granted('edit', object.getProject())", "denormalization_context": {"groups": "projectParticipation:update"}}
 *     }
 * )
 * @ApiFilter("Unilend\Filter\ArrayFilter", properties={"roles"})
 * @ApiFilter("Unilend\Filter\CountFilter", properties={"project.projectParticipations.projectParticipationOffers"})
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

    public const DUTY_PROJECT_PARTICIPATION_ARRANGER         = 'arranger'; // The company who arranges a loan syndication.
    public const DUTY_PROJECT_PARTICIPATION_DEPUTY_ARRANGER  = 'deputy_arranger';
    public const DUTY_PROJECT_PARTICIPATION_RUN              = 'run'; // Responsable Unique de Notation, who gives a note on the borrower.
    public const DUTY_PROJECT_PARTICIPATION_PARTICIPANT      = 'participant';
    public const DUTY_PROJECT_PARTICIPATION_LOAN_OFFICER     = 'loan_officer';
    public const DUTY_PROJECT_PARTICIPATION_SECURITY_TRUSTEE = 'security_trustee';

    public const DUTY_GROUP_PROJECT_PARTICIPATION_ORGANIZER = [
        self::DUTY_PROJECT_PARTICIPATION_ARRANGER,
        self::DUTY_PROJECT_PARTICIPATION_DEPUTY_ARRANGER,
        self::DUTY_PROJECT_PARTICIPATION_RUN,
        self::DUTY_PROJECT_PARTICIPATION_LOAN_OFFICER,
        self::DUTY_PROJECT_PARTICIPATION_SECURITY_TRUSTEE,
    ];

    private const STATUS_NOT_CONSULTED = 0;
    private const STATUS_CONSULTED     = 10;
    private const STATUS_UNINTERESTED  = 20;

    private const DEFAULT_STATUS = self::STATUS_NOT_CONSULTED;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Groups({"projectParticipation:list", "project:view"})
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
     * @Groups({"projectParticipation:list", "projectParticipation:create", "projectParticipation:view"})
     *
     * @MaxDepth(1)
     */
    private $project;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", inversedBy="projectParticipations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"project:list", "project:view", "projectParticipation:list", "projectParticipation:create", "projectParticipation:view"})
     */
    private $company;

    /**
     * @var ProjectParticipationContact[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationContact", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"projectParticipation:view"})
     */
    private $projectParticipationContacts;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     *
     * @Groups({"projectParticipation:list", "projectParticipation:view"})
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
     * @ORM\OneToOne(targetEntity="ProjectParticipationFee", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"project:view", "projectParticipation:list", "projectParticipation:create", "projectParticipation:view"})
     */
    private $projectParticipationFee;

    /**
     * @var ProjectParticipationOffer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ProjectParticipationOffer", mappedBy="projectParticipation")
     */
    private $projectParticipationOffers;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney", columnPrefix="invitation_")
     *
     * @Groups({"project:view", "projectParticipation:list", "projectParticipation:create", "projectParticipation:view", "projectParticipation:update"})
     */
    private $invitationMoney;

    /**
     * @param Clients    $addedBy
     * @param Companies  $company
     * @param Project    $project
     * @param Money|null $invitationMoney
     *
     * @throws Exception
     */
    public function __construct(
        Clients $addedBy,
        Companies $company,
        Project $project,
        Money $invitationMoney = null
    ) {
        $this->projectParticipationContacts = new ArrayCollection();
        $this->permission                   = new Permission();
        $this->added                        = new DateTimeImmutable();
        $this->addedBy                      = $addedBy;
        $this->company                      = $company;
        $this->project                      = $project;
        $this->invitationMoney              = $invitationMoney ?? new NullableMoney();
        $this->projectParticipationOffers   = new ArrayCollection();
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
     * @return bool
     *
     * @Groups({"project:list"})
     */
    public function hasOffer(): bool
    {
        return 0 < count($this->projectParticipationOffers);
    }

    /**
     * @return bool
     */
    public function hasValidatedOffer(): bool
    {
        return 0 < count(
            $this->projectParticipationOffers->filter(
                static function (ProjectParticipationOffer $participationOffer) {
                    return $participationOffer->isAccepted();
                }
            )
        );
    }

    /**
     * @return ArrayCollection|ProjectParticipationOffer[]
     */
    public function getProjectParticipationOffers()
    {
        return $this->projectParticipationOffers;
    }

    /**
     * @return ProjectParticipationOffer|null
     *
     * @Groups({"project:view"})
     */
    public function getCurrentProjectParticipationOffer(): ?ProjectParticipationOffer
    {
        return $this->projectParticipationOffers->last() ?: null;
    }

    /**
     * @param ProjectParticipationOffer $projectOffer
     *
     * @return ProjectParticipation
     */
    public function setCurrentProjectParticipationOffer(ProjectParticipationOffer $projectOffer): ProjectParticipation
    {
        if ($this->getId() !== $projectOffer->getProjectParticipation()->getId()) {
            throw new InvalidArgumentException('Invalid offer');
        }

        $this->projectParticipationOffers->add($projectOffer);

        return $this;
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
     * @throws Exception
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
     * @return ProjectParticipationFee|null
     */
    public function getProjectParticipationFee(): ?ProjectParticipationFee
    {
        return $this->projectParticipationFee;
    }

    /**
     * @return NullableMoney|null
     */
    public function getInvitationMoney(): ?NullableMoney
    {
        return $this->invitationMoney->isValid() ? $this->invitationMoney : null;
    }

    /**
     * @param NullableMoney $nullableMoney
     *
     * @return NullableMoney
     */
    public function setInvitationMoney(NullableMoney $nullableMoney)
    {
        return $this->invitationMoney = $nullableMoney;
    }

    /**
     * @throws Exception
     *
     * @return Money|null
     */
    public function getOfferMoney(): ?Money
    {
        $projectOffer = $this->getCurrentProjectParticipationOffer();

        return  $projectOffer ? $projectOffer->getOfferMoney() : null;
    }

    /**
     * @return bool
     */
    public function isBiddable(): bool
    {
        foreach ([static::DUTY_PROJECT_PARTICIPATION_ARRANGER, static::DUTY_PROJECT_PARTICIPATION_PARTICIPANT] as $role) {
            if (in_array($role, $this->roles, true)) {
                return true;
            }
        }

        return false;
    }
}
