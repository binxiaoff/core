<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\PermissionTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Model\Bitmask;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:staffPermission:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:staffPermission:write", "permission:write"}},
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object)"},
 *         "patch": {"security_post_denormalize": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"}
 *     },
 *     collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)"},
 *         "get"
 *     }
 * )
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "staff:read",
 *             "user:read"
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_staff_permission")
 */
class StaffPermission
{
    use PublicizeIdentityTrait;
    use PermissionTrait;
    use TimestampableTrait;

    public const PERMISSION_READ_PROGRAM   = 1 << 0;
    public const PERMISSION_CREATE_PROGRAM = 1 << 1;
    public const PERMISSION_EDIT_PROGRAM   = 1 << 2;

    public const PERMISSION_READ_RESERVATION   = 1 << 3;
    public const PERMISSION_CREATE_RESERVATION = 1 << 4;
    public const PERMISSION_EDIT_RESERVATION   = 1 << 5;

    // The grant permission is in the same position as the corresponding permission to grant, so that we can easily check if a staff can grant a given permission.
    public const PERMISSION_GRANT_READ_PROGRAM   = 1 << 0;
    public const PERMISSION_GRANT_CREATE_PROGRAM = 1 << 1;
    public const PERMISSION_GRANT_EDIT_PROGRAM   = 1 << 2;

    public const PERMISSION_GRANT_READ_RESERVATION   = 1 << 3;
    public const PERMISSION_GRANT_CREATE_RESERVATION = 1 << 4;
    public const PERMISSION_GRANT_EDIT_RESERVATION   = 1 << 5;
    // A typical admin of program managing company (CASA) has 1111 (or 15 in decimal).
    public const MANAGING_COMPANY_ADMIN_PERMISSIONS = self::PERMISSION_GRANT_READ_PROGRAM
    | self::PERMISSION_GRANT_EDIT_PROGRAM
    | self::PERMISSION_GRANT_CREATE_PROGRAM
    | self::PERMISSION_GRANT_READ_RESERVATION;
    // A typical admin of participant has 111001 (or 57 in decimal)
    public const PARTICIPANT_ADMIN_PERMISSIONS = self::PERMISSION_GRANT_READ_PROGRAM
    | self::PERMISSION_GRANT_READ_RESERVATION
    | self::PERMISSION_GRANT_CREATE_RESERVATION
    | self::PERMISSION_GRANT_EDIT_RESERVATION;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Staff")
     * @ORM\JoinColumn(name="id_staff", nullable=false, unique=true)
     *
     * @Groups({"creditGuaranty:staffPermission:read", "creditGuaranty:staffPermission:write"})
     */
    private Staff $staff;

    /**
     * We must first have permissions to be able to grant permissions.
     *
     * @ORM\Column(type="bitmask")
     *
     * @Assert\Expression("(this.getPermissions().get() & this.getGrantPermissions().get()) === this.getGrantPermissions().get()")
     *
     * @Groups({"creditGuaranty:staffPermission:read"})
     */
    private Bitmask $grantPermissions;

    public function __construct(Staff $staff, Bitmask $permissions)
    {
        $this->staff            = $staff;
        $this->permissions      = $permissions;
        $this->grantPermissions = new Bitmask(0);
        $this->added            = new DateTimeImmutable();
    }

    public function getStaff(): Staff
    {
        return $this->staff;
    }

    /**
     * @Groups({"creditGuaranty:staffPermission:read"})
     */
    public function getStaffPermissions(): Bitmask
    {
        return $this->permissions;
    }

    /**
     * @Groups({"creditGuaranty:staffPermission:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @Groups({"creditGuaranty:staffPermission:read"})
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function getGrantPermissions(): Bitmask
    {
        return $this->grantPermissions;
    }

    /**
     * @param Bitmask|int $grantPermissions
     */
    public function setGrantPermissions($grantPermissions): StaffPermission
    {
        $this->grantPermissions = new Bitmask($grantPermissions);

        return $this;
    }
}
