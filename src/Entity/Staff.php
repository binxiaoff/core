<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Interfaces\{StatusInterface, TraceableStatusAwareInterface};
use Unilend\Entity\Traits\{PublicizeIdentityTrait, RoleableTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"staff:read", "client:read", "client_status:read", "staffStatus:read", "timestampable:read", "traceableStatus:read"}},
 *     attributes={"pagination_client_enabled"=true},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {"security_post_denormalize": "is_granted('edit', object)", "denormalization_context": {"groups": {"staff:update", "role:write", "staffStatus:create"}}}
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {"groups": {"role:write", "staff:create"}}
 *         },
 *         "get"
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"company.groupName"})
 *
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_client", "id_company"})})
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"company", "client"}, message="Staff.client.unique")
 */
class Staff implements TraceableStatusAwareInterface
{
    use RoleableTrait {
        getRoles as private baseRolesGetter;
    }
    use TimestampableTrait;
    use PublicizeIdentityTrait;

    public const DUTY_STAFF_OPERATOR   = 'DUTY_STAFF_OPERATOR';
    public const DUTY_STAFF_MANAGER    = 'DUTY_STAFF_MANAGER';
    public const DUTY_STAFF_ADMIN      = 'DUTY_STAFF_ADMIN';
    public const DUTY_STAFF_AUDITOR    = 'DUTY_STAFF_AUDITOR';
    public const DUTY_STAFF_ACCOUNTANT = 'DUTY_STAFF_ACCOUNTANT';
    public const DUTY_STAFF_SIGNATORY  = 'DUTY_STAFF_SIGNATORY';

    public const SERIALIZER_GROUP_ADMIN_CREATE = 'staff:admin:create';

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company", inversedBy="staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     * })
     *
     * @Assert\NotBlank(message="Staff.company.empty")
     *
     * @Groups({"staff:read", "staff:create"})
     *
     * @MaxDepth(2)
     */
    private Company $company;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="staff", cascade={"persist", "refresh"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id", nullable=false)
     * })
     *
     * @Assert\NotBlank(message="Staff.client.empty")
     * @Assert\Valid
     *
     * @Groups({"staff:read", "staff:create"})
     *
     * @MaxDepth(1)
     */
    private Clients $client;

    /**
     * @var Collection|MarketSegment[]
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Entity\MarketSegment")
     *
     * @Groups({"staff:read", "staff:update", Staff::SERIALIZER_GROUP_ADMIN_CREATE})
     */
    private $marketSegments;

    /**
     * @var StaffStatus|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\StaffStatus", cascade={"persist"})
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
     * @ORM\OneToMany(targetEntity="Unilend\Entity\StaffStatus", mappedBy="staff", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     */
    private Collection $statuses;

    /**
     * Staff constructor.
     *
     * @param Company $company
     * @param Clients $client
     * @param Staff   $addedBy
     *
     * @throws Exception
     */
    public function __construct(Company $company, Clients $client, Staff $addedBy)
    {
        $this->marketSegments = new ArrayCollection();
        $this->added          = new DateTimeImmutable();
        $this->company        = $company;
        $this->client         = $client;
        $this->statuses       = new ArrayCollection();
        $this->setCurrentStatus(new StaffStatus($this, StaffStatus::STATUS_ACTIVE, $addedBy));
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @param Company $company
     *
     * @return Staff
     */
    public function setCompany(Company $company): Staff
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }

    /**
     * @param Clients $client
     *
     * @return Staff
     */
    public function setClient(Clients $client): Staff
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection|MarketSegment[]
     */
    public function getMarketSegments(): Collection
    {
        return $this->marketSegments;
    }

    /**
     * @param MarketSegment $marketSegment
     *
     * @return Staff
     */
    public function addMarketSegment(MarketSegment $marketSegment): Staff
    {
        if (false === $this->marketSegments->contains($marketSegment)) {
            $this->marketSegments[] = $marketSegment;
        }

        return $this;
    }

    /**
     * @param MarketSegment $marketSegment
     *
     * @return Staff
     */
    public function removeMarketSegment(MarketSegment $marketSegment): Staff
    {
        $this->marketSegments->removeElement($marketSegment);

        return $this;
    }

    /**
     * @param Collection|MarketSegment[] $marketSegments
     *
     * @return Staff
     */
    public function setMarketSegments($marketSegments): Staff
    {
        if (\is_array($marketSegments)) {
            $marketSegments = new ArrayCollection($marketSegments);
        }

        $this->marketSegments = $marketSegments;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(static::DUTY_STAFF_ADMIN);
    }

    /**
     * @return bool
     */
    public function isManager(): bool
    {
        return $this->hasRole(static::DUTY_STAFF_MANAGER);
    }

    /**
     * @return bool
     */
    public function isAuditor(): bool
    {
        return $this->hasRole(static::DUTY_STAFF_AUDITOR);
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getCurrentStatus() && StaffStatus::STATUS_ACTIVE === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @param StatusInterface|StaffStatus $currentStatus
     *
     * @return Staff
     */
    public function setCurrentStatus(StatusInterface $currentStatus): Staff
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @return bool
     */
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

    /**
     * @return StaffStatus
     */
    public function getCurrentStatus(): StaffStatus
    {
        return $this->currentStatus;
    }

    /**
     * @Groups({"staff:read"})
     *
     * @return array
     */
    public function getRoles(): array
    {
        return $this->baseRolesGetter();
    }
}
