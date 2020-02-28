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
 *     normalizationContext={"groups": {"staff:read", "client:read", "client_status:read"}},
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
 *         "post": {"security_post_denormalize": "is_granted('create', object)", "denormalization_context": {"groups": {"role:write", "staff:create", "client:create"}}}
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

    public const SERIALIZER_GROUP_ADMIN_READ   = 'staff:admin:read';
    public const SERIALIZER_GROUP_ADMIN_CREATE = 'staff:admin:create';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @Groups({"staff:create"})
     */
    private $company;

    /**
     * @var Clients
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Clients", inversedBy="staff", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id", nullable=false)
     * })
     *
     * @Assert\NotBlank(message="Staff.client.empty")
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
     * @Groups({Staff::SERIALIZER_GROUP_ADMIN_READ, "staff:update", Staff::SERIALIZER_GROUP_ADMIN_CREATE})
     */
    private $marketSegments;

    /**
     * Staff constructor.
     *
     * @param Company               $company
     * @param Clients               $client
     * @param array|string[]|string $roles
     *
     * @throws Exception
     */
    public function __construct(Company $company, Clients $client, $roles = [])
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

    /**
     * @return bool
     */
    public function isOperator(): bool
    {
        return $this->hasRole(static::DUTY_STAFF_OPERATOR);
    }
}
