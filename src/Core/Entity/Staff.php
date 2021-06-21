<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Generator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Interfaces\StatusInterface;
use Unilend\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     attributes={
 *         "pagination_client_enabled": true
 *     },
 *     normalizationContext={"groups": {"staff:read", "user:read", "user_status:read", "staffStatus:read", "timestampable:read", "traceableStatus:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {"groups": {"staff:update", "staffStatus:create"}}
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"staff:create", "user:create"}}
 *         },
 *         "get"
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="core_staff",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_user", "id_team"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"team", "user"}, message="Core.Staff.user.unique")
 */
class Staff implements TraceableStatusAwareInterface
{
    use TimestampableTrait;
    use PublicizeIdentityTrait;

    public const SERIALIZER_GROUP_OWNER_READ = 'staff:owner:read';

    public const ID_ADMIN = 1;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User", inversedBy="staff", cascade={"persist", "refresh"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user", referencedColumnName="id", nullable=false)
     * })
     *
     * @Assert\NotBlank(message="Core.Staff.user.empty")
     * @Assert\Valid
     *
     * @Groups({"staff:read", "staff:create"})
     *
     * @MaxDepth(1)
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Team", inversedBy="staff")
     * @ORM\JoinColumn(name="id_team", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Groups({"staff:read", "staff:create", "staff:update"})
     */
    private Team $team;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"staff:read", "staff:create", "staff:update"})
     */
    private bool $manager;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Core\Entity\StaffStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"staff:read", "staff:update"})
     *
     * @MaxDepth(1)
     */
    private ?StaffStatus $currentStatus;

    /**
     * @var Collection|StaffStatus[]
     *
     * @Assert\Valid
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\StaffStatus", mappedBy="staff", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     */
    private Collection $statuses;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"staff:read", "staff:create", "staff:update"})
     */
    private bool $arrangementProjectCreationPermission;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"staff:read", "staff:create", "staff:update"})
     */
    private bool $agencyProjectCreationPermission;

    /**
     * @ORM\ManyToMany(targetEntity="Unilend\Core\Entity\CompanyGroupTag")
     * @ORM\JoinTable(name="core_staff_company_group_tag")
     *
     * @Groups({"staff:read", "staff:create", "staff:update"})
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Assert\All({
     *     @Assert\Choice(callback="getAvailableCompanyGroupTags")
     * })
     * @Assert\Unique
     */
    private Collection $companyGroupTags;

    /**
     * @throws Exception
     */
    public function __construct(User $user, Team $team, ?Staff $addedBy = null)
    {
        $this->companyGroupTags                     = new ArrayCollection();
        $this->added                                = new DateTimeImmutable();
        $this->user                                 = $user;
        $this->team                                 = $team;
        $this->manager                              = false;
        $this->statuses                             = new ArrayCollection();
        $this->arrangementProjectCreationPermission = false;
        $this->agencyProjectCreationPermission      = false;
        $this->setCurrentStatus(new StaffStatus($this, StaffStatus::STATUS_ACTIVE, $addedBy ?? $this));
    }

    /**
     * @Groups({"staff:read"})
     */
    public function getCompany(): Company
    {
        return $this->team->getCompany();
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Staff
    {
        $this->user = $user;

        return $this;
    }

    public function setManager(bool $manager): Staff
    {
        $this->manager = $manager;

        return $this;
    }

    public function isManager(): bool
    {
        return $this->manager;
    }

    public function isActive(): bool
    {
        return $this->getCurrentStatus() && StaffStatus::STATUS_ACTIVE === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @param StatusInterface|StaffStatus $currentStatus
     */
    public function setCurrentStatus(StatusInterface $currentStatus): Staff
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->getCurrentStatus() && StaffStatus::STATUS_ARCHIVED === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @return Collection|StaffStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    public function getCurrentStatus(): StaffStatus
    {
        return $this->currentStatus;
    }

    /**
     * @Groups({Staff::SERIALIZER_GROUP_OWNER_READ})
     */
    public function getActivatedModules(): array
    {
        return $this->getCompany()->getActivatedModules();
    }

    /**
     * @return CompanyGroupTag[]|array
     */
    public function getCompanyGroupTags(): array
    {
        return $this->companyGroupTags->toArray();
    }

    /**
     * @return CompanyGroupTag[]|array
     */
    public function getAvailableCompanyGroupTags(): array
    {
        return $this->getCompany()->getCompanyGroupTags();
    }

    public function addCompanyGroupTag(CompanyGroupTag $tag): Staff
    {
        $companyGroup = $this->getCompany()->getCompanyGroup();

        if (null === $companyGroup) {
            return $this;
        }

        if ($companyGroup !== $tag->getCompanyGroup()) {
            return $this;
        }

        if (false === $this->companyGroupTags->contains($tag)) {
            $this->companyGroupTags[] = $tag;
        }

        return $this;
    }

    public function removeCompanyGroupTag(CompanyGroupTag $tag): Staff
    {
        $this->companyGroupTags->removeElement($tag);

        return $this;
    }

    public function setTeam(Team $team): Staff
    {
        $this->team = $team;

        return $this;
    }

    public function hasArrangementProjectCreationPermission(): bool
    {
        return $this->arrangementProjectCreationPermission;
    }

    public function hasAgencyProjectCreationPermission(): bool
    {
        return $this->agencyProjectCreationPermission;
    }

    public function setArrangementProjectCreationPermission(bool $arrangementProjectCreationPermission): Staff
    {
        $this->arrangementProjectCreationPermission = $arrangementProjectCreationPermission;

        return $this;
    }

    public function setAgencyProjectCreationPermission(bool $agencyProjectCreationPermission): Staff
    {
        $this->agencyProjectCreationPermission = $agencyProjectCreationPermission;

        return $this;
    }

    public function isGrantedLogin(): bool
    {
        $company = $this->getCompany();

        if ($company->isCAGMember() && false === $company->hasSigned()) {
            return false;
        }

        return $this->isActive();
    }

    /**
     * @Groups({"staff:read"})
     */
    public function isAdmin(): bool
    {
        foreach ($this->getCompany()->getAdmins() as $admin) {
            if ($admin->getUser()->getPublicId() === $this->getUser()->getPublicId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Generator|Staff[]
     */
    public function getManagedStaff(): Generator
    {
        // Non-manager only get self in collection
        if (false === $this->manager) {
            yield $this;

            return;
        }

        // Manager get self in collection (he is present in his team) and other people (manager or not) in his team and its descendents
        /** @var Team $team */
        foreach ([$this->team, ...$this->team->getDescendents()] as $team) {
            yield from $team->getStaff();
        }
    }

    /**
     * @return Generator|User[]
     */
    public function getManagedUsers(): Generator
    {
        foreach ($this->getManagedStaff() as $staff) {
            yield $staff->getUser();
        }
    }

    /**
     * Assert there is only one staff for each company for a given user.
     *
     * @Assert\Callback
     */
    public function validateCompanyUnicity(ExecutionContextInterface $context)
    {
        foreach ($this->user->getStaff() as $staff) {
            if ($this->id !== $staff->getId() && $staff->getTeam()->getCompany() === $this->team->getCompany()) {
                $context->buildViolation('Staff.company.unicity')
                    ->atPath('team')
                    ->setInvalidValue($this->team)
                    ->addViolation()
                ;
            }
        }
    }
}
