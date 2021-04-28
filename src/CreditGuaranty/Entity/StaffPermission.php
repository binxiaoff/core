<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\PermissionTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Model\Bitmask;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:staffPermission:read", "staff:read", "user:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:staffPermission:write", "permission:write"}},
 *     itemOperations={
 *         "get",
 *         "patch"
 *     },
 *     collectionOperations={
 *         "post",
 *         "get"
 *     }
 * )
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

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Staff")
     * @ORM\JoinColumn(name="id_staff", nullable=false)
     *
     * @Groups({"creditGuaranty:staffPermission:read", "creditGuaranty:staffPermission:create"})
     */
    private Staff $staff;

    public function __construct(Staff $staff, int $permissions)
    {
        $this->staff       = $staff;
        $this->permissions = new Bitmask($permissions);
        $this->added       = new \DateTimeImmutable();
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
}
