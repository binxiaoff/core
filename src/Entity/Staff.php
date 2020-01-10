<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{RoleableTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"staff:read", "profile:read", "client_status:read", "role:read"}},
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "delete": {"security": "is_granted('delete', object)"},
 *         "patch": {"security_post_denormalize": "is_granted('edit', object)", "denormalization_context": {"groups": {"staff:update", "role:write"}}}
 *     },
 *     collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)", "denormalization_context": {"groups": {"staff:create", "role:write", "client:create"}}}
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"client"}, message="Staff.client.unique")
 */
class Staff
{
    use RoleableTrait;
    use TimestampableTrait;

    /** @deprecated Just for backward compatibility. Later, we will define a new role list for staff.*/
    public const ROLE_COMPANY_OWNER = 'ROLE_COMPANY_OWNER';

    public const DUTY_STAFF_OPERATOR   = 'DUTY_STAFF_OPERATOR';
    public const DUTY_STAFF_MANAGER    = 'DUTY_STAFF_MANAGER';
    public const DUTY_STAFF_ADMIN      = 'DUTY_STAFF_ADMIN';
    public const DUTY_STAFF_ACCOUNTANT = 'DUTY_STAFF_ACCOUNTANT';
    public const DUTY_STAFF_SIGNATORY  = 'DUTY_STAFF_SIGNATORY';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", inversedBy="staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     * })
     *
     * @Assert\NotBlank(message="Staff.company.empty")
     */
    private $company;

    /**
     * @var Clients
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Clients", inversedBy="staff", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     *
     * @Assert\NotBlank(message="Staff.client.empty")
     * @Assert\Expression(expression="this.getCompany().isStaffable(value)", message="Staff.client.staffable")
     * @Assert\Valid
     *
     * @Groups({"staff:read", "staff:create"})
     */
    private $client;

    /**
     * @var Collection|MarketSegment[]
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Entity\MarketSegment")
     *
     * @Groups({"staff:read", "staff:update", "staff:create"})
     */
    private $marketSegments;

    /**
     * Staff constructor.
     *
     * @param Companies             $company
     * @param Clients               $client
     * @param array|string[]|string $roles
     *
     * @throws Exception
     */
    public function __construct(Companies $company, Clients $client, $roles = [])
    {
        $this->marketSegments = new ArrayCollection();
        $this->added          = new DateTimeImmutable();
        $this->company        = $company;
        $this->client         = $client;
        $this->roles          = (array) $roles;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @return Staff
     */
    public function setCompany(Companies $company): Staff
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
        if (is_array($marketSegments)) {
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
}
